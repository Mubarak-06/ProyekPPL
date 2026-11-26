<style>
    .konfirmasi-keluar {
        box-sizing: border-box;
        width: min(400px, calc(100% - 32px));
        max-height: calc(100% - 32px);
        margin: auto;
        padding: 0;
        border: 0;
        border-radius: 12px;
        background: #fff;
        color: var(--navy, #111827);
        font-family: inherit;
        box-shadow: 0 12px 36px rgb(15 23 42 / 16%);
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 180ms ease, transform 180ms ease, display 180ms allow-discrete, overlay 180ms allow-discrete;
    }

    .konfirmasi-keluar[open] {
        opacity: 1;
        transform: translateY(0);
    }

    .konfirmasi-keluar::backdrop {
        background: rgb(15 23 42 / 40%);
        opacity: 0;
        transition: opacity 180ms ease, display 180ms allow-discrete, overlay 180ms allow-discrete;
    }

    .konfirmasi-keluar[open]::backdrop {
        opacity: 1;
    }

    @starting-style {
        .konfirmasi-keluar[open] {
            opacity: 0;
            transform: translateY(8px);
        }

        .konfirmasi-keluar[open]::backdrop {
            opacity: 0;
        }
    }

    .konfirmasi-keluar .isi-konfirmasi-keluar {
        padding: clamp(16px, 5vw, 24px);
        text-align: center;
    }

    .konfirmasi-keluar .ikon-konfirmasi-keluar {
        display: inline-grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--hijau-muda, #ecfdf5);
        color: var(--hijau, #008a50);
        font-size: 18px;
    }

    .konfirmasi-keluar h2 {
        margin: 10px 0 6px;
        font-size: 18px;
        font-weight: 600;
        line-height: 1.3;
    }

    .konfirmasi-keluar p {
        margin: 0;
        color: var(--abu, #64748b);
        font-size: 13px;
        line-height: 1.5;
    }

    .konfirmasi-keluar form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin: 16px 0 0;
    }

    .konfirmasi-keluar .tombol {
        min-height: 40px;
        padding: 8px;
        font-size: 13px;
        line-height: 1.4;
    }

    :where(.konfirmasi-keluar) .tombol {
        border: 1px solid var(--hijau, #008a50);
        border-radius: 8px;
        background: var(--hijau, #008a50);
        color: #fff;
        font-family: inherit;
        font-weight: 500;
        cursor: pointer;
    }

    :where(.konfirmasi-keluar) .tombol:hover {
        border-color: #007543;
        background: #007543;
    }

    :where(.konfirmasi-keluar) .tombol-outline {
        border-color: var(--border, #dce4ef);
        background: #fff;
        color: var(--navy, #111827);
    }

    :where(.konfirmasi-keluar) .tombol-outline:hover {
        border-color: var(--hijau, #008a50);
        background: var(--hijau-muda, #ecfdf5);
        color: var(--hijau, #008a50);
    }

    .konfirmasi-keluar button:focus-visible {
        outline: 2px solid var(--hijau, #008a50);
        outline-offset: 2px;
    }

    @media (prefers-reduced-motion: reduce) {
        .konfirmasi-keluar, .konfirmasi-keluar::backdrop {
            transition: none;
        }
    }
</style>

<dialog id="konfirmasi-keluar" class="konfirmasi-keluar" aria-labelledby="judul-konfirmasi-keluar" aria-describedby="pesan-konfirmasi-keluar">
    <div class="isi-konfirmasi-keluar">
        <i class="bi bi-box-arrow-right ikon-konfirmasi-keluar" aria-hidden="true"></i>
        <h2 id="judul-konfirmasi-keluar">Konfirmasi Keluar</h2>
        <p id="pesan-konfirmasi-keluar">Apakah Anda yakin ingin keluar dari akun Anda?</p>
        <form action="{{ route('pelanggan.logout') }}" method="POST">
            @csrf
            <button class="tombol tombol-outline" type="button" data-batal-keluar autofocus>Batal</button>
            <button class="tombol" type="submit">Ya, Keluar</button>
        </form>
    </div>
</dialog>

<script>
    (() => {
        const dialog = document.getElementById('konfirmasi-keluar');
        const batal = dialog.querySelector('[data-batal-keluar]');
        let pemicu;
        let mulaiDiLuar = false;

        document.querySelectorAll('[data-buka-konfirmasi-keluar]').forEach(tombol => {
            tombol.addEventListener('click', () => {
                const dropdown = tombol.closest('details.dropdown-akun');
                pemicu = dropdown?.querySelector('summary') ?? tombol;
                if (dropdown) {
                    dropdown.open = false;
                    pemicu.focus();
                }
                mulaiDiLuar = false;
                dialog.showModal();
            });
        });

        batal.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => pemicu?.focus());

        dialog.addEventListener('pointerdown', event => {
            mulaiDiLuar = event.target === dialog;
        });
        dialog.addEventListener('click', event => {
            if (mulaiDiLuar && event.target === dialog) {
                dialog.close();
            }
        });
    })();
</script>
