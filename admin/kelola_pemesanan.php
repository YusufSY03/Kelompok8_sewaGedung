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
            $pesan = "Gagal memperbarui status booking: " . $stmt->error;
            $pesan_tipe = "danger";
        }
        $stmt->close();
    } else {
        $pesan = "Status tidak valid.";
        $pesan_tipe = "danger";
    }
}

// Logika untuk menghapus booking
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM booking WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        $pesan = "Booking berhasil dihapus.";
        $pesan_tipe = "success";
    } else {
        $pesan = "Gagal menghapus booking: " . $stmt->error;
        $pesan_tipe = "danger";
    }
    $stmt->close();
}

// Logika untuk menambahkan booking (Offline)
if (isset($_POST['add_booking_offline'])) {
    $customer_id_offline = intval($_POST['customer_id_offline']);
    $tanggal_acara_offline = $_POST['tanggal_acara_offline'];
    $waktu_mulai_offline = $_POST['waktu_mulai_offline'];
    $waktu_selesai_offline = $_POST['waktu_selesai_offline'];
    // Ambil harga dari input tersembunyi yang dihitung oleh JavaScript
    $harga_offline = floatval(str_replace('.', '', $_POST['harga_real_offline'])); // Hapus format ribuan

    // Validasi input
    if (empty($customer_id_offline) || empty($tanggal_acara_offline) || empty($waktu_mulai_offline) || empty($waktu_selesai_offline) || empty($harga_offline)) {
        $pesan = "Semua kolom booking offline harus diisi!";
        $pesan_tipe = "danger";
    } else {
        // Asumsi user_username yang membuat booking offline adalah user yang sedang login
        $user_username = $username_logged_in;
        $status_default = 'dikonfirmasi'; // Booking offline langsung dikonfirmasi

        $stmt = $conn->prepare("INSERT INTO booking (customer_id, user_username, tanggal_acara, waktu_mulai, waktu_selesai, harga, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssis", $customer_id_offline, $user_username, $tanggal_acara_offline, $waktu_mulai_offline, $waktu_selesai_offline, $harga_offline, $status_default);

        if ($stmt->execute()) {
            $pesan = "Booking offline berhasil ditambahkan dan dikonfirmasi!";
            $pesan_tipe = "success";
        } else {
            $pesan = "Gagal menambahkan booking offline: " . $stmt->error;
            $pesan_tipe = "danger";
        }
        $stmt->close();
    }
}


// Ambil data booking beserta nama customer
$sql = "SELECT b.id, c.nama AS customer_nama, b.tanggal_acara, b.waktu_mulai, b.waktu_selesai, b.harga, b.status, b.created_at, b.user_username
        FROM booking b
        JOIN customer c ON b.customer_id = c.id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);
$bookings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Ambil daftar customer untuk dropdown
$sql_customers_dropdown = "SELECT id, nama FROM customer ORDER BY nama ASC";
$result_customers_dropdown = $conn->query($sql_customers_dropdown);
$customers_dropdown = [];
if ($result_customers_dropdown->num_rows > 0) {
    while ($row = $result_customers_dropdown->fetch_assoc()) {
        $customers_dropdown[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - SCC Admin</title>
    <link rel="stylesheet" type="text/css" href="../css/bootstrap.css" />
    <link href="../css/font-awesome.min.css" rel="stylesheet" />
    <link href="../css/style.css" rel="stylesheet" />
    <link href="../css/responsive.css" rel="stylesheet" />
    <style>
        body { background-color: #f8f9fa; }
        .wrapper { display: flex; }
        .sidebar { width: 250px; height: 100vh; background-color: #343a40; padding-top: 20px; color: white; position: fixed; } /* Fixed sidebar */
        .sidebar a { color: white; padding: 10px 15px; text-decoration: none; display: block; }
        .sidebar a:hover { background-color: #007bff; }
        .content { flex-grow: 1; padding: 20px; margin-left: 250px; } /* Adjust content margin for fixed sidebar */
        .navbar { background-color: #ffffff; border-bottom: 1px solid #dee2e6; }
        .info-section { /* Container umum untuk form dan tabel */
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-section h4 {
            color: #343a40;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        /* Tambahan styling untuk pesan notifikasi */
        .alert-custom {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            font-weight: bold;
        }
        .alert-success-custom {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger-custom {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .table thead th {
            background-color: #007bff;
            color: white;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 123, 255, 0.05);
        }
        .btn-status {
            padding: 0.25rem 0.6rem;
            font-size: 0.8rem;
        }
        .status-badge {
            display: inline-block;
            padding: .35em .65em;
            font-size: .75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
        }
        .status-pending { background-color: #ffc107; color: #343a40; } /* Warning */
        .status-dikonfirmasi { background-color: #28a745; } /* Success */
        .status-selesai { background-color: #007bff; } /* Primary */
        .status-dibatalkan { background-color: #dc3545; } /* Danger */
        .btn-custom-add {
            background-color: #28a745;
            color: white;
        }
        .btn-custom-add:hover {
            background-color: #218838;
            color: white;
        }
    </style>
</head>
<body class="sub_page">

    <div class="hero_area">
        <header class="header_section">
            <div class="container-fluid">
                <nav class="navbar navbar-expand-lg custom_nav-container ">
                    <a class="navbar-brand" href="dashboard_admin.php">
                        <span>SCC Admin</span>
                    </a>

                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class=""> </span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav  mx-auto">
                        </ul>
                        <div class="user_option">
                            <a href="#" class="user_link">
                                <i class="fa fa-user" aria-hidden="true"></i> <?php echo $username_logged_in; ?> (<?php echo ucfirst($role_logged_in); ?>)
                            </a>
                            <a href="../logout.php" class="order_online">
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </header>
    </div>

    <div class="wrapper">
        <nav id="sidebar" class="sidebar">
            <ul class="list-unstyled components">
                <li><a href="dashboard_admin.php">Dashboard</a></li>
                <li><a href="kelola_pemesanan.php">Kelola Pemesanan</a></li>
                <li><a href="kelola_customer.php">Kelola Customer</a></li>
                <?php if ($role_logged_in == 'superadmin'): ?>
                <li><a href="kelola_user.php">Kelola User</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div id="content" class="content">
            <h2 class="mb-4">Kelola Pemesanan</h2>

            <?php if (!empty($pesan)): ?>
                <div class="alert alert-dismissible fade show alert-custom alert-<?php echo $pesan_tipe; ?>-custom" role="alert">
                    <?php echo $pesan; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="info-section mt-4">
                <h4>Tambah Pemesanan Offline</h4>
                <form action="kelola_pemesanan.php" method="POST" class="mb-4">
                    <div class="form-group">
                        <label for="customer_id_offline">Pilih Customer:</label>
                        <select class="form-control" id="customer_id_offline" name="customer_id_offline" required>
                            <option value="">-- Pilih Customer --</option>
                            <?php foreach ($customers_dropdown as $customer): ?>
                                <option value="<?php echo htmlspecialchars($customer['id']); ?>">
                                    <?php echo htmlspecialchars($customer['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="tanggal_acara_offline">Tanggal Acara:</label>
                            <input type="date" class="form-control" id="tanggal_acara_offline" name="tanggal_acara_offline" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="waktu_mulai_offline">Waktu Mulai:</label>
                            <input type="time" class="form-control" id="waktu_mulai_offline" name="waktu_mulai_offline" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="waktu_selesai_offline">Waktu Selesai:</label>
                            <input type="time" class="form-control" id="waktu_selesai_offline" name="waktu_selesai_offline" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="harga_display_offline">Harga (Otomatis Dihitung):</label>
                        <input type="text" class="form-control" id="harga_display_offline" readonly>
                        <input type="hidden" id="harga_real_offline" name="harga_real_offline">
                    </div>
                    <button type="submit" name="add_booking_offline" class="btn btn-custom-add">Tambahkan Booking</button>
                </form>
            </div>

            <div class="info-section mt-4">
                <h4>Daftar Pemesanan</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID Booking</th>
                                <th>Customer</th>
                                <th>User Input</th>
                                <th>Tanggal Acara</th>
                                <th>Waktu</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Dibuat Pada</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bookings)): ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_nama']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['user_username']); ?></td>
                                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($booking['tanggal_acara']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('H:i', strtotime($booking['waktu_mulai']))) . ' - ' . htmlspecialchars(date('H:i', strtotime($booking['waktu_selesai']))); ?></td>
                                        <td>Rp <?php echo number_format($booking['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($booking['created_at']))); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-info dropdown-toggle btn-status" type="button" id="dropdownStatus<?php echo $booking['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Ubah Status
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownStatus<?php echo $booking['id']; ?>">
                                                    <a class="dropdown-item" href="kelola_pemesanan.php?action=update_status&id=<?php echo $booking['id']; ?>&status=pending">Pending</a>
                                                    <a class="dropdown-item" href="kelola_pemesanan.php?action=update_status&id=<?php echo $booking['id']; ?>&status=dikonfirmasi">Dikonfirmasi</a>
                                                    <a class="dropdown-item" href="kelola_pemesanan.php?action=update_status&id=<?php echo $booking['id']; ?>&status=selesai">Selesai</a>
                                                    <a class="dropdown-item" href="kelola_pemesanan.php?action=update_status&id=<?php echo $booking['id']; ?>&status=dibatalkan">Dibatalkan</a>
                                                </div>
                                            </div>
                                            <a href="kelola_pemesanan.php?action=delete&id=<?php echo htmlspecialchars($booking['id']); ?>"
                                               class="btn btn-sm btn-danger mt-1" onclick="return confirm('Apakah Anda yakin ingin menghapus pemesanan ini?');">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data pemesanan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>


    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="../js/bootstrap.js"></script>
    <script src="../js/custom.js"></script>
    <script>
        document.getElementById('displayYear').innerText = new Date().getFullYear();

        // JavaScript untuk perhitungan harga offline
        $(document).ready(function() {
            function calculatePriceOffline() {
                var tanggalAcara = $('#tanggal_acara_offline').val();
                var waktuMulai = $('#waktu_mulai_offline').val();
                var waktuSelesai = $('#waktu_selesai_offline').val();
                var harga = 0;

                if (tanggalAcara && waktuMulai && waktuSelesai) {
                    var startDateTime = new Date(tanggalAcara + 'T' + waktuMulai);
                    var endDateTime = new Date(tanggalAcara + 'T' + waktuSelesai);

                    // Handle case where end time is on the next day (e.g., 22:00 to 02:00)
                    if (endDateTime < startDateTime) {
                        endDateTime.setDate(endDateTime.getDate() + 1);
                    }

                    var durationMs = endDateTime - startDateTime;
                    var durationHours = durationMs / (1000 * 60 * 60);

                    var promoFulldayHarga = 20000000;
                    var promo12JamHarga = 12000000;
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
            $('#tanggal_acara_offline, #waktu_mulai_offline, #waktu_selesai_offline').on('change', calculatePriceOffline);

            // Panggil fungsi saat halaman dimuat jika ada nilai default
            calculatePriceOffline(); // Panggil saat load untuk mengisi harga awal jika ada nilai
        });
    </script>
</body>
</html>