<?php
// File: admin_data_api.php
require_once 'config.php';
session_start();

header('Content-Type: application/json');
$response = [];

// Pastikan hanya admin yang bisa mengakses API ini
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['error'] = 'Akses ditolak. Anda bukan admin.';
    echo json_encode($response);
    exit();
}

$dataType = $_GET['data'] ?? '';

switch ($dataType) {
    case 'users':
        $users = readJsonFile(USERS_FILE);
        // Tambahkan data investasi, pinjaman dll ke user jika diperlukan untuk tampilan komprehensif
        $response = $users;
        break;
    case 'investments':
        // Ambil semua data user untuk menampilkan saldo investasi mereka
        $allUsers = readJsonFile(USERS_FILE);
        $investmentData = [];
        foreach ($allUsers as $user) {
            if ($user['investmentBalance'] > 0 || (isset($user['investmentStartTime']) && $user['investmentStartTime'] > 0)) {
                $investmentData[] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'initialInvestmentAmount' => $user['investmentBalanceAtStart'] ?? 0, // Perlu disimpan saat mulai investasi
                    'currentInvestmentBalance' => $user['investmentBalance'],
                    'investmentStartTime' => $user['investmentStartTime'] ?? 0,
                    'investmentLastUpdate' => $user['investmentLastUpdate'] ?? 0,
                ];
            }
        }
        $response = $investmentData;
        break;
    case 'loans':
        $response = readJsonFile(LOANS_FILE);
        break;
    case 'swaps':
        $response = readJsonFile(SWAPS_FILE);
        break;
    case 'topups':
        $response = readJsonFile(TOPUPS_FILE);
        break;
    case 'withdrawals':
        $response = readJsonFile(WITHDRAWALS_FILE);
        break;
    case 'airdrops':
        $response = readJsonFile(AIRDROPS_FILE);
        break;
    default:
        $response['error'] = 'Jenis data tidak dikenal.';
        break;
}

echo json_encode($response);
?>
