# Database Migration Fix: User Preferences Table

## Problem Statement

The application is experiencing two critical issues in the settings page:

1. **Database Error**: Settings cannot be saved due to missing `user_preferences` table
   - Error: `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'wall_social_platform.user_preferences' doesn't exist`
   - This prevents users from saving any preference changes (theme, language, privacy, notifications, etc.)

2. **UI Layout Issue**: The Save button in settings is overlapping with page content
   - The sticky footer containing the Save button is not properly positioned
   - Visual interference affects user experience

## Root Cause Analysis

### Issue 1: Missing Database Table

The `user_preferences` table definition exists in migration file `003_add_user_preferences.sql` but has not been applied to the database. This indicates:

- Migration script exists but was not executed
- The migration runner may have been skipped during deployment
- Database schema is out of sync with application expectations

**Evidence**:
- Migration file located at: `database/migrations/003_add_user_preferences.sql`
- Controller code references the table: `src/Controllers/SettingsController.php` (lines 19, 24, 56, 83, 94)
- Migration runner exists: `database/run_migrations.php`

### Issue 2: Save Button Layout

The settings footer uses `position: sticky` with `bottom: 0` which can cause:
- Overlapping with content when the viewport is small
- Lack of proper spacing between content and footer
- Z-index conflicts with other page elements

**Evidence**:
- CSS at `frontend/src/views/SettingsView.vue` (lines 706-712)
- Missing bottom margin/padding on settings container to accommodate sticky footer

## Solution Design

### Part 1: Database Migration Execution

**Objective**: Ensure the `user_preferences` table is created in the database

**Approach**: Execute the pending migration to create the missing table

**Migration Script Content** (from `003_add_user_preferences.sql`):
- Creates `user_preferences` table with columns:
  - `preference_id`: Auto-increment primary key
  - `user_id`: Foreign key to users table (unique, with CASCADE delete)
  - `theme`: VARCHAR(50), default 'light'
  - `language`: VARCHAR(5), default 'en'
  - `email_notifications`: BOOLEAN, default TRUE
  - `push_notifications`: BOOLEAN, default TRUE
  - `notification_frequency`: ENUM('instant', 'hourly', 'daily'), default 'instant'
  - `privacy_default_wall`: ENUM('public', 'followers', 'private'), default 'public'
  - `privacy_can_follow`: ENUM('everyone', 'mutual', 'nobody'), default 'everyone'
  - `privacy_can_message`: ENUM('everyone', 'followers', 'mutual'), default 'everyone'
  - `accessibility_font_size`: ENUM('small', 'medium', 'large'), default 'medium'
  - `created_at`, `updated_at`: Timestamps
- Adds `preferred_language` column to `users` table

**Migration Runner**: Use existing `database/run_migrations.php` script that:
- Connects to MySQL using Docker environment variables
- Creates database if not exists
- Executes all `.sql` files in `database/migrations/` in alphabetical order
- Handles statement splitting and error reporting
- Provides verification of created tables

**Execution Steps**:

| Step | Action | Verification |
|------|--------|-------------|
| 1 | Navigate to project root | Ensure in correct directory |
| 2 | Run migration via Docker PHP container | Execute: `docker-compose exec php php database/run_migrations.php` |
| 3 | Verify table creation | Check output for "user_preferences" in created tables list |
| 4 | Validate table structure | Confirm all columns exist with correct types |
| 5 | Test settings API | Make PATCH request to `/users/me/settings` endpoint |

**Expected Outcome**:
- `user_preferences` table created with proper schema
- Foreign key constraint to `users` table established
- Default values set for all preference columns
- UTF8MB4 encoding applied for international character support

### Part 2: Settings Footer Layout Fix

**Objective**: Prevent Save button from overlapping with page content

**Current Implementation Issues**:

```
.settings-container {
  margin-bottom: var(--spacing-8);  // Currently ~2rem
}

.settings-footer {
  position: sticky;
  bottom: 0;
  padding: var(--spacing-4) 0;
  border-top: 2px solid var(--color-border);
}
```

**Problems**:
- Sticky footer can overlap with last content items
- No safe zone at bottom of scrollable content
- Mobile viewports especially affected

**Proposed Layout Changes**:

| CSS Selector | Property | Current Value | New Value | Rationale |
|--------------|----------|---------------|-----------|-----------|
| `.settings-view` | `padding-bottom` | Not set | `var(--spacing-20)` (~5rem) | Create safe zone for sticky footer |
| `.settings-footer` | `z-index` | Not set | `var(--z-index-sticky)` (1020) | Ensure footer stays above content |
| `.settings-footer` | `box-shadow` | Not set | `0 -2px 8px rgba(0,0,0,0.1)` | Visual separation from content |

**Responsive Considerations**:

For mobile viewports (max-width: 768px):
- Increase bottom padding to `var(--spacing-24)` (~6rem)
- Make Save button full-width for better touch targets
- Ensure footer doesn't hide on scroll

**Visual Hierarchy**:

```
Settings View Layout:
┌─────────────────────────────────┐
│ Header (h1)                     │
├─────────────────────────────────┤
│ ┌──────┬────────────────────┐  │
│ │ Tabs │ Tab Content        │  │
│ │ Nav  │                    │  │
│ │      │ (Scrollable)       │  │
│ │      │                    │  │
│ │      │                    │  │
│ │      │                    │  │
│ └──────┴────────────────────┘  │
│                                 │
│ [Safe Zone - padding-bottom]   │
├═════════════════════════════════┤ ← Sticky Footer
│  [Save Settings Button]        │
└─────────────────────────────────┘
```

**Additional Improvements**:
- Add `will-change: transform` to footer for smoother sticky behavior
- Consider backdrop blur effect for visual separation
- Ensure keyboard focus visible when Save button is focused

## Implementation Impact

### Database Changes

**Tables Created**:
- `user_preferences`: New table for storing all user customization settings

**Tables Modified**:
- `users`: Adds `preferred_language` column for quick access to user's language preference

**Data Migration**: Not required (table is new, no existing data to migrate)

### Application Components Affected

| Component | Change Type | Description |
|-----------|-------------|-------------|
| `src/Controllers/SettingsController.php` | No change | Already references user_preferences table |
| `frontend/src/views/SettingsView.vue` | CSS update | Modify styles for footer positioning |
| Database schema | New table | user_preferences table creation |

### API Endpoints

No API changes required. Existing endpoints already expect this table:
- `PATCH /users/me/settings` - Updates user preferences
- `GET /users/me/settings` - Retrieves user preferences

### User Experience Impact

**Before Fix**:
- ❌ Settings page shows error when clicking Save
- ❌ No user preferences can be persisted
- ❌ Theme, language, privacy settings don't work
- ❌ Save button overlaps content

**After Fix**:
- ✅ Settings save successfully
- ✅ User preferences persist across sessions
- ✅ All customization options functional
- ✅ Clean visual separation of Save button
- ✅ No content overlap in any viewport size

## Testing Strategy

### Database Migration Testing

| Test Case | Steps | Expected Result |
|-----------|-------|-----------------|
| Migration execution | Run `php database/run_migrations.php` | All migrations execute without errors |
| Table structure | Query `DESCRIBE user_preferences` | All columns present with correct types |
| Foreign key constraint | Insert invalid user_id | Error due to FK constraint |
| Default values | Insert row with only user_id | All defaults populated |
| Cascade delete | Delete user record | Corresponding preferences deleted |

### Settings Functionality Testing

| Test Case | Steps | Expected Result |
|-----------|-------|-----------------|
| Save theme preference | Select theme, click Save | Theme persisted to database |
| Save language | Change language, click Save | Language saved and UI updates |
| Save privacy settings | Modify privacy, click Save | Settings stored in user_preferences |
| Save notifications | Toggle notifications, click Save | Boolean values saved correctly |
| First-time user | New user accesses settings | Preferences row auto-created with defaults |

### UI Layout Testing

| Test Case | Viewport | Expected Result |
|-----------|----------|-----------------|
| Desktop save button | 1920x1080 | Button visible, no overlap |
| Tablet save button | 768x1024 | Button visible with padding |
| Mobile save button | 375x667 | Full-width button, no overlap |
| Scroll behavior | All sizes | Footer stays at bottom when scrolling |
| Long content | All sizes | Safe zone prevents content hiding |

## Rollback Plan

If issues occur after migration:

1. **Database Rollback**:
   - Drop `user_preferences` table
   - Remove `preferred_language` column from `users` table
   - Rollback SQL provided:

```
Rollback Operations:
- DROP TABLE IF EXISTS user_preferences;
- ALTER TABLE users DROP COLUMN IF EXISTS preferred_language;
```

2. **Frontend Rollback**:
   - Revert CSS changes in `SettingsView.vue`
   - Restore previous sticky footer styles

3. **Verification**:
   - Confirm application loads without database errors
   - Verify settings page renders (even if saving doesn't work)

## Success Criteria

The solution is considered successful when:

1. ✅ Migration runs without errors
2. ✅ `user_preferences` table exists with correct schema
3. ✅ Users can save all settings types (theme, language, privacy, notifications)
4. ✅ Settings persist across login sessions
5. ✅ Save button is visually separated from content
6. ✅ No overlapping occurs in any common viewport size
7. ✅ Backend returns success response when saving settings
8. ✅ No JavaScript console errors related to settings

## Dependencies

**Required for Execution**:
- Docker containers must be running (`docker-compose up -d`)
- MySQL container must be healthy and accessible
- PHP container has access to migration files
- Database credentials in environment variables are correct

**No External Dependencies**:
- No new packages or libraries required
- No API changes needed
- No frontend build process changes

## Security Considerations

**Database Security**:
- Foreign key constraint ensures referential integrity
- CASCADE delete prevents orphaned preference records
- ENUM constraints prevent invalid values
- UTF8MB4 encoding prevents character encoding vulnerabilities

**Data Privacy**:
- User preferences are personal data - ensure proper access controls
- Only authenticated user can modify their own preferences
- API endpoints should verify user ownership before updates

**Input Validation**:
- Controller validates preference values against allowed options
- ENUM fields in database enforce value constraints
- Language codes validated to prevent injection

## Monitoring and Validation

**Post-Deployment Checks**:

1. Database health check
2. Settings API response time monitoring
3. Error rate for `/users/me/settings` endpoint
4. User preference update success rate

**Logging**:
- Log migration execution results
- Log any FK constraint violations
- Log settings update failures for debugging

**Metrics to Track**:
- Number of users with saved preferences
- Most common theme/language selections
- Settings update frequency
- Error rates before and after fix
