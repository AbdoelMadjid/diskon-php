<?php
// File: config.php
// Konfigurasi database

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'diskon_app';

// Buat koneksi
$conn = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat tabel jika belum ada
$sql = "CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(20) NOT NULL,
    tanggal DATETIME NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    jenis_diskon ENUM('persen', 'nominal') NOT NULL,
    diskon_persen DECIMAL(5,2) DEFAULT 0,
    diskon_nominal DECIMAL(15,2) DEFAULT 0,
    diskon_member DECIMAL(5,2) DEFAULT 0,
    total_diskon DECIMAL(15,2) NOT NULL,
    harga_akhir DECIMAL(15,2) NOT NULL,
    jumlah_bayar DECIMAL(15,2) DEFAULT 0,
    kembalian DECIMAL(15,2) DEFAULT 0,
    tipe_pelanggan VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

$sql = "CREATE TABLE IF NOT EXISTS detail_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    nama_barang VARCHAR(100) NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    jumlah INT NOT NULL,
    harga_total DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

// Preset diskon berdasarkan tipe pelanggan
$diskon_presets = [
    'reguler' => 0,
    'silver' => 5,
    'gold' => 10,
    'platinum' => 15,
    'vip' => 20
];
