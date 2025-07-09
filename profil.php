<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("
    SELECT u.nama_lengkap, u.email, u.nomor_telepon, a.alamat, a.no_rumah, a.rt, a.rw, a.kelurahan, a.kecamatan, a.kota
    FROM users u
    LEFT JOIN alamat a ON u.id = a.user_id
    WHERE u.id = ? LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    $stmt_user_only = $conn->prepare("SELECT nama_lengkap, email, nomor_telepon FROM users WHERE id = ?");
    $stmt_user_only->bind_param("i", $user_id);
    $stmt_user_only->execute();
    $user_data = $stmt_user_only->get_result()->fetch_assoc();
    $stmt_user_only->close();
    
    $alamat_fields = ['alamat', 'no_rumah', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kota'];
    foreach ($alamat_fields as $field) {
        $user_data[$field] = '';
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>
    <link rel="stylesheet" href="profil.css?v=1.1">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo ucfirst($role); ?> Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <?php if ($role == 'admin'): ?>
                    <a href="dashboardadmin.php">Manajemen Sampah</a>
                    <a href="admin_penukaran_hadiah.php">Manajemen Hadiah</a>
                <?php elseif ($role == 'kurir'): ?>
                    <a href="dashboardkurir.php">Tugas Penjemputan</a>
                <?php else: ?>
                    <a href="dashboardpengguna.php?page=dashboard">Dashboard</a>
                    <a href="dashboardpengguna.php?page=form_pengajuan">Ajukan Pembuangan</a>
                    <a href="dashboardpengguna.php?page=status_pengajuan">Status Pengajuan</a>
                    <a href="dashboardpengguna.php?page=riwayat">Riwayat</a>
                    <a href="dashboardpengguna.php?page=tukar_poin">Tukar Poin</a>
                    <a href="dashboardpengguna.php?page=status_penukaran_poin">Status Penukaran</a>
                <?php endif; ?>
                <a href="profil.php" class="active">Profil Saya</a>
            </nav>
            <div class="sidebar-footer">
                <span>Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1>Profil Saya</h1>
                <p>Perbarui informasi data diri dan alamat Anda di sini.</p>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>

            <div class="content-box">
                <form action="update_profil.php" method="POST" class="form-profil">
                     <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nomor_telepon">Nomor Telepon</label>
                        <input type="tel" id="nomor_telepon" name="nomor_telepon" value="<?php echo htmlspecialchars($user_data['nomor_telepon']); ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="email">Email (tidak dapat diubah)</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                    </div>
                    
                    <h3 class="full-width" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">Alamat Pengiriman/Penjemputan</h3>
                    <div class="form-group full-width">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" placeholder="Contoh: Jl. Kelapa Puan" value="<?php echo htmlspecialchars($user_data['alamat']); ?>">
                    </div>
                     <div class="form-group">
                        <label for="no_rumah">Nomor Rumah</label>
                        <input type="text" id="no_rumah" name="no_rumah" placeholder="Contoh: 31A" value="<?php echo htmlspecialchars($user_data['no_rumah']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="rt">RT</label>
                        <input type="text" id="rt" name="rt" placeholder="Contoh: 006" value="<?php echo htmlspecialchars($user_data['rt']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="rw">RW</label>
                        <input type="text" id="rw" name="rw" placeholder="Contoh: 003" value="<?php echo htmlspecialchars($user_data['rw']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="kelurahan">Kelurahan / Desa</label>
                        <input type="text" id="kelurahan" name="kelurahan" placeholder="Contoh: Jagakarsa" value="<?php echo htmlspecialchars($user_data['kelurahan']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="kecamatan">Kecamatan</label>
                        <input type="text" id="kecamatan" name="kecamatan" placeholder="Contoh: Jagakarsa" value="<?php echo htmlspecialchars($user_data['kecamatan']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="kota">Kota / Kabupaten</label>
                        <input type="text" id="kota" name="kota" placeholder="Contoh: Jakarta Selatan" value="<?php echo htmlspecialchars($user_data['kota']); ?>">
                    </div>
                    <div class="form-group full-width">
                        <button type="submit" class="btn-submit">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>