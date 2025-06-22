<?php
// File: swap.php
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

$currentCfxBalance = $loggedInUser['cryptoBalance'];
$currentTotalSwappedIDR = $loggedInUser['mainBalance']; // Asumsi hasil swap masuk ke mainBalance
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Swap Token</title>
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

        /* Swap Card */
        .swap-card {
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

        .swap-card h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .swap-card h2 .icon {
            font-size: 1.2em;
            color: var(--accent-color);
        }

        .balance-info {
            font-size: 1.2em;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .balance-info .balance-value {
            font-weight: bold;
            color: #ffd700; /* Gold for balance */
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
        }

        .form-group input[type="number"] {
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

        .form-group input[type="number"]:focus {
            border-color: var(--accent-color);
        }

        .conversion-result {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 20px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            color: #ffd700;
            opacity: 0; /* Hidden by default for animation */
            transition: opacity 0.5s ease-in-out;
            min-height: 55px; /* Prevent layout shift when empty */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .conversion-result.show {
            opacity: 1;
        }

        .total-swap-balance {
            font-size: 1.3em;
            font-weight: bold;
            margin-top: 25px;
            color: var(--text-color);
        }
        .total-swap-balance .value {
            color: var(--success-color);
            font-size: 1.1em;
        }


        .swap-card button {
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
            margin: 30px auto 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .swap-card button:hover:not(:disabled) {
            background-color: var(--button-primary-hover);
            transform: translateY(-3px);
        }

        .swap-card button:disabled {
            background-color: #4a4a5a;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Modal (Popup Form) */
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

        .modal-content h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
            font-size: 2em;
        }

        .modal-form-group { /* Specific for modal form group */
            margin-bottom: 20px;
        }

        .modal-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        .modal-form-group input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-size: 1em;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .modal-form-group input[type="number"]:focus {
            border-color: var(--accent-color);
        }

        .modal-content button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #c7374d; /* Darker accent */
        }

        #modalWarning {
            color: var(--error-color);
            font-size: 0.9em;
            margin-top: 10px;
            text-align: center;
            display: none;
        }

        /* Back Button */
        .back-button {
            background-color: var(--button-primary-hover); /* A bit darker for general buttons */
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
            .swap-card {
                padding: 25px;
                max-width: 450px;
            }
            .swap-card h2 {
                font-size: 2em;
                gap: 10px;
            }
            .balance-info {
                font-size: 1.1em;
            }
            .form-group input[type="number"] {
                padding: 10px;
                font-size: 1em;
            }
            .conversion-result {
                font-size: 1.1em;
                min-height: 50px;
            }
            .total-swap-balance {
                font-size: 1.2em;
            }
            .swap-card button {
                padding: 10px 20px;
                font-size: 1em;
            }
            .modal-content {
                padding: 25px;
            }
            .modal-content h2 {
                font-size: 1.8em;
            }
            .modal-form-group input[type="number"] {
                padding: 10px;
                font-size: 0.95em;
            }
            .modal-content button[type="submit"] {
                padding: 10px 20px;
                font-size: 1em;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .swap-card h2 {
                font-size: 1.8em;
            }
            .balance-info {
                font-size: 1em;
            }
            .conversion-result {
                font-size: 1em;
            }
            .total-swap-balance {
                font-size: 1.1em;
            }
            .modal-content h2 {
                font-size: 1.5em;
            }
            .modal-form-group label {
                font-size: 0.9em;
            }
            .modal-form-group input, .modal-content button[type="submit"] {
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
        <div class="swap-card">
            <h2><span class="icon"><i class="fas fa-exchange-alt"></i></span> Konversi Token (Swap)</h2>

            <p class="balance-info">Saldo CFX Anda: <span class="balance-value" id="cfxBalance"><?php echo formatCfx($currentCfxBalance); ?></span></p>

            <div class="form-group">
                <label for="cfxInput">Jumlah CFX yang ingin di-swap:</label>
                <input type="number" id="cfxInput" step="0.00001" min="0" placeholder="Masukkan jumlah CFX">
            </div>

            <div class="conversion-result" id="conversionResult">
                </div>

            <p class="total-swap-balance">Total Saldo Hasil Swap: <span class="value" id="totalSwappedBalance"><?php echo formatRupiah($currentTotalSwappedIDR); ?></span></p>

            <button id="sendToWalletBtn" disabled><i class="fas fa-wallet"></i> Kirim ke Dompet</button>
        </div>

        <div id="sendModal" class="modal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2>Kirim Hasil Swap ke Dompet</h2>
                <form id="sendSwapForm">
                    <div class="modal-form-group">
                        <label for="sendNominal">Nominal yang akan dikirim (IDR):</label>
                        <input type="number" id="sendNominal" placeholder="Minimal Rp 15.000" min="15000" required>
                        <p id="modalWarning" style="display: none;">Nominal minimal adalah Rp 15.000.</p>
                    </div>
                    <button type="submit">Konfirmasi Kirim</button>
                </form>
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
        // Constants and DOM Elements
        const CFX_TO_IDR_RATE = 5987011; // 1 CFX = Rp5,987,011
        const MIN_SEND_NOMINAL_IDR = 15000; // Rp15.000

        let currentCfxBalance = parseFloat("<?php echo $currentCfxBalance; ?>");
        let currentTotalSwappedIDR = parseFloat("<?php echo $currentTotalSwappedIDR; ?>");

        const cfxBalanceEl = document.getElementById('cfxBalance');
        const cfxInput = document.getElementById('cfxInput');
        const conversionResultEl = document.getElementById('conversionResult');
        const totalSwappedBalanceEl = document.getElementById('totalSwappedBalance');
        const sendToWalletBtn = document.getElementById('sendToWalletBtn');

        const sendModal = document.getElementById('sendModal');
        const closeButton = document.querySelector('#sendModal .close-button');
        const sendSwapForm = document.getElementById('sendSwapForm');
        const sendNominalInput = document.getElementById('sendNominal');
        const modalWarningEl = document.getElementById('modalWarning');

        // --- Helper Functions ---

        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        function formatCfx(amount) {
            return parseFloat(amount).toFixed(5) + ' CFX'; // 5 decimal places for CFX
        }

        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.textContent = formatRupiah(start + progress * (end - start));
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    obj.textContent = formatRupiah(end); // Ensure final value is accurate
                }
            };
            window.requestAnimationFrame(step);
        }

        // --- Initialize Page ---
        cfxBalanceEl.textContent = formatCfx(currentCfxBalance);
        totalSwappedBalanceEl.textContent = formatRupiah(currentTotalSwappedIDR);
        sendNominalInput.min = MIN_SEND_NOMINAL_IDR; // Set min value for modal input

        // --- Event Listeners ---

        // CFX Input Change (Real-time conversion)
        cfxInput.addEventListener('input', () => {
            let cfxAmount = parseFloat(cfxInput.value);

            if (isNaN(cfxAmount) || cfxAmount <= 0) {
                conversionResultEl.textContent = '';
                conversionResultEl.classList.remove('show');
                sendToWalletBtn.disabled = true;
                return;
            }

            if (cfxAmount > currentCfxBalance) {
                cfxInput.value = currentCfxBalance.toFixed(5); // Cap input at current balance
                cfxAmount = currentCfxBalance;
            }

            const convertedIDR = cfxAmount * CFX_TO_IDR_RATE;

            if (conversionResultEl.textContent === '' || Math.abs(parseFloat(conversionResultEl.textContent.replace('Rp ', '').replace(/\./g, '')) - convertedIDR) > 1) {
                 const currentDisplayedValue = parseFloat(conversionResultEl.textContent.replace('Rp ', '').replace(/\./g, '').replace(/,/g, '.') || '0');
                 animateValue(conversionResultEl, currentDisplayedValue, convertedIDR, 300); // 300ms animation
            }

            conversionResultEl.classList.add('show');
            sendToWalletBtn.disabled = false; // Enable send button if a valid amount is entered
        });

        // "Kirim ke Dompet" button click
        sendToWalletBtn.addEventListener('click', () => {
            const cfxAmount = parseFloat(cfxInput.value);
            if (isNaN(cfxAmount) || cfxAmount <= 0 || cfxAmount > currentCfxBalance) {
                alert("Jumlah CFX yang ingin di-swap tidak valid atau melebihi saldo Anda.");
                return;
            }
            const convertedIDR = cfxAmount * CFX_TO_IDR_RATE;

            sendNominalInput.value = convertedIDR.toFixed(0); // Pre-fill with converted amount
            sendNominalInput.max = convertedIDR; // Max is the converted amount
            modalWarningEl.style.display = 'none';

            sendModal.style.display = 'flex';
        });

        // Close Send Modal
        closeButton.addEventListener('click', () => {
            sendModal.style.display = 'none';
            modalWarningEl.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === sendModal) {
                sendModal.style.display = 'none';
                modalWarningEl.style.display = 'none';
            }
        });

        // Handle Send Swap Form Submission (inside modal)
        sendSwapForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nominalToSend = parseFloat(sendNominalInput.value);
            const cfxToSwap = parseFloat(cfxInput.value);
            const actualConvertedIDR = cfxToSwap * CFX_TO_IDR_RATE;

            if (isNaN(nominalToSend) || nominalToSend < MIN_SEND_NOMINAL_IDR) {
                modalWarningEl.textContent = `Nominal minimal adalah ${formatRupiah(MIN_SEND_NOMINAL_IDR)}.`;
                modalWarningEl.style.display = 'block';
                return;
            }

            if (nominalToSend > actualConvertedIDR) {
                modalWarningEl.textContent = `Nominal tidak boleh melebihi hasil konversi ${formatRupiah(actualConvertedIDR)}.`;
                modalWarningEl.style.display = 'block';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'perform_swap');
            formData.append('cfxAmount', cfxToSwap);
            formData.append('idrAmount', nominalToSend);

            try {
                const response = await fetch('swap_withdrawal_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                alert(result.message);
                if (result.success) {
                    currentCfxBalance = parseFloat(result.newCfxBalance);
                    currentTotalSwappedIDR = parseFloat(result.newIdrBalance);

                    cfxBalanceEl.textContent = formatCfx(currentCfxBalance);
                    totalSwappedBalanceEl.textContent = formatRupiah(currentTotalSwappedIDR);

                    cfxInput.value = '';
                    conversionResultEl.textContent = '';
                    conversionResultEl.classList.remove('show');
                    sendToWalletBtn.disabled = true;

                    sendModal.style.display = 'none';
                    modalWarningEl.style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses swap. Silakan coba lagi.');
            }
        });

        // Validate modal nominal input
        sendNominalInput.addEventListener('input', () => {
            const nominal = parseFloat(sendNominalInput.value);
            const cfxAmount = parseFloat(cfxInput.value);
            const actualConvertedIDR = cfxAmount * CFX_TO_IDR_RATE;

            if (isNaN(nominal) || nominal < MIN_SEND_NOMINAL_IDR || nominal > actualConvertedIDR) {
                sendNominalInput.setCustomValidity("Invalid");
                if (nominal < MIN_SEND_NOMINAL_IDR) {
                    modalWarningEl.textContent = `Nominal minimal adalah ${formatRupiah(MIN_SEND_NOMINAL_IDR)}.`;
                } else if (nominal > actualConvertedIDR) {
                    modalWarningEl.textContent = `Nominal tidak boleh melebihi hasil konversi ${formatRupiah(actualConvertedIDR)}.`;
                }
                modalWarningEl.style.display = 'block';
            } else {
                sendNominalInput.setCustomValidity("");
                modalWarningEl.style.display = 'none';
            }
        });
    </script>
</body>
</html>
