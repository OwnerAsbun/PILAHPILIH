<?php
/**
 * Form Jemput (Logistics/Pickup Scheduling Form) - Aligned with Dashboard
 * Pilah-Pilih Partner Dashboard
 */

require_once 'config.php';
require_login();  // Require login to access

$user_id = $_SESSION['user_id'];
$company_name = $_SESSION['company_name'] ?? '';
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
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="Mitra.css" rel="stylesheet">
    
    <style>
        /* =========================================================
           DASHBOARD UNIFIED PATTERN STYLING 
           ========================================================= */
        body {
            background-color: #f7f8f4;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .page-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* --- SIDEBAR STYLE (MATCHED 100%) --- */
        .sidebar {
           width: 280px;
            background-color: var(--primary-dark, #354024);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }   
        

        .sidebar-brand {
            padding: 0 20px 20px;
            display: block;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Bricolage Grotesque', sans-serif;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-item {
            margin-bottom: 12px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            gap: 12px;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary-light, #889063);
        }

        .sidebar-user {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }


        .sidebar-user .user-name {
            display: block;
            font-weight: 600;
            color: #C2C7A9;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .btn-logout-sidebar {
            color: #ff6b6b;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .btn-logout-sidebar:hover {
            color: #ff4c4c;
            transform: translateX(3px);
        }

        /* --- MAIN CONTENT AREA --- */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 30px 40px;
            transition: all 0.3s;
        }

        /* --- REFACTORED FORM CONTAINER (MATCHES DASHBOARD CARDS) --- */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid rgba(136, 144, 99, 0.15);
            margin-top: 15px;
        }

        /* --- HEADINGS & LABELS --- */
        .section-title {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark, #354024);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-label {
            font-family: 'Bricolage Grotesque', sans-serif;
            color: var(--primary-dark, #354024);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border: 1px solid rgba(136, 144, 99, 0.25);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light, #889063);
            box-shadow: 0 0 0 3px rgba(136, 144, 99, 0.15);
            outline: none;
        }

        /* --- CUSTOM ALERT & INFO BOXES --- */
        .weight-info {
            background: rgba(136, 144, 99, 0.08);
            border-left: 3px solid var(--primary-light, #889063);
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 0.85rem;
            color: var(--primary-dark, #354024);
        }

        .info-box-custom {
            background: #f9faf7;
            border: 1px solid rgba(136, 144, 99, 0.12);
            color: var(--primary-dark, #354024);
            border-radius: 8px;
            padding: 18px;
            margin: 20px 0;
        }

        .info-box-custom ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
            font-size: 0.9rem;
            color: #555;
        }

        /* --- BUTTONS --- */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-dark, #354024) 0%, #2a3319 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(53, 64, 36, 0.15);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(53, 64, 36, 0.25);
        }

        .btn-cancel {
            background: transparent;
            color: #354024;
            border: 1px solid rgba(136, 144, 99, 0.5);
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: rgba(136, 144, 99, 0.05);
            border-color: #354024;
            color: #354024;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary-light, #889063);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 15px;
            transition: 0.2s;
        }

        .back-link:hover {
            color: var(--primary-dark, #354024);
            transform: translateX(-3px);
        }

        /* --- TOGGLE BUTTON FOR MOBILE --- */
        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1050;
            background-color: var(--primary-dark, #354024);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .dashboard-footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(136, 144, 99, 0.1);
        }

        /* --- MOBILE RESPONSIVE --- */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 70px 15px 20px;
            }
            .sidebar-toggle-btn {
                display: block;
            }
        }
    </style>
</head>
<body>

    <button class="sidebar-toggle-btn" id="sidebarToggle">
        <i class="bi bi-list"></i> Menu
    </button>

    <div class="page-wrapper">
        <nav class="sidebar" id="sidebar">
            <div>
                <a href="dashboard_mitra.php" class="sidebar-brand">
                    <span>pilah-pilih.</span>
                </a>
                <ul class="sidebar-menu">
                    <li class="sidebar-item">
                        <a href="dashboard_mitra.php" class="sidebar-link">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="form_jemput.php" class="sidebar-link active">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Jadwalkan Jemput</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="dashboard_mitra.php#history" class="sidebar-link">
                            <i class="bi bi-clock-history"></i>
                            <span>Riwayat</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-user">
                <span class="user-name" title="<?php echo htmlspecialchars($company_name); ?>">
                    <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($company_name); ?>
                </span>
                <a href="logout.php" class="btn-logout-sidebar">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>
        </nav>

        <main class="main-content">
            
            <a href="dashboard_mitra.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>

            <div class="row">
                <div class="col-12">
                    <h5 class="section-title"><i class="bi bi-calendar-plus-fill"></i> Jadwalkan Jemput</h5>
                    <p class="text-muted small ms-4 ps-1">Buat permintaan pengangkutan baru untuk sampah organik Anda</p>
                </div>
            </div>

            <div class="form-container">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 rounded-3 mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success border-0 rounded-3 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label延 for="pickup_date" class="form-label">Tanggal Jemput *</label延>
                            <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                        </div>

                        <div class="col-md-6">
                            <label for="pickup_time" class="form-label">Waktu Jemput *</label>
                            <input type="time" class="form-control" id="pickup_time" name="pickup_time" required>
                            <div class="form-text text-muted small mt-1">Jam operasional: 07:00 - 17:00</div>
                        </div>

                        <div class="col-md-6">
                            <label for="estimated_weight" class="form-label">Berat Perkiraan (kg) *</label>
                            <input type="number" class="form-control" id="estimated_weight" name="estimated_weight" 
                                   step="0.1" min="0.1" max="10000" placeholder="Contoh: 100" required>
                            <div class="weight-info">
                                <i class="bi bi-info-circle-fill me-1"></i> Estimasi total timbangan berat sampah organik.
                            </div>
                        </div>

                        <div class="col-md-6">
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

                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan Tambahan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Tulis instruksi khusus lokasi, kondisi sampah, atau info tambahan lainnya..."></textarea>
                        </div>
                    </div>

                    <div class="info-box-custom">
                        <span class="fw-bold text-dark"><i class="bi bi-info-circle-fill text-primary me-2"></i>Informasi Penting:</span>
                        <ul>
                            <li>Pastikan sampah organik telah dipisahkan dari material non-organik lainnya.</li>
                            <li>Tempatkan sampah di area yang mudah diakses dan dijangkau oleh armada truk penjemputan.</li>
                            <li>Tim kami akan melakukan konfirmasi jadwal kembali melalui WhatsApp dalam waktu maksimal 1 jam.</li>
                        </ul>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-submit px-4">
                            <i class="bi bi-check-lg me-1"></i> Jadwalkan Sekarang
                        </button>
                        <a href="dashboard_mitra.php" class="btn btn-cancel px-4">
                            Batalkan
                        </a>
                    </div>
                </form>
            </div>

            <footer class="dashboard-footer text-center py-4">
                <p class="text-muted small mb-0">&copy; 2026 Pilah-Pilih. Logistik Sampah Organik. Semua Hak Dilindungi.</p>
            </footer>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar di Mobile
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Set minimum date to today
        const dateInput = document.getElementById('pickup_date');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        }

        // Set default time to 08:00
        const timeInput = document.getElementById('pickup_time');
        if (timeInput && !timeInput.value) {
            timeInput.value = '08:00';
        }

        // Validate weight input maximum
        const weightInput = document.getElementById('estimated_weight');
        if (weightInput) {
            weightInput.addEventListener('change', function() {
                if (parseFloat(this.value) > 10000) {
                    this.value = 10000;
                    alert('Berat maksimal penjemputan tunggal adalah 10000 kg');
                }
            });
        }
    </script>
</body>
</html>