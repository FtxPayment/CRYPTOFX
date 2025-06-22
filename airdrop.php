<?php
// File: airdrop.php
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

$userInvestmentBalance = $loggedInUser['investmentBalance'] ?? 0;
$hasClaimedAirdrop = $loggedInUser['hasClaimedAirdrop'] ?? false; // Flag di data user
$minInvestmentForAirdrop = 10000; // Rp 10.000

$canClaimAirdrop = ($userInvestmentBalance >= $minInvestmentForAirdrop) && !$hasClaimedAirdrop;
$claimButtonDisabled = !$canClaimAirdrop;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Program Airdrop</title>
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

        /* Airdrop Card */
        .airdrop-card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            text-align: center;
            margin-bottom: 30px;
        }

        .airdrop-card h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .airdrop-card h2 .icon {
            font-size: 1.2em;
            color: var(--accent-color);
        }

        .airdrop-description {
            font-size: 1.1em;
            margin-bottom: 25px;
            color: var(--text-color);
        }

        .airdrop-requirements {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .airdrop-requirements h3 {
            font-size: 1.5em;
            color: #fff;
            margin-bottom: 15px;
            text-align: center;
        }

        .airdrop-requirements ul {
            list-style: none; /* Remove default bullet */
            padding: 0;
        }

        .airdrop-requirements ul li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 1em;
            color: var(--text-color);
        }

        .airdrop-requirements ul li i {
            color: var(--success-color);
            margin-right: 10px;
            font-size: 1.2em;
            width: 25px; /* Fixed width for icon alignment */
            text-align: center;
            flex-shrink: 0;
        }

        /* Upload Form */
        .upload-form {
            text-align: left;
            margin-bottom: 25px;
        }

        .upload-form .form-group {
            margin-bottom: 15px;
        }

        .upload-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
            font-size: 1em;
        }

        .upload-form input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-size: 0.95em;
            cursor: pointer;
        }
        /* Custom styling for file input button if needed, but browser default is often fine */
        .upload-form input[type="file"]::-webkit-file-upload-button {
            background-color: var(--button-primary);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .upload-form input[type="file"]::-webkit-file-upload-button:hover {
            background-color: var(--button-primary-hover);
        }

        .min-investment-info {
            font-size: 1em;
            color: #ffd700; /* Gold for emphasis */
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }
        .info-message.error {
            color: var(--accent-color);
            font-weight: bold;
            text-align: center;
            margin-top: 15px;
        }


        .claim-button {
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
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .claim-button:hover:not(:disabled) {
            background-color: #c7374d; /* Darker accent */
            transform: translateY(-3px);
        }
        .claim-button:disabled {
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
            color: var(--success-color);
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
            .airdrop-card {
                padding: 25px;
                max-width: 550px;
            }
            .airdrop-card h2 {
                font-size: 2em;
                gap: 10px;
            }
            .airdrop-description {
                font-size: 1em;
            }
            .airdrop-requirements h3 {
                font-size: 1.3em;
            }
            .airdrop-requirements ul li {
                font-size: 0.95em;
                align-items: center;
            }
            .upload-form label {
                font-size: 0.95em;
            }
            .upload-form input[type="file"] {
                padding: 8px;
                font-size: 0.9em;
            }
            .min-investment-info {
                font-size: 0.95em;
            }
            .claim-button {
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
            .airdrop-card h2 {
                font-size: 1.8em;
            }
            .airdrop-description {
                font-size: 0.9em;
            }
            .airdrop-requirements ul li {
                font-size: 0.85em;
            }
            .min-investment-info {
                font-size: 0.9em;
            }
            .claim-button {
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
        <div class="airdrop-card">
            <h2><span class="icon"><i class="fas fa-hand-holding-usd"></i></span> Program Airdrop CRYPTOFX</h2>

            <p class="airdrop-description">Dapatkan **0.005 CFX** secara gratis dengan mengikuti program Airdrop kami! Ini adalah kesempatan emas untuk menambah aset kripto Anda tanpa biaya.</p>

            <div class="airdrop-requirements">
                <h3>Syarat Partisipasi</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Bagikan informasi CRYPTOFX ke **5 grup** media sosial atau komunitas kripto.</li>
                    <li><i class="fas fa-check-circle"></i> Miliki saldo investasi minimal **<?php echo formatRupiah($minInvestmentForAirdrop); ?>** di platform kami.</li>
                    <li><i class="fas fa-check-circle"></i> Kirimkan bukti *screenshot* dari setiap *share* Anda.</li>
                </ul>
            </div>

            <div class="upload-form">
                <form id="airdropClaimForm">
                    <?php if ($hasClaimedAirdrop): ?>
                        <p class="info-message error">Anda sudah mengklaim Airdrop ini. Terima kasih!</p>
                        <button type="button" class="claim-button" disabled>Sudah Diklaim</button>
                    <?php elseif ($userInvestmentBalance < $minInvestmentForAirdrop): ?>
                        <p class="info-message error">Saldo investasi Anda harus minimal <?php echo formatRupiah($minInvestmentForAirdrop); ?> untuk bisa klaim.</p>
                        <button type="submit" class="claim-button" id="claimButton" disabled>Kirim Klaim</button>
                    <?php else: ?>
                        <p class="min-investment-info">Lengkapi semua screenshot dan klik kirim klaim.</p>
                        <div class="form-group">
                            <label for="screenshot1">Screenshot Bukti Share 1:</label>
                            <input type="file" id="screenshot1" name="screenshots[]" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label for="screenshot2">Screenshot Bukti Share 2:</label>
                            <input type="file" id="screenshot2" name="screenshots[]" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label for="screenshot3">Screenshot Bukti Share 3:</label>
                            <input type="file" id="screenshot3" name="screenshots[]" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label for="screenshot4">Screenshot Bukti Share 4:</label>
                            <input type="file" id="screenshot4" name="screenshots[]" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label for="screenshot5">Screenshot Bukti Share 5:</label>
                            <input type="file" id="screenshot5" name="screenshots[]" accept="image/*" required>
                        </div>
                        <button type="submit" class="claim-button" id="claimButton" <?php echo $claimButtonDisabled ? 'disabled' : ''; ?>><i class="fas fa-paper-plane"></i> Kirim Klaim</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div id="claimSuccessModal" class="modal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h3><i class="fas fa-check-circle"></i> Klaim Berhasil Dikirim!</h3>
                <p id="modalMessage">Terima kasih! Bukti Anda sedang divalidasi oleh tim CRYPTOFX. Klaim Airdrop Anda akan diproses dalam 1x24 jam.</p>
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
        // DOM Elements
        const airdropClaimForm = document.getElementById('airdropClaimForm');
        const claimButton = document.getElementById('claimButton');
        const fileInputs = document.querySelectorAll('.upload-form input[type="file"]');
        const claimSuccessModal = document.getElementById('claimSuccessModal');
        const closeButton = document.querySelector('#claimSuccessModal .close-button');
        const modalOkButton = document.getElementById('modalOkButton');
        const modalMessageEl = document.getElementById('modalMessage');

        // Initial state from PHP
        const userHasClaimedAirdrop = <?php echo json_encode($hasClaimedAirdrop); ?>;
        const userInvestmentBalance = <?php echo $userInvestmentBalance; ?>;
        const minInvestmentRequired = <?php echo $minInvestmentForAirdrop; ?>;

        // --- Helper Functions ---

        // Function to check if all file inputs have a file selected
        function areAllFilesSelected() {
            let allSelected = true;
            fileInputs.forEach(input => {
                if (input.files.length === 0) {
                    allSelected = false;
                }
            });
            return allSelected;
        }

        function updateClaimButtonState() {
            if (userHasClaimedAirdrop || userInvestmentBalance < minInvestmentRequired) {
                claimButton.disabled = true;
            } else {
                claimButton.disabled = !areAllFilesSelected();
            }
        }

        // --- Event Listeners ---

        // Enable/disable claim button based on file selection
        fileInputs.forEach(input => {
            input.addEventListener('change', updateClaimButtonState);
        });

        // Handle form submission
        airdropClaimForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent default form submission

            if (userHasClaimedAirdrop) {
                alert("Anda sudah mengklaim Airdrop ini.");
                return;
            }
            if (userInvestmentBalance < minInvestmentRequired) {
                alert(`Saldo investasi Anda harus minimal ${formatRupiah(minInvestmentRequired)} untuk bisa klaim Airdrop.`);
                return;
            }
            if (!areAllFilesSelected()) {
                alert("Harap lengkapi semua 5 screenshot bukti share.");
                return;
            }

            const formData = new FormData(airdropClaimForm);
            formData.append('action', 'claim_airdrop');

            // Simulate file upload (frontend won't actually upload to PHP temp folder, just pass names for mock)
            // In a real app, you'd send formData directly. For now, let's keep it simple.
            // const files = Array.from(fileInputs).map(input => input.files[0] ? input.files[0].name : 'no_file');
            // formData.append('fileNames', JSON.stringify(files)); // Example if needed

            try {
                const response = await fetch('airdrop_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                modalMessageEl.textContent = result.message;
                claimSuccessModal.style.display = 'flex'; // Show success modal

                if (result.success) {
                    // Update hasClaimedAirdrop status on client-side to disable button
                    userHasClaimedAirdrop = true;
                    updateClaimButtonState();
                    airdropClaimForm.innerHTML = '<p class="info-message error">Klaim Airdrop Anda telah berhasil diajukan dan sedang dalam proses validasi. Terima kasih!</p>';
                }

            } catch (error) {
                console.error('Error:', error);
                modalMessageEl.textContent = 'mohon tunggu. pihak CRYPTOFX sedang melakukan pengecekan.';
                claimSuccessModal.style.display = 'flex';
            }
        });

        // Close Modal
        closeButton.addEventListener('click', () => {
            claimSuccessModal.style.display = 'none';
        });

        modalOkButton.addEventListener('click', () => {
            claimSuccessModal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === claimSuccessModal) {
                claimSuccessModal.style.display = 'none';
            }
        });

        // Initial state: disable claim button if not all files are selected or conditions not met
        document.addEventListener('DOMContentLoaded', updateClaimButtonState);
    </script>
</body>
</html>
