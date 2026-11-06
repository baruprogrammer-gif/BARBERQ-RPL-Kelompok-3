<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$user = current_user();
if (!$user || $user['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['ok'=>false,'message'=>'Login diperlukan.']);
    exit;
}
$date = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)) || $date < date('Y-m-d')) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'Tanggal tidak valid.']);
    exit;
}
$stmt = db()->prepare("SELECT TIME_FORMAT(reservation_time,'%H:%i') t FROM reservations WHERE reservation_date=? AND status IN ('BOOKED','WAITING','IN_SERVICE')");
$stmt->execute([$date]);
$booked = array_column($stmt->fetchAll(), 't');
$items = [];
foreach (slots_for_date($date) as $slot) {
    $items[] = ['time'=>$slot,'available'=>!in_array($slot,$booked,true) && !($date===date('Y-m-d') && $slot<=date('H:i'))];
}
echo json_encode(['ok'=>true,'slots'=>$items], JSON_UNESCAPED_UNICODE);
