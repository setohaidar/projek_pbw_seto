<?php
// dashboardadmin.php
session_start();
require_once 'db_connect.php';

// Keamanan: Cek apakah pengguna sudah login dan rolenya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil daftar kurir untuk dropdown penugasan
$kurir_list = [];
$result_kurir = $conn->query("SELECT id, nama_lengkap FROM users WHERE role = 'kurir'");
while ($row = $result_kurir->fetch_assoc()) {
    $kurir_list[] = $row;
}

// Ambil data pengajuan sampah
$pengajuan_list = [];
$query = "
    SELECT 
        p.id AS pengajuan_id, 
        p.user_id, 
        p.jenis_sampah, 
        p.berat, 
        p.status, 
        p.jadwal_penjemputan,
        u.nama_lengkap AS nama_user,
        k.nama_lengkap AS nama_kurir
    FROM pengajuan p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN users k ON p.kurir_id = k.id
    ORDER BY 
        CASE 
            WHEN p.status = 'Menunggu Persetujuan' THEN 1
            WHEN p.status = 'Jadwal Ditentukan' THEN 2 
            ELSE 3 
        END, 
        p.tanggal_pengajuan ASC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $pengajuan_list[] = $row;
}
$conn->close();

// Variabel untuk menandai halaman aktif di sidebar
$current_page = 'manajemen_sampah';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Sampah</title>
    <link rel="stylesheet" href="dashboardadmin.css">
    <style>
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ccc;
            background-color: #f8f9fa;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-btn.active, .filter-btn:hover {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
    </style>
    </head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboardadmin.php" class="<?php echo ($current_page == 'manajemen_sampah') ? 'active' : ''; ?>">
                        Manajemen Sampah
                    </a>
                </li>
                <li>
                    <a href="admin_penukaran_hadiah.php" class="<?php echo ($current_page == 'manajemen_hadiah') ? 'active' : ''; ?>">
                        Manajemen Hadiah
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <span>Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="admin-main-content">
            <div class="content-header">
                <h1>Manajemen Pengajuan Sampah</h1>
                <input type="text" id="searchInput" placeholder="Cari berdasarkan nama atau jenis sampah..." style="width: 100%; padding: 12px; margin-top: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                
                <div class="filter-buttons">
                    <button class="filter-btn active" data-status="Semua">Semua</button>
                    <button class="filter-btn" data-status="Menunggu Persetujuan">Menunggu Persetujuan</button>
                    <button class="filter-btn" data-status="Jadwal Ditentukan">Jadwal Ditentukan</button>
                </div>
                </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Detail Sampah</th>
                            <th>Jadwal</th>
                            <th>Kurir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="searchResultsBody">
                        <?php if (empty($pengajuan_list)): ?>
                            <tr><td colspan="7" style="text-align:center;">Belum ada pengajuan sampah.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pengajuan_list as $p): ?>
                                <tr>
                                    <td>#<?php echo $p['pengajuan_id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['nama_user']); ?></td>
                                    <td><?php echo htmlspecialchars($p['jenis_sampah']); ?> (<?php echo $p['berat']; ?> kg)</td>
                                    <td><?php echo $p['jadwal_penjemputan'] ? date('d M Y, H:i', strtotime($p['jadwal_penjemputan'])) : '-'; ?></td>
                                    <td><?php echo $p['nama_kurir'] ?? 'Belum Ditugaskan'; ?></td>
                                    <td><span class="status <?php echo strtolower(str_replace(' ', '-', $p['status'])); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                                    <td class="action-cell">
                                        <?php if ($p['status'] == 'Menunggu Persetujuan'): ?>
                                            <form action="update_status.php" method="POST" class="action-form">
                                                <input type="hidden" name="pengajuan_id" value="<?php echo $p['pengajuan_id']; ?>">
                                                <input type="hidden" name="user_id_penerima" value="<?php echo $p['user_id']; ?>">
                                                <button type="submit" name="status_baru" value="Disetujui" class="btn-approve">Setujui</button>
                                                <button type="submit" name="status_baru" value="Ditolak" class="btn-reject">Tolak</button>
                                            </form>
                                        <?php elseif ($p['status'] == 'Jadwal Ditentukan'): ?>
                                            <form action="assign_kurir.php" method="POST">
                                                <input type="hidden" name="pengajuan_id" value="<?php echo $p['pengajuan_id']; ?>">
                                                <select name="kurir_id" required>
                                                    <option value="">-- Pilih Kurir --</option>
                                                    <?php foreach ($kurir_list as $kurir): ?>
                                                        <option value="<?php echo $kurir['id']; ?>"><?php echo htmlspecialchars($kurir['nama_lengkap']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit">Tugaskan</button>
                                            </form>
                                        <?php else: echo '-'; endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterButtons = document.querySelectorAll('.filter-btn');

        // Fungsi untuk menjalankan pencarian
        function performSearch() {
            const query = searchInput.value;
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-status');
            const resultsBody = document.getElementById("searchResultsBody");

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "live_search.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    resultsBody.innerHTML = this.responseText;
                }
            };

            // Kirim query dan status filter ke server
            xhr.send("query=" + encodeURIComponent(query) + "&status=" + encodeURIComponent(activeFilter));
        }

        // Event listener untuk input pencarian
        searchInput.addEventListener('keyup', performSearch);

        // Event listener untuk tombol filter
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                performSearch();
            });
        });
    });
    </script>
    </body>
</html>