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
            $pesan = "Email customer sudah terdaftar!";
            $pesan_tipe = "danger";
        } else {
            // Masukkan data customer baru
            $stmt_insert = $conn->prepare("INSERT INTO customer (nama, email, telepon, alamat) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $nama, $email, $telepon, $alamat);

            if ($stmt_insert->execute()) {
                $pesan = "Customer berhasil ditambahkan!";
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

// Logika untuk menghapus customer
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $customer_id = intval($_GET['id']); // Pastikan ID adalah integer

    $stmt_delete = $conn->prepare("DELETE FROM customer WHERE id = ?");
    $stmt_delete->bind_param("i", $customer_id);

    if ($stmt_delete->execute()) {
        $pesan = "Customer dan semua pemesanannya berhasil dihapus.";
        $pesan_tipe = "success";
    } else {
        $pesan = "Gagal menghapus customer: " . $stmt_delete->error;
        $pesan_tipe = "danger";
    }
    $stmt_delete->close();
}


$search_query = "";
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
}

// Query untuk menampilkan customer
$sql = "SELECT id, nama, email, telepon, alamat FROM customer";
if (!empty($search_query)) {
    $sql .= " WHERE nama LIKE ? OR email LIKE ? OR telepon LIKE ?";
    $search_param = "%" . $search_query . "%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$customers = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
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
    <title>Kelola Customer</title>
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
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard_admin.php">Dashboard</a>
        <a href="kelola_pemesanan.php">Kelola Pemesanan</a>
        <a href="kelola_customer.php" class="active">Kelola Customer</a>
        <?php if ($role_logged_in === 'superadmin'): ?>
            <a href="kelola_user.php">Kelola User</a>
        <?php endif; ?>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="content">
        <div class="container-fluid">
            <h2 class="mt-4 mb-4">Kelola Customer</h2>
            <p>Selamat datang, <?php echo $username_logged_in; ?> (Role: <?php echo $role_logged_in; ?>)</p>

            <?php if (!empty($pesan)) : ?>
                <div class="alert alert-<?php echo $pesan_tipe; ?> text-center">
                    <?php echo $pesan; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <h3>Tambah Customer Baru</h3>
                <form action="kelola_customer.php" method="POST">
                    <input type="hidden" name="add_customer" value="1">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap:</label>
                        <input type="text" id="nama" name="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="telepon">Telepon (Opsional):</label>
                        <input type="text" id="telepon" name="telepon" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat (Opsional):</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Customer</button>
                </form>
            </div>

            <div class="card">
                <h3>Daftar Customer</h3>
                <form action="kelola_customer.php" method="GET" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama, email, telepon..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-info">Cari</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="kelola_customer.php" class="btn btn-secondary ml-2">Reset</a>
                    <?php endif; ?>
                </form>

                <?php if (!empty($customers)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
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
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['telepon']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['alamat']); ?></td>
                                        <td>
                                            <a href="kelola_customer.php?action=delete&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus customer ini? Semua pemesanan terkait juga akan terhapus!');">
                                                Hapus
                                            </a>
                                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">Tidak ada data customer yang ditemukan.</div>
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