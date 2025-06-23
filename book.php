<?php
session_start(); // Mulai sesi

// Pastikan user sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Arahkan ke halaman login jika belum login
    exit;
}

// Pastikan path ke koneksiDB.php sudah benar.
// Contoh: Jika book.php dan koneksiDB.php berada di folder yang sama:
include 'koneksiDB.php';
// Jika book.php berada di subfolder (misalnya 'public/') dan koneksiDB.php di root:
// include '../koneksiDB.php';


$pesan = ""; // Untuk menampilkan pesan sukses/error
$pesan_tipe = ""; // Untuk menentukan tipe pesan (sukses/error)
$harga_display = "Rp. 0,00"; // Untuk menampilkan harga dinamis di form

// --- Logika untuk Mendapatkan Hari dan Waktu yang Sudah Dipesan ---
$occupied_dates_info = []; // Menyimpan tanggal dan rentang waktu yang terisi
// Pastikan query ini sesuai dengan nama tabel dan kolom di database Anda
$sql_occupied = "SELECT tanggal_acara, waktu_mulai, waktu_selesai FROM booking WHERE status IN ('pending', 'dikonfirmasi') ORDER BY tanggal_acara ASC, waktu_mulai ASC";
$result_occupied = $conn->query($sql_occupied);
if ($result_occupied) {
    while ($row = $result_occupied->fetch_assoc()) {
        $tanggal = $row['tanggal_acara'];
        $mulai = date('H:i', strtotime($row['waktu_mulai']));
        $selesai = date('H:i', strtotime($row['waktu_selesai']));
        
        if (!isset($occupied_dates_info[$tanggal])) {
            $occupied_dates_info[$tanggal] = [];
        }
        $occupied_dates_info[$tanggal][] = "$mulai - $selesai";
    }
} else {
    // Log error ini jika terjadi masalah pada query
    error_log("Error fetching occupied dates: " . $conn->error);
    // Anda bisa tambahkan pesan ke user untuk debugging, tapi hapus saat production
    // $pesan = "Terjadi masalah saat memuat jadwal. Mohon coba lagi nanti. (" . $conn->error . ")";
    // $pesan_tipe = "error";
}

// --- Logika Pemrosesan Form Pemesanan ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Data dari form pemesanan
    $nama_customer = trim($_POST['nama_customer']);
    $email_customer = trim($_POST['email_customer']);
    $telepon_customer = trim($_POST['telepon_customer']);
    $alamat_customer = trim($_POST['alamat_customer']);
    $tanggal_acara = $_POST['tanggal_acara'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $waktu_selesai = $_POST['waktu_selesai'];
    $status_booking = 'pending'; // Status awal selalu pending

    // Ambil username dari sesi user yang login
    // Pastikan session 'username' ini ada dan benar dari proses login Anda
    $user_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'guest'; 

    // Validasi input dasar
    if (empty($nama_customer) || empty($email_customer) || empty($tanggal_acara) || empty($waktu_mulai) || empty($waktu_selesai)) {
        $pesan = "Mohon lengkapi semua data wajib (Nama, Email, Tanggal, Waktu Mulai, Waktu Selesai).";
        $pesan_tipe = "error";
    } else {
        // --- Validasi H-1 ---
        $tanggal_sekarang = new DateTime();
        $tanggal_acara_dt = new DateTime($tanggal_acara);
        $diff = $tanggal_sekarang->diff($tanggal_acara_dt);
        
        // Memastikan tanggal acara setidaknya H+1 dari tanggal hari ini
        // Atau jika hari ini, pastikan ada minimal 24 jam ke depan
        if ($tanggal_acara_dt <= $tanggal_sekarang) { // Jika tanggal acara adalah hari ini atau masa lalu
            $pesan = "Pemesanan harus dilakukan minimal H-1 sebelum tanggal acara.";
            $pesan_tipe = "error";
        } else {
            // --- Hitung Durasi dan Harga ---
            $mulai_timestamp = strtotime($tanggal_acara . ' ' . $waktu_mulai);
            $selesai_timestamp = strtotime($tanggal_acara . ' ' . $waktu_selesai);

            if ($selesai_timestamp < $mulai_timestamp) {
                // Jika waktu selesai melewati tengah malam, tambahkan 1 hari ke waktu selesai
                $selesai_timestamp = strtotime($tanggal_acara . ' ' . $waktu_selesai . ' +1 day');
            }

            $durasi_detik = $selesai_timestamp - $mulai_timestamp;
            $durasi_jam = round($durasi_detik / 3600); // Durasi dalam jam, dibulatkan ke terdekat

            $harga = 0;
            $promo_12_jam_harga = 17000000;
            $promo_fullday_harga = 30000000;
            $harga_per_jam = 1150000;

            // Cek Full Day (01:00 sampai 23:59)
            if ($waktu_mulai == "01:00:00" && $waktu_selesai == "23:59:00") {
                $harga = $promo_fullday_harga;
            } elseif ($durasi_jam >= 12) {
                // Promo 12 jam
                $harga = $promo_12_jam_harga;
                $sisa_jam = $durasi_jam - 12;
                if ($sisa_jam > 0) {
                    $harga += ($sisa_jam * $harga_per_jam);
                }
            } else {
                // Hitung per jam untuk durasi kurang dari 12 jam
                // Bulatkan ke atas jika ada sisa menit (misal 1.5 jam dihitung 2 jam)
                $harga = ceil($durasi_detik / 3600) * $harga_per_jam; 
            }

            // Simpan harga yang sudah dihitung ke variabel untuk ditampilkan
            $harga_display = "Rp " . number_format($harga, 0, ',', '.');


            // --- Cek Overlap Waktu di Tanggal yang Sama ---
            // Query ini mencari booking yang sudah ada yang beririsan dengan waktu yang diminta
            $stmt_overlap = $conn->prepare("SELECT COUNT(*) FROM booking WHERE tanggal_acara = ? AND (
                                            (waktu_mulai < ? AND waktu_selesai > ?) OR
                                            (waktu_mulai < ? AND waktu_selesai > ?)
                                        ) AND status IN ('pending', 'dikonfirmasi')");
            // Parameter untuk query overlap:
            // waktu_mulai < new_selesai AND waktu_selesai > new_mulai
            // Ini adalah kondisi umum untuk mengecek overlap dua interval waktu
            $stmt_overlap->bind_param("sssss", 
                $tanggal_acara, 
                $waktu_selesai, $waktu_mulai, // Kondisi 1 (booking lama dimulai sebelum selesai baru DAN booking lama berakhir setelah mulai baru)
                $waktu_mulai, $waktu_selesai  // Kondisi 2 (mulai baru dimulai sebelum selesai booking lama DAN selesai baru berakhir setelah mulai booking lama)
            );
            $stmt_overlap->execute();
            $stmt_overlap->bind_result($overlap_count);
            $stmt_overlap->fetch();
            $stmt_overlap->close();

            if ($overlap_count > 0) {
                $pesan = "Waktu yang Anda pilih pada tanggal " . date('d M Y', strtotime($tanggal_acara)) . " sudah terisi sebagian atau penuh. Mohon pilih tanggal atau waktu lain.";
                $pesan_tipe = "error";
            } else {
                $conn->begin_transaction(); // Mulai transaksi database

                try {
                    // 1. Cek apakah customer sudah ada berdasarkan email
                    $customer_id = null;
                    $stmt_check_customer = $conn->prepare("SELECT id FROM customer WHERE email = ?");
                    $stmt_check_customer->bind_param("s", $email_customer);
                    $stmt_check_customer->execute();
                    $stmt_check_customer->bind_result($customer_id);
                    $stmt_check_customer->fetch();
                    $stmt_check_customer->close();

                    if ($customer_id === null) {
                        // Customer belum ada, insert baru
                        $stmt_insert_customer = $conn->prepare("INSERT INTO customer (nama, email, telepon, alamat) VALUES (?, ?, ?, ?)");
                        $stmt_insert_customer->bind_param("ssss", $nama_customer, $email_customer, $telepon_customer, $alamat_customer);
                        if (!$stmt_insert_customer->execute()) {
                            throw new Exception("Gagal menyimpan data customer: " . $stmt_insert_customer->error);
                        }
                        $customer_id = $conn->insert_id; // Ambil ID customer yang baru di-generate
                        $stmt_insert_customer->close();
                    } else {
                        // Customer sudah ada, update data jika ada perubahan (opsional)
                        // Anda mungkin ingin menambahkan logika untuk hanya mengupdate jika ada perubahan
                        $stmt_update_customer = $conn->prepare("UPDATE customer SET nama = ?, telepon = ?, alamat = ? WHERE id = ?");
                        $stmt_update_customer->bind_param("sssi", $nama_customer, $telepon_customer, $alamat_customer, $customer_id);
                        if (!$stmt_update_customer->execute()) {
                            error_log("Warning: Gagal mengupdate data customer (ID: $customer_id): " . $stmt_update_customer->error);
                        }
                        $stmt_update_customer->close();
                    }

                    // 2. Masukkan data booking
                    $stmt_insert_booking = $conn->prepare("INSERT INTO booking (customer_id, user_username, tanggal_acara, waktu_mulai, waktu_selesai, harga, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert_booking->bind_param("isssssd", $customer_id, $user_username, $tanggal_acara, $waktu_mulai, $waktu_selesai, $harga, $status_booking);
                    if (!$stmt_insert_booking->execute()) {
                        throw new Exception("Gagal membuat pemesanan: " . $stmt_insert_booking->error);
                    }
                    $stmt_insert_booking->close();

                    $conn->commit(); // Commit transaksi jika semua berhasil
                    $pesan = "Pemesanan berhasil diajukan! Menunggu konfirmasi.";
                    $pesan_tipe = "sukses";

                    // Opsional: Redirect untuk mencegah form resubmission
                    // header("Location: book.php?success=true");
                    // exit;

                } catch (Exception $e) {
                    $conn->rollback(); // Rollback transaksi jika terjadi error
                    $pesan = "Terjadi kesalahan: " . $e->getMessage();
                    $pesan_tipe = "error";
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Gedung</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/responsive.css" rel="stylesheet" />
    <style>
        /* Optional: tambahkan sedikit CSS jika ada elemen yang belum terdefinisi di style.css Anda */
        .occupied-dates-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            padding: 10px;
            border-radius: 5px;
            background-color: #fcfcfc;
        }
        .occupied-dates-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .occupied-dates-list li {
            padding: 5px 0;
            border-bottom: 1px dashed #eee;
        }
        .occupied-dates-list li:last-child {
            border-bottom: none;
        }
        .list-group-item .badge {
            min-width: 70px; /* Lebar minimum untuk badge */
        }
    </style>
</head>
<body class="sub_page">

    <div class="hero_area">
        <div class="bg-box">
            <img src="images/hero-bg.jpg" alt="">
        </div>
        <header class="header_section">
            <div class="container">
                <nav class="navbar navbar-expand-lg custom_nav-container ">
                    <a class="navbar-brand" href="index.html">
                        <span>
                            <img src="images/scc.png" alt="">
                        </span>
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class=""> </span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav  mx-auto ">
                            <li class="nav-item">
                                <a class="nav-link" href="index.html">Home</a>
                            </li>
                            <li class="nav-item active">
                                <a class="nav-link" href="book.php">Booking <span class="sr-only">(current)</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="about.html">About</a>
                            </li>
                        </ul>
                        <div class="user_option">
                            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                                <a href="#" class="order_online">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin')): ?>
                                    <a href="admin/dashboard_admin.php" class="order_online">Dashboard Admin</a>
                                <?php endif; ?>
                                <a href="logout.php" class="order_online">Logout</a>
                            <?php else: ?>
                                <a href="login.php" class="order_online">Login</a>
                                <a href="register.php" class="order_online" >Sign in</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </nav>
            </div>
        </header>
    </div>
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Form Pemesanan Gedung
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <?php if (!empty($pesan)) : ?>
                        <div class="alert alert-<?php echo ($pesan_tipe == 'sukses' ? 'success' : 'danger'); ?> text-center mb-4">
                            <?php echo $pesan; ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-box mb-4">
                        <h4 class="text-center mb-3">Jadwal Gedung yang Sudah Terisi:</h4>
                        <?php if (!empty($occupied_dates_info)): ?>
                            <div class="occupied-dates-list">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($occupied_dates_info as $date => $times): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo date('d M Y', strtotime($date)); ?>:</strong>
                                            <ul class="list-inline mb-0">
                                                <?php foreach ($times as $time_range): ?>
                                                    <li class="list-inline-item">
                                                        <span class="badge badge-warning badge-pill"><?php echo htmlspecialchars($time_range); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <p class="text-muted text-center mt-2">Mohon pilih tanggal dan waktu di luar jadwal di atas.</p>
                        <?php else: ?>
                            <p class="text-center text-success">Tidak ada jadwal yang terisi saat ini. Semua tanggal dan waktu tersedia!</p>
                        <?php endif; ?>
                    </div>

                    <form action="book.php" method="POST">
                        <h3>Data Customer</h3>
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
                        <h3>Detail Pemesanan</h3>
                        <p class="text-info">
                            <strong>Aturan Pemesanan:</strong> Minimal H-1 sebelum acara.
                        </p>
                        <p class="text-info">
                            <strong>Promo Harga:</strong><br>
                            - Minimal 12 jam: Rp 17.000.000 (jam berikutnya Rp 1.150.000/jam, dibulatkan ke atas)<br>
                            - Full Day (01:00 - 23:59): Rp 30.000.000<br>
                            - Di luar promo: Rp 1.150.000/jam (dibulatkan ke atas untuk setiap jam)
                        </p>
                        
                        <div class="form-group">
                            <label for="tanggal_acara">Tanggal Acara:</label>
                            <input type="date" id="tanggal_acara" name="tanggal_acara" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
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
                            <label for="harga">Estimasi Harga:</label>
                            <input type="text" id="harga" name="harga_tampil" class="form-control" value="<?php echo $harga_display; ?>" readonly>
                            <input type="hidden" name="harga_real" value="<?php echo isset($harga) ? $harga : 0; ?>">
                        </div>

                        <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Ajukan Pemesanan</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
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
    <script src="js/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="https://unpkg.com/isotope-layout@3.0.4/dist/isotope.pkgd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/js/jquery.nice-select.min.js"></script>
    <script src="js/custom.js"></script>
    <script>
        // Set tahun di footer
        document.getElementById('displayYear').innerText = new Date().getFullYear();

        // JavaScript untuk menghitung harga secara dinamis (opsional, jika ingin real-time feedback)
        // Ini akan menghitung ulang harga saat user mengubah waktu mulai/selesai
        $(document).ready(function() {
            function calculatePrice() {
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
                $('#harga').val("Rp " + harga.toLocaleString('id-ID'));
                // Update hidden input for PHP (PHP akan melakukan perhitungan ulang untuk keamanan)
                $('input[name="harga_real"]').val(harga); 
            }

            // Panggil fungsi saat waktu mulai atau selesai berubah
            $('#waktu_mulai, #waktu_selesai').on('change', calculatePrice);

            // Panggil fungsi saat halaman dimuat jika ada nilai default
            calculatePrice();
        });
    </script>
</body>
</html>