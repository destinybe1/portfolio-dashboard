<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

requireLogin();
requireAdmin();

$user = currentUser();

$sql = "
    SELECT 
        user_id,
        full_name,
        username,
        email,
        role,
        is_active,
        last_login_at,
        created_at
    FROM users
    ORDER BY user_id DESC
";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <h1>จัดการผู้ใช้</h1>
                <p class="subtitle">ผู้ดูแลระบบ: <?= htmlspecialchars($user['full_name'] ?? '') ?></p>
            </div>
            <div class="topbar-actions">
                <a class="btn" href="../dashboard.php">กลับ Dashboard</a>
                <a class="btn btn-primary" href="user_form.php">+ เพิ่มผู้ใช้</a>
                <a class="btn btn-danger" href="../logout.php">ออกจากระบบ</a>
            </div>
        </div>

        <div class="table-card">
            <h2>รายการผู้ใช้ในระบบ</h2>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อ-สกุล</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>สถานะ</th>
                        <th>เข้าใช้ล่าสุด</th>
                        <th>สร้างเมื่อ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td><?= (int)$row['user_id'] ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars((string)($row['email'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($row['role']) ?></td>
                                <td>
                                    <?php if ((int)$row['is_active'] === 1): ?>
                                        <span class="badge badge-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">ปิดใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)($row['last_login_at'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string)($row['created_at'] ?? '-')) ?></td>
                                <td>
                                    <div class="action-group">
                                        <a class="btn btn-sm" href="user_form.php?id=<?= (int)$row['user_id'] ?>">แก้ไข</a>

                                        <?php if ((int)$row['user_id'] !== (int)$user['user_id']): ?>
                                            <a class="btn btn-sm <?= (int)$row['is_active'] === 1 ? 'btn-danger' : 'btn-primary' ?>"
                                               href="user_toggle.php?id=<?= (int)$row['user_id'] ?>"
                                               onclick="return confirm('ยืนยันการเปลี่ยนสถานะผู้ใช้?');">
                                                <?= (int)$row['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge">บัญชีปัจจุบัน</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty">ยังไม่มีข้อมูลผู้ใช้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>