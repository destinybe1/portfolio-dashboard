<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

requireLogin();
requireAdmin();

$currentUser = currentUser();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$error = '';
$success = '';

$formData = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'role' => 'user',
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();

    if (!$found) {
        die('ไม่พบผู้ใช้');
    }

    $formData = [
        'full_name' => $found['full_name'],
        'username' => $found['username'],
        'email' => $found['email'] ?? '',
        'role' => $found['role'],
        'is_active' => (int)$found['is_active'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'user');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    $formData = [
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'is_active' => $isActive,
    ];

    if ($fullName === '' || $username === '') {
        $error = 'กรุณากรอกชื่อ-สกุล และ username';
    } elseif (!in_array($role, ['admin', 'user'], true)) {
        $error = 'สิทธิ์ผู้ใช้ไม่ถูกต้อง';
    } elseif (!$isEdit && $password === '') {
        $error = 'กรุณากรอกรหัสผ่านสำหรับผู้ใช้ใหม่';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $error = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        if ($isEdit) {
            $check = $pdo->prepare("
                SELECT user_id 
                FROM users 
                WHERE (username = :username OR email = :email)
                  AND user_id <> :id
                LIMIT 1
            ");
            $check->execute([
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'id' => $id
            ]);
        } else {
            $check = $pdo->prepare("
                SELECT user_id 
                FROM users 
                WHERE username = :username OR email = :email
                LIMIT 1
            ");
            $check->execute([
                'username' => $username,
                'email' => $email !== '' ? $email : null
            ]);
        }

        $exists = $check->fetch();

        if ($exists) {
            $error = 'Username หรือ Email นี้ถูกใช้งานแล้ว';
        } else {
            if ($isEdit) {
                if ($password !== '') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $sql = "
                        UPDATE users
                        SET full_name = :full_name,
                            username = :username,
                            email = :email,
                            role = :role,
                            is_active = :is_active,
                            password_hash = :password_hash
                        WHERE user_id = :id
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'full_name' => $fullName,
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                        'role' => $role,
                        'is_active' => $isActive,
                        'password_hash' => $passwordHash,
                        'id' => $id
                    ]);
                } else {
                    $sql = "
                        UPDATE users
                        SET full_name = :full_name,
                            username = :username,
                            email = :email,
                            role = :role,
                            is_active = :is_active
                        WHERE user_id = :id
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'full_name' => $fullName,
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                        'role' => $role,
                        'is_active' => $isActive,
                        'id' => $id
                    ]);
                }

                $success = 'แก้ไขข้อมูลผู้ใช้เรียบร้อยแล้ว';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "
                    INSERT INTO users (
                        full_name,
                        username,
                        email,
                        password_hash,
                        role,
                        is_active
                    ) VALUES (
                        :full_name,
                        :username,
                        :email,
                        :password_hash,
                        :role,
                        :is_active
                    )
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email !== '' ? $email : null,
                    'password_hash' => $passwordHash,
                    'role' => $role,
                    'is_active' => $isActive
                ]);

                header('Location: users.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้' ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 720px;">
        <div class="topbar">
            <div>
                <h1><?= $isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้' ?></h1>
                <p class="subtitle">ผู้ดูแลระบบ: <?= htmlspecialchars($currentUser['full_name'] ?? '') ?></p>
            </div>
            <div class="topbar-actions">
                <a class="btn" href="users.php">กลับรายการผู้ใช้</a>
            </div>
        </div>

        <div class="card">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>ชื่อ-สกุล</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($formData['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars((string)$formData['email']) ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>สิทธิ์</label>
                        <select name="role" required>
                            <option value="user" <?= $formData['role'] === 'user' ? 'selected' : '' ?>>user</option>
                            <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                        </select>
                    </div>

                    <div class="form-group checkbox-wrap">
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?= (int)$formData['is_active'] === 1 ? 'checked' : '' ?>>
                            เปิดใช้งานผู้ใช้
                        </label>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>รหัสผ่าน <?= $isEdit ? '(เว้นว่างถ้าไม่เปลี่ยน)' : '' ?></label>
                        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?>>
                    </div>

                    <div class="form-group">
                        <label>ยืนยันรหัสผ่าน</label>
                        <input type="password" name="confirm_password" <?= $isEdit ? '' : 'required' ?>>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้' ?>
                    </button>
                    <a href="users.php" class="btn">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>