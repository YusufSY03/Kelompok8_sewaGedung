<?php
session_start();
include '../koneksiDB.php'; // Sesuaikan path jika berbeda

// Periksa apakah pengguna sudah login dan memiliki role admin atau superadmin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("location: ../login.php"); // Arahkan kembali ke halaman login jika tidak memiliki akses
    exit;
}

$username_logged_in = htmlspecialchars($_SESSION['username']);
$role_logged_in = htmlspecialchars($_SESSION['role']);

$pesan = "";
$pesan_tipe = "";

// --- Logika untuk mengubah status booking ---
if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $booking_id = intval($_GET['id']);
    $new_status = $_GET['status'];

    if (in_array($new_status, ['pending', 'dikonfirmasi', 'selesai', 'dibatalkan'])) {
        $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        if ($stmt->execute()) {
            $pesan = "Status booking berhasil diperbarui.";
            $pesan_tipe = "success";
        } else {
            $pesan = "Error: " . $stmt->error;
            $pesan_tipe = "danger";
        }
        $stmt->close();
    } else {
        $pesan = "Status tidak valid.";
        $pesan_tipe = "danger";
    }
}

// --- Logika untuk Menambah Pemesanan (Offline/Manual Input) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_offline_booking'])) {
    $nama_customer = trim($_POST['nama_customer']);
    $email_customer = trim($_POST['email_customer']);
    $telepon_customer = trim($_POST['telepon_customer']);
    $alamat_customer = trim($_POST['alamat_customer']);
    $tanggal_acara = $_POST['tanggal_acara'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $waktu_selesai = $_POST['waktu_selesai'];
    $status_booking_manual = $_POST['status_booking']; // Status bisa ditentukan langsung
    $harga_real = floatval($_POST['harga_real']); // Harga dari hidden input JS

    if (empty($nama_customer) || empty($email_customer) || empty($tanggal_acara) || empty($waktu_mulai) || empty($waktu_selesai)) {
        $pesan = "Mohon lengkapi semua data wajib (Nama, Email, Tanggal, Waktu Mulai, Waktu Selesai) untuk pemesanan manual.";
        $pesan_tipe = "danger";
    } else {
        // --- Validasi H-1 (opsional untuk offline, tapi baiknya tetap ada) ---
        $tanggal_sekarang = new DateTime();
        $tanggal_acara_dt = new DateTime($tanggal_acara);

        // Jika Anda ingin admin bisa input pemesanan di hari yang sama atau masa lalu,
        // hapus atau modifikasi kondisi ini. Saat ini, saya biarkan untuk konsistensi.
        if ($tanggal_acara_dt <= $tanggal_sekarang) {
            $pesan = "Pemesanan harus dilakukan minimal H-1 sebelum tanggal acara.";
            $pesan_tipe = "danger";
        } else {
            // --- Hitung Durasi dan Harga (ulang dari PHP untuk keamanan) ---
            $mulai_timestamp = strtotime($tanggal_acara . ' ' . $waktu_mulai);
            $selesai_timestamp = strtotime($tanggal_acara . ' ' . $waktu_selesai);

            if ($selesai_timestamp < $mulai_timestamp) {
                $selesai_timestamp = strtotime($tanggal_acara . ' ' . $waktu_selesai . ' +1 day');
            }

            $durasi_detik = $selesai_timestamp - $mulai_timestamp;
            $durasi_jam = round($durasi_detik / 3600);

            $harga_hitung_ulang = 0;
            $promo_12_jam_harga = 17000000;
            $promo_fullday_harga = 30000000;
            $harga_per_jam = 1150000;

            if ($waktu_mulai == "01:00:00" && $waktu_selesai == "23:59:00") {
                $harga_hitung_ulang = $promo_fullday_harga;
            } elseif ($durasi_jam >= 12) {
                $harga_hitung_ulang = $promo_12_jam_harga;
                $sisa_jam = $durasi_jam - 12;
                if ($sisa_jam > 0) {
                    $harga_hitung_ulang += (ceil($sisa_jam) * $harga_per_jam);
                }
            } else {
                $harga_hitung_ulang = ceil($duration_detik / 3600) * $harga_per_jam;
            }
            
            // Verifikasi harga dari JS dengan harga hitung ulang PHP
            // Jika ada perbedaan signifikan, mungkin ada manipulasi atau bug JS
            if (abs($harga_hitung_ulang - $harga_real) > 100) { // Toleransi kecil untuk pembulatan
                error_log("Warning: Harga dari JS (" . $harga_real . ") berbeda jauh dari harga hitungan PHP (" . $harga_hitung_ulang . ")");
                // Anda bisa memilih untuk menggunakan harga hitung ulang PHP atau menampilkan error
                $harga_to_save = $harga_hitung_ulang; 
            } else {
                $harga_to_save = $harga_real;
            }


            // --- Cek Overlap Waktu di Tanggal yang Sama ---
            $stmt_overlap = $conn->prepare("SELECT COUNT(*) FROM booking WHERE tanggal_acara = ? AND (
                                            (waktu_mulai < ? AND waktu_selesai > ?) OR
                                            (waktu_mulai < ? AND waktu_selesai > ?)
                                        ) AND status IN ('pending', 'dikonfirmasi')");
            $stmt_overlap->bind_param("sssss", 
                $tanggal_acara, 
                $waktu_selesai, $waktu_mulai, 
                $waktu_mulai, $waktu_selesai
            );
            $stmt_overlap->execute();
            $stmt_overlap->bind_result($overlap_count);
            $stmt_overlap->fetch();
            $stmt_overlap->close();

            if ($overlap_count > 0) {
                $pesan = "Waktu yang dipilih pada tanggal " . date('d M Y', strtotime($tanggal_acara)) . " sudah terisi sebagian atau penuh. Mohon pilih tanggal atau waktu lain.";
                $pesan_tipe = "danger";
            } else {
                $conn->begin_transaction(); // Mulai transaksi database

                try {
                    // 1. Cek apakah customer sudah ada berdasarkan email
                    $customer_id = null;
                    $stmt_check_customer = $conn->prepare("SELECT id FROM customer WHERE email = ?");
                    $stmt_check_customer->bind_param("s", $email_customer);
                    $stmt_check_customer->execute();
                    $stmt_check_customer->bind_result($customer_id_found);
                    $stmt_check_customer->fetch();
                    $stmt_check_customer->close();
                    
                    if ($customer_id_found !== null) {
                        $customer_id = $customer_id_found; // Customer sudah ada, gunakan ID-nya
                        // Opsional: Update data customer jika ada perubahan pada nama/telepon/alamat
                        $stmt_update_customer = $conn->prepare("UPDATE customer SET nama = ?, telepon = ?, alamat = ? WHERE id = ?");
                        $stmt_update_customer->bind_param("sssi", $nama_customer, $telepon_customer, $alamat_customer, $customer_id);
                        $stmt_update_customer->execute(); // Jalankan update, error tidak perlu dilempar karena ini hanya update info
                        $stmt_update_customer->close();
                    } else {
                        // Customer belum ada, insert baru
                        $stmt_insert_customer = $conn->prepare("INSERT INTO customer (nama, email, telepon, alamat) VALUES (?, ?, ?, ?)");
                        $stmt_insert_customer->bind_param("ssss", $nama_customer, $email_customer, $telepon_customer, $alamat_customer);
                        if (!$stmt_insert_customer->execute()) {
                            throw new Exception("Gagal menyimpan data customer: " . $stmt_insert_customer->error);
                        }
                        $customer_id = $conn->insert_id; // Ambil ID customer yang baru di-generate
                        $stmt_insert_customer->close();
                    }

                    // 2. Masukkan data booking
                    $stmt_insert_booking = $conn->prepare("INSERT INTO booking (customer_id, user_username, tanggal_acara, waktu_mulai, waktu_selesai, harga, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert_booking->bind_param("isssssd", $customer_id, $username_logged_in, $tanggal_acara, $waktu_mulai, $waktu_selesai, $harga_to_save, $status_booking_manual);
                    if (!$stmt_insert_booking->execute()) {
                        throw new Exception("Gagal membuat pemesanan: " . $stmt_insert_booking->error);
                    }
                    $stmt_insert_booking->close();

                    $conn->commit(); // Commit transaksi jika semua berhasil
                    $pesan = "Pemesanan manual berhasil ditambahkan!";
                    $pesan_tipe = "success";

                } catch (Exception $e) {
                    $conn->rollback(); // Rollback transaksi jika terjadi error
                    $pesan = "Terjadi kesalahan: " . $e->getMessage();
                    $pesan_tipe = "danger";
                }
            }
        }
    }
}


// --- Ambil semua data pemesanan dari database (setelah potensi update/insert) ---
$sql = "SELECT b.id as booking_id, c.nama as customer_nama, c.email as customer_email, c.telepon as customer_telepon, 
               b.tanggal_acara, b.waktu_mulai, b.waktu_selesai, b.harga, b.status, b.created_at, b.user_username 
        FROM booking b
        JOIN customer c ON b.customer_id = c.id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);

$bookings = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - Admin</title>
    <link rel="stylesheet" type="text/css" href="../css/bootstrap.css" />
    <link href="../css/font-awesome.min.css" rel="stylesheet" />
    <link href="../css/style.css" rel="stylesheet" />
    <link href="../css/responsive.css" rel="stylesheet" />
    <style>
        /* Tambahan styling untuk tabel dan tombol status */
        .table-responsive {
            margin-top: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .status-dropdown {
            width: 120px; /* Lebar tetap untuk dropdown status */
        }
        .action-buttons button {
            margin-bottom: 5px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 60px;
            color: white;
        }
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .sidebar .active {
            background-color: #007bff;
        }
        .content {
            margin-left: 260px; /* Sesuaikan dengan lebar sidebar */
            padding: 20px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .card h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-row .form-group {
            flex: 1;
            margin-right: 15px;
        }
        .form-row .form-group:last-child {
            margin-right: 0;
        }
        .occupied-dates-list {
            max-height: 150px; /* Lebih kecil untuk di admin panel */
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            padding: 10px;
            border-radius: 5px;
            background-color: #fcfcfc;
            margin-bottom: 15px;
        }
        .occupied-dates-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .occupied-dates-list li {
            padding: 5px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .occupied-dates-list li:last-child {
            border-bottom: none;
        }
        .time-badges {
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .time-badges .badge {
            min-width: 90px;
            padding: 5px 8px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="dashboard_admin.php" class="active">Dashboard</a>
        <a href="kelola_pemesanan.php">Kelola Pemesanan</a>
        <a href="kelola_customer.php">Kelola Customer</a>
        <?php if ($role_logged_in === 'superadmin'): ?>
            <a href="kelola_user.php">Kelola User</a>
        <?php endif; ?>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="content">
        <div class="container-fluid">
            <h2 class="mt-4 mb-4">Kelola Pemesanan</h2>
            <p>Selamat datang, <?php echo $username_logged_in; ?> (Role: <?php echo $role_logged_in; ?>)</p>

            <?php if (!empty($pesan)) : ?>
                <div class="alert alert-<?php echo $pesan_tipe; ?> text-center">
                    <?php echo $pesan; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <h3>Input Pemesanan Manual (Offline)</h3>
                <form action="kelola_pemesanan.php" method="POST">
                    <input type="hidden" name="add_offline_booking" value="1">

                    <h4>Data Customer</h4>
                    <div class="form-group">
                        <label for="nama_customer">Nama Lengkap:</label>
                        <input type="text" id="nama_customer" name="nama_customer" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email_customer">Email:</label>
                        <input type="email" id="email_customer" name="email_customer" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="telepon_customer">Nomor Telepon (Opsional):</label>
                        <input type="text" id="telepon_customer" name="telepon_customer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="alamat_customer">Alamat (Opsional):</label>
                        <textarea id="alamat_customer" name="alamat_customer" class="form-control" rows="3"></textarea>
                    </div>

                    <hr>
                    <h4>Detail Pemesanan</h4>
                    <div class="form-group">
                        <label for="tanggal_acara">Tanggal Acara:</label>
                        <input type="date" id="tanggal_acara" name="tanggal_acara" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="waktu_mulai">Waktu Mulai:</label>
                            <input type="time" id="waktu_mulai" name="waktu_mulai" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="waktu_selesai">Waktu Selesai:</label>
                            <input type="time" id="waktu_selesai" name="waktu_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="status_booking">Status Pemesanan:</label>
                        <select id="status_booking" name="status_booking" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="dikonfirmasi">Dikonfirmasi</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="harga_display_offline">Estimasi Harga:</label>
                        <input type="text" id="harga_display_offline" class="form-control" value="Rp 0,00" readonly>
                        <input type="hidden" name="harga_real" id="harga_real_offline" value="0">
                    </div>

                    <button type="submit" class="btn btn-success mt-3 d-block mx-auto">Tambahkan Pemesanan</button>
                </form>
            </div>

            <div class="card">
                <h3>Daftar Semua Pemesanan</h3>
                <div class="table-responsive">
                    <?php if (!empty($bookings)): ?>
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID Booking</th>
                                    <th>Tanggal Booking</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Tanggal Acara</th>
                                    <th>Waktu</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Dipesan Oleh</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_nama']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_email']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_telepon']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($booking['tanggal_acara'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($booking['waktu_mulai'])) . ' - ' . date('H:i', strtotime($booking['waktu_selesai'])); ?></td>
                                        <td>Rp <?php echo number_format($booking['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <form action="" method="GET" class="form-inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?php echo $booking['booking_id']; ?>">
                                                <select name="status" class="form-control form-control-sm status-dropdown" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo ($booking['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="dikonfirmasi" <?php echo ($booking['status'] == 'dikonfirmasi') ? 'selected' : ''; ?>>Dikonfirmasi</option>
                                                    <option value="selesai" <?php echo ($booking['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                    <option value="dibatalkan" <?php echo ($booking['status'] == 'dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['user_username']); ?></td>
                                        <td class="action-buttons">
                                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center">Belum ada data pemesanan.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <footer class="footer_section">
        <div class="container">
            <div class="row">
                <div class="col-md-4 footer-col">
                    <div class="footer_contact">
                        <h4>Contact Us</h4>
                        <div class="contact_link_box">
                            <a href=""><i class="fa fa-map-marker" aria-hidden="true"></i><span>Universitas PGRI Sumatera Barat Convention Center, Gn. Pangilun, Kec. Padang Utara, Kota Padang, Sumatera Barat 25173</span></a>
                            <a href=""><i class="fa fa-phone" aria-hidden="true"></i><span>Call +01 1234567890</span></a>
                            <a href=""><i class="fa fa-envelope" aria-hidden="true"></i><span>scc@gmail.com</span></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 footer-col">
                    <div class="footer_detail">
                        <a href="" class="footer-logo">SCC</a>
                        <p>Pilihan yang tepat untuk Gedung Pesta Perkawinan, Seminar, Ujian, Wisuda dan Event Lainnya.</p>
                        <div class="footer_social">
                            <a href=""><i class="fa fa-facebook" aria-hidden="true"></i></a>
                            <a href=""><i class="fa fa-twitter" aria-hidden="true"></i></a>
                            <a href=""><i class="fa fa-linkedin" aria-hidden="true"></i></a>
                            <a href=""><i class="fa fa-instagram" aria-hidden="true"></i></a>
                            <a href=""><i class="fa fa-pinterest" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 footer-col">
                    <h4>Opening Hours</h4>
                    <p>Everyday</p>
                    <p>10.00 Am -10.00 Pm</p>
                </div>
            </div>
            <div class="footer-info">
                <p>&copy; <span id="displayYear"></span> All Rights Reserved By &copy; <span id="displayYear"></span> Distributed By <a href="https://themewagon.com/" target="_blank">ThemeWagon</a></p>
            </div>
        </div>
    </footer>
    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="../js/bootstrap.js"></script>
    <script src="../js/custom.js"></script>
    <script>
        document.getElementById('displayYear').innerText = new Date().getFullYear();

        // JavaScript untuk menghitung harga secara dinamis di form input manual
        $(document).ready(function() {
            function calculatePriceOffline() {
                var waktuMulai = $('#waktu_mulai').val();
                var waktuSelesai = $('#waktu_selesai').val();
                var harga = 0;

                if (waktuMulai && waktuSelesai) {
                    var dateDummy = '2000-01-01 '; // Tanggal dummy untuk perhitungan waktu
                    var start = new Date(dateDummy + waktuMulai);
                    var end = new Date(dateDummy + waktuSelesai);

                    // Handle overnight booking (e.g., mulai 22:00 selesai 02:00)
                    if (end < start) {
                        end.setDate(end.getDate() + 1); // Tambah 1 hari
                    }

                    var durationMs = end - start; // Durasi dalam milidetik
                    var durationHours = durationMs / (1000 * 60 * 60); // Durasi dalam jam

                    var promo12JamHarga = 17000000;
                    var promoFulldayHarga = 30000000;
                    var hargaPerJam = 1150000;

                    // Cek Full Day (01:00 sampai 23:59)
                    if (waktuMulai === "01:00" && waktuSelesai === "23:59") {
                        harga = promoFulldayHarga;
                    } else if (durationHours >= 12) {
                        harga = promo12JamHarga;
                        // Bulatkan ke atas sisa jam
                        var sisaJam = Math.ceil(durationHours - 12); 
                        if (sisaJam > 0) {
                            harga += (sisaJam * hargaPerJam);
                        }
                    } else {
                        // Bulatkan ke atas untuk setiap jam
                        harga = Math.ceil(durationHours) * hargaPerJam; 
                    }
                }

                // Update input harga display
                $('#harga_display_offline').val("Rp " + harga.toLocaleString('id-ID'));
                // Update hidden input for PHP (PHP akan melakukan perhitungan ulang untuk keamanan)
                $('#harga_real_offline').val(harga); 
            }

            // Panggil fungsi saat waktu mulai atau selesai berubah
            $('#waktu_mulai, #waktu_selesai').on('change', calculatePriceOffline);

            // Panggil fungsi saat halaman dimuat jika ada nilai default
            calculatePriceOffline(); // Panggil saat load untuk mengisi harga awal jika ada nilai
        });
    </script>
</body>
</html>