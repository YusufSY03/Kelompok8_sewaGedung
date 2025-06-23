<?php
session_start(); // Mulai sesi untuk mengakses pesan
$pesan = isset($_SESSION['pesan_register']) ? $_SESSION['pesan_register'] : "";
$pesan_tipe = isset($_SESSION['pesan_tipe']) ? $_SESSION['pesan_tipe'] : "";
// Hapus pesan dari sesi agar tidak muncul lagi setelah refresh
unset($_SESSION['pesan_register']);
unset($_SESSION['pesan_tipe']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        .message { text-align: center; margin-bottom: 15px; font-weight: bold; }
        .message.sukses { color: green; }
        .message.error { color: red; }
        label { display: block; margin-bottom: 8px; color: #555; }
        input[type="text"],
        input[type="email"],
        input[type="password"] { width: calc(100% - 20px); padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        p.login-link { text-align: center; margin-top: 15px; }
        p.login-link a { color: #007bff; text-decoration: none; }
        p.login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Daftar Akun Baru</h2>
        <?php if (!empty($pesan)) : ?>
            <p class="message <?php echo $pesan_tipe; ?>">
                <?php echo $pesan; ?>
                <?php if ($pesan_tipe == "sukses"): ?>
                    <a href='login.php'>Login</a>.
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <form action="register_process.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Daftar">
        </form>
        <p class="login-link">Sudah punya akun? <a href="login.php">Login di sini</a>.</p>
    </div>
</body>
</html>