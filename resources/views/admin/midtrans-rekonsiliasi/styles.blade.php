@push('styles')
    <style>
        .rekon h1 { font-size: 22px; margin: 0 0 8px; }
        .rekon h2 { font-size: 16px; margin: 0 0 8px; }
        .rekon a { color: #007344; }
        .rekon-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin: 16px 0;
        }
        .rekon-pad { padding: 16px; }
        .rekon-filter { display: flex; flex-wrap: wrap; align-items: end; gap: 12px; }
        .rekon-filter label { display: grid; gap: 5px; min-width: 0; }
        .rekon-filter input, .rekon-filter select, .rekon-filter button {
            font: inherit;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            max-width: 100%;
        }
        .rekon-filter button { background: #007344; color: #fff; cursor: pointer; }
        .rekon-scroll { overflow-x: auto; }
        .rekon table { width: 100%; border-collapse: collapse; text-align: left; }
        .rekon th, .rekon td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .rekon th { background: #f8fafc; }
        .rekon small { color: #475569; }
        .rekon-links { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
        .rekon-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); gap: 16px; margin: 0; }
        .rekon-fields dt { color: #475569; margin-bottom: 4px; }
        .rekon-fields dd { margin: 0; }
    </style>
@endpush
