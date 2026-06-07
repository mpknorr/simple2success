<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/includes/conn.php';
    echo 'conn.php: OK<br>';
} catch (\Throwable $e) {
    echo '<b>conn.php FEHLER:</b> ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo 'Datei: ' . $e->getFile() . ' Zeile: ' . $e->getLine() . '<br>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    exit;
}

echo 'DB: OK<br>';
echo 'PHP: ' . PHP_VERSION . '<br>';
echo 'Fertig.';
