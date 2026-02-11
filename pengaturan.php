<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: beranda.php");
    exit;
}
require_once 'koneksi.php';
$fullName = $_SESSION['full_name'];

$schoolName = "";
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'school_name'");
    $stmt->execute();
    $res = $stmt->fetch();
    if($res) $schoolName = $res['setting_value'];
} catch(Exception $e){}

// Fetch current settings
$settings = $pdo->query("SELECT * FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Kas Kelas <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional page-specific styles if needed */
    </style>
</head>
<body>
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="fa-solid fa-wallet" style="color: var(--primary);"></i>
            Kas Kelas <?php echo htmlspecialchars($schoolName); ?>
        </div>
        <div class="nav-menu">
            <a href="beranda.php" class="nav-link">
                <i class="fa-solid fa-table-columns"></i> Dashboard
            </a>
            <a href="verifikasi.php" class="nav-link">
                <i class="fa-solid fa-circle-check"></i> Verifikasi
            </a>
            <a href="data_siswa.php" class="nav-link">
                <i class="fa-solid fa-users"></i> Siswa
            </a>
            <a href="data_pengeluaran.php" class="nav-link">
                <i class="fa-solid fa-arrow-trend-down"></i> Pengeluaran
            </a>
            <a href="rekap.php" class="nav-link">
                <i class="fa-solid fa-chart-simple"></i> Rekap
            </a>
            <a href="pengaturan.php" class="nav-link active">
                <i class="fa-solid fa-gear"></i> Pengaturan
            </a>
        </div>
        <div class="nav-user">
            <div class="user-info" style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-user" style="font-size: 1.5rem; color: var(--primary);"></i>
                <span class="user-name" style="font-weight: 500;"><?php echo htmlspecialchars($fullName); ?></span>
            </div>
            <a href="keluar.php" class="btn btn-danger" style="padding: 0.5rem; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Keluar">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </nav>

    <div class="container animate-fade-in">
        <div class="flex-between mb-4">
            <div>
                <h1 class="text-gradient" style="font-size: 1.8rem;">Pengaturan Aplikasi</h1>
                <p>Sesuaikan preferensi sistem dan manajemen data.</p>
            </div>
        </div>

        <div class="dashboard-grid" style="grid-template-columns: 1fr; gap: 2rem;">
            
            <!-- General Settings -->
            <div class="card">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-gear"></i> Pengaturan Umum
                </h2>
                <form id="generalSettingsForm">
                    <div class="dashboard-grid" style="margin-bottom: 0; grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label class="form-label">Nama Sekolah / Kelas</label>
                            <input type="text" name="school_name" class="form-input" value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nominal Iuran Mingguan (Rp)</label>
                            <input type="number" name="monthly_dues" class="form-input" value="<?php echo htmlspecialchars($settings['monthly_dues'] ?? '10000'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Mulai Semester</label>
                            <input type="date" name="semester_start_date" class="form-input" value="<?php echo htmlspecialchars($settings['semester_start_date'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Data Management -->
            <div class="card">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-database"></i> Manajemen Data
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <!-- Backup -->
                    <div>
                        <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">Backup Database</h3>
                        <p style="font-size: 0.9rem; margin-bottom: 1rem;">Unduh salinan lengkap database (.sql) untuk keamanan.</p>
                        <a href="backup_db.php" class="btn btn-secondary w-full" target="_blank">
                            <i class="fa-solid fa-download"></i> Download Backup
                        </a>
                    </div>

                    <!-- Restore -->
                    <div>
                        <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">Restore Database</h3>
                        <p style="font-size: 0.9rem; margin-bottom: 1rem;">Kembalikan data dari file backup (.sql). Data saat ini akan ditimpa.</p>
                        <form id="restoreForm">
                            <input type="file" id="restoreFile" accept=".sql" style="display: none;" onchange="handleFileSelect(this)">
                            <label for="restoreFile" class="file-upload">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: var(--text-muted);"></i>
                                <span id="fileName" style="color: var(--text-muted); font-size: 0.9rem;">Klik untuk pilih file .sql</span>
                            </label>
                            <button type="submit" class="btn btn-primary w-full" style="margin-top: 1rem; display: none;" id="btnRestore">
                                <i class="fa-solid fa-rotate-left"></i> Restore Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card" style="border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--danger); display:flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Zona Bahaya
                </h2>
                <p style="margin-bottom: 1.5rem; font-size: 0.9rem;">Tindakan di bawah ini tidak dapat dibatalkan. Harap berhati-hati.</p>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="resetData('transactions')" class="btn btn-danger">Hapus Semua Transaksi & Pengeluaran</button>
                    <button onclick="resetData('full')" class="btn btn-danger" style="background: rgba(127, 29, 29, 0.8);">Reset Total (Termasuk Siswa)</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('generalSettingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_settings');
            
            try {
                const res = await fetch('proses.php?action=update_settings', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) alert('Pengaturan berhasil disimpan!');
                else alert('Gagal: ' + data.message);
            } catch (e) { alert('Error network'); }
        });

        function handleFileSelect(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Klik untuk pilih file .sql';
            document.getElementById('fileName').textContent = fileName;
            document.getElementById('btnRestore').style.display = input.files[0] ? 'block' : 'none';
        }

        document.getElementById('restoreForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!confirm('PERINGATAN: Database saat ini akan ditimpa total dengan data dari file backup. Lanjutkan?')) return;

            const formData = new FormData();
            formData.append('backup_file', document.getElementById('restoreFile').files[0]);

            const btn = document.getElementById('btnRestore');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            try {
                const res = await fetch('restore_db.php', { method: 'POST', body: formData });
                const text = await res.text();
                alert(text);
                window.location.reload();
            } catch (e) { 
                alert('Gagal restore database'); 
                btn.disabled = false;
            }
        });

        async function resetData(type) {
            const msg = type === 'full' 
                ? 'ANDA YAKIN? Ini akan menghapus SEMUA user (kecuali Anda), transaksi, dan pengeluaran. Aplikasi akan kembali seperti baru.'
                : 'Anda yakin akan menghapus semua riwayat transaksi dan pengeluaran? Data siswa akan tetap ada.';
            
            if (!confirm(msg)) return;
            if (!confirm('Yakin sekali lagi?')) return;

            try {
                const formData = new FormData();
                formData.append('type', type);
                const res = await fetch('reset_db.php', { method: 'POST', body: formData });
                const text = await res.text();
                alert(text);
                if (type === 'full') window.location.href = 'keluar.php';
                else window.location.reload();
            } catch (e) { alert('Gagal reset'); }
        }
    </script>
</body>
</html>

