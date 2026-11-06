<?php
require __DIR__.'/../includes/bootstrap.php';$user=require_login('customer');
$stmt=db()->prepare("SELECT r.*,s.name service_name FROM reservations r JOIN services s ON s.id=r.service_id WHERE r.user_id=? AND r.status='COMPLETED' ORDER BY r.reservation_date DESC,r.reservation_time DESC");$stmt->execute([$user['id']]);$rows=$stmt->fetchAll();
render_header('Riwayat Pelayanan');page_head('Riwayat kunjungan','Semua pelayanan selesai yang tercatat di akunmu.');
if(!$rows): ?>
<div class="empty"><div class="empty-icon"><?= ui_icon('history',30) ?></div><h3>Belum ada riwayat</h3><p>Kunjungan yang sudah selesai akan muncul di sini.</p></div>
<?php else: ?>
<div class="table-shell"><table class="responsive-table"><thead><tr><th>Layanan</th><th>Tanggal / waktu</th><th>No. antrean</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td data-label="Layanan"><strong><?= e($r['service_name']) ?></strong></td><td data-label="Tanggal / waktu"><?= e(date('d M Y',strtotime($r['reservation_date']))) ?> · <?= e(substr($r['reservation_time'],0,5)) ?></td><td data-label="No. antrean"><?= e((string)($r['queue_number']??'—')) ?></td><td data-label="Status"><span class="badge ok">Selesai</span></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif;render_footer(); ?>
