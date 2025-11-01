-- Add token tracking fields to ai_applications table
-- Migration: 009_add_token_tracking_to_ai_applications
-- Date: 2025-11-01

ALTER TABLE ai_applications 
ADD COLUMN input_tokens INT DEFAULT 0 AFTER generation_model,
ADD COLUMN output_tokens INT DEFAULT 0 AFTER input_tokens,
ADD COLUMN total_tokens INT DEFAULT 0 AFTER output_tokens,
ADD INDEX idx_model_tokens (generation_model, total_tokens);

-- Update schema.sql documentation
-- ai_applications table now includes:
-- - input_tokens: Number of tokens in the input prompt
-- - output_tokens: Number of tokens in the generated output
-- - total_tokens: Total tokens used (input + output)
-- - Index on generation_model and total_tokens for analytics queries
