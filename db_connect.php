<?php
// db_connect.php

$servername = "localhost";
$username = "projec15_root"; // Ganti dengan username database Anda
$password = "@kaesquare123";     // Ganti dengan password database Anda
$dbname = "projec15_db_sampah";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}
?>
