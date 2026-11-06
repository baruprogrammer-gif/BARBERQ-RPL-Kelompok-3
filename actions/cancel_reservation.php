<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login('customer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pelanggan/reservasi_aktif.php');
}
verify_csrf();
$id = (int)($_POST['id'] ?? 0);

 
 
$stmt = db()->prepare("UPDATE reservations
    SET status='CANCELLED', updated_at=NOW()
    WHERE id=? AND user_id=? AND status IN ('BOOKED','WAITING')");
$stmt->execute([$id, $user['id']]);
$success = $stmt->rowCount() === 1;
flash($success ? 'success' : 'error', $success
    ? 'Reservasi berhasil dibatalkan.'
    : 'Reservasi tidak dapat dibatalkan. Pastikan reservasi milikmu dan pelayanan belum dimulai.');
redirect('pelanggan/reservasi_aktif.php');
