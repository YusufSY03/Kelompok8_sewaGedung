<?php
session_start();
include '../koneksiDB.php'; // Sesuaikan path

// Cek apakah user sudah login dan memiliki role admin atau superadmin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: ../login.php"); // Arahkan kembali ke halaman login
    exit;
}

$username_logged_in = htmlspecialchars($_SESSION['username']);
$role_logged_in = htmlspecialchars($_SESSION['role']);

$pesan = "";
$pesan_tipe = "";

// Logika untuk menambahkan customer baru
if (isset($_POST['add_customer'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);

    if (empty($nama) || empty($email)) {
        $pesan = "Nama dan Email harus diisi!";
        $pesan_tipe = "danger";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt_check = $conn->prepare("SELECT id FROM customer WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "Email sudah terdaftar. Gunakan email lain.";
            $pesan_tipe = "danger";
        } else {
            $stmt = $conn->prepare("INSERT INTO customer (nama, email, telepon, alamat) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $email, $telepon, $alamat);
            if ($stmt->execute()) {
                $pesan = "Customer baru berhasil ditambahkan!";
                $pesan_tipe = "success";
            } else {
                $pesan = "Gagal menambahkan customer: " . $stmt->error;
                $pesan_tipe = "danger";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Logika untuk mengupdate customer
if (isset($_POST['edit_customer'])) {
    $id = intval($_POST['edit_id']);
    $nama = trim($_POST['edit_nama']);
    $email = trim($_POST['edit_email']);
    $telepon = trim($_POST['edit_telepon']);
    $alamat = trim($_POST['edit_alamat']);

    if (empty($nama) || empty($email)) {
        $pesan = "Nama dan Email harus diisi!";
        $pesan_tipe = "danger";
    } else {
        // Cek apakah email sudah terdaftar untuk customer lain
        $stmt_check = $conn->prepare("SELECT id FROM customer WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "Email sudah terdaftar untuk customer lain. Gunakan email lain.";
            $pesan_tipe = "danger";
        } else {
            $stmt = $conn->prepare("UPDATE customer SET nama = ?, email = ?, telepon = ?, alamat = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nama, $email, $telepon, $alamat, $id);
            if ($stmt->execute()) {
                $pesan = "Data customer berhasil diperbarui!";
                $pesan_tipe = "success";
            } else {
                $pesan = "Gagal memperbarui customer: " . $stmt->error;
                $pesan_tipe = "danger";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Logika untuk menghapus customer
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Cek apakah customer memiliki booking terkait
    $stmt_check_booking = $conn->prepare("SELECT COUNT(*) FROM booking WHERE customer_id = ?");
    $stmt_check_booking->bind_param("i", $id);
    $stmt_check_booking->execute();
    $stmt_check_booking->bind_result($booking_count);
    $stmt_check_booking->fetch();
    $stmt_check_booking->close();

    if ($booking_count > 0) {
        $pesan = "Gagal menghapus customer: Customer ini memiliki booking terkait. Harap hapus booking terkait terlebih dahulu.";
        $pesan_tipe = "danger";
    } else {
        $stmt = $conn->prepare("DELETE FROM customer WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $pesan = "Customer berhasil dihapus!";
            $pesan_tipe = "success";
        } else {
            $pesan = "Gagal menghapus customer: " . $stmt->error;
            $pesan_tipe = "danger";
        }
        $stmt->close();
    }
}

// Ambil semua data customer
$sql = "SELECT * FROM customer ORDER BY id DESC";
$result = $conn->query($sql);
$customers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Customer - SCC Admin</title>
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
            <h2 class="mb-4">Kelola Customer</h2>

            <?php if (!empty($pesan)): ?>
                <div class="alert alert-dismissible fade show alert-custom alert-<?php echo $pesan_tipe; ?>-custom" role="alert">
                    <?php echo $pesan; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="info-section mt-4">
                <h4>Tambah Customer Baru</h4>
                <form action="kelola_customer.php" method="POST" class="mb-4">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nama">Nama Customer:</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="telepon">Telepon:</label>
                            <input type="text" class="form-control" id="telepon" name="telepon">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="alamat">Alamat:</label>
                            <input type="text" class="form-control" id="alamat" name="alamat">
                        </div>
                    </div>
                    <button type="submit" name="add_customer" class="btn btn-custom-add">Tambah Customer</button>
                </form>
            </div>

            <div class="info-section mt-4">
                <h4>Daftar Customer</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($customers)): ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['telepon']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['alamat']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-btn"
                                                    data-id="<?php echo htmlspecialchars($customer['id']); ?>"
                                                    data-nama="<?php echo htmlspecialchars($customer['nama']); ?>"
                                                    data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                                    data-telepon="<?php echo htmlspecialchars($customer['telepon']); ?>"
                                                    data-alamat="<?php echo htmlspecialchars($customer['alamat']); ?>"
                                                    data-toggle="modal" data-target="#editCustomerModal">
                                                Edit
                                            </button>
                                            <a href="kelola_customer.php?action=delete&id=<?php echo htmlspecialchars($customer['id']); ?>"
                                               class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus customer ini? Semua booking terkait (jika ada) akan terpengaruh jika customer_id dihapus dari tabel booking, atau akan dicegah jika ada booking terkait.');">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data customer.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="kelola_customer.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" id="edit_id" name="edit_id">
                                <div class="form-group">
                                    <label for="edit_nama">Nama Customer:</label>
                                    <input type="text" class="form-control" id="edit_nama" name="edit_nama" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_email">Email:</label>
                                    <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_telepon">Telepon:</label>
                                    <input type="text" class="form-control" id="edit_telepon" name="edit_telepon">
                                </div>
                                <div class="form-group">
                                    <label for="edit_alamat">Alamat:</label>
                                    <input type="text" class="form-control" id="edit_alamat" name="edit_alamat">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="submit" name="edit_customer" class="btn btn-primary">Simpan Perubahan</button>
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
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var email = $(this).data('email');
            var telepon = $(this).data('telepon');
            var alamat = $(this).data('alamat');

            $('#edit_id').val(id);
            $('#edit_nama').val(nama);
            $('#edit_email').val(email);
            $('#edit_telepon').val(telepon);
            $('#edit_alamat').val(alamat);
        });
    </script>
</body>
</html>