-- Run this in phpMyAdmin to add payment tracking to existing customer table
ALTER TABLE customer
    ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) NOT NULL DEFAULT 'Unpaid',
    ADD COLUMN IF NOT EXISTS payment_approved_by VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS payment_approved_at DATETIME NULL;

-- Add must_change_password flag to customer_login
ALTER TABLE customer_login
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0;

-- Add security hint/answer for password reset verification
ALTER TABLE customer_login
    ADD COLUMN IF NOT EXISTS security_hint VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS security_answer VARCHAR(100) NULL;

-- Also ensure rooms table has individual price per room
-- (rooms.price column should already exist — this just confirms)
-- ALTER TABLE rooms ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0.00;
