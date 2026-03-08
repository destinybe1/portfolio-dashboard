<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $username === '' || $password === '') {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ';
    } elseif ($password !== $confirmPassword) {
        $error = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $check->execute([
            'username' => $username,
            'email' => $email !== '' ? $email : null
        ]);
        $exists = $check->fetch();

        if ($exists) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("
                INSERT INTO users (full_name, username, email, password_hash, role)
                VALUES (:full_name, :username, :email, :password_hash, 'admin')
            ");

            $insert->execute([
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'password_hash' => $passwordHash
            ]);

            $success = 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 480px; margin-top: 50px;">
        <div class="card">
            <h1>สมัครสมาชิก</h1>

            <?php if ($error !== ''): ?>
                <div class="loss" style="margin-bottom: 16px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="profit" style="margin-bottom: 16px;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post">
                <div style="margin-bottom: 12px;">
                    <label>ชื่อ-สกุล</label>
                    <input type="text" name="full_name" style="width:100%;padding:10px;" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" name="username" style="width:100%;padding:10px;" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label>อีเมล</label>
                    <input type="email" name="email" style="width:100%;padding:10px;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" style="width:100%;padding:10px;" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label>ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" style="width:100%;padding:10px;" required>
                </div>

                <button type="submit" style="width:100%;padding:12px;">สมัครสมาชิก</button>
            </form>

            <p style="margin-top:16px;">
                มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
            </p>
        </div>
    </div>
</body>
</html>