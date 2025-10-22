<?php
// File: index.php
// File utama aplikasi

// Include file yang diperlukan
require_once 'config.php';
require_once 'functions.php';

// Inisialisasi session
session_start();

// Inisialisasi variabel
$harga_total = 0;
$diskon_persen = 0;
$diskon_nominal = 0;
$total_diskon = 0;
$harga_akhir = 0;
$jumlah_bayar = 0;
$kembalian = 0;
$error = '';
$success = '';
$jenis_diskon = 'persen';
$tipe_pelanggan = 'reguler';
$step = isset($_GET['step']) ? $_GET['step'] : 1;
$jumlah_barang = isset($_SESSION['jumlah_barang']) ? $_SESSION['jumlah_barang'] : 0;

// Cek apakah perlu menampilkan modal sukses
$show_cetak_modal = false;
if (isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal']) {
    $show_cetak_modal = true;
    unset($_SESSION['show_success_modal']);
}

// Ambil riwayat transaksi
$riwayat_transaksi = getRiwayatTransaksi($conn);


// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Step 1: Input jumlah barang
    if (isset($_POST['submit_jumlah'])) {
        $jumlah_barang = intval($_POST['jumlah_barang']);

        if ($jumlah_barang <= 0 || $jumlah_barang > 20) {
            $error = 'Jumlah barang harus antara 1 dan 20!';
        } else {
            $_SESSION['jumlah_barang'] = $jumlah_barang;
            $_SESSION['barang'] = array();
            header('Location: index.php?step=2');
            exit;
        }
    }

    // Step 2: Input detail barang
    if (isset($_POST['submit_barang'])) {
        $barang_list = array();
        $total_harga_semua = 0;

        for ($i = 1; $i <= $jumlah_barang; $i++) {
            $nama_barang = isset($_POST["nama_barang_$i"]) ? trim($_POST["nama_barang_$i"]) : '';
            $harga_satuan = isset($_POST["harga_satuan_$i"]) ? floatval(str_replace('.', '', $_POST["harga_satuan_$i"])) : 0;
            $jumlah = isset($_POST["jumlah_$i"]) ? intval($_POST["jumlah_$i"]) : 0;
            $harga_total_barang = $harga_satuan * $jumlah;

            if (empty($nama_barang) || $harga_satuan <= 0 || $jumlah <= 0) {
                $error = "Data barang ke-$i tidak lengkap atau tidak valid!";
                break;
            }

            $barang_list[] = array(
                'nama' => $nama_barang,
                'harga_satuan' => $harga_satuan,
                'jumlah' => $jumlah,
                'harga_total' => $harga_total_barang
            );

            $total_harga_semua += $harga_total_barang;
        }

        if (empty($error)) {
            $_SESSION['barang'] = $barang_list;
            $_SESSION['total_harga_semua'] = $total_harga_semua;
            header('Location: index.php?step=3');
            exit;
        }
    }

    // Step 3: Konfirmasi barang
    if (isset($_POST['submit_konfirmasi'])) {
        header('Location: index.php?step=4');
        exit;
    }

    // Step 4: Hitung diskon dan simpan ke database
    if (isset($_POST['submit_diskon'])) {
        $harga_total = isset($_SESSION['total_harga_semua']) ? $_SESSION['total_harga_semua'] : 0;
        $jenis_diskon = isset($_POST['jenis_diskon']) ? $_POST['jenis_diskon'] : 'persen';
        $tipe_pelanggan = isset($_POST['tipe_pelanggan']) ? $_POST['tipe_pelanggan'] : 'reguler';

        // Validasi harga
        if ($harga_total <= 0) {
            $error = 'Total harga barang harus lebih dari 0!';
        } else {
            // Hitung diskon berdasarkan jenis
            if ($jenis_diskon == 'persen') {
                $diskon_persen = isset($_POST['diskon_persen']) ? floatval($_POST['diskon_persen']) : 0;

                // Tambah diskon member
                $diskon_member = $diskon_presets[$tipe_pelanggan];
                $total_diskon_persen = $diskon_persen + $diskon_member;

                // Batasi maksimal diskon 100%
                if ($total_diskon_persen > 100) {
                    $total_diskon_persen = 100;
                    $success = 'Diskon melebihi 100%, otomatis diset ke 100%';
                }

                $total_diskon = ($harga_total * $total_diskon_persen) / 100;
            } else {
                $diskon_nominal = isset($_POST['diskon_nominal']) ? floatval(str_replace('.', '', $_POST['diskon_nominal'])) : 0;

                // Tambah diskon member
                $diskon_member = ($harga_total * $diskon_presets[$tipe_pelanggan]) / 100;
                $total_diskon = $diskon_nominal + $diskon_member;

                // Batasi diskon tidak melebihi harga
                if ($total_diskon > $harga_total) {
                    $total_diskon = $harga_total;
                    $success = 'Total diskon melebihi harga barang, otomatis disesuaikan';
                }
            }

            // Hitung harga akhir
            $harga_akhir = $harga_total - $total_diskon;

            // TAMBAHKAN VALIDASI DI SINI
            if ($harga_akhir <= 0) {
                $error = 'Harga akhir tidak valid! Silakan periksa kembali perhitungan diskon.';
            } else {
                // Simpan nilai ke session untuk digunakan di langkah berikutnya
                $_SESSION['harga_akhir'] = $harga_akhir;
                $_SESSION['total_diskon'] = $total_diskon;
                $_SESSION['diskon_persen'] = $diskon_persen;
                $_SESSION['diskon_nominal'] = $diskon_nominal;
                $_SESSION['jenis_diskon'] = $jenis_diskon;
                $_SESSION['tipe_pelanggan'] = $tipe_pelanggan;

                // Format untuk display
                $harga_total_display = formatRupiah($harga_total);
                $harga_akhir_display = formatRupiah($harga_akhir);
                $total_diskon_display = formatRupiah($total_diskon);
            }
        }
    }

    // Step 5: Proses pembayaran
    if (isset($_POST['submit_pembayaran'])) {
        $harga_total = isset($_SESSION['total_harga_semua']) ? $_SESSION['total_harga_semua'] : 0;
        $jenis_diskon = isset($_POST['jenis_diskon']) ? $_POST['jenis_diskon'] : 'persen';
        $tipe_pelanggan = isset($_POST['tipe_pelanggan']) ? $_POST['tipe_pelanggan'] : 'reguler';
        $harga_akhir = isset($_POST['harga_akhir_hidden']) ? floatval($_POST['harga_akhir_hidden']) : 0;
        $jumlah_bayar = isset($_POST['jumlah_bayar']) ? floatval(str_replace('.', '', $_POST['jumlah_bayar'])) : 0;

        // Hitung kembalian
        $kembalian = $jumlah_bayar - $harga_akhir;

        if ($jumlah_bayar < $harga_akhir) {
            $error = 'Jumlah bayar kurang dari total pembayaran!';
        } else {
            // Hitung diskon berdasarkan jenis
            if ($jenis_diskon == 'persen') {
                $diskon_persen = isset($_POST['diskon_persen']) ? floatval($_POST['diskon_persen']) : 0;
                $diskon_nominal = 0;

                // Tambah diskon member
                $diskon_member = $diskon_presets[$tipe_pelanggan];
                $total_diskon_persen = $diskon_persen + $diskon_member;

                // Batasi maksimal diskon 100%
                if ($total_diskon_persen > 100) {
                    $total_diskon_persen = 100;
                    $success = 'Diskon melebihi 100%, otomatis diset ke 100%';
                }

                $total_diskon = ($harga_total * $total_diskon_persen) / 100;
            } else {
                $diskon_persen = 0;
                $diskon_nominal = isset($_POST['diskon_nominal']) ? floatval(str_replace('.', '', $_POST['diskon_nominal'])) : 0;

                // Tambah diskon member
                $diskon_member = ($harga_total * $diskon_presets[$tipe_pelanggan]) / 100;
                $total_diskon = $diskon_nominal + $diskon_member;

                // Batasi diskon tidak melebihi harga
                if ($total_diskon > $harga_total) {
                    $total_diskon = $harga_total;
                    $success = 'Total diskon melebihi harga barang, otomatis disesuaikan';
                }
            }

            // Siapkan data untuk disimpan
            $data_transaksi = array(
                'kode_transaksi' => generateKodeTransaksi(),
                'tanggal' => date('Y-m-d H:i:s'),
                'total_harga' => $harga_total,
                'jenis_diskon' => $jenis_diskon,
                'diskon_persen' => $diskon_persen,
                'diskon_nominal' => $diskon_nominal,
                'diskon_member' => $diskon_presets[$tipe_pelanggan],
                'total_diskon' => $total_diskon,
                'harga_akhir' => $harga_akhir,
                'jumlah_bayar' => $jumlah_bayar,
                'kembalian' => $kembalian,
                'tipe_pelanggan' => $tipe_pelanggan,
                'barang' => $_SESSION['barang']
            );

            // Simpan ke database
            $result = simpanTransaksi($conn, $data_transaksi);

            if ($result['success']) {
                // Reset session untuk transaksi baru
                unset($_SESSION['jumlah_barang']);
                unset($_SESSION['barang']);
                unset($_SESSION['total_harga_semua']);
                unset($_SESSION['harga_akhir']);
                unset($_SESSION['total_diskon']);
                unset($_SESSION['diskon_persen']);
                unset($_SESSION['diskon_nominal']);
                unset($_SESSION['jenis_diskon']);
                unset($_SESSION['tipe_pelanggan']);

                // Set flag untuk menampilkan modal notifikasi
                $_SESSION['show_success_modal'] = true;

                // Redirect ke halaman awal
                header('Location: index.php');
                exit;
            } else {
                $error = "Error: " . $result['error'];
            }
        }
    }

    // Reset form
    if (isset($_POST['reset'])) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}

// Ambil data barang dari session
$barang_list = isset($_SESSION['barang']) ? $_SESSION['barang'] : array();
$total_harga_semua = isset($_SESSION['total_harga_semua']) ? $_SESSION['total_harga_semua'] : 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Kalkulator Diskon dengan Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="container main-container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h1><i class="fas fa-calculator"></i> Kalkulator Diskon</h1>
                        <p>Hitung harga setelah diskon dengan mudah dan cepat</p>
                    </div>

                    <div class="card-body p-4">
                        <div class="icon-box">
                            <i class="fas fa-shopping-cart"></i>
                        </div>

                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
                            <div class="step <?php echo $step >= 4 ? ($step > 4 ? 'completed' : 'active') : ''; ?>">4</div>
                            <div class="step <?php echo $step >= 5 ? ($step > 5 ? 'completed' : 'active') : ''; ?>">5</div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Step 1: Input Jumlah Barang -->
                        <?php if ($step == 1): ?>
                            <div class="step-title">
                                <h2><i class="fas fa-list-ol"></i> Langkah 1: Input Jumlah Barang</h2>
                                <p>Masukkan jumlah barang yang akan dihitung diskonnya</p>
                            </div>

                            <form method="POST" action="">
                                <div class="row mb-4">
                                    <div class="col-md-6 mx-auto">
                                        <label for="jumlah_barang" class="form-label fw-bold">
                                            <i class="fas fa-boxes"></i> Jumlah Barang
                                        </label>
                                        <input type="number" class="form-control" id="jumlah_barang"
                                            name="jumlah_barang" min="1" max="20" value="<?php echo $jumlah_barang; ?>" required>
                                        <div class="form-text">Maksimal 20 jenis barang</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php'">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                    <button type="submit" name="submit_jumlah" class="btn btn-primary">
                                        <i class="fas fa-arrow-right"></i> Lanjut ke Input Barang
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- Step 2: Input Detail Barang -->
                        <?php if ($step == 2): ?>
                            <div class="step-title">
                                <h2><i class="fas fa-box-open"></i> Langkah 2: Input Detail Barang</h2>
                                <p>Masukkan detail untuk setiap barang</p>
                            </div>

                            <form method="POST" action="">
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Barang</th>
                                                <th>Harga Satuan (Rp)</th>
                                                <th>Jumlah</th>
                                                <th>Harga Total (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for ($i = 1; $i <= $jumlah_barang; $i++): ?>
                                                <tr class="barang-row">
                                                    <td><?php echo $i; ?></td>
                                                    <td>
                                                        <input type="text" class="form-control" name="nama_barang_<?php echo $i; ?>"
                                                            placeholder="Nama barang" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control harga-input" name="harga_satuan_<?php echo $i; ?>"
                                                            placeholder="0" onkeyup="formatRupiah(this); calculateTotal(<?php echo $i; ?>)" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control jumlah-input" name="jumlah_<?php echo $i; ?>"
                                                            min="1" value="1" onchange="calculateTotal(<?php echo $i; ?>)" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control total-input" name="total_<?php echo $i; ?>"
                                                            placeholder="0" readonly>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?step=1'">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </button>
                                    <button type="submit" name="submit_barang" class="btn btn-primary">
                                        <i class="fas fa-arrow-right"></i> Lanjut ke Konfirmasi
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- Step 3: Konfirmasi Barang -->
                        <?php if ($step == 3): ?>
                            <div class="step-title">
                                <h2><i class="fas fa-clipboard-check"></i> Langkah 3: Konfirmasi Barang</h2>
                                <p>Periksa kembali data barang yang telah Anda input</p>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Barang</th>
                                            <th>Harga Satuan (Rp)</th>
                                            <th>Jumlah</th>
                                            <th>Harga Total (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1;
                                        foreach ($barang_list as $barang): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($barang['nama']); ?></td>
                                                <td><?php echo formatRupiah($barang['harga_satuan']); ?></td>
                                                <td><?php echo $barang['jumlah']; ?></td>
                                                <td><?php echo formatRupiah($barang['harga_total']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Total Harga Semua Barang:</td>
                                            <td class="fw-bold">Rp <?php echo formatRupiah($total_harga_semua); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <form method="POST" action="">
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?step=2'">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </button>
                                    <div>
                                        <button type="submit" name="submit_konfirmasi" class="btn btn-primary me-2">
                                            <i class="fas fa-arrow-right"></i> Lanjut ke Hitung Diskon
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?step=1'">
                                            <i class="fas fa-redo"></i> Input Ulang
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- Step 4: Hitung Diskon -->
                        <?php if ($step == 4): ?>
                            <div class="step-title">
                                <h2><i class="fas fa-percent"></i> Langkah 4: Hitung Diskon</h2>
                                <p>Masukkan detail diskon untuk total pembelian</p>
                            </div>

                            <form method="POST" action="">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-money-bill-wave"></i> Total Harga Semua Barang
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control rounded-end"
                                                value="<?php echo formatRupiah($total_harga_semua); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="tipe_pelanggan" class="form-label fw-bold">
                                            <i class="fas fa-user-tag"></i> Tipe Pelanggan
                                        </label>
                                        <select class="form-select" id="tipe_pelanggan" name="tipe_pelanggan">
                                            <option value="reguler" <?php echo $tipe_pelanggan == 'reguler' ? 'selected' : ''; ?>>
                                                Reguler (0%)
                                            </option>
                                            <option value="silver" <?php echo $tipe_pelanggan == 'silver' ? 'selected' : ''; ?>>
                                                Silver (+5%)
                                            </option>
                                            <option value="gold" <?php echo $tipe_pelanggan == 'gold' ? 'selected' : ''; ?>>
                                                Gold (+10%)
                                            </option>
                                            <option value="platinum" <?php echo $tipe_pelanggan == 'platinum' ? 'selected' : ''; ?>>
                                                Platinum (+15%)
                                            </option>
                                            <option value="vip" <?php echo $tipe_pelanggan == 'vip' ? 'selected' : ''; ?>>
                                                VIP (+20%)
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-percent"></i> Jenis Diskon
                                        </label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="jenis_diskon" id="persen"
                                                value="persen" <?php echo $jenis_diskon == 'persen' ? 'checked' : ''; ?>
                                                onchange="toggleDiskonType()">
                                            <label class="btn btn-outline-primary" for="persen">
                                                <i class="fas fa-percentage"></i> Persentase (%)
                                            </label>

                                            <input type="radio" class="btn-check" name="jenis_diskon" id="nominal"
                                                value="nominal" <?php echo $jenis_diskon == 'nominal' ? 'checked' : ''; ?>
                                                onchange="toggleDiskonType()">
                                            <label class="btn btn-outline-primary" for="nominal">
                                                <i class="fas fa-money-bill"></i> Nominal (Rp)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div id="diskonPersenField">
                                            <label for="diskon_persen" class="form-label fw-bold">
                                                <i class="fas fa-percentage"></i> Besar Diskon (%)
                                            </label>
                                            <input type="number" class="form-control" id="diskon_persen"
                                                name="diskon_persen" min="0" max="100" step="0.1"
                                                value="<?php echo $diskon_persen; ?>" placeholder="0">
                                        </div>

                                        <div id="diskonNominalField" style="display: none;">
                                            <label for="diskon_nominal" class="form-label fw-bold">
                                                <i class="fas fa-money-bill"></i> Besar Diskon (Rp)
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control rounded-end" id="diskon_nominal"
                                                    name="diskon_nominal" placeholder="0"
                                                    value="<?php echo isset($_POST['diskon_nominal']) ? formatRupiah(floatval(str_replace('.', '', $_POST['diskon_nominal']))) : ''; ?>"
                                                    onkeyup="formatRupiah(this)">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" name="submit_diskon" class="btn btn-primary w-100">
                                            <i class="fas fa-calculator"></i> Hitung Diskon
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?step=3'">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </button>
                                    <button type="submit" name="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo"></i> Reset Semua
                                    </button>
                                </div>
                            </form>

                            <?php if ($harga_akhir > 0): ?>
                                <div class="result-box show">
                                    <div class="text-center">
                                        <h3><i class="fas fa-check-circle"></i> Hasil Perhitungan Diskon</h3>

                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <div class="discount-badge">
                                                    <i class="fas fa-tag"></i> Total Harga
                                                </div>
                                                <div class="price-display">Rp <?php echo $harga_total_display; ?></div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="discount-badge">
                                                    <i class="fas fa-piggy-bank"></i> Total Diskon
                                                </div>
                                                <div class="price-display">Rp <?php echo $total_diskon_display; ?></div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="discount-badge">
                                                    <i class="fas fa-shopping-cart"></i> Harga Akhir
                                                </div>
                                                <div class="price-display">Rp <?php echo $harga_akhir_display; ?></div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <span class="member-badge">
                                                <i class="fas fa-crown"></i> Anda Hemat: Rp <?php echo $total_diskon_display; ?>
                                            </span>
                                        </div>

                                        <?php if ($tipe_pelanggan != 'reguler'): ?>
                                            <div class="mt-3">
                                                <small>
                                                    <i class="fas fa-info-circle"></i>
                                                    Diskon member <?php echo strtoupper($tipe_pelanggan); ?>:
                                                    <?php echo $diskon_presets[$tipe_pelanggan]; ?>%
                                                    telah ditambahkan
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mt-4">
                                            <button type="button" class="btn btn-light me-2" onclick="window.location.href='index.php?step=5'">
                                                <i class="fas fa-money-bill-wave"></i> Lanjut ke Pembayaran
                                            </button>
                                            <button type="button" class="btn btn-light" onclick="window.location.href='index.php'">
                                                <i class="fas fa-plus"></i> Transaksi Baru
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Step 5: Pembayaran -->
                        <?php if ($step == 5): ?>
                            <?php
                            // Ambil nilai dari session jika ada
                            if (!isset($harga_akhir) || $harga_akhir == 0) {
                                $harga_akhir = isset($_SESSION['harga_akhir']) ? $_SESSION['harga_akhir'] : 0;
                                $total_diskon = isset($_SESSION['total_diskon']) ? $_SESSION['total_diskon'] : 0;
                                $diskon_persen = isset($_SESSION['diskon_persen']) ? $_SESSION['diskon_persen'] : 0;
                                $diskon_nominal = isset($_SESSION['diskon_nominal']) ? $_SESSION['diskon_nominal'] : 0;
                                $jenis_diskon = isset($_SESSION['jenis_diskon']) ? $_SESSION['jenis_diskon'] : 'persen';
                                $tipe_pelanggan = isset($_SESSION['tipe_pelanggan']) ? $_SESSION['tipe_pelanggan'] : 'reguler';
                                $harga_total = isset($_SESSION['total_harga_semua']) ? $_SESSION['total_harga_semua'] : 0;

                                // Format untuk display
                                $harga_total_display = formatRupiah($harga_total);
                                $harga_akhir_display = formatRupiah($harga_akhir);
                                $total_diskon_display = formatRupiah($total_diskon);
                            }
                            ?>

                            <div class="step-title">
                                <h2><i class="fas fa-money-check-alt"></i> Langkah 5: Pembayaran</h2>
                                <p>Masukkan jumlah pembayaran dan hitung kembalian</p>
                            </div>

                            <form method="POST" action="">
                                <input type="hidden" id="harga_akhir_hidden" name="harga_akhir_hidden" value="<?php echo $harga_akhir; ?>">
                                <input type="hidden" name="jenis_diskon" value="<?php echo $jenis_diskon; ?>">
                                <input type="hidden" name="tipe_pelanggan" value="<?php echo $tipe_pelanggan; ?>">
                                <input type="hidden" name="diskon_persen" value="<?php echo $diskon_persen; ?>">
                                <input type="hidden" name="diskon_nominal" value="<?php echo $diskon_nominal; ?>">

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-shopping-cart"></i> Total Harga
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control rounded-end"
                                                value="<?php echo $harga_total_display; ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-piggy-bank"></i> Total Diskon
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control rounded-end"
                                                value="<?php echo $total_diskon_display; ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-money-bill-wave"></i> Harga Akhir
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control rounded-end"
                                                value="<?php echo $harga_akhir_display; ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="jumlah_bayar" class="form-label fw-bold">
                                            <i class="fas fa-money-check"></i> Jumlah Bayar
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control rounded-end" id="jumlah_bayar"
                                                name="jumlah_bayar" placeholder="0"
                                                onkeyup="formatRupiah(this); hitungKembalian()" required>
                                        </div>
                                        <div id="errorBayar" class="invalid-feedback d-none">
                                            Jumlah bayar kurang dari total pembayaran!
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-hand-holding-usd"></i> Kembalian
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control rounded-end" id="kembalian"
                                                value="0" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" name="submit_pembayaran" id="submitSimpan" class="btn btn-primary w-100" disabled>
                                            <i class="fas fa-save"></i> Simpan Transaksi
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?step=4'">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </button>
                                    <button type="submit" name="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo"></i> Reset Semua
                                    </button>
                                </div>
                            </form>

                            <?php if ($kembalian >= 0 && $jumlah_bayar > 0): ?>
                                <div class="result-box show">
                                    <div class="text-center">
                                        <h3><i class="fas fa-check-circle"></i> Transaksi Berhasil</h3>

                                        <div class="row mt-4">
                                            <div class="col-md-3">
                                                <div class="discount-badge">
                                                    <i class="fas fa-tag"></i> Total Harga
                                                </div>
                                                <div class="price-display">Rp <?php echo $harga_total_display; ?></div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="discount-badge">
                                                    <i class="fas fa-piggy-bank"></i> Total Diskon
                                                </div>
                                                <div class="price-display">Rp <?php echo $total_diskon_display; ?></div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="discount-badge">
                                                    <i class="fas fa-money-bill-wave"></i> Jumlah Bayar
                                                </div>
                                                <div class="price-display">Rp <?php echo $jumlah_bayar_display; ?></div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="discount-badge">
                                                    <i class="fas fa-hand-holding-usd"></i> Kembalian
                                                </div>
                                                <div class="price-display">Rp <?php echo $kembalian_display; ?></div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <span class="member-badge">
                                                <i class="fas fa-crown"></i> Anda Hemat: Rp <?php echo $total_diskon_display; ?>
                                            </span>
                                        </div>

                                        <div class="mt-4">
                                            <button type="button" class="btn btn-light me-2" onclick="window.location.href='index.php'">
                                                <i class="fas fa-plus"></i> Transaksi Baru
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Riwayat Transaksi -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-history"></i> Riwayat Transaksi</h4>
                    </div>
                    <div class="card-body p-2">
                        <?php if (empty($riwayat_transaksi)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Belum ada transaksi</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-riwayat table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Total</th>
                                            <th>Diskon</th>
                                            <th>Bayar</th>
                                            <th>Kembali</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($riwayat_transaksi as $transaksi): ?>
                                            <tr>
                                                <td>
                                                    <div class="kode-transaksi"><?php echo $transaksi['kode_transaksi']; ?></div>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m', strtotime($transaksi['tanggal'])); ?></small><br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($transaksi['tanggal'])); ?></small>
                                                </td>
                                                <td>Rp <?php echo formatRupiah($transaksi['total_harga']); ?></td>
                                                <td>Rp <?php echo formatRupiah($transaksi['total_diskon']); ?></td>
                                                <td>Rp <?php echo formatRupiah($transaksi['jumlah_bayar']); ?></td>
                                                <td>Rp <?php echo formatRupiah($transaksi['kembalian']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-outline-primary btn-action btn-sm"
                                                        onclick="showDetail(<?php echo $transaksi['id']; ?>)"
                                                        title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success btn-action btn-sm"
                                                        onclick="cetakStruk(<?php echo $transaksi['id']; ?>)"
                                                        title="Cetak Struk">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-action btn-sm"
                                                        onclick="hapusTransaksi(<?php echo $transaksi['id']; ?>, '<?php echo $transaksi['kode_transaksi']; ?>')"
                                                        title="Hapus Transaksi">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Transaksi -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content akan diisi via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Container untuk Toast Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <!-- Modal Cetak Struk Otomatis -->
    <?php if ($show_cetak_modal): ?>
        <div class="modal fade" id="cetakModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle"></i> Transaksi Berhasil
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-receipt fa-4x text-success"></i>
                        </div>
                        <h4>Transaksi berhasil disimpan!</h4>
                        <p class="text-info mt-2">Anda dapat mencetak struk melalui riwayat transaksi</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="fas fa-check"></i> OK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>

    <!-- Script untuk menampilkan modal cetak otomatis -->
    <?php if ($show_cetak_modal && isset($transaksi)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const cetakModal = new bootstrap.Modal(document.getElementById('cetakModal'));
                cetakModal.show();
            });
        </script>
    <?php endif; ?>
</body>

</html>