<?php
// File: get_detail.php
// Handler untuk AJAX detail transaksi

require_once 'config.php';
require_once 'functions.php';

if (isset($_GET['id'])) {
    $transaksi_id = intval($_GET['id']);
    $data = getDetailTransaksi($conn, $transaksi_id);

    if ($data['transaksi']) {
?>
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Kode Transaksi</h6>
                    <h5><?php echo $data['transaksi']['kode_transaksi']; ?></h5>
                </div>
                <div class="col-md-6 text-end">
                    <h6 class="text-muted">Tanggal</h6>
                    <h5><?php echo date('d/m/Y H:i', strtotime($data['transaksi']['tanggal'])); ?></h5>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted">Total Harga</h6>
                        <h4>Rp <?php echo formatRupiah($data['transaksi']['total_harga']); ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted">Total Diskon</h6>
                        <h4>Rp <?php echo formatRupiah($data['transaksi']['total_diskon']); ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted">Harga Akhir</h6>
                        <h4>Rp <?php echo formatRupiah($data['transaksi']['harga_akhir']); ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted">Tipe Pelanggan</h6>
                        <h4>
                            <?php echo ucfirst($data['transaksi']['tipe_pelanggan']); ?>
                            <span class="badge bg-<?php echo $data['transaksi']['tipe_pelanggan']; ?> ms-2">
                                <?php echo $data['transaksi']['tipe_pelanggan']; ?>
                            </span>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="text-center p-3 bg-success text-white rounded">
                        <h6>Jumlah Bayar</h6>
                        <h4>Rp <?php echo formatRupiah($data['transaksi']['jumlah_bayar']); ?></h4>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center p-3 bg-info text-white rounded">
                        <h6>Kembalian</h6>
                        <h4>Rp <?php echo formatRupiah($data['transaksi']['kembalian']); ?></h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($data['barang'] as $barang): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                <td>Rp <?php echo formatRupiah($barang['harga_satuan']); ?></td>
                                <td><?php echo $barang['jumlah']; ?></td>
                                <td>Rp <?php echo formatRupiah($barang['harga_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php if ($data['transaksi']['jenis_diskon'] == 'persen'): ?>
                        Diskon: <?php echo $data['transaksi']['diskon_persen']; ?>% +
                        Diskon Member: <?php echo $data['transaksi']['diskon_member']; ?>%
                    <?php else: ?>
                        Diskon: Rp <?php echo formatRupiah($data['transaksi']['diskon_nominal']); ?> +
                        Diskon Member: <?php echo $data['transaksi']['diskon_member']; ?>%
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    } else {
        echo '<p class="text-center text-danger">Data transaksi tidak ditemukan</p>';
    }
} else {
    echo '<p class="text-center text-danger">ID transaksi tidak valid</p>';
}
?>