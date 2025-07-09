<?php
// form_pengajuan.php (Konten)
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$alamat_list = [];

// Ambil daftar alamat pengguna
$query_alamat = "SELECT id, label, CONCAT_WS(', ', alamat, no_rumah) AS alamat_display FROM alamat WHERE user_id = ?";
$stmt_alamat = $conn->prepare($query_alamat);
$stmt_alamat->bind_param("i", $user_id);
$stmt_alamat->execute();
$result_alamat = $stmt_alamat->get_result();
while ($row = $result_alamat->fetch_assoc()) {
    $alamat_list[] = $row;
}
$stmt_alamat->close();

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alamat_id = $_POST['alamat_id'];
    $jenis_sampah = $_POST['jenis_sampah'];
    $berat = $_POST['berat'];
    $catatan_user = trim($_POST['catatan_user']);

    if (empty($alamat_id) || empty($jenis_sampah) || empty($berat)) {
        $errors[] = "Harap isi semua field yang wajib diisi.";
    }

    if ($berat <= 0) {
        $errors[] = "Berat sampah tidak boleh nol atau negatif.";
    }

    if (empty($errors)) {
        $stmt_insert = $conn->prepare("INSERT INTO pengajuan (user_id, alamat_id, jenis_sampah, berat, catatan_user) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iisds", $user_id, $alamat_id, $jenis_sampah, $berat, $catatan_user);
        
        if ($stmt_insert->execute()) {
            header("Location: dashboardpengguna.php?page=status_pengajuan&status=sukses");
            exit();
        } else {
            $errors[] = "Gagal membuat pengajuan.";
        }
        $stmt_insert->close();
    }
}
?>

<div class="content-header">
    <h1>Ajukan Pembuangan Sampah</h1>
</div>
<div class="content-wrapper">
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="dashboardpengguna.php?page=form_pengajuan" method="POST">
        <div class="input-group">
            <label for="jenis_sampah">Jenis Sampah *</label>
            <select id="jenis_sampah" name="jenis_sampah" required>
                <option value="">-- Pilih Jenis Sampah --</option>
                <option value="Organik Kering">Sampah Organik Kering (Kardus, Kertas)</option>
                <option value="Organik Basah">Sampah Organik Basah (Sisa Sayur, Sisa Buah, Sisa Makanan)</option>
                <option value="Plastik">Plastik (Botol, Gelas, Kemasan, dll)</option>
                <option value="Styrofoam">Styrofoam</option>
                <option value="Kaleng">Kaleng</option>
                <option value="Beling/Kaca">Beling/Kaca (Botol, Pecahan Kaca)</option>
                <option value="Tekstil">Tekstil (Pakaian atau Kain Bekas)</option>
                <option value="Baterai">Baterai</option>
                <option value="Kabel">Kabel</option>
                <option value="Lampu">Lampu</option>
                <option value="Elektronik Lainnya">Sampah Elektronik Lainnya</option>
                <option value="Obat-obatan">Obat-obatan Kedaluwarsa</option>
            </select>
        </div>
        <div class="input-group">
            <label for="berat">Estimasi Berat (kg) *</label>
            <input type="number" id="berat" name="berat" step="0.1" placeholder="Contoh: 2.5" required min="0">
        </div>
        <div class="input-group">
            <label for="alamat_id">Alamat Penjemputan *</label>
            <select id="alamat_id" name="alamat_id" required>
                <option value="">-- Pilih Alamat --</option>
                <?php foreach($alamat_list as $alamat): ?>
                    <option value="<?php echo $alamat['id']; ?>"><?php echo htmlspecialchars($alamat['label']) . ' - ' . htmlspecialchars($alamat['alamat_display']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-group">
            <label for="catatan_user">Catatan (Opsional)</label>
            <textarea id="catatan_user" name="catatan_user" rows="3" placeholder="Contoh: Sampah ada di depan pagar"></textarea>
        </div>
        <button type="submit" class="btn-submit">Kirim Pengajuan</button>
    </form>
</div>