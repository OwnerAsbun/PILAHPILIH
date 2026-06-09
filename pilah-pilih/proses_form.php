<?php
require_once 'config.php'; // Pastikan koneksi DB tersedia

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil data dari formulir
    $jenis_kemitraan = sanitize_input($_POST['jenis_kemitraan']);
    $nama_perusahaan = sanitize_input($_POST['nama_perusahaan']);
    $nama_kontak     = sanitize_input($_POST['nama_kontak']);
    $email_kontak    = sanitize_input($_POST['email_kontak']);
    $nomor_telepon   = sanitize_input($_POST['nomor_telepon']);
    $pesan           = sanitize_input($_POST['pesan']);

    // 2. Simpan ke database (Asumsi Anda punya tabel 'kemitraan')
    try {
        $sql = "INSERT INTO kemitraan (jenis_kemitraan, nama_perusahaan, nama_kontak, email, telepon, pesan) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jenis_kemitraan, $nama_perusahaan, $nama_kontak, $email_kontak, $nomor_telepon, $pesan]);

        // 3. Redirect ke register_mitra.php dengan membawa data email & perusahaan
        $url = "register_mitra.php?email=" . urlencode($email_kontak) . "&company=" . urlencode($nama_perusahaan);
        header("Location: " . $url);
        exit();

    } catch (PDOException $e) {
        die("Gagal menyimpan data: " . $e->getMessage());
    }
}
?>