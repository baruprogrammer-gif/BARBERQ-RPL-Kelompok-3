<?php
require __DIR__.'/../includes/bootstrap.php';require_login('admin');
$date=$_GET['date']??date('Y-m-d');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))$date=date('Y-m-d');
$stmt=db()->prepare("SELECT r.*,u.name customer_name,u.email,s.name service_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN services s ON s.id=r.service_id WHERE r.reservation_date=? AND r.status IN ('BOOKED','WAITING','IN_SERVICE') ORDER BY COALESCE(r.queue_number,999999),r.reservation_time");$stmt->execute([$date]);$rows=$stmt->fetchAll();
render_header('Reservasi & Antrean');page_head('Reservasi & antrean','Tandai kedatangan lalu perbarui progres pelayanan setiap pelanggan.');
?>
<form method="get" class="panel date-filter"><div><label for="date" style="margin-top:0">Tanggal pelayanan</label><p class="help" style="margin:0">Lihat reservasi aktif pada tanggal tertentu.</p></div><div class="actions"><input id="date" type="date" name="date" value="<?= e($date) ?>" required><button class="btn" type="submit"><?= ui_icon('search',17) ?> Tampilkan</button></div></form>
<div class="section-head"><h2 class="section-title">Daftar pelanggan <span class="muted small" style="font-weight:500">(<?= count($rows) ?> aktif)</span></h2></div>
<?php if(!$rows): ?><div class="empty"><div class="empty-icon"><?= ui_icon('calendar',30) ?></div><h3>Tidak ada reservasi aktif</h3><p>Belum ada pelanggan yang terjadwal di tanggal ini.</p></div>
<?php else: ?><div class="table-shell"><table class="responsive-table"><thead><tr><th>Pelanggan</th><th>Layanan</th><th>Jam</th><th>No.</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr>
<td data-label="Pelanggan"><div class="cell-content"><strong><?= e($r['customer_name']) ?></strong><div class="muted small"><?= e($r['email']) ?></div></div></td>
<td data-label="Layanan"><?= e($r['service_name']) ?></td>
<td data-label="Jam"><?= e(substr($r['reservation_time'],0,5)) ?> WIB</td>
<td data-label="No. antrean"><?= e((string)($r['queue_number']??'—')) ?></td>
<td data-label="Status"><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
<td data-label="Tindakan"><div class="actions">
  <?php if($r['status']==='BOOKED'): ?><form class="inline" method="post" action="<?= e(app_url('actions/queue_status.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="WAITING"><button class="btn small warn" type="submit"><?= ui_icon('check',14) ?> Tandai datang</button></form>
  <?php elseif($r['status']==='WAITING'): ?><form class="inline" method="post" action="<?= e(app_url('actions/queue_status.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="IN_SERVICE"><button class="btn small" type="submit"><?= ui_icon('scissors',14) ?> Mulai pelayanan</button></form>
  <?php elseif($r['status']==='IN_SERVICE'): ?><form class="inline" method="post" action="<?= e(app_url('actions/queue_status.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="COMPLETED"><button class="btn small ok" type="submit"><?= ui_icon('check',14) ?> Selesai</button></form><?php endif; ?>
  <?php if(in_array($r['status'], ['BOOKED','WAITING'], true)): ?>
  <form class="inline" method="post" action="<?= e(app_url('actions/queue_status.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="CANCELLED"><button class="btn small ghost" type="submit" data-confirm="Batalkan reservasi/antrean pelanggan ini?">Batalkan</button></form>
  <?php endif; ?>
</div></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php render_footer(); ?>
