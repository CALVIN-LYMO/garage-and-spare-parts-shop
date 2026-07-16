-- ============================================================
-- GARAGE AUTO REPAIR MANAGEMENT SYSTEM
-- Database: garage_db
-- Design: Third Normal Form (3NF)
-- Author: BIT Student - CBE
-- ============================================================

CREATE DATABASE IF NOT EXISTS garage_db;
USE garage_db;

-- ------------------------------------------------------------
-- Table 1: users (Admin & Mechanic accounts)
-- All personal data stored encrypted (AES-256-CBC)
-- ------------------------------------------------------------
CREATE TABLE users (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    username    VARCHAR(255) NOT NULL UNIQUE,     -- plaintext (used for login lookup)
    password    VARCHAR(255) NOT NULL,             -- bcrypt hashed
    full_name   VARCHAR(500),                      -- encrypted
    email       VARCHAR(500),                      -- encrypted
    phone       VARCHAR(500),                      -- encrypted
    location    VARCHAR(500),                      -- encrypted mechanic location
    role        ENUM('admin','mechanic') NOT NULL DEFAULT 'mechanic',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table 2: customers
-- All PII encrypted
-- ------------------------------------------------------------
CREATE TABLE customers (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    full_name   VARCHAR(500) NOT NULL,             -- encrypted
    phone       VARCHAR(500),                      -- encrypted
    email       VARCHAR(500),                      -- encrypted
    address     TEXT,                              -- encrypted
    password    VARCHAR(255) NULL,                 -- hashed for customer login
    is_active   TINYINT(1) DEFAULT 1,
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Table 3: vehicles
-- Separated from customers (3NF: one customer can have many vehicles)
-- ------------------------------------------------------------
CREATE TABLE vehicles (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    customer_id     INT NOT NULL,
    plate_number    VARCHAR(500) NOT NULL,          -- encrypted
    make            VARCHAR(500),                   -- encrypted (e.g. Toyota)
    model           VARCHAR(500),                   -- encrypted (e.g. Corolla)
    year            VARCHAR(500),                   -- encrypted
    color           VARCHAR(500),                   -- encrypted
    engine_number   VARCHAR(500),                   -- encrypted
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table 4: repair_jobs
-- Core table — one job per vehicle visit
-- ------------------------------------------------------------
CREATE TABLE repair_jobs (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id      INT NOT NULL,
    mechanic_id     INT,
    job_description TEXT,                           -- encrypted
    diagnosis       TEXT,                           -- encrypted
    status          VARCHAR(100) DEFAULT 'pending', -- encrypted
    date_received   VARCHAR(500),                   -- encrypted
    date_completed  VARCHAR(500),                   -- encrypted
    total_cost      VARCHAR(500),                   -- encrypted
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id)  REFERENCES vehicles(id)  ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES users(id)     ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Table 5: payments
-- Separated from repair_jobs (3NF: payment details independent)
-- ------------------------------------------------------------
CREATE TABLE payments (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    job_id      INT NOT NULL,
    amount      VARCHAR(500),                       -- encrypted
    method      VARCHAR(500),                       -- encrypted (cash/mpesa/bank)
    reference   VARCHAR(500),                       -- encrypted
    notes       TEXT,                               -- encrypted
    paid_at     VARCHAR(500),                       -- encrypted
    recorded_by INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)      REFERENCES repair_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)       ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Table 6: services (lookup table — 3NF: avoid repeating service names)
-- ------------------------------------------------------------
CREATE TABLE services (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(500) NOT NULL,             -- encrypted
    base_price   VARCHAR(500),                      -- encrypted
    description  TEXT,                              -- encrypted
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table 7: job_services (many-to-many: job <-> services)
-- 3NF: removes repeating service groups from repair_jobs
-- ------------------------------------------------------------
CREATE TABLE job_services (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    job_id     INT NOT NULL,
    service_id INT NOT NULL,
    price      VARCHAR(500),                        -- encrypted (actual charged price)
    FOREIGN KEY (job_id)     REFERENCES repair_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)    ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table 8: categories (product taxonomy)
-- ------------------------------------------------------------
CREATE TABLE categories (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(500) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table 9: products (spare parts shop catalog)
-- ------------------------------------------------------------
CREATE TABLE products (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    category_id  INT,
    name         VARCHAR(500) NOT NULL,
    description  TEXT,
    image_path   VARCHAR(500),
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock        INT NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Table 10: service_requests (mobile service orders)
-- ------------------------------------------------------------
CREATE TABLE service_requests (
    id                    INT PRIMARY KEY AUTO_INCREMENT,
    customer_id           INT NOT NULL,
    vehicle_id            INT,
    service_id            INT,
    issue_description     TEXT,
    location              VARCHAR(500),
    preferred_date        DATE,
    assigned_mechanic_id  INT,
    status                ENUM('pending','in-progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    notes                 TEXT,
    created_by            INT,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id)          REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id)           REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id)           REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_mechanic_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)           REFERENCES users(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Table 11: orders (spare parts orders)
-- ------------------------------------------------------------
CREATE TABLE orders (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    customer_id     INT NOT NULL,
    total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_status  ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    shipping_address TEXT,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table 12: order_items (products in each order)
-- ------------------------------------------------------------
CREATE TABLE order_items (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    order_id    INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- ------------------------------------------------------------
-- Table 13: mechanics_assignments (service request assignments)
-- ------------------------------------------------------------
CREATE TABLE mechanics_assignments (
    id                 INT PRIMARY KEY AUTO_INCREMENT,
    service_request_id INT NOT NULL,
    mechanic_id        INT NOT NULL,
    assigned_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status             ENUM('assigned','in-progress','completed','cancelled') NOT NULL DEFAULT 'assigned',
    notes              TEXT,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id)        REFERENCES users(id)            ON DELETE CASCADE
);
