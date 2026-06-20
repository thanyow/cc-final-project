<?php
require __DIR__ . '/../app/config.php';

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'instance' => $instanceName,
    'timestamp' => gmdate('c'),
]);
?>
