<?php
session_start();
// Periksa apakah pengguna sudah login dan memiliki role admin atau superadmin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("location: ../login.php"); // Arahkan kembali ke halaman login jika tidak memiliki akses
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);
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
        .sidebar { width: 250px; height: 100vh; background-color: #343a40; padding-top: 20px; color: white; }
        .sidebar a { color: white; padding: 10px 15px; text-decoration: none; display: block; }
        .sidebar a:hover { background-color: #495057; }
        .content { flex-grow: 1; padding: 20px; }
        .header_section .user_option { justify-content: flex-end; } /* Adjust alignment */
        .text-white.mr-3 { margin-right: 1rem !important; }
        .ml-2 { margin-left: 0.5rem !important; }
    </style>
</head>
<body>
    
        <header class="header_section">
            <div class="container">
                <nav class="navbar navbar-expand-lg custom_nav-container ">
                    <a class="navbar-brand" href="../index.php">
                        <span>
                            <img src="../images/scc.png" alt="">
                        </span>
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class=""> </span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav mx-auto">
                            </ul>
                        <div class="user_option">
                            <span class="text-white mr-3">Welcome, <?php echo $username; ?>! (<?php echo $role; ?>)</span>
                            <a href="../logout.php" class="order_online">Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </header>
        

    <div class="wrapper">
        <div class="sidebar">
            <h4 class="text-center mt-3">Admin Menu</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
                </li>
                <?php if ($_SESSION['role'] == 'superadmin') : ?>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_user.php">Kelola User</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_pemesanan.php">Kelola Pemesanan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_customer.php">Kelola Customer</a>
                </li>
                </ul>
        </div>
        <div class="content">
            <h1 class="mb-4">Admin Dashboard</h1>
            <p>Ini adalah halaman dashboard untuk role Admin dan Superadmin.</p>
            <p>Sebagai **<?php echo $role; ?>**, Anda memiliki akses ke fitur berikut:</p>
            <ul>
                <li>Kelola Pemesanan</li>
                <li>Kelola Customer</li>
                <?php if ($_SESSION['role'] == 'superadmin') : ?>
                <li>Kelola User (hanya untuk Superadmin)</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <footer class="footer_section">
        <div class="container">
            <div class="row">
                <div class="col-md-4 footer-col">
                    <div class="footer_contact">
                        <h4>Contact Us</h4>
                        <div class="contact_link_box">
                            <a href="#"><i class="fa fa-map-marker" aria-hidden="true"></i><span>Universitas PGRI Sumatera Barat Convention Center, Gn. Pangilun, Kec. Padang Utara, Kota Padang, Sumatera Barat 25173</span></a>
                            <a href="#"><i class="fa fa-phone" aria-hidden="true"></i><span>Call +01 1234567890</span></a>
                            <a href="#"><i class="fa fa-envelope" aria-hidden="true"></i><span>scc@gmail.com</span></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 footer-col">
                    <div class="footer_detail">
                        <a href="#" class="footer-logo">SCC</a>
                        <p>Pilihan yang tepat untuk Gedung Pesta Perkawinan, Seminar, Ujian, Wisuda dan Event Lainnya.</p>
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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="../js/bootstrap.js"></script>
    <script src="../js/custom.js"></script>
    <script>
        // Set current year for footer
        document.getElementById('displayYear').innerText = new Date().getFullYear();
    </script>
</body>
</html>