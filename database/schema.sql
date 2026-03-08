-- =========================================
-- Portfolio Dashboard Database Schema
-- For MySQL / MariaDB
-- =========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================
-- 1) master_assets
-- เก็บข้อมูลแผนลงทุน/สินทรัพย์
-- =========================================
DROP TABLE IF EXISTS master_assets;
CREATE TABLE master_assets (
    asset_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(20) NOT NULL UNIQUE,
    asset_name_th VARCHAR(100) NOT NULL,
    asset_name_en VARCHAR(100) DEFAULT NULL,
    asset_type VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 2) nav_history
-- เก็บ NAV รายวันของแต่ละแผน
-- =========================================
DROP TABLE IF EXISTS nav_history;
CREATE TABLE nav_history (
    nav_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    nav_date DATE NOT NULL,
    nav_value DECIMAL(12,4) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_nav_asset
        FOREIGN KEY (asset_id) REFERENCES master_assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_nav_asset_date (asset_id, nav_date),
    KEY idx_nav_date (nav_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 3) allocation_history
-- เก็บสัดส่วนเป้าหมายที่ใช้กระจาย DCA ตามช่วงเวลา
-- =========================================
DROP TABLE IF EXISTS allocation_history;
CREATE TABLE allocation_history (
    allocation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    effective_date DATE NOT NULL,
    asset_id INT UNSIGNED NOT NULL,
    target_percent DECIMAL(7,4) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_allocation_asset
        FOREIGN KEY (asset_id) REFERENCES master_assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_allocation_effective_asset (effective_date, asset_id),
    KEY idx_allocation_effective_date (effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 4) dca_contributions
-- เก็บยอดเงิน DCA รายรอบ/รายเดือน
-- contribution_type:
--   SELF = เงินสะสมผู้ใช้
--   GOV  = เงินสมทบรัฐ
--   OTHER = อื่น ๆ
-- =========================================
DROP TABLE IF EXISTS dca_contributions;
CREATE TABLE dca_contributions (
    contribution_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contribution_month DATE NOT NULL,
    contribution_type ENUM('SELF','GOV','OTHER') NOT NULL DEFAULT 'SELF',
    amount DECIMAL(14,2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_contribution_month_type (contribution_month, contribution_type),
    KEY idx_contribution_month (contribution_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 5) dca_allocations
-- เก็บการกระจาย DCA ลงแต่ละแผนในแต่ละรอบ
-- ใช้ทั้งยอดเงิน, NAV ตอนซื้อ, หน่วยที่ได้รับ
-- =========================================
DROP TABLE IF EXISTS dca_allocations;
CREATE TABLE dca_allocations (
    dca_allocation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contribution_id BIGINT UNSIGNED NOT NULL,
    asset_id INT UNSIGNED NOT NULL,
    allocated_percent DECIMAL(7,4) NOT NULL,
    allocated_amount DECIMAL(14,2) NOT NULL,
    nav_value DECIMAL(12,4) NOT NULL,
    units_bought DECIMAL(18,6) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dca_alloc_contribution
        FOREIGN KEY (contribution_id) REFERENCES dca_contributions(contribution_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_dca_alloc_asset
        FOREIGN KEY (asset_id) REFERENCES master_assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_dca_allocation_contribution_asset (contribution_id, asset_id),
    KEY idx_dca_allocation_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 6) rebalance_log
-- เก็บการปรับพอร์ตแต่ละครั้ง
-- before / after ใช้เก็บภาพรวมมูลค่าก่อนและหลัง
-- =========================================
DROP TABLE IF EXISTS rebalance_log;
CREATE TABLE rebalance_log (
    rebalance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rebalance_date DATE NOT NULL,
    rebalance_name VARCHAR(150) DEFAULT NULL,
    total_value_before DECIMAL(16,2) DEFAULT 0.00,
    total_value_after DECIMAL(16,2) DEFAULT 0.00,
    note TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_rebalance_date (rebalance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 7) rebalance_details
-- รายละเอียดการย้ายเงินแต่ละแผนในการ rebalance
-- from_asset_id / to_asset_id อนุญาต NULL กรณีเป็นมุมมอง snapshot
-- =========================================
DROP TABLE IF EXISTS rebalance_details;
CREATE TABLE rebalance_details (
    rebalance_detail_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rebalance_id BIGINT UNSIGNED NOT NULL,
    from_asset_id INT UNSIGNED DEFAULT NULL,
    to_asset_id INT UNSIGNED DEFAULT NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    from_nav DECIMAL(12,4) DEFAULT NULL,
    to_nav DECIMAL(12,4) DEFAULT NULL,
    units_sold DECIMAL(18,6) DEFAULT NULL,
    units_bought DECIMAL(18,6) DEFAULT NULL,
    remark VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rebalance_detail_header
        FOREIGN KEY (rebalance_id) REFERENCES rebalance_log(rebalance_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rebalance_from_asset
        FOREIGN KEY (from_asset_id) REFERENCES master_assets(asset_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_rebalance_to_asset
        FOREIGN KEY (to_asset_id) REFERENCES master_assets(asset_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    KEY idx_rebalance_detail_rebalance (rebalance_id),
    KEY idx_rebalance_detail_from_asset (from_asset_id),
    KEY idx_rebalance_detail_to_asset (to_asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 8) portfolio_daily_snapshot
-- ตารางสรุปสถานะรายวันของแต่ละแผน
-- ใช้เก็บผลคำนวณพร้อมแสดง dashboard ได้เร็ว
-- cost_basis_total = ต้นทุนสะสม
-- market_value = มูลค่าตาม NAV วันนั้น
-- unrealized_profit = market_value - cost_basis_total
-- =========================================
DROP TABLE IF EXISTS portfolio_daily_snapshot;
CREATE TABLE portfolio_daily_snapshot (
    snapshot_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_date DATE NOT NULL,
    asset_id INT UNSIGNED NOT NULL,
    units_total DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
    cost_basis_total DECIMAL(16,2) NOT NULL DEFAULT 0.00,
    nav_value DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    market_value DECIMAL(16,2) NOT NULL DEFAULT 0.00,
    unrealized_profit DECIMAL(16,2) NOT NULL DEFAULT 0.00,
    unrealized_profit_pct DECIMAL(9,4) NOT NULL DEFAULT 0.0000,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_snapshot_asset
        FOREIGN KEY (asset_id) REFERENCES master_assets(asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_snapshot_date_asset (snapshot_date, asset_id),
    KEY idx_snapshot_date (snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- Seed data: master assets
-- ปรับชื่อ asset_code ได้ตามที่คุณใช้จริง
-- =========================================
INSERT INTO master_assets (asset_code, asset_name_th, asset_name_en, asset_type, sort_order) VALUES
('THAI', 'หุ้นไทย', 'Thai Equity', 'EQUITY', 1),
('INTL', 'หุ้นต่างประเทศ', 'International Equity', 'EQUITY', 2),
('GOLD', 'ทองคำ', 'Gold', 'COMMODITY', 3),
('IFB',  'ตราสารหนี้', 'Fixed Income Bond', 'BOND', 4);

SET FOREIGN_KEY_CHECKS = 1;-- Portfolio database schema 
