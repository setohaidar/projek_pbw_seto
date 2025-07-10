<?php
// update_profil.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data dari form, termasuk provinsi
$nama_lengkap = $_POST['nama_lengkap'];
$nomor_telepon = $_POST['nomor_telepon'];
$alamat = $_POST['alamat'];
$no_rumah = $_POST['no_rumah'];
$rt = $_POST['rt'];
$rw = $_POST['rw'];
$kelurahan = $_POST['kelurahan'];
$kecamatan = $_POST['kecamatan'];
$kota = $_POST['kota'];
$provinsi = $_POST['provinsi']; // Mengambil data provinsi

if (empty($nama_lengkap) || empty($nomor_telepon)) {
    $_SESSION['message'] = "Nama lengkap dan nomor telepon tidak boleh kosong.";
    header("Location: dashboardpengguna.php?page=profil_page");
    exit();
}

$conn->begin_transaction();
try {
    $stmt_user = $conn->prepare("UPDATE `users` SET `nama_lengkap` = ?, `nomor_telepon` = ? WHERE `id` = ?");
    $stmt_user->bind_param("ssi", $nama_lengkap, $nomor_telepon, $user_id);
    $stmt_user->execute();
    $stmt_user->close();

    $stmt_check = $conn->prepare("SELECT `id` FROM `alamat` WHERE `user_id` = ?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $stmt_check->close();

    if ($result_check->num_rows > 0) {
        // PERBAIKAN: Menambahkan `provinsi` ke query UPDATE
        $stmt_alamat = $conn->prepare("UPDATE `alamat` SET `alamat` = ?, `no_rumah` = ?, `rt` = ?, `rw` = ?, `kelurahan` = ?, `kecamatan` = ?, `kota` = ?, `provinsi` = ? WHERE `user_id` = ?");
        $stmt_alamat->bind_param("ssssssssi", $alamat, $no_rumah, $rt, $rw, $kelurahan, $kecamatan, $kota, $provinsi, $user_id);
    } else {
        // PERBAIKAN: Menambahkan `provinsi` ke query INSERT
        $default_label = 'Rumah';
        $stmt_alamat = $conn->prepare("INSERT INTO `alamat` (`user_id`, `label`, `alamat`, `no_rumah`, `rt`, `rw`, `kelurahan`, `kecamatan`, `kota`, `provinsi`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_alamat->bind_param("isssssssss", $user_id, $default_label, $alamat, $no_rumah, $rt, $rw, $kelurahan, $kecamatan, $kota, $provinsi);
    }
    $stmt_alamat->execute();
    $stmt_alamat->close();

    $conn->commit();

    $_SESSION['nama_lengkap'] = $nama_lengkap;
    $_SESSION['message'] = "Profil berhasil diperbarui!";
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    $_SESSION['message'] = "Terjadi kesalahan saat memperbarui profil: " . $exception->getMessage();
}

$conn->close();
header("Location: dashboardpengguna.php?page=profil_page");
exit();
?>
