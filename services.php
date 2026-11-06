<?php
require __DIR__ . '/includes/bootstrap.php';
$services=db()->query('SELECT * FROM services WHERE active=1 ORDER BY id')->fetchAll();
$user=current_user();
$action=$user&&$user['role']==='customer'?'<a class="btn" href="'.e(app_url('pelanggan/reservasi.php')).'">'.ui_icon('plus',17).' Buat reservasi</a>':'';
render_header('Layanan'); page_head('Layanan pilihan','Pilih layanan yang paling cocok untuk penampilanmu.',$action);
?>
<?php if(!$services): ?>
<div class="empty"><div class="empty-icon"><?= ui_icon('scissors',30) ?></div><h3>Layanan belum tersedia</h3><p>Silakan kunjungi halaman ini kembali nanti.</p></div>
<?php else: ?>
<div class="grid">
  <?php foreach($services as $i=>$s): ?>
    <article class="card service-card">
      <div class="service-icon"><?= ui_icon('scissors',25) ?></div>
      <div class="overline">LAYANAN <?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></div>
      <h3 style="margin-top:9px"><?= e($s['name']) ?></h3>
      <p><?= e($s['description'] ?: 'Layanan pilihan barbershop.') ?></p>
      <div class="service-footer"><div><div class="price-label">Harga layanan</div><div class="price"><?= e(rupiah($s['price'])) ?></div></div><a href="<?= e(app_url($user&&$user['role']==='customer'?'pelanggan/reservasi.php':'login.php')) ?>" aria-label="Reservasi <?= e($s['name']) ?>"><?= ui_icon('arrow-up-right',18) ?></a></div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; render_footer(); ?>
