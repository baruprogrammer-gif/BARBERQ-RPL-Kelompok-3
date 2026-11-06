<?php
require __DIR__ . '/../includes/bootstrap.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/layanan.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$priceInput = trim((string)($_POST['price'] ?? ''));

 
$isNumericPrice = ctype_digit($priceInput);
$isWithinRange = $isNumericPrice && (
    strlen(ltrim($priceInput, '0')) < 10 ||
    (strlen(ltrim($priceInput, '0')) === 10 && strcmp(ltrim($priceInput, '0'), '4294967295') <= 0)
);
if ($name === '' || strlen($name) > 120) {
    flash('error', 'Nama layanan wajib diisi dan maksimal 120 karakter.');
    redirect('admin/layanan.php');
}
if (!$isWithinRange) {
    flash('error', 'Harga harus berupa bilangan bulat 0 sampai 4.294.967.295.');
    redirect('admin/layanan.php');
}
$price = (int)$priceInput;

try {
    $pdo = db();
    if ($id > 0) {
         
        $check = $pdo->prepare('SELECT id FROM services WHERE id = ? LIMIT 1');
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            flash('error', 'Layanan yang akan diubah tidak ditemukan.');
            redirect('admin/layanan.php');
        }
        $stmt = $pdo->prepare('UPDATE services SET name = ?, description = ?, price = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $description, $price, $id]);
        flash('success', 'Layanan berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO services (name, description, price, active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$name, $description, $price]);
        flash('success', 'Layanan berhasil ditambahkan.');
    }
} catch (PDOException $e) {
    error_log('BARBERQ gagal menyimpan layanan: ' . $e->getMessage());
    flash('error', 'Layanan belum berhasil disimpan. Silakan coba lagi.');
}
redirect('admin/layanan.php');
