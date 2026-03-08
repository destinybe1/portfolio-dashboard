<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/config.php';

try {
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

    echo json_encode([
        'status' => 'success',
        'count' => count($rows),
        'data' => $rows
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}