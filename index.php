<?php
// Aplikasi Kalkulator Diskon dengan Input Barang
// File: index.php

// Inisialisasi session
session_start();

// Inisialisasi variabel
$harga_total = 0;
$diskon_persen = 0;
$diskon_nominal = 0;
$total_diskon = 0;
$harga_akhir = 0;
$hemat = 0;
$error = '';
$success = '';
$jenis_diskon = 'persen';
$tipe_pelanggan = 'reguler';
$step = isset($_GET['step']) ? $_GET['step'] : 1;
$jumlah_barang = isset($_SESSION['jumlah_barang']) ? $_SESSION['jumlah_barang'] : 0;

// Preset diskon berdasarkan tipe pelanggan
$diskon_presets = [
    'reguler' => 0,
    'silver' => 5,
    'gold' => 10,
    'platinum' => 15,
    'vip' => 20
];

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

    // Step 4: Hitung diskon
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

            // Hitung harga akhir dan hemat
            $harga_akhir = $harga_total - $total_diskon;
            $hemat = $total_diskon;

            // Format untuk display
            $harga_total_display = number_format($harga_total, 0, ',', '.');
            $harga_akhir_display = number_format($harga_akhir, 0, ',', '.');
            $hemat_display = number_format($hemat, 0, ',', '.');
            $total_diskon_display = number_format($total_diskon, 0, ',', '.');
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
    <title>Aplikasi Kalkulator Diskon dengan Input Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            padding: 50px 0;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .card-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-secondary {
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
        }

        .result-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            display: none;
        }

        .result-box.show {
            display: block;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .price-display {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .discount-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin: 5px;
        }

        .member-badge {
            background: #ffd700;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px 0 0 10px;
        }

        .form-control.rounded-end {
            border-radius: 0 10px 10px 0;
        }

        .icon-box {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon-box i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
            position: relative;
        }

        .step.active {
            background: #667eea;
            color: white;
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: #e0e0e0;
            transform: translateY(-50%);
        }

        .step.completed:not(:last-child):after {
            background: #28a745;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .barang-row {
            transition: all 0.3s;
        }

        .barang-row:hover {
            background-color: #f8f9fa;
        }

        .step-title {
            text-align: center;
            margin-bottom: 30px;
            color: #667eea;
        }

        .step-title h2 {
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
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
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle"></i> <?php echo $success; ?>
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
                                                <td><?php echo number_format($barang['harga_satuan'], 0, ',', '.'); ?></td>
                                                <td><?php echo $barang['jumlah']; ?></td>
                                                <td><?php echo number_format($barang['harga_total'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Total Harga Semua Barang:</td>
                                            <td class="fw-bold">Rp <?php echo number_format($total_harga_semua, 0, ',', '.'); ?></td>
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
                                                value="<?php echo number_format($total_harga_semua, 0, ',', '.'); ?>" readonly>
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
                                                    value="<?php echo isset($_POST['diskon_nominal']) ? number_format(floatval(str_replace('.', '', $_POST['diskon_nominal'])), 0, ',', '.') : ''; ?>"
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
                                        <h3><i class="fas fa-check-circle"></i> Hasil Perhitungan</h3>

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
                                                <i class="fas fa-crown"></i> Anda Hemat: Rp <?php echo $hemat_display; ?>
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
                                            <button type="button" class="btn btn-light" onclick="window.print()">
                                                <i class="fas fa-print"></i> Cetak Hasil
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format input ke Rupiah
        function formatRupiah(input) {
            let value = input.value.replace(/[^\d]/g, '');
            let formatted = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            input.value = formatted;
        }

        // Toggle antara diskon persen dan nominal
        function toggleDiskonType() {
            const persenRadio = document.getElementById('persen');
            const nominalRadio = document.getElementById('nominal');
            const persenField = document.getElementById('diskonPersenField');
            const nominalField = document.getElementById('diskonNominalField');

            if (persenRadio.checked) {
                persenField.style.display = 'block';
                nominalField.style.display = 'none';
            } else {
                persenField.style.display = 'none';
                nominalField.style.display = 'block';
            }
        }

        // Hitung total harga per barang
        function calculateTotal(index) {
            const hargaInput = document.querySelector(`input[name="harga_satuan_${index}"]`);
            const jumlahInput = document.querySelector(`input[name="jumlah_${index}"]`);
            const totalInput = document.querySelector(`input[name="total_${index}"]`);

            const harga = parseFloat(hargaInput.value.replace(/[^\d]/g, '')) || 0;
            const jumlah = parseInt(jumlahInput.value) || 0;
            const total = harga * jumlah;

            totalInput.value = total.toLocaleString('id-ID');
        }

        // Inisialisasi tampilan saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            toggleDiskonType();

            // Format semua input harga saat halaman dimuat
            document.querySelectorAll('.harga-input').forEach(input => {
                formatRupiah(input);
            });

            // Hitung total untuk semua barang
            <?php if ($step == 2): ?>
                <?php for ($i = 1; $i <= $jumlah_barang; $i++): ?>
                    calculateTotal(<?php echo $i; ?>);
                <?php endfor; ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>