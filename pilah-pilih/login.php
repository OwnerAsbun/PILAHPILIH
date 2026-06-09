<?php
/**
 * Login Page
 * Pilah-Pilih Partner Dashboard
 * * Secure login with CSRF protection and rate limiting
 */

require_once 'config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: dashboard_mitra.php');
    exit();
}

$error = '';
$email_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $email_input = $email;

        if (empty($email) || empty($password)) {
            $error = 'Email dan password diperlukan.';
        } else {
            try {
                // Get user by email
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && verify_password($password, $user['password_hash'])) {
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['company_name'] = $user['company_name'];

                    // Log audit
                    log_audit_action($user['id'], 'LOGIN', 'Successful login', $pdo);

                    // Redirect to dashboard or requested page
                    $redirect = $_GET['redirect'] ?? 'dashboard_mitra.php';
                    header('Location: ' . (filter_var($redirect, FILTER_VALIDATE_URL) ? $redirect : 'dashboard_mitra.php'));
                    exit();
                } else {
                    $error = 'Email atau password salah.';
                    log_audit_action(null, 'LOGIN_FAILED', 'Failed login attempt for email: ' . $email, $pdo);
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Terjadi kesalahan. Silakan coba lagi nanti.';
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
    <title>Login Mitra - pilah-pilih.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Share+Tech&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Beranda.css">
    
    <style>
        /* ==========================================================================
           OVERRIDE NAVBAR MENGIKUTI LAYANAN.CSS
           ========================================================================== */
        .navbar {
            background-color: var(--white) !important;
            height: 80px;
            margin: 20px !important;
            border-radius: 16px !important;
            padding: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
            border: none !important;
            backdrop-filter: none !important;
            z-index: 1050;
            transition: var(--transition-smooth) !important;
        }

        /* Kotak navbar tetap diam saat dihover */
        .navbar:hover {
             box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
             transform: none !important; 
        }

        .navbar .brand-text {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 700;
            color: var(--primary-light) !important; /* var(--moss) */
            font-size: 24px;
            transition: var(--transition-smooth);
            letter-spacing: normal !important;
        }

        .navbar-brand:hover .brand-text {
            color: var(--primary-dark) !important; /* var(--kombi) */
            transform: none !important;
        }

        .navbar-toggler {
            border: none;
            font-size: 1.25rem;
            color: inherit !important;
        }

        .navbar-toggler:focus,
        .btn-close:focus {
            box-shadow: none;
            outline: none;
        }

        .navbar .nav-link {
            color: var(--primary-light) !important; /* var(--moss) */
            font-weight: 500;
            position: relative;
            /* Tambahkan transisi transform agar efek naik mulus */
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease !important; 
            font-family: 'Inter', sans-serif;
            background: transparent !important;
            border-radius: 0 !important;
            padding: .5rem 1rem !important;
            margin: 0 !important;
            box-shadow: none !important;
            display: inline-block; /* Memastikan transform translateY berfungsi sempurna pada teks */
            letter-spacing: normal !important;
        }

        /* EFEK NAIK PADA TEKS MENU SAAT DIHOVER */
        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: var(--primary-dark) !important; /* var(--kombi) */
            background: transparent !important;
            box-shadow: none !important;
            transform: translateY(-4px) !important; /* Teks naik 4px ke atas */
        }

        @media (min-width: 991px) {
            .navbar .nav-link::before {
                content: "" !important;
                display: block !important;
                position: absolute !important;
                bottom: -5px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                width: 0 !important;
                height: 2px !important;
                background-color: var(--primary-dark) !important; /* var(--kombi) */
                visibility: hidden !important;
                transition: var(--transition-smooth) !important;
                border-radius: 0 !important;
            }
            .navbar .nav-link:hover::before,
            .navbar .nav-link.active::before {
                width: 100% !important;
                visibility: visible !important;
            }
        }
        
        @media (max-width: 767px) {
            .navbar {
                margin: 10px !important;
                height: 65px;
            }
            .navbar .nav-link:hover,
            .navbar .nav-link.active {
                transform: none !important; /* Nonaktifkan efek naik pada mode dropdown mobile */
            }
        }
        /* ========================================================================== */

        .login-section {
            padding: 140px 20px 80px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Base color gradient */
            background: linear-gradient(135deg, var(--eco-white) 0%, var(--muted-sage) 50%, #d8d0c3 100%);
            position: relative;
            overflow: hidden;
        }

        /* Background Gambar Transparan ala Hero Section */
        .login-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1459787759585-dc0201b4e0bb?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center;
            background-size: cover;
            opacity: 0.4; /* Gambar dibuat transparan menyatu dengan gradien */
            z-index: 0;
            pointer-events: none; /* Agar tidak menutupi interaksi form */
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1; /* Memastikan form berada di atas background gambar */
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: var(--white);
            padding: 45px 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(136, 144, 99, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark);
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-family: 'Share Tech', sans-serif;
            color: var(--primary-light);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-lg);
            padding: 12px 15px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition-smooth);
            background: #fafafa;
        }

        .form-control:focus {
            background: var(--white);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(136, 144, 99, 0.15);
            outline: none;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .alert-danger {
            border-radius: var(--radius-lg);
            border: none;
            background-color: #fff3cd;
            color: #856404;
            font-size: 0.9rem;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .form-check {
            margin-bottom: 20px;
        }

        .form-check-input {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            margin-top: 3px;
        }

        .form-check-input:checked {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        .form-check-label {
            color: var(--primary-text);
            font-size: 0.9rem;
            margin-left: 8px;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-dark);
        }

        .btn-login {
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
            margin-top: 5px;
            cursor: pointer;
        }

        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(20, 26, 14, 0.15);
            color: var(--white);
        }

        .security-info {
            background: var(--eco-white);
            border-left: 4px solid var(--primary-light);
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--primary-dark);
            margin-top: 25px;
            font-weight: 500;
        }

        .security-info i {
            margin-right: 8px;
            color: var(--primary-dark);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 600px) {
            .login-card { padding: 35px 25px; }
            .login-header h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4 px-lg-5">
      <a class="navbar-brand me-auto" href="Beranda.html">
        <span class="brand-text">pilah-pilih.</span>
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
          <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
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
              <a class="nav-link mx-lg-2 active" aria-current="page" href="login.php">Login</a>
            </li>
            </ul>
        </div>
      </div>
    </div>
  </nav>

    <section class="login-section">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2>Login Mitra</h2>
                    <p>Akses Dashboard Sirkular</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="email" class="form-label">Email Operasional *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email_input); ?>"
                               placeholder="email@perusahaan.com" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Masukkan password Anda" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Ingat saya
                            </label>
                        </div>
                        <div class="forgot-password mb-0">
                            <a href="forgot_password.php">Lupa password?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Dashboard
                    </button>

                    <div class="security-info">
                        <i class="bi bi-shield-check"></i>
                        Akun Anda dilindungi dengan enkripsi tingkat bank
                    </div>

                    <div class="login-footer">
                        Belum punya akun? <a href="register_mitra.php">Daftar di sini</a>
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
                        <li><a href="login.php">Login</a></li>
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