<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$externalId = $_GET['external_id'] ?? '';
if ($externalId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'external_id é obrigatório.']);
    exit;
}

$stmt = db()->prepare("SELECT status, amount FROM orders WHERE external_id = ?");
$stmt->execute([$externalId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido não encontrado.']);
    exit;
}

echo json_encode($row);
