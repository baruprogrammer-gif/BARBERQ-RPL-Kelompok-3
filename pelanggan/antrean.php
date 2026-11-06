<?php
require __DIR__.'/../includes/bootstrap.php';
$user = require_login('customer');
$pdo = db();
 
$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if ($id !== null && $id !== false && $id > 0) {
    $stmt=$pdo->prepare("SELECT r.*,s.name service_name FROM reservations r JOIN services s ON s.id=r.service_id WHERE r.id=? AND r.user_id=? LIMIT 1");
    $stmt->execute([$id,$user['id']]);
    $r=$stmt->fetch();
} else {
    $stmt=$pdo->prepare("SELECT r.*,s.name service_name FROM reservations r JOIN services s ON s.id=r.service_id WHERE r.user_id=? AND r.status IN ('BOOKED','WAITING','IN_SERVICE') ORDER BY r.reservation_date,r.reservation_time,r.id LIMIT 1");
    $stmt->execute([$user['id']]);
    $r=$stmt->fetch();
    if (!$r) {
        $stmt=$pdo->prepare("SELECT r.*,s.name service_name FROM reservations r JOIN services s ON s.id=r.service_id WHERE r.user_id=? AND r.status='COMPLETED' AND r.reservation_date=? ORDER BY r.updated_at DESC LIMIT 1");
        $stmt->execute([$user['id'],date('Y-m-d')]);
        $r=$stmt->fetch();
    }
}
$stmt=$pdo->prepare("SELECT r.id,r.reservation_date,r.reservation_time,r.status,s.name service_name FROM reservations r JOIN services s ON s.id=r.service_id WHERE r.user_id=? AND r.status IN ('BOOKED','WAITING','IN_SERVICE') ORDER BY r.reservation_date,r.reservation_time,r.id");
$stmt->execute([$user['id']]);
$activeList=$stmt->fetchAll();
render_header('Status Antrean');page_head('Status antrean','Pantau progres masing-masing reservasi, mulai dari kedatangan hingga selesai.');
if (count($activeList) > 1): ?>
<div class="panel queue-switch"><label for="queue-picker" style="margin-top:0">Pilih reservasi yang dipantau</label><select id="queue-picker" data-queue-picker>
<?php foreach ($activeList as $item): ?><option value="<?= e(app_url('pelanggan/antrean.php?id='.(int)$item['id'])) ?>" <?= $r && (int)$r['id']===(int)$item['id']?'selected':'' ?>><?= e($item['service_name']) ?> · <?= e(date('d M Y',strtotime($item['reservation_date']))) ?> · <?= e(substr($item['reservation_time'],0,5)) ?> · <?= e(status_label($item['status'])) ?></option><?php endforeach; ?>
</select></div>
<?php endif;
if (!$r): ?>
<div class="empty"><div class="empty-icon"><?= ui_icon('ticket',30) ?></div><h3>Belum ada antrean</h3><p>Antrean akan tersedia setelah reservasi dibuat dan kedatanganmu dicatat oleh petugas.</p><div style="margin-top:20px"><a class="btn" href="<?= e(app_url('pelanggan/reservasi.php')) ?>">Buat reservasi <?= ui_icon('arrow',17) ?></a></div></div>
<?php else:
$index=['BOOKED'=>0,'WAITING'=>1,'IN_SERVICE'=>2,'COMPLETED'=>3,'CANCELLED'=>-1][$r['status']]??-1;
$description=match($r['status']){
  'BOOKED'=>'Reservasi sudah dibuat. Silakan datang sesuai jadwal.',
  'WAITING'=>'Kamu sudah hadir dan sedang menunggu giliran.',
  'IN_SERVICE'=>'Pelayanan sedang berlangsung.',
  'COMPLETED'=>'Pelayanan selesai. Terima kasih sudah berkunjung!',
  'CANCELLED'=>'Reservasi ini telah dibatalkan.',
  default=>'Informasi status reservasi.',
};
?>
<article class="queue-card" data-live-queue data-queue-url="<?= e(app_url('api/my_queue.php?id='.(int)$r['id'])) ?>">
  <div class="queue-label">Nomor Antrean Anda</div>
  <div class="figma-queue-overview">
    <div class="figma-queue-number"><span data-queue-number><?= $r['queue_number']!==null ? e((string)$r['queue_number']) : '—' ?></span></div>
    <div class="figma-queue-status">
      <h2 data-queue-heading><?= e($r['status']) ?></h2>
      <p data-queue-description><?= e($description) ?></p>
      <span class="badge <?= e(status_class($r['status'])) ?>" data-queue-status aria-live="polite"><?= e($r['status']) ?></span>
    </div>
  </div>
  <span class="figma-queue-detail"><?= e($r['service_name']) ?> · <?= e(date('d M Y',strtotime($r['reservation_date']))) ?> · <?= e(substr($r['reservation_time'],0,5)) ?></span>
  <div data-queue-notice><?php if($r['status']==='CANCELLED'): ?><div class="note">Reservasi tidak lagi aktif.</div><?php endif; ?></div>
  <h3 class="figma-queue-progress">Urutan layanan</h3>
  <div class="figma-queue-stages" data-queue-steps>
    <?php foreach (['BOOKED','WAITING','IN_SERVICE'] as $i=>$step): ?>
      <div class="step <?= $index===$i?'active':($index>$i?'is-done':'') ?>"><?= e($step) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="queue-refresh"><?= ui_icon('refresh',15) ?> <span data-queue-refresh-status>Status antrean diperbarui otomatis.</span></div>
</article>
<div class="actions" style="margin-top:18px"><a class="btn secondary" href="<?= e(app_url('pelanggan/reservasi_aktif.php')) ?>">Reservasi Aktif</a><a class="btn ghost" href="<?= e(app_url('pelanggan/riwayat.php')) ?>">Riwayat</a></div>
<?php endif; render_footer(); ?>
