<?php
require_once 'config.php';

if (is_logged_in()) {
    header('Location: dashboard_mitra.php');
    exit();
}

$prefill_email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$prefill_company = isset($_GET['company']) ? htmlspecialchars($_GET['company']) : '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $contact_person = sanitize_input($_POST['contact_person'] ?? '');
        $phone = sanitize_input($_POST['phone_number'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email tidak valid.';
        } elseif ($password !== $password_confirm) {
            $error = 'Password tidak cocok.';
        } else {
            try {
                $password_hash = hash_password($password);
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, company_name, contact_person, phone_number, status) VALUES (?, ?, ?, ?, ?, 'active')");
                
                if ($stmt->execute([$email, $password_hash, $company_name, $contact_person, $phone])) {
                    $user_id = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO impact_data (user_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['company_name'] = $company_name;
                    
                    header('Location: dashboard_mitra.php?welcome=1');
                    exit();
                }
            } catch (PDOException $e) {
                $error = 'Registrasi gagal: ' . $e->getMessage();
            }
        }
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Mitra - pilah-pilih.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Share+Tech&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Beranda.css">
    
    <style>
        /* Menggunakan variabel yang ada di Beranda.css */
        .register-section {
            padding: 140px 20px 80px; /* Padding atas agar tidak tertutup fixed navbar */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--eco-white) 0%, var(--muted-sage) 100%);
            position: relative;
            overflow: hidden;
        }

        /* --- Background Gambar Transparan --- */
        .register-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://plus.unsplash.com/premium_photo-1725394921112-ab7b0265e5fc?q=80&w=1171&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center;
            background-size: cover;
            opacity: 0.4; /* Gambar transparan menyatu dengan gradien */
            z-index: 0;
            pointer-events: none;
        }

        .register-container {
            width: 100%;
            max-width: 550px;
            position: relative;
            z-index: 1; /* Di atas gambar background */
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Efek Transparansi Kaca (Glassmorphism) --- */
        .register-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 45px 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(136, 144, 99, 0.1);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark);
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .register-header p {
            font-family: 'Share Tech', sans-serif;
            color: var(--primary-light);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0;
            font-weight: 600;
        }

        .form-label {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-lg);
            padding: 12px 15px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition-smooth);
            background: rgba(250, 250, 250, 0.9); /* Sedikit transparan */
        }

        .form-control:focus {
            background: var(--white);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(136, 144, 99, 0.15);
            outline: none;
        }

        .form-control[readonly] {
            background-color: rgba(230, 232, 219, 0.8);
            border-color: #d8d0c3;
            color: var(--primary-dark);
            font-weight: 500;
            cursor: not-allowed;
        }

        .alert-danger {
            border-radius: var(--radius-lg);
            border: none;
            background-color: #fff3cd;
            color: #856404;
            font-size: 0.9rem;
            padding: 12px 15px;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #2a3319 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-lg);
            padding: 14px 20px;
            font-weight: 700;
            width: 100%;
            font-size: 1rem;
            font-family: 'Bricolage Grotesque', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-md);
            margin-top: 15px;
        }

        .btn-register:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(20, 26, 14, 0.15);
            color: var(--white);
        }

        .login-link {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition-smooth);
        }

        .login-link:hover {
            color: var(--primary-light);
        }

        @media (max-width: 600px) {
            .register-card { padding: 35px 25px; }
            .register-header h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top" style="background-color: var(--white); height: 80px; margin: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); z-index: 1050;">
    <div class="container-fluid px-4 px-lg-5"> <a class="navbar-brand" href="Beranda.html">
            <span class="brand-text" style="font-family: 'Bricolage Grotesque', sans-serif; font-weight: 700; color: var(--primary-dark); font-size: 24px;">pilah-pilih.</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">pilah-pilih.</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav ms-auto flex-wrap">
                    <li class="nav-item">
                        <a class="nav-link mx-lg-2" href="Beranda.html">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-2" href="Tentang.html">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-2" href="Mitra.html">Mitra</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-2" href="Layanan.html">Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-2" href="Formulir.php">Gabung Mitra</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-2" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>


    <section class="register-section">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h2>Daftar Mitra</h2>
                    <p>Bergabung di Ekosistem Sirkular</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="company_name" class="form-label">Nama Perusahaan *</label>
                        <input type="text" class="form-control" name="company_name" 
                               value="<?php echo $prefill_company; ?>" 
                               <?php echo ($prefill_company !== '') ? 'readonly' : ''; ?> required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Operasional *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo $prefill_email; ?>" 
                               <?php echo ($prefill_email !== '') ? 'readonly' : ''; ?> required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Nama Kontak *</label>
                            <input type="text" class="form-control" name="contact_person" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">Nomor Telepon *</label>
                            <input type="tel" class="form-control" name="phone_number" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Konfirmasi Password *</label>
                        <input type="password" class="form-control" name="password_confirm" required>
                    </div>

                    <button type="submit" class="btn-register">
                        <i class="bi bi-person-plus-fill me-2"></i> Buat Akun Mitra
                    </button>
                    
                    <div class="text-center mt-4">
                        <small style="color: #444; font-size: 0.9rem; font-weight: 500;">
                            Sudah punya akun? <a href="login.php" class="login-link">Login di sini</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer-section">
        <div class="container">
            <div class="row justify-content-center">
                
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0 offset-lg-1">
                    <h5 class="footer-logo mb-4">pilah-pilih.</h5>
                    <div id="Kontak" class="contact-info">
                        <p><i class="bi bi-geo-alt-fill"></i> Surabaya, Jawa Timur (ITS Area)</p>
                        <p><i class="bi bi-whatsapp"></i> +62 812-3456-7890</p>
                        <p><i class="bi bi-envelope-fill"></i> info@pilahpilih.com</p>
                        <p><i class="bi bi-instagram"></i> @pilah-pilih.</p>
                        <p><i class="bi bi-twitter-x"></i> @pilah-pilih.</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="footer-heading">Layanan</h5>
                    <ul class="footer-links">
                        <li><a href="Layanan.html">On-Demand Pick-Up</a></li>
                        <li><a href="Layanan.html">Smart Sorting Bin</a></li>
                        <li><a href="Layanan.html">Traceability Report</a></li>
                        <li><a href="Formulir.php">Circular Consultation</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-12">
                    <h5 class="footer-heading">Navigasi</h5>
                    <ul class="footer-links">
                        <li><a href="Tentang.html">Tentang Kami</a></li>
                        <li><a href="Formulir.php">Klien Portal</a></li>
                        <li><a href="Mitra.html">Mitra Kami</a></li>
                        <li><a href="#Kontak">Kontak Kami</a></li>
                    </ul>
                </div>
                
            </div>
            
            <div class="footer-bottom mt-5 pt-4">
                <p>© 2026 pilah-pilih. Logistik Sampah Organik. Semua Hak Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>