<?php
// File: topup.php
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

// Logika untuk menangani permintaan AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $action = $_POST['action'] ?? '';

    if ($action === 'submit_topup') {
        $nominal = filter_input(INPUT_POST, 'nominal', FILTER_VALIDATE_FLOAT);
        $senderName = filter_input(INPUT_POST, 'senderName', FILTER_SANITIZE_STRING);
        $paymentMethod = filter_input(INPUT_POST, 'paymentMethod', FILTER_SANITIZE_STRING);

        $MIN_TOPUP_NOMINAL = 50000;

        if ($nominal === false || $nominal < $MIN_TOPUP_NOMINAL || empty($senderName) || empty($paymentMethod)) {
            $response['message'] = 'Data top up tidak valid atau tidak lengkap.';
            echo json_encode($response);
            exit();
        }

        // Simpan data topup
        $topups = readJsonFile(TOPUPS_FILE);
        $newTopup = [
            'id' => uniqid(),
            'userId' => $loggedInUser['id'], // TAMBAHAN: Menyimpan userId
            'email' => $loggedInUser['email'],
            'nominal' => $nominal,
            'method' => $paymentMethod,
            'senderName' => $senderName, // Tambahkan senderName ke JSON juga
            'status' => 'pending',
            'requestDate' => date('Y-m-d H:i:s')
        ];
        $topups[] = $newTopup;
        writeJsonFile(TOPUPS_FILE, $topups);

        $response['success'] = true;
        $response['message'] = 'Permintaan Top Up berhasil diajukan. Menunggu verifikasi.';
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
    <title>CRYPTOFX - Top Up Saldo</title>
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
            --success-color: #28a745;
            --qris-color: #00b050; /* Greenish for QRIS */
            --info-color: #00bcd4; /* Light blue for info */
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

        /* Top Up Card */
        .topup-card {
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

        .topup-card h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .topup-card h2 .icon {
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
            color: var(--accent-color);
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
        }

        .check-button {
            background-color: var(--button-primary);
            color: white;
            padding: 12px 25px;
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
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .check-button:hover:not(:disabled) {
            background-color: var(--button-primary-hover);
            transform: translateY(-3px);
        }

        .check-button:disabled {
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
            max-width: 450px;
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
            color: #fff;
            font-size: 2em;
            margin-bottom: 20px;
        }

        .payment-details p {
            font-size: 1.1em;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .payment-details .detail-item {
            font-weight: bold;
            color: #ffd700; /* Gold for important details */
        }

        .qris-code {
            background-color: #fff; /* White background for QRIS */
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
            margin: 20px auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .qris-code img {
            width: 180px;
            height: 180px;
            display: block;
        }

        .payment-status-message {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--info-color);
            padding: 20px;
            margin-top: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .modal-content button {
            background-color: var(--success-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .modal-content button:hover {
            background-color: #218838;
        }
        .modal-content button:disabled {
            background-color: #4a4a5a;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            .topup-card {
                padding: 25px;
                max-width: 450px;
            }
            .topup-card h2 {
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
            .check-button {
                padding: 10px 20px;
                font-size: 1em;
            }
            .modal-content {
                padding: 25px;
            }
            .modal-content h3 {
                font-size: 1.8em;
            }
            .payment-details p {
                font-size: 1em;
            }
            .qris-code img {
                width: 150px;
                height: 150px;
            }
            .payment-status-message {
                font-size: 1.1em;
                padding: 15px;
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
            .topup-card h2 {
                font-size: 1.8em;
            }
            #nominalWarning {
                font-size: 0.85em;
            }
            .modal-content h3 {
                font-size: 1.5em;
            }
            .payment-details p {
                font-size: 0.9em;
            }
            .qris-code img {
                width: 120px;
                height: 120px;
            }
            .payment-status-message {
                font-size: 1em;
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
        <div class="topup-card">
            <h2><span class="icon"><i class="fas fa-plus-circle"></i></span> Top Up Saldo</h2>

            <form id="topupForm">
                <div class="form-group">
                    <label for="topupNominal">Nominal Top Up (Min. Rp 50.000):</label>
                    <input type="number" id="topupNominal" placeholder="Masukkan nominal" min="50000" required>
                    <p id="nominalWarning" style="display: none;">Minimal top up adalah Rp 50.000.</p>
                </div>
                <div class="form-group">
                    <label for="senderName">Nama Pengirim:</label>
                    <input type="text" id="senderName" placeholder="Nama lengkap Anda" required>
                </div>
                <div class="form-group">
                    <label for="paymentMethod">Metode Pembayaran:</label>
                    <select id="paymentMethod" required>
                        <option value="qris" selected>QRIS</option>
                        <option value="bank_transfer" disabled>Transfer Bank (Coming Soon)</option>
                        <option value="e_wallet" disabled>E-Wallet (Coming Soon)</option>
                    </select>
                </div>

                <button type="submit" class="check-button" id="checkButton"><i class="fas fa-check-circle"></i> Cek Pembayaran</button>
            </form>
        </div>

        <div id="paymentModal" class="modal">
            <div class="modal-content" id="paymentModalContent">
                <span class="close-button" id="closeModalButton">&times;</span>
                <div id="qrisPaymentSection">
                    <h3>Detail Pembayaran QRIS</h3>
                    <div class="payment-details">
                        <p>Nominal: <span class="detail-item" id="modalNominal"></span></p>
                        <p>Nama Pengirim: <span class="detail-item" id="modalSenderName"></span></p>
                    </div>
                    <div class="qris-code">
                        <img src="img/QRIS.jpg" alt="ALLNEEDSS">
                        <p style="font-size:0.8em; color:#666; margin-top: 5px;">(tekan QRIS)</p>
                    </div>
                    <p style="font-size:0.9em; color:var(--text-color);">Scan QRIS di atas dengan aplikasi pembayaran Anda.</p>
                    <button id="iHavePaidButton"><i class="fas fa-money-bill-wave"></i> Saya Sudah Bayar</button>
                </div>

                <div id="processingStatusSection" style="display: none;">
                    <h3><i class="fas fa-hourglass-half"></i> Pembayaran Diproses</h3>
                    <p class="payment-status-message">Pembayaranmu sedang diproses. Mohon tunggu beberapa saat. Saldo akan bertambah setelah verifikasi.</p>
                    <button id="modalOkButtonProcess">OK</button>
                </div>
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
        const MIN_TOPUP_NOMINAL = 50000; // Rp 50.000

        // DOM Elements
        const topupForm = document.getElementById('topupForm');
        const topupNominalInput = document.getElementById('topupNominal');
        const senderNameInput = document.getElementById('senderName');
        const paymentMethodSelect = document.getElementById('paymentMethod');
        const checkButton = document.getElementById('checkButton');
        const nominalWarningEl = document.getElementById('nominalWarning');

        const paymentModal = document.getElementById('paymentModal');
        const closeModalButton = document.getElementById('closeModalButton');
        const modalNominalEl = document.getElementById('modalNominal');
        const modalSenderNameEl = document.getElementById('modalSenderName');
        const iHavePaidButton = document.getElementById('iHavePaidButton');

        const qrisPaymentSection = document.getElementById('qrisPaymentSection');
        const processingStatusSection = document.getElementById('processingStatusSection');
        const modalOkButtonProcess = document.getElementById('modalOkButtonProcess');

        // --- Helper Functions ---

        // Formats a number to Rupiah currency
        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        // --- Event Listeners ---

        // Validate nominal input on change/input
        topupNominalInput.addEventListener('input', updateCheckButtonState);
        senderNameInput.addEventListener('input', updateCheckButtonState);
        paymentMethodSelect.addEventListener('change', updateCheckButtonState);


        function updateCheckButtonState() {
            const nominal = parseFloat(topupNominalInput.value);
            const senderName = senderNameInput.value.trim();
            const paymentMethod = paymentMethodSelect.value;

            if (isNaN(nominal) || nominal < MIN_TOPUP_NOMINAL || senderName === '' || paymentMethod === '') {
                nominalWarningEl.style.display = (isNaN(nominal) || nominal < MIN_TOPUP_NOMINAL) ? 'block' : 'none';
                checkButton.disabled = true;
            } else {
                nominalWarningEl.style.display = 'none';
                checkButton.disabled = false;
            }
        }

        // Handle form submission (Check button click)
        topupForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent default form submission

            const nominal = parseFloat(topupNominalInput.value);
            const senderName = senderNameInput.value.trim();
            const paymentMethod = paymentMethodSelect.value;

            if (isNaN(nominal) || nominal < MIN_TOPUP_NOMINAL || senderName === '' || paymentMethod === '') {
                alert("Harap lengkapi semua data dan pastikan nominal sesuai syarat.");
                return;
            }

            // Display details in modal
            modalNominalEl.textContent = formatRupiah(nominal);
            modalSenderNameEl.textContent = senderName;

            // Show QRIS payment section and hide processing status
            qrisPaymentSection.style.display = 'block';
            processingStatusSection.style.display = 'none';
            paymentModal.style.display = 'flex'; // Show modal

            // Ensure I Have Paid Button is enabled
            iHavePaidButton.disabled = false;
        });

        // Close Modal Button
        closeModalButton.addEventListener('click', () => {
            paymentModal.style.display = 'none';
            // Optional: reset form after closing modal
            topupForm.reset();
            updateCheckButtonState(); // Disable button again
        });

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === paymentModal) {
                paymentModal.style.display = 'none';
                // Optional: reset form after closing modal
                topupForm.reset();
                updateCheckButtonState();
            }
        });

        // "Saya Sudah Bayar" button in modal
        iHavePaidButton.addEventListener('click', async () => {
            // Simulate payment processing
            qrisPaymentSection.style.display = 'none'; // Hide QRIS details
            processingStatusSection.style.display = 'block'; // Show processing message
            iHavePaidButton.disabled = true; // Disable this button once clicked

            const nominal = parseFloat(topupNominalInput.value);
            const senderName = senderNameInput.value.trim();
            const paymentMethod = paymentMethodSelect.value;

            const formData = new FormData();
            formData.append('action', 'submit_topup');
            formData.append('nominal', nominal);
            formData.append('senderName', senderName);
            formData.append('paymentMethod', paymentMethod);

            try {
                const response = await fetch('topup.php', { // Kirim data ke topup.php itu sendiri
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Message already set in modal by logic
                } else {
                    alert(result.message);
                    // Optionally, revert UI to QRIS section if submission failed
                    qrisPaymentSection.style.display = 'block';
                    processingStatusSection.style.display = 'none';
                    iHavePaidButton.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('deposit sedang dalam proses silahkan tunggu, mohon cek dompet anda segera jika anda sudah melakukan pembayaran.');
                // Optionally, revert UI
                qrisPaymentSection.style.display = 'block';
                processingStatusSection.style.display = 'none';
                iHavePaidButton.disabled = false;
            }
        });

        // "OK" button in processing status modal
        modalOkButtonProcess.addEventListener('click', () => {
            paymentModal.style.display = 'none';
            // Optional: reset form after closing modal
            topupForm.reset();
            updateCheckButtonState();
        });

        // Initial state: disable check button on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateCheckButtonState(); // Initialize button state
        });
    </script>
</body>
</html>
