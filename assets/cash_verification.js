// Cash Verification Function for Students
async function submitCashVerification() {
    // Prompt user for amount
    const amountInput = prompt('Masukkan jumlah uang tunai yang dibayarkan (Rp):');

    if (!amountInput || amountInput.trim() === '') {
        alert('Nominal pembayaran harus diisi.');
        return;
    }

    const amount = parseInt(amountInput.replace(/\D/g, ''));

    if (isNaN(amount) || amount <= 0) {
        alert('Nominal tidak valid. Harap masukkan angka yang benar.');
        return;
    }

    if (!confirm(`Apakah Anda yakin akan mengajukan pembayaran tunai sebesar Rp ${amount.toLocaleString('id-ID')}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('amount', amount);

        const response = await fetch('api.php?action=submit_cash_payment', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeModal();
            fetchTransactions(); // Refresh list
        } else {
            alert('Gagal: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim permintaan verifikasi.');
    }
}

// Fetch Pending Cash Verifications for Treasurer
async function fetchPendingVerifications() {
    try {
        const response = await fetch('api.php?action=get_pending_verifications');
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

        const response = await fetch('api.php?action=verify_cash_payment', {
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
            <div style="background: rgba(255, 255, 255, 0.05); padding: 16px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                        <p style="color: var(--text-primary); font-weight: 700; margin-bottom: 4px;">${v.full_name}</p>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">${date}</p>
                    </div>
                    <p style="color: var(--primary); font-weight: 700; font-size: 1.1rem;">Rp ${parseFloat(v.amount).toLocaleString('id-ID')}</p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button onclick="verifyCashPayment(${v.id}, 'approve')" style="flex: 1; padding: 10px; background: linear-gradient(to right, #10b981, #059669); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">✅ Setujui</button>
                    <button onclick="verifyCashPayment(${v.id}, 'reject')" style="flex: 1; padding: 10px; background: linear-gradient(to right, #ef4444, #dc2626); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">❌ Tolak</button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}
