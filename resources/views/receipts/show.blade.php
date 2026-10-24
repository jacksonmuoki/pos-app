
@extends('layouts.app')

@section('title', 'Receipt Details')
@section('page-heading', 'Receipt Details')

@section('content')

<style>
    .receipt-page {
        --r-bg: #f5f8f6;
        --r-card: #ffffff;
        --r-text: #17251d;
        --r-muted: #718078;
        --r-border: #e4ebe6;
        --r-soft: #eff7f1;
        --r-green: #15803d;
        --r-green-dark: #166534;
        color: var(--r-text);
    }

    [data-theme="dark"] .receipt-page,
    .dark .receipt-page {
        --r-bg: #101a14;
        --r-card: #17231b;
        --r-text: #e5eee8;
        --r-muted: #9aaba0;
        --r-border: #2a3a30;
        --r-soft: #1d3325;
        --r-green: #4ade80;
        --r-green-dark: #22c55e;
    }

    .receipt-page * {
        box-sizing: border-box;
    }

    .receipt-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 20px;
    }

    .receipt-toolbar h2 {
        margin: 0;
        color: var(--r-text);
        font-size: 21px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .receipt-toolbar p {
        margin: 5px 0 0;
        color: var(--r-muted);
        font-size: 12px;
    }

    .receipt-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .receipt-btn {
        height: 39px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 7px;
        padding: 0 14px;
        border: 1px solid var(--r-border);
        border-radius: 9px;
        background: var(--r-card);
        color: var(--r-text);
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: .2s ease;
    }

    .receipt-btn:hover {
        border-color: var(--r-green);
        color: var(--r-green);
    }

    .receipt-btn-primary {
        background: var(--r-green);
        color: #fff;
        border-color: var(--r-green);
    }

    .receipt-btn-primary:hover {
        background: var(--r-green-dark);
        color: #fff;
    }

    .receipt-preview-card {
        padding: 28px 16px;
        background: var(--r-card);
        border: 1px solid var(--r-border);
        border-radius: 15px;
        display: flex;
        justify-content: center;
        min-height: 350px;
    }

    .receipt-preview {
        width: 100%;
        max-width: 340px;
        padding: 20px 15px;
        background: #fff;
        color: #111;
        border: 1px solid #e5e7eb;
        box-shadow: 0 5px 22px rgba(0, 0, 0, .07);
    }

    .receipt-preview .receipt {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        font-family: "Courier New", monospace;
        font-size: 12px;
        line-height: 1.5;
        color: #111;
    }

    .receipt-preview table {
        width: 100%;
        border-collapse: collapse;
    }

    .receipt-preview img {
        max-width: 100%;
    }

    @media(max-width: 600px) {
        .receipt-toolbar {
            align-items: flex-start;
        }

        .receipt-toolbar h2 {
            font-size: 18px;
        }

        .receipt-actions {
            width: 100%;
        }

        .receipt-actions .receipt-btn {
            flex: 1;
        }

        .receipt-preview-card {
            padding: 14px 8px;
        }

        .receipt-preview {
            padding: 15px 10px;
        }
    }

    @media print {
        @page {
            margin: 0;
            size: auto;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        body * {
            visibility: hidden !important;
        }

        .receipt-preview,
        .receipt-preview * {
            visibility: visible !important;
        }

        .receipt-preview {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        .receipt-preview .receipt {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            font-family: monospace !important;
            font-size: 11px !important;
            color: #000 !important;
        }

        .no-print,
        header,
        nav,
        aside,
        footer {
            display: none !important;
        }
    }
</style>

<div class="receipt-page">

    <div class="receipt-toolbar no-print">
        <div>
            <h2>Receipt Preview</h2>
            <p>Review your receipt before printing or downloading.</p>
        </div>

        <div class="receipt-actions">
            <button
                type="button"
                onclick="window.print()"
                class="receipt-btn receipt-btn-primary"
            >
                <span>⎙</span> Print Receipt
            </button>

            <a
                href="{{ route('receipts.download', $order) }}"
                class="receipt-btn"
            >
                <span>↓</span> Download
            </a>

            <a
                href="{{ route('receipts.index') }}"
                class="receipt-btn"
            >
                Back
            </a>
        </div>
    </div>

    <div class="receipt-preview-card">
        <div class="receipt-preview">
            @include('receipts._receipt')
        </div>
    </div>

</div>

@endsection
