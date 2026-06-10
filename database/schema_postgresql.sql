-- ============================================================
-- SABAWYAN HOTEL MANAGEMENT SYSTEM
-- PostgreSQL Complete Schema (Supabase / Railway / Render)
-- ============================================================

-- ============================================================
-- SECTION A: EXISTING / CORE TABLES
-- ============================================================

-- A1. rooms (individual price per room)
CREATE TABLE IF NOT EXISTS rooms (
    id              SERIAL PRIMARY KEY,
    roomnumber      VARCHAR(10)   NOT NULL UNIQUE,
    roomtype        VARCHAR(20)   NOT NULL DEFAULT 'AC',
    bedtype         VARCHAR(20)   NOT NULL DEFAULT 'Single',
    price           NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    status          VARCHAR(20)   NOT NULL DEFAULT 'not booked',
    "cleanDerty"    VARCHAR(20)   NOT NULL DEFAULT 'Clean',
    floor           SMALLINT      DEFAULT 1,
    price_override  NUMERIC(10,2),
    room_type_id    INT,
    is_active       SMALLINT      DEFAULT 1,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- A2. customer (active guests)
CREATE TABLE IF NOT EXISTS customer (
    id                   SERIAL PRIMARY KEY,
    name                 VARCHAR(100),
    email                VARCHAR(100),
    mobilenumber         VARCHAR(20),
    nationality          VARCHAR(60),
    gender               VARCHAR(10),
    idproof              VARCHAR(50),
    address              TEXT,
    bedtype              VARCHAR(20),
    roomtype             VARCHAR(20),
    roomnumber           VARCHAR(10),
    checkin              DATE,
    checkout             DATE,
    priceperday          NUMERIC(10,2) DEFAULT 0.00,
    daystayed            INT           DEFAULT 0,
    totalamount          NUMERIC(10,2) DEFAULT 0.00,
    -- Payment approval (finance feature)
    payment_status       VARCHAR(20)   NOT NULL DEFAULT 'Unpaid',
    payment_approved_by  VARCHAR(100),
    payment_approved_at  TIMESTAMP,
    created_at           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- A3. deleted_customers (checked-out archive)
CREATE TABLE IF NOT EXISTS deleted_customers (
    id           SERIAL PRIMARY KEY,
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
    priceperday  NUMERIC(10,2) DEFAULT 0.00,
    daystayed    INT           DEFAULT 0,
    totalamount  NUMERIC(10,2) DEFAULT 0.00,
    deleted_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- A4. feedback
CREATE TABLE IF NOT EXISTS feedback (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(100),
    experience VARCHAR(50),
    message    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- A5. contact_messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(100),
    message    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- A6. customer_login (guest auth + security hint for password reset)
CREATE TABLE IF NOT EXISTS customer_login (
    id                   SERIAL PRIMARY KEY,
    username             VARCHAR(100) NOT NULL,
    email                VARCHAR(100) NOT NULL UNIQUE,
    password             VARCHAR(255) NOT NULL,
    address              VARCHAR(200),
    -- Password reset security
    security_hint        VARCHAR(200),
    security_answer      VARCHAR(100),
    must_change_password SMALLINT     NOT NULL DEFAULT 0,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SECTION B: NEW BACKEND TABLES
-- ============================================================

-- B1. roles
CREATE TABLE IF NOT EXISTS roles (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(200)
);
INSERT INTO roles (name, description) VALUES
('admin',        'Full system access'),
('manager',      'Operations and reports'),
('receptionist', 'Check-in, check-out, reservations'),
('housekeeping', 'Room cleaning status'),
('cashier',      'Billing and payments'),
('guest',        'Self-service portal')
ON CONFLICT (name) DO NOTHING;

-- B2. users (staff accounts)
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    role_id       INT          NOT NULL DEFAULT 3,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    is_active     SMALLINT     NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO users (role_id, full_name, email, password_hash) VALUES
(2, 'Hotel Manager',   'manager@sabawyan.com', '$2y$10$placeholder_replace_with_bcrypt'),
(5, 'Finance Officer', 'finance@sabawyan.com',  '$2y$10$placeholder_replace_with_bcrypt'),
(3, 'Hotel Staff',     'staff@sabawyan.com',    '$2y$10$placeholder_replace_with_bcrypt')
ON CONFLICT (email) DO NOTHING;

-- B3. guests (extended guest profiles)
CREATE TABLE IF NOT EXISTS guests (
    id            SERIAL PRIMARY KEY,
    user_id       INT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100),
    phone         VARCHAR(20),
    nationality   VARCHAR(60),
    gender        VARCHAR(10),
    date_of_birth DATE,
    id_type       VARCHAR(30),
    id_number     VARCHAR(50),
    address       TEXT,
    is_vip        SMALLINT  DEFAULT 0,
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- B4. room_types
CREATE TABLE IF NOT EXISTS room_types (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(50)   NOT NULL UNIQUE,
    description   TEXT,
    base_price    NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    max_occupancy SMALLINT      NOT NULL DEFAULT 2
);
INSERT INTO room_types (name, base_price, max_occupancy) VALUES
('Single', 800.00,  1),
('Double', 1200.00, 2),
('Deluxe', 2000.00, 2),
('Suite',  4000.00, 4)
ON CONFLICT (name) DO NOTHING;

-- B5. services (additional charges during stay)
CREATE TABLE IF NOT EXISTS services (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    category   VARCHAR(50),
    unit_price NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    is_active  SMALLINT      DEFAULT 1
);
INSERT INTO services (name, category, unit_price) VALUES
('Room Service',     'Food',      150.00),
('Laundry',          'Laundry',    80.00),
('Airport Transfer', 'Transport', 500.00),
('Extra Bed',        'Room',      200.00)
ON CONFLICT DO NOTHING;

-- B6. inventory_items
CREATE TABLE IF NOT EXISTS inventory_items (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    category      VARCHAR(50),
    unit          VARCHAR(20)   DEFAULT 'Pieces',
    quantity      INT           NOT NULL DEFAULT 0,
    reorder_level INT           NOT NULL DEFAULT 10,
    unit_cost     NUMERIC(10,2) DEFAULT 0.00,
    supplier      VARCHAR(100),
    notes         TEXT,
    updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SECTION C: TABLES WITH FOREIGN KEYS
-- ============================================================

-- C1. reservations
CREATE TABLE IF NOT EXISTS reservations (
    id                  SERIAL PRIMARY KEY,
    reservation_code    VARCHAR(20)   NOT NULL UNIQUE,
    guest_id            INT           NOT NULL,
    room_id             INT           NOT NULL,
    check_in_date       DATE          NOT NULL,
    check_out_date      DATE          NOT NULL,
    adults              SMALLINT      NOT NULL DEFAULT 1,
    children            SMALLINT      NOT NULL DEFAULT 0,
    status              VARCHAR(20)   NOT NULL DEFAULT 'Pending',
    special_requests    TEXT,
    total_amount        NUMERIC(10,2) DEFAULT 0.00,
    advance_paid        NUMERIC(10,2) DEFAULT 0.00,
    source              VARCHAR(20)   DEFAULT 'Online',
    created_by          INT,
    cancelled_at        TIMESTAMP,
    cancellation_reason TEXT,
    created_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_guest FOREIGN KEY (guest_id)   REFERENCES guests(id),
    CONSTRAINT fk_res_room  FOREIGN KEY (room_id)    REFERENCES rooms(id),
    CONSTRAINT fk_res_user  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_res_dates CHECK (check_out_date > check_in_date)
);

-- C2. checkins
CREATE TABLE IF NOT EXISTS checkins (
    id                SERIAL PRIMARY KEY,
    reservation_id    INT,
    guest_id          INT         NOT NULL,
    room_id           INT         NOT NULL,
    check_in_datetime TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_checkout DATE        NOT NULL,
    actual_checkout   TIMESTAMP,
    adults            SMALLINT    DEFAULT 1,
    children          SMALLINT    DEFAULT 0,
    id_verified       SMALLINT    DEFAULT 0,
    checked_in_by     INT,
    notes             TEXT,
    status            VARCHAR(20) NOT NULL DEFAULT 'Active',
    created_at        TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ci_res   FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    CONSTRAINT fk_ci_guest FOREIGN KEY (guest_id)       REFERENCES guests(id),
    CONSTRAINT fk_ci_room  FOREIGN KEY (room_id)        REFERENCES rooms(id),
    CONSTRAINT fk_ci_user  FOREIGN KEY (checked_in_by)  REFERENCES users(id) ON DELETE SET NULL
);

-- C3. invoices
CREATE TABLE IF NOT EXISTS invoices (
    id             SERIAL PRIMARY KEY,
    invoice_number VARCHAR(20)   NOT NULL UNIQUE,
    checkin_id     INT           NOT NULL UNIQUE,
    guest_id       INT           NOT NULL,
    room_charge    NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    service_charge NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    discount       NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    tax            NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    total_amount   NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    status         VARCHAR(20)   NOT NULL DEFAULT 'Draft',
    issued_at      TIMESTAMP,
    due_date       DATE,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inv_ci    FOREIGN KEY (checkin_id) REFERENCES checkins(id),
    CONSTRAINT fk_inv_guest FOREIGN KEY (guest_id)   REFERENCES guests(id)
);

-- C4. payments
CREATE TABLE IF NOT EXISTS payments (
    id           SERIAL PRIMARY KEY,
    invoice_id   INT           NOT NULL,
    amount       NUMERIC(10,2) NOT NULL,
    method       VARCHAR(20)   NOT NULL DEFAULT 'Cash',
    reference    VARCHAR(100),
    status       VARCHAR(20)   DEFAULT 'Completed',
    processed_by INT,
    paid_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    notes        TEXT,
    CONSTRAINT fk_pay_inv  FOREIGN KEY (invoice_id)   REFERENCES invoices(id),
    CONSTRAINT fk_pay_user FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- C5. checkouts
CREATE TABLE IF NOT EXISTS checkouts (
    id            SERIAL PRIMARY KEY,
    checkin_id    INT       NOT NULL UNIQUE,
    invoice_id    INT       NOT NULL,
    checkout_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nights_stayed SMALLINT  NOT NULL DEFAULT 1,
    processed_by  INT,
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_co_ci   FOREIGN KEY (checkin_id)   REFERENCES checkins(id),
    CONSTRAINT fk_co_inv  FOREIGN KEY (invoice_id)   REFERENCES invoices(id),
    CONSTRAINT fk_co_user FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- C6. checkin_charges (extra services billed during stay)
CREATE TABLE IF NOT EXISTS checkin_charges (
    id         SERIAL PRIMARY KEY,
    checkin_id INT           NOT NULL,
    service_id INT           NOT NULL,
    quantity   SMALLINT      NOT NULL DEFAULT 1,
    unit_price NUMERIC(10,2) NOT NULL,
    total      NUMERIC(10,2) NOT NULL,
    added_by   INT,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cc_ci      FOREIGN KEY (checkin_id) REFERENCES checkins(id),
    CONSTRAINT fk_cc_service FOREIGN KEY (service_id) REFERENCES services(id),
    CONSTRAINT fk_cc_user    FOREIGN KEY (added_by)   REFERENCES users(id) ON DELETE SET NULL
);

-- C7. housekeeping_tasks
CREATE TABLE IF NOT EXISTS housekeeping_tasks (
    id           SERIAL PRIMARY KEY,
    room_id      INT         NOT NULL,
    task_type    VARCHAR(20) DEFAULT 'Cleaning',
    status       VARCHAR(20) DEFAULT 'Pending',
    assigned_to  INT,
    notes        TEXT,
    started_at   TIMESTAMP,
    completed_at TIMESTAMP,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hk_room FOREIGN KEY (room_id)     REFERENCES rooms(id),
    CONSTRAINT fk_hk_user FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- C8. inventory_transactions
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id         SERIAL PRIMARY KEY,
    item_id    INT         NOT NULL,
    type       VARCHAR(20) NOT NULL DEFAULT 'StockIn',
    quantity   INT         NOT NULL,
    reason     VARCHAR(200),
    done_by    INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_it_item FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    CONSTRAINT fk_it_user FOREIGN KEY (done_by)  REFERENCES users(id) ON DELETE SET NULL
);

-- C9. audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGSERIAL PRIMARY KEY,
    user_id    INT,
    action     VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id  INT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- SECTION D: FOREIGN KEYS ON INDEPENDENT TABLES
-- ============================================================

ALTER TABLE guests
    ADD CONSTRAINT fk_gs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE users
    ADD CONSTRAINT fk_us_role FOREIGN KEY (role_id) REFERENCES roles(id);

-- ============================================================
-- SECTION E: INDEXES FOR PERFORMANCE
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_customer_email      ON customer(email);
CREATE INDEX IF NOT EXISTS idx_customer_roomnumber ON customer(roomnumber);
CREATE INDEX IF NOT EXISTS idx_customer_payment    ON customer(payment_status);
CREATE INDEX IF NOT EXISTS idx_deleted_checkout    ON deleted_customers(checkout);
CREATE INDEX IF NOT EXISTS idx_deleted_roomnumber  ON deleted_customers(roomnumber);
CREATE INDEX IF NOT EXISTS idx_rooms_status        ON rooms(status);
CREATE INDEX IF NOT EXISTS idx_feedback_created    ON feedback(created_at);
CREATE INDEX IF NOT EXISTS idx_customer_login_email ON customer_login(email);
CREATE INDEX IF NOT EXISTS idx_audit_created       ON audit_logs(created_at);
