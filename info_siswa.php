<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'koneksi.php';
$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

$schoolName = "Kas Kelas";
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
    <title>Daftar Siswa - <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .online-dot { height: 8px; width: 8px; background-color: var(--success); border-radius: 50%; display: inline-block; margin-right: 6px; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }
        .offline-dot { height: 8px; width: 8px; background-color: var(--text-muted); border-radius: 50%; display: inline-block; margin-right: 6px; }
    </style>
</head>
<body>
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="fa-solid fa-wallet" style="color: var(--primary);"></i>
            Kas Kelas <?php echo htmlspecialchars($schoolName); ?>
        </div>
        <div class="nav-menu">
            <!-- Empty menu for student list page, or add dashboard link here if preferred -->
        </div>
        
        <div class="nav-user">
            <a href="beranda.php" class="btn btn-secondary" style="font-size: 0.9rem;">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        
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
                <h1 class="text-gradient" style="font-size: 1.8rem;">Daftar Siswa</h1>
                <p>Status online dan informasi siswa.</p>
            </div>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Status Akun</th>
                            <th>Status Keuangan</th>
                        </tr>
                    </thead>
                    <tbody id="studentListBody">
                        <tr><td colspan="3" style="text-align:center; padding: 2rem;">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('proses.php?action=get_student_status')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('studentListBody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.students.length > 0) {
                        data.students.forEach(student => {
                            const isOnline = student.is_online;
                            const lastActive = student.last_active 
                                ? new Date(student.last_active).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) 
                                : '-';

                            const statusDisplay = isOnline 
                                ? `<span style="color: var(--success); font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; font-weight: 500;"><i class="fa-solid fa-wifi"></i> Online</span>` 
                                : `<span style="color: var(--text-muted); font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem;"><i class="fa-regular fa-clock"></i> ${lastActive}</span>`;
                            
                            // Financial Status
                            let finStatus = '';
                            if (student.status === 'Aman') {
                                finStatus = `<span class="badge badge-success" style="padding: 0.5rem 1rem;"><i class="fa-solid fa-check-circle"></i> Aman</span>`;
                            } else {
                                const arrears = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(student.arrears || 0);
                                finStatus = `<span class="badge badge-danger" style="padding: 0.5rem 1rem;"><i class="fa-solid fa-circle-exclamation"></i> Nunggak ${arrears}</span>`;
                            }

                            const row = `
                                <tr style="transition: background 0.2s;">
                                    <td style="padding: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div style="width: 45px; height: 45px; background: rgba(139, 92, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(139, 92, 246, 0.2);">
                                                <i class="fa-solid fa-user-graduate" style="color: var(--primary); font-size: 1.25rem;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 1rem; color: var(--text-main);">${student.name}</div>
                                                <div style="font-size: 0.85rem; color: var(--text-muted);">Siswa</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem;">${statusDisplay}</td>
                                    <td style="padding: 1rem;">${finStatus}</td>
                                </tr>
                            `;
                            tbody.insertAdjacentHTML('beforeend', row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 3rem; color: var(--text-muted);"><i class="fa-solid fa-users-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>Belum ada data siswa.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('studentListBody').innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 2rem; color: var(--danger);">Gagal memuat data.</td></tr>';
                });
        });
    </script>
</body>
</html>

