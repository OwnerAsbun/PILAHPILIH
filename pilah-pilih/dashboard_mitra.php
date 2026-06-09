<?php
/**
 * Dashboard Mitra (Partner Dashboard) - Updated
 * Pilah-Pilih Partner Dashboard System
 */

require_once 'config.php';
require_login();  // Redirect to login if not authenticated

$user_id = $_SESSION['user_id'];
$company_name = $_SESSION['company_name'] ?? '';

// --- CONFIGURATION FOR SEARCH & PAGINATION ---
$limit = 20; // limit 20 data per halaman
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Get user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get total shipments for quick stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shipments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_shipments = $stmt->fetch()['total'];

    // Get total weight
    $stmt = $pdo->prepare("
        SELECT 
            SUM(COALESCE(actual_weight, estimated_weight)) as total_weight 
        FROM shipments 
        WHERE user_id = ? AND status IN ('picked_up', 'processed', 'finished')
    ");
    $stmt->execute([$user_id]);
    $total_weight = $stmt->fetch()['total_weight'] ?? 0;

    // Get last pickup status
    $stmt = $pdo->prepare("
        SELECT status, pickup_date, created_at 
        FROM shipments 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $last_pickup = $stmt->fetch();

    // Get impact data
    $stmt = $pdo->prepare("SELECT * FROM impact_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $impact = $stmt->fetch();

    // Get latest shipment for timeline
    $stmt = $pdo->prepare("
        SELECT * FROM shipments 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $latest_shipment = $stmt->fetch();

    // --- PAGINATION & FILTERED QUERY ---
    // Membersihkan input pencarian dari karakter '#' atau '0' di depan untuk pencarian ID numerik
    $clean_search = ltrim($search, '#');
    $clean_search = ltrim($clean_search, '0');

    // Query menghitung total data yang sesuai filter pencarian
    $count_sql = "SELECT COUNT(*) as total FROM shipments WHERE user_id = ?";
    $count_params = [$user_id];

    if ($search !== '') {
        if (is_numeric($clean_search) && $clean_search !== '') {
            $count_sql .= " AND (id = ? OR waste_type LIKE ?)";
            $count_params[] = intval($clean_search);
            $count_params[] = "%$search%";
        } else {
            $count_sql .= " AND waste_type LIKE ?";
            $count_params[] = "%$search%";
        }
    }

    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($count_params);
    $total_filtered_shipments = $stmt->fetch()['total'];
    $total_pages = max(1, ceil($total_filtered_shipments / $limit));

    // Query mengambil data riwayat dengan filter pencarian dan limitasi halaman (20 per halaman)
    $shipments_sql = "SELECT * FROM shipments WHERE user_id = ?";
    if ($search !== '') {
        if (is_numeric($clean_search) && $clean_search !== '') {
            $shipments_sql .= " AND (id = ? OR waste_type LIKE ?)";
        } else {
            $shipments_sql .= " AND waste_type LIKE ?";
        }
    }
    $shipments_sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($shipments_sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    
    $param_idx = 2;
    if ($search !== '') {
        if (is_numeric($clean_search) && $clean_search !== '') {
            $stmt->bindValue($param_idx++, intval($clean_search), PDO::PARAM_INT);
        }
        $stmt->bindValue($param_idx++, "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue($param_idx++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($param_idx++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $shipments = $stmt->fetchAll();

    // Calculate conversion rates (based on industry standards)
    $carbon_per_kg = 0.5;  // kg CO2 per kg waste
    $compost_conversion = 0.35;  // 35% of waste becomes compost
    $carbon_reduced = $total_weight * $carbon_per_kg;
    $compost_generated = $total_weight * $compost_conversion;

    // Update impact data (recalculate)
    if ($impact) {
        $stmt = $pdo->prepare("
            UPDATE impact_data 
            SET total_waste_kg = ?, carbon_reduced_kg = ?, compost_generated_kg = ? 
            WHERE user_id = ?
        ");
        $stmt->execute([$total_weight, $carbon_reduced, $compost_generated, $user_id]);
    }

    log_audit_action($user_id, 'DASHBOARD_VIEW', 'Accessed dashboard', $pdo);

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die("Error loading dashboard data");
}

// Status badge helper
function get_status_badge($status) {
    $badges = [
        'waiting' => ['bg-warning', 'Menunggu', 'bi-hourglass-split'],
        'picked_up' => ['bg-info', 'Dijemput', 'bi-truck'],
        'processed' => ['bg-primary', 'Diproses', 'bi-gear'],
        'finished' => ['bg-success', 'Selesai', 'bi-check-circle'],
        'cancelled' => ['bg-danger', 'Dibatalkan', 'bi-x-circle']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['bg-secondary', 'Unknown', 'bi-question-circle'];
}

// Get status progress (0-100%)
function get_status_progress($status) {
    $progress = [
        'waiting' => 20,
        'picked_up' => 40,
        'processed' => 70,
        'finished' => 100,
        'cancelled' => 0
    ];
    return $progress[$status] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mitra - pilah-pilih.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400..800;1,400..800&family=Share+Tech&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="Mitra.css" rel="stylesheet">
    
    <style>
        /* =========================================================
   DASHBOARD LAYOUT FIXES (SIDEBAR & SPLIT GRID)
   ========================================================= */
body {
    background-color: #f7f8f4; /* Warna background dashboard yang soft/abu-abu terang */
}

/* Membungkus seluruh halaman agar bisa bersebelahan */
.dashboard-wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

/* --- STYLING SIDEBAR KIRI --- */
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
}

/* --- STYLING KONTEN UTAMA (KANAN) --- */
.main-content {
    margin-left: 280px; /* Memberi ruang untuk sidebar fixed */
    width: calc(100% - 280px);
    padding: 30px 40px;
}

/* --- STAT CARDS (BARIS PALING ATAS) --- */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    border: 1px solid rgba(136, 144, 99, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    background-color: rgba(136, 144, 99, 0.1);
    color: var(--primary-dark, #354024);
}

.stat-content h6 {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.stat-number {
    margin: 5px 0 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-dark, #354024);
}

.stat-label {
    color: #888;
}

/* --- TOMBOL TOGGLE UNTUK HP --- */
.sidebar-toggle-btn {
    display: none; /* Sembunyikan di laptop/PC */
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: var(--primary-dark, #354024);
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* --- RESPONSIVITAS (AGAR TIDAK HANCUR DI HP) --- */
@media (max-width: 991px) {
    .sidebar {
        transform: translateX(-100%); /* Sembunyikan sidebar ke kiri */
    }
    .sidebar.active {
        transform: translateX(0); /* Munculkan saat tombol ditekan */
    }
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 70px 15px 20px; /* Tambah padding atas agar tidak tertutup tombol toggle */
    }
    .sidebar-toggle-btn {
        display: block; /* Munculkan tombol di layar kecil */
    }
}

        /* CSS LAYOUT DENGAN SIDEBAR */
        body {
            background-color: var(--eco-white);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background-color: var(--primary-dark);
            color: var(--white);
            transition: all 0.3s;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 30px 20px;
        }

        .sidebar-brand {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--muted-sage);
            text-decoration: none;
            margin-bottom: 40px;
            display: block;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-item {
            margin-bottom: 12px;
        }

        .sidebar-link {
            color: var(--eco-white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 18px;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: var(--transition-smooth);
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: var(--primary-light);
            color: var(--white);
        }

        .sidebar-user {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        .sidebar-user .user-name {
            display: block;
            font-weight: 600;
            color: var(--muted-sage);
            margin-bottom: 10px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .btn-logout-sidebar {
            color: #ff6b6b;
            text-decoration: none;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition-smooth);
        }

        .btn-logout-sidebar:hover {
            color: #ff8787;
            transform: translateX(3px);
        }

        /* Main Content Styling */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 40px;
            transition: all 0.3s;
        }

        /* Scrollable Table Container */
        .table-scroll-container {
            max-height: 500px; /* Batas tinggi tabel */
            overflow-y: auto; /* Scroll vertikal jika data melebihi tinggi */
            overflow-x: auto; /* Scroll horizontal untuk responsive */
            border-radius: var(--radius-lg);
            background: var(--white);
            border: 1px solid rgba(136, 144, 99, 0.12);
            box-shadow: var(--shadow-sm);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .history-table th {
            background-color: #f7f8f4;
            color: var(--primary-dark);
            padding: 16px 20px;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 2;
            border-bottom: 2px solid rgba(136, 144, 99, 0.15);
        }

        .history-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(136, 144, 99, 0.08);
            vertical-align: middle;
        }

        /* Floating WhatsApp Button */
        .wa-floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #25d366;
            color: var(--white) !important;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            z-index: 9999;
            transition: var(--transition-smooth);
            text-decoration: none;
        }

        .wa-floating-btn:hover {
            transform: scale(1.1) translateY(-5px);
            background-color: #20ba5a;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }

        /* Filter & Search Panel Styling */
        .search-panel {
            background-color: var(--white);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 25px;
            border: 1px solid rgba(136, 144, 99, 0.08);
        }

        /* Modern Pagination Styling */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 0 10px;
        }

        /* Mobile Responsive adjustments */
        @media (max-width: 991px) {
            .sidebar {
                margin-left: -280px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .sidebar-toggle-btn {
                display: block !important;
            }
        }

        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1050;
            background-color: var(--primary-dark);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: var(--radius-sm);
        }
    </style>
</head>
<body>

    <!-- Tombol toggle sidebar untuk mobile/tablet -->
    <button class="sidebar-toggle-btn" id="sidebarToggle">
        <i class="bi bi-list"></i> Menu
    </button>

    <div class="dashboard-wrapper">
        <!-- SIDEBAR NAVIGATION -->
        <nav class="sidebar" id="sidebar">
            <div>
                <a href="#" class="sidebar-brand">
                    <span>pilah-pilih.</span>
                </a>
                <ul class="sidebar-menu">
                    <li class="sidebar-item">
                        <a href="#" class="sidebar-link active">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="form_jemput.php" class="sidebar-link">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Jadwalkan Jemput</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="#history" class="sidebar-link">
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

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            
            <?php if (isset($_GET['welcome'])): ?>
            <div class="welcome-banner mb-4">
                <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                        <div>
                            <h5 class="mb-1">Selamat Datang di Dashboard Mitra!</h5>
                            <p class="mb-0 text-muted">Kelola pengangkutan sampah organik Anda dengan mudah dan efisien.</p>
                        </div>
                    </div>
                    <button class="btn-close" onclick="this.parentElement.parentElement.remove();"></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Aksi Cepat Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="section-title mb-3"><i class="bi bi-lightning-charge-fill"></i> Aksi Cepat</h5>
                    <div class="action-buttons d-flex gap-3 flex-wrap">
                        <a href="form_jemput.php" class="action-btn action-btn-primary flex-fill">
                            <i class="bi bi-calendar-plus"></i>
                            <span>
                                <strong>Jadwalkan Jemput</strong>
                                <small>Buat permintaan pengangkutan baru</small>
                            </span>
                        </a>
                        <a href="#history" class="action-btn action-btn-tertiary flex-fill">
                            <i class="bi bi-file-text"></i>
                            <span>
                                <strong>Lihat Riwayat</strong>
                                <small>Laporan lengkap pengiriman</small>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistik Cepat Section -->
            <div class="row mb-4">
                <h5 class="section-title mb-3"><i class="bi bi-speedometer2"></i> Statistik Cepat</h5>

                <div class="col-md-4 mb-3">
                    <div class="stat-card stat-card-1 h-100">
                        <div class="stat-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="stat-content">
                            <h6>Total Pengiriman</h6>
                            <p class="stat-number"><?php echo $total_shipments; ?></p>
                            <small class="stat-label">Pengiriman sampah organik</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="stat-card stat-card-2 h-100">
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-content">
                            <h6>Total Berat</h6>
                            <p class="stat-number"><?php echo number_format($total_weight, 1); ?> <span class="unit">kg</span></p>
                            <small class="stat-label">Sampah yang telah diangkut</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="stat-card stat-card-3 h-100">
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-content">
                            <h6>Status Terakhir</h6>
                            <?php if ($last_pickup): 
                                $badge = get_status_badge($last_pickup['status']);
                            ?>
                                <p class="stat-number" style="font-size: 1.1rem; margin-top: 5px;">
                                    <span class="status-badge bg-<?php echo substr($badge[0], 3); ?>">
                                        <i class="bi <?php echo $badge[2]; ?>"></i> <?php echo $badge[1]; ?>
                                    </span>
                                </p>
                                <small class="stat-label"><?php echo date('d M Y', strtotime($last_pickup['created_at'])); ?></small>
                            <?php else: ?>
                                <p class="stat-number" style="font-size: 0.9rem; color: #999; margin-top: 10px;">Belum ada pengiriman</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Pengiriman Terbaru (Timeline) -->
            <?php if ($latest_shipment): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="section-title mb-3"><i class="bi bi-diagram-3"></i> Status Pengiriman Terbaru</h5>
                    <div class="timeline-card">
                        <div class="timeline-header">
                            <h6>Pengiriman #<?php echo str_pad($latest_shipment['id'], 5, '0', STR_PAD_LEFT); ?></h6>
                            <span class="timeline-date"><?php echo date('d M Y', strtotime($latest_shipment['created_at'])); ?></span>
                        </div>

                        <div class="progress-bar-container">
                            <div class="progress-bar-background">
                                <div class="progress-bar-fill" style="width: <?php echo get_status_progress($latest_shipment['status']); ?>%;"></div>
                            </div>
                        </div>

                        <div class="timeline-steps">
                            <div class="step <?php echo get_status_progress($latest_shipment['status']) >= 20 ? 'active' : ''; ?>">
                                <div class="step-circle">1</div>
                                <p class="step-label">Menunggu</p>
                            </div>
                            <div class="step <?php echo get_status_progress($latest_shipment['status']) >= 40 ? 'active' : ''; ?>">
                                <div class="step-circle">2</div>
                                <p class="step-label">Dijemput</p>
                            </div>
                            <div class="step <?php echo get_status_progress($latest_shipment['status']) >= 70 ? 'active' : ''; ?>">
                                <div class="step-circle">3</div>
                                <p class="step-label">Diproses</p>
                            </div>
                            <div class="step <?php echo get_status_progress($latest_shipment['status']) >= 100 ? 'active' : ''; ?>">
                                <div class="step-circle">4</div>
                                <p class="step-label">Selesai</p>
                            </div>
                        </div>

                        <div class="timeline-details">
                            <div class="detail-item">
                                <strong>Jenis Sampah:</strong> <?php echo htmlspecialchars($latest_shipment['waste_type']); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Berat Estimasi:</strong> <?php echo $latest_shipment['estimated_weight']; ?> kg
                                <?php if ($latest_shipment['actual_weight']): ?>
                                    <br><strong>Berat Aktual:</strong> <?php echo $latest_shipment['actual_weight']; ?> kg
                                <?php endif; ?>
                            </div>
                            <div class="detail-item">
                                <strong>Tanggal Jemput:</strong> <?php echo date('d M Y H:i', strtotime($latest_shipment['pickup_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dampak Lingkungan Section -->
            <div class="row mb-4">
                <h5 class="section-title mb-3"><i class="bi bi-graph-up-arrow"></i> Dampak Lingkungan</h5>

                <div class="col-md-6 mb-3">
                    <div class="impact-card impact-card-carbon h-100">
                        <div class="impact-icon">
                            <i class="bi bi-cloud-fill"></i>
                        </div>
                        <div class="impact-content">
                            <h6>Pengurangan Karbon</h6>
                            <p class="impact-number"><?php echo number_format($carbon_reduced, 2); ?></p>
                            <p class="impact-unit">kg CO₂ terhindar</p>
                            <small class="impact-info">Setara dengan menanam <?php echo floor($carbon_reduced / 20); ?> pohon</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="impact-card impact-card-compost h-100">
                        <div class="impact-icon">
                            <i class="bi bi-leaves"></i>
                        </div>
                        <div class="impact-content">
                            <h6>Kompos Dihasilkan</h6>
                            <p class="impact-number"><?php echo number_format($compost_generated, 2); ?></p>
                            <p class="impact-unit">kg kompos berkualitas</p>
                            <small class="impact-info">Dapat digunakan untuk pertanian berkelanjutan</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pengiriman & Filter Search Section -->
            <div class="row" id="history">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <h5 class="section-title m-0"><i class="bi bi-clock-history"></i> Riwayat Pengiriman</h5>
                    </div>

                    <!-- Panel Pencarian -->
                    <div class="search-panel">
                        <form method="GET" action="" class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0" 
                                           placeholder="Cari ID Pengiriman (contoh: 00012 atau 12) atau Jenis Sampah..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100" style="background-color: var(--primary-dark); border-color: var(--primary-dark)">
                                    Cari Data
                                </button>
                                <?php if ($search !== ''): ?>
                                    <a href="?" class="btn btn-outline-secondary">Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Wadah Tabel dengan Fitur Scroll -->
                    <div class="table-scroll-container">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>ID Pengiriman</th>
                                    <th>Tanggal Pembuatan</th>
                                    <th>Jenis Sampah</th>
                                    <th>Berat (kg)</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($shipments) > 0): ?>
                                    <?php foreach ($shipments as $shipment): 
                                        $badge = get_status_badge($shipment['status']);
                                    ?>
                                    <tr class="history-row">
                                        <td data-label="ID Pengiriman">
                                            <strong>#<?php echo str_pad($shipment['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td data-label="Tanggal"><?php echo date('d M Y', strtotime($shipment['created_at'])); ?></td>
                                        <td data-label="Jenis Sampah"><?php echo htmlspecialchars($shipment['waste_type']); ?></td>
                                        <td data-label="Berat"><?php echo $shipment['actual_weight'] ?? $shipment['estimated_weight']; ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge bg-<?php echo substr($badge[0], 3); ?>">
                                                <i class="bi <?php echo $badge[2]; ?>"></i> <?php echo $badge[1]; ?>
                                            </span>
                                        </td>
                                        <td data-label="Aksi">
                                            <button class="btn-detail" data-shipment-id="<?php echo $shipment['id']; ?>">
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p style="color: #999; margin: 0; font-family: 'Inter', sans-serif;">Data pengiriman tidak ditemukan</p>
                                            <a href="form_jemput.php" style="color: #2D5A27; font-weight: 600; text-decoration: none; font-family: 'Inter', sans-serif;">
                                                Buat pengiriman baru &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginasi (Prev & Next) -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="text-muted small">
                            Menampilkan data ke-<?php echo $offset + 1; ?> sampai <?php echo min($offset + $limit, $total_filtered_shipments); ?> dari <?php echo $total_filtered_shipments; ?> data
                        </div>
                        <nav>
                            <ul class="pagination mb-0">
                                <!-- Tombol Sebelumnya -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" style="color: var(--primary-dark)">
                                        <i class="bi bi-chevron-left"></i> Sblmnya
                                    </a>
                                </li>
                                
                                <!-- Indikator Halaman -->
                                <li class="page-item disabled">
                                    <span class="page-link text-dark">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?></span>
                                </li>

                                <!-- Tombol Selanjutnya -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" style="color: var(--primary-dark)">
                                        Selanjutnya <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <footer class="dashboard-footer mt-5 text-center py-4 border-top">
                <p class="text-muted mb-0">&copy; 2026 Pilah-Pilih. Logistik Sampah Organik. Semua Hak Dilindungi.</p>
            </footer>
        </main>
    </div>

    <!-- FLOATING WHATSAPP BUTTON (HUBUNGI CS) -->
    <a href="https://wa.me/628123456789?text=Halo%20Pilah-Pilih%2C%20saya%20ingin%20konsultasi%20mengenai%20layanan%20mitra." 
       class="wa-floating-btn" 
       target="_blank" 
       title="Hubungi Customer Service">
        <i class="bi bi-whatsapp"></i>
    </a>

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

        // Detail button handler
        document.querySelectorAll('.btn-detail').forEach(btn => {
            btn.addEventListener('click', function() {
                const shipmentId = this.getAttribute('data-shipment-id');
                alert('Detail Pengiriman #' + shipmentId.padStart(5, '0'));
            });
        });
    </script>
</body>
</html>