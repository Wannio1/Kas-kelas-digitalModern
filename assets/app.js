document.addEventListener('DOMContentLoaded', () => {
    fetchTransactions();

    if (USER_ROLE === 'bendahara') {
        // Poll for notifications every 5 seconds
        setInterval(checkNotifications, 5000);
    }

    if (USER_ROLE === 'murid') {
        const payBtn = document.getElementById('payButton');
        const confirmBtn = document.getElementById('confirmPaymentBtn');
        const modal = document.getElementById('qrisModal');

        if (payBtn) {
            payBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
                // Trigger updateQr for the default checked item
                const checkedPm = document.querySelector('input[name="pmKey"]:checked');
                if (checkedPm) updateQr(checkedPm.value);
            });
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', processPayment);
        }
    }
});

let lastPendingCount = 0;

async function checkNotifications() {
    try {
        const response = await fetch('api.php?action=count_pending');
        const data = await response.json();

        if (data.success) {
            const count = parseInt(data.count);

            // If new pending transactions appear (count increased)
            if (count > lastPendingCount) {
                // Play notification sound
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'); // Simple beep
                audio.play().catch(e => console.log('Audio error (interaction needed):', e));

                // Show toast or alert
                showNotification(`Ada ${count} transaksi menunggu konfirmasi!`);

                // Refresh table automatically
                fetchTransactions();
            }
            lastPendingCount = count;
        }
    } catch (e) {
        console.error('Notification check failed', e);
    }
}

function showNotification(msg) {
    let notif = document.getElementById('toast-notification');
    if (!notif) {
        notif = document.createElement('div');
        notif.id = 'toast-notification';
        notif.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            z-index: 2000;
            font-weight: 600;
            animation: fadeIn 0.5s;
        `;
        document.body.appendChild(notif);
    }

    notif.textContent = msg;
    notif.style.display = 'block';

    setTimeout(() => {
        notif.style.display = 'none';
    }, 4000);
}

function closeModal() {
    const modal = document.getElementById('qrisModal');
    if (modal) modal.style.display = 'none';
}

function updateQr(method) {
    const qrImg = document.getElementById('qrImage');
    const qrInstruction = document.getElementById('qrInstruction');
    const deepLinkBtn = document.getElementById('deepLinkBtn');
    const qrSection = document.querySelector('.qr-section');
    const confirmBtn = document.getElementById('confirmPaymentBtn');

    // Handle cash payment
    if (method === 'cash') {
        if (qrSection) qrSection.style.display = 'none';
        if (deepLinkBtn) {
            deepLinkBtn.style.display = 'block';
            deepLinkBtn.href = '#';
            deepLinkBtn.onclick = (e) => {
                e.preventDefault();
                submitCashVerification();
            };
            deepLinkBtn.innerHTML = '<span style="margin-right:8px">✅</span>Minta Verifikasi Bendahara';
            deepLinkBtn.style.background = 'linear-gradient(to right, #10b981, #059669)';
        }
        // Update instruction for cash payment
        if (qrInstruction) {
            qrInstruction.style.display = 'block';
            qrInstruction.textContent = 'Pembayaran Tunai - Klik tombol di bawah untuk meminta verifikasi';
            qrInstruction.style.fontSize = '0.95rem';
            qrInstruction.style.padding = '1rem';
            qrInstruction.style.background = 'rgba(16, 185, 129, 0.1)';
            qrInstruction.style.borderRadius = '12px';
            qrInstruction.style.marginBottom = '1rem';
        }
        // Hide confirm payment button for cash (verification button replaces it)
        if (confirmBtn) confirmBtn.style.display = 'none';
        return;
    }

    // Show elements for e-wallet payments
    if (qrSection) qrSection.style.display = 'block';
    if (deepLinkBtn) {
        deepLinkBtn.style.display = 'block';
        deepLinkBtn.onclick = null; // Remove cash handler
    }
    if (confirmBtn) confirmBtn.style.display = 'block';
    if (qrInstruction) {
        qrInstruction.style.fontSize = '';
        qrInstruction.style.padding = '';
        qrInstruction.style.background = '';
        qrInstruction.style.borderRadius = '';
        qrInstruction.style.marginBottom = '';
    }

    // Update QR Image
    if (method === 'dana') {
        qrImg.src = 'assets/QRdana.jpg';
    } else {
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BayarKasKelas_${method.toUpperCase()}`;
    }
    qrInstruction.textContent = `Scan QR Code ${method.toUpperCase()} di bawah`;

    // Update Deep Link
    let scheme = '';
    let color = '';
    let appName = '';

    if (method === 'gopay') {
        scheme = 'gojek://'; // Common scheme for Gojek (GoPay)
        color = 'linear-gradient(to right, #00AA13, #00C016)';
        appName = 'GOPAY';
    } else if (method === 'ovo') {
        scheme = 'ovo://';
        color = 'linear-gradient(to right, #4c3494, #6d4cb8)';
        appName = 'OVO';
    } else if (method === 'dana') {
        scheme = 'dana://';
        color = 'linear-gradient(to right, #118ee9, #3ca5f2)';
        appName = 'DANA';
    }

    if (deepLinkBtn) {
        deepLinkBtn.href = scheme;
        deepLinkBtn.innerHTML = `<span style="margin-right:8px">📱</span>Buka Aplikasi ${appName}`;
        deepLinkBtn.style.background = color;
    }
}

async function fetchTransactions() {
    try {
        const response = await fetch('api.php?action=get_transactions');
        const data = await response.json();

        if (data.success) {
            const totalEl = document.getElementById('totalAmount');
            if (totalEl) totalEl.textContent = data.total_collected;

            // Treasurer Stats
            if (USER_ROLE === 'bendahara') {
                const expEl = document.getElementById('totalExpense');
                const balEl = document.getElementById('finalBalance');
                if (expEl) expEl.textContent = data.total_expense;
                if (balEl) balEl.textContent = data.final_balance;

                renderExpenseTable(data.expenses);

                // Fetch pending cash verifications
                if (typeof fetchPendingVerifications === 'function') {
                    fetchPendingVerifications();
                }
            }

            renderTable(data.transactions);
        }
    } catch (error) {
        console.error('Error fetching data:', error);
    }
}

function renderExpenseTable(expenses) {
    const tbody = document.querySelector('#expenseTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!expenses || expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 2rem; color: #94a3b8;">Belum ada pengeluaran.</td></tr>';
        return;
    }

    expenses.forEach(e => {
        const tr = document.createElement('tr');
        const date = new Date(e.expense_date).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(e.amount);

        tr.innerHTML = `
            <td>${date}</td>
            <td style="color:white; font-weight:500;">${e.description}</td>
            <td style="color:#fca5a5;">-${amount}</td>
        `;
        tbody.appendChild(tr);
    });
}

function openExpenseModal() {
    const modal = document.getElementById('expenseModal');
    if (modal) modal.style.display = 'flex';
}

function closeExpenseModal() {
    const modal = document.getElementById('expenseModal');
    if (modal) modal.style.display = 'none';
}

async function submitExpense() {
    const desc = document.getElementById('expenseDesc').value;
    const amount = document.getElementById('expenseAmount').value;

    if (!desc || !amount) {
        alert('Mohon lengkapi data!');
        return;
    }

    const formData = new FormData();
    formData.append('description', desc);
    formData.append('amount', amount);

    try {
        const response = await fetch('api.php?action=add_expense', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert('Pengeluaran berhasil dicatat!');
            closeExpenseModal();
            document.getElementById('expenseDesc').value = '';
            document.getElementById('expenseAmount').value = '';
            fetchTransactions();
        } else {
            alert(data.message || 'Gagal menyimpan');
        }
    } catch (e) {
        alert('Terjadi kesalahan');
    }
}

function renderTable(transactions) {
    const tbody = document.querySelector('#transactionTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 2rem; color: #94a3b8;">Belum ada data transaksi.</td></tr>';
        return;
    }

    transactions.forEach(t => {
        const tr = document.createElement('tr');

        // Format Date
        const date = new Date(t.transaction_date).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        // Format Currency
        let formattedAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(t.amount);

        // Show placeholder for pending/zero amounts
        if (t.status === 'pending' && t.amount == 0) {
            formattedAmount = '<span style="color:var(--text-secondary); font-size:0.9em; font-style:italic;">Menunggu Verifikasi</span>';
        }

        // Status Badge Class
        let statusClass = '';
        if (t.status === 'paid') statusClass = 'status-paid';
        else if (t.status === 'pending') statusClass = 'status-pending';
        else statusClass = 'status-rejected';

        // Translate Status
        const statusLabel = t.status === 'paid' ? 'Selesai' : (t.status === 'pending' ? 'Menunggu' : 'Ditolak');

        let html = `
            <td>${date}</td>
            <td style="font-weight:600; color:var(--text-primary);">${t.full_name}</td>
            <td>${formattedAmount}</td>
            <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
        `;

        if (USER_ROLE === 'bendahara') {
            if (t.status === 'pending') {
                html += `
                    <td>
                        <div class="action-group">
                            <button onclick="updateStatus(${t.id}, 'paid')" class="btn-action btn-approve" title="Terima">✓</button>
                            <button onclick="updateStatus(${t.id}, 'rejected')" class="btn-action btn-reject" title="Tolak">✕</button>
                        </div>
                    </td>
                `;
            } else {
                html += `<td>-</td>`;
            }
        }

        tr.innerHTML = html;
        tbody.appendChild(tr);
    });
}

// ... existing code ...
