// Initial Load
document.addEventListener('DOMContentLoaded', () => {
    fetchTransactions();
    // Check for pending verifications every 10 seconds (for Bendahara)
    if (typeof USER_ROLE !== 'undefined' && USER_ROLE === 'bendahara') {
        fetchPendingVerifications();
        setInterval(fetchPendingVerifications, 10000);
    }

    // Refresh student list if modal open
    setInterval(() => {
        const modal = document.getElementById('studentListModal');
        if (modal && modal.style.display === 'flex') {
            fetchStudentList();
        }
    }, 15000);
});

// QRIS Payment Flow
function openEWallet(method) {
    let url = '';
    if (method === 'gopay') url = 'https://gopay.co.id';
    else if (method === 'dana') url = 'https://dana.id';
    else if (method === 'ovo') url = 'https://ovo.id';

    window.open(url, '_blank');

    // Switch to upload view
    document.getElementById('paymentMethods').style.display = 'none';
    document.getElementById('uploadProofSection').style.display = 'block';
    document.getElementById('selectedMethod').value = method;
}

function backToMethods() {
    document.getElementById('paymentMethods').style.display = 'block';
    document.getElementById('uploadProofSection').style.display = 'none';
}

// Handle Proof Submission
// Handle Proof Submission
const qrisForm = document.getElementById('qrisProofForm');
if (qrisForm) {
    qrisForm.onsubmit = async (e) => {
        e.preventDefault();
        const btn = qrisForm.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';
        btn.disabled = true;

        const formData = new FormData(e.target);

        try {
            const res = await fetch('proses.php?action=submit_qris_payment', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                closeModal();
                fetchTransactions();
                // Reset form
                e.target.reset();
                if (document.getElementById('previewContainer')) document.getElementById('previewContainer').style.display = 'none';
                if (document.getElementById('uploadPrompt')) document.getElementById('uploadPrompt').style.display = 'block';
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Gagal mengupload bukti');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };
}

function closeModal() {
    const modal = document.getElementById('qrisModal');
    if (modal) modal.style.display = 'none';
}

async function fetchTransactions() {
    try {
        const res = await fetch('proses.php?action=get_transactions');
        const data = await res.json();

        if (data.success) {
            // Update Stats
            if (document.getElementById('totalAmount')) {
                document.getElementById('totalAmount').innerText = data.total_collected;
            }
            if (document.getElementById('finalBalance')) {
                document.getElementById('finalBalance').innerText = data.final_balance;
            }

            // Render Income Table
            const tbody = document.querySelector('#transactionTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                if (data.transactions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem; color: var(--text-muted);">Belum ada data pemasukan atau pengeluaran.</td></tr>';
                } else {
                    data.transactions.forEach(t => {
                        const date = new Date(t.transaction_date).toLocaleDateString('id-ID', {
                            day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                        });
                        const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(t.amount);
                        const statusColor = t.status === 'paid' ? 'var(--success)' : 'var(--warning)';
                        const statusIcon = t.status === 'paid' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-regular fa-clock"></i>';

                        // For Treasurer: Show buttons to Approve/Reject if pending
                        let actionHtml = '';
                        if (USER_ROLE === 'bendahara' && t.status === 'pending') {
                            actionHtml = `
                    <div class="action-buttons">
                        <button onclick="updateStatus(${t.id}, 'paid', ${t.amount})" class="btn-approve" title="Terima"><i class="fa-solid fa-check"></i></button>
                        <button onclick="updateStatus(${t.id}, 'rejected')" class="btn-reject" title="Tolak"><i class="fa-solid fa-xmark"></i></button>
                    </div>`;
                        } else {
                            actionHtml = `<span style="color:${statusColor}; font-weight:500; display:flex; align-items:center; gap:4px;">${statusIcon} ${t.status === 'paid' ? 'Lunas' : 'Menunggu'}</span>`;
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                    <td style="padding: 1rem;">${date}</td>
                    <td style="padding: 1rem; font-weight: 600;">
                        <i class="fa-solid fa-user-circle" style="margin-right: 8px; color: var(--primary);"></i>${t.full_name}
                    </td>
                    <td style="padding: 1rem;">${amount}</td>
                    <td style="padding: 1rem;">${t.payment_method.toUpperCase()}</td>
                `;
                        tbody.appendChild(tr);
                    });
                }
            }

            // Render Expenses Table (For Everyone now)
            renderExpenseTable(data.expenses);

            // Treasurer specific: Pending Verifications
            if (USER_ROLE === 'bendahara' && typeof fetchPendingVerifications === 'function') {
                fetchPendingVerifications();
            }
        }
    } catch (error) {
        console.error('Error fetching transactions:', error);
    }
}

// Midtrans Payment Logic
async function processMidtransPayment() {
    const amount = document.getElementById('payAmount').value;
    const btn = document.getElementById('btnPayNow');

    if (!amount || amount <= 0) {
        alert('Masukkan nominal yang valid');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

    try {
        const formData = new FormData();
        formData.append('amount', amount);

        // 1. Get Snap Token from Backend
        const res = await fetch('proses.php?action=get_midtrans_token', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            // 2. Open Snap Popup
            window.snap.pay(data.token, {
                onSuccess: function (result) {
                    // 3. Record Payment on Backend
                    recordPayment(result, amount);
                },
                onPending: function (result) {
                    // QRIS/VA often return pending initially. We should record it.
                    recordPayment(result, amount);
                    alert('Pembayaran tertunda/menunggu pembayaran via aplikasi.');
                },
                onError: function (result) {
                    alert('Pembayaran gagal.');
                },
                onClose: function () {
                    btn.disabled = false;
                    btn.innerHTML = 'Bayar Sekarang';
                }
            });
        } else {
            alert('Gagal: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = 'Bayar Sekarang';
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan koneksi');
        btn.disabled = false;
        btn.innerHTML = 'Bayar Sekarang';
    }
}

async function recordPayment(result, amount) {
    try {
        const formData = new FormData();
        formData.append('order_id', result.order_id);
        formData.append('amount', amount);
        formData.append('status', result.transaction_status);
        formData.append('payment_type', result.payment_type);

        const res = await fetch('proses.php?action=record_midtrans_payment', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            alert('Pembayaran Berhasil! Terima kasih.');
            document.getElementById('paymentModal').style.display = 'none';
            document.getElementById('payAmount').value = '';
            document.getElementById('btnPayNow').disabled = false;
            document.getElementById('btnPayNow').innerHTML = 'Bayar Sekarang';
            fetchTransactions();
        } else {
            alert('Pembayaran berhasil di Midtrans, tapi gagal disimpan di database. Hubungi admin.');
        }
    } catch (e) {
        alert('Error network saving payment');
    }
}

// Student List Functions
async function fetchStudentList() {
    try {
        const res = await fetch('proses.php?action=get_student_status');
        const data = await res.json();

        if (data.success) {
            const tbody = document.getElementById('studentListBody');
            if (!tbody) return;
            tbody.innerHTML = '';

            data.students.forEach(s => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

                // Online/Offline Dot
                const dotClass = s.is_online ? 'online-dot' : 'offline-dot';
                const statusText = s.is_online ? 'Online' : 'Offline';
                const lastActive = s.last_active ? new Date(s.last_active).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-';

                // Financial Status
                const arrearsFormatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(s.arrears);
                let finStatus = '';
                if (s.status === 'Aman') {
                    finStatus = `<span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Aman</span>`;
                } else {
                    finStatus = `<span class="badge badge-danger"><i class="fa-solid fa-circle-exclamation"></i> Nunggak ${arrearsFormatted}</span>`;
                }

                // Last seen icon
                const lastSeenDisplay = s.is_online
                    ? `<span style="color: var(--success); font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem;"><i class="fa-solid fa-wifi"></i> Online</span>`
                    : `<span style="color: var(--text-muted); font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem;"><i class="fa-regular fa-clock"></i> ${lastActive}</span>`;

                tr.innerHTML = `
                    <td style="padding: 12px;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: rgba(139, 92, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(139, 92, 246, 0.2);">
                                <i class="fa-solid fa-user-graduate" style="color: var(--primary); font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.95rem;">${s.name}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Siswa</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 12px;">
                        ${lastSeenDisplay}
                    </td>
                    <td style="padding: 12px;">${finStatus}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (error) {
        console.error('Error fetching student list:', error);
    }
}

function openStudentListModal() {
    const modal = document.getElementById('studentListModal');
    if (modal) {
        modal.style.display = 'flex';
        fetchStudentList();
    }
}

function closeStudentListModal() {
    const modal = document.getElementById('studentListModal');
    if (modal) modal.style.display = 'none';
}

function renderExpenseTable(expenses) {
    const tbody = document.querySelector('#expenseTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!expenses || expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem; color: #94a3b8;">Belum ada pengeluaran.</td></tr>';
        return;
    }

    expenses.forEach(e => {
        const tr = document.createElement('tr');
        const date = new Date(e.expense_date).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(e.amount);

        // Check if we are on the expense management page (has edit buttons)
        // If we are on dashboard (role might be bendahara but table structure is different)
        // Dashboard has 3 columns, Expenses page has 4.
        // Simple check: does the header have 4 columns?
        const isManagementPage = document.querySelector('#expenseTable th:nth-child(4)') !== null;

        let actionHtml = '';
        if (isManagementPage) {
            actionHtml = `
                <td style="text-align: right;">
                    <button onclick="editExpense(${e.id})" style="background:none; border:none; color:#f59e0b; cursor:pointer; margin-right:10px;">✏️ Edit</button>
                    <button onclick="deleteExpense(${e.id})" style="background:none; border:none; color:#ef4444; cursor:pointer;">🗑️ Hapus</button>
                </td>
            `;
        }

        tr.innerHTML = `
            <td style="padding: 1rem;">${date}</td>
            <td style="color:white; font-weight:500; padding: 1rem;">${e.description}</td>
            <td style="color:#fca5a5; padding: 1rem;">-${amount}</td>
            ${actionHtml}
        `;
        tbody.appendChild(tr);
    });
}

function openExpenseModal(isEdit = false) {
    const modal = document.getElementById('expenseModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('modalTitle').innerText = isEdit ? 'Edit Pengeluaran' : 'Catat Pengeluaran';
        if (!isEdit) {
            document.getElementById('expenseId').value = '';
            document.getElementById('expenseDesc').value = '';
            document.getElementById('expenseAmount').value = '';
        }
    }
}

function closeExpenseModal() {
    const modal = document.getElementById('expenseModal');
    if (modal) modal.style.display = 'none';
}

async function editExpense(id) {
    try {
        const res = await fetch(`proses.php?action=get_expense_details&id=${id}`);
        const data = await res.json();
        if (data.success) {
            document.getElementById('expenseId').value = data.expense.id;
            document.getElementById('expenseDesc').value = data.expense.description;
            document.getElementById('expenseAmount').value = data.expense.amount;
            openExpenseModal(true);
        } else {
            alert('Gagal mengambil data');
        }
    } catch (e) {
        alert('Error network');
    }
}

async function deleteExpense(id) {
    if (!confirm('Hapus pengeluaran ini?')) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        const res = await fetch('proses.php?action=delete_expense', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            fetchTransactions();
        } else {
            alert(data.message);
        }
    } catch (e) {
        alert('Error network');
    }
}

async function submitExpense() {
    const id = document.getElementById('expenseId').value;
    const desc = document.getElementById('expenseDesc').value;
    const amount = document.getElementById('expenseAmount').value;

    if (!desc || !amount) {
        alert('Mohon lengkapi data!');
        return;
    }

    const formData = new FormData();
    formData.append('description', desc);
    formData.append('amount', amount);
    if (id) formData.append('id', id);

    const action = id ? 'update_expense' : 'add_expense';

    try {
        const response = await fetch(`proses.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(id ? 'Pengeluaran diperbarui!' : 'Pengeluaran berhasil dicatat!');
            closeExpenseModal();
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
            formattedAmount = '<span class="text-muted" style="font-size:0.9em; font-style:italic;">Menunggu Verifikasi</span>';
        }

        // Status Badge Class & Label
        let badgeClass = '';
        let statusLabel = '';
        let icon = '';

        if (t.status === 'paid') {
            badgeClass = 'badge badge-success';
            statusLabel = 'Diterima';
            icon = '<i class="fa-solid fa-circle-check"></i>';
        } else if (t.status === 'rejected') {
            badgeClass = 'badge badge-danger';
            statusLabel = 'Ditolak';
            icon = '<i class="fa-solid fa-circle-xmark"></i>';
        } else {
            badgeClass = 'badge badge-warning';
            statusLabel = 'Menunggu';
            icon = '<i class="fa-regular fa-clock"></i>';
        }

        let html = `
            <td>${date}</td>
            <td style="font-weight:600; color:var(--text-main);">
                <i class="fa-solid fa-user-circle" style="margin-right: 8px; color: var(--primary);"></i>${t.full_name}
            </td>
            <td>${formattedAmount}</td>
            <td><span class="${badgeClass}">${icon} ${statusLabel}</span></td>
        `;

        tr.innerHTML = html;
        tbody.appendChild(tr);
    });
}

