<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    $stmt = Database::query('DESCRIBE walls');
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo $column['Field'] . ' (' . $column['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}