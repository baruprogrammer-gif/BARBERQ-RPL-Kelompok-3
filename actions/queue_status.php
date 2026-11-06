<?php
require __DIR__ . '/../includes/bootstrap.php';
require_login('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/reservasi.php');
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$target = (string)($_POST['status'] ?? '');
$allowed = ['WAITING','IN_SERVICE','COMPLETED','CANCELLED'];
if ($id < 1 || !in_array($target,$allowed,true)) {
    flash('error','Permintaan atau status tidak valid.');
    redirect('admin/reservasi.php');
}
$pdo = db();
$find = $pdo->prepare('SELECT reservation_date FROM reservations WHERE id=? LIMIT 1');
$find->execute([$id]);
$date = $find->fetchColumn();
if (!$date) {
    flash('error','Reservasi tidak ditemukan.');
    redirect('admin/reservasi.php');
}
$transitions = [
    'BOOKED'=>['WAITING','CANCELLED'],
    'WAITING'=>['IN_SERVICE','CANCELLED'],
    'IN_SERVICE'=>['COMPLETED'],
];
$locked = false;
$lockName = 'bq-queue-'.sha1((string)$date);
try {
     
    if ($target === 'WAITING') {
        $getLock = $pdo->prepare('SELECT GET_LOCK(?, 8)');
        $getLock->execute([$lockName]);
        if ((int)$getLock->fetchColumn() !== 1) {
            throw new RuntimeException('Antrean sedang diproses. Coba lagi sebentar.');
        }
        $locked = true;
    }
    $pdo->beginTransaction();
     
     
    $find = $pdo->prepare('SELECT * FROM reservations WHERE id=? FOR UPDATE');
    $find->execute([$id]);
    $r = $find->fetch();
    if ($r && $target === 'CANCELLED' && in_array($r['status'], ['IN_SERVICE', 'COMPLETED'], true)) {
        throw new RuntimeException('Pembatalan ditolak: pelayanan sudah dimulai atau selesai.');
    }
    if (!$r || !in_array($target,$transitions[$r['status']] ?? [],true)) {
        throw new RuntimeException('Status reservasi sudah berubah atau alur tindakan tidak sesuai. Muat ulang halaman.');
    }
    $queue = $r['queue_number'];
    if ($target === 'WAITING' && $queue === null) {
        $q = $pdo->prepare('SELECT COALESCE(MAX(queue_number),0)+1 FROM reservations WHERE reservation_date=?');
        $q->execute([$r['reservation_date']]);
        $queue = (int)$q->fetchColumn();
    }
    $save = $pdo->prepare('UPDATE reservations SET status=?,queue_number=?,updated_at=NOW() WHERE id=?');
    $save->execute([$target,$queue,$id]);
    $pdo->commit();
    flash('success','Status antrean berhasil diperbarui.');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error',$e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('BARBERQ queue failed: '.$e->getMessage());
    flash('error','Gagal memperbarui antrean. Silakan coba kembali.');
} finally {
    if ($locked) {
        try {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        } catch (Throwable $e) { error_log('BARBERQ queue lock release failed: '.$e->getMessage()); }
    }
}
redirect('admin/reservasi.php?date='.$date);
