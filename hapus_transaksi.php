<?php
// File: hapus_transaksi.php
// Handler untuk menghapus transaksi

require_once 'config.php';
require_once 'functions.php';

// Cek apakah ada ID transaksi yang dikirim
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $response = [
        'success' => false,
        'message' => 'ID transaksi tidak valid'
    ];
    echo json_encode($response);
    exit;
}

$transaksi_id = intval($_POST['id']);

// Mulai transaksi
$conn->begin_transaction();

try {
    // Hapus detail barang terlebih dahulu
    $sql = "DELETE FROM detail_barang WHERE transaksi_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();

    // Hapus transaksi utama
    $sql = "DELETE FROM transaksi WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();

    // Commit transaksi
    $conn->commit();

    $response = [
        'success' => true,
        'message' => 'Transaksi berhasil dihapus'
    ];
} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollback();

    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
