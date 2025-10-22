<?php
// File: functions.php
// Fungsi-fungsi umum aplikasi

// Format angka ke Rupiah
function formatRupiah($angka)
{
    return number_format($angka, 0, ',', '.');
}

// Format tanggal Indonesia
function formatTanggalIndonesia($tanggal)
{
    $bulan = array(
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    );

    $pecahkan = explode('-', $tanggal);
    $tahun = $pecahkan[0];
    $bulan = $bulan[(int)$pecahkan[1]];
    $tanggal = $pecahkan[2];

    return $tanggal . ' ' . $bulan . ' ' . $tahun;
}

// Generate kode transaksi unik
function generateKodeTransaksi()
{
    return 'TRX' . date('YmdHis');
}

// Ambil riwayat transaksi dari database
function getRiwayatTransaksi($conn, $limit = 10)
{
    $riwayat = array();

    $sql = "SELECT t.*, COUNT(d.id) as jumlah_item 
            FROM transaksi t 
            LEFT JOIN detail_barang d ON t.id = d.transaksi_id 
            GROUP BY t.id 
            ORDER BY t.created_at DESC 
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $riwayat[] = $row;
    }

    return $riwayat;
}

// Simpan transaksi ke database
function simpanTransaksi($conn, $data)
{
    $conn->begin_transaction();

    try {
        // Insert ke tabel transaksi
        $sql = "INSERT INTO transaksi (kode_transaksi, tanggal, total_harga, jenis_diskon, 
                diskon_persen, diskon_nominal, diskon_member, total_diskon, harga_akhir, 
                jumlah_bayar, kembalian, tipe_pelanggan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssdsddddddss",
            $data['kode_transaksi'],
            $data['tanggal'],
            $data['total_harga'],
            $data['jenis_diskon'],
            $data['diskon_persen'],
            $data['diskon_nominal'],
            $data['diskon_member'],
            $data['total_diskon'],
            $data['harga_akhir'],
            $data['jumlah_bayar'],
            $data['kembalian'],
            $data['tipe_pelanggan']
        );
        $stmt->execute();

        $transaksi_id = $conn->insert_id;

        // Insert detail barang
        foreach ($data['barang'] as $barang) {
            $sql = "INSERT INTO detail_barang (transaksi_id, nama_barang, harga_satuan, jumlah, harga_total) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isddd",
                $transaksi_id,
                $barang['nama'],
                $barang['harga_satuan'],
                $barang['jumlah'],
                $barang['harga_total']
            );
            $stmt->execute();
        }

        // Commit transaksi
        $conn->commit();

        return array(
            'success' => true,
            'transaksi_id' => $transaksi_id,
            'kode_transaksi' => $data['kode_transaksi']
        );
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

// Ambil detail transaksi
function getDetailTransaksi($conn, $transaksi_id)
{
    $data = array();

    // Ambil data transaksi
    $sql = "SELECT * FROM transaksi WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['transaksi'] = $result->fetch_assoc();

    if ($data['transaksi']) {
        // Ambil detail barang
        $sql = "SELECT * FROM detail_barang WHERE transaksi_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transaksi_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['barang'] = $result->fetch_all(MYSQLI_ASSOC);
    }

    return $data;
}
