<?php
session_start();
require 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Helper function for Midtrans API Call
function getSnapToken($amount, $orderId, $customerDetails) {
    $params = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int)$amount, // Ensure INT
        ],
        'customer_details' => $customerDetails,
        'enabled_payments' => ['gopay', 'shopeepay', 'permata_va', 'bca_va', 'bni_va', 'bri_va', 'other_qris'],
        'expiry' => [
            'unit' => 'days',
            'duration' => 500
        ]
    ];

    $payload = json_encode($params);
    $serverKey = MIDTRANS_SERVER_KEY;
    $url = MIDTRANS_IS_PRODUCTION 
        ? 'https://app.midtrans.com/snap/v1/transactions' 
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    // Debug: Log Payload
    file_put_contents('midtrans_debug.log', "REQ: $payload\n", FILE_APPEND);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ]);

    $result = curl_exec($ch);
    file_put_contents('midtrans_debug.log', "RES: $result\n", FILE_APPEND); // Debug Log

    if (curl_errno($ch)) {
        return ['error' => curl_error($ch)];
    }
    curl_close($ch);

    return json_decode($result, true);
}

// Update Last Active
try {
    $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = :id");
    $stmt->execute(['id' => $userId]);
} catch (Exception $e) { /* Ignore error to keep app running */ }

try {
    if ($action === 'get_transactions') {
        // Transparency: Everyone sees ALL transactions now
        // BUT for 'murid', we might want to highlight THEIR transactions, or just show all.
        // User request: "riwayat pemasukan kas" -> Implies all income.
        
        $stmt = $pdo->query("
            SELECT t.*, u.full_name 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id 
            ORDER BY t.transaction_date DESC
        ");
        
        $transactions = $stmt->fetchAll();
        
        // Calculate total stats
        // Students now see Class Balance too
        $totalIncomeStmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'paid'");
        $totalIncome = $totalIncomeStmt->fetchColumn() ?: 0;

        // Everyone sees expenses
        $eStmt = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC");
        $expenses = $eStmt->fetchAll();
        
        $totalExpenseStmt = $pdo->query("SELECT SUM(amount) FROM expenses");
        $totalExpense = $totalExpenseStmt->fetchColumn() ?: 0;
        
        // Final Balance
        $finalBalance = $totalIncome - $totalExpense;

        echo json_encode([
            'success' => true, 
            'transactions' => $transactions,
            'expenses' => $expenses,
            'total_collected' => number_format($totalIncome, 0, ',', '.'),
            'total_expense' => number_format($totalExpense, 0, ',', '.'),
            'final_balance' => number_format($finalBalance, 0, ',', '.')
        ]);

    } elseif ($action === 'add_expense' && $role === 'bendahara') {
        $desc = $_POST['description'] ?? '';
        $amount = $_POST['amount'] ?? 0;

        if (!$desc || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, created_by) VALUES (:desc, :amount, :uid)");
        $stmt->execute(['desc' => $desc, 'amount' => $amount, 'uid' => $userId]);

        echo json_encode(['success' => true]);

    } elseif ($action === 'get_midtrans_token' && $role === 'murid') {
        $amount = $_POST['amount'] ?? 0;
        
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Nominal tidak valid']);
            exit;
        }

        // Get User Info
        $stmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $orderId = 'TRX-' . time() . '-' . $userId . '-' . rand(100, 999);
        
        $customerDetails = [
            'first_name' => $user['full_name'],
            'email' => $user['username'] . '@example.com', // Dummy email if not exists
            'phone' => '08123456789' // Dummy phone
        ];

        $snapResponse = getSnapToken($amount, $orderId, $customerDetails);

        if (isset($snapResponse['token'])) {
            echo json_encode(['success' => true, 'token' => $snapResponse['token'], 'order_id' => $orderId]);
        } else {
            error_log("Midtrans Error: " . print_r($snapResponse, true));
            echo json_encode(['success' => false, 'message' => 'Gagal membuat token pembayaran.']);
        }

    } elseif ($action === 'record_midtrans_payment' && $role === 'murid') {
        // Called by frontend after Snap success
        $orderId = $_POST['order_id'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $status = $_POST['status'] ?? 'pending';
        $paymentType = $_POST['payment_type'] ?? 'unknown';

        if (!$orderId || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        // We treat Midtrans success as 'paid' instantly
        // In production, you should double check with status_code from notification
        $dbStatus = ($status == 'success' || $status == 'settlement' || $status == 'capture') ? 'paid' : 'pending';

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, status, payment_method, verification_status) VALUES (:uid, :amount, :status, :pm, 'approved')");
        $stmt->execute(['uid' => $userId, 'amount' => $amount, 'status' => $dbStatus, 'pm' => 'midtrans-' . $paymentType]);

        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil direkam']);

    } elseif ($action === 'pay' && $role === 'murid') {
        // ... (keep existing pay logic if needed, but we are adding instant_pay below)
        // actually, let's just add the new action after this block or modify this one? 
        // User wants "langsung masuk total uang", so let's make a dedicated action for this simulation.
        
        $amount = $_POST['amount'] ?? 0;
        $paymentMethod = $_POST['payment_method'] ?? 'qris';
        
        if ($amount < 0) {
            echo json_encode(['success' => false, 'message' => 'Nominal tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, status, payment_method) VALUES (:uid, :amount, 'pending', :pm)");
        $stmt->execute(['uid' => $userId, 'amount' => $amount, 'pm' => $paymentMethod]);
        
        echo json_encode(['success' => true, 'message' => 'Pembayaran ' . strtoupper($paymentMethod) . ' berhasil dikirim, menunggu konfirmasi Bendahara.']);

    } elseif ($action === 'submit_qris_payment' && $role === 'murid') {
        $method = $_POST['method'] ?? 'qris';
        $amount = 10000; // Fixed simulation amount or from post
        
        if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
             echo json_encode(['success' => false, 'message' => 'Bukti pembayaran wajib diupload']);
             exit;
        }

        $uploadDir = 'uploads/proofs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileExt = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        $fileName = 'proof_' . $userId . '_' . time() . '.' . $fileExt;
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $uploadFile)) {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, status, payment_method, verification_status, proof_image) VALUES (:uid, :amount, 'pending', :pm, 'pending', :proof)");
            $stmt->execute(['uid' => $userId, 'amount' => $amount, 'pm' => $method, 'proof' => $uploadFile]);

            echo json_encode(['success' => true, 'message' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi Bendahara.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload bukti pembayaran']);
        }


    } elseif ($action === 'update_status' && $role === 'bendahara') {
        // Approve/Reject transaction
        $transId = $_POST['transaction_id'] ?? null;
        $status = $_POST['status'] ?? ''; // 'paid' or 'rejected'
        $finalAmount = $_POST['amount'] ?? null;

        if (!$transId || !in_array($status, ['paid', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        if ($status === 'paid' && $finalAmount !== null) {
            // Update status AND real amount
            $stmt = $pdo->prepare("UPDATE transactions SET status = :status, amount = :amount WHERE id = :id");
            $stmt->execute(['status' => $status, 'amount' => $finalAmount, 'id' => $transId]);
        } else {
            // Update status only
            $stmt = $pdo->prepare("UPDATE transactions SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $transId]);
        }

        echo json_encode(['success' => true]);


    } elseif ($action === 'count_pending' && $role === 'bendahara') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'");
        $count = $stmt->fetchColumn();
        echo json_encode(['success' => true, 'count' => $count]);

    } elseif ($action === 'submit_cash_payment' && $role === 'murid') {
        $amount = $_POST['amount'] ?? 0;
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Nominal tidak valid']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, status, payment_method, verification_status) VALUES (:uid, :amount, 'pending', 'cash', 'pending')");
        $stmt->execute(['uid' => $userId, 'amount' => $amount]);
        echo json_encode(['success' => true, 'message' => 'Permintaan verifikasi pembayaran tunai telah dikirim ke Bendahara.']);

    } elseif ($action === 'get_pending_verifications' && $role === 'bendahara') {
        // Fetch ALL pending verifications (Cash AND QRIS)
        // Only fetch if verification_status is pending. 
        // For 'cash', it is set to pending in submit_cash_payment.
        // For 'qris', it is set to pending in submit_qris_payment.
        // Note: 'status' for these is usually 'pending' too.
        $stmt = $pdo->query("
            SELECT t.*, u.full_name 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.verification_status = 'pending' 
            ORDER BY t.transaction_date DESC
        ");
        $pendingVerifications = $stmt->fetchAll();
        echo json_encode(['success' => true, 'verifications' => $pendingVerifications]);

    } elseif (($action === 'verify_payment' || $action === 'verify_cash_payment') && $role === 'bendahara') {
        // Support both action names for backward compatibility, though we'll update frontend to verify_payment
        $transactionId = $_POST['transaction_id'] ?? 0;
        $verificationAction = $_POST['verification_action'] ?? '';
        
        if (!$transactionId || !in_array($verificationAction, ['approve', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        $newStatus = ($verificationAction === 'approve') ? 'paid' : 'rejected';
        $verificationStatus = ($verificationAction === 'approve') ? 'approved' : 'rejected';
        
        // Removed `AND payment_method = 'cash'` to allow QRIS verification too
        $stmt = $pdo->prepare("UPDATE transactions SET status = :status, verification_status = :vstatus WHERE id = :tid");
        $stmt->execute(['status' => $newStatus, 'vstatus' => $verificationStatus, 'tid' => $transactionId]);
        
        $message = ($verificationAction === 'approve') ? 'Pembayaran berhasil disetujui.' : 'Pembayaran berhasil ditolak.';
        echo json_encode(['success' => true, 'message' => $message]);

    // Student Management Actions
    } elseif ($action === 'get_expense_details' && $role === 'bendahara') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        $expense = $stmt->fetch();
        if ($expense) {
            echo json_encode(['success' => true, 'expense' => $expense]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

    } elseif ($action === 'update_expense' && $role === 'bendahara') {
        $id = $_POST['id'] ?? 0;
        $desc = $_POST['description'] ?? '';
        $amount = $_POST['amount'] ?? 0;

        if (!$id || !$desc || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE expenses SET description = :desc, amount = :amount WHERE id = :id");
        if ($stmt->execute(['desc' => $desc, 'amount' => $amount, 'id' => $id])) {
            echo json_encode(['success' => true, 'message' => 'Pengeluaran diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui']);
        }

    } elseif ($action === 'delete_expense' && $role === 'bendahara') {
        $id = $_POST['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'Pengeluaran dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus']);
        }
    
    } elseif ($action === 'get_students' && $role === 'bendahara') {
        $stmt = $pdo->query("SELECT id, username, full_name, role, created_at FROM users WHERE role = 'murid' ORDER BY full_name ASC");
        $students = $stmt->fetchAll();
        echo json_encode(['success' => true, 'students' => $students]);

    } elseif ($action === 'create_student' && $role === 'bendahara') {
        $username = $_POST['username'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$username || !$fullName || !$password) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit;
        }

        // Check availability
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (:username, :password, :full_name, 'murid')");
        if ($stmt->execute(['username' => $username, 'password' => $hashedPassword, 'full_name' => $fullName])) {
            echo json_encode(['success' => true, 'message' => 'Siswa berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan siswa']);
        }

    } elseif ($action === 'delete_student' && $role === 'bendahara') {
        $studentId = $_POST['student_id'] ?? 0;
        if (!$studentId) {
            echo json_encode(['success' => false, 'message' => 'ID Siswa tidak valid']);
            exit;
        }

        // Prevent deleting self (just in case)
        if ($studentId == $userId) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'murid'");
        if ($stmt->execute(['id' => $studentId])) {
            echo json_encode(['success' => true, 'message' => 'Siswa berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus siswa']);
        }

    } elseif ($action === 'reset_password' && $role === 'bendahara') {
        $studentId = $_POST['student_id'] ?? 0;
        $newPassword = $_POST['new_password'] ?? '';

        if (!$studentId || !$newPassword) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id AND role = 'murid'");
        if ($stmt->execute(['password' => $hashedPassword, 'id' => $studentId])) {
            echo json_encode(['success' => true, 'message' => 'Password berhasil direset']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mereset password']);
        }
        exit;



    // Financial Recap & Arrears

        // Get Settings
        $settingsStmt = $pdo->query("SELECT * FROM app_settings");
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $monthlyDues = (int)($settings['monthly_dues'] ?? 10000);
        $startDateStr = $settings['semester_start_date'] ?? date('Y-m-01');
        
        // Calculate WEEKS passed since start date
        $start = new DateTime($startDateStr);
        $now = new DateTime();
        
        // Calculate difference in days
        $diff = $now->diff($start);
        $daysPassed = $diff->days;
        
        // If start date is in the future, weeks passed is 0
        if ($now < $start) {
            $weeksPassed = 0;
        } else {
            // Calculate weeks: floor(days / 7) + 1
            // Example: Day 0-6 = Week 1, Day 7-13 = Week 2
            $weeksPassed = floor($daysPassed / 7) + 1;
        }
        
        $targetAmount = $weeksPassed * $monthlyDues; // $monthlyDues is now treated as Weekly Dues

        // Get all students and their total paid
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, COALESCE(SUM(t.amount), 0) as total_paid 
            FROM users u 
            LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'paid' 
            WHERE u.role = 'murid' 
            GROUP BY u.id
        ");
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $recapData = [];
        foreach ($students as $s) {
            $paid = (int)$s['total_paid'];
            $arrears = $targetAmount - $paid;
            // logic: if arrears > 0, it means they missed payments. 
            // The "double next week" logic is inherent: 
            // Week 1 target 10k. Paid 0. Arrears 10k.
            // Week 2 target 20k. Paid 0. Arrears 20k.
            // So they owe 20k (which is 2 weeks worth). 
            // If they pay 10k in week 2, they still owe 10k.
            
            $status = ($arrears <= 0) ? 'Aman' : 'Nunggak';

            $recapData[] = [
                'id' => $s['id'],
                'full_name' => $s['full_name'],
                'total_paid' => $paid,
                'target' => $targetAmount,
                'arrears' => $arrears,
                'status' => $status
            ];
        }

        echo json_encode([
            'success' => true,
            'recap' => $recapData,
            'meta' => [
                'weeks_passed' => $weeksPassed, // Renamed from months_passed
                'weekly_dues' => $monthlyDues,  // Renamed logic
                'target_total' => $targetAmount,
                'start_date' => $startDateStr
            ]
        ]);

    } elseif ($action === 'update_settings' && $role === 'bendahara') {
        // ... (existing code)
        $schoolName = $_POST['school_name'] ?? '';
        $monthlyDues = $_POST['monthly_dues'] ?? '';
        $semesterStart = $_POST['semester_start_date'] ?? '';

        if (!$schoolName || !$monthlyDues || !$semesterStart) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit;
        }

        $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('school_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$schoolName, $schoolName]);
        $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('monthly_dues', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$monthlyDues, $monthlyDues]);
        $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('semester_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$semesterStart, $semesterStart]);

        echo json_encode(['success' => true, 'message' => 'Pengaturan disimpan']);

    // Student Status & Online List
    } elseif ($action === 'get_student_status') {
        // Get Settings for arrears calc
        $settings = $pdo->query("SELECT * FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $monthlyDues = (int)($settings['monthly_dues'] ?? 10000);
        $startDateStr = $settings['semester_start_date'] ?? date('Y-m-01');
        
        // Calculate WEEKS passed since start date
        $start = new DateTime($startDateStr);
        $now = new DateTime();
        
        $diff = $now->diff($start);
        $daysPassed = $diff->days;
        
        if ($now < $start) {
            $weeksPassed = 0;
        } else {
            $weeksPassed = floor($daysPassed / 7) + 1;
        }
        
        $targetAmount = $weeksPassed * $monthlyDues; // Weekly Dues

        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.last_active, COALESCE(SUM(t.amount), 0) as total_paid
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'paid'
            WHERE u.role = 'murid'
            GROUP BY u.id
            ORDER BY u.full_name ASC
        ");
        
        $students = $stmt->fetchAll();
        $list = [];
        
        foreach ($students as $s) {
            $lastActive = $s['last_active'] ? strtotime($s['last_active']) : 0;
            $isOnline = (time() - $lastActive) < 300; // Active in last 5 mins
            
            $paid = (int)$s['total_paid'];
            $arrears = $targetAmount - $paid;
            $status = ($arrears <= 0) ? 'Aman' : 'Nunggak';
            
            $list[] = [
                'name' => $s['full_name'],
                'is_online' => $isOnline,
                'last_active' => $s['last_active'], // formatted in JS
                'total_paid' => $paid,
                'arrears' => $arrears,
                'status' => $status
            ];
        }
        
        echo json_encode(['success' => true, 'students' => $list]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

