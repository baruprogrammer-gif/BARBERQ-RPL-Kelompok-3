<?php
require __DIR__ . '/../includes/bootstrap.php';
require_login('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/layanan.php');
verify_csrf();
$id=(int)($_POST['id']??0);
if($id<=0){flash('error','ID layanan tidak valid.');redirect('admin/layanan.php');}
try {
    $stmt=db()->prepare('UPDATE services SET active=IF(active=1,0,1), updated_at=NOW() WHERE id=?');
    $stmt->execute([$id]);
    flash($stmt->rowCount()>0?'success':'warning',$stmt->rowCount()>0?'Status layanan berhasil diperbarui.':'Layanan tidak ditemukan.');
} catch (PDOException $ex) {
    error_log('BARBERQ gagal mengubah status layanan: '.$ex->getMessage());
    flash('error','Status layanan belum berhasil diperbarui.');
}
redirect('admin/layanan.php');
