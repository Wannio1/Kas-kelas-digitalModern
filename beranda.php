<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

// Fetch School Name
$schoolName = ""; 
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'school_name'");
    $stmt->execute();
    $result = $stmt->fetch();
    if ($result && !empty($result['setting_value'])) {
        $schoolName = $result['setting_value'];
    }
} catch (Exception $e) { /* Ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kas Kelas <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/pm-list-styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/cash_verification.js?v=<?php echo time(); ?>" defer></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
</head>
<body>
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="fa-solid fa-wallet" style="color: var(--primary);"></i>
            Kas Kelas <?php echo htmlspecialchars($schoolName); ?>
        </div>
        
        <?php if ($role === 'bendahara'): ?>
        <div class="nav-menu">
            <a href="verifikasi.php" class="nav-link" title="Verifikasi Kas">
                <i class="fa-solid fa-circle-check"></i> <span class="nav-text">Verifikasi</span>
            </a>
            <a href="data_siswa.php" class="nav-link" title="Manajemen Siswa">
                <i class="fa-solid fa-users"></i> <span class="nav-text">Siswa</span>
            </a>
            <a href="data_pengeluaran.php" class="nav-link" title="Pengeluaran">
                <i class="fa-solid fa-arrow-trend-down"></i> <span class="nav-text">Pengeluaran</span>
            </a>
            <a href="rekap.php" class="nav-link" title="Rekapitulasi">
                <i class="fa-solid fa-chart-simple"></i> <span class="nav-text">Rekap</span>
            </a>
            <a href="pengaturan.php" class="nav-link" title="Pengaturan">
                <i class="fa-solid fa-gear"></i> <span class="nav-text">Pengaturan</span>
            </a>
        </div>
        <?php endif; ?>

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

    <?php if ($role === 'murid'): ?>
    <button onclick="window.location.href='info_siswa.php'" class="btn btn-primary" style="position: fixed; bottom: 30px; right: 30px; z-index: 100; box-shadow: var(--shadow-lg); border-radius: 50px; padding: 12px 24px;">
        <i class="fa-solid fa-users" style="font-size: 1.25rem;"></i> Jumlah Siswa
    </button>
    <?php endif; ?>

    <div class="container animate-fade-in">
        <div class="flex-between mb-4" style="align-items: flex-end;">
            <div>
                <h1 class="text-gradient">Dashboard Keuangan</h1>
                <p>Pantau arus kas dan status keuangan kelas secara realtime.</p>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.9rem; color: var(--text-muted);">
                    <i class="fa-regular fa-calendar"></i> <?php echo date('l, d F Y'); ?>
                </span>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <span class="form-label" style="text-transform: uppercase; letter-spacing: 0.05em;">
                    <?php echo ($role === 'bendahara') ? 'Total Kas Masuk (Hari ini)' : 'Total Saldo Kelas'; ?>
                </span>
                <div style="font-size: 2.5rem; font-weight: 700; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Rp <span id="totalAmount">0</span>
                </div>
            </div>
            
            <?php if ($role === 'bendahara'): ?>
            <div class="card">
                <span class="form-label" style="text-transform: uppercase; letter-spacing: 0.05em;">Saldo Akhir (Bersih)</span>
                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary);">
                    Rp <span id="finalBalance">0</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'murid'): ?>
            <div class="card" style="grid-column: span 2; display: flex; gap: 1rem;">
                <button onclick="document.getElementById('paymentModal').style.display = 'flex'" class="btn btn-primary w-full" style="padding: 1.5rem; border-radius: var(--radius-lg); background: linear-gradient(135deg, #059669, #10b981);">
                    <i class="fa-solid fa-globe" style="font-size: 2rem;"></i>
                    <div style="text-align: left;">
                        <div style="font-size: 1.1rem; font-weight: 700;">Bayar Online</div>
                        <div style="font-size: 0.85rem; opacity: 0.9;">QRIS, E-Wallet, VA (Otomatis)</div>
                    </div>
                </button>
                
                <button onclick="submitCashVerification()" class="btn btn-primary w-full" style="padding: 1.5rem; border-radius: var(--radius-lg); background: linear-gradient(135deg, #d97706, #fbbf24);">
                    <i class="fa-solid fa-money-bill" style="font-size: 2rem;"></i>
                    <div style="text-align: left;">
                        <div style="font-size: 1.1rem; font-weight: 700;">Bayar Tunai</div>
                        <div style="font-size: 0.85rem; opacity: 0.9;">Verifikasi Manual</div>
                    </div>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; margin-top: 2rem;">
            <!-- Income Table -->
            <div class="table-container" style="height: 500px; display: flex; flex-direction: column;">
                <div style="padding: 1.25rem; border-bottom: 1px solid var(--border-subtle); background: rgba(15, 23, 42, 0.5);">
                    <h2 style="font-size: 1.1rem; color: var(--success); display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                        <i class="fa-solid fa-circle-arrow-down"></i> Riwayat Pemasukan
                    </h2>
                </div>
                <div class="table-wrapper" style="flex: 1; overflow-y: auto;">
                    <table id="transactionTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Nominal</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" style="text-align:center; padding: 2rem;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Expense Table -->
            <div class="table-container" style="height: 500px; display: flex; flex-direction: column;">
                <div style="padding: 1.25rem; border-bottom: 1px solid var(--border-subtle); background: rgba(15, 23, 42, 0.5);">
                    <h2 style="font-size: 1.1rem; color: var(--danger); display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                        <i class="fa-solid fa-circle-arrow-up"></i> Riwayat Pengeluaran
                    </h2>
                </div>
                <div class="table-wrapper" style="flex: 1; overflow-y: auto;">
                    <table id="expenseTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" style="text-align:center; padding: 2rem;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($role === 'murid'): ?>
    <!-- Online Payment Modal (Midtrans) -->
    <div id="paymentModal" class="modal-overlay" style="
        position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); 
        display: none; align-items: center; justify-content: center; z-index: 1000;
        padding: 1rem;
    ">
        <div class="card" style="max-width: 400px; width: 100%; padding: 2rem; text-align: center; position: relative;">
            <button onclick="closePaymentModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            <i class="fa-solid fa-credit-card" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Pembayaran Online</h2>
            <p style="margin-bottom: 1.5rem;">Masukkan nominal pembayaran.</p>
            
            <div class="form-group" style="text-align: left;">
                <label class="form-label">Nominal (Rp)</label>
                <input type="number" id="payAmount" class="form-input" placeholder="Contoh: 20000">
            </div>

            <button onclick="processMidtransPayment()" class="btn btn-primary w-full" id="btnPayNow">
                Bayar Sekarang
            </button>
        </div>
    </div>
    <script>
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
    </script>
    <?php endif; ?>

    <script>
        const USER_ROLE = "<?php echo $role; ?>";
    </script>
    <script src="assets/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>

