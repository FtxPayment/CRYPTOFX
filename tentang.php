<?php
// File: tentang.php
require_once 'config.php';
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect ke halaman login jika belum
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang CRYPTOFX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        :root {
            --primary-bg: #1a1a2e; /* Dark background */
            --secondary-bg: #16213e; /* Slightly lighter dark background */
            --accent-color: #e94560; /* Reddish accent */
            --text-color: #e0e0e0; /* Light text */
            --heading-color: #ffffff; /* White for headings */
            --card-bg: #0f3460; /* Dark blue for section cards/boxes */
            --border-color: #0c2d50; /* Darker blue border */
            --link-color: #007bff; /* Blue for links */
            --link-hover-color: #0056b3;
            --disclaimer-bg: #4a1c22; /* Darker red for disclaimer */
            --disclaimer-text: #ffcccc; /* Lighter red for disclaimer text */
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
            line-height: 1.8; /* Increased line height for readability */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        .header h1 {
            font-size: 3.5em;
            color: var(--heading-color);
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.2em;
            color: var(--text-color);
        }

        /* Section Styling */
        .section {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
        }

        .section h2 {
            font-size: 2.2em;
            color: var(--heading-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section h2 .icon {
            color: var(--accent-color);
        }

        .section h3 {
            font-size: 1.6em;
            color: #ffd700; /* Gold for subheadings */ /* Changed from var(--win-color) as it was not defined */
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .section p {
            margin-bottom: 15px;
            text-align: justify; /* Justify text for whitepaper feel */
        }

        .section ul {
            list-style: none; /* Remove default bullets */
            padding-left: 0;
            margin-bottom: 15px;
        }
        .section ul li {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .section ul li i {
            color: var(--accent-color);
            margin-top: 4px; /* Align icon better with text */
            flex-shrink: 0; /* Prevent icon from shrinking */
        }

        /* Roadmap Specific Styling */
        .roadmap-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 15px;
        }
        .roadmap-item .quarter {
            font-size: 1.2em;
            font-weight: bold;
            color: #00bcd4; /* Light blue for info color */ /* Changed from var(--info-color) as it was not defined */
            flex-shrink: 0;
            width: 80px; /* Fixed width for alignment */
        }
        .roadmap-item .description {
            flex-grow: 1;
        }
        .roadmap-item .description strong {
            color: var(--heading-color);
        }

        /* Team Section */
        .team-member {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .team-member .avatar {
            font-size: 2.5em;
            color: var(--accent-color);
        }
        .team-member .info h4 {
            font-size: 1.2em;
            color: var(--heading-color);
            margin-bottom: 5px;
        }
        .team-member .info p {
            font-size: 0.9em;
            color: var(--text-color);
            margin-bottom: 0;
        }

        /* Disclaimer Section */
        .disclaimer-section {
            background-color: var(--disclaimer-bg);
            border: 1px solid var(--accent-color); /* Stronger border for warning */
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            color: var(--disclaimer-text);
        }
        .disclaimer-section h2 {
            color: var(--accent-color);
            border-bottom-color: rgba(233, 69, 96, 0.3);
        }
        .disclaimer-section p {
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        /* Back Button */
        .back-button {
            background-color: #0c2d50; /* Changed from var(--button-toggle-off) as it was not defined */
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            margin-top: 30px;
            margin-bottom: 20px; /* Space from footer */
            align-self: flex-start; /* Align to the left in column flex */
        }

        .back-button:hover {
            background-color: #0056b3; /* Corresponds to var(--link-hover-color) */
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
            margin-top: auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .header h1 {
                font-size: 2.5em;
            }
            .header p {
                font-size: 1em;
            }
            .section {
                padding: 20px;
                margin-bottom: 20px;
            }
            .section h2 {
                font-size: 1.8em;
                margin-bottom: 15px;
            }
            .section h3 {
                font-size: 1.4em;
                margin-top: 20px;
                margin-bottom: 10px;
            }
            .section p, .section ul li {
                font-size: 0.95em;
            }
            .roadmap-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            .roadmap-item .quarter {
                width: auto;
                font-size: 1.1em;
                margin-bottom: 5px;
            }
            .team-member {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }
            .team-member .avatar {
                margin-bottom: 10px;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 2em;
            }
            .section h2 {
                font-size: 1.5em;
            }
            .section h3 {
                font-size: 1.2em;
            }
            .section p, .section ul li, .team-member .info p, .disclaimer-section p {
                font-size: 0.9em;
            }
            .roadmap-item .description {
                font-size: 0.9em;
            }
            .team-member .avatar {
                font-size: 2em;
            }
            .team-member .info h4 {
                font-size: 1.1em;
            }
            .back-button {
                padding: 8px 15px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <header class="header">
            <h1>Tentang CRYPTOFX</h1>
            <p>Memimpin revolusi keuangan terdesentralisasi dengan keamanan, transparansi, dan kemudahan.</p>
        </header>

        <section class="section">
            <h2><i class="fas fa-info-circle icon"></i> 1. Pendahuluan</h2>
            <p>CRYPTOFX adalah platform inovatif yang dibangun di atas fondasi teknologi blockchain, dirancang untuk memberdayakan individu dalam ekosistem keuangan terdesentralisasi (DeFi). Misi kami adalah menyediakan platform investasi dan pinjaman aset kripto yang tidak hanya aman dan transparan, tetapi juga mudah diakses oleh semua kalangan, dari investor berpengalaman hingga pemula.</p>
            <p>Kami percaya bahwa masa depan finansial terletak pada desentralisasi, di mana setiap orang memiliki kontrol penuh atas aset mereka dan akses ke peluang keuangan yang adil. CRYPTOFX hadir sebagai jembatan yang menghubungkan Anda dengan potensi tak terbatas dari dunia kripto.</p>
        </section>

        <section class="section">
            <h2><i class="fas fa-cogs icon"></i> 2. Fitur Utama</h2>
            <p>CRYPTOFX menawarkan serangkaian fitur komprehensif yang dirancang untuk memenuhi kebutuhan finansial Anda di dunia kripto:</p>
            <ul>
                <li><i class="fas fa-check-circle"></i> <strong>Investasi dengan Return 15% per Hari:</strong> Manfaatkan peluang pasar kripto dengan program investasi kami yang menawarkan potensi return hingga 15% per hari dari saldo investasi Anda. Sistem kami dirancang untuk mengoptimalkan keuntungan Anda dengan perhitungan real-time.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Pinjaman Fleksibel dengan Bunga Kompetitif:</strong> Ajukan pinjaman dengan mudah menggunakan aset kripto Anda sebagai jaminan. Kami menawarkan bunga yang kompetitif dan tenor yang fleksibel, disesuaikan dengan kebutuhan finansial Anda. Proses pengajuan cepat dan transparan.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Dompet Kripto Aman & Efisien:</strong> Kelola berbagai aset kripto Anda dalam dompet digital yang aman dan terenkripsi. Lakukan pengiriman dan penerimaan dana dengan cepat dan mudah, terintegrasi langsung dengan fitur platform lainnya.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Fitur Swap Token Instan:</strong> Konversi token CFX Anda ke mata uang fiat (IDR) atau aset kripto lainnya secara instan. Nikmati kemudahan transaksi dengan kurs yang transparan dan kompetitif.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Program Airdrop & Hadiah:</strong> Partisipasi dalam program airdrop kami untuk mendapatkan token CFX gratis, serta uji keberuntungan Anda di program hadiah menarik seperti "Slide Spin" atau "Buka Kotak" dengan berbagai hadiah menanti.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Keamanan & Validasi Admin:</strong> Keamanan aset dan data pengguna adalah prioritas utama kami. Kami menerapkan standar keamanan tinggi seperti enkripsi data, otentikasi multi-faktor, dan tim admin yang berdedikasi untuk memvalidasi setiap transaksi dan klaim, memastikan lingkungan yang aman dan terpercaya.</li>
            </ul>
        </section>

        <section class="section">
            <h2><i class="fas fa-coins icon"></i> 3. Tokenomics</h2>
            <h3>Token CFX: Jantung Ekosistem CRYPTOFX</h3>
            <p>Token CFX adalah aset digital asli yang menjadi tulang punggung ekosistem CRYPTOFX. Dirancang dengan utilitas yang kuat, CFX tidak hanya berfungsi sebagai alat tukar tetapi juga sebagai pendorong partisipasi dan pertumbuhan komunitas. Pengguna dapat memperoleh CFX melalui program airdrop, hadiah, atau berpartisipasi aktif dalam platform.</p>
            <p>Peran CFX meliputi:</p>
            <ul>
                <li><i class="fas fa-dot-circle"></i> **Biaya Transaksi:** Digunakan untuk membayar biaya layanan tertentu di platform.</li>
                <li><i class="fas fa-dot-circle"></i> **Hadiah & Insentif:** Menjadi imbalan dalam berbagai program insentif dan hadiah.</li>
                <li><i class="fas fa-dot-circle"></i> **Akses Fitur Eksklusif:** Potensi untuk membuka akses ke fitur-fitur premium atau tingkat layanan yang lebih tinggi di masa depan.</li>
            </ul>
        </section>

        <section class="section">
            <h2><i class="fas fa-map-signs icon"></i> 4. Roadmap</h2>
            <p>Berikut adalah rencana pengembangan CRYPTOFX untuk tahun ini:</p>
            <h3>Roadmap 2025</h3>
            <div class="roadmap-item">
                <div class="quarter">Q1 2025</div>
                <div class="description">
                    <strong>Fase Konseptualisasi & Fondasi</strong>
                    <p>Pembentukan tim inti, riset pasar mendalam, dan perancangan arsitektur platform. Pengembangan prototipe dasar untuk fitur investasi dan dompet.</p>
                </div>
            </div>
            <div class="roadmap-item">
                <div class="quarter">Q2 2025</div>
                <div class="description">
                    <strong>Pengembangan & Pengujian Internal</strong>
                    <p>Implementasi fitur utama (Investasi, Pinjaman, Dompet, Swap). Pengujian internal intensif untuk stabilitas dan keamanan. Peluncuran Alpha Test.</p>
                </div>
            </div>
            <div class="roadmap-item">
                <div class="quarter">Q3 2025</div>
                <div class="description">
                    <strong>Peluncuran Publik & Program Komunitas</strong>
                    <p>Peluncuran resmi platform CRYPTOFX. Kampanye pemasaran awal. Peluncuran program Airdrop dan fitur Game Hadiah.</p>
                </div>
            </div>
            <div class="roadmap-item">
                <div class="quarter">Q4 2025</div>
                <div class="description">
                    <strong>Ekspansi & Peningkatan Fitur</strong>
                    <p>Integrasi aset kripto baru, peningkatan kapasitas pinjaman, dan optimalisasi fitur yang ada. Penjajakan kemitraan strategis.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2><i class="fas fa-users icon"></i> 5. Tim Developer</h2>
            <p>CRYPTOFX didukung oleh tim profesional berdedikasi dengan pengalaman luas di bidang teknologi blockchain, keuangan, dan pengembangan perangkat lunak.</p>
            <div class="team-member">
                <div class="avatar"><i class="fas fa-user-circle"></i></div>
                <div class="info">
                    <h4>John Doe</h4>
                    <p>CEO & Pendiri</p>
                    <p>Ahli Strategi Blockchain</p>
                </div>
            </div>
            <div class="team-member">
                <div class="avatar"><i class="fas fa-user-circle"></i></div>
                <div class="info">
                    <h4>Jane Smith</h4>
                    <p>CTO</p>
                    <p>Arsitek Sistem & Keamanan</p>
                </div>
            </div>
            <div class="team-member">
                <div class="avatar"><i class="fas fa-user-circle"></i></div>
                <div class="info">
                    <h4>Michael Brown</h4>
                    <p>Lead Developer</p>
                    <p>Spesialis Smart Contract</p>
                </div>
            </div>
        </section>

        <section class="disclaimer-section">
            <h2><i class="fas fa-exclamation-triangle icon"></i> Disclaimer & Risiko Investasi</h2>
            <p>Investasi dalam aset kripto memiliki risiko yang melekat dan signifikan. Harga aset kripto sangat fluktuatif dan dapat mengalami penurunan nilai yang drastis, yang berpotensi menyebabkan kerugian finansial yang substansial. Anda harus memahami bahwa nilai investasi Anda dapat berfluktuasi naik atau turun.</p>
            <p>CRYPTOFX tidak memberikan saran investasi, keuangan, hukum, atau pajak. Semua informasi yang disediakan di platform ini hanya untuk tujuan informasi umum. Anda bertanggung jawab penuh untuk melakukan riset Anda sendiri dan/atau mencari saran dari penasihat keuangan profesional sebelum membuat keputusan investasi apa pun.</p>
            <p>Dengan menggunakan platform CRYPTOFX, Anda mengakui dan menerima risiko yang terkait dengan investasi kripto. Berinvestasilah hanya dengan dana yang Anda mampu untuk kehilangan.</p>
        </section>

        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <footer class="footer">
        <p>&copy; 2025 CRYPTOFX. Hak cipta dilindungi undang-undang.</p>
    </footer>

    <script>
        // Tidak ada JavaScript interaktif yang spesifik diperlukan untuk halaman statis ini.
    </script>
</body>
</html>
