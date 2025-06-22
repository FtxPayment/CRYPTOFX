<?php
// File: investasi.php
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

$currentInvestmentBalance = $loggedInUser['investmentBalance'] ?? 0;
$initialInvestmentAtStart = $loggedInUser['investmentBalanceAtStart'] ?? 0; // Saldo saat investasi dimulai
$isInvestmentActive = isset($loggedInUser['investmentStartTime']) && $loggedInUser['investmentStartTime'] > 0;
$investmentStartTime = $loggedInUser['investmentStartTime'] ?? 0;
$investmentLastUpdate = $loggedInUser['investmentLastUpdate'] ?? 0;

// Hitung profit yang belum di-apply jika investasi aktif dan ada selisih waktu
$profitAccumulatedSinceLastUpdate = 0;
if ($isInvestmentActive && $investmentStartTime > 0 && $initialInvestmentAtStart > 0 && $investmentLastUpdate > 0) {
    $dailyReturnRate = 0.15; // 15% per hari
    $secondsPerDay = 86400; // 24 * 60 * 60
    $returnPerSecond = ($dailyReturnRate / $secondsPerDay);

    $elapsedSeconds = time() - $investmentLastUpdate;
    $profitAccumulatedSinceLastUpdate = $initialInvestmentAtStart * $returnPerSecond * $elapsedSeconds;
}

$displayedBalance = $currentInvestmentBalance + $profitAccumulatedSinceLastUpdate;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Investasi</title>
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
            --button-start: #28a745; /* Green for start */
            --button-stop: #dc3545; /* Red for stop */
            --button-claim: #007bff; /* Blue for claim */
            --button-hover-darker: #0c2d50; /* Darker hover for general buttons */
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

        /* Investment Summary */
        .investment-summary {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            text-align: center;
            margin-bottom: 30px;
        }

        .investment-summary h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .investment-summary h2 .icon {
            font-size: 1.2em;
            color: var(--accent-color);
        }

        .investment-summary p {
            font-size: 1.2em;
            color: var(--text-color);
        }

        .investment-summary .balance {
            font-size: 3em;
            font-weight: bold;
            color: #ffd700; /* Gold for balance */
            margin-top: 10px;
            display: block;
        }

        /* Control Buttons */
        .investment-controls {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            width: 100%;
            max-width: 600px;
        }

        .investment-controls button {
            background-color: var(--card-bg); /* Default button color */
            color: #fff;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            flex: 1 1 auto; /* Allow buttons to grow and shrink */
            min-width: 180px; /* Minimum width for buttons */
        }

        .investment-controls button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.4);
        }

        .investment-controls button:disabled {
            background-color: #4a4a5a;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .investment-controls button.start-btn { background-color: var(--button-start); }
        .investment-controls button.start-btn:hover:not(:disabled) { background-color: #218838; }

        .investment-controls button.stop-btn { background-color: var(--button-stop); }
        .investment-controls button.stop-btn:hover:not(:disabled) { background-color: #c82333; }

        .investment-controls button.claim-btn { background-color: var(--button-claim); }
        .investment-controls button.claim-btn:hover:not(:disabled) { background-color: #0056b3; }


        /* Status Message */
        .status-message {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            width: 100%;
            max-width: 600px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.1em;
            color: var(--text-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .status-message.success { color: var(--success-color); border-color: var(--success-color); }
        .status-message.error { color: var(--error-color); border-color: var(--error-color); }

        /* Back Button */
        .back-button {
            background-color: var(--button-hover-darker);
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
            margin-top: 20px; /* Adjust spacing as needed */
        }

        .back-button:hover {
            background-color: var(--accent-color); /* A bit brighter on hover */
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
            .investment-summary {
                padding: 25px;
            }
            .investment-summary h2 {
                font-size: 2em;
                flex-direction: column;
                gap: 10px;
            }
            .investment-summary .balance {
                font-size: 2.5em;
            }
            .investment-controls {
                flex-direction: column;
                gap: 15px;
            }
            .investment-controls button {
                width: 100%; /* Full width on small screens */
                min-width: unset;
                padding: 12px 20px;
                font-size: 1em;
            }
            .status-message {
                padding: 15px;
                font-size: 1em;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .investment-summary h2 {
                font-size: 1.8em;
            }
            .investment-summary .balance {
                font-size: 2em;
            }
            .status-message {
                font-size: 0.95em;
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
        <div class="investment-summary">
            <h2><span class="icon"><i class="fas fa-chart-pie"></i></span> Saldo Investasi Anda</h2>
            <p>Total Saldo: <span class="balance" id="currentBalance"><?php echo formatRupiah($displayedBalance); ?></span></p>
        </div>

        <div class="investment-controls">
            <button id="startButton" class="start-btn"><i class="fas fa-play-circle"></i> Mulai Investasi</button>
            <button id="stopButton" class="stop-btn" disabled><i class="fas fa-pause-circle"></i> Berhenti Investasi</button>
            <button id="claimButton" class="claim-btn" disabled><i class="fas fa-wallet"></i> Klaim Investasi</button>
        </div>

        <div class="status-message" id="statusMessage">
            Siap untuk memulai investasi Anda?
        </div>

        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <footer class="footer">
        <p>&copy; 2025 CRYPTOFX. Hak cipta dilindungi undang-undang.</p>
    </footer>

    <script>
        const initialInvestmentAmount = <?php echo $initialInvestmentAtStart; ?>; // Saldo saat investasi DULU dimulai
        let currentInvestmentBalance = <?php echo $currentInvestmentBalance; ?>; // Saldo terakhir dari server
        let investmentActive = <?php echo json_encode($isInvestmentActive); ?>;
        let investmentStartTime = <?php echo $investmentStartTime; ?>; // Unix timestamp
        let lastUpdateTime = <?php echo $investmentLastUpdate; ?>; // Unix timestamp

        const dailyReturnRate = 0.15; // 15% per hari
        const secondsPerDay = 86400; // 24 * 60 * 60
        const returnPerSecond = (dailyReturnRate / secondsPerDay);

        const currentBalanceEl = document.getElementById('currentBalance');
        const startButton = document.getElementById('startButton');
        const stopButton = document.getElementById('stopButton');
        const claimButton = document.getElementById('claimButton');
        const statusMessageEl = document.getElementById('statusMessage');

        let intervalId; // Untuk menyimpan ID interval

        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Fungsi untuk menghitung dan memperbarui profit yang ditampilkan
        function updateDisplayedProfit() {
            if (investmentActive && initialInvestmentAmount > 0) {
                const now = Math.floor(Date.now() / 1000); // Current timestamp in seconds
                // Hitung profit dari investmentStartTime (basisnya saldo awal investasi)
                const elapsedSecondsFromStart = now - investmentStartTime;
                const totalProfitFromStart = initialInvestmentAmount * returnPerSecond * elapsedSecondsFromStart;

                currentBalanceEl.textContent = formatRupiah(initialInvestmentAmount + totalProfitFromStart);
            } else {
                // Jika investasi tidak aktif, tampilkan saldo terakhir dari PHP
                currentBalanceEl.textContent = formatRupiah(currentInvestmentBalance);
            }
        }

        async function sendInvestmentAction(action) {
            const formData = new FormData();
            formData.append('action', action);

            try {
                const response = await fetch('investment_loan_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    currentInvestmentBalance = parseFloat(result.newBalance); // Saldo investasi terbaru dari server
                    investmentActive = result.isInvestmentActive;
                    investmentStartTime = result.startTime || 0; // Update start time dari server
                    lastUpdateTime = result.lastUpdate || 0; // Update last update time dari server

                    if (action === 'start_investment') {
                        // Saat mulai, initialInvestmentAmount jadi saldo terakhir yang dikirim
                        initialInvestmentAmount = currentInvestmentBalance;
                        intervalId = setInterval(updateDisplayedProfit, 1000); // Mulai hitung real-time
                        statusMessageEl.textContent = 'Investasi sedang berjalan...';
                        statusMessageEl.className = 'status-message';
                    } else if (action === 'stop_investment' || action === 'claim_investment') {
                        clearInterval(intervalId); // Hentikan hitungan real-time
                        intervalId = null;
                        initialInvestmentAmount = 0; // Reset agar tidak dihitung lagi dari start
                        statusMessageEl.className = 'status-message success';
                    }

                    updateButtonStates();
                    statusMessageEl.textContent = result.message;
                    updateDisplayedProfit(); // Update display immediately after action

                } else {
                    alert(result.message);
                    statusMessageEl.textContent = result.message;
                    statusMessageEl.className = 'status-message error';
                }
                updateButtonStates(); // Selalu perbarui status tombol
            } catch (error) {
                console.error('Error:', error);
                statusMessageEl.textContent = 'Silahkan Refresh...';
                statusMessageEl.className = 'status-message error';
                updateButtonStates();
            }
        }

        function updateButtonStates() {
            const minInvestmentRequired = 10000; // Minimal investasi Rp 10.000

            if (investmentActive) {
                startButton.disabled = true;
                stopButton.disabled = false;
                claimButton.disabled = true; // Hanya bisa klaim setelah berhenti
            } else {
                startButton.disabled = (currentInvestmentBalance < minInvestmentRequired);
                stopButton.disabled = true;
                // Bisa klaim jika ada profit setelah berhenti, atau jika saldo investasi > 0 (bahkan jika tidak ada profit, bisa diklaim ke main balance)
                claimButton.disabled = (currentInvestmentBalance <= 0 || currentInvestmentBalance <= initialInvestmentAmount); // Hanya bisa klaim jika ada dana di saldo investasi
                
                if (currentInvestmentBalance < minInvestmentRequired) {
                    statusMessageEl.textContent = `Untuk memulai investasi, saldo Anda harus minimal ${formatRupiah(minInvestmentRequired)}.`;
                    statusMessageEl.className = 'status-message error';
                } else if (!investmentActive && initialInvestmentAmount === 0 && currentInvestmentBalance > 0) {
                     statusMessageEl.textContent = 'Siap untuk memulai investasi Anda?';
                     statusMessageEl.className = 'status-message';
                }
            }
        }

        // Event Listeners
        startButton.addEventListener('click', () => {
             const minInvestmentRequired = 10000;
             if (currentInvestmentBalance < minInvestmentRequired) {
                 alert(`Anda perlu memiliki saldo investasi minimal ${formatRupiah(minInvestmentRequired)} untuk memulai investasi.`);
                 return;
             }
             sendInvestmentAction('start_investment');
        });
        stopButton.addEventListener('click', () => sendInvestmentAction('stop_investment'));
        claimButton.addEventListener('click', () => sendInvestmentAction('claim_investment'));

        // Initial setup on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (investmentActive) {
                intervalId = setInterval(updateDisplayedProfit, 1000);
                statusMessageEl.textContent = 'Investasi sedang berjalan...';
                statusMessageEl.className = 'status-message';
            }
            updateDisplayedProfit(); // Tampilkan saldo awal dengan profit terhitung
            updateButtonStates();
        });

        // Clear interval if user leaves the page
        window.addEventListener('beforeunload', () => {
            if (intervalId) {
                clearInterval(intervalId);
            }
        });
    </script>
</body>
</html>
