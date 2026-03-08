<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'user_id'   => $user['user_id'],
                'full_name' => $user['full_name'],
                'username'  => $user['username'],
                'role'      => $user['role'],
            ];

            $update = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = :user_id");
            $update->execute(['user_id' => $user['user_id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 420px; margin-top: 80px;">
        <div class="card">
            <h1>เข้าสู่ระบบ</h1>

            <?php if ($error !== ''): ?>
                <div class="loss" style="margin-bottom: 16px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div style="margin-bottom: 12px;">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" name="username" style="width:100%;padding:10px;" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" style="width:100%;padding:10px;" required>
                </div>

                <button type="submit" style="width:100%;padding:12px;">เข้าสู่ระบบ</button>
            </form>

            <p style="margin-top:16px;">
                ยังไม่มีผู้ใช้? <a href="register.php">สมัครสมาชิก</a>
            </p>
        </div>
    </div>
</body>
</html>