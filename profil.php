<?php
// File: profil.php
require_once 'config.php';
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
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
    session_destroy();
    header('Location: index.php');
    exit();
}

// Data yang akan ditampilkan
$userName = htmlspecialchars($loggedInUser['fullName']);
$userEmail = htmlspecialchars($loggedInUser['email']);
$userJoinDate = htmlspecialchars($loggedInUser['joinDate']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Profil Pengguna</title>
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
            --warning-bg: #ffe082; /* Light orange for warnings */
            --warning-text: #333; /* Dark text for warnings */
            --button-bg-primary: #0f3460;
            --button-hover-primary: #533483;
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
            align-items: center; /* Center content horizontally */
            justify-content: center; /* Center content vertically if space allows */
        }

        /* Warning Section */
        .warning-message {
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

        /* Profile Card */
        .profile-card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 500px; /* Limit card width for better appearance */
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            text-align: center;
        }

        .profile-card h2 {
            font-size: 2.5em;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* Space between icon and title */
        }

        .profile-card h2 .main-icon {
            font-size: 1.2em;
            color: var(--accent-color);
        }

        .profile-detail {
            display: flex;
            align-items: center;
            background-color: var(--card-bg);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }

        .profile-detail .icon {
            font-size: 1.5em;
            color: var(--accent-color);
            margin-right: 15px;
            width: 30px; /* Fixed width for alignment */
            text-align: center;
        }

        .profile-detail .label {
            font-weight: bold;
            color: var(--text-color);
            margin-right: 10px;
            min-width: 120px; /* Ensure labels align */
            text-align: left;
        }

        .profile-detail .value {
            flex-grow: 1;
            color: #fff;
            text-align: left;
        }

        /* Back Button */
        .back-button {
            background-color: var(--button-bg-primary);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 30px;
            text-decoration: none; /* For anchor tag styling */
            display: inline-flex; /* Align icon and text */
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .back-button:hover {
            background-color: var(--button-hover-primary);
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
            margin-top: auto; /* Push footer to the bottom */
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .warning-message {
                padding: 15px 20px;
                font-size: 0.95em;
            }
            .profile-card {
                padding: 25px;
            }
            .profile-card h2 {
                font-size: 2em;
                flex-direction: column;
                gap: 5px;
            }
            .profile-card h2 .main-icon {
                font-size: 1.1em;
            }
            .profile-detail {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 15px;
            }
            .profile-detail .icon {
                margin-right: 0;
                margin-bottom: 8px;
                width: auto;
                text-align: left;
            }
            .profile-detail .label {
                min-width: unset;
                margin-right: 0;
                margin-bottom: 5px;
            }
            .profile-detail .value {
                font-size: 0.95em;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .warning-message {
                font-size: 0.9em;
            }
            .profile-card h2 {
                font-size: 1.8em;
            }
            .profile-detail {
                padding: 10px;
            }
            .profile-detail .icon {
                font-size: 1.3em;
            }
            .profile-detail .label, .profile-detail .value {
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
        <div class="warning-message">
            <p><i class="fas fa-exclamation-triangle"></i> Pastikan data Anda benar. Perubahan data akun seperti nama atau email hanya bisa dilakukan melalui tim dukungan pelanggan/admin CRYPTOFX.</p>
        </div>

        <div class="profile-card">
            <h2><span class="main-icon"><i class="fas fa-id-badge"></i></span> Profil Pengguna</h2>

            <div class="profile-detail">
                <div class="icon"><i class="fas fa-user"></i></div>
                <div class="label">Nama Lengkap:</div>
                <div class="value" id="userName"><?php echo $userName; ?></div>
            </div>

            <div class="profile-detail">
                <div class="icon"><i class="fas fa-envelope"></i></div>
                <div class="label">Email:</div>
                <div class="value" id="userEmail"><?php echo $userEmail; ?></div>
            </div>

            <div class="profile-detail">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="label">Tanggal Daftar:</div>
                <div class="value" id="userJoinDate"><?php echo $userJoinDate; ?></div>
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
        // JavaScript here remains client-side only, as data is rendered by PHP
    </script>
</body>
</html>
