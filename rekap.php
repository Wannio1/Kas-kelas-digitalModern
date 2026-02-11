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
    <title>Rekap Keuangan - Kas Kelas <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
        }
        .meta-item { display: flex; flex-direction: column; }
        .meta-label { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.25rem; }
        .meta-value { font-size: 1.1rem; font-weight: 600; color: var(--text-main); }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #dbeafe;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        @media print {
            body { background: white; color: black; background-image: none; }
            .dashboard-nav, .btn, .info-box { display: none !important; }
            .container { max-width: 100%; margin: 0; padding: 20px; }
            table { border-collapse: collapse; width: 100%; border: 1px solid #ddd; }
            th, td { border: 1px solid #ddd; padding: 8px; color: black !important; }
            th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
            h1 { color: black !important; -webkit-text-fill-color: black !important; background: none !important; }
            .meta-grid { border: 1px solid #ddd; background: none; color: black; }
            .meta-value, .meta-label { color: black !important; }
            .card { border: none; box-shadow: none; background: none; }
            .table-container { border: none; box-shadow: none; background: none; border-radius: 0; }
        }
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
            <a href="rekap.php" class="nav-link active">
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
                <h1 class="text-gradient" style="font-size: 1.8rem;">Rekap Keuangan & Tunggakan</h1>
                <p>Laporan lengkap status pembayaran siswa.</p>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
        </div>

        <div class="info-box">
            <i class="fa-solid fa-circle-info" style="font-size: 1.2rem; margin-top: 2px;"></i>
            <div>
                <strong>Penjelasan Tunggakan Mingguan:</strong><br>
                Target tagihan dihitung berdasarkan jumlah minggu yang telah berjalan dikali iuran mingguan. Jika pembayaran kurang dari target, selisihnya menjadi tunggakan.
            </div>
        </div>

        <div class="meta-grid" id="metaContainer">
            <!-- Populated by JS -->
        </div>
        
        <div class="table-container">
            <div class="table-wrapper">
                <table id="recapTable">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Total Bayar</th>
                            <th>Target (Mingguan)</th>
                            <th>Tunggakan</th>
                            <th>Status</th>
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
        document.addEventListener('DOMContentLoaded', fetchRecap);

        async function fetchRecap() {
            try {
                const res = await fetch('proses.php?action=get_recap');
                const data = await res.json();
                
                if (data.success) {
                    // Update Meta
                    const meta = data.meta;
                    const metaHtml = `
                        <div class="meta-item">
                            <span class="meta-label">Mulai Semester</span>
                            <span class="meta-value">${meta.start_date}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Minggu Berjalan</span>
                            <span class="meta-value">Minggu ke-${meta.weeks_passed}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Iuran Per Minggu</span>
                            <span class="meta-value">Rp ${parseInt(meta.weekly_dues).toLocaleString('id-ID')}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Target Saat Ini</span>
                            <span class="meta-value" style="color: var(--primary);">Rp ${parseInt(meta.target_total).toLocaleString('id-ID')}</span>
                        </div>
                    `;
                    document.getElementById('metaContainer').innerHTML = metaHtml;

                    // Update Table
                    const tbody = document.querySelector('#recapTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.recap.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem;">Belum ada data siswa.</td></tr>';
                        return;
                    }

                    data.recap.forEach(r => {
                        const paid = parseInt(r.total_paid).toLocaleString('id-ID');
                        const target = parseInt(r.target).toLocaleString('id-ID');
                        const arrears = parseInt(r.arrears).toLocaleString('id-ID');
                        
                        let statusBadge = '';
                        let arrearsStyle = '';
                        
                        if (r.arrears <= 0) {
                            statusBadge = '<span class="badge badge-success">Aman</span>';
                        } else {
                            statusBadge = '<span class="badge badge-warning">Nunggak</span>';
                            arrearsStyle = 'color: var(--danger); font-weight: 700;';
                        }
                        
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="font-weight: 500;">${r.full_name}</td>
                            <td>Rp ${paid}</td>
                            <td>Rp ${target}</td>
                            <td style="${arrearsStyle}">Rp ${arrears}</td>
                            <td>${statusBadge}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (e) {
                console.error(e);
                alert('Gagal memuat data rekap');
            }
        }
    </script>
</body>
</html>

