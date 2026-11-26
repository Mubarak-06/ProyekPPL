@once
    <dialog class="kirim-dialog" id="dialog-aksi-pengiriman" aria-labelledby="judul-aksi-pengiriman" aria-describedby="pesan-aksi-pengiriman">
        <h2 id="judul-aksi-pengiriman"></h2>
        <p id="pesan-aksi-pengiriman"></p>
        <form method="dialog">
            <button class="kirim-tombol kirim-batal" value="batal" autofocus>Batal</button>
            <button class="kirim-tombol" value="setuju" data-setuju></button>
        </form>
    </dialog>
    <script>
        (() => {
            const dialog = document.getElementById('dialog-aksi-pengiriman');
            let form, tombol, disetujui;
            document.addEventListener('submit', event => {
                if (!event.target.matches('[data-konfirmasi-pengiriman]') || event.target === disetujui) return;
                event.preventDefault();
                form = event.target;
                tombol = event.submitter ?? form.querySelector('[type="submit"]');
                dialog.returnValue = '';
                dialog.querySelector('h2').textContent = form.dataset.judul;
                dialog.querySelector('p').textContent = form.dataset.konfirmasiPengiriman;
                dialog.querySelector('[data-setuju]').textContent = form.dataset.setuju;
                dialog.showModal();
            });
            dialog.addEventListener('close', () => {
                if (dialog.returnValue === 'setuju') {
                    disetujui = form;
                    form.requestSubmit(tombol);
                    disetujui = null;
                }
            });
        })();
    </script>
@endonce
