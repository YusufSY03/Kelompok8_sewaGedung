<?php
session_start(); // Mulai sesi
include 'koneksiDB.php'; // Sertakan file koneksi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // Trim whitespace
    $password = $_POST['password']; // Password diambil langsung (plainteks)

    // Validasi sederhana
    if (empty($username) || empty($password)) {
        $_SESSION['pesan_login'] = "Username dan Password harus diisi!";
        $_SESSION['pesan_tipe'] = "error";
    } else {
        // Cari pengguna berdasarkan username dan ambil role-nya
        $stmt = $conn->prepare("SELECT username, password, role FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($db_username, $db_password, $db_role); // Dapatkan role
        $stmt->fetch();

        if ($stmt->num_rows == 1) {
            // *** VERIFIKASI PASSWORD PLAINTEKS (UNSAFE UNTUK PRODUKSI) ***
            if ($password === $db_password) { // Membandingkan password plainteks secara langsung
                // Login berhasil, set variabel sesi
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $db_role; // Simpan role di session

                // Arahkan berdasarkan role
                if ($db_role == 'superadmin' || $db_role == 'admin') {
                    header("location: admin/dashboard_admin.php"); // Arahkan ke halaman admin
                } else {
                    header("location: index.php"); // Arahkan ke halaman utama web
                }
                exit;
            } else {
                $_SESSION['pesan_login'] = "Password salah. Coba lagi.";
                $_SESSION['pesan_tipe'] = "error";
            }
        } else {
            $_SESSION['pesan_login'] = "Username tidak ditemukan.";
            $_SESSION['pesan_tipe'] = "error";
        }
        $stmt->close();
    }
}
$conn->close();

// Arahkan kembali ke halaman login.php untuk menampilkan pesan
header("Location: login.php");
exit;
?>