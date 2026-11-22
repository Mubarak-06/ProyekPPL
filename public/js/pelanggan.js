let formHapus = null;
let tombolHapus = null;

document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-password]');
    if (toggle) {
        const input = document.getElementById(toggle.dataset.password);
        input.type = input.type === 'password' ? 'text' : 'password';
        toggle.textContent = input.type === 'password' ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi';
    }

    const jumlah = event.target.closest('[data-jumlah]');
    if (jumlah) {
        const input = jumlah.parentElement.querySelector('input[type="number"]');
        const nilai = Math.min(Number(input.max || 1000000), Math.max(Number(input.min || 1), Number(input.value || 1) + Number(jumlah.dataset.jumlah)));
        if (nilai !== Number(input.value) && nilai >= 1) {
            input.value = nilai;
            if (jumlah.form?.hasAttribute('data-jumlah-langsung')) jumlah.form.requestSubmit();
        }
    }

    const foto = event.target.closest('[data-galeri]');
    if (foto) {
        const utama = document.querySelector('.foto-utama');
        const gambar = foto.querySelector('img');
        if (utama && gambar) utama.replaceChildren(gambar.cloneNode());
    }

    const salin = event.target.closest('[data-salin]');
    if (salin) {
        if (!navigator.clipboard) {
            salin.textContent = 'Pilih dan salin nomor di atas';
            return;
        }
        navigator.clipboard.writeText(salin.dataset.salin).then(function () {
            salin.textContent = 'Tersalin';
        }).catch(function () {
            salin.textContent = 'Pilih dan salin nomor di atas';
        });
    }

    const tutup = event.target.closest('[data-tutup]');
    if (tutup) tutup.closest('dialog').close();

    if (event.target.closest('[data-setuju-hapus]') && formHapus) {
        formHapus.dataset.disetujui = 'ya';
        document.getElementById('dialog-konfirmasi').close();
        if (tombolHapus) formHapus.requestSubmit(tombolHapus);
        else formHapus.requestSubmit();
    }
});

document.addEventListener('submit', function (event) {
    if (event.target.matches('[data-snap-payment]')) {
        event.preventDefault();
        bukaPembayaranSnap(event.target);
        return;
    }

    const pesan = event.submitter?.dataset.konfirmasiTombol || event.target.dataset.konfirmasi;
    if (pesan && event.target.dataset.disetujui !== 'ya') {
        event.preventDefault();
        formHapus = event.target;
        tombolHapus = event.submitter;
        const dialog = document.getElementById('dialog-konfirmasi');
        dialog.querySelector('[data-konfirmasi-teks]').textContent = pesan;
        dialog.showModal();
    }
});

async function bukaPembayaranSnap(form) {
    const tombol = form.querySelector('button');
    const status = document.querySelector('[data-snap-status]');
    if (tombol.disabled) return;
    tombol.disabled = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'},
        });
        if (!response.ok) throw new Error();
        const hasil = await response.json();
        if (hasil.status === 'ready' && (!hasil.token || !window.snap?.pay) && hasil.redirect_url) {
            window.location.assign(hasil.redirect_url);
            return;
        }
        if (hasil.status === 'ready' && hasil.token && window.snap?.pay) {
            const periksa = (hasilSnap) => {
                tombol.disabled = false;
                const formStatus = document.querySelector('[data-snap-check]');
                const idTransaksi = formStatus?.elements.transaction_id;
                if (idTransaksi && typeof hasilSnap?.transaction_id === 'string') {
                    idTransaksi.value = hasilSnap.transaction_id;
                }
                if (formStatus && (!idTransaksi || idTransaksi.value)) mulaiPeriksaSnap(formStatus);
            };
            window.snap.pay(hasil.token, {
                onSuccess: periksa, onPending: periksa, onError: periksa, onClose: () => periksa(),
            });
            return;
        }
        status.textContent = 'Pembayaran belum dapat dibuka. Muat ulang untuk memeriksa status.';
    } catch {
        status.textContent = 'Pembayaran belum dapat dibuka. Coba lagi setelah beberapa saat.';
    }
    tombol.disabled = false;
}

async function mulaiPeriksaSnap(form) {
    if (form.dataset.memeriksa) return;
    form.dataset.memeriksa = 'ya';
    try {
        for (let percobaan = 0; percobaan < 8; percobaan++) {
            if (percobaan) await new Promise(resolve => setTimeout(resolve, 16000));
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'},
            });
            if (response.status === 429) continue;
            if (!response.ok) return;
            const hasil = await response.json();
            if (hasil.redirect_url) {
                window.location.assign(hasil.redirect_url);
                return;
            }
            if (hasil.status === 'applied' || hasil.status === 'needs_reconciliation' || hasil.reason === 'already_paid') {
                window.location.reload();
                return;
            }
            const status = document.querySelector('[data-snap-status]');
            if (status && hasil.message) status.textContent = hasil.message;
            if (hasil.status === 'transaction_id_required') return;
        }
    } catch {
        // Form tetap tersedia untuk pemeriksaan manual.
    } finally {
        delete form.dataset.memeriksa;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const formStatus = document.querySelector('[data-snap-check]');
    if (formStatus && formStatus.checkValidity()) mulaiPeriksaSnap(formStatus);
    const formDana = document.querySelector('[data-snap-autostart]');
    if (formDana) bukaPembayaranSnap(formDana);
});

document.addEventListener('change', function (event) {
    const input = event.target;
    if (input.matches('[data-pilih-alamat] input[name="alamat_id"]')) input.form.requestSubmit();
    if (input.matches('[data-jumlah-langsung] input[name="jumlah"]')) {
        if (input.checkValidity()) input.form.requestSubmit();
        else input.reportValidity();
    }
    if (input.matches('[data-pilih-semua]')) {
        document.querySelectorAll('[data-pilih-item]').forEach(item => item.checked = input.checked);
        input.form.requestSubmit();
    }
    if (input.matches('[data-pilih-item]')) input.form.requestSubmit();

    if (input.matches('[data-preview]') && input.files[0]) {
        const preview = document.getElementById(input.dataset.preview);
        if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
        preview.dataset.url = URL.createObjectURL(input.files[0]);
        preview.src = preview.dataset.url;
        preview.hidden = false;
        input.closest('.foto-profil-form')?.querySelectorAll('.avatar-kosong, .avatar:not(#preview-profil)').forEach(item => item.hidden = true);
    }
    if (input.matches('[data-bukti]')) {
        const pesan = input.closest('[data-dropzone]').querySelector('[data-nama-file]');
        pesan.textContent = input.files[0]?.name || 'Belum ada file dipilih';
    }
});

const pilihanKeranjang = [...document.querySelectorAll('[data-pilih-item]')];
const pilihSemua = document.querySelector('[data-pilih-semua]');
if (pilihSemua) {
    const terpilih = pilihanKeranjang.filter(input => input.checked).length;
    pilihSemua.indeterminate = terpilih > 0 && terpilih < pilihanKeranjang.length;
}

document.querySelectorAll('[data-dropzone]').forEach(function (area) {
    area.addEventListener('dragover', event => event.preventDefault());
    area.addEventListener('drop', function (event) {
        event.preventDefault();
        const file = area.querySelector('input[type="file"]');
        if (event.dataTransfer.files.length === 1) {
            file.files = event.dataTransfer.files;
            file.dispatchEvent(new Event('change', {bubbles: true}));
        }
    });
});

document.querySelectorAll('[data-sukses]').forEach(dialog => dialog.showModal());

const wilayah = document.querySelector('[data-wilayah]');
if (wilayah) {
    const provinsi = wilayah.querySelector('#provinsi');
    const kota = wilayah.querySelector('#kota');
    const kecamatan = wilayah.querySelector('#kecamatan');
    const kelurahan = wilayah.querySelector('#kelurahan');
    const pesan = wilayah.querySelector('[data-wilayah-pesan]');

    function kosongkan(select, nama) {
        select.replaceChildren(new Option('Pilih ' + nama, ''));
    }
    async function muat(select, jenis, induk, pilihan = '') {
        kosongkan(select, jenis);
        if (!induk) return;
        select.disabled = true;
        try {
            const response = await fetch(wilayah.dataset.wilayah + '?' + new URLSearchParams({jenis, induk}));
            if (!response.ok) throw new Error('Wilayah tidak tersedia');
            const daftar = await response.json();
            daftar.forEach(item => select.add(new Option(item.nama, item.id, false, String(item.id) === String(pilihan))));
            pesan.textContent = '';
        } catch (error) {
            pesan.textContent = 'Gagal memuat wilayah. Silakan pilih kembali wilayah induk.';
        } finally {
            select.disabled = false;
        }
    }
    provinsi.addEventListener('change', function () {
        kosongkan(kecamatan, 'Kecamatan');
        kosongkan(kelurahan, 'Kelurahan');
        muat(kota, 'kota', provinsi.value);
    });
    kota.addEventListener('change', function () {
        kosongkan(kelurahan, 'Kelurahan');
        muat(kecamatan, 'kecamatan', kota.value);
    });
    kecamatan.addEventListener('change', () => muat(kelurahan, 'kelurahan', kecamatan.value));
    (async function () {
        await muat(kota, 'kota', wilayah.dataset.provinsi, wilayah.dataset.kota);
        await muat(kecamatan, 'kecamatan', wilayah.dataset.kota, wilayah.dataset.kecamatan);
        await muat(kelurahan, 'kelurahan', wilayah.dataset.kecamatan, wilayah.dataset.kelurahan);
    })();
}
