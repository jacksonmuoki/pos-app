
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Receipt {{ $order->receipt_no }}</title>

    @php
        $paper = (int) ($paper ?? 80);
        $paper = in_array($paper, [58, 80], true) ? $paper : 80;
    @endphp

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f5f8f6;
            color: #111;
            font-family: Arial, sans-serif;
        }

        .print-toolbar {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 18px;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 17px;
            border: 1px solid #e4ebe6;
            border-radius: 9px;
            background: #fff;
            color: #17251d;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .print-btn-primary {
            background: #15803d;
            border-color: #15803d;
            color: #fff;
        }

        .print-btn-primary:hover {
            background: #166534;
        }

        .paper-selector {
            min-height: 40px;
            padding: 0 10px;
            border: 1px solid #e4ebe6;
            border-radius: 9px;
            background: #fff;
            color: #17251d;
        }

        .receipt-paper {
            width: {{ $paper }}mm;
            max-width: 100%;
            min-height: 100px;
            margin: 0 auto 25px;
            padding: 4mm;
            background: #fff;
            box-shadow: 0 3px 18px rgba(0, 0, 0, .08);
        }

        .receipt-paper .receipt {
            width: 100%;
            max-width: none;
            margin: 0 auto;
            font-family: "Courier New", monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }

        .receipt-paper table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-paper th,
        .receipt-paper td {
            padding: 2px 1px;
            vertical-align: top;
        }

        .receipt-paper img {
            max-width: 100%;
            height: auto;
        }

        @media screen and (max-width: 420px) {
            .print-toolbar {
                padding: 12px 6px;
                gap: 6px;
            }

            .print-btn {
                padding: 0 10px;
                font-size: 11px;
            }
        }

        @media print {
            @page {
                size: {{ $paper }}mm auto;
                margin: 0;
            }

            html, body {
                width: {{ $paper }}mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .no-print {
                display: none !important;
            }

            .receipt-paper {
                width: {{ $paper }}mm !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 3mm !important;
                box-shadow: none !important;
                border: none !important;
            }

            .receipt-paper .receipt {
                width: 100% !important;
                font-size: 10px !important;
            }

            .receipt-paper table {
                width: 100% !important;
            }

            .receipt-paper tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="print-toolbar no-print">
        <button
            type="button"
            onclick="window.print()"
            class="print-btn print-btn-primary"
        >
            Print Receipt
        </button>

        <select
            class="paper-selector"
            aria-label="Receipt paper size"
            onchange="changePaper(this.value)"
        >
            <option value="58" @selected($paper === 58)>58mm Paper</option>
            <option value="80" @selected($paper === 80)>80mm Paper</option>
        </select>

        <a
            href="{{ route('receipts.download', $order) }}"
            class="print-btn"
        >
            Download
        </a>

        <a
            href="{{ route('receipts.index') }}"
            class="print-btn"
        >
            Back
        </a>
    </div>

    <main class="receipt-paper">
        @include('receipts._receipt')
    </main>

    <script>
        function changePaper(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('paper', value);
            window.location.href = url.toString();
        }
    </script>

</body>
</html>
