<?php

declare(strict_types=1);
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
$user = currentUser();
$isAdmin = ($user['role'] ?? '') === 'admin';
$sql = "
    SELECT 
        p.snapshot_date,
        a.asset_code,
        a.asset_name_th,
        p.units_total,
        p.cost_basis_total,
        p.nav_value,
        p.market_value,
        p.unrealized_profit,
        p.unrealized_profit_pct
    FROM portfolio_daily_snapshot p
    INNER JOIN master_assets a ON a.asset_id = p.asset_id
    INNER JOIN (
        SELECT asset_id, MAX(snapshot_date) AS latest_date
        FROM portfolio_daily_snapshot
        GROUP BY asset_id
    ) latest 
        ON latest.asset_id = p.asset_id
       AND latest.latest_date = p.snapshot_date
    ORDER BY a.sort_order ASC, a.asset_id ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

$totalCost = 0;
$totalValue = 0;
$totalProfit = 0;

foreach ($rows as $row) {
    $totalCost += (float)$row['cost_basis_total'];
    $totalValue += (float)$row['market_value'];
    $totalProfit += (float)$row['unrealized_profit'];
}

$totalProfitPct = $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
<p class="subtitle">ยินดีต้อนรับ, <?= htmlspecialchars($user['full_name'] ?? '') ?><div class="topbar-actions" style="margin-bottom: 16px;">
    <?php if ($isAdmin): ?>
        <a class="btn btn-primary" href="admin/users.php">จัดการผู้ใช้</a>
    <?php endif; ?>
    <a class="btn btn-danger" href="logout.php">ออกจากระบบ</a>
</div></p>
        <h1>Portfolio Dashboard</h1>
        <p class="subtitle">ภาพรวมพอร์ตจากข้อมูลล่าสุดของแต่ละแผน</p>

        <div class="summary-grid">
            <div class="card">
                <div class="label">ต้นทุนรวม</div>
                <div class="value"><?= number_format($totalCost, 2) ?></div>
            </div>
            <div class="card">
                <div class="label">มูลค่าปัจจุบัน</div>
                <div class="value"><?= number_format($totalValue, 2) ?></div>
            </div>
            <div class="card">
                <div class="label">กำไร / ขาดทุน</div>
                <div class="value <?= $totalProfit >= 0 ? 'profit' : 'loss' ?>">
                    <?= number_format($totalProfit, 2) ?>
                </div>
            </div>
            <div class="card">
                <div class="label">ผลตอบแทน (%)</div>
                <div class="value <?= $totalProfitPct >= 0 ? 'profit' : 'loss' ?>">
                    <?= number_format($totalProfitPct, 2) ?>%
                </div>
            </div>
        </div>

        <div class="table-card">
            <h2>รายละเอียดแต่ละแผน</h2>

            <table>
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>รหัส</th>
                        <th>แผน</th>
                        <th>หน่วยสะสม</th>
                        <th>ต้นทุน</th>
                        <th>NAV</th>
                        <th>มูลค่า</th>
                        <th>กำไร/ขาดทุน</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['snapshot_date']) ?></td>
                                <td><?= htmlspecialchars($row['asset_code']) ?></td>
                                <td><?= htmlspecialchars($row['asset_name_th']) ?></td>
                                <td><?= number_format((float)$row['units_total'], 4) ?></td>
                                <td><?= number_format((float)$row['cost_basis_total'], 2) ?></td>
                                <td><?= number_format((float)$row['nav_value'], 4) ?></td>
                                <td><?= number_format((float)$row['market_value'], 2) ?></td>
                                <td class="<?= (float)$row['unrealized_profit'] >= 0 ? 'profit' : 'loss' ?>">
                                    <?= number_format((float)$row['unrealized_profit'], 2) ?>
                                </td>
                                <td class="<?= (float)$row['unrealized_profit_pct'] >= 0 ? 'profit' : 'loss' ?>">
                                    <?= number_format((float)$row['unrealized_profit_pct'], 2) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty">ยังไม่มีข้อมูลใน portfolio_daily_snapshot</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>