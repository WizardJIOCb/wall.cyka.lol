# Settings Page Fix - User Preferences Table & Layout

**Date:** November 7, 2025, 11:10 AM  
**Tokens Used:** ~55,000  
**Status:** Partial - Frontend Complete, Database Migration Pending

## Issues Fixed

### 1. Missing user_preferences Table
**Problem:** Settings page throws database error when saving
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'wall_social_platform.user_preferences' doesn't exist
```

**Root Cause:** Migration file `003_add_user_preferences.sql` exists but was never executed on the database.

**Solution:** Database migration must be run to create the table.

**Action Required:** 
```bash
# Start Docker Desktop first, then run:
cd C:\Projects\wall.cyka.lol
docker-compose up -d
docker-compose exec php php database/run_migrations.php
```

**Migration Creates:**
- `user_preferences` table with columns:
  - Theme preference (light/dark/green/blue/cream/high-contrast)
  - Language (en/ru)
  - Email & push notifications settings
  - Privacy defaults (wall visibility, follow permissions, messaging)
  - Font size accessibility options
- `preferred_language` column added to `users` table

### 2. Save Button Overlapping Content ✅
**Problem:** Sticky footer Save button overlaps with page content, especially visible on mobile.

**Solution Applied:**
- Added `padding-bottom: 120px` to `.settings-view` for safe zone
- Increased to `140px` on mobile viewports
- Added `z-index: 100` to settings footer
- Added `box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1)` for visual separation
- Added `will-change: transform` for smoother sticky behavior

**Files Modified:**
- `frontend/src/views/SettingsView.vue` - CSS styles updated

**Build Status:** ✅ Frontend rebuilt successfully
- Output: `../public/assets/SettingsView-*.js` and `*.css`
- All 215 modules transformed
- Build completed in 1.82s

## Technical Details

### Database Schema
The `user_preferences` table structure:
```sql
CREATE TABLE IF NOT EXISTS user_preferences (
  preference_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  theme VARCHAR(50) DEFAULT 'light',
  language VARCHAR(5) DEFAULT 'en',
  email_notifications BOOLEAN DEFAULT TRUE,
  push_notifications BOOLEAN DEFAULT TRUE,
  notification_frequency ENUM('instant', 'hourly', 'daily') DEFAULT 'instant',
  privacy_default_wall ENUM('public', 'followers', 'private') DEFAULT 'public',
  privacy_can_follow ENUM('everyone', 'mutual', 'nobody') DEFAULT 'everyone',
  privacy_can_message ENUM('everyone', 'followers', 'mutual') DEFAULT 'everyone',
  accessibility_font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)
```

### Settings Controller Behavior
`src/Controllers/SettingsController.php` expects this table:
- Line 19: SELECT query to fetch preferences
- Line 24: INSERT for new users without preferences
- Line 83: UPDATE preferences on save

### API Endpoints
- `GET /users/me/settings` - Fetch user preferences
- `PATCH /users/me/settings` - Update preferences

## Testing Required

Once Docker is started and migration is run:

1. **Verify Table Creation:**
```bash
docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 -e "DESCRIBE wall_social_platform.user_preferences;"
```

2. **Test Settings Save:**
- Navigate to Settings page (http://localhost:3000/settings)
- Change theme, language, or any other setting
- Click "Save Settings" button
- Verify success message appears
- Refresh page and confirm settings persisted

3. **Test Layout:**
- Scroll to bottom of settings page
- Verify Save button doesn't overlap content
- Test on mobile viewport (F12 → Device toolbar)
- Check all tabs (Account, Profile, Privacy, Notifications, Appearance, Bricks)

## Known Issues

### Docker Not Running
**Error:** `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified`

**Resolution:** Docker Desktop must be started before running migrations.

### TypeScript Warnings (Pre-existing)
Two type errors in SettingsView.vue (lines 300-301) are unrelated to our changes:
- Theme string type mismatch
- These existed before our modifications

## Next Steps

**REQUIRED BEFORE SETTINGS WORK:**

1. **Start Docker Desktop**
   - Open Docker Desktop application
   - Wait for it to fully start

2. **Run Database Migration**
   ```bash
   cd C:\Projects\wall.cyka.lol
   docker-compose up -d
   docker-compose exec php php database/run_migrations.php
   ```

3. **Verify Migration Success**
   - Check output shows "user_preferences" in created tables list
   - Look for "✓ Success" message

4. **Test Settings Page**
   - Access http://localhost:3000/settings
   - Try saving preferences
   - Verify no database errors

## Files Modified

1. `frontend/src/views/SettingsView.vue`
   - Added padding-bottom to prevent overlap
   - Enhanced sticky footer styling
   - Mobile responsive improvements

2. `history/run.md`
   - Updated migration instructions with warning

3. Frontend build artifacts regenerated in `public/assets/`

## Success Criteria

- [x] Layout fix applied and built
- [ ] Database migration executed (pending Docker start)
- [ ] user_preferences table exists
- [ ] Settings can be saved without errors
- [ ] Settings persist across sessions
- [ ] No content overlap with Save button

## Rollback Plan

If issues occur after migration:

```sql
-- Rollback SQL
DROP TABLE IF EXISTS user_preferences;
ALTER TABLE users DROP COLUMN IF EXISTS preferred_language;
```

To revert frontend changes:
```bash
cd frontend
git checkout src/views/SettingsView.vue
npm run build
```

## Documentation Updated

- Design document created: `.qoder/quests/database-migration.md`
- Run instructions enhanced: `history/run.md`
- This history document: `history/07-11-2025-1110-Settings-Fix-User-Preferences.md`
