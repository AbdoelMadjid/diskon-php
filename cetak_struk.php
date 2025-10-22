<?php
// File: cetak_struk.php
// Untuk mencetak struk transaksi

require_once 'config.php';
require_once 'functions.php';

// Ambil ID transaksi dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID transaksi tidak valid");
}

$transaksi_id = intval($_GET['id']);
$data = getDetailTransaksi($conn, $transaksi_id);

if (!$data['transaksi']) {
    die("Data transaksi tidak ditemukan");
}

$transaksi = $data['transaksi'];
$barang = $data['barang'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.4;
            }

            .struk {
                width: 230px;
                margin: 0 auto;
                padding: 10px;
            }

            .header {
                text-align: center;
                margin-bottom: 10px;
                border-bottom: 1px dashed #000;
                padding-bottom: 10px;
            }

            .header h1 {
                font-size: 16px;
                margin: 0;
                font-weight: bold;
            }

            .header p {
                margin: 3px 0;
                font-size: 10px;
            }

            .info {
                margin-bottom: 10px;
            }

            .info p {
                margin: 3px 0;
            }

            .barang {
                margin-bottom: 10px;
            }

            .barang-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }

            .barang-nama {
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 120px;
            }

            .barang-harga {
                text-align: right;
            }

            .total {
                border-top: 1px dashed #000;
                border-bottom: 1px dashed #000;
                padding: 5px 0;
                margin: 10px 0;
            }

            .total-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
            }

            .pembayaran {
                margin-bottom: 10px;
            }

            .footer {
                text-align: center;
                margin-top: 15px;
                border-top: 1px dashed #000;
                padding-top: 10px;
            }

            .footer p {
                margin: 3px 0;
                font-size: 10px;
            }

            .btn-print {
                display: none;
            }
        }

        @media screen {
            body {
                margin: 0;
                padding: 20px;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.4;
                background-color: #f5f5f5;
            }

            .struk {
                width: 230px;
                margin: 0 auto;
                padding: 15px;
                background: white;
                border: 1px solid #ddd;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }

            .btn-print {
                display: block;
                margin: 20px auto;
                padding: 10px 20px;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="struk">
        <div class="header">
            <h1>TOKO SERBA ADA</h1>
            <p>Jl. Sudirman No. 123</p>
            <p>Telp: (021) 12345678</p>
        </div>

        <div class="info">
            <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal'])); ?></p>
            <p><strong>Kode:</strong> <?php echo $transaksi['kode_transaksi']; ?></p>
            <p><strong>Kasir:</strong> ADMIN</p>
        </div>

        <div class="barang">
            <?php foreach ($barang as $item): ?>
                <div class="barang-item">
                    <div class="barang-nama"><?php echo htmlspecialchars($item['nama_barang']); ?></div>
                    <div class="barang-harga"><?php echo $item['jumlah']; ?> x <?php echo formatRupiah($item['harga_satuan']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total">
            <div class="total-item">
                <div>Total:</div>
                <div><?php echo formatRupiah($transaksi['total_harga']); ?></div>
            </div>

            <?php if ($transaksi['jenis_diskon'] == 'persen'): ?>
                <div class="total-item">
                    <div>Diskon (<?php echo $transaksi['diskon_persen'] + $transaksi['diskon_member']; ?>%):</div>
                    <div>-<?php echo formatRupiah($transaksi['total_diskon']); ?></div>
                </div>
            <?php else: ?>
                <div class="total-item">
                    <div>Diskon:</div>
                    <div>-<?php echo formatRupiah($transaksi['total_diskon']); ?></div>
                </div>
            <?php endif; ?>

            <div class="total-item">
                <div>Total Bayar:</div>
                <div><?php echo formatRupiah($transaksi['harga_akhir']); ?></div>
            </div>
        </div>

        <div class="pembayaran">
            <div class="total-item">
                <div>Tunai:</div>
                <div><?php echo formatRupiah($transaksi['jumlah_bayar']); ?></div>
            </div>

            <div class="total-item">
                <div>Kembalian:</div>
                <div><?php echo formatRupiah($transaksi['kembalian']); ?></div>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih atas kunjungan Anda</p>
            <p>Barang yang sudah dibeli tidak dapat dikembalikan</p>
        </div>
    </div>

    <button class="btn-print" onclick="window.print()">Cetak Struk</button>
</body>

</html>