<?php
require __DIR__ . '/bootstrap.php';

$_SESSION = [];
session_destroy();

echo json_encode(['ok' => true]);
