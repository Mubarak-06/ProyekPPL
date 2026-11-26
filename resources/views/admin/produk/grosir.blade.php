<fieldset id="pengaturan-grosir" class="form-group">
    <h3 class="grosir-title">Harga Grosir</h3>
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
    /* Pengaturan Harga Grosir */
    #pengaturan-grosir {
        min-width: 0;
        margin: 0 0 15px;
        padding: 15px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        background: #FFFFFF;
    }

    #pengaturan-grosir .grosir-title {
        margin: 0 0 14px;
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    #pengaturan-grosir > label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        cursor: pointer;
    }

    #pengaturan-grosir .cek-grosir {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: #008A50;
        cursor: pointer;
    }

    #pengaturan-grosir [data-tingkat] {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
        gap: 12px;
        align-items: end;
        padding: 12px;
        margin-bottom: 12px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
    }

    #pengaturan-grosir [data-tingkat] label {
        min-width: 0;
        margin-bottom: 0;
        font-size: 11px;
    }

    #pengaturan-grosir [data-tingkat] input {
        min-width: 0;
        height: 40px;
        margin-top: 6px;
    }

    #pengaturan-grosir button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        border-radius: 6px;
        font-family: 'Poppins', sans-serif;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }

    #pengaturan-grosir [data-tambah] {
        min-height: 36px;
        padding: 8px 12px;
        border-color: #BBF7D0;
        background: #F0FDF4;
        color: #008A50;
        font-size: 11px;
        font-weight: 500;
    }

    #pengaturan-grosir [data-tambah]:hover {
        background: #DCFCE7;
        border-color: #008A50;
    }

    #pengaturan-grosir [data-hapus] {
        width: 40px;
        height: 40px;
        background: #FEF2F2;
        border-color: #FECACA;
        color: #DC2626;
        font-size: 14px;
    }

    #pengaturan-grosir [data-hapus]:hover {
        background: #FEE2E2;
    }

    #pengaturan-grosir button:focus-visible {
        outline: 2px solid #008A50;
        outline-offset: 2px;
    }

    #pengaturan-grosir [role="alert"] {
        margin: 8px 0;
        font-size: 11px;
        color: #DC2626;
    }

    @media (max-width: 600px) {
        #pengaturan-grosir {
            padding: 12px;
        }

        #pengaturan-grosir [data-tingkat] {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
        }

        #pengaturan-grosir [data-tingkat] label:first-child {
            grid-column: 1 / -1;
        }

        #pengaturan-grosir [data-tambah] {
            width: 100%;
        }
    }
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
