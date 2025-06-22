<?php
// File: pinjaman.php
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

$hasInvested = $loggedInUser['hasInvested'] ?? false;
$userEmail = $loggedInUser['email']; // Untuk mencatat di data pinjaman

// Logika untuk menangani permintaan AJAX (cek limit & ajukan pinjaman)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $action = $_POST['action'] ?? '';

    if ($action === 'check_limit') {
        if (!$hasInvested) {
            $response['message'] = 'Anda harus memulai investasi terlebih dahulu sebelum bisa mengajukan pinjaman.';
            echo json_encode($response);
            exit();
        }

        // Simulasikan limit pinjaman sekali pakai
        $loanLimitFound = null;
        $loans = readJsonFile(LOANS_FILE);
        foreach ($loans as $loan) {
            if ($loan['userId'] === $loggedInUser['id'] && ($loan['status'] === 'approved' || $loan['status'] === 'pending')) {
                $loanLimitFound = $loan['limit']; // Ini bisa diubah sesuai logika bisnis, misal hanya 1x pinjam
                break;
            }
        }

        if ($loanLimitFound !== null) {
            // Jika sudah pernah mengajukan dan statusnya approved/pending, limit tidak akan diberikan lagi (untuk simulasi 1x pakai)
            $response['message'] = 'Anda sudah memiliki atau sedang dalam proses pinjaman.';
            $response['loanLimit'] = $loanLimitFound;
            $response['success'] = true;
            $response['existingLoan'] = true; // Flag untuk frontend
        } else {
            // Generate limit baru jika belum ada pinjaman aktif/pending
            $userLoanLimit = mt_rand(50000, 1000000); // 50rb - 1jt
            $userLoanLimit = floor($userLoanLimit / 10000) * 10000; // Kelipatan 10 ribu

            // Simpan limit ini sementara atau langsung buat entri pinjaman pending
            // Untuk simulasi ini, kita langsung kembalikan dan biarkan user ajukan
            // Dalam sistem nyata, ini bisa disimpan di session atau tabel khusus
            $response['success'] = true;
            $response['message'] = 'Limit pinjaman ditemukan.';
            $response['loanLimit'] = $userLoanLimit;
        }

    } elseif ($action === 'submit_loan') {
        $loanAmount = filter_input(INPUT_POST, 'loanAmount', FILTER_VALIDATE_FLOAT);
        $tenor = filter_input(INPUT_POST, 'tenor', FILTER_VALIDATE_INT);
        $loanPurpose = filter_input(INPUT_POST, 'loanPurpose', FILTER_SANITIZE_STRING);

        if ($loanAmount === false || $loanAmount <= 0 || $tenor === false || $tenor <= 0 || empty($loanPurpose)) {
            $response['message'] = 'Harap lengkapi semua data pinjaman dengan benar.';
            echo json_encode($response);
            exit();
        }

        if (!$hasInvested) {
            $response['message'] = 'Anda harus memulai investasi terlebih dahulu sebelum bisa mengajukan pinjaman.';
            echo json_encode($response);
            exit();
        }

        // Simulasi: cek apakah ada pinjaman yang pending/approved
        $loans = readJsonFile(LOANS_FILE);
        $existingLoan = false;
        foreach ($loans as $loan) {
            if ($loan['userId'] === $loggedInUser['id'] && ($loan['status'] === 'approved' || $loan['status'] === 'pending')) {
                $existingLoan = true;
                break;
            }
        }

        if ($existingLoan) {
            $response['message'] = 'Anda sudah memiliki atau sedang dalam proses pinjaman aktif.';
            echo json_encode($response);
            exit();
        }

        // Dapatkan bunga
        $interestRate = 0;
        if ($tenor >= 1 && $tenor <= 6) {
            $interestRate = 0.09; // 9%
        } elseif ($tenor >= 9 && $tenor <= 12) {
            $interestRate = 0.13; // 13%
        }

        $totalPayment = $loanAmount * (1 + $interestRate);
        $monthlyPayment = $totalPayment / $tenor;

        // Simpan data pinjaman
        $newLoan = [
            'id' => uniqid(),
            'userId' => $loggedInUser['id'],
            'email' => $loggedInUser['email'],
            'amount' => $loanAmount,
            'tenor' => $tenor,
            'purpose' => $loanPurpose,
            'interestRate' => $interestRate,
            'totalPayment' => $totalPayment,
            'monthlyPayment' => $monthlyPayment,
            'status' => 'pending', // Status awal pending
            'requestDate' => date('Y-m-d H:i:s')
        ];
        $loans[] = $newLoan;
        writeJsonFile(LOANS_FILE, $loans);

        $response['success'] = true;
        $response['message'] = 'Pengajuan pinjaman berhasil! Menunggu persetujuan admin.';

    } else {
        $response['message'] = 'Aksi tidak dikenal.';
    }

    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Pinjaman Kripto</title>
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
            --button-check-limit: #28a745; /* Green for check limit */
            --button-check-limit-hover: #218838;
            --warning-bg: #ffe082; /* Light orange for warnings */
            --warning-text: #333; /* Dark text for warnings */
            --info-bg: #d1ecf1; /* Light blue for info */
            --info-text: #0c5460; /* Dark blue for info text */
            --disabled-color: #4a4a5a;
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

        /* Top Warning */
        .loan-warning {
            background-color: var(--warning-bg);
            color: var(--warning-text);
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            width: 100%;
            text-align: center;
            font-weight: 600;
            font-size: 1.05em;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 1px solid rgba(255, 200, 0, 0.5);
        }

        /* Loan Card / Form */
        .loan-card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            text-align: left;
            margin-bottom: 30px;
        }

        .loan-card h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 25px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .loan-card h2 .icon {
            font-size: 1.2em;
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1em;
            font-weight: bold;
            color: var(--text-color);
        }

        .form-group input[type="text"],
        .form-group input[type="range"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-size: 1em;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.3);
        }

        .form-group input[type="range"] {
            -webkit-appearance: none;
            height: 8px;
            background: var(--border-color);
            border-radius: 5px;
            outline: none;
            margin-top: 5px;
        }

        .form-group input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: var(--accent-color);
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .form-group input[type="range"]::-webkit-slider-thumb:hover {
            background: #c7374d;
        }

        .range-value {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.2em;
            color: #ffd700; /* Gold for value */
        }

        .loan-card button {
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

        .loan-card button:hover:not(:disabled) {
            background-color: var(--button-primary-hover);
            transform: translateY(-3px);
        }

        .loan-card button:disabled {
            background-color: var(--disabled-color);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        #checkLimitBtn {
            background-color: var(--button-check-limit);
            margin-bottom: 20px;
            width: 100%;
        }
        #checkLimitBtn:hover:not(:disabled) {
            background-color: var(--button-check-limit-hover);
        }

        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Loan Details */
        .loan-details {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
            margin-top: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            width: 100%;
        }

        .loan-details h3 {
            font-size: 1.8em;
            color: #fff;
            margin-bottom: 20px;
            text-align: center;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item .label {
            font-weight: bold;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-item .value {
            color: #fff;
            font-weight: 600;
        }
        .detail-item.total .value {
            color: #ffd700;
            font-size: 1.2em;
        }


        /* Platform Info / Notification */
        .platform-info {
            background-color: var(--info-bg);
            color: var(--info-text);
            padding: 20px 25px;
            border-radius: 8px;
            margin-top: 30px;
            width: 100%;
            text-align: center;
            font-size: 1em;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 1px solid rgba(172, 218, 227, 0.5);
        }

        .platform-info .icon {
            margin-right: 10px;
            color: var(--info-text);
        }


        /* Popup Warning */
        .popup-warning {
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

        .popup-content {
            background-color: var(--secondary-bg);
            margin: auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            position: relative;
            animation: fadeInScale 0.3s ease-out;
            text-align: center;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .popup-content h3 {
            color: var(--accent-color);
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .popup-content p {
            font-size: 1.1em;
            margin-bottom: 25px;
        }

        .popup-content button {
            background-color: var(--accent-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .popup-content button:hover {
            background-color: #c7374d;
        }

        /* Back Button */
        .back-button {
            background-color: var(--button-check-limit-hover); /* A bit darker for general buttons */
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
            background-color: var(--button-primary-hover);
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
            .loan-warning, .platform-info {
                padding: 15px 20px;
                font-size: 0.95em;
            }
            .loan-card {
                padding: 25px;
            }
            .loan-card h2 {
                font-size: 2em;
                flex-direction: column;
                gap: 10px;
            }
            .form-group label {
                font-size: 1em;
            }
            .form-group input, .form-group select {
                padding: 10px;
            }
            .range-value {
                font-size: 1.1em;
            }
            #checkLimitBtn, .loan-card button {
                padding: 12px 20px;
                font-size: 1em;
            }
            .loan-details h3 {
                font-size: 1.5em;
            }
            .detail-item {
                font-size: 0.95em;
            }
            .popup-content {
                padding: 20px;
            }
            .popup-content h3 {
                font-size: 1.5em;
            }
            .popup-content p {
                font-size: 1em;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .loan-warning, .platform-info {
                font-size: 0.85em;
            }
            .loan-card h2 {
                font-size: 1.8em;
            }
            .detail-item .label, .detail-item .value {
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
        <div class="loan-warning">
            <p><i class="fas fa-exclamation-triangle"></i> Mengambil pinjaman adalah tanggung jawab finansial. Pastikan Anda memahami semua syarat dan ketentuan sebelum mengajukan pinjaman.</p>
        </div>

        <div class="loan-card">
            <h2><span class="icon"><i class="fas fa-hand-holding-usd"></i></span> Ajukan Pinjaman</h2>

            <div class="form-group">
                <button id="checkLimitBtn" <?php echo $hasInvested ? '' : 'disabled'; ?>><span id="checkLimitText"><i class="fas fa-search-dollar"></i> Cek Limit Pinjaman</span></button>
                <p id="loanLimitDisplay" class="range-value" style="display: none;">Limit Pinjaman: Rp 0,00</p>
            </div>

            <div id="loanInputSection" style="display: none;">
                <div class="form-group">
                    <label for="loanAmount">Jumlah Pinjaman:</label>
                    <input type="range" id="loanAmount" min="0" max="1000000" value="0" step="10000" disabled>
                    <div id="loanAmountValue" class="range-value">Rp 0,00</div>
                </div>

                <div class="form-group">
                    <label for="tenor">Tenor (Durasi Pinjaman):</label>
                    <select id="tenor" disabled>
                        <option value="">Pilih Tenor</option>
                        <option value="1">1 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="9">9 Bulan</option>
                        <option value="12">12 Bulan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="loanPurpose">Tujuan Pinjaman:</label>
                    <input type="text" id="loanPurpose" placeholder="Contoh: Modal Usaha, Kebutuhan Mendesak" disabled>
                </div>

                <div class="loan-details">
                    <h3>Detail Pinjaman</h3>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-money-bill-wave"></i> Jumlah Dipinjam:</span>
                        <span class="value" id="detailAmount">Rp 0,00</span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-percent"></i> Bunga (%):</span>
                        <span class="value" id="detailInterest">0%</span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-money-bill-alt"></i> Total Cicilan:</span>
                        <span class="value total" id="detailTotalPayment">Rp 0,00</span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="fas fa-calendar-alt"></i> Estimasi Cicilan/Bulan:</span>
                        <span class="value total" id="detailMonthlyPayment">Rp 0,00</span>
                    </div>
                </div>

                <button id="submitLoanBtn" disabled><i class="fas fa-paper-plane"></i> Ajukan Pinjaman</button>
            </div>
        </div>

        <div class="platform-info">
            <p><i class="fas fa-info-circle"></i> cek limit pinjaman akan memberikan anda limit pinjaman jika anda sudah mulai berinvestasi & Pinjaman Anda akan diproses dalam 1x24 jam kerja setelah pengajuan disetujui. Riwayat pinjaman akan tersedia di menu Dompet Anda.</p>
        </div>

        <div id="investmentWarningPopup" class="popup-warning">
            <div class="popup-content">
                <h3><i class="fas fa-exclamation-circle"></i> Peringatan!</h3>
                <p>Maaf, Anda harus memulai investasi terlebih dahulu sebelum bisa mengajukan pinjaman.</p>
                <button id="closePopup">Mengerti</button>
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
        // Global variables for elements
        const checkLimitBtn = document.getElementById('checkLimitBtn');
        const checkLimitText = document.getElementById('checkLimitText');
        const loanLimitDisplay = document.getElementById('loanLimitDisplay');
        const loanInputSection = document.getElementById('loanInputSection');
        const loanAmountSlider = document.getElementById('loanAmount');
        const loanAmountValue = document.getElementById('loanAmountValue');
        const tenorSelect = document.getElementById('tenor');
        const loanPurposeInput = document.getElementById('loanPurpose');
        const submitLoanBtn = document.getElementById('submitLoanBtn');

        const detailAmount = document.getElementById('detailAmount');
        const detailInterest = document.getElementById('detailInterest');
        const detailTotalPayment = document.getElementById('detailTotalPayment');
        const detailMonthlyPayment = document.getElementById('detailMonthlyPayment');

        const investmentWarningPopup = document.getElementById('investmentWarningPopup');
        const closePopupButton = document.getElementById('closePopup');

        let userLoanLimit = 0;
        let hasCheckedLimit = false;
        let hasInvested = <?php echo json_encode($hasInvested); ?>; // From PHP

        // Helper function to format currency
        function formatRupiah(amount) {
            return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        // --- Event Listeners ---

        // Check Limit Button Click
        checkLimitBtn.addEventListener('click', async () => {
            if (!hasInvested) {
                investmentWarningPopup.style.display = 'flex';
                return;
            }
            if (hasCheckedLimit) {
                return; // Prevent multiple clicks
            }

            checkLimitText.innerHTML = '<span class="loading-spinner"></span> Menghitung Limit...';
            checkLimitBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'check_limit');

            try {
                const response = await fetch('investment_loan_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    userLoanLimit = result.loanLimit;
                    loanLimitDisplay.textContent = `Limit Pinjaman Anda: ${formatRupiah(userLoanLimit)}`;
                    loanLimitDisplay.style.display = 'block';

                    if (result.existingLoan) {
                         alert(result.message);
                         checkLimitText.innerHTML = '<i class="fas fa-search-dollar"></i> Limit Ditemukan!';
                         // Disable further input if loan already exists
                         loanAmountSlider.disabled = true;
                         tenorSelect.disabled = true;
                         loanPurposeInput.disabled = true;
                         submitLoanBtn.disabled = true;
                         loanInputSection.style.display = 'block'; // Show section to display existing limit
                         hasCheckedLimit = true;
                         return; // Exit after showing existing loan message
                    }

                    loanAmountSlider.max = userLoanLimit;
                    loanAmountSlider.value = 0; // Reset value
                    loanAmountSlider.disabled = false;
                    loanAmountValue.textContent = formatRupiah(0); // Reset display

                    tenorSelect.disabled = false;
                    loanPurposeInput.disabled = false;
                    loanInputSection.style.display = 'block';
                    checkLimitText.innerHTML = '<i class="fas fa-search-dollar"></i> Limit Ditemukan!';
                    hasCheckedLimit = true;
                    updateLoanDetails(); // Update initial loan details

                } else {
                    alert(result.message);
                    checkLimitText.innerHTML = '<i class="fas fa-search-dollar"></i> Cek Limit Pinjaman';
                    checkLimitBtn.disabled = false; // Re-enable if failed
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memeriksa limit. Silakan coba lagi.');
                checkLimitText.innerHTML = '<i class="fas fa-search-dollar"></i> Cek Limit Pinjaman';
                checkLimitBtn.disabled = false; // Re-enable on error
            }
        });

        // Loan Amount Slider Change
        loanAmountSlider.addEventListener('input', () => {
            loanAmountValue.textContent = formatRupiah(loanAmountSlider.value);
            updateLoanDetails();
        });

        // Tenor Select Change
        tenorSelect.addEventListener('change', () => {
            updateLoanDetails();
        });

        // Purpose Input (Optional: Can be used to enable/disable submit button)
        loanPurposeInput.addEventListener('input', () => {
             updateSubmitButtonState();
        });

        // Submit Loan Button Click
        submitLoanBtn.addEventListener('click', async () => {
            if (!hasInvested) {
                investmentWarningPopup.style.display = 'flex';
                return;
            }

            const loanAmount = parseFloat(loanAmountSlider.value);
            const tenor = parseInt(tenorSelect.value);
            const loanPurpose = loanPurposeInput.value;

            const formData = new FormData();
            formData.append('action', 'submit_loan');
            formData.append('loanAmount', loanAmount);
            formData.append('tenor', tenor);
            formData.append('loanPurpose', loanPurpose);

            try {
                const response = await fetch('investment_loan_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                alert(result.message);
                if (result.success) {
                    resetLoanForm(); // Reset form after simulated submission
                }
            } catch (error) {
                console.error('Error:', error);
                alert('pinjaman sedang dalam proses verifikasi mohon tunggu');
            }
        });

        // Close Popup Button
        closePopupButton.addEventListener('click', () => {
            investmentWarningPopup.style.display = 'none';
        });

        // Close popup when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === investmentWarningPopup) {
                investmentWarningPopup.style.display = 'none';
            }
        });

        // --- Functions for Loan Logic ---

        function getInterestRate(tenor) {
            if (tenor >= 1 && tenor <= 6) {
                return 0.09; // 9%
            } else if (tenor >= 9 && tenor <= 12) {
                return 0.13; // 13%
            }
            return 0; // Default if tenor is not selected or invalid
        }

        function calculateTotalPayment(amount, tenor, interestRate) {
            return amount * (1 + interestRate);
        }

        function calculateMonthlyPayment(totalPayment, tenor) {
            if (tenor > 0) {
                return totalPayment / tenor;
            }
            return 0;
        }

        function updateLoanDetails() {
            const loanAmount = parseFloat(loanAmountSlider.value);
            const tenor = parseInt(tenorSelect.value);
            const interestRate = getInterestRate(tenor);

            const totalPayment = calculateTotalPayment(loanAmount, tenor, interestRate);
            const monthlyPayment = calculateMonthlyPayment(totalPayment, tenor);

            detailAmount.textContent = formatRupiah(loanAmount);
            detailInterest.textContent = (interestRate * 100) + '%';
            detailTotalPayment.textContent = formatRupiah(totalPayment);
            detailMonthlyPayment.textContent = formatRupiah(monthlyPayment);

            updateSubmitButtonState();
        }

        function updateSubmitButtonState() {
            const loanAmount = parseFloat(loanAmountSlider.value);
            const tenor = parseInt(tenorSelect.value);
            const loanPurpose = loanPurposeInput.value.trim();

            // Enable submit button only if limit is checked, amount > 0, tenor selected, and purpose filled
            if (hasCheckedLimit && loanAmount > 0 && !isNaN(tenor) && tenor > 0 && loanPurpose.length > 0) {
                submitLoanBtn.disabled = false;
            } else {
                submitLoanBtn.disabled = true;
            }
        }

        function resetLoanForm() {
            userLoanLimit = 0;
            hasCheckedLimit = false;
            checkLimitBtn.disabled = !hasInvested; // Re-enable if hasInvested is true
            checkLimitText.innerHTML = '<i class="fas fa-search-dollar"></i> Cek Limit Pinjaman';
            loanLimitDisplay.style.display = 'none';
            loanInputSection.style.display = 'none'; // Hide input section again
            loanAmountSlider.value = 0;
            loanAmountSlider.disabled = true;
            loanAmountValue.textContent = formatRupiah(0);
            tenorSelect.value = '';
            tenorSelect.disabled = true;
            loanPurposeInput.value = '';
            loanPurposeInput.disabled = true;
            submitLoanBtn.disabled = true;

            // Reset loan details display
            detailAmount.textContent = formatRupiah(0);
            detailInterest.textContent = '0%';
            detailTotalPayment.textContent = formatRupiah(0);
            detailMonthlyPayment.textContent = formatRupiah(0);
        }

        // Initial update when page loads
        document.addEventListener('DOMContentLoaded', () => {
            resetLoanForm(); // Ensure all fields are reset on load
            // Check if user has already an active/pending loan on load
            // This would require another AJAX call or passing more data from PHP initially
            // For now, it will only prevent new applications after 'check limit' is clicked
        });
    </script>
</body>
</html>
