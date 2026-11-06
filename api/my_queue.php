<?php
require __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
$user = current_user();
if (!$user || $user['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['ok'=>false,'message'=>'Login diperlukan.']);
    exit;
}
$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'ID reservasi tidak valid.']);
    exit;
}
 
$stmt = db()->prepare("SELECT queue_number,status FROM reservations WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$id,$user['id']]);
$r = $stmt->fetch();
if (!$r) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'message'=>'Reservasi tidak ditemukan.']);
    exit;
}
echo json_encode(['ok'=>true,'queue'=>[
    'number'=>$r['queue_number'] !== null ? (string)$r['queue_number'] : null,
    'status'=>$r['status'],
]],JSON_UNESCAPED_UNICODE);
