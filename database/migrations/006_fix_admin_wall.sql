-- Migration 006: Fix admin wall to match schema
-- This migration ensures the admin wall has all required columns

INSERT IGNORE INTO walls (
    wall_id, user_id, wall_slug, display_name, description, 
    privacy_level, allow_comments, allow_reactions, allow_reposts
) VALUES (
    1, 1, 'admin', 'Admin Wall', 'Official system administrator wall', 
    'public', TRUE, TRUE, TRUE
) ON DUPLICATE KEY UPDATE
    wall_slug = VALUES(wall_slug),
    display_name = VALUES(display_name),
    description = VALUES(description),
    privacy_level = VALUES(privacy_level),
    allow_comments = VALUES(allow_comments),
    allow_reactions = VALUES(allow_reactions),
    allow_reposts = VALUES(allow_reposts);