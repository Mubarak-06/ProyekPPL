<fieldset id="pengaturan-grosir" class="form-group">
    <legend>Harga Grosir</legend>
    <input type="hidden" name="grosir_aktif" value="0">
    <label><input class="cek-grosir" type="checkbox" name="grosir_aktif" value="1" @checked(old('grosir_aktif', $produk->grosir_aktif ?? false))> Aktifkan harga grosir</label>
    <div data-tingkatan>
        @foreach((array) old('grosir', session()->hasOldInput('grosir_aktif') ? [] : ($grosir ?? [])) as $index => $tingkat)
            <div data-tingkat>
                <label>Minimal pembelian <input type="number" name="grosir[{{ $index }}][min_qty]" min="2" max="4294967295" step="1" value="{{ is_scalar(data_get($tingkat, 'min_qty')) ? data_get($tingkat, 'min_qty') : '' }}" required></label>
                <label>Harga grosir per satuan (Rp) <input type="number" name="grosir[{{ $index }}][harga_satuan]" min="1" step="1" value="{{ is_scalar(data_get($tingkat, 'harga_satuan')) ? data_get($tingkat, 'harga_satuan') : '' }}" required></label>
                <button type="button" data-hapus title="Hapus harga grosir" aria-label="Hapus harga grosir">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
            </div>
        @endforeach
    </div>
    @foreach($errors->get('grosir*') as $pesan)
        @foreach($pesan as $teks)
            <p role="alert">{{ $teks }}</p>
        @endforeach
    @endforeach
    <button type="button" data-tambah>+ Tambah Harga Grosir</button>
    <template>
        <div data-tingkat>
            <label>Minimal pembelian <input type="number" min="2" max="4294967295" step="1" required></label>
            <label>Harga grosir per satuan (Rp) <input type="number" min="1" step="1" required></label>
            <button type="button" data-hapus title="Hapus harga grosir" aria-label="Hapus harga grosir">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </div>
    </template>
</fieldset>
<style>
    #pengaturan-grosir { min-width: 0; padding: 1rem; border: 1px solid #d1d5db; border-radius: .5rem; }
    #pengaturan-grosir .cek-grosir { width: auto; margin-right: .35rem; }
    #pengaturan-grosir [data-tingkat] {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
        gap: .75rem;
        align-items: end;
        margin: .75rem 0;
    }
    #pengaturan-grosir [data-tingkat] label { margin-bottom: 0; }
    #pengaturan-grosir [data-tingkat] input { margin-top: 4px; }
    #pengaturan-grosir button {
        border: 1px solid transparent;
        border-radius: 6px;
        font: inherit;
        cursor: pointer;
    }
    #pengaturan-grosir [data-tambah] {
        padding: 8px 12px;
        border-color: #a8c5ae;
        background: #f3faf4;
        color: #235b35;
        font-size: 12px;
        font-weight: 600;
    }
    #pengaturan-grosir [data-hapus] {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        background: #fff1f2;
        color: #be123c;
        font-size: 16px;
    }
    #pengaturan-grosir [data-hapus]:hover { background: #ffe4e6; color: #9f1239; }
    #pengaturan-grosir [data-hapus]:focus-visible { outline: 2px solid #be123c; outline-offset: 2px; }
</style>
<script>
    (() => {
        const pengaturan = document.getElementById('pengaturan-grosir');
        const daftar = pengaturan.querySelector('[data-tingkatan]');
        const template = pengaturan.querySelector('template');

        function urutkanInput() {
            daftar.querySelectorAll('[data-tingkat]').forEach((baris, indeks) => {
                const [minimal, harga] = baris.querySelectorAll('input');
                minimal.name = `grosir[${indeks}][min_qty]`;
                harga.name = `grosir[${indeks}][harga_satuan]`;
            });
        }

        pengaturan.querySelector('[data-tambah]').addEventListener('click', () => {
            daftar.append(template.content.cloneNode(true));
            urutkanInput();
            daftar.lastElementChild.querySelector('input').focus();
        });

        daftar.addEventListener('click', (event) => {
            const tombolHapus = event.target.closest('[data-hapus]');
            if (tombolHapus) {
                tombolHapus.closest('[data-tingkat]').remove();
                urutkanInput();
            }
        });

        urutkanInput();
    })();
</script>
