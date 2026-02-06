<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kas Kelas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Kas Kelas</h1>
            <p class="auth-subtitle">Sistem Pembayaran Kas Digital</p>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Masukan username Anda" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Masukan password Anda" required>
                </div>
                <button type="submit" class="btn-primary">Masuk Sekarang</button>
                <div id="errorMsg" class="error-message"></div>
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
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    errorMsg.textContent = data.message;
                    errorMsg.style.display = 'block';
                    submitBtn.innerHTML = 'Masuk Sekarang';
                    submitBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                errorMsg.textContent = 'Terjadi kesalahan jaringan atau server.';
                errorMsg.style.display = 'block';
                submitBtn.innerHTML = 'Masuk Sekarang';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
