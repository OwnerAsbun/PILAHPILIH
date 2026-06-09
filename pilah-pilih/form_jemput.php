<?php
/**
 * Form Jemput (Logistics/Pickup Scheduling Form)
 * Pilah-Pilih Partner Dashboard
 * * Partners use this form to schedule organic waste pickup
 */

require_once 'config.php';
require_login();  // Require login to access

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get waste types for dropdown
try {
    $stmt = $pdo->prepare("SELECT * FROM waste_types ORDER BY name");
    $stmt->execute();
    $waste_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $waste_types = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        // Get and sanitize inputs
        $pickup_date = sanitize_input($_POST['pickup_date'] ?? '');
        $pickup_time = sanitize_input($_POST['pickup_time'] ?? '');
        $estimated_weight = floatval($_POST['estimated_weight'] ?? 0);
        $waste_type = sanitize_input($_POST['waste_type'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');

        // Validation
        $errors = [];

        if (empty($pickup_date)) {
            $errors[] = 'Tanggal jemput diperlukan';
        } else {
            $pickup_timestamp = strtotime($pickup_date);
            if ($pickup_timestamp < time()) {
                $errors[] = 'Tanggal jemput tidak boleh di masa lalu';
            }
        }

        if ($estimated_weight <= 0 || $estimated_weight > 10000) {
            $errors[] = 'Berat perkiraan harus antara 0.1 - 10000 kg';
        }

        if (empty($waste_type)) {
            $errors[] = 'Jenis sampah harus dipilih';
        }

        if (count($errors) > 0) {
            $error = implode('<br>', $errors);
        } else {
            try {
                // Combine date and time
                $pickup_datetime = $pickup_date . ' ' . $pickup_time;

                // Insert shipment
                $stmt = $pdo->prepare("
                    INSERT INTO shipments (
                        user_id, pickup_date, scheduled_time, estimated_weight, 
                        waste_type, notes, status
                    ) VALUES (?, ?, ?, ?, ?, ?, 'waiting')
                ");

                if ($stmt->execute([
                    $user_id,
                    $pickup_date,
                    $pickup_time,
                    $estimated_weight,
                    $waste_type,
                    $notes
                ])) {
                    $shipment_id = $pdo->lastInsertId();

                    // Log initial status
                    $stmt = $pdo->prepare("
                        INSERT INTO shipment_history (shipment_id, status, notes)
                        VALUES (?, 'waiting', 'Permintaan jemput dibuat')
                    ");
                    $stmt->execute([$shipment_id]);

                    // Log audit
                    log_audit_action($user_id, 'CREATE_SHIPMENT', 'Shipment #' . $shipment_id . ' created', $pdo);

                    $success = 'Permintaan jemput berhasil dibuat! ID Pengiriman: #' . str_pad($shipment_id, 5, '0', STR_PAD_LEFT);
                    
                    // Redirect to dashboard after 2 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'dashboard_mitra.php';
                        }, 2000);
                    </script>";
                } else {
                    $error = 'Gagal membuat permintaan jemput. Silakan coba lagi.';
                }
            } catch (PDOException $e) {
                error_log("Shipment creation error: " . $e->getMessage());
                $error = 'Terjadi kesalahan database. Silakan coba lagi.';
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
    <title>Jadwalkan Jemput - pilah-pilih.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Share+Tech&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="dashboard.css" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors matching Beranda */
            --primary-dark: #354024;
            --primary-light: #889063;
            --primary-text: #141A0E;
            
            /* Supplementary Colors */
            --eco-white: #E6E8DB;
            --muted-sage: #C2C7A9;
            --white: #ffffff;
            
            /* Radius & Shadow */
            --radius-lg: 16px;
            --radius-xl: 24px;
            --shadow-sm: 0 4px 12px rgba(20, 26, 14, 0.05);
            --shadow-md: 0 8px 24px rgba(20, 26, 14, 0.12);
            --shadow-xl: 0 16px 48px rgba(20, 26, 14, 0.18);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--primary-text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f9faf7;
        }

        /* --- Navbar Styles --- */
        .dashboard-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .dashboard-navbar .container-fluid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .brand-text {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 800;
            color: var(--primary-dark);
            font-size: 1.8rem;
            letter-spacing: -1px;
            text-decoration: none;
            display: inline-block;
        }

        .brand-text span {
            color: var(--primary-light);
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            font-weight: 600;
            color: var(--primary-dark);
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .btn-logout {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-smooth);
        }

        .btn-logout:hover {
            color: var(--primary-dark);
        }

        /* ==========================================================================
           SECTION FORM STYLE (MENGADOPSI DARI LOGIN.PHP)
           ========================================================================== */
        .schedule-section {
            padding: 80px 20px;
            flex: 1; /* Memastikan section mengisi sisa ruang secara penuh */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        /* Background Gambar Transparan ala Hero Section (Sama seperti login.php) */
        .schedule-section::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: url('https://images.unsplash.com/photo-1459787759585-dc0201b4e0bb?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            opacity: 0.3; /* Gambar dibuat transparan menyatu dengan gradien */
            z-index: -2;
            pointer-events: none; /* Agar tidak menutupi interaksi form */
        }
        
        .schedule-section::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, var(--eco-white) 0%, var(--muted-sage) 50%, #d8d0c3 100%);
            z-index: -1;
            pointer-events: none;
        }

        /* --- Form Container Styles --- */
        .form-container {
            width: 100%;
            max-width: 800px;
            position: relative;
            z-index: 10; /* Di atas gambar background */
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 45px;
            animation: slideUp 0.6s ease-out;
            
            /* Background putih sedikit transparan / blur untuk form box */
            background: rgba(255, 255, 255, 0.92); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .form-header h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--primary-light);
            font-size: 1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-lg);
            padding: 12px 15px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition-smooth);
            background: #ffffff; /* Input solid */
        }

        .form-control:focus, .form-select:focus {
            background: var(--white);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(136, 144, 99, 0.15);
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .form-container { padding: 30px 20px; }
            .schedule-section { padding: 40px 15px; }
        }

        .form-text {
            font-size: 0.85rem;
            color: #555;
            margin-top: 6px;
            font-weight: 500;
        }

        /* --- Button Styles --- */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #2a3319 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-lg);
            padding: 14px 30px;
            font-weight: 700;
            width: 100%;
            font-size: 1rem;
            font-family: 'Bricolage Grotesque', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition-smooth);
            margin-top: 10px;
            box-shadow: var(--shadow-md);
        }

        .btn-submit:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(20, 26, 14, 0.15);
            color: var(--white);
        }

        .btn-cancel {
            background: transparent;
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
            border-radius: var(--radius-lg);
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            font-size: 1rem;
            font-family: 'Bricolage Grotesque', sans-serif;
            text-align: center;
            text-decoration: none;
            transition: var(--transition-smooth);
            margin-top: 10px;
            display: inline-block;
        }

        .btn-cancel:hover {
            background: rgba(136, 144, 99, 0.1);
            color: var(--primary-dark);
            border-color: var(--primary-dark);
            text-decoration: none;
        }

        .form-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 25px;
        }

        /* --- Alert & Info Box Styles --- */
        .alert {
            border-radius: var(--radius-lg);
            border: none;
            margin-bottom: 25px;
            padding: 15px 20px;
        }

        .weight-info {
            background: rgba(230, 232, 219, 0.8);
            border-left: 4px solid var(--primary-light);
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 0.85rem;
            color: var(--primary-dark);
            font-weight: 600;
        }

        .info-box-custom {
            background: rgba(230, 232, 219, 0.8);
            border: none;
            color: var(--primary-dark);
            border-radius: var(--radius-lg);
            padding: 20px;
        }

        .info-box-custom strong {
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition-smooth);
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .back-link:hover {
            color: var(--primary-light);
            transform: translateX(-5px);
        }

        /* --- Footer Styles --- */
        .dashboard-footer {
            text-align: center;
            padding: 25px 0;
            background-color: #354024;
            color: #ffffff;
            font-size: 0.9rem;
            margin-top: auto;
            width: 100%;
            position: relative; 
            z-index: 10;
        }
        
        .dashboard-footer p {
            margin: 0;
        }
    </style>
</head>
<body>
    <nav class="dashboard-navbar">
        <div class="container-fluid">
            <a href="dashboard_mitra.php" class="navbar-brand brand-text">
                <span>pilah-pilih.</span>
            </a>
            <div class="navbar-user">
                <span class="user-name"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['company_name'] ?? 'Mitra'); ?></span>
                <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    <section class="schedule-section">
        <div class="form-container">
            <a href="dashboard_mitra.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>

            <div class="form-header">
                <h2>Jadwalkan Jemput</h2>
                <p>Buat permintaan pengangkutan sampah organik Anda</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
                    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="pickup_date" class="form-label">Tanggal Jemput *</label>
                        <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                        <small class="form-text">Pilih tanggal untuk pengangkutan</small>
                    </div>

                    <div class="form-group">
                        <label for="pickup_time" class="form-label">Waktu Jemput *</label>
                        <input type="time" class="form-control" id="pickup_time" name="pickup_time" required>
                        <small class="form-text">Jam operasional: 07:00 - 17:00</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estimated_weight" class="form-label">Berat Perkiraan (kg) *</label>
                        <input type="number" class="form-control" id="estimated_weight" name="estimated_weight" 
                               step="0.1" min="0.1" max="10000" placeholder="100" required>
                        <div class="weight-info">
                            <i class="bi bi-info-circle"></i> Berat sampah organik yang akan diangkut
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="waste_type" class="form-label">Jenis Sampah *</label>
                        <select class="form-select" id="waste_type" name="waste_type" required>
                            <option value="">-- Pilih Jenis Sampah --</option>
                            <?php foreach ($waste_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['name']); ?>">
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes" class="form-label">Catatan Tambahan</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4" 
                              placeholder="Deskripsi kondisi sampah, lokasi khusus, atau informasi penting lainnya..."></textarea>
                    <small class="form-text">Informasi ini membantu tim kami mempersiapkan pengangkutan dengan lebih baik</small>
                </div>

                <div class="alert info-box-custom">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Informasi Penting:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 0.9rem;">
                        <li>Pastikan sampah organik telah dipisahkan dari material lain</li>
                        <li>Tempatkan sampah di area yang mudah diakses oleh truk</li>
                        <li>Kami akan konfirmasi jadwal melalui WhatsApp dalam 1 jam</li>
                        <li>Jika terjadi perubahan, hubungi kami sebelum jam 14:00</li>
                    </ul>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-lg"></i> Jadwalkan Sekarang
                    </button>
                    <a href="dashboard_mitra.php" class="btn-cancel">
                        <i class="bi bi-x-lg"></i> Batalkan
                    </a>
                </div>
            </form>
        </div>
    </section>

    <footer class="dashboard-footer">
        <p>&copy; 2026 pilah-pilih. Logistik Sampah Organik. Semua Hak Dilindungi.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const dateInput = document.getElementById('pickup_date');
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);

        // Set default time to 08:00
        document.getElementById('pickup_time').value = '08:00';

        // Validate weight input
        const weightInput = document.getElementById('estimated_weight');
        weightInput.addEventListener('change', function() {
            if (this.value > 10000) {
                this.value = 10000;
                alert('Berat maksimal adalah 10000 kg');
            }
        });
    </script>
</body>
</html>