<?php
require __DIR__.'/../includes/bootstrap.php';
$user=require_login('customer');
$pdo=db();
$stmt=$pdo->prepare("SELECT r.*,s.name service_name FROM reservations r JOIN services s ON s.id=r.service_id WHERE r.user_id=? AND r.status IN ('BOOKED','WAITING','IN_SERVICE') ORDER BY r.reservation_date,r.reservation_time,r.id");
$stmt->execute([$user['id']]);
$activeItems=$stmt->fetchAll();
$activeCount=count($activeItems);
$today=date('Y-m-d');
$visit=null;
foreach ($activeItems as $booking) {if ($booking['reservation_date']===$today) {$visit=$booking;break;}}
if (!$visit) $visit=$activeItems[0]??null;
$stmt=$pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id=? AND status='COMPLETED'");
$stmt->execute([$user['id']]);
$historyCount=(int)$stmt->fetchColumn();
render_header('Dashboard Pelanggan');
page_head('Dashboard Pelanggan','Halo! Kelola reservasi dan pantau jadwal kunjunganmu.');
?>
<div class="figma-stat-grid">
  <article class="figma-stat"><span class="figma-chip">AKTIF</span><strong><?= $activeCount ?></strong><span>Reservasi aktif</span></article>
  <article class="figma-stat"><span class="figma-chip">ANTREAN</span><strong><?= $visit && $visit['queue_number']!==null ? e(str_pad((string)$visit['queue_number'],2,'0',STR_PAD_LEFT)) : '—' ?></strong><span>Nomor antrean Anda</span></article>
  <article class="figma-stat"><span class="figma-chip">RIWAYAT</span><strong><?= $historyCount ?></strong><span>Kunjungan selesai</span></article>
</div>
<section class="figma-data-card figma-visit-card" aria-labelledby="next-heading">
  <h2 id="next-heading"><?= $visit && $visit['reservation_date']===$today ? 'Kunjungan Hari Ini' : 'Reservasi Berikutnya' ?></h2>
  <?php if ($visit): ?>
    <h3><?= e($visit['service_name']) ?> · <?= e(date('d F Y',strtotime($visit['reservation_date']))) ?></h3>
    <p class="muted">Jam <?= e(substr($visit['reservation_time'],0,5)) ?> · Status <?= e($visit['status']) ?></p>
    <div class="figma-visit-actions"><span class="figma-chip"><?= e($visit['status']) ?></span><a class="btn" href="<?= e(app_url('pelanggan/antrean.php?id='.(int)$visit['id'])) ?>">Lihat Detail</a></div>
    <?php if ($activeCount>1): ?><p class="muted small figma-more-bookings">Masih ada <?= $activeCount-1 ?> reservasi lainnya. <a href="<?= e(app_url('pelanggan/reservasi_aktif.php')) ?>">Lihat semuanya</a>.</p><?php endif; ?>
  <?php else: ?>
    <p class="muted">Belum ada reservasi aktif. Pilih layanan dan waktu kunjungan yang tersedia.</p>
    <a class="btn" href="<?= e(app_url('pelanggan/reservasi.php')) ?>">Buat Reservasi</a>
  <?php endif; ?>
</section>
<div class="figma-primary-action"><a class="btn gold" href="<?= e(app_url('pelanggan/reservasi.php')) ?>">Buat Reservasi</a></div>
<?php render_footer(); ?>
