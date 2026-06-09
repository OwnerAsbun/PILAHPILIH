<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pilah Pilih | Form Kemitraan</title>
  <!-- CSS Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts import -->
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Share+Tech&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'cafe-noir': '#4c3d19',
            'kombi': '#354024',
            'moss': '#889063',
            'tan-custom': '#cfbb88',
            'bone': '#e5d7c4',
            'kombi-dark': '#2a3319',
          },
          fontFamily: {
            'bricolage': ['Bricolage Grotesque', 'sans-serif'],
            'sharetech': ['Share Tech', 'monospace'],
            'playfair': ['Playfair Display', 'serif'],
            'inter': ['Inter', 'sans-serif'],
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="Formulir.css">
</head>
<body class="overflow-x-hidden">
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
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
              <a class="nav-link mx-lg-2 active" aria-current="page" href="Formulir.php">Gabung Mitra</a>
            </li>
            <li class="nav-item">
              <a class="nav-link mx-lg-2" href="login.php">Login</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
    <section class="hero-section form-hero"> 
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8 text-center">
            
            <h1 class="hero-title-form">
              Bergabunglah Bersama <br>
              <span class="hero-accent">Revolusi Sampah</span>
            </h1>
            <p class="hero-subtitle-form">
              Satu langkah kecil untuk nilai yang lebih besar. Mari kita ciptakan ekosistem sampah organik yang berkelanjutan bersama-sama.
            </p>
          </div>
        </div>
      </div>
    </section>

  <!-- BENEFITS SECTION (Dipindah ke atas Form dan mendapatkan lengkungan) -->
  <section class="benefits-section">
    <div class="container">
      <div class="section-header">
        <div class="section-label justify-content-center">
          <span class="label-line"></span>
          <span class="label-text">Keuntungan Mitra</span>
        </div>
        <h2 class="section-title">Mengapa Bergabung dengan Kami?</h2>
      </div>
      <div class="row g-4 mt-2">
        <div class="col-md-6 col-lg-3">
          <div class="benefit-card">
            <div class="benefit-icon">
              <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <h4 class="benefit-title">Ekosistem Solid</h4>
            <p class="benefit-desc">Terhubung dengan ratusan mitra industri untuk solusi terintegrasi.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="benefit-card">
            <div class="benefit-icon">
              <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
            <h4 class="benefit-title">Pertumbuhan Cepat</h4>
            <p class="benefit-desc">Skalabilitas bisnis dengan dukungan teknologi logistik terdepan.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="benefit-card">
            <div class="benefit-icon">
              <i data-lucide="leaf" class="w-6 h-6"></i>
            </div>
            <h4 class="benefit-title">Dampak Lingkungan</h4>
            <p class="benefit-desc">Berkontribusi langsung pada pengurangan emisi karbon dan limbah.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="benefit-card">
            <div class="benefit-icon">
              <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <h4 class="benefit-title">Kepatuhan Regulasi</h4>
            <p class="benefit-desc">Laporan transparan untuk memenuhi standar lingkungan nasional.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FORM SECTION -->
  <section class="form-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
          <div class="form-wrapper">
            <div class="form-header">
              <h2 class="form-title">Hubungkan Sampah Anda</h2>
              <p class="form-subtitle">Isi formulir di bawah untuk memulai kerjasama strategis bersama Pilah-Pilih.</p>
            </div>
            <form action="proses_form.php" method="POST" class="form-content">
              <div class="form-group">
                <label for="jenis_kemitraan" class="form-label">Jenis Kemitraan</label>
                <select class="form-select" id="jenis_kemitraan" name="jenis_kemitraan" required>
                  <option value="" selected disabled>Pilih peran Anda...</option>
                  <option value="Mitra Penghasil">Mitra Penghasil (Punya Sampah)</option>
                  <option value="Mitra Pengelola">Mitra Pengelola (Akan Mengolah)</option>
                  <option value="Investor">Investor Startup</option>
                  <option value="Peneliti">Mitra Riset</option>
                </select>
              </div>

              <div class="form-group">
                <label for="nama_perusahaan" class="form-label">Nama Perusahaan/Instansi</label>
                <input type="text" class="form-control" id="nama_perusahaan" name="nama_perusahaan" required placeholder="Contoh: PT Pangan Abadi">
              </div>

              <div class="form-group">
                <label for="nama_kontak" class="form-label">Nama Kontak / Penanggung Jawab</label>
                <input type="text" class="form-control" id="nama_kontak" name="nama_kontak" required placeholder="Nama lengkap Anda">
              </div>

              <div class="form-group">
                <label for="email_kontak" class="form-label">Email</label>
                <input type="email" class="form-control" id="email_kontak" name="email_kontak" required placeholder="contoh@perusahaan.com">
              </div>

              <div class="form-group">
                <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
                <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" required placeholder="+62 812-3456-7890">
              </div>

              <div class="form-group">
                <label for="pesan" class="form-label">Pesan/Catatan Tambahan</label>
                <textarea class="form-control" id="pesan" name="pesan" rows="4" placeholder="Deskripsikan jenis limbah atau solusi yang Anda butuhkan..."></textarea>
              </div>

              <button type="submit" name="submit" class="btn btn-submit-form">
                <span>Kirim Formulir</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
              </button>
            </form>
            <p class="form-note">*Kami akan menghubungi Anda dalam 24 jam untuk konsultasi lebih lanjut</p>
          </div>
        </div>
      </div>
    </div>
  </section>

<!-- FOOTER -->
  <footer class="footer-section">
    <div class="container">
      <div class="row justify-content-center">
        
        <div class="col-lg-4 col-md-6 mb-4 mb-lg-0 offset-lg-1">
          <h5 class="footer-logo mb-4">Pilah-Pilih.</h5>
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
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="animations.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    });
  </script>
</body>
</html>