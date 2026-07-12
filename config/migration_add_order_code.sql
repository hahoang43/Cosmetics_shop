-- Migration: Add order_code column to Orders table
-- This script adds a unique order code column to track orders by a 7-character alphanumeric code

-- Check if column doesn't exist before adding
ALTER TABLE Orders ADD COLUMN order_code VARCHAR(7) DEFAULT NULL UNIQUE COMMENT 'Mã đơn hàng gồm 7 ký tự (VD: aB3xYz9)';

-- Note: If you get an error "Duplicate column name", it means the column already exists and you can ignore this script.
