<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($name === '' || strlen($name) > 100) {
            flash('error', 'Nama Admin/Petugas wajib diisi dan maksimal 100 karakter.');
            redirect('admin/profil.php');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            flash('error', 'Format email tidak valid.');
            redirect('admin/profil.php');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $check->execute([$email, $user['id']]);
        if ($check->fetch()) {
            flash('error', 'Email tersebut sudah digunakan akun lain.');
            redirect('admin/profil.php');
        }

        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = ?');
        $stmt->execute([$name, $email, $user['id'], 'admin']);
        flash('success', 'Profil Admin/Petugas berhasil diperbarui. Gunakan email baru untuk login berikutnya.');
        redirect('admin/profil.php');
    }

    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? AND role = ? LIMIT 1');
        $stmt->execute([$user['id'], 'admin']);
        $account = $stmt->fetch();

        if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
            flash('error', 'Password lama tidak sesuai.');
            redirect('admin/profil.php');
        }
        if (strlen($newPassword) < 8) {
            flash('error', 'Password baru minimal 8 karakter.');
            redirect('admin/profil.php');
        }
        if ($newPassword !== $confirmPassword) {
            flash('error', 'Konfirmasi password baru tidak sama.');
            redirect('admin/profil.php');
        }
        if ($currentPassword === $newPassword) {
            flash('error', 'Password baru harus berbeda dari password lama.');
            redirect('admin/profil.php');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND role = ?');
        $update->execute([$newHash, $user['id'], 'admin']);
        flash('success', 'Password Admin/Petugas berhasil diganti.');
        redirect('admin/profil.php');
    }

    flash('error', 'Aksi tidak dikenali.');
    redirect('admin/profil.php');
}

$user = require_login('admin');
render_header('Profil Admin');
page_head('Profil Admin/Petugas', 'Ubah nama, email login, atau password akun Admin/Petugas.');
?>
<div class="form-shell">
    <section class="panel">
        <div class="panel-heading"><span><?= ui_icon('user',21) ?></span><div><h2>Data akun</h2><p>Informasi identitas Admin/Petugas.</p></div></div>
        <p class="muted">Gunakan alamat email aktif untuk masuk ke akun Anda.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">

            <label>Nama Admin/Petugas</label>
            <input type="text" name="name" maxlength="100" required value="<?= e($user['name']) ?>" placeholder="Nama Admin/Petugas">

            <label>Email Login</label>
            <input type="email" name="email" maxlength="190" required value="<?= e($user['email']) ?>" placeholder="admin@example.com">
            <div class="help">Setelah email diubah, gunakan email baru saat login berikutnya.</div>

            <div class="actions" style="margin-top:20px">
                <button class="btn" type="submit">Simpan Profil</button>
            </div>
        </form>
    </section>

    <aside class="info-card">
        <h3>Informasi akun</h3>
        <ul class="info-list">
            <li>Hak akses: Admin/Petugas</li>
            <li>Gunakan email dan kata sandi saat masuk.</li>
            <li>Periksa kembali nama dan email sebelum menyimpan.</li>
        </ul>
    </aside>
</div>

<h2 class="section-title">Ganti password</h2>
<div class="form-shell">
    <section class="panel">
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_password">

            <label>Password Lama</label>
            <div class="password-field"><input id="current-password" type="password" name="current_password" required autocomplete="current-password" placeholder="Masukkan password lama"><button type="button" class="password-toggle" data-toggle-password="current-password" aria-label="Tampilkan password lama">Lihat</button></div>

            <label>Password Baru</label>
            <div class="password-field"><input id="new-password" type="password" name="new_password" minlength="8" required autocomplete="new-password" placeholder="Minimal 8 karakter"><button type="button" class="password-toggle" data-toggle-password="new-password" aria-label="Tampilkan password baru">Lihat</button></div>

            <label>Konfirmasi Password Baru</label>
            <div class="password-field"><input id="confirm-password" type="password" name="confirm_password" minlength="8" required autocomplete="new-password" placeholder="Ulangi password baru"><button type="button" class="password-toggle" data-toggle-password="confirm-password" aria-label="Tampilkan konfirmasi password">Lihat</button></div>

            <div class="actions" style="margin-top:20px">
                <button class="btn" type="submit">Ganti Password</button>
            </div>
        </form>
    </section>

    <aside class="info-card">
        <h3>Keamanan</h3>
        <ul class="info-list">
            <li>Password lama harus benar.</li>
            <li>Password baru minimal 8 karakter.</li>
            <li>Jaga kerahasiaan kata sandi akun Anda.</li>
        </ul>
    </aside>
</div>
<?php render_footer(); ?>
