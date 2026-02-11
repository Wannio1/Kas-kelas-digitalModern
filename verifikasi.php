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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kas - Kas Kelas <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <a href="verifikasi.php" class="nav-link active">
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
        
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
            <div class="card">
                <span class="form-label" style="text-transform: uppercase;">Menunggu Verifikasi</span>
                <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: var(--warning);"><?php echo count($pendingTransactions ?? []); ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 600; display:flex; align-items:center; gap:0.5rem; margin: 0;">
                    <i class="fa-solid fa-money-bill-wave"></i> Verifikasi Pembayaran Tunai
                </h2>
                <!-- Filter tabs removed as we only verify cash -->
            </div>

            <div class="table-wrapper">
                <table id="verificationTable">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Metode</th>
                            <th>Nominal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" style="text-align:center; padding: 2rem;">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let allVerifications = [];
        let currentFilter = 'all';

        document.addEventListener('DOMContentLoaded', fetchPendingVerificationsPage);

        async function fetchPendingVerificationsPage() {
            try {
                const res = await fetch('proses.php?action=get_pending_verifications');
                const data = await res.json();
                
                if (data.success) {
                    // Filter locally to ensure only cash is shown, even if API returns others
                    allVerifications = data.verifications.filter(v => v.payment_method === 'cash');
                    renderTable();
                    document.querySelector('.stat-value').innerText = allVerifications.length;
                } else {
                    allVerifications = [];
                    renderTable();
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Removed filterTransactions function as it's no longer needed

        function renderTable() {
            const tbody = document.querySelector('#verificationTable tbody');
            tbody.innerHTML = '';

            const filtered = allVerifications; // No further filtering needed

            if (filtered.length > 0) {
                filtered.forEach(v => {
                    const date = new Date(v.transaction_date).toLocaleDateString('id-ID', {
                        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                    });
                    const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(v.amount);
                    const methodBadge = v.payment_method === 'cash' 
                        ? `<span class="badge badge-warning">Tunai</span>`
                        : `<span class="badge badge-success">QRIS</span>`;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${date}</td>
                        <td style="font-weight: 500;">${v.full_name}</td>
                        <td>${methodBadge}</td>
                        <td>${amount}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                ${v.proof_image ? 
                                    `<a href="${v.proof_image}" target="_blank" class="btn btn-secondary" style="padding:0.4rem 0.8rem; font-size:0.8rem;" title="Lihat Bukti">
                                        <i class="fa-solid fa-image"></i>
                                    </a>` : 
                                    (v.payment_method !== 'cash' ? `<span style="font-size:0.8rem; color:var(--danger);">Tanpa Bukti</span>` : '')
                                }
                                <button onclick="verifyPayment(${v.id}, 'approve')" class="btn btn-primary" style="padding:0.4rem 0.8rem; background: var(--success); box-shadow: none;" title="Setujui">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button onclick="verifyPayment(${v.id}, 'reject')" class="btn btn-danger" style="padding:0.4rem 0.8rem;" title="Tolak">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--text-muted);">Tidak ada permintaan verifikasi.</td></tr>';
            }
        }

        async function verifyPayment(id, action) {
            const actionText = action === 'approve' ? 'Setujui' : 'Tolak';
            if(!confirm(actionText + ' pembayaran ini?')) return;

            const formData = new FormData();
            formData.append('transaction_id', id);
            formData.append('verification_action', action);

            try {
                const res = await fetch('proses.php?action=verify_payment', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if(data.success) {
                    fetchPendingVerificationsPage();
                } else {
                    alert(data.message);
                }
            } catch(e) {
                alert('Terjadi kesalahan network');
            }
        }
    </script>
</body>
</html>

