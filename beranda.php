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



            <div class="payment-actions">
                <button onclick="document.getElementById('paymentModal').style.display = 'flex'" class="btn btn-payment btn-online">
                    <i class="fa-solid fa-globe"></i>
                    <div class="btn-payment-content">
                        <div class="btn-payment-title">Bayar Online</div>
                        <div class="btn-payment-subtitle">QRIS, E-Wallet, VA (Otomatis)</div>
                    </div>
                </button>
                
                <button onclick="openCashPaymentModal()" class="btn btn-payment btn-cash">
                    <i class="fa-solid fa-money-bill"></i>
                    <div class="btn-payment-content">
                        <div class="btn-payment-title">Bayar Tunai</div>
                        <div class="btn-payment-subtitle">Verifikasi Manual</div>
                    </div>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-grid grid-split" style="margin-top: 2rem;">
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
    <!-- Cash Payment Modal -->
    <div id="cashPaymentModal" class="modal-overlay" style="
        position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); 
        display: none; align-items: center; justify-content: center; z-index: 1000;
        padding: 1rem;
    ">
        <div class="card animate-fade-in" style="max-width: 400px; width: 100%; padding: 2.5rem; text-align: center; position: relative; border: 1px solid rgba(255,255,255,0.1);">
            <button onclick="closeCashPaymentModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: color 0.3s;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">&times;</button>
            
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 1px solid rgba(16, 185, 129, 0.3); box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-money-bill-wave" style="font-size: 2.5rem; color: #34d399;"></i>
            </div>
            
            <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem; font-weight: 700; background: linear-gradient(to right, #fff, #cbd5e1); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Pembayaran Tunai</h2>
            <p style="margin-bottom: 2rem; color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">Silakan serahkan uang tunai ke Bendahara, lalu input nominalnya di sini untuk verifikasi.</p>
            
            <div class="form-group" style="text-align: left; margin-bottom: 2rem;">
                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem;"><i class="fa-solid fa-coins" style="color: var(--primary);"></i> Nominal Pembayaran (Rp)</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-weight: 500;">Rp</span>
                    <input type="number" id="cashAmount" class="form-input" placeholder="0" style="padding-left: 2.5rem; font-size: 1.1rem; font-weight: 600;">
                </div>
            </div>

            <button onclick="processCashPayment()" class="btn btn-verification" id="btnCashPay">
                <i class="fa-solid fa-paper-plane"></i> Kirim Permintaan
            </button>
        </div>
    </div>

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

    <!-- Floating Data Siswa Button (Desktop & Mobile) -->
    <?php if ($role === 'murid'): ?>
    <a href="info_siswa.php" class="fab-student" title="Data Siswa">
        <i class="fa-solid fa-users"></i>
        <span class="fab-label">Data Siswa</span>
    </a>
    <?php endif; ?>

    <footer class="main-footer animate-fade-in">
        <p>&copy; <?php echo date('Y'); ?> Kas Kelas <?php echo htmlspecialchars($schoolName); ?>. &bull; <span>Everywann Project</span></p>
    </footer>

    <script>
        const USER_ROLE = "<?php echo $role; ?>";
    </script>
    <script src="assets/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>

