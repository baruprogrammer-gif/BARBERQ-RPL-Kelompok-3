<?php
require __DIR__.'/../includes/bootstrap.php';
$user = require_login('customer');
$stmt = db()->prepare("SELECT r.*,s.name service_name,s.price
    FROM reservations r JOIN services s ON s.id=r.service_id
    WHERE r.user_id=? AND r.status IN ('BOOKED','WAITING','IN_SERVICE')
    ORDER BY r.reservation_date,r.reservation_time,r.id");
$stmt->execute([$user['id']]);
$reservations = $stmt->fetchAll();
render_header('Reservasi Aktif');
page_head('Reservasi aktif','Lihat semua jadwal kunjunganmu. Setiap reservasi memiliki status dan nomor antreannya sendiri.', '<a class="btn secondary" href="'.e(app_url('pelanggan/reservasi.php')).'">'.ui_icon('plus',16).' Reservasi baru</a>');
if (!$reservations): ?>
<div class="empty"><div class="empty-icon"><?= ui_icon('calendar',30) ?></div><h3>Belum ada reservasi aktif</h3><p>Ayo pilih layanan dan atur jadwal kunjungan berikutnya.</p><div style="margin-top:20px"><a class="btn" href="<?= e(app_url('pelanggan/reservasi.php')) ?>">Buat reservasi <?= ui_icon('arrow',16) ?></a></div></div>
<?php else: ?>
<div class="booking-list">
<?php foreach ($reservations as $r): ?>
<article class="card booking-detail">
  <div class="booking-detail-heading"><span class="service-icon"><?= ui_icon('scissors',25) ?></span><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></div>
  <div class="overline">LAYANAN TERPILIH</div><h2><?= e($r['service_name']) ?></h2>
  <div class="detail-grid">
    <div><span class="overline">TANGGAL</span><strong><?= e(date('d M Y',strtotime($r['reservation_date']))) ?></strong></div>
    <div><span class="overline">JAM</span><strong><?= e(substr($r['reservation_time'],0,5)) ?> WIB</strong></div>
    <div><span class="overline">HARGA LAYANAN</span><strong><?= e(rupiah($r['price'])) ?></strong></div>
    <div><span class="overline">NOMOR ANTREAN</span><strong><?= $r['queue_number'] ? e((string)$r['queue_number']) : 'Belum tersedia' ?></strong></div>
  </div>
  <div class="actions" style="margin-top:25px">
    <a class="btn secondary" href="<?= e(app_url('pelanggan/antrean.php?id='.(int)$r['id'])) ?>"><?= ui_icon('ticket',17) ?> Pantau antrean</a>
    <?php if (in_array($r['status'], ['BOOKED','WAITING'], true)): ?>
    <form class="inline" method="post" action="<?= e(app_url('actions/cancel_reservation.php')) ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <button class="btn ghost" type="submit" data-confirm="Yakin ingin membatalkan reservasi ini?">Batalkan reservasi</button>
    </form>
    <?php endif; ?>
  </div>
</article>
<?php endforeach; ?>
</div>
<?php endif; render_footer(); ?>
