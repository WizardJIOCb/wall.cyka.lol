-- Fix UTF-8 encoding issues for posts table
-- Change to utf8mb4 to support full Unicode including emoji and Cyrillic

ALTER TABLE posts 
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE posts 
MODIFY content_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY content_html TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY repost_commentary TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Also fix ai_applications table
ALTER TABLE ai_applications
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE ai_applications
MODIFY user_prompt TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY html_content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY css_content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY js_content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
MODIFY error_message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
