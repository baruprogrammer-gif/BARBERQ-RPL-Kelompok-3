<?php
require __DIR__.'/includes/bootstrap.php';
if(current_user()) redirect(current_user()['role']==='admin'?'admin/dashboard.php':'pelanggan/dashboard.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();$name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$password=$_POST['password']??'';
  if($name===''||strlen($name)>400||preg_match_all('/./us',$name)>100)$errors[]='Nama wajib diisi (maksimal 100 karakter).';
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)$errors[]='Alamat email tidak valid.';
  if(strlen($password)<6)$errors[]='Kata sandi minimal 6 karakter.';
  if(!$errors){$stmt=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);if($stmt->fetch())$errors[]='Email sudah digunakan.';}
  if(!$errors){
    try{$stmt=db()->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'customer')");$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);flash('success','Akun berhasil dibuat. Silakan masuk.');redirect('login.php');}
    catch(PDOException $ex){error_log('BARBERQ registrasi: '.$ex->getMessage());$errors[]='Pendaftaran belum berhasil. Periksa email atau coba lagi.';}
  }
}
render_header('Daftar');
?>
<div class="figma-register-header"><h1>Buat Akun Pelanggan</h1><p>Buat akun untuk memesan layanan dan memantau antrean.</p><span class="page-accent" aria-hidden="true"></span></div>
<section class="figma-register-panel" aria-label="Form pendaftaran">
  <?php foreach($errors as $err): ?><div class="flash error" role="alert"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="figma-register-cols"><div><label for="name">Nama Lengkap</label><input id="name" name="name" autocomplete="name" maxlength="100" required value="<?= e($_POST['name']??'') ?>" placeholder="Nama pelanggan"></div><div><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" maxlength="190" required value="<?= e($_POST['email']??'') ?>" placeholder="pelanggan@email.com"></div></div>
    <label for="password">Kata Sandi</label><div class="password-field"><input id="password" type="password" name="password" autocomplete="new-password" required minlength="6" placeholder="Minimal 6 karakter"><button type="button" class="password-toggle" data-toggle-password="password">Lihat</button></div>
    <p class="figma-password-help">Gunakan kata sandi minimal 6 karakter.</p>
    <button type="submit" class="btn">Daftar Akun</button>
    <p class="figma-auth-link">Sudah punya akun? <a href="<?= e(app_url('login.php')) ?>">Masuk</a></p>
  </form>
</section>
<?php render_footer(); ?>
