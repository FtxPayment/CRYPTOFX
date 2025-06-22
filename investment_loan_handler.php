<?php
// File: investment_loan_handler.php
require_once 'config.php';
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi tidak valid. Harap login kembali.';
    echo json_encode($response);
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
    $response['message'] = 'Data pengguna tidak ditemukan.';
    session_destroy();
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';

// Constants for investment calculation
$dailyReturnRate = 0.15; // 15% per hari
$secondsPerDay = 86400; // 24 * 60 * 60
$returnPerSecond = ($dailyReturnRate / $secondsPerDay);

switch ($action) {
    case 'start_investment':
        $initialInvestment = $loggedInUser['investmentBalance'] ?? 0;
        $minInvestment = 10000; // Minimal investasi Rp 10.000

        if ($initialInvestment < $minInvestment) {
            $response['message'] = 'Saldo investasi Anda harus minimal ' . formatRupiah($minInvestment) . ' untuk memulai investasi.';
            break;
        }

        if (isset($loggedInUser['investmentStartTime']) && $loggedInUser['investmentStartTime'] > 0) {
            $response['message'] = 'Investasi sudah berjalan.';
            break;
        }

        $users[$userIndex]['investmentStartTime'] = time(); // Waktu mulai investasi (timestamp)
        $users[$userIndex]['investmentLastUpdate'] = time(); // Waktu terakhir update profit
        $users[$userIndex]['hasInvested'] = true; // Tandai bahwa user sudah mulai investasi
        $users[$userIndex]['investmentBalanceAtStart'] = $initialInvestment; // Simpan saldo awal saat investasi dimulai

        writeJsonFile(USERS_FILE, $users);

        $response['success'] = true;
        $response['message'] = 'Investasi berhasil dimulai!';
        $response['newBalance'] = $initialInvestment; // Mengembalikan saldo investasi awal
        $response['isInvestmentActive'] = true;
        $response['startTime'] = $users[$userIndex]['investmentStartTime']; // Mengembalikan waktu mulai
        $response['lastUpdate'] = $users[$userIndex]['investmentLastUpdate'];
        break;

    case 'stop_investment':
        if (!isset($loggedInUser['investmentStartTime']) || $loggedInUser['investmentStartTime'] === 0) {
            $response['message'] = 'Investasi belum dimulai.';
            break;
        }

        $investmentBalanceAtStart = $loggedInUser['investmentBalanceAtStart'] ?? 0;
        if ($investmentBalanceAtStart <= 0) {
            $response['message'] = 'Data investasi tidak valid untuk dihentikan. Silakan hubungi dukungan.';
            break;
        }

        // Hitung profit yang belum diperbarui
        $elapsedSeconds = time() - $loggedInUser['investmentLastUpdate'];
        $profitEarned = $investmentBalanceAtStart * $returnPerSecond * $elapsedSeconds;

        $users[$userIndex]['investmentBalance'] += $profitEarned; // Tambahkan profit ke saldo investasi
        $users[$userIndex]['investmentStartTime'] = 0; // Hentikan investasi
        $users[$userIndex]['investmentLastUpdate'] = time(); // Update terakhir
        // investmentBalanceAtStart tidak direset di sini, agar bisa digunakan untuk perhitungan klaim

        writeJsonFile(USERS_FILE, $users);

        $response['success'] = true;
        $response['message'] = 'Investasi berhasil dihentikan. Profit telah ditambahkan ke saldo investasi Anda.';
        $response['newBalance'] = $users[$userIndex]['investmentBalance']; // Saldo total setelah profit
        $response['isInvestmentActive'] = false;
        $response['lastUpdate'] = $users[$userIndex]['investmentLastUpdate'];
        $response['startTime'] = 0;
        break;

    case 'claim_investment':
        if (isset($loggedInUser['investmentStartTime']) && $loggedInUser['investmentStartTime'] > 0) {
            $response['message'] = 'Harap hentikan investasi terlebih dahulu sebelum mengklaim.';
            break;
        }

        $investmentBalance = $loggedInUser['investmentBalance'] ?? 0;
        $investmentBalanceAtStart = $loggedInUser['investmentBalanceAtStart'] ?? 0;

        // Jika investasi belum pernah dimulai atau sudah diklaim
        if ($investmentBalance <= 0 && $investmentBalanceAtStart <= 0) {
             $response['message'] = 'Tidak ada saldo investasi yang bisa diklaim.';
             break;
        }

        // Pindahkan saldo investasi ke mainBalance
        $users[$userIndex]['mainBalance'] += $investmentBalance; // Pindahkan seluruh saldo investasi
        $users[$userIndex]['investmentBalance'] = 0; // Reset saldo investasi
        $users[$userIndex]['hasInvested'] = false; // Reset status investasi
        $users[$userIndex]['investmentStartTime'] = 0; // Pastikan status berhenti
        $users[$userIndex]['investmentLastUpdate'] = 0; // Pastikan status berhenti
        $users[$userIndex]['investmentBalanceAtStart'] = 0; // Reset saldo awal investasi

        writeJsonFile(USERS_FILE, $users);

        $response['success'] = true;
        $response['message'] = 'Saldo investasi sebesar ' . formatRupiah($investmentBalance) . ' berhasil diklaim ke dompet utama.';
        $response['newBalance'] = $users[$userIndex]['investmentBalance']; // Saldo investasi baru (0)
        $response['isInvestmentActive'] = false;
        $response['lastUpdate'] = 0;
        $response['startTime'] = 0;
        break;

    case 'check_limit':
        if (!($loggedInUser['hasInvested'] ?? false)) {
            $response['message'] = 'Anda harus memulai investasi terlebih dahulu sebelum bisa mengajukan pinjaman.';
            echo json_encode($response);
            exit();
        }

        // Cek apakah ada pinjaman aktif atau pending untuk user ini
        $loans = readJsonFile(LOANS_FILE);
        $existingLoan = null;
        foreach ($loans as $loan) {
            if ($loan['userId'] === $loggedInUser['id'] && ($loan['status'] === 'approved' || $loan['status'] === 'pending')) {
                $existingLoan = $loan;
                break;
            }
        }

        if ($existingLoan) {
            $response['message'] = 'Anda sudah memiliki atau sedang dalam proses pinjaman.';
            $response['loanLimit'] = $existingLoan['amount']; // Tampilkan nominal pinjaman yang sudah ada
            $response['success'] = true;
            $response['existingLoan'] = true;
        } else {
            // Generate limit baru
            $userLoanLimit = mt_rand(50000, 1000000); // 50rb - 1jt
            $userLoanLimit = floor($userLoanLimit / 10000) * 10000; // Kelipatan 10 ribu

            $response['success'] = true;
            $response['message'] = 'Limit pinjaman ditemukan.';
            $response['loanLimit'] = $userLoanLimit;
        }
        break;

    case 'submit_loan':
        $loanAmount = filter_input(INPUT_POST, 'loanAmount', FILTER_VALIDATE_FLOAT);
        $tenor = filter_input(INPUT_POST, 'tenor', FILTER_VALIDATE_INT);
        $loanPurpose = filter_input(INPUT_POST, 'loanPurpose', FILTER_SANITIZE_STRING);

        if ($loanAmount === false || $loanAmount <= 0 || $tenor === false || $tenor <= 0 || empty($loanPurpose)) {
            $response['message'] = 'Harap lengkapi semua data pinjaman dengan benar.';
            break;
        }

        if (!($loggedInUser['hasInvested'] ?? false)) {
            $response['message'] = 'Anda harus memulai investasi terlebih dahulu sebelum bisa mengajukan pinjaman.';
            break;
        }

        // Cek kembali jika sudah ada pinjaman aktif/pending
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
            break;
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
        break;

    default:
        $response['message'] = 'Aksi tidak dikenal.';
        break;
}

echo json_encode($response);
?>
