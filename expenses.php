<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: dashboard.php");
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
    <title>Kelola Pengeluaran - Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="dashboard-nav">
        <div class="nav-brand">Kas Kelas</div>
        <div class="nav-user">
            <a href="dashboard.php" style="color:var(--text-secondary); text-decoration:none; margin-right:1rem; font-weight:500;">Kembali ke Dashboard</a>
            <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-grid">
            <div class="stat-card">
                <span class="stat-label">Total Uang Keluar</span>
                <div class="stat-value"><span id="totalExpense">0</span></div>
            </div>
            <div class="stat-card">
                <span class="stat-label">Sisa Saldo Kas</span>
                <div class="stat-value"><span id="finalBalance">0</span></div>
            </div>
        </div>

        <div class="table-container" style="margin-bottom: 3rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
                <h2 style="font-weight: 600;">Riwayat Pengeluaran</h2>
                <button onclick="openExpenseModal()" class="btn-primary" style="width:auto; padding: 10px 20px; font-size:0.9rem; margin:0;">+ Catat Pengeluaran</button>
            </div>
            <table id="expenseTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan Kegiatan</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="3" style="text-align:center;">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expense Modal -->
    <div id="expenseModal" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-close" onclick="closeExpenseModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
            <div class="modal-header">
                <h2>Catat Pengeluaran</h2>
                <p>Masukkan detail penggunaan uang kas</p>
            </div>
            
            <div class="form-group" style="text-align:left;">
                <label class="form-label">Keterangan Kegiatan</label>
                <input type="text" id="expenseDesc" class="form-input" placeholder="Contoh: Beli Spidol, Fotocopy Materi">
            </div>

            <div class="form-group" style="text-align:left;">
                <label class="form-label">Nominal (Rp)</label>
                <input type="number" id="expenseAmount" class="form-input" placeholder="0">
            </div>

            <button onclick="submitExpense()" class="btn-confirm" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                <span class="btn-text">Simpan Pengeluaran</span>
            </button>
        </div>
    </div>

    <script>
        const USER_ROLE = "<?php echo $role; ?>";
    </script>
    <script src="assets/app.js"></script>
</body>
</html>
