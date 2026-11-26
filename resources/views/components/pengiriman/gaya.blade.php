@once
    <style>
        .operasional-kirim { color: #111827; overflow-wrap: anywhere; }
        .operasional-kirim h2 { font-size: 18px; margin: 0 0 12px; }
        .kirim-panel { margin: 16px 0; padding: 16px; border: 1px solid #dce4ef; border-radius: 12px; background: #fff; }
        .kirim-form { display: grid; gap: 10px; margin: 12px 0; }
        .kirim-form label { display: grid; gap: 4px; }
        .kirim-form input:not([type="checkbox"]), .kirim-form textarea { width: 100%; min-width: 0; border: 1px solid #a7b4c4; border-radius: 8px; padding: 8px; font: inherit; }
        .kirim-form label:has(input[type="checkbox"]) { display: flex; align-items: center; }
        .operasional-kirim .kirim-tombol, .kirim-dialog .kirim-tombol { display: inline-flex; justify-content: center; align-items: center; min-height: 44px; width: fit-content; padding: 8px 14px; border: 1px solid #008a50; border-radius: 8px; background: #008a50; color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .operasional-kirim .kirim-tombol:hover, .kirim-dialog .kirim-tombol:hover { background: #007543; color: #fff; }
        .kirim-dialog .kirim-batal { background: #fff; color: #111827; border-color: #a7b4c4; }
        .operasional-kirim :focus-visible, .kirim-dialog :focus-visible { outline: 2px solid #008a50; outline-offset: 2px; }
        .kirim-foto { display: block; width: min(100%, 400px); max-height: 360px; object-fit: contain; object-position: left; border-radius: 8px; }
        .kirim-foto[hidden] { display: none; }
        .kirim-timeline { padding-left: 20px; }
        .kirim-timeline li { padding: 3px 0 8px 6px; }
        .kirim-timeline time { display: block; font-size: .85em; color: #475569; }
        .kirim-dialog { box-sizing: border-box; width: min(400px, calc(100% - 32px)); padding: 20px; border: 0; border-radius: 12px; color: #111827; font-family: inherit; }
        .kirim-dialog::backdrop { background: rgb(15 23 42 / 40%); }
        .kirim-dialog form { display: flex; flex-wrap: wrap; gap: 8px; }
    </style>
@endonce
