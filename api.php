<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    if ($action === 'get_transactions') {
        if ($role === 'bendahara') {
            // Bendahara sees ALL transactions
            $stmt = $pdo->query("
                SELECT t.*, u.full_name 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                ORDER BY t.transaction_date DESC
            ");
        } else {
            // Murid sees ONLY their own transactions
            $stmt = $pdo->prepare("
                SELECT t.*, u.full_name 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.user_id = :uid 
                ORDER BY t.transaction_date DESC
            ");
            $stmt->execute(['uid' => $userId]);
        }
        
        $transactions = $stmt->fetchAll();
        
        // Calculate total stats
        $totalIncomeQuery = ($role === 'bendahara') 
            ? "SELECT SUM(amount) FROM transactions WHERE status = 'paid'"
            : "SELECT SUM(amount) FROM transactions WHERE status = 'paid' AND user_id = $userId";
        $totalIncome = $pdo->query($totalIncomeQuery)->fetchColumn() ?: 0;

        $totalExpense = 0;
        $expenses = [];

        if ($role === 'bendahara') {
            $eStmt = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC");
            $expenses = $eStmt->fetchAll();
            
            $totalExpenseStmt = $pdo->query("SELECT SUM(amount) FROM expenses");
            $totalExpense = $totalExpenseStmt->fetchColumn() ?: 0;
        }

        echo json_encode([
            'success' => true, 
            'transactions' => $transactions,
            'expenses' => $expenses,
            'total_collected' => number_format($totalIncome, 0, ',', '.'),
            'total_expense' => number_format($totalExpense, 0, ',', '.'),
            'final_balance' => number_format($totalIncome - $totalExpense, 0, ',', '.')
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

    } elseif ($action === 'pay' && $role === 'murid') {
        // Create new transaction
        $amount = $_POST['amount'] ?? 0;
        $paymentMethod = $_POST['payment_method'] ?? 'qris'; // Default to qris if not specified
        
        if ($amount < 0) {
            echo json_encode(['success' => false, 'message' => 'Nominal tidak valid']);
            exit;
        }

        // Validate allowed payment methods
        $allowedMethods = ['ovo', 'gopay', 'dana', 'qris'];
        if (!in_array($paymentMethod, $allowedMethods)) {
            $paymentMethod = 'qris';
        }

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, status, payment_method) VALUES (:uid, :amount, 'pending', :pm)");
        $stmt->execute(['uid' => $userId, 'amount' => $amount, 'pm' => $paymentMethod]);
        
        echo json_encode(['success' => true, 'message' => 'Pembayaran ' . strtoupper($paymentMethod) . ' berhasil dikirim, menunggu konfirmasi Bendahara.']);

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
        $stmt = $pdo->query("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.payment_method = 'cash' AND t.verification_status = 'pending' ORDER BY t.transaction_date DESC");
        $pendingVerifications = $stmt->fetchAll();
        echo json_encode(['success' => true, 'verifications' => $pendingVerifications]);

    } elseif ($action === 'verify_cash_payment' && $role === 'bendahara') {
        $transactionId = $_POST['transaction_id'] ?? 0;
        $verificationAction = $_POST['verification_action'] ?? '';
        if (!$transactionId || !in_array($verificationAction, ['approve', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }
        $newStatus = ($verificationAction === 'approve') ? 'paid' : 'rejected';
        $verificationStatus = ($verificationAction === 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE transactions SET status = :status, verification_status = :vstatus WHERE id = :tid AND payment_method = 'cash'");
        $stmt->execute(['status' => $newStatus, 'vstatus' => $verificationStatus, 'tid' => $transactionId]);
        $message = ($verificationAction === 'approve') ? 'Pembayaran tunai berhasil disetujui.' : 'Pembayaran tunai berhasil ditolak.';
        echo json_encode(['success' => true, 'message' => $message]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
