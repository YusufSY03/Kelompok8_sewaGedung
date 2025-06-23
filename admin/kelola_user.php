<?php
session_start();
include '../koneksiDB.php'; // Sesuaikan path

// Periksa apakah pengguna sudah login dan memiliki role superadmin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'superadmin') {
    header("location: ../login.php"); // Arahkan kembali ke halaman login jika tidak memiliki akses
    exit;
}

$username_logged_in = htmlspecialchars($_SESSION['username']);
$role_logged_in = htmlspecialchars($_SESSION['role']);

$pesan = "";
$pesan_tipe = ""; // Untuk menentukan tipe pesan (success/danger)

// Logika untuk menambahkan user
if (isset($_POST['add_user'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];
    $new_role = $_POST['role'];

    if (empty($new_username) || empty($new_email) || empty($new_password) || empty($new_role)) {
        $pesan = "Semua kolom harus diisi!";
        $pesan_tipe = "danger";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash password

        // Cek apakah username sudah ada
        $stmt_check = $conn->prepare("SELECT username FROM user WHERE username = ?");
        $stmt_check->bind_param("s", $new_username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "Username sudah ada. Mohon gunakan username lain.";
            $pesan_tipe = "danger";
        } else {
            // Masukkan data pengguna baru ke database
            $stmt_insert = $conn->prepare("INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, ?)");
            // Diasumsikan kolom 'role' ada di tabel 'user'
            $stmt_insert->bind_param("ssss", $new_username, $new_email, $hashed_password, $new_role);

            if ($stmt_insert->execute()) {
                $pesan = "User berhasil ditambahkan!";
                $pesan_tipe = "success";
            } else {
                $pesan = "Error: " . $stmt_insert->error;
                $pesan_tipe = "danger";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// Logika untuk menghapus user
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['username'])) {
    $user_to_delete = htmlspecialchars($_GET['username']);

    // Mencegah superadmin menghapus dirinya sendiri
    if ($user_to_delete === $username_logged_in) {
        $pesan = "Anda tidak dapat menghapus akun Anda sendiri.";
        $pesan_tipe = "danger";
    } else {
        // Cek apakah user yang akan dihapus memiliki role superadmin
        $stmt_check_role = $conn->prepare("SELECT role FROM user WHERE username = ?");
        $stmt_check_role->bind_param("s", $user_to_delete);
        $stmt_check_role->execute();
        $stmt_check_role->bind_result($target_role);
        $stmt_check_role->fetch();
        $stmt_check_role->close();

        // Hanya superadmin yang bisa menghapus user lain atau admin
        if ($role_logged_in === 'superadmin' || ($role_logged_in === 'admin' && $target_role !== 'superadmin')) { // <-- Perbaikan logika di sini
            $stmt_delete = $conn->prepare("DELETE FROM user WHERE username = ?");
            $stmt_delete->bind_param("s", $user_to_delete);

            if ($stmt_delete->execute()) {
                $pesan = "User berhasil dihapus.";
                $pesan_tipe = "success";
            } else {
                $pesan = "Gagal menghapus user: " . $stmt_delete->error;
                $pesan_tipe = "danger";
            }
            $stmt_delete->close();
        } else {
            $pesan = "Anda tidak memiliki izin untuk menghapus user ini.";
            $pesan_tipe = "danger";
        }
    }
}


// Ambil semua data user dari database
$sql = "SELECT username, email, role FROM user";
$result = $conn->query($sql);

$users = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin</title>
    <link rel="stylesheet" type="text/css" href="../css/bootstrap.css" />
    <link href="../css/font-awesome.min.css" rel="stylesheet" />
    <link href="../css/style.css" rel="stylesheet" />
    <link href="../css/responsive.css" rel="stylesheet" />
    <style>
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
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard_admin.php">Dashboard</a>
        <a href="kelola_pemesanan.php">Kelola Pemesanan</a>
        <a href="kelola_customer.php">Kelola Customer</a>
        <?php if ($role_logged_in === 'superadmin'): ?>
            <a href="kelola_user.php" class="active">Kelola User</a>
        <?php endif; ?>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="content">
        <div class="container-fluid">
            <h2 class="mt-4 mb-4">Kelola User</h2>
            <p>Selamat datang, <?php echo $username_logged_in; ?> (Role: <?php echo $role_logged_in; ?>)</p>

            <?php if (!empty($pesan)) : ?>
                <div class="alert alert-<?php echo $pesan_tipe; ?> text-center">
                    <?php echo $pesan; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <h3>Tambah User Baru</h3>
                <form action="kelola_user.php" method="POST">
                    <input type="hidden" name="add_user" value="1">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah User</button>
                </form>
            </div>

            <div class="card">
                <h3>Daftar User</h3>
                <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td>
                                            <?php if ($user['username'] !== $username_logged_in): // Tidak bisa menghapus akun sendiri ?>
                                                <a href="kelola_user.php?action=delete&username=<?php echo htmlspecialchars($user['username']); ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus user <?php echo htmlspecialchars($user['username']); ?>? Ini akan menghapus semua booking yang dipesan oleh user ini (jika Foreign Key telah diatur ON DELETE CASCADE)!');">
                                                    Hapus
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>Hapus (Diri Sendiri)</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">Tidak ada data user yang ditemukan.</div>
                <?php endif; ?>
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
    </script>
</body>
</html>