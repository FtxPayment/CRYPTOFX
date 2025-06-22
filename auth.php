<?php
// File: auth.php
require_once 'config.php';

session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi input dasar
    if (empty($email) || empty($password)) {
        $response['message'] = 'Email dan password harus diisi.';
        echo json_encode($response);
        exit();
    }

    $users = readJsonFile(USERS_FILE);

    // Tambahkan user admin default jika belum ada dan jika ini adalah registrasi pertama kali (atau cek sederhana)
    // Logika ini akan dijalankan saat ada upaya register/login, memastikan admin ada.
    $adminExists = false;
    foreach ($users as $user) {
        if ($user['email'] === 'Ibnuadmin@cryptofx.com') {
            $adminExists = true;
            break;
        }
    }

    if (!$adminExists) {
        $adminUser = [
            'id' => uniqid('admin_'), // ID unik untuk admin
            'fullName' => 'Ibnu Admin',
            'email' => 'Ibnuadmin@cryptofx.com',
            'password' => password_hash('Ibnumaelan123', PASSWORD_DEFAULT),
            'mainBalance' => 100000000, // Contoh saldo admin
            'investmentBalance' => 50000000,
            'cryptoBalance' => 10.00000,
            'joinDate' => date('d F Y'),
            'hasInvested' => true,
            'isAdmin' => true,
            'isSuspended' => false,
            'investmentStartTime' => 0, // Pastikan ada properti ini
            'investmentLastUpdate' => 0,
            'investmentBalanceAtStart' => 0 // Untuk melacak saldo awal saat investasi dimulai
        ];
        $users[] = $adminUser;
        writeJsonFile(USERS_FILE, $users); // Simpan admin user
    }

    if ($action === 'register') {
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        if (empty($confirmPassword) || $password !== $confirmPassword) {
            $response['message'] = 'Konfirmasi password tidak cocok.';
            echo json_encode($response);
            exit();
        }

        // Cek apakah email sudah terdaftar
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $response['message'] = 'Email sudah terdaftar.';
                echo json_encode($response);
                exit();
            }
        }

        // Tambahkan user baru
        $newUser = [
            'id' => uniqid('user_'),
            'fullName' => 'Pengguna Baru', // Akan diupdate di halaman profil jika perlu
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT), // Hash password
            'mainBalance' => 0,
            'investmentBalance' => 0,
            'cryptoBalance' => 0.00000, // Saldo CFX
            'joinDate' => date('d F Y'),
            'hasInvested' => false, // Untuk validasi pinjaman
            'isAdmin' => false,
            'isSuspended' => false,
            'investmentStartTime' => 0, // Untuk fitur investasi
            'investmentLastUpdate' => 0,
            'investmentBalanceAtStart' => 0, // Saldo awal saat investasi dimulai
            'hasClaimedAirdrop' => false // Untuk fitur airdrop
        ];

        $users[] = $newUser;
        writeJsonFile(USERS_FILE, $users);

        $response['success'] = true;
        $response['message'] = 'Registrasi berhasil! Silakan login.';

    } elseif ($action === 'login') {
        $foundUser = null;
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $foundUser = $user;
                break;
            }
        }

        if ($foundUser && password_verify($password, $foundUser['password'])) {
            if ($foundUser['isSuspended']) {
                $response['message'] = 'Akun Anda telah disuspend. Silakan hubungi Tim CRYPTOFX.';
            } else {
                $_SESSION['user_id'] = $foundUser['id'];
                $_SESSION['user_email'] = $foundUser['email'];
                $_SESSION['is_admin'] = $foundUser['isAdmin'];

                $response['success'] = true;
                $response['message'] = 'Login berhasil!';
                $response['redirect'] = $foundUser['isAdmin'] ? 'admin_dashboard.php' : 'dashboard.php';
            }
        } else {
            $response['message'] = 'Email atau password salah.';
        }
    } else {
        $response['message'] = 'Aksi tidak dikenal.';
    }
}

echo json_encode($response);
?>
