-- Add partial content tracking to ai_generation_jobs table

ALTER TABLE ai_generation_jobs 
ADD COLUMN partial_content_length INT DEFAULT 0 COMMENT 'Length of generated content so far',
ADD COLUMN content_generation_rate DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Characters per second generation rate';
