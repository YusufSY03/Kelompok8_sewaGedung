<?php
session_start();
// Periksa apakah pengguna sudah login dan memiliki role admin atau superadmin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("location: ../login.php"); // Arahkan kembali ke halaman login jika tidak memiliki akses
    exit;
}

include '../koneksiDB.php'; // Pastikan path ini benar sesuai lokasi file koneksiDB.php Anda

$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// --- Ambil Data Agregat untuk Dashboard (SUM, MAX, COUNT) ---
$sum_harga_all_bookings = 0;
$max_harga_all_bookings = 0;
$total_bookings = 0;

// Query untuk mendapatkan SUM, MAX harga, dan COUNT dari semua booking
$sql_aggregate_all = "SELECT SUM(harga) AS total_harga_all, MAX(harga) AS max_harga_all, COUNT(id) AS total_all_bookings FROM booking";
$result_aggregate_all = $conn->query($sql_aggregate_all);

if ($result_aggregate_all && $result_aggregate_all->num_rows > 0) {
    $row_aggregate_all = $result_aggregate_all->fetch_assoc();
    $sum_harga_all_bookings = $row_aggregate_all['total_harga_all'] ? $row_aggregate_all['total_harga_all'] : 0;
    $max_harga_all_bookings = $row_aggregate_all['max_harga_all'] ? $row_aggregate_all['max_harga_all'] : 0;
    $total_bookings = $row_aggregate_all['total_all_bookings'] ? $row_aggregate_all['total_all_bookings'] : 0;
}

// --- Data dari VIEW v_pending_bookings ---
$pending_bookings = [];
$sql_pending_bookings = "SELECT booking_id, customer_nama, tanggal_acara, waktu_mulai, waktu_selesai, harga, status, created_at FROM v_pending_bookings ORDER BY created_at DESC LIMIT 5"; // Ambil 5 terbaru
$result_pending_bookings = $conn->query($sql_pending_bookings);
if ($result_pending_bookings && $result_pending_bookings->num_rows > 0) {
    while ($row = $result_pending_bookings->fetch_assoc()) {
        $pending_bookings[] = $row;
    }
}

// --- Data untuk contoh penggunaan FUNCTION CalculateBookingDurationHours ---
$bookings_with_duration = [];
$sql_bookings_duration = "SELECT
                            b.id AS booking_id,
                            c.nama AS customer_nama,
                            b.tanggal_acara,
                            b.waktu_mulai,
                            b.waktu_selesai,
                            CalculateBookingDurationHours(b.waktu_mulai, b.waktu_selesai) AS durasi_jam
                          FROM booking b
                          JOIN customer c ON b.customer_id = c.id
                          ORDER BY b.tanggal_acara DESC LIMIT 5"; // Ambil 5 booking terbaru dengan durasi
$result_bookings_duration = $conn->query($sql_bookings_duration);
if ($result_bookings_duration && $result_bookings_duration->num_rows > 0) {
    while ($row = $result_bookings_duration->fetch_assoc()) {
        $bookings_with_duration[] = $row;
    }
}

// --- Data untuk menampilkan daftar TRIGGERS ---
$database_triggers = [];
$sql_show_triggers = "SHOW TRIGGERS;";
$result_show_triggers = $conn->query($sql_show_triggers);

if ($result_show_triggers && $result_show_triggers->num_rows > 0) {
    while ($row = $result_show_triggers->fetch_assoc()) {
        $database_triggers[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SCC</title>
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
        .navbar { /* Style for the header/navbar */
            background-color: #2c3e50; /* Darker blue-gray */
            border-bottom: 3px solid #e74c3c; /* Red accent */
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar .navbar-brand {
            color: #ecf0f1; /* Light gray for brand text */
            font-weight: bold;
            font-size: 1.8rem;
            letter-spacing: 1px;
        }
        .navbar .navbar-brand span {
            color: #f1c40f; /* Yellow accent for 'Admin' */
        }
        .navbar .user_option .user_link {
            color: #ecf0f1; /* Light gray for user link */
        }
        .navbar .user_option .order_online {
            background-color: #e74c3c; /* Red for logout button */
            color: white;
            border-radius: 5px;
            padding: 8px 15px;
            transition: background-color 0.3s ease;
        }
        .navbar .user_option .order_online:hover {
            background-color: #c0392b; /* Darker red on hover */
        }

        .stats-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stats-card h5 {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .stats-card .count {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .info-section { /* Menggunakan info-section sebagai container umum untuk konten dashboard */
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
                                <i class="fa fa-user" aria-hidden="true"></i> <?php echo $username; ?> (<?php echo ucfirst($role); ?>)
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
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                <li><a href="kelola_user.php">Kelola User</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div id="content" class="content">
            <h2 class="mb-4">Dashboard Admin</h2>

            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>Total Harga Semua Booking</h5>
                        <div class="count">Rp <?php echo number_format($sum_harga_all_bookings, 0, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>Harga Booking Tertinggi</h5>
                        <div class="count">Rp <?php echo number_format($max_harga_all_bookings, 0, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>Jumlah Semua Booking</h5>
                        <div class="count"><?php echo $total_bookings; ?></div>
                    </div>
                </div>
            </div>

            <div class="info-section mt-4">
                <h4>Selamat Datang, <?php echo $username; ?>!</h4>
                <p>Anda login sebagai **<?php echo ucfirst($role); ?>**.</p>
                <p>Gunakan menu di sisi kiri untuk navigasi ke fitur pengelolaan data.</p>
                <p>Objek database **TRIGGER `trg_booking_updated_at`** berjalan di latar belakang untuk otomatis mengupdate waktu modifikasi setiap pemesanan.</p>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="info-section">
                        <h4>Pemesanan Menunggu Konfirmasi (VIEW: `v_pending_bookings`)</h4>
                        <?php if (!empty($pending_bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_bookings as $pb): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($pb['booking_id']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($pb['tanggal_acara']))); ?></td>
                                                <td><?php echo htmlspecialchars(date('H:i', strtotime($pb['waktu_mulai']))) . ' - ' . htmlspecialchars(date('H:i', strtotime($pb['waktu_selesai']))); ?></td>
                                                <td>Rp <?php echo number_format($pb['harga'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Menampilkan 5 pemesanan pending terbaru.</small>
                        <?php else: ?>
                            <p class="text-success">Tidak ada pemesanan yang berstatus 'pending'.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-section">
                        <h4>Durasi Pemesanan Terbaru (FUNCTION: `CalculateBookingDurationHours`)</h4>
                        <?php if (!empty($bookings_with_duration)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID Booking</th>
                                            <th>Customer</th>
                                            <th>Tanggal</th>
                                            <th>Durasi (Jam)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings_with_duration as $bd): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bd['booking_id']); ?></td>
                                                <td><?php echo htmlspecialchars($bd['customer_nama']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($bd['tanggal_acara']))); ?></td>
                                                <td><?php echo htmlspecialchars(number_format($bd['durasi_jam'], 2)); ?> jam</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Menampilkan 5 pemesanan terbaru beserta durasinya.</small>
                        <?php else: ?>
                            <p class="text-info">Tidak ada data pemesanan untuk menampilkan durasi.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="info-section">
                        <h4>Daftar Triggers Database</h4>
                        <?php if (!empty($database_triggers)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Trigger Name</th>
                                            <th>Event</th>
                                            <th>Table</th>
                                            <th>Timing</th>
                                            <th>Statement</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($database_triggers as $trigger): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($trigger['Trigger']); ?></td>
                                                <td><?php echo htmlspecialchars($trigger['Event']); ?></td>
                                                <td><?php echo htmlspecialchars($trigger['Table']); ?></td>
                                                <td><?php echo htmlspecialchars($trigger['Timing']); ?></td>
                                                <td><pre style="white-space: pre-wrap; font-size: 0.85em; margin-bottom: 0;"><?php echo htmlspecialchars($trigger['Statement']); ?></pre></td>
                                                <td><?php echo htmlspecialchars($trigger['Created']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-info">Tidak ada trigger yang ditemukan di database ini.</p>
                        <?php endif; ?>
                        <small class="text-muted">Ini menampilkan semua trigger yang terdaftar di database `db_gedung`. Untuk detail lebih lanjut atau untuk mengedit, gunakan alat manajemen database seperti phpMyAdmin atau MySQL Workbench.</small>
                    </div>
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
                            <a href="#">
                                <i class="fa fa-map-marker" aria-hidden="true"></i>
                                <span>
                                    Location
                                </span>
                            </a>
                            <a href="#">
                                <i class="fa fa-phone" aria-hidden="true"></i>
                                <span>
                                    Call +01 1234567890
                                </span>
                            </a>
                            <a href="#">
                                <i class="fa fa-envelope" aria-hidden="true"></i>
                                <span>
                                    demo@gmail.com
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 footer-col">
                    <div class="footer_detail">
                        <a href="" class="footer_logo">
                            Feane
                        </a>
                        <p>
                            Necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with
                        </p>
                        <div class="footer_social">
                            <a href="#"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa fa-instagram" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa fa-pinterest" aria-hidden="true"></i></a>
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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="../js/bootstrap.js"></script>
    <script src="../js/custom.js"></script>
    <script>
        // Set current year for footer (if custom.js doesn't handle it)
        document.getElementById('displayYear').innerText = new Date().getFullYear();
    </script>
</body>
</html>