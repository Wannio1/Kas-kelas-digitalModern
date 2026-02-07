-- Run this SQL to update the database schema
-- Add payment_method and verification_status columns if they don't exist

ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) DEFAULT 'qris',
ADD COLUMN IF NOT EXISTS verification_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL;
