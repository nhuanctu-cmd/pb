-- ========== Pickball System - Database Schema ==========
-- Database: pickball_db
-- All tables in dependency order

-- ========== 1. TENANTS (root table) ==========
CREATE TABLE IF NOT EXISTS tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    logo VARCHAR(255) NULL,
    domain VARCHAR(255) NULL,
    db_name VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 2. BRANCHES ==========
CREATE TABLE IF NOT EXISTS branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    latitude VARCHAR(50) NULL,
    longitude VARCHAR(50) NULL,
    is_main TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'maintenance', 'closed') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_tenant_code (tenant_id, code),
    CONSTRAINT fk_branch_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 3. ROLES ==========
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_system TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_tenant_slug (tenant_id, slug),
    CONSTRAINT fk_role_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 4. PERMISSIONS ==========
CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(100) NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_permission_parent FOREIGN KEY (parent_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 5. ROLE_PERMISSIONS ==========
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_role_permission (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 6. USERS ==========
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    avatar VARCHAR(255) NULL,
    gender ENUM('male', 'female', 'other') NULL,
    birth_date DATE NULL,
    last_login DATETIME NULL,
    last_ip VARCHAR(45) NULL,
    is_superadmin TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'suspended', 'banned') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_user_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 7. USER_ROLES ==========
CREATE TABLE IF NOT EXISTS user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_user_role (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 8. SETTINGS ==========
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `type` VARCHAR(50) DEFAULT 'text',
    `group` VARCHAR(50) NULL DEFAULT 'general',
    is_json TINYINT(1) DEFAULT 0,
    is_public TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_tenant_key (tenant_id, `key`),
    CONSTRAINT fk_setting_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_setting_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 9. AUDIT_LOGS ==========
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    description TEXT NULL,
    created_at DATETIME NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_audit_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 10. MEDIA_FILES ==========
CREATE TABLE IF NOT EXISTS media_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED DEFAULT 0,
    mime_type VARCHAR(100) NOT NULL,
    extension VARCHAR(20) NOT NULL,
    alt_text VARCHAR(255) NULL,
    width INT NULL,
    height INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_media_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_media_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_media_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 11. COURT_TYPES ==========
CREATE TABLE IF NOT EXISTS court_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name_vi VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    description_vi TEXT NULL,
    description_en TEXT NULL,
    default_capacity INT DEFAULT 4,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_court_type_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 12. COURTS ==========
CREATE TABLE IF NOT EXISTS courts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    court_type_id INT UNSIGNED NOT NULL,
    code VARCHAR(50) NOT NULL,
    name_vi VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    floor INT DEFAULT 1,
    area DECIMAL(10,2) NULL,
    is_indoor TINYINT(1) DEFAULT 0,
    has_light TINYINT(1) DEFAULT 1,
    has_fan TINYINT(1) DEFAULT 0,
    has_camera TINYINT(1) DEFAULT 0,
    status ENUM('available', 'occupied', 'maintenance', 'inactive') DEFAULT 'available',
    sort_order INT DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_court_code (tenant_id, branch_id, code),
    CONSTRAINT fk_court_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_court_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_court_type FOREIGN KEY (court_type_id) REFERENCES court_types(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 13. COURT_IMAGES ==========
CREATE TABLE IF NOT EXISTS court_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    court_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_court_img_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_court_img_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 14. BRANCH_OPENING_HOURS ==========
CREATE TABLE IF NOT EXISTS branch_opening_hours (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT(1) NOT NULL,
    open_time TIME NULL,
    close_time TIME NULL,
    is_closed TINYINT(1) DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_opening_hours (tenant_id, branch_id, day_of_week),
    CONSTRAINT fk_opening_hours_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_opening_hours_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 15. BRANCH_HOLIDAYS ==========
CREATE TABLE IF NOT EXISTS branch_holidays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    holiday_date DATE NOT NULL,
    name_vi VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    is_closed TINYINT(1) DEFAULT 1,
    note TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_holiday (tenant_id, branch_id, holiday_date),
    CONSTRAINT fk_holiday_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_holiday_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 16. COURT_MAINTENANCE ==========
CREATE TABLE IF NOT EXISTS court_maintenance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    court_id INT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    reason TEXT NULL,
    status ENUM('scheduled', 'doing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_maint_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_maint_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_maint_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 17. BOOKINGS ==========
CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    player_id INT UNSIGNED NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255) NULL,
    booking_code VARCHAR(50) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_minutes INT NOT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    deposit_amount DECIMAL(12,2) DEFAULT 0.00,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('pending', 'reserved', 'paid', 'checked_in', 'completed', 'cancelled', 'refunded', 'no_show') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
    source ENUM('admin', 'player_portal', 'public_web', 'zalo', 'phone') DEFAULT 'admin',
    note TEXT NULL,
    cancelled_at DATETIME NULL,
    cancelled_reason TEXT NULL,
    checked_in_at DATETIME NULL,
    completed_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_booking_code (booking_code),
    INDEX idx_booking_date (tenant_id, branch_id, booking_date),
    INDEX idx_booking_status (tenant_id, status),
    CONSTRAINT fk_booking_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_booking_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_booking_player FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 18. BOOKING_ITEMS ==========
CREATE TABLE IF NOT EXISTS booking_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NOT NULL,
    court_id INT UNSIGNED NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    price DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active', 'cancelled', 'refunded') DEFAULT 'active',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_booking_court (booking_id, court_id),
    CONSTRAINT fk_bi_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bi_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bi_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 19. BOOKING_QR_CODES ==========
CREATE TABLE IF NOT EXISTS booking_qr_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NOT NULL,
    qr_token VARCHAR(100) NOT NULL,
    qr_path VARCHAR(255) NULL,
    expired_at DATETIME NULL,
    used_at DATETIME NULL,
    status ENUM('active', 'used', 'expired', 'revoked') DEFAULT 'active',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_qr_token (qr_token),
    INDEX idx_qr_booking_status (booking_id, status),
    CONSTRAINT fk_qr_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_qr_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 20. BOOKING_LOGS ==========
CREATE TABLE IF NOT EXISTS booking_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NULL,
    message TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    INDEX idx_booking_log (booking_id, created_at),
    CONSTRAINT fk_bl_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bl_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 21. PRICE_TIERS ==========
CREATE TABLE IF NOT EXISTS price_tiers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    court_type_id INT UNSIGNED NULL,
    name_vi VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    day_of_week TINYINT(1) NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    price_per_hour DECIMAL(12,2) DEFAULT 0.00,
    price_per_slot DECIMAL(12,2) DEFAULT 0.00,
    min_deposit_percent DECIMAL(5,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_pt_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pt_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pt_court_type FOREIGN KEY (court_type_id) REFERENCES court_types(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========== 22. BOOKING_SETTINGS ==========
CREATE TABLE IF NOT EXISTS booking_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    allow_online_booking TINYINT(1) DEFAULT 1,
    require_deposit TINYINT(1) DEFAULT 0,
    deposit_percent DECIMAL(5,2) DEFAULT 0.00,
    min_advance_minutes INT DEFAULT 60,
    max_advance_days INT DEFAULT 14,
    slot_duration_minutes INT DEFAULT 60,
    max_slots_per_booking INT DEFAULT 4,
    booking_expiry_minutes INT DEFAULT 15,
    cancel_before_minutes INT DEFAULT 120,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_bs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bs_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
