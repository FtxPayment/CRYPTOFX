<?php
// File: penarikan.php
require_once 'config.php';
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$users = readJsonFile(USERS_FILE);
$loggedInUser = null;
$userIndex = -1;
foreach ($users as $index => $user) {
    if ($user['id'] === $_SESSION['user_id']) {
        $loggedInUser = $user;
        $userIndex = $index;
        break;
    }
}

if (!$loggedInUser) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$currentUserAvailableBalance = $loggedInUser['mainBalance'] ?? 0;

// Logika untuk menangani permintaan AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $action = $_POST['action'] ?? '';

    if ($action === 'submit_withdrawal') {
        $nominal = filter_input(INPUT_POST, 'nominal', FILTER_VALIDATE_FLOAT);
        $method = filter_input(INPUT_POST, 'method', FILTER_SANITIZE_STRING);
        $accountNumber = filter_input(INPUT_POST, 'accountNumber', FILTER_SANITIZE_STRING);
        $accountName = filter_input(INPUT_POST, 'accountName', FILTER_SANITIZE_STRING);

        $MIN_WITHDRAWAL_NOMINAL = 50000; // Rp 50.000
        $WITHDRAWAL_FEE_PERCENTAGE = 0.10; // 10%
        $ADMIN_FEE = 5000; // Rp 5.000

        if ($nominal === false || $nominal < $MIN_WITHDRAWAL_NOMINAL || empty($method) || empty($accountNumber) || empty($accountName)) {
            $response['message'] = 'Data penarikan tidak valid atau tidak lengkap.';
            echo json_encode($response);
            exit();
        }

        if ($nominal > $loggedInUser['mainBalance']) {
            $response['message'] = 'Saldo Anda tidak cukup untuk penarikan ini.';
            echo json_encode($response);
            exit();
        }

        // Hitung fee dan total diterima di backend untuk keamanan
        $calculatedFee = $nominal * $WITHDRAWAL_FEE_PERCENTAGE;
        $calculatedTotalReceived = $nominal - $calculatedFee - $ADMIN_FEE;

        if ($calculatedTotalReceived <= 0) {
            $response['message'] = 'Nominal yang diterima setelah biaya tidak boleh nol atau negatif.';
            echo json_encode($response);
            exit();
        }

        // Kurangi saldo pengguna segera setelah pengajuan
        $users[$userIndex]['mainBalance'] -= $nominal;
        writeJsonFile(USERS_FILE, $users);

        // Catat di riwayat penarikan
        $withdrawals = readJsonFile(WITHDRAWALS_FILE);
        $newWithdrawal = [
            'id' => uniqid(),
            'userId' => $loggedInUser['id'], // TAMBAHAN: Menyimpan userId
            'email' => $loggedInUser['email'],
            'nominal' => $nominal,
            'method' => $method,
            'accountNumber' => $accountNumber,
            'accountName' => $accountName,
            'fee' => $calculatedFee,
            'adminFee' => $ADMIN_FEE,
            'totalReceived' => $calculatedTotalReceived,
            'status' => 'pending', // Status awal pending
            'requestDate' => date('Y-m-d H:i:s')
        ];
        $withdrawals[] = $newWithdrawal;
        writeJsonFile(WITHDRAWALS_FILE, $withdrawals);

        $response['success'] = true;
        $response['message'] = 'Permintaan penarikan berhasil diajukan. Saldo Anda telah dikurangi dan menunggu persetujuan admin.';
        $response['newBalance'] = $users[$userIndex]['mainBalance'];
        echo json_encode($response);
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Penarikan Saldo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        :root {
            --primary-bg: #1a1a2e; /* Dark background */
            --secondary-bg: #16213e; /* Slightly lighter dark background */
            --accent-color: #e94560; /* Reddish accent */
            --text-color: #e0e0e0; /* Light text */
            --card-bg: #0f3460; /* Dark blue for cards */
            --border-color: #0c2d50; /* Darker blue border */
            --button-primary: #007bff; /* Blue for action buttons */
            --button-primary-hover: #0056b3;
            --info-color: #00bcd4; /* Light blue for info/status */
            --error-color: #dc3545;
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
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Withdrawal Card */
        .withdrawal-card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            text-align: center;
            margin-bottom: 30px;
        }

        .withdrawal-card h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .withdrawal-card h2 .icon {
            font-size: 1.2em;
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
            font-size: 1em;
        }

        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-size: 1.1em;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="number"]:focus,
        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-color: var(--accent-color);
        }

        #nominalWarning {
            color: var(--error-color);
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
        }

        #currentBalanceInfo {
            font-size: 0.95em;
            color: var(--text-color);
            margin-bottom: 20px;
        }
        #currentBalanceInfo .balance-value {
            font-weight: bold;
            color: #ffd700; /* Gold */
        }

        /* Withdrawal Details */
        .withdrawal-details {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .withdrawal-details h3 {
            font-size: 1.5em;
            color: #fff;
            margin-bottom: 15px;
            text-align: center;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            font-size: 1em;
        }
        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item .label {
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-item .value {
            font-weight: bold;
            color: #fff;
        }
        .detail-item.total-received .value {
            font-size: 1.2em;
            color: var(--info-color); /* Light blue for total received */
        }

        .confirm-button {
            background-color: var(--accent-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: fit-content;
            margin: 30px auto 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .confirm-button:hover:not(:disabled) {
            background-color: #c7374d; /* Darker accent */
            transform: translateY(-3px);
        }
        .confirm-button:disabled {
            background-color: #4a4a5a;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Modal (Popup) */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 100; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.8); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--secondary-bg);
            margin: auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            position: relative;
            animation: fadeInScale 0.3s ease-out;
            text-align: center;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .close-button {
            color: var(--text-color);
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 25px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-button:hover,
        .close-button:focus {
            color: var(--accent-color);
            text-decoration: none;
        }

        .modal-content h3 {
            color: var(--info-color);
            font-size: 2em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-content p {
            font-size: 1.1em;
            margin-bottom: 25px;
            color: var(--text-color);
        }

        .modal-content button {
            background-color: var(--button-primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .modal-content button:hover {
            background-color: var(--button-primary-hover);
        }

        /* Back Button */
        .back-button {
            background-color: var(--button-primary-hover);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            margin-top: 20px;
        }

        .back-button:hover {
            background-color: var(--button-primary);
            transform: translateY(-3px);
        }
        .back-button i {
            font-size: 1.2em;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .withdrawal-card {
                padding: 25px;
                max-width: 450px;
            }
            .withdrawal-card h2 {
                font-size: 2em;
                gap: 10px;
            }
            .form-group label {
                font-size: 0.95em;
            }
            .form-group input, .form-group select {
                padding: 10px;
                font-size: 1em;
            }
            #currentBalanceInfo {
                font-size: 0.9em;
            }
            .withdrawal-details h3 {
                font-size: 1.3em;
            }
            .detail-item {
                font-size: 0.95em;
            }
            .confirm-button {
                padding: 12px 25px;
                font-size: 1em;
            }
            .modal-content {
                padding: 25px;
            }
            .modal-content h3 {
                font-size: 1.8em;
                gap: 8px;
            }
            .modal-content p {
                font-size: 1em;
            }
            .modal-content button {
                padding: 10px 20px;
                font-size: 1em;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .withdrawal-card h2 {
                font-size: 1.8em;
            }
            #nominalWarning {
                font-size: 0.85em;
            }
            #currentBalanceInfo {
                font-size: 0.85em;
            }
            .detail-item .label, .detail-item .value {
                font-size: 0.9em;
            }
            .confirm-button {
                font-size: 0.9em;
                padding: 10px 20px;
            }
            .modal-content h3 {
                font-size: 1.5em;
            }
            .modal-content p {
                font-size: 0.9em;
            }
            .modal-content button {
                font-size: 0.9em;
            }
            .back-button {
                font-size: 0.9em;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="withdrawal-card">
            <h2><span class="icon"><i class="fas fa-wallet"></i></span> Penarikan Saldo</h2>

            <p id="currentBalanceInfo">Saldo Tersedia: <span class="balance-value" id="userCurrentBalance"><?php echo formatRupiah($currentUserAvailableBalance); ?></span></p>

            <form id="withdrawalForm">
                <div class="form-group">
                    <label for="withdrawalNominal">Nominal Penarikan (Min. Rp 50.000):</label>
                    <input type="number" id="withdrawalNominal" placeholder="Masukkan nominal" min="50000" required>
                    <p id="nominalWarning" style="display: none;">Nominal minimal penarikan adalah Rp 50.000 atau saldo tidak cukup.</p>
                </div>
                <div class="form-group">
                    <label for="withdrawalMethod">Metode Penarikan:</label>
                    <select id="withdrawalMethod" required>
                        <option value="">Pilih E-Wallet</option>
                        <option value="dana">DANA</option>
                        <option value="ovo">OVO</option>
                        <option value="gopay">GoPay</option>
                        <option value="shopeepay">ShopeePay</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="accountNumber">Nomor Rekening / HP E-Wallet:</label>
                    <input type="text" id="accountNumber" placeholder="Cth: 081234567890" required>
                </div>
                <div class="form-group">
                    <label for="accountName">Nama Pemilik Rekening / E-Wallet:</label>
                    <input type="text" id="accountName" placeholder="Nama Lengkap Sesuai E-Wallet" required>
                </div>

                <div class="withdrawal-details">
                    <h3>Ringkasan Penarikan</h3>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-coins"></i> Nominal Penarikan:</span>
                        <span class="value" id="detailNominal">Rp 0,00</span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-percentage"></i> Fee (10%):</span>
                        <span class="value" id="detailFee">Rp 0,00</span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-money-bill-wave"></i> Biaya Admin (Tetap):</span>
                        <span class="value" id="detailAdminFee">Rp 0,00</span>
                    </div>
                    <div class="detail-item total-received">
                        <span class="label"><i class="fas fa-hand-holding-usd"></i> Total Diterima:</span>
                        <span class="value" id="detailTotalReceived">Rp 0,00</span>
                    </div>
                </div>

                <button type="submit" class="confirm-button" id="confirmButton" disabled><i class="fas fa-check-circle"></i> Konfirmasi Penarikan</button>
            </form>
        </div>

        <div id="withdrawalModal" class="modal">
            <div class="modal-content">
                <span class="close-button" id="closeModalButton">&times;</span>
                <h3><i class="fas fa-hourglass-half"></i> Penarikan Dalam Proses</h3>
                <p>Penarikan sedang dalam proses. Silakan tunggu beberapa saat. Dana akan dikirim ke e-wallet Anda dalam waktu maksimal 1x24 jam.</p>
                <button id="modalOkButton">OK</button>
            </div>
        </div>

        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <footer class="footer">
        <p>&copy; 2025 CRYPTOFX. Hak cipta dilindungi undang-undang.</p>
    </footer>

    <script>
        // Constants
        const MIN_WITHDRAWAL_NOMINAL = 50000; // Rp 50.000
        const WITHDRAWAL_FEE_PERCENTAGE = 0.10; // 10%
        const ADMIN_FEE = 5000; // Rp 5.000

        // Simulated current user balance (replace with actual balance in real app)
        let currentUserAvailableBalance = parseFloat("<?php echo $currentUserAvailableBalance; ?>");

        // DOM Elements
        const userCurrentBalanceEl = document.getElementById('userCurrentBalance');
        const withdrawalForm = document.getElementById('withdrawalForm');
        const withdrawalNominalInput = document.getElementById('withdrawalNominal');
        const withdrawalMethodSelect = document.getElementById('withdrawalMethod');
        const accountNumberInput = document.getElementById('accountNumber');
        const accountNameInput = document.getElementById('accountName');
        const nominalWarningEl = document.getElementById('nominalWarning');
        const confirmButton = document.getElementById('confirmButton');

        const detailNominalEl = document.getElementById('detailNominal');
        const detailFeeEl = document.getElementById('detailFee');
        const detailAdminFeeEl = document.getElementById('detailAdminFee');
        const detailTotalReceivedEl = document.getElementById('detailTotalReceived');

        const withdrawalModal = document.getElementById('withdrawalModal');
        const closeModalButton = document.getElementById('closeModalButton');
        const modalOkButton = document.getElementById('modalOkButton');

        // --- Helper Functions ---

        // Formats a number to Rupiah currency
        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        // Updates the withdrawal details section and button state
        function updateWithdrawalDetails() {
            const nominal = parseFloat(withdrawalNominalInput.value);
            const isNominalValid = !isNaN(nominal) && nominal >= MIN_WITHDRAWAL_NOMINAL;

            detailAdminFeeEl.textContent = formatRupiah(ADMIN_FEE); // Admin fee is fixed

            if (isNominalValid) {
                const fee = nominal * WITHDRAWAL_FEE_PERCENTAGE;
                const totalReceived = nominal - fee - ADMIN_FEE;

                detailNominalEl.textContent = formatRupiah(nominal);
                detailFeeEl.textContent = formatRupiah(fee);
                detailTotalReceivedEl.textContent = formatRupiah(totalReceived);

                // Enable/disable button based on all conditions
                const isAllFieldsFilled = withdrawalMethodSelect.value !== '' && accountNumberInput.value.trim() !== '' && accountNameInput.value.trim() !== '';

                if (nominal > currentUserAvailableBalance) {
                    nominalWarningEl.textContent = `Saldo Anda tidak cukup. Saldo tersedia: ${formatRupiah(currentUserAvailableBalance)}`;
                    nominalWarningEl.style.display = 'block';
                    confirmButton.disabled = true;
                } else if (totalReceived <= 0) {
                     nominalWarningEl.textContent = "Nominal yang diterima setelah biaya tidak boleh nol atau negatif.";
                     nominalWarningEl.style.display = 'block';
                     confirmButton.disabled = true;
                }
                else {
                    nominalWarningEl.style.display = 'none';
                    confirmButton.disabled = !isAllFieldsFilled;
                }
            } else {
                // Reset details if nominal is invalid or empty
                detailNominalEl.textContent = formatRupiah(0);
                detailFeeEl.textContent = formatRupiah(0);
                detailTotalReceivedEl.textContent = formatRupiah(0);
                confirmButton.disabled = true;
            }
        }

        // --- Event Listeners ---

        // Update details and validate on input changes
        withdrawalNominalInput.addEventListener('input', () => {
            updateWithdrawalDetails();
            const nominal = parseFloat(withdrawalNominalInput.value);
            if (isNaN(nominal) || nominal < MIN_WITHDRAWAL_NOMINAL) {
                 nominalWarningEl.textContent = `Nominal minimal penarikan adalah ${formatRupiah(MIN_WITHDRAWAL_NOMINAL)}.`;
                 nominalWarningEl.style.display = 'block';
            } else {
                 // Warning will be handled by updateWithdrawalDetails based on balance
            }
        });

        withdrawalMethodSelect.addEventListener('change', updateWithdrawalDetails);
        accountNumberInput.addEventListener('input', updateWithdrawalDetails);
        accountNameInput.addEventListener('input', updateWithdrawalDetails);

        // Handle form submission (Confirm button click)
        withdrawalForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent default form submission

            const nominal = parseFloat(withdrawalNominalInput.value);
            const method = withdrawalMethodSelect.value;
            const accountNumber = accountNumberInput.value.trim();
            const accountName = accountNameInput.value.trim();
            // Fee and totalReceived will be calculated by backend for security
            // But we send them for consistency if frontend calculated them

            if (nominal < MIN_WITHDRAWAL_NOMINAL || nominal > currentUserAvailableBalance || method === '' || accountNumber === '' || accountName === '') {
                alert("Harap lengkapi semua data dengan benar dan pastikan nominal sesuai.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'submit_withdrawal');
            formData.append('nominal', nominal);
            formData.append('method', method);
            formData.append('accountNumber', accountNumber);
            formData.append('accountName', accountName);
            // formData.append('fee', fee); // No need to send from frontend, backend calculates
            // formData.append('totalReceived', totalReceived); // No need to send from frontend, backend calculates

            try {
                const response = await fetch('penarikan.php', { // Kirim data ke penarikan.php itu sendiri
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    currentUserAvailableBalance = parseFloat(result.newBalance); // Update local balance
                    userCurrentBalanceEl.textContent = formatRupiah(currentUserAvailableBalance); // Update display
                    
                    withdrawalModal.style.display = 'flex'; // Show success modal
                    withdrawalForm.reset(); // Reset form
                    updateWithdrawalDetails(); // Recalculate and disable button
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Penarikan anda sedang dalam proses mohon tunggu.');
            }
        });

        // Close Modal Button
        closeModalButton.addEventListener('click', () => {
            withdrawalModal.style.display = 'none';
        });

        modalOkButton.addEventListener('click', () => {
            withdrawalModal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === withdrawalModal) {
                withdrawalModal.style.display = 'none';
            }
        });

        // Initial state: display current balance and disable confirm button
        document.addEventListener('DOMContentLoaded', () => {
            userCurrentBalanceEl.textContent = formatRupiah(currentUserAvailableBalance);
            updateWithdrawalDetails(); // Initialize details and button state
        });
    </script>
</body>
</html>
