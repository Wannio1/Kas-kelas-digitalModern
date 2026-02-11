// Cash Verification Modal Functions
function openCashPaymentModal() {
    const modal = document.getElementById('cashPaymentModal');
    if (modal) {
        modal.style.display = 'flex';
        // Focus on input
        setTimeout(() => document.getElementById('cashAmount').focus(), 100);
    }
}

function closeCashPaymentModal() {
    const modal = document.getElementById('cashPaymentModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('cashAmount').value = '';
    }
}

async function processCashPayment() {
    const amountInput = document.getElementById('cashAmount').value;
    const btn = document.getElementById('btnCashPay');

    if (!amountInput || amountInput.trim() === '' || amountInput <= 0) {
        alert('Mohon masukkan nominal yang valid.');
        return;
    }

    const amount = parseInt(amountInput);
    const originalContent = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

    try {
        const formData = new FormData();
        formData.append('amount', amount);

        const response = await fetch('proses.php?action=submit_cash_payment', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeCashPaymentModal();
            fetchTransactions(); // Refresh list
        } else {
            alert('Gagal: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim permintaan verifikasi.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

// Fetch Pending Cash Verifications for Treasurer
async function fetchPendingVerifications() {
    try {
        const response = await fetch('proses.php?action=get_pending_verifications');
        const data = await response.json();

        if (data.success && data.verifications) {
            renderPendingVerifications(data.verifications);
        }
    } catch (error) {
        console.error('Error fetching verifications:', error);
    }
}

// Verify Cash Payment (Approve or Reject)
async function verifyCashPayment(transactionId, action) {
    const actionText = action === 'approve' ? 'menyetujui' : 'menolak';

    if (!confirm(`Apakah Anda yakin akan ${actionText} pembayaran tunai ini?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('transaction_id', transactionId);
        formData.append('verification_action', action);

        const response = await fetch('proses.php?action=verify_cash_payment', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            fetchPendingVerifications(); // Refresh pending list
            fetchTransactions(); // Refresh main list
        } else {
            alert('Gagal: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan.');
    }
}

// Render Pending Verifications Table
function renderPendingVerifications(verifications) {
    const container = document.getElementById('pendingVerificationsContainer');
    if (!container) return;

    if (verifications.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted); text-align: center;">Tidak ada permintaan verifikasi pending.</p>';
        return;
    }

    let html = `
        <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Verifikasi Pembayaran Tunai</h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
    `;

    verifications.forEach(v => {
        const date = new Date(v.transaction_date).toLocaleString('id-ID');
        html += `
            <div class="card" style="padding: 1rem; margin-bottom: 0.75rem; border: 1px solid var(--border-subtle);">
                <div class="flex-between" style="margin-bottom: 0.75rem;">
                    <div>
                        <p style="color: var(--text-main); font-weight: 600; margin-bottom: 0.25rem;">${v.full_name}</p>
                        <p class="text-muted" style="font-size: 0.85rem;">${date}</p>
                    </div>
                    <p style="color: var(--primary); font-weight: 700; font-size: 1.1rem;">Rp ${parseFloat(v.amount).toLocaleString('id-ID')}</p>
                </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button onclick="verifyCashPayment(${v.id}, 'approve')" class="btn btn-primary" title="Terima" style="padding: 0.5rem;">
                            <i class="fa-solid fa-check"></i>
                        </button>
                        <button onclick="verifyCashPayment(${v.id}, 'reject')" class="btn btn-danger" title="Tolak" style="padding: 0.5rem;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                         <button onclick="viewProof('${v.proof_image}')" class="btn btn-secondary" title="Lihat Bukti" style="padding: 0.5rem; ${v.payment_method === 'cash' ? 'display:none;' : ''}">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

