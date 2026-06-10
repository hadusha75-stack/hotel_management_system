-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================
-- SABAWYAN HOTEL MANAGEMENT SYSTEM
-- MySQL / MariaDB Schema
-- Run in phpMyAdmin: select database → SQL tab → paste → Go
-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================
-- SECTION A: EXISTING TABLES (your original tables)
-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================

CREATE TABLE IF NOT EXISTS rooms (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    roomnumber     VARCHAR(10)   NOT NULL UNIQUE,
    roomtype       VARCHAR(20)   NOT NULL DEFAULT 'AC',
    bedtype        VARCHAR(20)   NOT NULL DEFAULT 'Single',
    price          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status         VARCHAR(20)   NOT NULL DEFAULT 'not booked',
    cleanDerty     VARCHAR(20)   NOT NULL DEFAULT 'Clean',
    floor          TINYINT       DEFAULT 1,
    price_override DECIMAL(10,2) NULL,
    room_type_id   INT           NULL,
    is_active      TINYINT(1)    DEFAULT 1,
    created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    email        VARCHAR(100),
    mobilenumber VARCHAR(20),
    nationality  VARCHAR(60),
    gender       VARCHAR(10),
    idproof      VARCHAR(50),
    address      TEXT,
    bedtype      VARCHAR(20),
    roomtype     VARCHAR(20),
    roomnumber   VARCHAR(10),
    checkin      DATE,
    checkout     DATE,
    priceperday  DECIMAL(10,2) DEFAULT 0.00,
    daystayed    INT           DEFAULT 0,
    totalamount  DECIMAL(10,2) DEFAULT 0.00,
    created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deleted_customers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    email        VARCHAR(100),
    mobilenumber VARCHAR(20),
    nationality  VARCHAR(60),
    gender       VARCHAR(10),
    idproof      VARCHAR(50),
    address      TEXT,
    bedtype      VARCHAR(20),
    roomtype     VARCHAR(20),
    roomnumber   VARCHAR(10),
    checkin      DATE,
    checkout     DATE,
    priceperday  DECIMAL(10,2) DEFAULT 0.00,
    daystayed    INT           DEFAULT 0,
    totalamount  DECIMAL(10,2) DEFAULT 0.00,
    deleted_at   DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feedback (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(100),
    experience VARCHAR(50),
    message    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(100),
    message    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer_login (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    address    VARCHAR(200),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================
-- SECTION B: NEW BACKEND TABLES (no foreign keys yet)
-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================

CREATE TABLE IF NOT EXISTS roles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (name, description) VALUES
('admin',        'Full system access'),
('manager',      'Operations and reports'),
('receptionist', 'Check-in, check-out, reservations'),
('housekeeping', 'Room cleaning status'),
('cashier',      'Billing and payments'),
('guest',        'Self-service portal');

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    role_id       INT          NOT NULL DEFAULT 3,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO users (role_id, full_name, email, password_hash) VALUES
(2, 'Hotel Manager',   'manager@sabawyan.com', '$2y$10$placeholder_replace_with_bcrypt'),
(5, 'Finance Officer', 'finance@sabawyan.com',  '$2y$10$placeholder_replace_with_bcrypt'),
(3, 'Hotel Staff',     'staff@sabawyan.com',    '$2y$10$placeholder_replace_with_bcrypt');

CREATE TABLE IF NOT EXISTS guests (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100),
    phone         VARCHAR(20),
    nationality   VARCHAR(60),
    gender        VARCHAR(10),
    date_of_birth DATE,
    id_type       VARCHAR(30),
    id_number     VARCHAR(50),
    address       TEXT,
    is_vip        TINYINT(1)  DEFAULT 0,
    notes         TEXT,
    created_at    DATETIME    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_types (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(50)   NOT NULL UNIQUE,
    description   TEXT,
    base_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_occupancy TINYINT       NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO room_types (name, base_price, max_occupancy) VALUES
('Single', 800.00,  1),
('Double', 1200.00, 2),
('Deluxe', 2000.00, 2),
('Suite',  4000.00, 4);

CREATE TABLE IF NOT EXISTS services (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    category   VARCHAR(50),
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active  TINYINT(1)    DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO services (name, category, unit_price) VALUES
('Room Service',     'Food',      150.00),
('Laundry',          'Laundry',    80.00),
('Airport Transfer', 'Transport', 500.00),
('Extra Bed',        'Room',      200.00);

CREATE TABLE IF NOT EXISTS inventory_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    category      VARCHAR(50),
    unit          VARCHAR(20)   DEFAULT 'Pieces',
    quantity      INT           NOT NULL DEFAULT 0,
    reorder_level INT           NOT NULL DEFAULT 10,
    unit_cost     DECIMAL(10,2) DEFAULT 0.00,
    supplier      VARCHAR(100),
    notes         TEXT,
    updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================
-- SECTION C: TABLES WITH FOREIGN KEYS (inline — safe order)
-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================

CREATE TABLE IF NOT EXISTS reservations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code    VARCHAR(20)   NOT NULL UNIQUE,
    guest_id            INT           NOT NULL,
    room_id             INT           NOT NULL,
    check_in_date       DATE          NOT NULL,
    check_out_date      DATE          NOT NULL,
    adults              TINYINT       NOT NULL DEFAULT 1,
    children            TINYINT       NOT NULL DEFAULT 0,
    status              VARCHAR(20)   NOT NULL DEFAULT 'Pending',
    special_requests    TEXT,
    total_amount        DECIMAL(10,2) DEFAULT 0.00,
    advance_paid        DECIMAL(10,2) DEFAULT 0.00,
    source              VARCHAR(20)   DEFAULT 'Online',
    created_by          INT           NULL,
    cancelled_at        DATETIME      NULL,
    cancellation_reason TEXT,
    created_at          DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_guest FOREIGN KEY (guest_id)   REFERENCES guests(id),
    CONSTRAINT fk_res_room  FOREIGN KEY (room_id)    REFERENCES rooms(id),
    CONSTRAINT fk_res_user  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkins (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id    INT         NULL,
    guest_id          INT         NOT NULL,
    room_id           INT         NOT NULL,
    check_in_datetime DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_checkout DATE        NOT NULL,
    actual_checkout   DATETIME    NULL,
    adults            TINYINT     DEFAULT 1,
    children          TINYINT     DEFAULT 0,
    id_verified       TINYINT(1)  DEFAULT 0,
    checked_in_by     INT         NULL,
    notes             TEXT,
    status            VARCHAR(20) NOT NULL DEFAULT 'Active',
    created_at        DATETIME    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ci_res   FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    CONSTRAINT fk_ci_guest FOREIGN KEY (guest_id)       REFERENCES guests(id),
    CONSTRAINT fk_ci_room  FOREIGN KEY (room_id)        REFERENCES rooms(id),
    CONSTRAINT fk_ci_user  FOREIGN KEY (checked_in_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20)   NOT NULL UNIQUE,
    checkin_id     INT           NOT NULL UNIQUE,
    guest_id       INT           NOT NULL,
    room_charge    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    service_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status         VARCHAR(20)   NOT NULL DEFAULT 'Draft',
    issued_at      DATETIME      NULL,
    due_date       DATE          NULL,
    notes          TEXT,
    created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inv_ci    FOREIGN KEY (checkin_id) REFERENCES checkins(id),
    CONSTRAINT fk_inv_guest FOREIGN KEY (guest_id)   REFERENCES guests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id   INT           NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    method       VARCHAR(20)   NOT NULL DEFAULT 'Cash',
    reference    VARCHAR(100),
    status       VARCHAR(20)   DEFAULT 'Completed',
    processed_by INT           NULL,
    paid_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    notes        TEXT,
    CONSTRAINT fk_pay_inv  FOREIGN KEY (invoice_id)   REFERENCES invoices(id),
    CONSTRAINT fk_pay_user FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkouts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    checkin_id    INT      NOT NULL UNIQUE,
    invoice_id    INT      NOT NULL,
    checkout_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nights_stayed SMALLINT NOT NULL DEFAULT 1,
    processed_by  INT      NULL,
    notes         TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_co_ci   FOREIGN KEY (checkin_id)   REFERENCES checkins(id),
    CONSTRAINT fk_co_inv  FOREIGN KEY (invoice_id)   REFERENCES invoices(id),
    CONSTRAINT fk_co_user FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkin_charges (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    checkin_id INT           NOT NULL,
    service_id INT           NOT NULL,
    quantity   SMALLINT      NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total      DECIMAL(10,2) NOT NULL,
    added_by   INT           NULL,
    added_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cc_ci      FOREIGN KEY (checkin_id) REFERENCES checkins(id),
    CONSTRAINT fk_cc_service FOREIGN KEY (service_id) REFERENCES services(id),
    CONSTRAINT fk_cc_user    FOREIGN KEY (added_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS housekeeping_tasks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    room_id      INT         NOT NULL,
    task_type    VARCHAR(20) DEFAULT 'Cleaning',
    status       VARCHAR(20) DEFAULT 'Pending',
    assigned_to  INT         NULL,
    notes        TEXT,
    started_at   DATETIME    NULL,
    completed_at DATETIME    NULL,
    created_at   DATETIME    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hk_room FOREIGN KEY (room_id)     REFERENCES rooms(id),
    CONSTRAINT fk_hk_user FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    item_id    INT         NOT NULL,
    type       VARCHAR(20) NOT NULL DEFAULT 'StockIn',
    quantity   INT         NOT NULL,
    reason     VARCHAR(200),
    done_by    INT         NULL,
    created_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_it_item FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    CONSTRAINT fk_it_user FOREIGN KEY (done_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NULL,
    action     VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id  INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================
-- SECTION D: FK ON INDEPENDENT TABLES (added last)
-- InfinityFree Database: if0_42076337_web_hotel_managment
-- MySQL Host: sql206.infinityfree.com
-- User: if0_42076337

-- ============================================================

ALTER TABLE guests ADD CONSTRAINT fk_gs_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE users ADD CONSTRAINT fk_us_role
    FOREIGN KEY (role_id) REFERENCES roles(id);

SET FOREIGN_KEY_CHECKS = 1;
