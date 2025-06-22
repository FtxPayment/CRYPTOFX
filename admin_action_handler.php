<?php
// File: admin_action_handler.php
require_once 'config.php';
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Aksi tidak valid.'];

// Pastikan hanya admin yang bisa mengakses handler ini
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Akses ditolak. Anda bukan admin.';
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$itemId = $_POST['itemId'] ?? '';
$userId = $_POST['userId'] ?? null; // Digunakan untuk aksi airdrop
$amount = $_POST['amount'] ?? null; // Digunakan untuk aksi airdrop

if (empty($action) || empty($itemId)) {
    echo json_encode($response);
    exit();
}

switch ($action) {
    case 'toggle_suspend_user':
        $users = readJsonFile(USERS_FILE);
        foreach ($users as $key => $user) {
            if ($user['id'] === $itemId) {
                $users[$key]['isSuspended'] = !($user['isSuspended'] ?? false);
                writeJsonFile(USERS_FILE, $users);
                $status = $users[$key]['isSuspended'] ? 'disuspend' : 'diaktifkan kembali';
                $response['success'] = true;
                $response['message'] = "Pengguna {$user['email']} berhasil {$status}.";
                break 2;
            }
        }
        $response['message'] = 'Pengguna tidak ditemukan.';
        break;

    case 'approve_loan':
        $loans = readJsonFile(LOANS_FILE);
        foreach ($loans as $key => $loan) {
            if ($loan['id'] === $itemId && $loan['status'] === 'pending') {
                $loans[$key]['status'] = 'approved';
                writeJsonFile(LOANS_FILE, $loans);

                // --- Logika Penambahan Saldo ke User Setelah Pinjaman Disetujui ---
                $users = readJsonFile(USERS_FILE);
                foreach ($users as $userKey => $user) {
                    if ($user['id'] === $loan['userId']) {
                        $users[$userKey]['mainBalance'] += $loan['amount']; // Tambahkan jumlah pinjaman ke saldo utama
                        writeJsonFile(USERS_FILE, $users);
                        break;
                    }
                }
                // --- End Logika Penambahan Saldo ---

                $response['success'] = true;
                $response['message'] = 'Pinjaman berhasil disetujui.';
                break 2;
            }
        }
        $response['message'] = 'Pengajuan pinjaman tidak ditemukan atau sudah diproses.';
        break;

    case 'reject_loan':
        $loans = readJsonFile(LOANS_FILE);
        foreach ($loans as $key => $loan) {
            if ($loan['id'] === $itemId && $loan['status'] === 'pending') {
                $loans[$key]['status'] = 'rejected';
                writeJsonFile(LOANS_FILE, $loans);
                $response['success'] = true;
                $response['message'] = 'Pinjaman berhasil ditolak.';
                break 2;
            }
        }
        $response['message'] = 'Pengajuan pinjaman tidak ditemukan atau sudah diproses.';
        break;

    case 'approve_topup':
        $topups = readJsonFile(TOPUPS_FILE);
        foreach ($topups as $key => $topup) {
            if ($topup['id'] === $itemId && $topup['status'] === 'pending') {
                $topups[$key]['status'] = 'approved';
                writeJsonFile(TOPUPS_FILE, $topups);

                // --- Logika Penambahan Saldo ke User Setelah TopUp Disetujui ---
                $users = readJsonFile(USERS_FILE);
                foreach ($users as $userKey => $user) {
                    if ($user['id'] === $topup['userId']) { // topup.php belum menyimpan userId, perlu diupdate
                        $users[$userKey]['mainBalance'] += $topup['nominal'];
                        writeJsonFile(USERS_FILE, $users);
                        break;
                    }
                }
                // --- End Logika Penambahan Saldo ---

                $response['success'] = true;
                $response['message'] = 'Top Up berhasil disetujui.';
                break 2;
            }
        }
        $response['message'] = 'Permintaan Top Up tidak ditemukan atau sudah diproses.';
        break;

    case 'reject_topup':
        $topups = readJsonFile(TOPUPS_FILE);
        foreach ($topups as $key => $topup) {
            if ($topup['id'] === $itemId && $topup['status'] === 'pending') {
                $topups[$key]['status'] = 'rejected';
                writeJsonFile(TOPUPS_FILE, $topups);
                $response['success'] = true;
                $response['message'] = 'Top Up berhasil ditolak.';
                break 2;
            }
        }
        $response['message'] = 'Permintaan Top Up tidak ditemukan atau sudah diproses.';
        break;

    case 'approve_withdrawal':
        $withdrawals = readJsonFile(WITHDRAWALS_FILE);
        foreach ($withdrawals as $key => $withdrawal) {
            if ($withdrawal['id'] === $itemId && $withdrawal['status'] === 'pending') {
                $withdrawals[$key]['status'] = 'approved';
                writeJsonFile(WITHDRAWALS_FILE, $withdrawals);

                // Catatan: Saldo user sudah dikurangi di penarikan.php saat pengajuan.
                // Jika ingin saldo dikurangi saat admin approve, maka logika di penarikan.php perlu diubah.
                // Saat ini, diasumsikan pengurangan saldo terjadi saat user mengajukan.

                $response['success'] = true;
                $response['message'] = 'Penarikan berhasil disetujui.';
                break 2;
            }
        }
        $response['message'] = 'Permintaan penarikan tidak ditemukan atau sudah diproses.';
        break;

    case 'reject_withdrawal':
        $withdrawals = readJsonFile(WITHDRAWALS_FILE);
        foreach ($withdrawals as $key => $withdrawal) {
            if ($withdrawal['id'] === $itemId && $withdrawal['status'] === 'pending') {
                $withdrawals[$key]['status'] = 'rejected';
                writeJsonFile(WITHDRAWALS_FILE, $withdrawals);

                // --- Logika Pengembalian Saldo ke User Setelah Penarikan Ditolak ---
                // Penting: Saldo user sudah dikurangi di penarikan.php saat pengajuan.
                // Jadi, jika ditolak, saldo harus dikembalikan.
                $users = readJsonFile(USERS_FILE);
                foreach ($users as $userKey => $user) {
                    if ($user['id'] === $withdrawal['userId']) { // topup.php belum menyimpan userId, perlu diupdate
                        $users[$userKey]['mainBalance'] += $withdrawal['nominal']; // Kembalikan nominal penuh
                        writeJsonFile(USERS_FILE, $users);
                        break;
                    }
                }
                // --- End Logika Pengembalian Saldo ---

                $response['success'] = true;
                $response['message'] = 'Penarikan berhasil ditolak. Saldo dikembalikan ke pengguna.';
                break 2;
            }
        }
        $response['message'] = 'Permintaan penarikan tidak ditemukan atau sudah diproses.';
        break;

    case 'approve_airdrop':
        $airdrops = readJsonFile(AIRDROPS_FILE);
        foreach ($airdrops as $key => $airdrop) {
            if ($airdrop['id'] === $itemId && $airdrop['status'] === 'pending') {
                $airdrops[$key]['status'] = 'approved';
                writeJsonFile(AIRDROPS_FILE, $airdrops);

                // Tambahkan token CFX ke saldo user
                $users = readJsonFile(USERS_FILE);
                foreach ($users as $userKey => $user) {
                    if ($user['id'] === $userId) { // userId dikirim dari JS
                        $users[$userKey]['cryptoBalance'] += (float)$amount; // amount (CFX) dikirim dari JS
                        writeJsonFile(USERS_FILE, $users);
                        break;
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Klaim Airdrop berhasil disetujui. Token CFX telah dikirim.';
                break 2;
            }
        }
        $response['message'] = 'Klaim Airdrop tidak ditemukan atau sudah diproses.';
        break;

    case 'reject_airdrop':
        $airdrops = readJsonFile(AIRDROPS_FILE);
        foreach ($airdrops as $key => $airdrop) {
            if ($airdrop['id'] === $itemId && $airdrop['status'] === 'pending') {
                $airdrops[$key]['status'] = 'rejected';
                // Opsional: Reset hasClaimedAirdrop di user jika ingin mereka bisa klaim lagi
                $users = readJsonFile(USERS_FILE);
                foreach ($users as $userKey => $user) {
                    if ($user['id'] === $airdrop['userId']) {
                        $users[$userKey]['hasClaimedAirdrop'] = false; // Izinkan klaim lagi jika ditolak
                        $users[$userKey]['airdropClaimStatus'] = ''; // Reset status
                        writeJsonFile(USERS_FILE, $users);
                        break;
                    }
                }
                writeJsonFile(AIRDROPS_FILE, $airdrops);
                $response['success'] = true;
                $response['message'] = 'Klaim Airdrop berhasil ditolak.';
                break 2;
            }
        }
        $response['message'] = 'Klaim Airdrop tidak ditemukan atau sudah diproses.';
        break;

    default:
        $response['message'] = 'Aksi tidak dikenal.';
        break;
}

echo json_encode($response);
?>
