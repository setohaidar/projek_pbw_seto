<?php
session_start();
require_once 'db_connect.php';

// Keamanan: Cek sesi pengguna
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = htmlspecialchars($_SESSION['nama_lengkap']);

// 1. UBAH HALAMAN DEFAULT DARI 'dashboard' MENJADI 'profil_page'
$page = isset($_GET['page']) ? $_GET['page'] : 'profil_page';

// 2. HAPUS 'dashboard' DARI DAFTAR HALAMAN YANG DIIZINKAN
$allowed_pages = [
    'form_pengajuan',
    'status_pengajuan',
    'riwayat',
    'tukar_poin',
    'status_penukaran_poin',
    'profil_page'
];

if (!in_array($page, $allowed_pages)) {
    // Jika halaman tidak ada, arahkan ke profil sebagai default
    $page = 'profil_page';
}

$page_file = $page . '.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna</title>
    <link rel="stylesheet" href="dashboardpengguna.css">
    <link rel="stylesheet" href="form_pengajuan.css">
    <link rel="stylesheet" href="status_pengajuan.css">
    <link rel="stylesheet" href="tukar_poin.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <h3>Panel Pengguna</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboardpengguna.php?page=profil_page" class="nav-link <?php echo ($page == 'profil_page') ? 'active' : ''; ?>">Profil Saya</a>
                <a href="dashboardpengguna.php?page=form_pengajuan" class="nav-link <?php echo ($page == 'form_pengajuan') ? 'active' : ''; ?>">Ajukan Pembuangan</a>
                <a href="dashboardpengguna.php?page=status_pengajuan" class="nav-link <?php echo ($page == 'status_pengajuan') ? 'active' : ''; ?>">Status Pengajuan</a>
                <a href="dashboardpengguna.php?page=riwayat" class="nav-link <?php echo ($page == 'riwayat') ? 'active' : ''; ?>">Riwayat Pembuangan</a>
                <a href="dashboardpengguna.php?page=tukar_poin" class="nav-link <?php echo ($page == 'tukar_poin') ? 'active' : ''; ?>">Tukar Poin</a>
                <a href="dashboardpengguna.php?page=status_penukaran_poin" class="nav-link <?php echo ($page == 'status_penukaran_poin') ? 'active' : ''; ?>">Status Penukaran</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <span><?php echo $nama_lengkap; ?></span>
                    <small>Pengguna</small>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <?php
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                echo "<div class='content-wrapper'><h1>Halaman tidak ditemukan.</h1><p>Pastikan file <b>{$page_file}</b> ada di dalam folder proyek Anda.</p></div>";
            }
            ?>
        </main>
    </div>
</body>
</html>