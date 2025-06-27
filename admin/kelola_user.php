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
            $pesan = "Username sudah ada. Gunakan username lain.";
            $pesan_tipe = "danger";
        } else {
            $stmt = $conn->prepare("INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $new_username, $new_email, $hashed_password, $new_role);

            if ($stmt->execute()) {
                $pesan = "User baru berhasil ditambahkan!";
                $pesan_tipe = "success";
            } else {
                $pesan = "Gagal menambahkan user: " . $stmt->error;
                $pesan_tipe = "danger";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Logika untuk mengupdate user
if (isset($_POST['edit_user'])) {
    $edit_username = trim($_POST['edit_username']);
    $edit_email = trim($_POST['edit_email']);
    $edit_role = $_POST['edit_role'];
    $edit_password = $_POST['edit_password']; // Opsional, bisa kosong jika tidak diubah

    if (empty($edit_username) || empty($edit_email) || empty($edit_role)) {
        $pesan = "Username, Email, dan Role harus diisi!";
        $pesan_tipe = "danger";
    } else {
        // Cek apakah email sudah terdaftar untuk user lain (jika email diubah)
        $stmt_check_email = $conn->prepare("SELECT username FROM user WHERE email = ? AND username != ?");
        $stmt_check_email->bind_param("ss", $edit_email, $edit_username);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $pesan = "Email sudah terdaftar untuk user lain. Gunakan email lain.";
            $pesan_tipe = "danger";
        } else {
            $sql_update = "UPDATE user SET email = ?, role = ?";
            if (!empty($edit_password)) {
                $hashed_password = password_hash($edit_password, PASSWORD_DEFAULT);
                $sql_update .= ", password = ?";
            }
            $sql_update .= " WHERE username = ?";

            $stmt = $conn->prepare($sql_update);

            if (!empty($edit_password)) {
                $stmt->bind_param("ssss", $edit_email, $edit_role, $hashed_password, $edit_username);
            } else {
                $stmt->bind_param("sss", $edit_email, $edit_role, $edit_username);
            }

            if ($stmt->execute()) {
                $pesan = "Data user berhasil diperbarui!";
                $pesan_tipe = "success";
            } else {
                $pesan = "Gagal memperbarui user: " . $stmt->error;
                $pesan_tipe = "danger";
            }
            $stmt->close();
        }
        $stmt_check_email->close();
    }
}


// Logika untuk menghapus user
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['username'])) {
    $delete_username = $_GET['username'];

    // Cek apakah user memiliki booking terkait
    $stmt_check_booking = $conn->prepare("SELECT COUNT(*) FROM booking WHERE user_username = ?");
    $stmt_check_booking->bind_param("s", $delete_username);
    $stmt_check_booking->execute();
    $stmt_check_booking->bind_result($booking_count);
    $stmt_check_booking->fetch();
    $stmt_check_booking->close();

    if ($booking_count > 0) {
        $pesan = "Gagal menghapus user: User ini memiliki booking terkait. Harap hapus booking terkait terlebih dahulu.";
        $pesan_tipe = "danger";
    } else if ($delete_username == $_SESSION['username']) {
        $pesan = "Anda tidak bisa menghapus akun Anda sendiri!";
        $pesan_tipe = "danger";
    } else if ($delete_username == 'superadmin' && $role_logged_in == 'superadmin') { // Mencegah superadmin menghapus akun superadmin default
        $pesan = "Akun superadmin default tidak bisa dihapus.";
        $pesan_tipe = "danger";
    }
    else {
        $stmt = $conn->prepare("DELETE FROM user WHERE username = ?");
        $stmt->bind_param("s", $delete_username);
        if ($stmt->execute()) {
            $pesan = "User berhasil dihapus!";
            $pesan_tipe = "success";
        } else {
            $pesan = "Gagal menghapus user: " . $stmt->error;
            $pesan_tipe = "danger";
        }
        $stmt->close();
    }
}


// Ambil semua data user
$sql = "SELECT username, email, role FROM user ORDER BY username ASC";
$result = $conn->query($sql);
$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
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
    <title>Kelola User - SCC Admin</title>
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
            <h2 class="mb-4">Kelola User</h2>

            <?php if (!empty($pesan)): ?>
                <div class="alert alert-dismissible fade show alert-custom alert-<?php echo $pesan_tipe; ?>-custom" role="alert">
                    <?php echo $pesan; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="info-section mt-4">
                <h4>Tambah User Baru</h4>
                <form action="kelola_user.php" method="POST" class="mb-4">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="username">Username:</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password">Password:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="role">Role:</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <?php if ($role_logged_in == 'superadmin'): ?>
                                    <option value="superadmin">Superadmin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-custom-add">Tambah User</button>
                </form>
            </div>

            <div class="info-section mt-4">
                <h4>Daftar User</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-btn"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                    data-toggle="modal" data-target="#editUserModal">
                                                Edit
                                            </button>
                                            <a href="kelola_user.php?action=delete&username=<?php echo htmlspecialchars($user['username']); ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus user ini? Ini akan menghapus semua booking yang dibuat oleh user ini!');">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data user.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="kelola_user.php" method="POST">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="edit_username">Username:</label>
                                    <input type="text" class="form-control" id="edit_username" name="edit_username" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="edit_email">Email:</label>
                                    <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_password">New Password (isi jika ingin mengubah):</label>
                                    <input type="password" class="form-control" id="edit_password" name="edit_password">
                                </div>
                                <div class="form-group">
                                    <label for="edit_role">Role:</label>
                                    <select class="form-control" id="edit_role" name="edit_role" required>
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                        <?php if ($role_logged_in == 'superadmin'): ?>
                                            <option value="superadmin">Superadmin</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
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

        // JavaScript untuk mengisi data ke modal edit
        $(document).on('click', '.edit-btn', function() {
            var username = $(this).data('username');
            var email = $(this).data('email');
            var role = $(this).data('role');

            $('#edit_username').val(username);
            $('#edit_email').val(email);
            $('#edit_role').val(role);
            $('#edit_password').val(''); // Kosongkan password saat modal dibuka
        });
    </script>
</body>
</html>