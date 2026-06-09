<?php
$conn = mysqli_connect("localhost", "root", "", "db_pilah_pilih");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>