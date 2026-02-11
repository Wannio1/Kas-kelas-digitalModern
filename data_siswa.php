<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: beranda.php");
    exit;
}

require 'koneksi.php';

$fullName = $_SESSION['full_name'];
$role = $_SESSION['role'];

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
    <title>Manajemen Siswa - Kas Kelas <?php echo htmlspecialchars($schoolName); ?></title>
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
            <a href="verifikasi.php" class="nav-link">
                <i class="fa-solid fa-circle-check"></i> Verifikasi
            </a>
            <a href="data_siswa.php" class="nav-link active">
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
        <div class="flex-between mb-4">
            <div>
                <h1 class="text-gradient" style="font-size: 1.8rem;">Manajemen Siswa</h1>
                <p>Kelola data siswa dan reset password jika diperlukan.</p>
            </div>
            <button onclick="openAddModal()" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Siswa
            </button>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Terdaftar Sejak</th>
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

    <!-- Add/Edit Modal -->
    <div id="studentModal" class="modal-overlay" style="
        position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); 
        display: none; align-items: center; justify-content: center; z-index: 1000;
    ">
        <div class="card" style="max-width: 400px; width: 90%; padding: 2rem;">
            <div class="flex-between mb-4">
                <h2 style="font-size: 1.25rem;">Tambah Siswa Baru</h2>
                <button onclick="closeAddModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="addStudentForm">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="full_name" class="form-input" required placeholder="Contoh: Budi Santoso">
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required placeholder="Contoh: budi123">
                </div>
                <div class="form-group">
                    <label class="form-label">Password Awal</label>
                    <input type="text" name="password" class="form-input" value="siswa123" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Simpan Siswa</button>
            </form>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div id="resetModal" class="modal-overlay" style="
        position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); 
        display: none; align-items: center; justify-content: center; z-index: 1000;
    ">
        <div class="card" style="max-width: 400px; width: 90%; padding: 2rem;">
            <div class="flex-between mb-4">
                <h2 style="font-size: 1.25rem;">Reset Password</h2>
                <button onclick="closeResetModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <p style="margin-bottom: 1.5rem; font-size: 0.9rem;">Reset password untuk <span id="resetTargetName" style="color: var(--primary); font-weight: 600;"></span>?</p>
            <form id="resetPasswordForm">
                <input type="hidden" id="resetStudentId" name="student_id">
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="text" name="new_password" class="form-input" value="siswa123" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', fetchStudents);

        function openAddModal() { document.getElementById('studentModal').style.display = 'flex'; }
        function closeAddModal() { document.getElementById('studentModal').style.display = 'none'; }
        
        function openResetModal(id, name) { 
            document.getElementById('resetStudentId').value = id;
            document.getElementById('resetTargetName').textContent = name;
            document.getElementById('resetModal').style.display = 'flex'; 
        }
        function closeResetModal() { document.getElementById('resetModal').style.display = 'none'; }

        async function fetchStudents() {
            try {
                const res = await fetch('proses.php?action=get_students');
                const data = await res.json();
                if (data.success) {
                    const tbody = document.querySelector('#studentsTable tbody');
                    tbody.innerHTML = '';
                    if (data.students.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem;">Belum ada siswa.</td></tr>';
                        return;
                    }
                    data.students.forEach(s => {
                        const date = new Date(s.created_at).toLocaleDateString('id-ID');
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="font-weight: 500;">${s.full_name}</td>
                            <td style="color: var(--text-muted);">${s.username}</td>
                            <td>${date}</td>
                            <td style="text-align: right;">
                                <button onclick="openResetModal(${s.id}, '${s.full_name}')" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; margin-right: 0.5rem;">Reset</button>
                                <button onclick="deleteStudent(${s.id}, '${s.full_name}')" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Hapus</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (e) {
                console.error(e);
            }
        }

        document.getElementById('addStudentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('proses.php?action=create_student', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    closeAddModal();
                    e.target.reset();
                    fetchStudents();
                } else {
                    alert(data.message);
                }
            } catch (e) { alert('Error network'); }
        });

        document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('proses.php?action=reset_password', { method: 'POST', body: formData });
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert(data.message);
                        closeResetModal();
                        e.target.reset();
                    } else {
                        alert(data.message);
                    }
                } catch (err) {
                    alert('Terjadi kesalahan server.');
                }
            } catch (e) { alert('Error network'); }
        });

        async function deleteStudent(id, name) {
            if (!confirm(`Yakin ingin menghapus siswa "${name}"? Semua data transaksi akan ikut terhapus.`)) return;
            const formData = new FormData();
            formData.append('student_id', id);
            try {
                const res = await fetch('proses.php?action=delete_student', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    fetchStudents();
                } else {
                    alert(data.message);
                }
            } catch (e) { alert('Error network'); }
        }
    </script>
</body>
</html>

