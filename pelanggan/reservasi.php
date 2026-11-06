<?php
require __DIR__.'/../includes/bootstrap.php';
$user=require_login('customer');$pdo=db();
$services=$pdo->query('SELECT id,name,price FROM services WHERE active=1 ORDER BY name')->fetchAll();
$date=$_POST['reservation_date']??$_GET['date']??date('Y-m-d');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))$date=date('Y-m-d');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();$serviceId=(int)($_POST['service_id']??0);$date=trim($_POST['reservation_date']??'');$time=trim($_POST['reservation_time']??'');
  $validDate=(bool)preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)&&checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4));
  if($serviceId<=0)$errors[]='Pilih layanan terlebih dahulu.';
  if(!$validDate||$date<date('Y-m-d'))$errors[]='Tanggal reservasi tidak valid.';
  if(!$validDate||!in_array($time,slots_for_date($date),true))$errors[]='Waktu reservasi tidak valid.';
  if($validDate&&$date===date('Y-m-d')&&$time<=date('H:i'))$errors[]='Jam tersebut sudah lewat. Pilih waktu yang akan datang.';
  $stmt=$pdo->prepare('SELECT id FROM services WHERE id=? AND active=1');$stmt->execute([$serviceId]);if(!$stmt->fetch())$errors[]='Layanan tidak tersedia.';
  if(!$errors){
     
     
    $lockName='bq-slot-'.sha1($date.'-'.$time);
    $locked=false;
    $saved=false;
    try {
      $getLock=$pdo->prepare('SELECT GET_LOCK(?, 8)');
      $getLock->execute([$lockName]);
      if ((int)$getLock->fetchColumn() !== 1) {
        throw new RuntimeException('Jadwal sedang diproses. Coba lagi sebentar.');
      }
      $locked=true;
      $pdo->beginTransaction();
      $stmt=$pdo->prepare("SELECT id FROM reservations WHERE reservation_date=? AND reservation_time=? AND status IN ('BOOKED','WAITING','IN_SERVICE') LIMIT 1 FOR UPDATE");
      $stmt->execute([$date,$time]);
      if ($stmt->fetch()) {
        throw new RuntimeException('Slot baru saja diambil pelanggan lain. Silakan pilih waktu lainnya.');
      }
      $stmt=$pdo->prepare("INSERT INTO reservations(user_id,service_id,reservation_date,reservation_time,status) VALUES(?,?,?,?, 'BOOKED')");
      $stmt->execute([$user['id'],$serviceId,$date,$time.':00']);
      $pdo->commit();
      $saved=true;
    } catch (RuntimeException $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[]=$e->getMessage();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('BARBERQ booking failed: '.$e->getMessage());
      $errors[]='Reservasi belum berhasil disimpan. Silakan coba kembali.';
    } finally {
      if ($locked) {
        try {
          $release=$pdo->prepare('SELECT RELEASE_LOCK(?)');
          $release->execute([$lockName]);
        } catch (Throwable $e) { error_log('BARBERQ release slot lock: '.$e->getMessage()); }
      }
    }
    if ($saved) {
      flash('success','Reservasi berhasil dibuat. Sampai jumpa di barbershop!');
      redirect('pelanggan/reservasi_aktif.php');
    }
  }
  if(!$validDate)$date=date('Y-m-d');
}
$slots=slots_for_date($date);
$stmt=$pdo->prepare("SELECT TIME_FORMAT(reservation_time,'%H:%i') t FROM reservations WHERE reservation_date=? AND status IN ('BOOKED','WAITING','IN_SERVICE')");$stmt->execute([$date]);$booked=array_column($stmt->fetchAll(),'t');
render_header('Buat Reservasi');page_head('Buat reservasi','Pilih layanan favoritmu dan temukan waktu yang masih kosong.','<a class="btn secondary" href="'.e(app_url('pelanggan/reservasi_aktif.php')).'">'.ui_icon('ticket',16).' Reservasi aktif</a>');
foreach($errors as $er)echo'<div class="flash error" role="alert">'.ui_icon('info',18).e($er).'</div>';
?>
<div class="form-shell"><section class="panel">
<div class="panel-heading"><span><?= ui_icon('calendar',22) ?></span><div><h2>Atur jadwal kunjungan</h2><p>Langkah mudah untuk reservasi layanan pilihanmu.</p></div></div>
<form method="post" data-booking-form><?= csrf_field() ?>
<label for="service">01 — Pilih layanan</label><select name="service_id" id="service" required data-booking-service><option value="">Pilih layanan barbershop</option><?php foreach($services as $s): ?><option value="<?= (int)$s['id'] ?>" data-name="<?= e($s['name']) ?>" data-price="<?= e(rupiah($s['price'])) ?>" <?= (int)($_POST['service_id']??$_GET['service_id']??0)===(int)$s['id']?'selected':'' ?>><?= e($s['name']) ?> — <?= e(rupiah($s['price'])) ?></option><?php endforeach ?></select>
<label for="date">02 — Pilih tanggal</label><input type="date" id="date" name="reservation_date" data-reservation-date data-slot-url="<?= e(app_url('api/slots.php')) ?>" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($date) ?>">
<label for="time">03 — Pilih jam tersedia</label><select name="reservation_time" id="time" class="slot-native" data-reservation-time required><option value="">Pilih waktu</option><?php foreach($slots as $slot):$full=in_array($slot,$booked,true)||($date===date('Y-m-d')&&$slot<=date('H:i'));?><option value="<?= e($slot) ?>" <?= $full?'disabled':'' ?> <?= ($_POST['reservation_time']??'')===$slot?'selected':'' ?>><?= e($slot) ?><?= $full?' — penuh/tidak tersedia':'' ?></option><?php endforeach ?></select>
<div class="slot-buttons" data-slot-buttons role="group" aria-label="Jam reservasi yang tersedia"></div><div class="slot-summary" data-slot-summary aria-live="polite"><?= ui_icon('info',16) ?> Pilih jam yang masih tersedia.</div>
<div class="booking-summary"><?= ui_icon('check-circle',22) ?><div><strong data-booking-summary>Siap buat reservasi?</strong><small>Pastikan layanan, tanggal, dan jam sudah sesuai.</small></div></div>
<div class="actions"><button type="submit" class="btn">Simpan reservasi <?= ui_icon('arrow',16) ?></button><a class="btn ghost" href="<?= e(app_url('pelanggan/dashboard.php')) ?>">Kembali</a></div>
</form></section>
<aside class="info-card"><div class="eyebrow" style="color:var(--gold)"><span class="eyebrow-dot"></span> INFORMASI RESERVASI</div><h3 style="margin-top:19px">Datang sesuai jadwal, nikmati pelayanan.</h3><p>Pilih slot yang tersedia. Petugas akan mengatur antrean ketika kamu tiba di barbershop.</p><ul class="info-list"><li>Satu slot waktu untuk satu reservasi aktif</li><li>Jam operasional: <?= e($config['app']['open_hour']) ?>–<?= e($config['app']['close_hour']) ?> (pilihan setiap <?= (int)$config['app']['slot_minutes'] ?> menit)</li><li>Pembatalan tersedia sebelum pelayanan dimulai</li></ul></aside></div>
<?php render_footer(); ?>
