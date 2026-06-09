<?php
$page_title = "Form Kemitraan | Pilah-Pilih";
$show_navbar = true;
include 'header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0 text-center text-success fw-bold">
                    <i class="fas fa-handshake"></i> Form Kemitraan
                </h4>
            </div>
            <div class="card-body p-4">
                <form action="proses_form.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Kemitraan:</label>
                        <select name="jenis_kemitraan" class="form-select" required>
                            <option value="" disabled selected>Pilih peran Anda...</option>
                            <option value="Mitra Penghasil">Mitra Penghasil (Produsen Limbah Organik)</option>
                            <option value="Mitra Pengelola">Mitra Pengelola (Pengolahan Limbah)</option>
                            <option value="Investor">Investor</option>
                            <option value="Peneliti">Peneliti / Akademis</option>
                        </select>
                        <small class="text-muted">Pilih jenis kemitraan yang sesuai dengan perusahaan Anda</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Perusahaan:</label>
                        <input type="text" name="nama_perusahaan" class="form-control" placeholder="Contoh: PT Pangan Abadi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kontak:</label>
                        <input type="text" name="nama_kontak" class="form-control" placeholder="Nama lengkap contact person" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email:</label>
                        <input type="email" name="email_kontak" class="form-control" placeholder="email@perusahaan.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor Telepon:</label>
                        <input type="tel" name="nomor_telepon" class="form-control" placeholder="+62-812-3456-7890" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pesan/Catatan:</label>
                        <textarea name="pesan" class="form-control" rows="3" placeholder="Catatan atau pertanyaan tambahan (opsional)"></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-success w-100 fw-bold">
                        <i class="fas fa-paper-plane"></i> Kirim Formulir
                    </button>
                </form>

                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle text-success"></i>
                        Setelah mengirim formulir, Anda akan diminta membuat akun untuk melanjutkan.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                <h5 class="card-title">Berkelanjutan</h5>
                <p class="card-text small text-muted">Solusi logistik limbah organik yang ramah lingkungan</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-rocket fa-3x text-success mb-3"></i>
                <h5 class="card-title">Efisien</h5>
                <p class="card-text small text-muted">Sistem manajemen logistik yang cepat dan terintegrasi</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                <h5 class="card-title">Terpercaya</h5>
                <p class="card-text small text-muted">Data terenkripsi dan sistem keamanan berlapis</p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>