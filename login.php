<?php
session_start(); // Mulai sesi untuk mengakses pesan
$pesan = isset($_SESSION['pesan_login']) ? $_SESSION['pesan_login'] : "";
$pesan_tipe = isset($_SESSION['pesan_tipe']) ? $_SESSION['pesan_tipe'] : "";
// Hapus pesan dari sesi agar tidak muncul lagi setelah refresh
unset($_SESSION['pesan_login']);
unset($_SESSION['pesan_tipe']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        .message { text-align: center; margin-bottom: 15px; font-weight: bold; }
        .message.error { color: red; }
        label { display: block; margin-bottom: 8px; color: #555; }
        input[type="text"],
        input[type="password"] { width: calc(100% - 20px); padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        input[type="submit"]:hover { background-color: #218838; }
        p.register-link { text-align: center; margin-top: 15px; }
        p.register-link a { color: #007bff; text-decoration: none; }
        p.register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($pesan)) : ?>
            <p class="message <?php echo $pesan_tipe; ?>"><?php echo $pesan; ?></p>
        <?php endif; ?>
        <form action="login_process.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login">
        </form>
        <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a>.</p>
    </div>
</body>
</html>