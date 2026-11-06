<?php
require __DIR__.'/includes/bootstrap.php';
$user=current_user();
 
 
 
$featured=[];
if(in_array('mysql',PDO::getAvailableDrivers(),true)){
    $featured=db()->query("SELECT name,description FROM services WHERE active=1 ORDER BY CASE name WHEN 'Haircut' THEN 0 WHEN 'Shaving' THEN 1 WHEN 'Haircut + Wash' THEN 2 ELSE 3 END, id LIMIT 3")->fetchAll();
}
render_header('Beranda');
?>
<section class="figma-hero" aria-labelledby="hero-heading">
  <div class="figma-hero-copy">
    <h1 id="hero-heading">Reservasi barbershop,<br>lebih praktis.</h1>
    <p>Pilih layanan, atur jadwal kunjungan, dan pantau antrean secara online.</p>
    <?php if (!$user): ?>
      <a class="btn gold" href="<?= e(app_url('register.php')) ?>">Daftar Sekarang</a>
    <?php elseif ($user['role']==='admin'): ?>
      <a class="btn gold" href="<?= e(app_url('admin/dashboard.php')) ?>">Buka Dashboard</a>
    <?php else: ?>
      <a class="btn gold" href="<?= e(app_url('pelanggan/reservasi.php')) ?>">Buat Reservasi</a>
    <?php endif; ?>
  </div>
  <div class="figma-hero-logo" aria-hidden="true"><div>BQ</div><span>BARBERQ</span></div>
</section>
<section class="figma-home-services" aria-labelledby="services-heading">
  <h2 id="services-heading">Layanan pilihan</h2>
  <?php if($featured): ?>
  <div class="figma-home-grid">
    <?php foreach($featured as $service): ?>
    <a class="figma-home-service" href="<?= e(app_url('services.php')) ?>"><span class="figma-chip">LAYANAN</span><h3><?= e($service['name']) ?></h3><p><?= e($service['description']?:'Pilih layanan yang sesuai kebutuhan.') ?></p></a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="figma-home-empty">Layanan akan ditampilkan ketika layanan aktif sudah tersedia.</div>
  <?php endif; ?>
  <p class="figma-service-disclaimer">Lihat daftar layanan dan harga terkini pada halaman <a href="<?= e(app_url('services.php')) ?>">Layanan</a>.</p>
</section>
<?php render_footer(); ?>
