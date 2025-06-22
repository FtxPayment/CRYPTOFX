<?php
// File: admin_dashboard.php
require_once 'config.php';
session_start();

// Periksa apakah user sudah login dan apakah dia admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php'); // Redirect ke halaman login jika belum atau bukan admin
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Panel Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        :root {
            --primary-bg: #1a1a2e; /* Dark background */
            --secondary-bg: #16213e; /* Slightly lighter dark background */
            --accent-color: #e94560; /* Reddish accent */
            --text-color: #e0e0e0; /* Light text */
            --heading-color: #ffffff; /* White for headings */
            --card-bg: #0f3460; /* Dark blue for sections */
            --border-color: #0c2d50; /* Darker blue border */
            --button-active: #007bff; /* Blue for active state/ACC */
            --button-hover: #0056b3;
            --button-danger: #dc3545; /* Red for suspend/reject */
            --button-danger-hover: #c82333;
            --button-neutral: #6c757d; /* Grey for neutral/default */
            --button-neutral-hover: #5a6268;
            --admin-warning-bg: #8d3a01; /* Dark orange/brown for admin warning */
            --admin-warning-text: #ffd790; /* Light orange text */
            --table-header-bg: #0b2241; /* Dark blue for table headers */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        /* Admin Warning Header */
        .admin-warning {
            background-color: var(--admin-warning-bg);
            color: var(--admin-warning-text);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Main Dashboard Content */
        .dashboard-content {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            flex-grow: 1;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        .tab-button {
            background-color: var(--card-bg);
            color: var(--text-color);
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button:hover {
            background-color: var(--button-neutral-hover);
            transform: translateY(-2px);
        }

        .tab-button.active {
            background-color: var(--button-active);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .tab-button.active:hover {
             background-color: var(--button-hover);
        }

        /* Tab Content */
        .tab-content {
            padding-top: 10px;
        }

        .tab-content h3 {
            font-size: 2em;
            color: var(--heading-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Styling */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 0.95em;
        }

        .data-table thead {
            background-color: var(--table-header-bg);
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .data-table th {
            color: var(--heading-color);
            font-weight: bold;
            white-space: nowrap; /* Prevent headers from wrapping too much */
        }

        .data-table tbody tr:hover {
            background-color: var(--card-bg); /* Highlight row on hover */
        }

        .data-table .action-buttons button {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s ease;
            margin-right: 5px;
            white-space: nowrap; /* Prevent button text from wrapping */
        }

        .data-table .action-buttons button.acc-btn {
            background-color: var(--button-active);
            color: white;
        }
        .data-table .action-buttons button.acc-btn:hover {
            background-color: var(--button-hover);
        }

        .data-table .action-buttons button.reject-btn,
        .data-table .action-buttons button.suspend-btn {
            background-color: var(--button-danger);
            color: white;
        }
        .data-table .action-buttons button.reject-btn:hover,
        .data-table .action-buttons button.suspend-btn:hover {
            background-color: var(--button-danger-hover);
        }

        .data-table .status-pending { color: var(--accent-color); font-weight: bold; } /* Red for pending */
        .data-table .status-approved { color: #28a745; font-weight: bold; } /* Green for approved */
        .data-table .status-rejected { color: var(--button-danger); font-weight: bold; } /* Red for rejected */
        .data-table .status-suspended { color: var(--button-danger); font-weight: bold; } /* Red for suspended */
        .data-table .status-active { color: #28a745; font-weight: bold; } /* Green for active */

        /* No Data Message */
        .no-data {
            text-align: center;
            font-style: italic;
            color: var(--text-color);
            padding: 20px;
        }

        /* Back Button */
        .back-button {
            background-color: var(--button-neutral);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            margin-top: 30px;
            margin-bottom: 20px;
            align-self: flex-start;
        }

        .back-button:hover {
            background-color: var(--button-neutral-hover);
            transform: translateY(-3px);
        }
        .back-button i {
            font-size: 1.2em;
        }

        /* Footer */
        .footer {
            background-color: var(--secondary-bg);
            color: var(--text-color);
            text-align: center;
            padding: 30px 20px;
            border-top: 2px solid var(--border-color);
            font-size: 0.9em;
            margin-top: auto;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .data-table {
                font-size: 0.9em;
            }
            .data-table th, .data-table td {
                padding: 10px 12px;
            }
            .data-table .action-buttons button {
                padding: 5px 10px;
                font-size: 0.85em;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .admin-warning {
                font-size: 1em;
                padding: 12px 15px;
            }
            .dashboard-content {
                padding: 20px;
            }
            .tab-navigation {
                flex-direction: column; /* Stack tabs vertically */
                align-items: stretch; /* Make tabs full width */
            }
            .tab-button {
                width: 100%;
                justify-content: center;
            }
            .tab-content h3 {
                font-size: 1.8em;
            }
            /* Make tables scrollable horizontally on small screens */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            }
            .data-table {
                min-width: 600px; /* Ensure table content doesn't shrink too much */
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 576px) {
            .admin-warning {
                font-size: 0.9em;
            }
            .tab-content h3 {
                font-size: 1.5em;
            }
            .data-table {
                font-size: 0.85em;
            }
            .data-table th, .data-table td {
                padding: 8px 10px;
            }
            .data-table .action-buttons button {
                padding: 4px 8px;
                font-size: 0.8em;
                margin-right: 3px;
            }
            .back-button {
                padding: 8px 15px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="admin-warning">
            <i class="fas fa-exclamation-triangle"></i> Anda sedang mengakses panel admin. Harap gunakan dengan bijak.
        </div>

        <div class="dashboard-content">
            <div class="tab-navigation" role="tablist">
                <button class="tab-button active" id="tabUsers" data-tab="users" role="tab" aria-selected="true"><i class="fas fa-users"></i> User</button>
                <button class="tab-button" id="tabInvestments" data-tab="investments" role="tab" aria-selected="false"><i class="fas fa-chart-line"></i> Investasi</button>
                <button class="tab-button" id="tabLoans" data-tab="loans" role="tab" aria-selected="false"><i class="fas fa-hand-holding-usd"></i> Pinjaman</button>
                <button class="tab-button" id="tabSwaps" data-tab="swaps" role="tab" aria-selected="false"><i class="fas fa-exchange-alt"></i> Swap</button>
                <button class="tab-button" id="tabTopups" data-tab="topups" role="tab" aria-selected="false"><i class="fas fa-plus-circle"></i> TopUp</button>
                <button class="tab-button" id="tabWithdrawals" data-tab="withdrawals" role="tab" aria-selected="false"><i class="fas fa-money-check-alt"></i> Penarikan</button>
                <button class="tab-button" id="tabAirdrops" data-tab="airdrops" role="tab" aria-selected="false"><i class="fas fa-gift"></i> Airdrop</button>
            </div>

            <div class="tab-content" id="contentArea">
                </div>
        </div>

        <a href="logout.php" class="back-button">
            <i class="fas fa-sign-out-alt"></i> Logout Admin
        </a>
    </div>

    <footer class="footer">
        <p>&copy; 2025 CRYPTOFX. Hak cipta dilindungi undang-undang.</p>
    </footer>

    <script>
        const REFRESH_INTERVAL_MS = 3000;

        const tabButtons = document.querySelectorAll('.tab-button');
        const contentArea = document.getElementById('contentArea');

        let activeTab = 'users'; // Default tab

        // Helper functions from config.php (re-defined for client-side JS)
        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }
        function formatCfx(amount) {
            return parseFloat(amount).toFixed(5) + ' CFX';
        }

        // --- Fetch & Render Functions ---
        async function fetchData(endpoint) {
            try {
                const response = await fetch(endpoint);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Failed to fetch data:', error);
                contentArea.innerHTML = `<p class="no-data error">Gagal memuat data. ${error.message}</p>`;
                return null;
            }
        }

        async function renderUsersTab() {
            const data = await fetchData('admin_data_api.php?data=users');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-users"></i> Data Pengguna</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Saldo Utama</th>
                            <th>Saldo Investasi</th>
                            <th>Saldo Kripto</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="7" class="no-data">Tidak ada data pengguna.</td></tr>`;
            } else {
                data.forEach(user => {
                    const statusClass = user.isSuspended ? 'status-suspended' : 'status-active';
                    const statusText = user.isSuspended ? 'Suspended' : 'Aktif';
                    const suspendBtnText = user.isSuspended ? 'Buka Suspend' : 'Suspend';
                    const suspendBtnClass = user.isSuspended ? 'acc-btn' : 'suspend-btn';
                    tableHtml += `
                        <tr>
                            <td>${user.fullName}</td>
                            <td>${user.email}</td>
                            <td>${formatRupiah(user.mainBalance)}</td>
                            <td>${formatRupiah(user.investmentBalance)}</td>
                            <td>${formatCfx(user.cryptoBalance)}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td class="action-buttons">
                                <button class="${suspendBtnClass}" onclick="sendAdminAction('toggle_suspend_user', '${user.id}')">${suspendBtnText}</button>
                            </td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        async function renderInvestmentsTab() {
            const data = await fetchData('admin_data_api.php?data=investments');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-chart-line"></i> Data Investasi</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email Pengguna</th>
                            <th>Jumlah Investasi Awal</th>
                            <th>Saldo Investasi Saat Ini</th>
                            <th>Status</th>
                            <th>Mulai Sejak</th>
                            <th>Terakhir Update</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="6" class="no-data">Tidak ada data investasi.</td></tr>`;
            } else {
                data.forEach(item => {
                    const statusText = (item.investmentStartTime > 0) ? 'Aktif' : 'Tidak Aktif';
                    const statusClass = (item.investmentStartTime > 0) ? 'status-active' : 'status-rejected'; // Using rejected for inactive
                    tableHtml += `
                        <tr>
                            <td>${item.email}</td>
                            <td>${formatRupiah(item.initialInvestmentAmount)}</td>
                            <td>${formatRupiah(item.currentInvestmentBalance)}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>${item.investmentStartTime ? new Date(item.investmentStartTime * 1000).toLocaleString() : '-'}</td>
                            <td>${item.investmentLastUpdate ? new Date(item.investmentLastUpdate * 1000).toLocaleString() : '-'}</td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        async function renderLoansTab() {
            const data = await fetchData('admin_data_api.php?data=loans');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-hand-holding-usd"></i> Pengajuan Pinjaman</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email Pengguna</th>
                            <th>Jumlah Pinjaman</th>
                            <th>Tenor</th>
                            <th>Tujuan</th>
                            <th>Status</th>
                            <th>Tgl. Pengajuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="7" class="no-data">Tidak ada pengajuan pinjaman.</td></tr>`;
            } else {
                data.forEach(loan => {
                    const statusClass = `status-${loan.status}`;
                    const statusText = loan.status.charAt(0).toUpperCase() + loan.status.slice(1);
                    tableHtml += `
                        <tr>
                            <td>${loan.email}</td>
                            <td>${formatRupiah(loan.amount)}</td>
                            <td>${loan.tenor} Bulan</td>
                            <td>${loan.purpose}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>${new Date(loan.requestDate).toLocaleString()}</td>
                            <td class="action-buttons">
                                ${loan.status === 'pending' ? `
                                    <button class="acc-btn" onclick="sendAdminAction('approve_loan', '${loan.id}')">ACC</button>
                                    <button class="reject-btn" onclick="sendAdminAction('reject_loan', '${loan.id}')">Tolak</button>
                                ` : `
                                    <button disabled class="${loan.status === 'approved' ? 'acc-btn' : 'reject-btn'}">${statusText}</button>
                                `}
                            </td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        async function renderSwapsTab() {
            const data = await fetchData('admin_data_api.php?data=swaps');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-exchange-alt"></i> Riwayat Swap</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email Pengguna</th>
                            <th>Dari Token</th>
                            <th>Jumlah Dari</th>
                            <th>Ke Token</th>
                            <th>Jumlah Ke</th>
                            <th>Tanggal Swap</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="6" class="no-data">Tidak ada riwayat swap.</td></tr>`;
            } else {
                data.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.email}</td>
                            <td>${item.fromToken}</td>
                            <td>${item.amountFrom}</td>
                            <td>${item.toToken}</td>
                            <td>${formatRupiah(item.amountTo)}</td>
                            <td>${new Date(item.date).toLocaleString()}</td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        async function renderTopupsTab() {
            const data = await fetchData('admin_data_api.php?data=topups');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-plus-circle"></i> Permintaan Top Up</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email Pengguna</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Tgl. Pengajuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="6" class="no-data">Tidak ada permintaan top up.</td></tr>`;
            } else {
                data.forEach(topup => {
                    const statusClass = `status-${topup.status}`;
                    const statusText = topup.status.charAt(0).toUpperCase() + topup.status.slice(1);
                    tableHtml += `
                        <tr>
                            <td>${topup.email}</td>
                            <td>${formatRupiah(topup.nominal)}</td>
                            <td>${topup.method}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>${new Date(topup.requestDate).toLocaleString()}</td>
                            <td class="action-buttons">
                                ${topup.status === 'pending' ? `
                                    <button class="acc-btn" onclick="sendAdminAction('approve_topup', '${topup.id}')">ACC</button>
                                    <button class="reject-btn" onclick="sendAdminAction('reject_topup', '${topup.id}')">Tolak</button>
                                ` : `
                                    <button disabled class="${topup.status === 'approved' ? 'acc-btn' : 'reject-btn'}">${statusText}</button>
                                `}
                            </td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        async function renderWithdrawalsTab() {
            const data = await fetchData('admin_data_api.php?data=withdrawals');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-money-check-alt"></i> Permintaan Penarikan</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email Pengguna</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>No. Rekening/HP</th>
                            <th>Nama Pemilik</th>
                            <th>Total Diterima</th>
                            <th>Status</th>
                            <th>Tgl. Pengajuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="9" class="no-data">Tidak ada permintaan penarikan.</td></tr>`;
            } else {
                data.forEach(withdrawal => {
                    const statusClass = `status-${withdrawal.status}`;
                    const statusText = withdrawal.status.charAt(0).toUpperCase() + withdrawal.status.slice(1);
                    tableHtml += `
                        <tr>
                            <td>${withdrawal.email}</td>
                            <td>${formatRupiah(withdrawal.nominal)}</td>
                            <td>${withdrawal.method}</td>
                            <td>${withdrawal.accountNumber}</td>
                            <td>${withdrawal.accountName}</td>
                            <td>${formatRupiah(withdrawal.totalReceived)}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>${new Date(withdrawal.requestDate).toLocaleString()}</td>
                            <td class="action-buttons">
                                ${withdrawal.status === 'pending' ? `
                                    <button class="acc-btn" onclick="sendAdminAction('approve_withdrawal', '${withdrawal.id}')">ACC</button>
                                    <button class="reject-btn" onclick="sendAdminAction('reject_withdrawal', '${withdrawal.id}')">Tolak</button>
                                ` : `
                                    <button disabled class="${withdrawal.status === 'approved' ? 'acc-btn' : 'reject-btn'}">${statusText}</button>
                                `}
                            </td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        async function renderAirdropsTab() {
            const data = await fetchData('admin_data_api.php?data=airdrops');
            if (!data) return;

            let tableHtml = `
                <h3><i class="fas fa-gift"></i> Klaim Airdrop</h3>
                <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email Pengguna</th>
                            <th>Jumlah CFX</th>
                            <th>Tgl. Klaim</th>
                            <th>Status</th>
                            <th>Screenshot</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.length === 0) {
                tableHtml += `<tr><td colspan="6" class="no-data">Tidak ada klaim Airdrop.</td></tr>`;
            } else {
                data.forEach(claim => {
                    const statusClass = `status-${claim.status}`;
                    const statusText = claim.status.charAt(0).toUpperCase() + claim.status.slice(1);
                    tableHtml += `
                        <tr>
                            <td>${claim.email}</td>
                            <td>${formatCfx(claim.amountCFX)}</td>
                            <td>${new Date(claim.claimDate).toLocaleString()}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>
                                ${claim.screenshots && claim.screenshots.length > 0 ?
                                    claim.screenshots.map((s, idx) => `<a href="popo/airdrop_screenshots/${s}" target="_blank">SS ${idx+1}</a>`).join(', ')
                                    : 'N/A'}
                            </td>
                            <td class="action-buttons">
                                ${claim.status === 'pending' ? `
                                    <button class="acc-btn" onclick="sendAdminAction('approve_airdrop', '${claim.id}', '${claim.userId}', ${claim.amountCFX})">ACC</button>
                                    <button class="reject-btn" onclick="sendAdminAction('reject_airdrop', '${claim.id}')">Tolak</button>
                                ` : `
                                    <button disabled class="${claim.status === 'approved' ? 'acc-btn' : 'reject-btn'}">${statusText}</button>
                                `}
                            </td>
                        </tr>
                    `;
                });
            }
            tableHtml += `</tbody></table></div>`;
            contentArea.innerHTML = tableHtml;
        }

        // --- Admin Actions ---
        async function sendAdminAction(action, itemId, userId = null, amount = 0) {
            if (!confirm(`Yakin ingin melakukan aksi '${action.replace('_', ' ').toUpperCase()}' pada item ini?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', action);
            formData.append('itemId', itemId);
            if (userId) formData.append('userId', userId); // For Airdrop, suspend user
            if (amount) formData.append('amount', amount); // For Airdrop, topup user CFX balance

            try {
                const response = await fetch('admin_action_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                alert(result.message);
                if (result.success) {
                    refreshDashboardData(); // Refresh current tab after action
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses aksi admin. Silakan coba lagi.');
            }
        }

        // --- Main Dashboard Logic ---
        function activateTab(tabName) {
            tabButtons.forEach(button => button.classList.remove('active'));
            const selectedButton = document.querySelector(`.tab-button[data-tab="${tabName}"]`);
            if (selectedButton) {
                selectedButton.classList.add('active');
            }
            activeTab = tabName;
            renderActiveTabContent();
        }

        function renderActiveTabContent() {
            switch (activeTab) {
                case 'users':
                    renderUsersTab();
                    break;
                case 'investments':
                    renderInvestmentsTab();
                    break;
                case 'loans':
                    renderLoansTab();
                    break;
                case 'swaps':
                    renderSwapsTab();
                    break;
                case 'topups':
                    renderTopupsTab();
                    break;
                case 'withdrawals':
                    renderWithdrawalsTab();
                    break;
                case 'airdrops':
                    renderAirdropsTab();
                    break;
                default:
                    contentArea.innerHTML = `<p class="no-data">Pilih menu untuk melihat data.</p>`;
            }
        }

        function refreshDashboardData() {
            renderActiveTabContent(); // Simply re-render current tab
        }

        // --- Initialization ---
        document.addEventListener('DOMContentLoaded', () => {
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    activateTab(button.dataset.tab);
                });
            });

            activateTab('users'); // Start with Users tab active

            // Set up auto-refresh
            setInterval(refreshDashboardData, REFRESH_INTERVAL_MS);
        });
    </script>
</body>
</html>
