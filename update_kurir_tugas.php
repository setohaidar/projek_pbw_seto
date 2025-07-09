<?php
// update_kurir_tugas.php
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya kurir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['tugas_id'], $_POST['tipe_tugas'], $_POST['status_baru'])) {
        $tugas_id = $_POST['tugas_id'];
        $tipe_tugas = $_POST['tipe_tugas'];
        $status_baru = $_POST['status_baru'];
        $kurir_id = $_SESSION['user_id'];
        
        $tabel = '';
        $kolom_id = 'id';

        // Tentukan tabel mana yang akan diupdate
        if ($tipe_tugas == 'penjemputan') {
            $tabel = 'pengajuan';
        } elseif ($tipe_tugas == 'pengiriman') {
            $tabel = 'penukaran_poin';
        } else {
            // Tipe tugas tidak valid
            header("Location: dashboardkurir.php?error=invalid_task");
            exit();
        }

        // 1. Ambil data tugas saat ini, termasuk statusnya
        $kolom_berat = ($tipe_tugas == 'penjemputan') ? ', berat' : '';
        $stmt_get_data = $conn->prepare("SELECT user_id, status $kolom_berat FROM $tabel WHERE $kolom_id = ? AND kurir_id = ?");
        $stmt_get_data->bind_param("ii", $tugas_id, $kurir_id);
        $stmt_get_data->execute();
        $result_data = $stmt_get_data->get_result();
        if ($result_data->num_rows == 0) {
            header("Location: dashboardkurir.php?error=unauthorized");
            exit();
        }
        $data_tugas = $result_data->fetch_assoc();
        $user_id_penerima = $data_tugas['user_id'];
        $status_sekarang = $data_tugas['status'];
        $stmt_get_data->close();

        // 2. Cek apakah tugas sudah selesai/dibatalkan sebelumnya untuk mencegah duplikasi
        if ($status_sekarang == 'Selesai' || $status_sekarang == 'Dibatalkan') {
            header("Location: dashboardkurir.php?update=already_done");
            exit();
        }

        $conn->begin_transaction();
        try {
            // Update status tugas di tabel yang sesuai
            $stmt_update = $conn->prepare("UPDATE $tabel SET status = ? WHERE $kolom_id = ? AND kurir_id = ?");
            $stmt_update->bind_param("sii", $status_baru, $tugas_id, $kurir_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            // Logika pemberian poin (hanya untuk penjemputan sampah yang selesai)
            $poin_didapat = 0;
            if ($tipe_tugas == 'penjemputan' && $status_baru == 'Selesai') {
                $berat_sampah = $data_tugas['berat'];
                $poin_didapat = $berat_sampah * 1000;

                $stmt_add_poin = $conn->prepare("UPDATE users SET poin = poin + ? WHERE id = ?");
                $stmt_add_poin->bind_param("di", $poin_didapat, $user_id_penerima);
                $stmt_add_poin->execute();
                $stmt_add_poin->close();
            }

            // Buat notifikasi untuk pengguna
            $pesan = "Status " . ($tipe_tugas == 'penjemputan' ? "pengajuan sampah" : "penukaran hadiah") . " #{$tugas_id} Anda telah diperbarui menjadi: {$status_baru}.";
            if ($poin_didapat > 0) {
                $pesan .= " Anda mendapatkan {$poin_didapat} poin!";
            }
            // ==== PERBAIKAN DI SINI ====
            $link = ($tipe_tugas == 'penjemputan' ? "status_pengajuan.php" : "status_penukaran_poin.php");
            
            $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
            $stmt_notif->bind_param("iss", $user_id_penerima, $pesan, $link);
            $stmt_notif->execute();
            $stmt_notif->close();

            $conn->commit();
            header("Location: dashboardkurir.php?update=sukses");

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboardkurir.php?update=gagal");
        }
        
        $conn->close();
        exit();
    }
}

header("Location: dashboardkurir.php");
exit();
?>