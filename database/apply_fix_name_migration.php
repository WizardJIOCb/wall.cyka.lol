<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Utils\Database;

try {
    \ = file_get_contents(__DIR__ . '/migrations/018_fix_name_column_default.sql');
    \ = Database::getConnection();
    \->exec(\);
    echo " Migration applied successfully!\\n\;
}