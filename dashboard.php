<?php
// File: dashboard.php
require_once 'config.php';
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect ke halaman login jika belum
    exit();
}

$users = readJsonFile(USERS_FILE);
$loggedInUser = null;
foreach ($users as $user) {
    if ($user['id'] === $_SESSION['user_id']) {
        $loggedInUser = $user;
        break;
    }
}

if (!$loggedInUser) {
    // Jika user tidak ditemukan (mungkin data JSON korup), logout
    session_destroy();
    header('Location: index.php');
    exit();
}

// Redirect admin ke admin_dashboard
if ($loggedInUser['isAdmin']) {
    header('Location: admin_dashboard.php');
    exit();
}

$userNameDisplay = htmlspecialchars($loggedInUser['fullName']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Dashboard Pengguna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        :root {
            --primary-bg: #1a1a2e; /* Dark background */
            --secondary-bg: #16213e; /* Slightly lighter dark background */
            --accent-color: #e94560; /* Reddish accent */
            --text-color: #e0e0e0; /* Light text */
            --card-bg: #0f3460; /* Dark blue for cards/menus */
            --card-hover: #533483; /* Purple hover for cards */
            --border-color: #0c2d50; /* Darker blue border */
            --warning-bg: #ffe082; /* Light orange for warnings */
            --warning-text: #333; /* Dark text for warnings */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex-grow: 1; /* Allow container to grow and push footer down */
        }

        /* Header Welcome Message */
        .welcome-header {
            background-color: var(--secondary-bg);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 30px;
        }

        .welcome-header h1 {
            font-size: 2.8em;
            color: #fff;
            margin-bottom: 10px;
        }

        .welcome-header p {
            font-size: 1.1em;
            color: var(--text-color);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Dashboard Menu Grid */
        .dashboard-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .menu-item {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px 15px;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 140px; /* Ensure consistent height */
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .menu-item:hover {
            background-color: var(--card-hover);
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
        }

        .menu-item .icon {
            font-size: 3.5em;
            margin-bottom: 15px;
            color: var(--accent-color);
            transition: color 0.3s ease;
        }

        .menu-item:hover .icon {
            color: #ffd700; /* Gold on hover for icons */
        }

        .menu-item h3 {
            font-size: 1.2em;
            font-weight: 600;
            color: #fff;
        }

        /* Warning Section */
        .warning-section {
            background-color: var(--warning-bg);
            color: var(--warning-text);
            padding: 25px;
            border-radius: 8px;
            margin-top: 50px;
            border: 1px solid rgba(255, 200, 0, 0.5);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .warning-section h2 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #c7374d; /* Reddish for emphasis */
            text-align: center;
        }

        .warning-section p {
            font-size: 1em;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .warning-section ul {
            list-style: disc inside;
            padding-left: 20px;
        }

        .warning-section ul li {
            margin-bottom: 8px;
        }

        /* Logout Button */
        .logout-button {
            background-color: var(--accent-color);
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
            margin: 30px auto 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            text-decoration: none;
        }
        .logout-button:hover {
            background-color: #c7374d;
            transform: translateY(-3px);
        }


        /* Footer */
        .footer {
            background-color: var(--secondary-bg);
            color: var(--text-color);
            text-align: center;
            padding: 30px 20px;
            border-top: 2px solid var(--border-color);
            font-size: 0.9em;
            margin-top: auto; /* Push footer to the bottom */
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .welcome-header h1 {
                font-size: 2.2em;
            }
            .welcome-header p {
                font-size: 1em;
            }
            .dashboard-menu {
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
                gap: 15px;
            }
            .menu-item {
                padding: 20px 10px;
                min-height: 120px;
            }
            .menu-item .icon {
                font-size: 3em;
                margin-bottom: 10px;
            }
            .menu-item h3 {
                font-size: 1.1em;
            }
            .warning-section {
                padding: 20px;
            }
            .warning-section h2 {
                font-size: 1.5em;
            }
        }

        @media (max-width: 480px) {
            .welcome-header {
                padding: 30px 15px;
            }
            .welcome-header h1 {
                font-size: 1.8em;
            }
            .dashboard-menu {
                grid-template-columns: repeat(2, 1fr); /* 2 columns on very small screens */
                gap: 10px;
            }
            .menu-item {
                padding: 15px 8px;
                min-height: 100px;
            }
            .menu-item .icon {
                font-size: 2.5em;
                margin-bottom: 8px;
            }
            .menu-item h3 {
                font-size: 1em;
            }
            .warning-section {
                padding: 15px;
                font-size: 0.9em;
            }
            .warning-section p, .warning-section ul li {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <header class="welcome-header">
        <h1>Selamat Datang, <?php echo $userNameDisplay; ?>!</h1>
        <p>Jelajahi fitur dashboard Anda untuk mengelola investasi, pinjaman, dan aset kripto Anda dengan mudah dan aman.</p>
    </header>

    <div class="container">
        <div class="dashboard-menu">
            <a href="profil.php" class="menu-item">
                <div class="icon"><i class="fas fa-user-circle"></i></div>
                <h3>Profil</h3>
            </a>
            <a href="investasi.php" class="menu-item">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <h3>Investasi</h3>
            </a>
            <a href="pinjaman.php" class="menu-item">
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                <h3>Pinjaman</h3>
            </a>
            <a href="dompet.php" class="menu-item">
                <div class="icon"><i class="fas fa-wallet"></i></div>
                <h3>Dompet</h3>
            </a>
            <a href="swap.php" class="menu-item">
                <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                <h3>Swap</h3>
            </a>
            <a href="airdrop.php" class="menu-item">
                <div class="icon"><i class="fas fa-gift"></i></div>
                <h3>Airdrop</h3>
            </a>
            <a href="topup.php" class="menu-item">
                <div class="icon"><i class="fas fa-plus-circle"></i></div>
                <h3>TopUp</h3>
            </a>
            <a href="penarikan.php" class="menu-item">
                <div class="icon"><i class="fas fa-money-check-alt"></i></div>
                <h3>Penarikan</h3>
            </a>
            <a href="#" class="menu-item" id="rewardsMenu">
                <div class="icon"><i class="fas fa-medal"></i></div>
                <h3>Hadiah</h3>
            </a>
            <a href="tentang.php" class="menu-item">
                <div class="icon"><i class="fas fa-info-circle"></i></div>
                <h3>Tentang Project Saya</h3>
            </a>
        </div>

        <div class="warning-section">
            <h2>Peringatan Penting untuk Pengguna</h2>
            <p>Harap baca dan pahami hal-hal berikut sebelum melakukan aktivitas di platform CRYPTOFX:</p>
            <ul>
                <li>**Risiko Investasi**: Investasi dalam aset kripto memiliki risiko yang signifikan. Harga aset kripto sangat fluktuatif dan bisa mengalami penurunan nilai yang drastis. Berinvestasilah hanya dengan dana yang Anda siap untuk kehilangan.</li>
                <li>**Perlindungan Data**: Jaga kerahasiaan informasi akun Anda, termasuk password dan otentikasi dua faktor (2FA). CRYPTOFX tidak akan pernah meminta password Anda melalui email atau telepon.</li>
                <li>**Verifikasi Alamat Dompet**: Selalu verifikasi alamat dompet tujuan Anda sebelum melakukan transaksi penarikan atau transfer. Kesalahan alamat dapat mengakibatkan kehilangan aset permanen.</li>
                <li>**Pajak**: Anda bertanggung jawab penuh atas kewajiban pajak yang timbul dari aktivitas investasi dan keuntungan yang diperoleh di platform ini, sesuai dengan peraturan perundang-undangan yang berlaku di yurisdiksi Anda.</li>
                <li>**Kebijakan Penggunaan**: Dengan menggunakan platform kami, Anda menyetujui semua Syarat dan Ketentuan serta Kebijakan Privasi yang berlaku. Harap tinjau secara berkala untuk pembaruan.</li>
            </ul>
            <p>Tim CRYPTOFX berkomitmen untuk menyediakan lingkungan yang aman, namun keputusan investasi dan keamanan akun tetap menjadi tanggung jawab Anda.</p>
        </div>

        <a href="logout.php" class="logout-button">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <footer class="footer">
        <p>&copy; 2025 CRYPTOFX. Hak cipta dilindungi undang-undang.</p>
    </footer>

    <script>
        document.getElementById('rewardsMenu').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Sistem Hadiah sedang dalam tahap maintenance. Mohon maaf atas ketidaknyamanannya.');
        });

        document.getElementById('airdropMenu').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Fitur Airdrop akan segera hadir!'); // Placeholder for Airdrop feature
        });

        document.getElementById('aboutProjectMenu').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Informasi Tentang Project akan segera ditambahkan!'); // Placeholder for About Project
        });
    </script>
</body>
</html>
