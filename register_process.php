<?php
session_start(); // Mulai sesi untuk menyimpan pesan
include 'koneksiDB.php'; // Sertakan file koneksi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // Trim whitespace
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password diambil langsung (plainteks)

    // Validasi sederhana: pastikan semua kolom terisi
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['pesan_register'] = "Semua kolom harus diisi!";
        $_SESSION['pesan_tipe'] = "error";
    } else {
        // Periksa apakah username sudah ada
        $stmt_check = $conn->prepare("SELECT username FROM user WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['pesan_register'] = "Username sudah ada. Mohon gunakan username lain.";
            $_SESSION['pesan_tipe'] = "error";
        } else {
            // Masukkan data pengguna baru ke database (default role 'user')
            // *** TIDAK MENGGUNAKAN HASHING PASSWORD (UNSAFE UNTUK PRODUKSI) ***
            $stmt_insert = $conn->prepare("INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt_insert->bind_param("sss", $username, $email, $password); // Menyimpan password plainteks

            if ($stmt_insert->execute()) {
                $_SESSION['pesan_register'] = "Registrasi berhasil! Anda sekarang bisa Login.";
                $_SESSION['pesan_tipe'] = "sukses";
            } else {
                $_SESSION['pesan_register'] = "Error: " . $stmt_insert->error;
                $_SESSION['pesan_tipe'] = "error";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();

// Arahkan kembali ke halaman register.php untuk menampilkan pesan
header("Location: register.php");
exit;
?>