-- SQL Migration: Remove delivery and assembly fees from categories
-- Since fees are now tracked at the product level, these columns are no longer needed

ALTER TABLE categories DROP COLUMN delivery_fee;
ALTER TABLE categories DROP COLUMN assembly_fee;
