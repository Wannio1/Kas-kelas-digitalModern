<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: beranda.php");
    exit;
}
require_once 'koneksi.php';

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

$schoolName = "";
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'school_name'");
    $stmt->execute();
    $res = $stmt->fetch();
    if($res) $schoolName = $res['setting_value'];
} catch(Exception $e){}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengeluaran - Kas Kelas <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Hide number input spinners */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] { -moz-appearance: textfield; }
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
            <a href="data_pengeluaran.php" class="nav-link active">
                <i class="fa-solid fa-arrow-trend-down"></i> Pengeluaran
            </a>
            <a href="rekap.php" class="nav-link">
                <i class="fa-solid fa-chart-simple"></i> Rekap
            </a>
            <a href="pengaturan.php" class="nav-link">
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
                <h1 class="text-gradient" style="font-size: 1.8rem;">Manajemen Pengeluaran</h1>
                <p>Catat dan pantau penggunaan dana kas kelas.</p>
            </div>

        </div>

        <div class="dashboard-grid">
            <div class="card">
                <span class="form-label" style="text-transform: uppercase;">Total Uang Keluar</span>
                <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: var(--danger);">
                    Rp <span id="totalExpense">0</span>
                </div>
            </div>
            <div class="card">
                <span class="form-label" style="text-transform: uppercase;">Sisa Saldo Kas</span>
                <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: var(--success);">
                    Rp <span id="finalBalance">0</span>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.25rem; font-weight: 600; display:flex; align-items:center; gap:0.5rem; margin:0;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Riwayat Pengeluaran
                </h2>
                <button onclick="openExpenseModal()" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Catat Pengeluaran
                </button>
            </div>
            
            <div class="table-wrapper">
                <table id="expenseTable">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan Kegiatan</th>
                            <th>Nominal</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" style="text-align:center; padding: 2rem;">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Expense Modal (Add/Edit) -->
    <div id="expenseModal" class="modal-overlay" style="
        position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); 
        display: none; align-items: center; justify-content: center; z-index: 1000;
    ">
        <div class="card" style="max-width: 450px; width: 90%; padding: 2rem;">
            <div class="flex-between mb-4">
                <h2 id="modalTitle" style="font-size: 1.25rem;">Catat Pengeluaran</h2>
                <button onclick="closeExpenseModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <input type="hidden" id="expenseId">
            <div class="form-group">
                <label class="form-label">Keterangan Kegiatan</label>
                <input type="text" id="expenseDesc" class="form-input" placeholder="Contoh: Beli Spidol, Fotocopy Materi">
            </div>

            <div class="form-group">
                <label class="form-label">Nominal (Rp)</label>
                <input type="number" id="expenseAmount" class="form-input" placeholder="0">
            </div>

            <button onclick="submitExpense()" class="btn btn-primary w-full" style="background: linear-gradient(135deg, var(--danger), #b91c1c);">
                Simpan Pengeluaran
            </button>
        </div>
    </div>

    <script>
        const USER_ROLE = "<?php echo $role; ?>";
    </script>
    <script src="assets/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>

