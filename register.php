<?php
session_start();
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    // Validation
    if (empty($username) || empty($password) || empty($confirmPassword) || empty($fullName)) {
        $error = 'Semua field harus diisi.';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password dan konfirmasi password tidak cocok.';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        
        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan. Silakan pilih username lain.';
        } else {
            // Hash password and insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (:username, :password, :full_name, 'murid')");
            
            if ($stmt->execute(['username' => $username, 'password' => $hashedPassword, 'full_name' => $fullName])) {
                $success = 'Akun berhasil dibuat! Silakan login.';
                // Redirect to login after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0f0d;
            --bg-secondary: #111614;
            --bg-card: #141a17;
            --text-primary: #e2f0e9;
            --text-muted: #8b9c94;
            --primary: #4ade80;
            --primary-dark: #22c55e;
            --border: rgba(74, 222, 128, 0.15);
            --shadow: rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0a0f0d 0%, #1a2520 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
        }

        .register-container {
            background: var(--bg-card);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 60px var(--shadow);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .btn-register {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(74, 222, 128, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.1);
            color: var(--primary);
            border: 1px solid var(--border);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .password-strength {
            height: 4px;
            background: var(--bg-secondary);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: var(--primary); }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Daftar Akun Baru</h1>
            <p>Buat akun untuk mulai bayar kas kelas</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="full_name">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" placeholder="Masukkan nama lengkap" required 
                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Pilih username (min. 3 karakter)" required 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Buat password (min. 6 karakter)" required>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Ketik ulang password" required>
            </div>

            <button type="submit" class="btn-register">Daftar Sekarang</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="index.php">Login di sini</a>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            strengthBar.className = 'password-strength-bar';
            if (strength >= 8) {
                strengthBar.classList.add('strength-strong');
            } else if (strength >= 5) {
                strengthBar.classList.add('strength-medium');
            } else if (strength > 0) {
                strengthBar.classList.add('strength-weak');
            } else {
                strengthBar.style.width = '0%';
            }
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength += 3;
            if (password.length >= 10) strength += 2;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 2;
            return strength;
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
        });
    </script>
</body>
</html>
