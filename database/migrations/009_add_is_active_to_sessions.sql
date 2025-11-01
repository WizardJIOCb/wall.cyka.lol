-- Migration 009: Add is_active column to sessions table
-- This migration adds the missing is_active column to the sessions table

-- Add is_active column if it doesn't exist
ALTER TABLE sessions ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE NOT NULL AFTER expires_at;

-- Add index on is_active for better performance
CREATE INDEX IF NOT EXISTS idx_sessions_active ON sessions (is_active);

-- Update existing sessions to be active
UPDATE sessions SET is_active = TRUE WHERE is_active IS NULL;

-- Add a message to indicate completion
SELECT 'Sessions table updated successfully' as migration_result;