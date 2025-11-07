<?php
$content = file_get_contents('/var/www/html/src/Controllers/SearchController.php');
echo substr($content, 0, 500);