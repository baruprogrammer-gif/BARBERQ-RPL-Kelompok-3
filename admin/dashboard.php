<?php
require __DIR__.'/../includes/bootstrap.php';
require_login('admin');
$pdo=db();
$today=date('Y-m-d');
$summary=$pdo->prepare("SELECT status, COUNT(*) total FROM reservations WHERE reservation_date=? GROUP BY status");
$summary->execute([$today]);
$counts=['BOOKED'=>0,'WAITING'=>0,'IN_SERVICE'=>0,'COMPLETED'=>0,'CANCELLED'=>0];
foreach ($summary->fetchAll() as $item) {
    if (isset($counts[$item['status']])) $counts[$item['status']] = (int)$item['total'];
}
$dailyTotal=array_sum($counts);
$activeTotal=$counts['BOOKED']+$counts['WAITING']+$counts['IN_SERVICE'];
$stmt=$pdo->prepare("SELECT r.id,r.reservation_time,r.status,r.queue_number,u.name customer_name,s.name service_name
                    FROM reservations r JOIN users u ON u.id=r.user_id JOIN services s ON s.id=r.service_id
                    WHERE r.reservation_date=? AND r.status IN ('BOOKED','WAITING','IN_SERVICE')
                    ORDER BY r.created_at DESC,r.id DESC LIMIT 2");
$stmt->execute([$today]);
$recent=$stmt->fetchAll();
render_header('Dashboard Admin');
page_head('Dashboard Admin','Ringkasan aktivitas dan antrean barbershop.');
?>
<div class="figma-stat-grid">
  <article class="figma-stat"><span class="figma-chip">HARI INI</span><strong><?= $dailyTotal ?></strong><span>Reservasi hari ini</span></article>
  <article class="figma-stat"><span class="figma-chip">WAITING</span><strong><?= $counts['WAITING'] ?></strong><span>Menunggu layanan</span></article>
  <article class="figma-stat"><span class="figma-chip">SELESAI</span><strong><?= $counts['COMPLETED'] ?></strong><span>Pelayanan selesai</span></article>
</div>
<section class="figma-data-card" aria-labelledby="latest-heading">
  <h2 id="latest-heading">Reservasi Aktif Terbaru</h2>
  <?php if (!$recent): ?>
    <div class="figma-empty"><p>Belum ada reservasi aktif hari ini.</p><a href="<?= e(app_url('admin/reservasi.php')) ?>">Lihat daftar reservasi</a></div>
  <?php else: ?>
    <div class="figma-table-wrap"><table class="figma-table"><thead><tr><th>Pelanggan</th><th>Layanan</th><th>Jam</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($recent as $item): ?>
      <tr><td><strong><?= e($item['customer_name']) ?></strong></td><td><?= e($item['service_name']) ?></td><td><?= e(substr($item['reservation_time'],0,5)) ?></td><td><span class="status-text status-<?= e(strtolower($item['status'])) ?>"><?= e($item['status']) ?></span></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
  <div class="figma-card-actions"><a class="btn" href="<?= e(app_url('admin/reservasi.php')) ?>">Kelola Antrean</a><span class="muted small">Menampilkan <?= count($recent) ?> dari <?= $activeTotal ?> reservasi aktif</span></div>
</section>
<?php render_footer(); ?>
