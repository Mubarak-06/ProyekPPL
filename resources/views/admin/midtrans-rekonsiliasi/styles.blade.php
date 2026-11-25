@push('styles')
    <style>
        /* Judul halaman */
        .rekon h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .rekon h2 {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 8px;
        }

        .rekon-header {
            margin-bottom: 20px;
        }

        .rekon-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .rekon-breadcrumb a {
            color: #64748B;
            text-decoration: none;
        }

        /* Card */
        .rekon-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .rekon-pad {
            padding: 20px;
        }

        /* Filter */
        .rekon-filter {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 12px;
        }

        .rekon-filter label {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 150px;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
        }

        .rekon-filter input,
        .rekon-filter select {
            width: 100%;
            height: 40px;
            padding: 0 12px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 400;
            outline: none;
            box-sizing: border-box;
        }

        .rekon-filter input:focus,
        .rekon-filter select:focus {
            border-color: #008A50;
        }

        /* Tombol */
        .rekon-filter button,
        .rekon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            background: #008A50;
            color: #FFFFFF;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
        }

        .rekon-filter button:hover,
        .rekon-btn:hover {
            background: #007344;
        }

        .rekon-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 40px;
            padding: 0 14px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #64748B;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            box-sizing: border-box;
        }

        .rekon-reset:hover {
            background: #F8FAFC;
        }

        /* Tabel */
        .rekon-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .rekon table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            text-align: left;
        }

        .rekon th {
            padding: 17px 12px;
            background: #FFFFFF;
            color: #111827;
            font-weight: 600;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }

        .rekon td {
            padding: 15px 12px;
            color: #334155;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }

        .rekon tbody tr:last-child td {
            border-bottom: none;
        }

        .rekon tbody tr:hover {
            background: #F8FAFC;
        }

        /* Tabel kosong */
        .rekon td.rekon-empty {
            text-align: center;
            padding: 30px;
            color: #64748B;
        }

        /* Link dan tombol detail */
        .rekon table a {
            color: #008A50;
            text-decoration: none;
            font-weight: 500;
        }

        .rekon table a:hover {
            text-decoration: underline;
        }

        .rekon-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 11px;
            border: 1px solid #E2E8F0;
            border-radius: 5px;
            color: #008A50;
            background: #FFFFFF;
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }

        .rekon-action:hover {
            background: #F0FDF4;
        }

        /* Keterangan */
        .rekon small {
            display: block;
            font-size: 11px;
            color: #64748B;
            line-height: 1.6;
        }

        /* Navigasi dan pagination */
        .rekon-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
            color: #64748B;
        }

        .rekon-links a {
            color: #008A50;
            text-decoration: none;
            font-weight: 500;
        }

        .rekon-links a:hover {
            text-decoration: underline;
        }

        /* Header halaman detail */
        .rekon-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        /* Tombol kembali */
        .rekon-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
        }

        .rekon-back:hover {
            background: #F8FAFC;
        }

        /* Header card detail */
        .rekon-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        /* Isi card detail */
        .rekon-card-body {
            padding: 20px;
        }

        /* Informasi detail dua kolom */
        .rekon-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px 30px;
        }

        .rekon-info-label {
            font-size: 11px;
            color: #64748B;
            margin-bottom: 6px;
        }

        .rekon-info-value {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
            overflow-wrap: anywhere;
        }

        /* Status pembayaran */
        .rekon-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .rekon-status.success {
            background: #DCFCE7;
            color: #15803D;
        }

        .rekon-status.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .rekon-status.failed {
            background: #FEE2E2;
            color: #DC2626;
        }

        /* Tombol pemeriksaan */
        .rekon-detail-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        /* Pesan hasil pemeriksaan */
        .rekon-notice {
            padding: 14px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #F8FAFC;
            color: #334155;
            font-size: 12px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .rekon-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .rekon-filter label {
                width: 100%;
                min-width: 0;
            }

            .rekon-info-grid {
                grid-template-columns: 1fr;
            }

            .rekon-detail-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush