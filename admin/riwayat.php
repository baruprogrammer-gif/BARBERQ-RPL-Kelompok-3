<?php
require __DIR__.'/../includes/bootstrap.php';require_login('admin');
$rows=db()->query("SELECT r.*,u.name customer_name,s.name service_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN services s ON s.id=r.service_id WHERE r.status='COMPLETED' ORDER BY r.reservation_date DESC,r.reservation_time DESC")->fetchAll();
render_header('Riwayat Pelayanan');page_head('Riwayat pelayanan','Rekap kunjungan pelanggan yang telah selesai.');
if(!$rows): ?>
<div class="empty"><div class="empty-icon"><?= ui_icon('history',30) ?></div><h3>Belum ada pelayanan selesai</h3><p>Setelah petugas menandai status Selesai, catatan akan muncul di sini.</p></div>
<?php else: ?>
<div class="table-shell"><table class="responsive-table"><thead><tr><th>Pelanggan</th><th>Layanan</th><th>Tanggal / jam</th><th>No. antrean</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td data-label="Pelanggan"><strong><?= e($r['customer_name']) ?></strong></td><td data-label="Layanan"><?= e($r['service_name']) ?></td><td data-label="Tanggal / jam"><?= e(date('d M Y',strtotime($r['reservation_date']))) ?> · <?= e(substr($r['reservation_time'],0,5)) ?></td><td data-label="No. antrean"><?= e((string)($r['queue_number']??'—')) ?></td><td data-label="Status"><span class="badge ok">Selesai</span></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif;render_footer(); ?>
