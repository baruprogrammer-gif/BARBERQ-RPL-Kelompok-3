<?php
require __DIR__.'/../includes/bootstrap.php';
require_login('admin');
$pdo=db();
$editing=isset($_GET['edit']);
$adding=isset($_GET['new']);
$edit=null;
if ($editing) {
  $stmt=$pdo->prepare('SELECT * FROM services WHERE id=? LIMIT 1');
  $stmt->execute([(int)$_GET['edit']]);
  $edit=$stmt->fetch();
  if (!$edit) {
    flash('warning','Layanan tidak ditemukan.');
    redirect('admin/layanan.php');
  }
}
$rows=$pdo->query('SELECT * FROM services ORDER BY id ASC')->fetchAll();
render_header('Manajemen Layanan');
page_head('Manajemen Layanan','Tambah, ubah, dan nonaktifkan layanan barbershop.');
?>
<?php if ($editing || $adding): ?>
<section class="figma-data-card figma-form-card" aria-labelledby="form-heading">
  <h2 id="form-heading"><?= $editing?'Ubah Layanan':'Tambah Layanan' ?></h2>
  <form method="post" action="<?= e(app_url('actions/save_service.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
    <label for="service-name">Nama Layanan</label>
    <input id="service-name" name="name" maxlength="120" required value="<?= e($edit['name']??'') ?>" placeholder="Masukkan nama layanan">
    <label for="service-desc">Deskripsi</label>
    <textarea id="service-desc" name="description" placeholder="Masukkan deskripsi layanan"><?= e($edit['description']??'') ?></textarea>
    <label for="service-price">Harga (Rp)</label>
    <input id="service-price" type="number" name="price" min="0" required value="<?= e(isset($edit['price'])?(string)$edit['price']:'') ?>" placeholder="Masukkan harga">
    <div class="actions figma-form-actions"><button class="btn" type="submit">Simpan Layanan</button><a class="btn secondary" href="<?= e(app_url('admin/layanan.php')) ?>">Batal</a></div>
  </form>
</section>
<?php else: ?>
<section class="figma-data-card figma-service-list" aria-labelledby="service-list-heading">
  <h2 id="service-list-heading">Daftar Layanan</h2>
  <?php if (!$rows): ?>
    <div class="figma-empty">Belum ada layanan. Silakan tambahkan layanan pertama.</div>
  <?php else: ?>
  <div class="figma-table-wrap"><table class="figma-table figma-services-table"><thead><tr><th>Nama Layanan</th><th>Harga</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
  <?php foreach ($rows as $item): ?>
    <tr>
      <td><strong><?= e($item['name']) ?></strong></td>
      <td><?= e(rupiah($item['price'])) ?></td>
      <td><span class="status-text <?= $item['active']?'status-active':'status-inactive' ?>"><?= $item['active']?'Aktif':'Nonaktif' ?></span></td>
      <td class="figma-table-actions">
        <a href="<?= e(app_url('admin/layanan.php?edit='.(int)$item['id'])) ?>">Ubah</a>
        <form method="post" action="<?= e(app_url('actions/toggle_service.php')) ?>" class="inline">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
          <button type="submit" class="figma-action-link <?= $item['active']?'danger-link':'positive-link' ?>" data-confirm="<?= $item['active']?'Nonaktifkan':'Aktifkan' ?> layanan <?= e($item['name']) ?>?"> <?= $item['active']?'Nonaktifkan':'Aktifkan' ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
  <div class="figma-card-actions figma-service-cta"><a class="btn" href="<?= e(app_url('admin/layanan.php?new=1')) ?>">Tambah Layanan</a></div>
  <div class="figma-service-hint">Layanan nonaktif tidak tersedia untuk reservasi baru.</div>
</section>
<?php endif; ?>
<?php render_footer(); ?>
