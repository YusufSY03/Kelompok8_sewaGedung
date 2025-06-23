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

// --- Ambil Data Agregat untuk Dashboard ---

// 1. Total Pendapatan dari Booking yang Dikonfirmasi
$sql_revenue = "SELECT SUM(harga) AS total_pendapatan_dikonfirmasi FROM booking WHERE status = 'dikonfirmasi'";
$result_revenue = $conn->query($sql_revenue);
$total_revenue = 0;
if ($result_revenue && $result_revenue->num_rows > 0) {
    $row = $result_revenue->fetch_assoc();
    $total_revenue = $row['total_pendapatan_dikonfirmasi'] ? $row['total_pendapatan_dikonfirmasi'] : 0;
}

// 2. Jumlah Total Customer Terdaftar
$sql_customers = "SELECT COUNT(id) AS total_customer FROM customer";
$result_customers = $conn->query($sql_customers);
$total_customers = 0;
if ($result_customers && $result_customers->num_rows > 0) {
    $row = $result_customers->fetch_assoc();
    $total_customers = $row['total_customer'];
}

// 3. Jumlah Booking yang Masih Pending
$sql_pending_bookings = "SELECT COUNT(id) AS total_booking_pending FROM booking WHERE status = 'pending'";
$result_pending_bookings = $conn->query($sql_pending_bookings);
$total_pending_bookings = 0;
if ($result_pending_bookings && $result_pending_bookings->num_rows > 0) {
    $row = $result_pending_bookings->fetch_assoc();
    $total_pending_bookings = $row['total_booking_pending'];
}

// Tambahan: Penggunaan FUNCTION GetDailyConfirmedRevenue
// PASTIKAN FUNCTION INI SUDAH DIBUAT DI DATABASE ANDA agar data tampil
$tanggal_hari_ini = date('Y-m-d');
$sql_daily_revenue_func = "SELECT GetDailyConfirmedRevenue('$tanggal_hari_ini') AS daily_revenue;";
$result_daily_revenue_func = $conn->query($sql_daily_revenue_func);
$daily_revenue = 0; // Default value
if ($result_daily_revenue_func && $result_daily_revenue_func->num_rows > 0) {
    $row_daily = $result_daily_revenue_func->fetch_assoc();
    $daily_revenue = $row_daily['daily_revenue'] ? $row_daily['daily_revenue'] : 0;
}


// --- New Aggregates from Database Objects ---

// 5. Total Records di VIEW "BookingDetails"
// Menunjukkan jumlah total pemesanan yang dapat dilihat melalui view.
$sql_view_count = "SELECT COUNT(*) AS total_view_records FROM BookingDetails";
$result_view_count = $conn->query($sql_view_count);
$total_view_records = 0;
if ($result_view_count && $result_view_count->num_rows > 0) {
    $row = $result_view_count->fetch_assoc();
    $total_view_records = $row['total_view_records'];
}

// 6. Jumlah Tanggal Acara Unik yang Diindeks (dari tabel booking)
// Menunjukkan keragaman tanggal acara yang ada di tabel booking, yang diuntungkan oleh index.
$sql_indexed_dates_count = "SELECT COUNT(DISTINCT tanggal_acara) AS unique_indexed_dates FROM booking";
$result_indexed_dates_count = $conn->query($sql_indexed_dates_count);
$unique_indexed_dates = 0;
if ($result_indexed_dates_count && $result_indexed_dates_count->num_rows > 0) {
    $row = $result_indexed_dates_count->fetch_assoc();
    $unique_indexed_dates = $row['unique_indexed_dates'];
}

// 7. Jumlah Booking yang Sudah Diperbarui (melalui Trigger `update_booking_timestamp`)
// Menunjukkan berapa banyak record booking yang memiliki nilai di kolom `updated_at`,
// yang diisi secara otomatis oleh trigger. Ini bisa menjadi indikator aktivitas update.
$sql_triggered_updates_count = "SELECT COUNT(*) AS total_triggered_updates FROM booking WHERE updated_at IS NOT NULL";
$result_triggered_updates_count = $conn->query($sql_triggered_updates_count);
$total_triggered_updates = 0;
if ($result_triggered_updates_count && $result_triggered_updates_count->num_rows > 0) {
    $row = $result_triggered_updates_count->fetch_assoc();
    $total_triggered_updates = $row['total_triggered_updates'];
}

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
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
            color: white;
            position: fixed; /* Menjadikan sidebar tetap */
            top: 0;
            left: 0;
            overflow-y: auto; /* Memungkinkan scrolling jika konten sidebar banyak */
        }
        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
            color: #ffbe33;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            margin-bottom: 10px;
        }
        .sidebar ul li a {
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            border-radius: 5px;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: #495057;
            color: #ffbe33;
        }
        .main-content {
            margin-left: 250px; /* Sesuaikan dengan lebar sidebar */
            padding: 20px;
            width: calc(100% - 250px); /* Lebar konten utama */
        }
        .header {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h3 {
            margin: 0;
            color: #333;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }

        /* Dashboard Cards - Used for Aggregate Ops and now for DB Objects */
        .dashboard-cards {
            display: grid; /* Menggunakan Grid Layout */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Membuat kolom responsif, minimal 280px, memenuhi sisa ruang */
            gap: 20px; /* Jarak antar kartu */
            margin-top: 30px;
            justify-content: center; /* Untuk menengahkan grid jika tidak memenuhi lebar penuh */
        }

        .dashboard-cards .card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s ease-in-out;
        }

        .dashboard-cards .card:hover {
            transform: translateY(-5px);
        }

        .dashboard-cards .card h4 {
            margin-top: 0;
            color: #333;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .dashboard-cards .card p {
            font-size: 2.2em; /* Large font for aggregate values */
            font-weight: bold;
            color: #007bff; /* Default color for values */
            margin-bottom: 0;
        }
        /* Specific colors for aggregate value cards */
        .dashboard-cards .card:nth-child(2) p { color: #28a745; } /* Green for total customer */
        .dashboard-cards .card:nth-child(3) p { color: #ffc107; } /* Yellow for pending booking */
        .dashboard-cards .card:nth-child(4) p { color: #17a2b8; } /* Teal for daily revenue */
        /* New colors for DB Object results */
        .dashboard-cards .card:nth-child(5) p { color: #6f42c1; } /* Purple for view records */
        .dashboard-cards .card:nth-child(6) p { color: #fd7e14; } /* Orange for unique indexed dates */
        .dashboard-cards .card:nth-child(7) p { color: #e83e8c; } /* Pink for triggered updates */


        /* Styles for DB Object Cards - adjust font size for description */
        .dashboard-cards .card .db-object-desc {
            font-size: 0.9em; /* Smaller font for descriptions in DB object cards */
            color: #555;
            min-height: 50px; /* Ensure consistent height for descriptions */
        }
        .dashboard-cards .card .text-muted {
            font-size: 0.8em;
            display: block; /* Make small text act as a block for spacing */
            margin-top: 10px;
        }


        /* Section for Database Objects Explanation */
        .db-objects-section {
            margin-top: 50px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .db-objects-section h3 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Styling for the database object categories/tables */
        .db-object-category {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fefefe;
        }

        .db-object-category h5 {
            color: #007bff;
            font-size: 1.3em;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        .db-info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .db-info-table th {
            background-color: #007bff;
            color: white;
            text-align: left;
            padding: 10px;
        }

        .db-info-table td {
            padding: 10px;
            vertical-align: top;
            border: 1px solid #dee2e6; /* Ensure borders between cells */
        }

        .db-object-category pre {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.85em;
            color: #333;
            margin-top: 15px;
        }
    </style>
</head>
<body class="sub_page">
    <div class="wrapper">
        <div class="sidebar">
            <h3>SCC Admin</h3>
            <ul>
                <li><a href="dashboard_admin.php" class="active"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                <li><a href="kelola_pemesanan.php"><i class="fa fa-calendar-check-o"></i> Kelola Pemesanan</a></li>
                <li><a href="kelola_customer.php"><i class="fa fa-users"></i> Kelola Customer</a></li>
                <?php if ($role === 'superadmin'): ?>
                    <li><a href="kelola_user.php"><i class="fa fa-user-secret"></i> Kelola User Admin</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h3>Selamat Datang, <?php echo $username; ?> (Role: <?php echo $role; ?>)</h3>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>

            <div class="dashboard-cards">
                <div class="card">
                    <h4>Total Pendapatan (Dikonfirmasi)</h4>
                    <p>Rp <?php echo number_format($total_revenue, 2, ',', '.'); ?></p>
                </div>
                <div class="card">
                    <h4>Total Customer Terdaftar</h4>
                    <p><?php echo $total_customers; ?></p>
                </div>
                <div class="card">
                    <h4>Booking Pending</h4>
                    <p><?php echo $total_pending_bookings; ?></p>
                </div>
                <div class="card">
                    <h4>Pendapatan Hari Ini (Dikonfirmasi)</h4>
                    <p>Rp <?php echo number_format($daily_revenue, 2, ',', '.'); ?></p>
                </div>

                <div class="card">
                    <h4>Total Booking (View)</h4>
                    <p><?php echo $total_view_records; ?></p>
                </div>
                <div class="card">
                    <h4>Tanggal Unik Booking (Indeks)</h4>
                    <p><?php echo $unique_indexed_dates; ?></p>
                </div>
                <div class="card">
                    <h4>Booking Otomatis Diperbarui (Trigger)</h4>
                    <p><?php echo $total_triggered_updates; ?></p>
                </div>
            </div>

            <div class="db-objects-section">
                <h3>Struktur Database Aplikasi Ini</h3>
                <p>Berikut adalah beberapa objek database yang telah diimplementasikan untuk meningkatkan efisiensi dan fungsionalitas sistem Anda:</p>

                <div class="db-object-category">
                    <h5>1. Tabel</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped db-info-table">
                            <thead>
                                <tr>
                                    <th>Nama Tabel</th>
                                    <th>Deskripsi Singkat</th>
                                    <th>Kolom Kunci (Contoh)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>user</code></td>
                                    <td>Menyimpan data pengguna sistem (admin, superadmin) yang dapat mengelola aplikasi.</td>
                                    <td>`username` (Primary Key)</td>
                                </tr>
                                <tr>
                                    <td><code>customer</code></td>
                                    <td>Menyimpan informasi lengkap dari setiap pelanggan yang melakukan pemesanan gedung.</td>
                                    <td>`id` (Primary Key), `email` (Unique)</td>
                                </tr>
                                <tr>
                                    <td><code>booking</code></td>
                                    <td>Menyimpan detail setiap transaksi pemesanan gedung, termasuk tanggal acara, waktu, harga, dan status.</td>
                                    <td>`id` (Primary Key), `tanggal_acara`, `status`</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="db-object-category">
                    <h5>2. VIEW (Tampilan)</h5>
                    <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="card">
                            <h4><code>BookingDetails</code></h4>
                            <p class="db-object-desc">Menggabungkan informasi dari tabel <code>booking</code>, <code>customer</code>, dan <code>user</code> untuk detail pemesanan lengkap.</p>
                            <small class="text-muted">Manfaat: Menyederhanakan query di "Kelola Pemesanan".</small>
                        </div>
                    </div>
                    <p><strong>Contoh Penggunaan SQL:</strong></p>
                    <pre><code>SELECT * FROM BookingDetails WHERE status = 'dikonfirmasi';</code></pre>
                </div>

                <div class="db-object-category">
                    <h5>3. INDEX (Indeks)</h5>
                    <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="card">
                            <h4><code>idx_tanggal_acara</code></h4>
                            <p class="db-object-desc">Indeks pada kolom <code>tanggal_acara</code> di tabel <code>booking</code>.</p>
                            <small class="text-muted">Manfaat: Mempercepat pencarian dan pengurutan data berdasarkan tanggal acara.</small>
                        </div>
                    </div>
                    <p><strong>Contoh Penggunaan SQL:</strong> (Indeks bekerja di belakang layar)</p>
                    <pre><code>SELECT * FROM booking WHERE tanggal_acara = '2025-12-25';</code></pre>
                </div>

                <div class="db-object-category">
                    <h5>4. TRIGGER (Pemicu)</h5>
                    <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="card">
                            <h4><code>update_booking_timestamp</code></h4>
                            <p class="db-object-desc">Trigger ini dieksekusi <code>BEFORE UPDATE</code> pada tabel <code>booking</code>.</p>
                            <small class="text-muted">Manfaat: Otomatis memperbarui kolom <code>updated_at</code> untuk jejak waktu data.</small>
                        </div>
                    </div>
                    <p><strong>Contoh Konseptual SQL:</strong></p>
                    <pre><code>DELIMITER //
CREATE TRIGGER update_booking_timestamp
BEFORE UPDATE ON booking
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW(); -- Memperbarui kolom updated_at secara otomatis
END //
DELIMITER ;
</code></pre>
                    <p><em>(Catatan: Jika trigger Anda memiliki fungsi yang berbeda, sesuaikan deskripsi di atas sesuai dengan trigger yang benar-benar Anda buat.)</em></p>
                </div>
            </div>

        </div>
    </div>

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