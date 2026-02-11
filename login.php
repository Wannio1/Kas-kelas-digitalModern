<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card animate-fade-in">
            <h1 class="auth-title">Kas Kelas</h1>
            <p style="margin-bottom: 2rem; color: var(--text-muted);">Sistem Pembayaran Kas Digital</p>
            


            <form method="POST" autocomplete="off" id="loginForm">
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Masukan username Anda" required autocomplete="new-password">
                </div>
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Masukan password Anda" required autocomplete="new-password">
                </div>
                
                <div class="form-group" style="text-align: left; display: flex; align-items: center;">
                    <input type="checkbox" id="remember" name="remember" style="margin-right: 0.5rem; accent-color: var(--primary);">
                    <label for="remember" style="font-size: 0.9rem; color: var(--text-muted); cursor: pointer;">Ingat Saya</label>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="padding: 1rem; margin-top: 1rem;">
                    Masuk Sekarang
                </button>
                
                <div id="errorMsg" style="color: var(--danger); margin-top: 1rem; font-size: 0.9rem; display: none;"></div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const errorMsg = document.getElementById('errorMsg');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            errorMsg.style.display = 'none';
            submitBtn.innerHTML = 'Memproses...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            
            try {
                const response = await fetch('cek_login.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'beranda.php';
                } else {
                    errorMsg.textContent = data.message;
                    errorMsg.style.display = 'block';
                    submitBtn.innerHTML = 'Masuk Sekarang';
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
            } catch (err) {
                console.error(err);
                errorMsg.textContent = 'Terjadi kesalahan jaringan atau server.';
                errorMsg.style.display = 'block';
                submitBtn.innerHTML = 'Masuk Sekarang';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        });
    </script>
</body>
</html>

