<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRYPTOFX - Investasi & Pinjaman</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        :root {
            --primary-bg: #1a1a2e; /* Dark background */
            --secondary-bg: #16213e; /* Slightly lighter dark background */
            --accent-color: #e94560; /* Reddish accent */
            --text-color: #e0e0e0; /* Light text */
            --button-bg: #0f3460; /* Dark blue button */
            --button-hover: #533483; /* Purple hover */
            --border-color: #0f3460; /* Dark blue border */
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
            overflow-x: hidden; /* Prevent horizontal scroll due to animations */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header / Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-bg) 0%, var(--secondary-bg) 100%);
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom: 2px solid var(--border-color);
        }

        .hero-content {
            z-index: 1;
            padding: 20px;
            max-width: 800px;
        }

        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero p {
            font-size: 1.2em;
            margin-bottom: 30px;
            color: var(--text-color);
        }

        .hero-buttons .btn {
            background-color: var(--button-bg);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin: 0 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .hero-buttons .btn:hover {
            background-color: var(--button-hover);
            transform: translateY(-3px);
        }

        /* Crypto Icons Animation */
        .crypto-icon {
            position: absolute;
            font-size: 3em;
            opacity: 0.1;
            color: #ffd700; /* Gold for Bitcoin */
            animation: float 15s infinite ease-in-out;
            pointer-events: none;
            z-index: 0;
        }

        .crypto-icon:nth-child(1) { top: 10%; left: 10%; animation-duration: 12s; animation-delay: 0s; }
        .crypto-icon:nth-child(2) { top: 20%; right: 15%; color: #c0c0c0; /* Silver for Ethereum */ animation-duration: 14s; animation-delay: 2s; }
        .crypto-icon:nth-child(3) { bottom: 15%; left: 20%; color: #b8860b; /* DarkGoldenRod for Litecoin */ animation-duration: 10s; animation-delay: 4s; }
        .crypto-icon:nth-child(4) { top: 40%; left: 5%; color: #00ced1; /* DarkTurquoise for Cardano */ animation-duration: 16s; animation-delay: 6s; }
        .crypto-icon:nth-child(5) { bottom: 25%; right: 5%; color: #8a2be2; /* BlueViolet for Polkadot */ animation-duration: 13s; animation-delay: 8s; }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        /* Modal (Popup Form) */
        .modal {
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

        .modal-content {
            background-color: var(--secondary-bg);
            margin: auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            position: relative;
            animation: fadeInScale 0.3s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .close-button {
            color: var(--text-color);
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 25px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-button:hover,
        .close-button:focus {
            color: var(--accent-color);
            text-decoration: none;
        }

        .modal-content h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
            font-size: 2em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-size: 1em;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            border-color: var(--accent-color);
        }

        .modal-content button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #c7374d; /* Darker accent */
        }

        .toggle-form-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--accent-color);
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95em;
        }

        .toggle-form-link:hover {
            text-decoration: underline;
        }

        /* FAQ Section */
        .faq-section {
            padding: 60px 0;
            background-color: var(--secondary-bg);
            border-top: 2px solid var(--border-color);
            border-bottom: 2px solid var(--border-color);
        }

        .faq-section h2 {
            text-align: center;
            font-size: 2.8em;
            margin-bottom: 40px;
            color: #fff;
        }

        .faq-item {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            font-size: 1.1em;
            font-weight: bold;
            color: var(--text-color);
            cursor: pointer;
            background-color: var(--button-bg);
            border-bottom: 1px solid transparent;
            transition: background-color 0.3s ease;
        }

        .faq-question:hover {
            background-color: var(--button-hover);
        }

        .faq-question i {
            transition: transform 0.3s ease;
        }

        .faq-question.active i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 25px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out, padding 0.4s ease-out;
            color: var(--text-color);
        }

        .faq-answer.active {
            max-height: 200px; /* Adjust as needed for content */
            padding: 15px 25px 20px;
        }

        /* Project Timeline Section */
        .timeline-section {
            padding: 60px 0;
            background-color: var(--primary-bg);
            border-top: 2px solid var(--border-color);
        }

        .timeline-section h2 {
            text-align: center;
            font-size: 2.8em;
            margin-bottom: 50px;
            color: #fff;
        }

        .timeline {
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            width: 4px;
            background-color: var(--accent-color);
            top: 0;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
        }

        .timeline-item {
            padding: 20px 40px;
            position: relative;
            background-color: inherit;
            width: 50%;
            z-index: 1;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -17px;
            background-color: var(--accent-color);
            border: 4px solid var(--secondary-bg);
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }

        .timeline-left {
            left: 0;
        }

        .timeline-right {
            left: 50%;
        }

        .timeline-right::after {
            left: -17px;
        }

        .timeline-content {
            padding: 20px 30px;
            background-color: var(--secondary-bg);
            position: relative;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .timeline-content h3 {
            font-size: 1.8em;
            margin-bottom: 10px;
            color: #fff;
        }

        .timeline-content p {
            font-size: 1em;
            color: var(--text-color);
        }

        /* Footer */
        .footer {
            background-color: var(--secondary-bg);
            color: var(--text-color);
            text-align: center;
            padding: 30px 20px;
            border-top: 2px solid var(--border-color);
            font-size: 0.9em;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5em;
            }

            .hero p {
                font-size: 1em;
            }

            .hero-buttons .btn {
                padding: 10px 20px;
                font-size: 1em;
                margin: 5px;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }

            .modal-content h2 {
                font-size: 1.8em;
            }

            .faq-section h2,
            .timeline-section h2 {
                font-size: 2em;
            }

            /* Timeline responsiveness */
            .timeline::before {
                left: 18px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 50px;
                padding-right: 20px;
            }

            .timeline-left, .timeline-right {
                left: 0;
            }

            .timeline-item::after {
                left: 10px;
            }

            .timeline-right::after {
                left: 10px;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2em;
            }

            .hero-buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }

            .hero-buttons .btn {
                width: 80%;
            }

            .faq-question {
                font-size: 1em;
                padding: 15px 20px;
            }

            .faq-answer.active {
                padding: 10px 20px 15px;
            }

            .timeline-content h3 {
                font-size: 1.4em;
            }
            .timeline-content p {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <section class="hero">
        <div class="crypto-icon"><i class="fab fa-bitcoin"></i></div>
        <div class="crypto-icon"><i class="fab fa-ethereum"></i></div>
        <div class="crypto-icon"><i class="fas fa-coins"></i></div>
        <div class="crypto-icon"><i class="fas fa-money-bill-alt"></i></div>
        <div class="crypto-icon"><i class="fas fa-chart-line"></i></div>

        <div class="hero-content">
            <h1>Selamat Datang di CRYPTOFX</h1>
            <p>Platform terkemuka untuk investasi dan pinjaman aset kripto. Mulai perjalanan finansial Anda dengan aman dan mudah.</p>
            <div class="hero-buttons">
                <button class="btn" id="loginBtn">Login</button>
                <button class="btn" id="registerBtn">Register</button>
            </div>
        </div>
    </section>

    <div id="authModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modalTitle">Login</h2>
            <form id="authForm">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group" id="confirmPasswordGroup" style="display: none;">
                    <label for="confirmPassword">Konfirmasi Password:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword">
                </div>
                <button type="submit">Submit</button>
                <a href="#" class="toggle-form-link" id="toggleAuthForm">Belum punya akun? Register di sini.</a>
            </form>
        </div>
    </div>

    <section class="faq-section">
        <div class="container">
            <h2>Pertanyaan Umum</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        Apa itu CRYPTOFX? <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>CRYPTOFX adalah platform inovatif yang memungkinkan Anda untuk berinvestasi dalam berbagai aset kripto dan juga mengajukan pinjaman dengan jaminan kripto Anda. Kami menawarkan keamanan, kemudahan, dan dukungan pelanggan terbaik.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Bagaimana cara kerja investasi di CRYPTOFX? <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Anda bisa mendepositkan aset kripto Anda ke platform kami, memilih paket investasi yang sesuai, dan mulai mendapatkan keuntungan dari pergerakan pasar. Kami menyediakan berbagai pilihan investasi dengan risiko dan potensi keuntungan yang bervariasi.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Apakah CRYPTOFX aman? <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Keamanan adalah prioritas utama kami. Kami menggunakan teknologi enkripsi canggih, otentikasi multi-faktor (MFA), dan cold storage untuk melindungi aset pengguna. Tim keamanan kami memantau sistem 24/7 untuk mendeteksi dan mencegah ancaman.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Bagaimana cara mengajukan pinjaman? <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Anda dapat mengajukan pinjaman dengan menjaminkan aset kripto Anda. Prosesnya cepat dan transparan. Setelah pinjaman disetujui, dana akan dikirimkan ke dompet Anda dalam waktu singkat.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="timeline-section">
        <div class="container">
            <h2>Perkembangan Proyek</h2>
            <div class="timeline">
                <div class="timeline-item timeline-left">
                    <div class="timeline-content">
                        <h3>Q1 2024 - Fase Konseptualisasi</h3>
                        <p>Pengembangan ide awal CRYPTOFX, riset pasar, dan pembentukan tim inti. Perencanaan fitur utama dan arsitektur sistem.</p>
                    </div>
                </div>
                <div class="timeline-item timeline-right">
                    <div class="timeline-content">
                        <h3>Q2 2024 - Pengembangan Inti</h3>
                        <p>Pembangunan infrastruktur platform, integrasi blockchain, dan pengembangan modul investasi dasar. Uji coba internal dimulai.</p>
                    </div>
                </div>
                <div class="timeline-item timeline-left">
                    <div class="timeline-content">
                        <h3>Q3 2024 - Peluncuran Beta & Audit Keamanan</h3>
                        <p>Peluncuran versi beta untuk penguji terbatas. Audit keamanan eksternal dilakukan untuk memastikan integritas dan keamanan platform.</p>
                    </div>
                </div>
                <div class="timeline-item timeline-right">
                    <div class="timeline-content">
                        <h3>Q4 2024 - Peluncuran Publik</h3>
                        <p>CRYPTOFX diluncurkan secara resmi ke publik. Kampanye pemasaran dimulai dan fitur pinjaman diperkenalkan.</p>
                    </div>
                </div>
                <div class="timeline-item timeline-left">
                    <div class="timeline-content">
                        <h3>Q1 2025 - Integrasi Aset Baru</h3>
                        <p>Penambahan dukungan untuk berbagai aset kripto baru dan pengembangan fitur staking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; 2025 CRYPTOFX. Hak cipta dilindungi undang-undang.</p>
    </footer>

    <script>
        // Modal (Popup Form) Logic
        const authModal = document.getElementById('authModal');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const closeButton = document.querySelector('.close-button');
        const modalTitle = document.getElementById('modalTitle');
        const authForm = document.getElementById('authForm');
        const toggleAuthForm = document.getElementById('toggleAuthForm');
        const confirmPasswordGroup = document.getElementById('confirmPasswordGroup');

        let isLoginForm = true;

        function openModal(type) {
            authModal.style.display = 'flex';
            if (type === 'login') {
                modalTitle.textContent = 'Login';
                toggleAuthForm.textContent = 'Belum punya akun? Register di sini.';
                confirmPasswordGroup.style.display = 'none';
                isLoginForm = true;
            } else {
                modalTitle.textContent = 'Register';
                toggleAuthForm.textContent = 'Sudah punya akun? Login di sini.';
                confirmPasswordGroup.style.display = 'block';
                isLoginForm = false;
            }
        }

        function closeModal() {
            authModal.style.display = 'none';
            authForm.reset();
        }

        loginBtn.addEventListener('click', () => openModal('login'));
        registerBtn.addEventListener('click', () => openModal('register'));
        closeButton.addEventListener('click', closeModal);

        window.addEventListener('click', (event) => {
            if (event.target === authModal) {
                closeModal();
            }
        });

        toggleAuthForm.addEventListener('click', (e) => {
            e.preventDefault();
            if (isLoginForm) {
                openModal('register');
            } else {
                openModal('login');
            }
        });

        authForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            let action = isLoginForm ? 'login' : 'register';
            let formData = new FormData();
            formData.append('action', action);
            formData.append('email', email);
            formData.append('password', password);
            if (!isLoginForm) {
                formData.append('confirmPassword', confirmPassword);
            }

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                alert(result.message);
                if (result.success) {
                    closeModal();
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        });


        // FAQ Section Logic
        const faqQuestions = document.querySelectorAll('.faq-question');

        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const icon = question.querySelector('i');

                faqQuestions.forEach(item => {
                    if (item !== question && item.classList.contains('active')) {
                        item.classList.remove('active');
                        item.nextElementSibling.classList.remove('active');
                        item.querySelector('i').classList.remove('active');
                    }
                });

                question.classList.toggle('active');
                answer.classList.toggle('active');
                icon.classList.toggle('active');
            });
        });
    </script>
</body>
</html>
