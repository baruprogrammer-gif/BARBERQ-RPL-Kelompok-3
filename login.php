<?php
require __DIR__ . '/includes/bootstrap.php';
if(current_user()) redirect(current_user()['role']==='admin'?'admin/dashboard.php':'pelanggan/dashboard.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf(); $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
  $stmt=db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);$user=$stmt->fetch();
  if($user&&password_verify($password,$user['password_hash'])){session_regenerate_id(true);$_SESSION['user_id']=$user['id'];flash('success','Selamat datang kembali, '.$user['name'].'!');redirect($user['role']==='admin'?'admin/dashboard.php':'pelanggan/dashboard.php');}
  $error='Email atau kata sandi tidak sesuai.';
}
render_header('Masuk');
?>
<section class="figma-auth-layout" aria-label="Masuk ke BARBERQ">
  <div class="figma-auth-brand"><h1>Selamat datang<br>kembali.</h1><p>Masuk untuk melihat reservasi dan antrean BARBERQ.</p><span class="figma-auth-rule"></span><small>BOOK · WAIT · SERVICE</small></div>
  <div class="figma-auth-form">
    <div class="figma-auth-panel"><h2>Masuk Akun</h2><p>Gunakan akun pelanggan atau petugas.</p>
    <?php if($error): ?><div class="flash error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?>
      <label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" value="<?= e($_POST['email']??'') ?>" placeholder="nama@email.com" required>
      <label for="password">Kata Sandi</label><div class="password-field"><input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi"><button type="button" class="password-toggle" data-toggle-password="password">Lihat</button></div>
      <button type="submit" class="btn">Masuk</button>
    </form>
    <p class="figma-auth-link">Belum punya akun? <a href="<?= e(app_url('register.php')) ?>">Daftar pelanggan</a></p>
    </div>
  </div>
</section>
<?php render_footer(); ?>
