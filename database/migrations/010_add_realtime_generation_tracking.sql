-- Add real-time generation tracking fields to ai_generation_jobs table

ALTER TABLE ai_generation_jobs 
ADD COLUMN current_tokens INT DEFAULT 0 COMMENT 'Tokens generated so far',
ADD COLUMN tokens_per_second DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Generation speed in tokens/sec',
ADD COLUMN elapsed_time INT DEFAULT 0 COMMENT 'Elapsed time in milliseconds',
ADD COLUMN estimated_time_remaining INT DEFAULT 0 COMMENT 'Estimated time to completion in milliseconds',
ADD COLUMN last_update_at TIMESTAMP NULL COMMENT 'Last progress update timestamp';

-- Add index for efficient polling
ALTER TABLE ai_generation_jobs 
ADD INDEX idx_status_updated (status, updated_at);
