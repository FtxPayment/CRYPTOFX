<?php
// File: swap_withdrawal_handler.php
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

switch ($action) {
    case 'perform_swap':
        $cfxAmount = filter_input(INPUT_POST, 'cfxAmount', FILTER_VALIDATE_FLOAT);
        $idrAmount = filter_input(INPUT_POST, 'idrAmount', FILTER_VALIDATE_FLOAT);

        if ($cfxAmount === false || $cfxAmount <= 0 || $idrAmount === false || $idrAmount <= 0) {
            $response['message'] = 'Jumlah swap tidak valid.';
            break;
        }

        if ($cfxAmount > $loggedInUser['cryptoBalance']) {
            $response['message'] = 'Saldo CFX tidak cukup untuk melakukan swap.';
            break;
        }

        $CFX_TO_IDR_RATE = 5987011;
        $calculatedIDR = $cfxAmount * $CFX_TO_IDR_RATE;

        // Beri toleransi sedikit untuk floating point atau pembulatan di frontend
        if (abs($idrAmount - $calculatedIDR) > 100) { // Toleransi Rp 100
             $response['message'] = 'Jumlah IDR yang dikirim tidak sesuai dengan konversi CFX.';
             break;
        }

        // Kurangi CFX, tambahkan IDR ke mainBalance
        $users[$userIndex]['cryptoBalance'] -= $cfxAmount;
        $users[$userIndex]['mainBalance'] += $idrAmount;
        writeJsonFile(USERS_FILE, $users);

        // Catat di riwayat swap
        $swaps = readJsonFile(SWAPS_FILE);
        $swaps[] = [
            'id' => uniqid(),
            'userId' => $loggedInUser['id'],
            'email' => $loggedInUser['email'],
            'fromToken' => 'CFX',
            'amountFrom' => $cfxAmount,
            'toToken' => 'IDR',
            'amountTo' => $idrAmount,
            'date' => date('Y-m-d H:i:s')
        ];
        writeJsonFile(SWAPS_FILE, $swaps);

        $response['success'] = true;
        $response['message'] = 'Swap berhasil! Saldo dompet Anda telah diperbarui.';
        $response['newCfxBalance'] = $users[$userIndex]['cryptoBalance'];
        $response['newIdrBalance'] = $users[$userIndex]['mainBalance'];
        break;

    case 'submit_withdrawal':
        $nominal = filter_input(INPUT_POST, 'nominal', FILTER_VALIDATE_FLOAT);
        $method = filter_input(INPUT_POST, 'method', FILTER_SANITIZE_STRING);
        $accountNumber = filter_input(INPUT_POST, 'accountNumber', FILTER_SANITIZE_STRING);
        $accountName = filter_input(INPUT_POST, 'accountName', FILTER_SANITIZE_STRING);
        $fee = filter_input(INPUT_POST, 'fee', FILTER_VALIDATE_FLOAT);
        $totalReceived = filter_input(INPUT_POST, 'totalReceived', FILTER_VALIDATE_FLOAT);

        $MIN_WITHDRAWAL_NOMINAL = 50000; // Rp 50.000
        $ADMIN_FEE = 5000; // Rp 5.000
        $WITHDRAWAL_FEE_PERCENTAGE = 0.10; // 10%

        if ($nominal === false || $nominal < $MIN_WITHDRAWAL_NOMINAL || empty($method) || empty($accountNumber) || empty($accountName)) {
            $response['message'] = 'Data penarikan tidak valid atau tidak lengkap.';
            break;
        }

        if ($nominal > $loggedInUser['mainBalance']) {
            $response['message'] = 'Saldo Anda tidak cukup untuk penarikan ini.';
            break;
        }

        // Hitung ulang di backend untuk keamanan
        $calculatedFee = $nominal * $WITHDRAWAL_FEE_PERCENTAGE;
        $calculatedTotalReceived = $nominal - $calculatedFee - $ADMIN_FEE;

        if (abs($fee - $calculatedFee) > 1 || abs($totalReceived - $calculatedTotalReceived) > 1) { // Toleransi pembulatan
            $response['message'] = 'Ada perbedaan perhitungan biaya. Coba lagi.';
            // Untuk debugging, bisa tampilkan:
            // $response['debug'] = ['sentFee' => $fee, 'calcFee' => $calculatedFee, 'sentTotal' => $totalReceived, 'calcTotal' => $calculatedTotalReceived];
            break;
        }

        if ($calculatedTotalReceived <= 0) {
            $response['message'] = 'Nominal yang diterima setelah biaya tidak boleh nol atau negatif.';
            break;
        }

        // Kurangi saldo pengguna
        $users[$userIndex]['mainBalance'] -= $nominal;
        writeJsonFile(USERS_FILE, $users);

        // Catat di riwayat penarikan
        $withdrawals = readJsonFile(WITHDRAWALS_FILE);
        $withdrawals[] = [
            'id' => uniqid(),
            'userId' => $loggedInUser['id'],
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
        writeJsonFile(WITHDRAWALS_FILE, $withdrawals);

        $response['success'] = true;
        $response['message'] = 'Permintaan penarikan berhasil diajukan. Menunggu persetujuan admin.';
        $response['newBalance'] = $users[$userIndex]['mainBalance'];
        break;

    default:
        $response['message'] = 'Aksi tidak dikenal.';
        break;
}

echo json_encode($response);
?>
