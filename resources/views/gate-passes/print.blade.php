<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gate Pass - {{ $gatePass->gate_pass_number }}</title>
    <style>
        :root {
            --brand: #1d4ed8;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
        }
        * { box-sizing: border-box; }
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
            padding: 24px 32px;
            background: linear-gradient(135deg, var(--brand), #0ea5e9);
            color: #fff;
        }
        .doc-header .org {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.3px;
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
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.85;
        }
        .doc-header .doc-title .number {
            font-size: 22px;
            font-weight: 700;
        }
        .doc-body { padding: 32px; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-badge.checked { background: #dcfce7; color: #166534; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 32px;
            margin-top: 8px;
        }
        .field { border-bottom: 1px dashed var(--line); padding-bottom: 8px; }
        .field.full { grid-column: 1 / -1; }
        .field .k {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .field .v { font-size: 15px; font-weight: 500; white-space: pre-wrap; }
        .section-title {
            margin: 28px 0 12px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--brand);
            border-left: 4px solid var(--brand);
            padding-left: 10px;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 40px;
        }
        .signature .line {
            border-top: 1px solid var(--ink);
            padding-top: 6px;
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
        .btn.secondary { background: #fff; color: var(--brand); }
        @media print {
            body { padding: 0; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; }
            .doc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('gate-passes.index') }}" class="btn secondary">Back</a>
        <button type="button" class="btn" onclick="window.print()">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
            </svg>
            Print
        </button>
    </div>

    <div class="sheet">
        <div class="doc-header">
            <div class="org">
                {{ config('app.name', 'Laravel') }}
                <small>Facility Management &amp; Security</small>
            </div>
            <div class="doc-title">
                <div class="label">Gate Pass</div>
                <div class="number">{{ $gatePass->gate_pass_number }}</div>
            </div>
        </div>

        <div class="doc-body">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="status-badge {{ $gatePass->isChecked() ? 'checked' : 'pending' }}">
                    {{ $gatePass->isChecked() ? 'Checked' : 'Pending Check' }}
                </span>
                <span style="font-size: 12px; color: var(--muted);">Issued: {{ $gatePass->issue_date->format('F d, Y') }}</span>
            </div>

            <div class="section-title">Visitor / Item Details</div>
            <div class="grid">
                <div class="field">
                    <div class="k">Name</div>
                    <div class="v">{{ $gatePass->name }}</div>
                </div>
                <div class="field">
                    <div class="k">Issue Date</div>
                    <div class="v">{{ $gatePass->issue_date->format('F d, Y') }}</div>
                </div>
                <div class="field">
                    <div class="k">Quantity</div>
                    <div class="v">{{ $gatePass->quantity }}</div>
                </div>
                <div class="field">
                    <div class="k">Address</div>
                    <div class="v">{{ $gatePass->address ?? '—' }}</div>
                </div>
                <div class="field full">
                    <div class="k">Purpose</div>
                    <div class="v">{{ $gatePass->purpose }}</div>
                </div>
                <div class="field full">
                    <div class="k">Description</div>
                    <div class="v">{{ $gatePass->description ?? '—' }}</div>
                </div>
            </div>

            <div class="section-title">Authorization</div>
            <div class="grid">
                <div class="field">
                    <div class="k">Prepared By</div>
                    <div class="v">{{ $gatePass->prepared_by ?? '—' }}</div>
                </div>
                <div class="field">
                    <div class="k">Checked By</div>
                    <div class="v">{{ $gatePass->checked_by ?? '—' }}</div>
                </div>
            </div>

            <div class="signatures">
                <div class="signature">
                    <div class="line">Signature of Prepared By</div>
                </div>
                <div class="signature">
                    <div class="line">Signature of Checked By / Security</div>
                </div>
            </div>
        </div>

        <div class="doc-footer">
            This gate pass was generated electronically via the {{ config('app.name', 'Laravel') }} dashboard.
            Pass No: {{ $gatePass->gate_pass_number }} &middot; Printed on {{ now()->format('F d, Y h:i A') }}
        </div>
    </div>
</body>
</html>
