<?php
// File: dompet.php
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

    $amountToSend = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if ($amountToSend === false || $amountToSend <= 0) {
        $response['message'] = 'Nominal yang dikirim tidak valid.';
        echo json_encode($response);
        exit();
    }

    if ($amountToSend > $loggedInUser['mainBalance']) {
        $response['message'] = 'Saldo tidak cukup untuk melakukan pengiriman!';
        echo json_encode($response);
        exit();
    }

    // Lakukan simulasi transfer
    $users[$userIndex]['mainBalance'] -= $amountToSend;
    $users[$userIndex]['investmentBalance'] += $amountToSend; // Tambahkan ke saldo investasi
    $users[$userIndex]['hasInvested'] = true; // Set hasInvested menjadi true
    writeJsonFile(USERS_FILE, $users);

    $response['success'] = true;
    $response['message'] = 'Pengiriman ' . formatRupiah($amountToSend) . ' ke Saldo Investasi berhasil!';
    $response['newBalance'] = formatRupiah($users[$userIndex]['mainBalance']);
    echo json_encode($response);
    exit();
}

// Data yang akan ditampilkan di HTML
$userName = htmlspecialchars($loggedInUser['fullName']);
$userMainBalance = formatRupiah($loggedInUser['mainBalance']);

// Fungsi untuk menghasilkan nomor wallet virtual
function generateVirtualWalletNumber() {
    $walletNum = '';
    for ($i = 0; $i < 16; $i++) {
        $walletNum .= mt_rand(0, 9);
    }
    return chunk_split($walletNum, 4, ' ');
}
$walletNumber = generateVirtualWalletNumber(); // Generate setiap kali halaman dimuat (simulasi)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Dompet Digital</title>
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
            --wallet-card-gradient-start: #232c3d; /* Darker blue-grey for card */
            --wallet-card-gradient-end: #0e1620; /* Even darker blue-grey */
            --glossy-effect: rgba(255, 255, 255, 0.15); /* Light sheen for glossy */
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

        /* Digital ATM Card */
        .atm-card {
            background: linear-gradient(145deg, var(--wallet-card-gradient-start), var(--wallet-card-gradient-end));
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 450px;
            aspect-ratio: 16 / 9; /* Standard card aspect ratio */
            box-shadow: 0 10px 25px rgba(0,0,0,0.6);
            position: relative;
            overflow: hidden;
            color: #fff;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .atm-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--glossy-effect) 0%, transparent 50%, var(--glossy-effect) 100%);
            opacity: 0.2;
            z-index: 0;
            border-radius: 15px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .card-logo {
            font-weight: bold;
            font-size: 1.8em;
            color: var(--accent-color);
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-logo i {
            font-size: 1em;
        }

        .card-title {
            font-size: 1.2em;
            color: var(--text-color);
        }

        .card-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
            z-index: 1;
            flex-grow: 1; /* Allow details to take available space */
            justify-content: center;
        }

        .card-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1em;
        }

        .card-label {
            font-size: 0.9em;
            color: var(--text-color);
        }

        .card-value {
            font-size: 1.1em;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .card-value.wallet-number {
            font-family: 'monospace', sans-serif; /* Monospace for numbers */
            font-size: 1.2em;
        }

        .card-balance {
            font-size: 2.2em;
            font-weight: bold;
            color: #ffd700; /* Gold for balance */
            text-align: right;
            margin-top: 15px;
            position: relative;
            z-index: 1;
        }

        /* Send Button */
        .send-button {
            background-color: var(--button-primary);
            color: #fff;
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
            margin: 0 auto 30px auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .send-button:hover {
            background-color: var(--button-primary-hover);
            transform: translateY(-3px);
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        .form-group input[type="number"],
        .form-group select {
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

        .form-group input[type="number"]:focus,
        .form-group select:focus {
            border-color: var(--accent-color);
        }

        .form-group select option:disabled {
            color: #666;
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
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .atm-card {
                padding: 25px;
                max-width: 380px;
            }
            .card-logo {
                font-size: 1.6em;
            }
            .card-title {
                font-size: 1em;
            }
            .card-value.wallet-number {
                font-size: 1em;
            }
            .card-balance {
                font-size: 1.8em;
            }
            .send-button {
                padding: 12px 25px;
                font-size: 1em;
            }
            .modal-content {
                padding: 25px;
            }
            .modal-content h2 {
                font-size: 1.8em;
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
            .atm-card {
                padding: 20px;
                max-width: 320px;
                aspect-ratio: unset; /* Remove fixed aspect ratio for very small screens */
                min-height: 200px; /* Set a minimum height instead */
            }
            .card-logo {
                font-size: 1.4em;
            }
            .card-value.wallet-number {
                font-size: 0.9em;
            }
            .card-balance {
                font-size: 1.5em;
            }
            .send-button {
                font-size: 0.9em;
                padding: 10px 20px;
            }
            .modal-content h2 {
                font-size: 1.5em;
            }
            .form-group label {
                font-size: 0.9em;
            }
            .form-group input, .form-group select {
                padding: 10px;
                font-size: 0.9em;
            }
            .modal-content button[type="submit"] {
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
        <div class="atm-card">
            <div class="card-header">
                <div class="card-logo"><i class="fas fa-coins"></i> CRYPTOFX</div>
                <div class="card-title">Dompet Digital</div>
            </div>
            <div class="card-details">
                <div class="card-detail-row">
                    <span class="card-label">Nama Pengguna</span>
                    <span class="card-value" id="userName"><?php echo $userName; ?></span>
                </div>
                <div class="card-detail-row">
                    <span class="card-label">Virtual Wallet Number</span>
                    <span class="card-value wallet-number" id="walletNumber"><?php echo $walletNumber; ?></span>
                </div>
            </div>
            <div class="card-balance" id="totalBalance"><?php echo $userMainBalance; ?></div>
        </div>

        <button class="send-button" id="sendButton"><i class="fas fa-paper-plane"></i> Kirim Dana</button>

        <div id="sendModal" class="modal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2>Kirim Dana</h2>
                <form id="sendForm">
                    <div class="form-group">
                        <label for="sendAmount">Nominal:</label>
                        <input type="number" id="sendAmount" placeholder="Masukkan nominal" min="1000" required>
                    </div>
                    <div class="form-group">
                        <label for="sendMethod">Metode Pengiriman:</label>
                        <select id="sendMethod">
                            <option value="investment" selected>Kirim ke Saldo Investasi</option>
                            <option value="other" disabled>Transfer ke Bank (Coming Soon)</option>
                            <option value="crypto" disabled>Transfer ke Wallet Kripto (Coming Soon)</option>
                        </select>
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
        // DOM Elements
        const userNameEl = document.getElementById('userName');
        const walletNumberEl = document.getElementById('walletNumber');
        const totalBalanceEl = document.getElementById('totalBalance');
        const sendButton = document.getElementById('sendButton');
        const sendModal = document.getElementById('sendModal');
        const closeButton = document.querySelector('#sendModal .close-button');
        const sendForm = document.getElementById('sendForm');
        const sendAmountInput = document.getElementById('sendAmount');
        const sendMethodSelect = document.getElementById('sendMethod');

        // Initial Data (from PHP)
        let currentUserBalance = parseFloat("<?php echo $loggedInUser['mainBalance']; ?>");

        // --- Helper Functions ---
        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        // --- Initialize Page Data ---
        totalBalanceEl.textContent = formatRupiah(currentUserBalance);
        sendAmountInput.max = currentUserBalance; // Set max nominal user can send

        // --- Event Listeners ---

        // Open Send Modal
        sendButton.addEventListener('click', () => {
            sendModal.style.display = 'flex';
            sendAmountInput.value = ''; // Clear previous input
            sendAmountInput.max = currentUserBalance; // Update max value in case balance changed
        });

        // Close Send Modal
        closeButton.addEventListener('click', () => {
            sendModal.style.display = 'none';
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', (event) => {
            if (event.target === sendModal) {
                sendModal.style.display = 'none';
            }
        });

        // Handle Send Form Submission
        sendForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const amountToSend = parseFloat(sendAmountInput.value);
            const selectedMethod = sendMethodSelect.value;

            if (isNaN(amountToSend) || amountToSend <= 0) {
                alert("Nominal yang dikirim tidak valid.");
                return;
            }

            if (amountToSend > currentUserBalance) {
                alert("Saldo tidak cukup untuk melakukan pengiriman!");
                return;
            }

            if (selectedMethod === 'investment') {
                const formData = new FormData();
                formData.append('amount', amountToSend);

                try {
                    const response = await fetch('dompet.php', { // Kirim ke file dompet.php itu sendiri
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    alert(result.message);
                    if (result.success) {
                        currentUserBalance = parseFloat(result.newBalance.replace('Rp ', '').replace(/\./g, '').replace(/,/g, '.'));
                        totalBalanceEl.textContent = formatRupiah(currentUserBalance);
                        sendModal.style.display = 'none'; // Close modal
                        // Redirect to investment page or update investment balance display
                        // For now, let's just log and update the wallet balance.
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengirim dana. Silakan coba lagi.');
                }
            } else {
                alert(`Metode pengiriman '${selectedMethod}' belum tersedia. Silakan pilih 'Kirim ke Saldo Investasi'.`);
            }
        });

        // Update nominal max value based on current balance if user tries to enter manually
        sendAmountInput.addEventListener('input', () => {
            if (parseFloat(sendAmountInput.value) > currentUserBalance) {
                sendAmountInput.setCustomValidity("Nominal melebihi saldo yang tersedia!");
            } else {
                sendAmountInput.setCustomValidity("");
            }
        });
    </script>
</body>
</html>
