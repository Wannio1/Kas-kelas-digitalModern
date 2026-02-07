<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/pm-list-styles.css">
    <script src="assets/cash_verification.js" defer></script>
</head>
<body>
    <nav class="dashboard-nav">
        <div class="nav-brand">Kas Kelas</div>
        <div class="nav-user">
            <span class="user-name">Halo, <?php echo htmlspecialchars($fullName); ?> (<?php echo ucfirst($role); ?>)</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-grid">
            <div class="stat-card">
                <span class="stat-label">
                    <?php echo ($role === 'bendahara') ? 'Total Kas Masuk (Kotor)' : 'Total Pembayaran Saya'; ?>
                </span>
                <div class="stat-value">Rp <span id="totalAmount">0</span></div>
            </div>
            
            <?php if ($role === 'bendahara'): ?>
            <div class="stat-card" style="cursor:pointer;" onclick="window.location.href='expenses.php'">
                <span class="stat-label">Kelola Pengeluaran</span>
                <div class="stat-value">Lihat Data &rarr;</div>
            </div>
            <div class="stat-card">
                <span class="stat-label">Saldo Akhir (Bersih)</span>
                <div class="stat-value"><span id="finalBalance">0</span></div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'murid'): ?>
            <div class="stat-card" style="justify-content: center; align-items: center;">
                <button id="payButton" class="btn-primary" style="margin: 0;">Bayar Kas (QRIS)</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pending Cash Verifications (Bendahara Only) -->
        <?php if ($role === 'bendahara'): ?>
        <div id="pendingVerificationsContainer" style="margin: 2rem 0;">
            <!-- Populated by JavaScript -->
        </div>
        <?php endif; ?>

        <div class="table-container">
            <h2 style="margin-bottom: 1rem; font-weight: 600;">Riwayat Pemasukan Kas</h2>
            <table id="transactionTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Siswa</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <?php if ($role === 'bendahara'): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated by JS -->
                    <tr><td colspan="5" style="text-align:center;">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- QRIS Modal (Only for Murid) -->
    <?php if ($role === 'murid'): ?>
    <div id="qrisModal" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-close" onclick="closeModal()">&times;</button>
            <div class="modal-header">
                <h2>Pembayaran Aman</h2>
                <p>Scan QR Code atau pilih metode E-Wallet</p>
            </div>
            
            <div class="qr-section">
                <div class="qr-frame">
                    <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BayarKasKelas_GOPAY" alt="QR Code" class="qr-img">
                    <div class="scan-line"></div>
                </div>
                <p id="qrInstruction">Scan QR Code GOPAY</p>
            </div>

            <div class="payment-selector">
                <label class="pm-item">
                    <input type="radio" name="pmKey" value="gopay" checked onchange="updateQr('gopay')">
                    <div class="pm-row">
                        <div class="pm-info">
                            <img src="img/gopay_logo.png" alt="GoPay" class="pm-img">
                            <span class="pm-label">GoPay</span>
                        </div>
                        <div class="pm-radio-circle"></div>
                    </div>
                </label>
                <label class="pm-item">
                    <input type="radio" name="pmKey" value="ovo" onchange="updateQr('ovo')">
                    <div class="pm-row">
                        <div class="pm-info">
                            <img src="img/ovo_logo.webp" alt="OVO" class="pm-img">
                            <span class="pm-label">OVO</span>
                        </div>
                        <div class="pm-radio-circle"></div>
                    </div>
                </label>
                <label class="pm-item">
                    <input type="radio" name="pmKey" value="dana" onchange="updateQr('dana')">
                    <div class="pm-row">
                        <div class="pm-info">
                            <img src="img/Logo DANA -  dianisa.com.png" alt="DANA" class="pm-img">
                            <span class="pm-label">DANA</span>
                        </div>
                        <div class="pm-radio-circle"></div>
                    </div>
                </label>
                <label class="pm-item">
                    <input type="radio" name="pmKey" value="cash" onchange="updateQr('cash')">
                    <div class="pm-row">
                        <div class="pm-info">
                            <div class="pm-img" style="display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">💵</div>
                            <span class="pm-label">Tunai (Cash)</span>
                        </div>
                        <div class="pm-radio-circle"></div>
                    </div>
                </label>
            </div>

            <a id="deepLinkBtn" href="gojek://" class="btn-deeplink">
                Buka Aplikasi GOPAY
            </a>

            <!-- Amount Input Removed as per request -->

            <button id="confirmPaymentBtn" class="btn-confirm">
                <span class="btn-text">Konfirmasi Pembayaran</span>
                <span class="btn-arrow">→</span>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Pass PHP variables to JS
        const USER_ROLE = "<?php echo $role; ?>";
    </script>
    <script src="assets/app.js"></script>
</body>
</html>
