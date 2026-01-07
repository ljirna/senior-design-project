-- SQL Migration: Add dimensions to products table
-- This script adds width, height, and depth columns to store product dimensions in centimeters

ALTER TABLE products ADD COLUMN IF NOT EXISTS width_cm DECIMAL(8, 2) DEFAULT NULL COMMENT 'Width in centimeters';
ALTER TABLE products ADD COLUMN IF NOT EXISTS height_cm DECIMAL(8, 2) DEFAULT NULL COMMENT 'Height in centimeters';
ALTER TABLE products ADD COLUMN IF NOT EXISTS depth_cm DECIMAL(8, 2) DEFAULT NULL COMMENT 'Depth in centimeters';
ALTER TABLE products ADD COLUMN IF NOT EXISTS weight_kg DECIMAL(8, 2) DEFAULT NULL COMMENT 'Weight in kilograms';

-- Optional: Create a dimensions table for more flexibility
-- CREATE TABLE IF NOT EXISTS product_dimensions (
--     dimension_id INT AUTO_INCREMENT PRIMARY KEY,
--     product_id INT NOT NULL UNIQUE,
--     width_cm DECIMAL(8, 2),
--     height_cm DECIMAL(8, 2),
--     depth_cm DECIMAL(8, 2),
--     weight_kg DECIMAL(8, 2),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
-- );
