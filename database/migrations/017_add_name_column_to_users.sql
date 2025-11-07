-- Add name column to users table
ALTER TABLE users ADD COLUMN name VARCHAR(100) NULL AFTER display_name;

-- Populate name column with display_name values where name is NULL
UPDATE users SET name = display_name WHERE name IS NULL;

-- Make name column NOT NULL
ALTER TABLE users MODIFY COLUMN name VARCHAR(100) NOT NULL;

-- Add index for name column
ALTER TABLE users ADD INDEX idx_name (name);