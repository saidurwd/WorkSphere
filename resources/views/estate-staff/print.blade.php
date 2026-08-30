<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estate Staff - {{ $staff->quarter_code ?? $staff->staff_name }}</title>
    <style>
        :root {
            --brand: #1d4ed8;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--ink);
            margin: 0;
            padding: 32px;
            background: #fff;
        }

        .sheet {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: linear-gradient(135deg, var(--brand), #0ea5e9);
            color: #fff;
        }

        .doc-header .org {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .doc-header .org small {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: 0.85;
        }

        .doc-header .doc-title {
            text-align: right;
        }

        .doc-header .doc-title .label {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.85;
        }

        .doc-header .doc-title .number {
            font-size: 22px;
            font-weight: 700;
        }

        .doc-body {
            padding: 10px;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 32px;
            align-items: start;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px 32px;
            margin-top: 8px;
        }

        .field {
            border-bottom: 1px dashed var(--line);
            padding-bottom: 4px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field .k {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .field .v {
            font-size: 28px;
            font-weight: 500;
            white-space: pre-wrap;
        }

        .section-title {
            margin: 28px 0 12px;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--brand);
            border-left: 4px solid var(--brand);
            padding-left: 10px;
        }

        .qr-block {
            text-align: center;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 5px;
        }

        .qr-block svg {
            width: 100%;
            max-width: 320px;
            height: auto;
            display: block;
        }

        .qr-block .qr-cap {
            margin-top: 10px;
            font-size: 12px;
            color: var(--muted);
        }

        .doc-footer {
            padding: 16px 32px;
            border-top: 1px solid var(--line);
            font-size: 11px;
            color: var(--muted);
            text-align: center;
        }

        .toolbar {
            max-width: 800px;
            margin: 0 auto 16px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.secondary {
            background: #fff;
            color: var(--brand);
        }

        @media print {
            body {
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                border: none;
                border-radius: 0;
            }

            .doc-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar no-print">
        <a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('resources.index'), 'estate_staff') }}"
            class="btn secondary">Back</a>
        <button type="button" class="btn" onclick="window.print()">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
            </svg>
            Print
        </button>
    </div>

    <div class="sheet">
        <div class="doc-header">
            <div class="org">
                Residence Identification
            </div>
            <div class="doc-title">
                <div class="label">{{ $staff->residenceType?->residence_type_bn ?? '—' }}</div>
                <div class="number">{{ $staff->quarter_code ?? '—' }}</div>
            </div>
        </div>

        <div class="doc-body">
            <div class="layout">
                <div>
                    <div class="section-title">Staff Details</div>
                    <div class="grid">
                        <div class="field">
                            <div class="k">Estate</div>
                            <div class="v">{{ $staff->estate?->estate_name_bn ?? '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="k">Division</div>
                            <div class="v">{{ $staff->division?->division_name_bn ?? '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="k">PF Number</div>
                            <div class="v">{{ $staff->pf_number ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                <div class="qr-block">
                    {!! $qrSvg !!}
                </div>
            </div>
        </div>
        <!-- <div class="doc-footer">
            This record was generated electronically via the {{ config('app.name', 'Laravel') }}.
            Quarter Number: {{ $staff->quarter_code ?? '—' }} &middot; Printed on {{ now()->format('F d, Y h:i A') }}
        </div> -->
    </div>
</body>

</html>
