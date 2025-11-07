# Database Migration Failure Resolution

## Problem Statement

Two database migrations are failing during execution:

1. **003_search_system.sql** - Fails when attempting to drop index `idx_walls_search` that doesn't exist
2. **005_add_reactions_table.sql** - Fails due to duplicate key name `idx_created_at` already existing

These failures occur because the migrations are not idempotent (safe to run multiple times) and don't check for the existence of database objects before attempting to create or drop them.

## Root Cause Analysis

### Migration 003_search_system.sql
- Attempts to add FULLTEXT index `idx_walls_search` on the `walls` table without checking if it already exists
- Contains verification queries (SHOW INDEX) that execute as part of the migration, causing potential confusion
- Does not use conditional logic (IF EXISTS / IF NOT EXISTS) for index operations

### Migration 005_add_reactions_table.sql  
- Creates table with `IF NOT EXISTS` clause (correct approach)
- However, separate CREATE INDEX statements for `idx_reaction_type` and `idx_created_at` lack conditional checks
- When migration reruns, table creation is skipped but index creation attempts proceed, causing duplicate key errors

## Solution Design

### Objective
Make both migration files idempotent by adding conditional checks for all DDL operations (CREATE/DROP INDEX, ALTER TABLE).

### Approach

#### 1. Fix 003_search_system.sql

**Strategy**: Use conditional index creation/dropping

| Operation | Current Issue | Solution |
|-----------|---------------|----------|
| ADD FULLTEXT INDEX | No existence check | Wrap in stored procedure that checks `INFORMATION_SCHEMA.STATISTICS` before adding index |
| DROP INDEX | Attempts drop without existence check in rollback section | Add IF EXISTS clause (MySQL 5.7.4+) or conditional procedure |
| Verification queries | SHOW INDEX executes during migration | Move to comment block or remove from migration file |

**Implementation Pattern**:
```
Check if index exists in INFORMATION_SCHEMA.STATISTICS
If not exists, execute ALTER TABLE ADD INDEX
If exists, skip operation
```

#### 2. Fix 005_add_reactions_table.sql

**Strategy**: Convert standalone CREATE INDEX to inline index definitions or add conditional checks

| Current Statement | Issue | Solution |
|-------------------|-------|----------|
| CREATE INDEX idx_reaction_type | No IF NOT EXISTS support | Use procedural approach or embed in table creation |
| CREATE INDEX idx_created_at | No IF NOT EXISTS support | Use procedural approach or embed in table creation |

**Two possible approaches**:

**Approach A** (Preferred): Move all index definitions into the CREATE TABLE statement
- Eliminates separate CREATE INDEX statements
- All indexes defined inline are created atomically with table
- IF NOT EXISTS on table handles idempotency

**Approach B**: Add conditional checks using stored procedures
- Check INFORMATION_SCHEMA.STATISTICS before creating each index
- More verbose but allows granular index management

### Migration File Modifications

#### File: 003_search_system.sql

**Changes Required**:
1. Remove or comment out all verification queries (SHOW INDEX, DESCRIBE)
2. Add conditional index creation logic for all ALTER TABLE ADD INDEX statements
3. Ensure idempotency for all DDL operations

**Modified Structure**:
```
Section 1: Conditional FULLTEXT Index Creation
  - Check existence before adding idx_posts_search
  - Check existence before adding idx_walls_search  
  - Check existence before adding idx_users_search
  - Check existence before adding idx_ai_apps_search

Section 2: Create search_logs table (already has IF NOT EXISTS)

Section 3: Conditional Additional Index Creation
  - Check existence before adding idx_posts_reaction_count
  - Check existence before adding idx_posts_comment_count
  - Check existence before adding idx_posts_created
  - Check existence before adding idx_walls_subscriber_count
  - Check existence before adding idx_walls_post_count
  - Check existence before adding idx_users_followers_count

Section 4: Create view with OR REPLACE (already idempotent)

Section 5: Remove all verification queries
```

**Conditional Index Pattern**:
Use MySQL stored procedure or conditional statement:
```
For each index:
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'target_table' 
    AND index_name = 'index_name'
  ) THEN
    ALTER TABLE target_table ADD INDEX index_name (columns);
  END IF;
```

#### File: 005_add_reactions_table.sql

**Changes Required**:
1. Move standalone index definitions into CREATE TABLE statement
2. Remove separate CREATE INDEX statements
3. Ensure all indexes are created as part of table definition

**Modified Structure**:
```
CREATE TABLE IF NOT EXISTS reactions (
  reaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reactable_type ENUM('post', 'comment') NOT NULL,
  reactable_id INT NOT NULL,
  reaction_type ENUM('like', 'love', 'laugh', 'wow', 'sad', 'angry') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_user_reaction (user_id, reactable_type, reactable_id),
  INDEX idx_reactable (reactable_type, reactable_id),
  INDEX idx_user (user_id),
  INDEX idx_reaction_type (reaction_type),
  INDEX idx_created_at (created_at),
  
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

All indexes now created inline - eliminates duplicate key error on rerun.

### Migration Runner Enhancement

**Optional Improvement**: Enhance run_migrations.php to track executed migrations

| Component | Purpose | Benefit |
|-----------|---------|---------|
| migrations_history table | Store executed migration filenames and timestamps | Prevents re-execution of successful migrations |
| Checksum validation | Store MD5/SHA256 hash of migration content | Detect modified migrations |
| Execution tracking | Record success/failure status | Enable rollback and debugging |

**Structure**:
```
Table: migrations_history
Columns:
  - id (auto increment)
  - migration_file (varchar, unique)
  - executed_at (timestamp)
  - status (enum: success, failed)
  - checksum (varchar)
  - error_message (text, nullable)
```

**Modified Runner Logic**:
```
For each migration file:
  1. Calculate checksum
  2. Check if exists in migrations_history with same checksum
  3. If exists and status=success: skip
  4. If exists and status=failed: log warning, optionally skip or retry
  5. If not exists: execute migration
  6. Record result in migrations_history
```

## Testing Strategy

### Pre-Execution Validation
1. Backup current database schema
2. Document existing indexes on affected tables
3. Record current migration execution state

### Test Scenarios

| Scenario | Expected Outcome | Validation Method |
|----------|------------------|-------------------|
| Fresh database | All migrations succeed | Check all tables/indexes created |
| Re-run on existing database | All migrations succeed or skip gracefully | No errors, no duplicate objects |
| Partial failure recovery | Failed migrations can be retried | Idempotent execution |
| Manual schema modifications | Migrations detect existing objects | Conditional logic works correctly |

### Validation Queries

**Check index existence**:
```
SELECT table_name, index_name 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE table_schema = 'wall_social_platform' 
AND index_name IN ('idx_walls_search', 'idx_created_at', 'idx_reaction_type')
ORDER BY table_name, index_name;
```

**Verify reactions table structure**:
```
SHOW CREATE TABLE reactions;
```

**Check search_logs table**:
```
SHOW CREATE TABLE search_logs;
```

## Implementation Steps

1. **Modify 003_search_system.sql**
   - Add conditional checks for all index creation operations
   - Remove verification queries from migration body
   - Test on clean database instance

2. **Modify 005_add_reactions_table.sql**
   - Move standalone CREATE INDEX into table definition
   - Test on clean database instance

3. **Execute migrations**
   - Run `docker-compose exec php php database/run_migrations.php`
   - Verify all migrations succeed

4. **Re-run migrations** (idempotency test)
   - Execute migration runner again
   - Confirm no errors occur
   - Validate database state unchanged

5. **Document changes**
   - Update migration best practices guide
   - Add idempotency checklist for future migrations

## Rollback Strategy

If modifications introduce new issues:

1. Restore database from backup
2. Revert migration files to original versions
3. Manually execute DDL fixes:
   - Drop idx_walls_search if exists: `ALTER TABLE walls DROP INDEX idx_walls_search;`
   - Drop idx_created_at on reactions if exists: `ALTER TABLE reactions DROP INDEX idx_created_at;`
4. Re-apply fixed migrations individually

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Breaking existing queries dependent on indexes | Low | Medium | Test on staging environment first |
| Migration procedure syntax errors | Medium | High | Validate SQL syntax before deployment |
| Existing data incompatibility | Low | Low | Migrations only affect schema, not data |
| Long execution time on large tables | Low | Medium | Run during maintenance window if needed |

## Success Criteria

- All 14 migrations execute successfully without errors
- Migrations can be re-run multiple times without failures
- Database schema matches expected structure
- All indexes properly created on target tables
- No duplicate key errors
- Application functionality unaffected
