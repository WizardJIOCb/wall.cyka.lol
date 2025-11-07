# Database Migration Idempotency Fixes

**Date:** November 7, 2025, 11:30  
**Tokens Used:** ~46,000

## Problem

Two database migrations were failing with non-idempotent SQL:

1. **003_search_system.sql** - Failed attempting to drop index `idx_walls_search` that didn't exist
2. **005_add_reactions_table.sql** - Failed with duplicate key error on `idx_created_at` when re-run

## Root Cause

- Migrations lacked conditional checks for existing database objects
- Separate `CREATE INDEX` statements outside `CREATE TABLE` caused duplicate key errors on re-runs
- Verification queries (`SHOW INDEX`, `DESCRIBE`) caused unbuffered query errors in migration runner
- Migration runner couldn't handle prepared statements with `DELIMITER` syntax

## Solution Implemented

### 1. Fixed 003_search_system.sql

**Changes:**
- Created stored procedure `add_index_if_not_exists()` to safely add indexes
- Procedure checks `INFORMATION_SCHEMA.STATISTICS` before creating indexes
- Supports both FULLTEXT and regular indexes
- Removed all verification queries from migration file (commented out)
- Applied to all index creation operations (9 indexes total)

**Pattern Used:**
```sql
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS add_index_if_not_exists(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT,
    IN p_is_fulltext BOOLEAN
)
BEGIN
    DECLARE index_count INT;
    
    SELECT COUNT(*) INTO index_count
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = p_table
      AND index_name = p_index;
    
    IF index_count = 0 THEN
        IF p_is_fulltext THEN
            SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD FULLTEXT INDEX ', p_index, ' (', p_columns, ')');
        ELSE
            SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD INDEX ', p_index, ' (', p_columns, ')');
        END IF;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Usage example
CALL add_index_if_not_exists('posts', 'idx_posts_search', 'title, content', TRUE);
```

### 2. Fixed 005_add_reactions_table.sql

**Changes:**
- Moved standalone `CREATE INDEX` statements into `CREATE TABLE` definition
- All indexes now created inline as part of table creation
- Leverages `IF NOT EXISTS` clause on table to ensure idempotency

**Before:**
```sql
CREATE TABLE IF NOT EXISTS reactions (
  -- columns...
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE INDEX idx_reaction_type ON reactions(reaction_type);
CREATE INDEX idx_created_at ON reactions(created_at);
```

**After:**
```sql
CREATE TABLE IF NOT EXISTS reactions (
  -- columns...
  INDEX idx_user (user_id),
  INDEX idx_reaction_type (reaction_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB;
```

### 3. Enhanced Migration Runner (run_migrations.php)

**Changes:**
- Added detection for `DELIMITER` keyword in migration files
- Special handling for stored procedure migrations:
  - Removes `DELIMITER $$` and `DELIMITER ;` commands
  - Splits SQL on `$$` to process procedure blocks separately
  - Executes each block individually via PDO
- Maintains backward compatibility with simple migrations

**Code Pattern:**
```php
if (preg_match('/DELIMITER/i', $sql)) {
    // Remove DELIMITER commands
    $sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
    $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
    
    // Split on $$
    $blocks = preg_split('/\$\$/', $sql);
    
    foreach ($blocks as $block) {
        $block = trim($block);
        if (!empty($block)) {
            $pdo->exec($block);
        }
    }
}
```

## Testing Results

### First Run (Fresh Database State)
```
✓ All 14 migrations executed successfully
✓ Total tables: 28
✓ New tables created: user_follows, notifications, user_preferences, conversations, conversation_participants, messages
```

### Second Run (Idempotency Test)
```
✓ All 14 migrations executed successfully (no errors)
✓ No duplicate key errors
✓ No index creation failures
✓ Database state unchanged
```

### Verification
```sql
-- Confirmed idx_walls_search exists
SHOW INDEX FROM walls WHERE Key_name = 'idx_walls_search';

-- Confirmed reactions indexes exist
SHOW INDEX FROM reactions WHERE Key_name IN ('idx_created_at', 'idx_reaction_type');
```

## Benefits

1. **Idempotent Migrations** - Can safely re-run migrations multiple times
2. **No Manual Intervention** - Failed migrations no longer require manual SQL execution
3. **Production Safe** - Safe to run on existing databases without breaking changes
4. **Better Error Handling** - Clear detection of stored procedure vs simple migrations
5. **Future-Proof** - Pattern established for all future migrations

## Files Modified

| File | Lines Changed | Purpose |
|------|---------------|---------|
| `database/migrations/003_search_system.sql` | +45, -60 | Added stored procedure for conditional index creation |
| `database/migrations/005_add_reactions_table.sql` | +2, -4 | Moved indexes into table definition |
| `database/run_migrations.php` | +21, -30 | Enhanced to handle DELIMITER syntax |

## Migration Best Practices Established

For future migrations:

1. **Always check existence before CREATE/DROP:**
   - Use `CREATE TABLE IF NOT EXISTS`
   - Use stored procedures for `ALTER TABLE ADD INDEX`
   - Check `INFORMATION_SCHEMA` before operations

2. **Avoid standalone CREATE INDEX:**
   - Define indexes inline in `CREATE TABLE`
   - Ensures atomic creation with table

3. **Comment out verification queries:**
   - Don't include `SHOW`, `DESCRIBE`, `SELECT` in migrations
   - These cause unbuffered query errors
   - Run manually if needed for debugging

4. **Use stored procedures for complex logic:**
   - Wrap conditional DDL in procedures
   - Clean up procedures after use with `DROP PROCEDURE`
   - Supports reusable patterns

## Next Steps

No action required. All migrations now work correctly and are idempotent.

If new migrations are needed:
1. Follow the patterns in `003_search_system.sql` for index creation
2. Define all indexes inline in `CREATE TABLE` statements
3. Test migrations twice (fresh DB + re-run) before committing
