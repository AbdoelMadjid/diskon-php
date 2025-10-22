// File: script.js
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

// Hitung kembalian
function hitungKembalian() {
    const hargaAkhir = parseFloat(document.getElementById('harga_akhir_hidden').value) || 0;
    const jumlahBayar = parseFloat(document.getElementById('jumlah_bayar').value.replace(/[^\d]/g, '')) || 0;
    const kembalian = jumlahBayar - hargaAkhir;
    
    document.getElementById('kembalian').value = kembalian >= 0 ? kembalian.toLocaleString('id-ID') : '0';
    
    // Validasi jika bayar kurang
    if (jumlahBayar < hargaAkhir) {
        document.getElementById('errorBayar').classList.remove('d-none');
        document.getElementById('submitSimpan').disabled = true;
    } else {
        document.getElementById('errorBayar').classList.add('d-none');
        document.getElementById('submitSimpan').disabled = false;
    }
}

// Tampilkan detail transaksi
function showDetail(transaksiId) {
    $.ajax({
        url: 'get_detail.php',
        type: 'GET',
        data: { id: transaksiId },
        success: function(response) {
            $('#modalContent').html(response);
            $('#detailModal').modal('show');
        },
        error: function() {
            $('#modalContent').html('<p class="text-center text-danger">Gagal memuat data</p>');
            $('#detailModal').modal('show');
        }
    });
}

// Cetak struk
function cetakStruk(transaksiId) {
    // Buat iframe tersembunyi untuk mencetak
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    
    // Set URL cetak struk
    iframe.src = 'cetak_struk.php?id=' + transaksiId;
    
    // Tunggu iframe selesai loading lalu cetak
    iframe.onload = function() {
        iframe.contentWindow.print();
        
        // Hapus iframe setelah dicetak
        setTimeout(function() {
            document.body.removeChild(iframe);
        }, 1000);
    };
}

// Hapus transaksi
function hapusTransaksi(transaksiId, kodeTransaksi) {
    // Tampilkan modal konfirmasi
    const modalHtml = `
        <div class="modal fade modal-confirm" id="hapusModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4>Apakah Anda yakin ingin menghapus transaksi ini?</h4>
                        <p class="text-muted">Kode Transaksi: <strong>${kodeTransaksi}</strong></p>
                        <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="button" class="btn btn-danger" id="confirmHapus">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Hapus modal yang sudah ada jika ada
    const existingModal = document.getElementById('hapusModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Tambahkan modal ke body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Tampilkan modal
    const hapusModal = new bootstrap.Modal(document.getElementById('hapusModal'));
    hapusModal.show();
    
    // Event listener untuk tombol hapus
    document.getElementById('confirmHapus').addEventListener('click', function() {
        // Kirim request AJAX untuk menghapus transaksi
        $.ajax({
            url: 'hapus_transaksi.php',
            type: 'POST',
            data: { id: transaksiId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Tampilkan notifikasi sukses
                    const toastHtml = `
                        <div class="toast align-items-center text-white bg-success border-0" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-check-circle me-2"></i> ${response.message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                            </div>
                        </div>
                    `;
                    
                    // Tambahkan toast ke container
                    const toastContainer = document.getElementById('toastContainer');
                    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
                    
                    // Tampilkan toast
                    const toastElement = toastContainer.lastElementChild;
                    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
                    toast.show();
                    
                    // Hapus toast setelah selesai
                    toastElement.addEventListener('hidden.bs.toast', function() {
                        toastElement.remove();
                    });
                    
                    // Reload halaman setelah delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Tampilkan notifikasi error
                    const toastHtml = `
                        <div class="toast align-items-center text-white bg-danger border-0" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-exclamation-circle me-2"></i> ${response.message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                            </div>
                        </div>
                    `;
                    
                    // Tambahkan toast ke container
                    const toastContainer = document.getElementById('toastContainer');
                    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
                    
                    // Tampilkan toast
                    const toastElement = toastContainer.lastElementChild;
                    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
                    toast.show();
                    
                    // Hapus toast setelah selesai
                    toastElement.addEventListener('hidden.bs.toast', function() {
                        toastElement.remove();
                    });
                }
                
                // Tutup modal
                hapusModal.hide();
                
                // Hapus modal dari DOM setelah ditutup
                document.getElementById('hapusModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            },
            error: function() {
                // Tambahkan animasi shake ke modal
                document.querySelector('#hapusModal .modal-content').classList.add('shake');
                
                // Hapus animasi setelah selesai
                setTimeout(function() {
                    document.querySelector('#hapusModal .modal-content').classList.remove('shake');
                }, 500);
            }
        });
    });
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Toggle diskon type
    toggleDiskonType();
    
    // Format semua input harga
    document.querySelectorAll('.harga-input').forEach(input => {
        formatRupiah(input);
    });
    
    // Hitung total untuk semua barang (jika di step 2)
    const jumlahBarang = document.querySelectorAll('.barang-row').length;
    for (let i = 1; i <= jumlahBarang; i++) {
        calculateTotal(i);
    }
    
    // Format input bayar jika ada
    const bayarInput = document.getElementById('jumlah_bayar');
    if (bayarInput) {
        formatRupiah(bayarInput);
    }
    
    // Tampilkan modal cetak otomatis jika ada parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('cetak')) {
        const cetakModal = new bootstrap.Modal(document.getElementById('cetakModal'));
        cetakModal.show();
    }
});