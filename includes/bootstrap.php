<?php
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $d = $config['db'];
    $dsn = "mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset={$d['charset']}";
    try {
        $pdo = new PDO($dsn, $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('BARBERQ database error: ' . $e->getMessage());
        exit('<!doctype html><html lang="id"><meta charset="utf-8"><title>Database belum terhubung</title><style>body{font-family:system-ui;background:#f7f3ec;padding:40px;color:#222}.box{max-width:760px;margin:auto;background:#fff;padding:28px;border-radius:18px;border:1px solid #e6dfd5}code{background:#f2eee8;padding:2px 6px;border-radius:6px}pre{white-space:pre-wrap;background:#171717;color:#eee;padding:14px;border-radius:10px}</style><div class="box"><h1>Database belum terhubung</h1><p>Pastikan MySQL pada XAMPP sudah aktif, database <code>barberq</code> sudah dibuat dan file <code>database/barberq.sql</code> sudah di-import.</p><p>Jika memakai hosting online, ubah koneksi di <code>config/config.php</code>.</p><p>Periksa konfigurasi database di server. Jika masalah berlanjut, lihat log PHP.</p></div></html>');
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts = explode('/', trim($script, '/'));
     
    $known = ['pelanggan','admin','actions'];
    if (count($parts) > 1 && in_array($parts[count($parts)-2], $known, true)) {
        array_pop($parts);
        array_pop($parts);
    } else {
        array_pop($parts);
    }
    $base = '/' . implode('/', $parts);
    $base = rtrim($base, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
    if ($role !== null && $user['role'] !== $role) {
        http_response_code(403);
        render_header('Akses ditolak');
        echo '<div class="empty"><div class="empty-icon">⛔</div><h2>Akses ditolak</h2><p>Anda tidak memiliki hak akses ke halaman ini.</p></div>';
        render_footer();
        exit;
    }
    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $posted = $_POST['csrf'] ?? '';
    if (!$posted || !hash_equals($_SESSION['csrf'] ?? '', $posted)) {
        http_response_code(419);
        exit('Permintaan tidak valid (CSRF). Silakan kembali dan coba lagi.');
    }
}

function rupiah(int|float|string $value): string
{
    return 'Rp' . number_format((float)$value, 0, ',', '.');
}

function status_label(string $status): string
{
    return [
        'BOOKED' => 'Reservasi Aktif',
        'WAITING' => 'Menunggu',
        'IN_SERVICE' => 'Sedang Dilayani',
        'COMPLETED' => 'Selesai',
        'CANCELLED' => 'Dibatalkan',
    ][$status] ?? $status;
}

function status_class(string $status): string
{
    return match ($status) {
        'COMPLETED' => 'ok',
        'CANCELLED' => 'danger',
        'WAITING', 'IN_SERVICE' => 'warn',
        default => 'neutral',
    };
}

function is_active_status(string $status): bool
{
    return in_array($status, ['BOOKED','WAITING','IN_SERVICE'], true);
}

function slots_for_date(string $date): array
{
    global $config;
    $slots = [];
    $start = new DateTimeImmutable($date . ' ' . $config['app']['open_hour']);
    $end = new DateTimeImmutable($date . ' ' . $config['app']['close_hour']);
    $step = new DateInterval('PT' . (int)$config['app']['slot_minutes'] . 'M');
    for ($t = $start; $t < $end; $t = $t->add($step)) {
        $slots[] = $t->format('H:i');
    }
    return $slots;
}

function ui_icon(string $name, int $size = 20): string
{
    $icons = [
        'home' => '<path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 21v-7h6v7"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="m8.1 8.1 12.8 12.8M14 10l7-7M8.1 15.9 11 13"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users' => '<path d="M16 20a6 6 0 0 0-12 0M20 20a6 6 0 0 0-3-5.2"/><circle cx="10" cy="8" r="4"/><path d="M17 4a4 4 0 0 1 0 8"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'shield' => '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>',
        'spark' => '<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5Z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M5 5l14 14M19 5 5 19"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L9 17l-4 1 1-4L16.5 3.5Z"/>',
        'ticket' => '<path d="M3 8V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a4 4 0 0 0 0 8v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a4 4 0 0 0 0-8Z"/><path d="M13 3v2m0 3v2m0 4v2m0 3v2"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-14-5L3 9M3 3v6h6M4 13a8 8 0 0 0 14 5l3-3M21 21v-6h-6"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12 20 3l1 3-3 1 1 3-3 1-2 2"/>',
        'phone' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
    ];
    $path = $icons[$name] ?? $icons['spark'];
    return '<svg class="icon" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$path.'</svg>';
}

function nav_item(string $label, string $path, string $icon, bool $active = false): string
{
    $class = $active ? 'nav-link is-current' : 'nav-link';
    return '<a class="'.$class.'" href="'.e(app_url($path)).'"'.($active?' aria-current="page"':'').'>'.ui_icon($icon, 18).'<span>'.e($label).'</span></a>';
}

function app_sidebar_item(string $label, string $path, bool $active = false): string
{
    return '<a class="sidebar-link'.($active?' is-current':'').'" href="'.e(app_url($path)).'"'.($active?' aria-current="page"':'').'>'.e($label).'</a>';
}

function render_header(string $title): void
{
    global $config;
    $user = current_user();
    $flashes = consume_flashes();
    $GLOBALS['barberq_has_sidebar'] = (bool)$user;
    $script = trim(str_replace('\\','/',$_SERVER['SCRIPT_NAME']??''), '/');
    $public = !$user;
    $css = e(app_url('assets/css/style.css'));
    $figmaCss = e(app_url('assets/css/figma.css'));
    $js = e(app_url('assets/js/app.js'));
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="theme-color" content="#142b23"><meta name="description" content="BARBERQ - Sistem Reservasi & Antrean Barbershop"><title>'.e($title).' · BARBERQ</title><link rel="stylesheet" href="'.$css.'"><link rel="stylesheet" href="'.$figmaCss.'"><script defer src="'.$js.'"></script></head>';
    echo '<body class="'.($public?'is-public':'has-session').'">';
    echo '<a class="skip-link" href="#main-content">Lewati ke konten</a>';
    if ($public) {
        echo '<header class="site-header"><div class="nav-wrap"><a class="brand" href="'.e(app_url('index.php')).'" aria-label="BARBERQ - Beranda"><span class="brand-mark">B</span><span class="brand-word"><span class="brand-name">BARBERQ</span><small>BARBERSHOP SYSTEM</small></span></a>';
        echo '<button class="nav-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="Buka menu navigasi" data-nav-toggle>'.ui_icon('menu',22).'</button>';
        echo '<nav id="site-navigation" class="site-navigation" aria-label="Navigasi utama">';
        echo nav_item('Beranda', 'index.php', 'home', str_ends_with($script,'index.php'));
        echo nav_item('Layanan', 'services.php', 'scissors', str_ends_with($script,'services.php'));
        echo nav_item('Masuk', 'login.php', 'user', str_ends_with($script,'login.php'));
        echo nav_item('Daftar', 'register.php', 'plus', str_ends_with($script,'register.php'));
        echo '</nav></div></header><main id="main-content" class="container public-container">';
    } else {
        $isAdmin = $user['role']==='admin';
        $items = $isAdmin
            ? [
                ['Dashboard','admin/dashboard.php',str_ends_with($script,'admin/dashboard.php')],
                ['Layanan','admin/layanan.php',str_ends_with($script,'admin/layanan.php')],
                ['Reservasi & Antrean','admin/reservasi.php',str_ends_with($script,'admin/reservasi.php')],
                ['Riwayat','admin/riwayat.php',str_ends_with($script,'admin/riwayat.php')],
              ]
            : [
                ['Dashboard','pelanggan/dashboard.php',str_ends_with($script,'pelanggan/dashboard.php')],
                ['Buat Reservasi','pelanggan/reservasi.php',str_ends_with($script,'pelanggan/reservasi.php')],
                ['Reservasi Aktif','pelanggan/reservasi_aktif.php',str_ends_with($script,'pelanggan/reservasi_aktif.php')],
                ['Antrean','pelanggan/antrean.php',str_ends_with($script,'pelanggan/antrean.php')],
                ['Riwayat','pelanggan/riwayat.php',str_ends_with($script,'pelanggan/riwayat.php')],
              ];
        echo '<div class="app-shell"><aside class="app-sidebar" id="site-navigation" aria-label="Navigasi '.($isAdmin?'admin':'pelanggan').'">';
        echo '<a class="sidebar-brand" href="'.e(app_url($isAdmin?'admin/dashboard.php':'pelanggan/dashboard.php')).'"><span class="sidebar-mark">B</span><span>BARBERQ</span></a>';
        echo '<div class="sidebar-role">'.($isAdmin?'ADMIN / PETUGAS':'PELANGGAN').'</div>';
        echo '<nav class="sidebar-nav" aria-label="Menu utama">';
        foreach ($items as [$label,$path,$active]) echo app_sidebar_item($label,$path,$active);
        echo '</nav><a class="sidebar-logout" href="'.e(app_url('logout.php')).'">Keluar / Logout</a>';
        echo '<div class="sidebar-foot">BARBERQ · Sistem Reservasi</div></aside>';
        echo '<div class="app-workspace"><header class="app-mobile-header"><span class="sidebar-mark">B</span><strong>BARBERQ</strong><button type="button" class="nav-toggle" data-nav-toggle aria-label="Buka menu navigasi" aria-controls="site-navigation" aria-expanded="false">'.ui_icon('menu',23).'</button></header>';
        echo '<main id="main-content" class="container app-container">';
    }
    foreach ($flashes as $f) {
        echo '<div class="flash '.e($f['type']).'" role="status">'.ui_icon($f['type']==='success'?'check-circle':'info',19).'<span>'.e($f['message']).'</span></div>';
    }
}

function render_footer(): void
{
    $session = $GLOBALS['barberq_has_sidebar'] ?? false;
    if ($session) {
        echo '</main></div></div></body></html>';
        return;
    }
    echo '</main><footer class="site-footer"><div class="footer-inner"><div><span class="footer-logo">BARBERQ</span><span class="footer-sub">Sistem Reservasi & Antrean Barbershop</span></div><div>BARBERQ · Barbershop System</div></div></footer></body></html>';
}

function page_head(string $title, string $subtitle = '', string $action = ''): void
{
    echo '<div class="page-head"><div class="page-title-wrap"><h1>'.e($title).'</h1>';
    if ($subtitle !== '') echo '<p>'.e($subtitle).'</p>';
    echo '<span class="page-accent" aria-hidden="true"></span></div>'.($action?'<div class="page-head-action">'.$action.'</div>':'').'</div>';
}
