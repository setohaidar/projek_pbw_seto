<?php
// update_profil.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// ... (kode untuk memproses data form tidak berubah) ...
$user_id = $_SESSION['user_id'];
$nama_lengkap = $_POST['nama_lengkap'];
$nomor_telepon = $_POST['nomor_telepon'];
$alamat = $_POST['alamat'];
$no_rumah = $_POST['no_rumah'];
$rt = $_POST['rt'];
$rw = $_POST['rw'];
$kelurahan = $_POST['kelurahan'];
$kecamatan = $_POST['kecamatan'];
$kota = $_POST['kota'];

// ... (kode validasi dan simpan ke database tidak berubah) ...
$conn->begin_transaction();
try {
    // Update users
    $stmt_user = $conn->prepare("UPDATE users SET nama_lengkap = ?, nomor_telepon = ? WHERE id = ?");
    $stmt_user->bind_param("ssi", $nama_lengkap, $nomor_telepon, $user_id);
    $stmt_user->execute();
    $stmt_user->close();

    // Update/Insert alamat
    $stmt_check = $conn->prepare("SELECT id FROM alamat WHERE user_id = ?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $stmt_check->close();

    if ($result_check->num_rows > 0) {
        $stmt_alamat = $conn->prepare("UPDATE alamat SET alamat = ?, no_rumah = ?, rt = ?, rw = ?, kelurahan = ?, kecamatan = ?, kota = ? WHERE user_id = ?");
        $stmt_alamat->bind_param("sssssssi", $alamat, $no_rumah, $rt, $rw, $kelurahan, $kecamatan, $kota, $user_id);
    } else {
        $stmt_alamat = $conn->prepare("INSERT INTO alamat (user_id, alamat, no_rumah, rt, rw, kelurahan, kecamatan, kota, label) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Rumah')");
        $stmt_alamat->bind_param("isssssss", $user_id, $alamat, $no_rumah, $rt, $rw, $kelurahan, $kecamatan, $kota);
    }
    $stmt_alamat->execute();
    $stmt_alamat->close();

    $conn->commit();

    $_SESSION['nama_lengkap'] = $nama_lengkap;
    $_SESSION['message'] = "Profil berhasil diperbarui!";
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    $_SESSION['message'] = "Terjadi kesalahan saat memperbarui profil.";
}
$conn->close();

// Pengalihan kembali yang benar
header("Location: dashboardpengguna.php?page=profil_page");
exit();
?>