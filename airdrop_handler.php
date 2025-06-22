<?php
// File: airdrop_handler.php
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
    case 'claim_airdrop':
        $minInvestmentForAirdrop = 10000; // Rp 10.000
        $airdropAmountCFX = 0.005;

        if ($loggedInUser['hasClaimedAirdrop'] ?? false) {
            $response['message'] = 'Anda sudah mengklaim Airdrop ini.';
            break;
        }

        if (($loggedInUser['investmentBalance'] ?? 0) < $minInvestmentForAirdrop) {
            $response['message'] = 'Saldo investasi Anda harus minimal ' . formatRupiah($minInvestmentForAirdrop) . ' untuk bisa klaim Airdrop.';
            break;
        }

        // Validasi file (minimal 5 screenshot)
        if (!isset($_FILES['screenshots']) || count($_FILES['screenshots']['name']) < 5) {
            $response['message'] = 'Anda harus mengunggah 5 screenshot bukti share.';
            break;
        }

        $uploadedFileNames = [];
        $uploadDir = DATA_DIR . 'airdrop_screenshots/'; // Subfolder untuk screenshot
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmp_name) {
            $file_name = uniqid() . '_' . basename($_FILES['screenshots']['name'][$key]);
            $target_file = $uploadDir . $file_name;
            if (move_uploaded_file($tmp_name, $target_file)) {
                $uploadedFileNames[] = $file_name;
            } else {
                $response['message'] = 'Gagal mengunggah beberapa file. Coba lagi.';
                // Hapus file yang sudah terunggah jika ada kegagalan
                foreach($uploadedFileNames as $f) {
                    unlink($uploadDir . $f);
                }
                echo json_encode($response);
                exit();
            }
        }

        // Tandai user sudah klaim dan catat klaim airdrop
        $users[$userIndex]['hasClaimedAirdrop'] = true;
        $users[$userIndex]['airdropClaimStatus'] = 'pending'; // Status pending untuk divalidasi admin
        writeJsonFile(USERS_FILE, $users);

        $airdropClaims = readJsonFile(AIRDROPS_FILE); // Catat klaim di file terpisah
        $airdropClaims[] = [
            'id' => uniqid(),
            'userId' => $loggedInUser['id'],
            'email' => $loggedInUser['email'],
            'amountCFX' => $airdropAmountCFX,
            'screenshots' => $uploadedFileNames,
            'claimDate' => date('Y-m-d H:i:s'),
            'status' => 'pending' // Status untuk admin dashboard
        ];
        writeJsonFile(AIRDROPS_FILE, $airdropClaims);

        $response['success'] = true;
        $response['message'] = 'Klaim Airdrop berhasil dikirim! Menunggu validasi admin.';
        break;

    default:
        $response['message'] = 'Aksi airdrop tidak dikenal.';
        break;
}

echo json_encode($response);
?>
