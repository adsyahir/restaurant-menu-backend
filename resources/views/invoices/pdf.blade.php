<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1c1917; font-size: 13px; margin: 0; padding: 40px; }
        .head { display: flex; justify-content: space-between; margin-bottom: 32px; }
        .brand { font-size: 20px; font-weight: bold; color: #b45309; }
        .muted { color: #78716c; }
        h1 { font-size: 26px; margin: 0 0 4px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e7e5e4; }
        th { background: #fafaf9; font-size: 11px; text-transform: uppercase; color: #78716c; }
        .right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 15px; border-top: 2px solid #1c1917; border-bottom: none; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: bold; text-transform: capitalize; }
        .paid { background: #dcfce7; color: #15803d; }
        .due { background: #fef3c7; color: #b45309; }
        .failed { background: #fee2e2; color: #b91c1c; }
        .foot { margin-top: 48px; font-size: 11px; color: #a8a29e; border-top: 1px solid #e7e5e4; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <div class="brand">{{ $workspace->emoji }} {{ $workspace->name }}</div>
            <div class="muted">
                @if($workspace->address){{ $workspace->address }}<br>@endif
                @if($workspace->city){{ $workspace->city }}@endif
                @if($workspace->state), {{ $workspace->state }}@endif
                @if($workspace->postcode) {{ $workspace->postcode }}@endif
            </div>
        </div>
        <div class="right">
            <h1>INVOICE</h1>
            <div class="muted">{{ $invoice->number }}</div>
        </div>
    </div>

    <table>
        <tr>
            <td class="muted">Issued</td>
            <td class="right">{{ $invoice->issued_on?->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="muted">Status</td>
            <td class="right"><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ ucfirst($ownerPlan) }} plan subscription
                    <div class="muted">Monthly billing · {{ $invoice->issued_on?->format('F Y') }}</div>
                </td>
                <td class="right">{{ $currency }} {{ number_format((float) $invoice->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td class="right">{{ $currency }} {{ number_format((float) $invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="foot">
        Demo invoice — no real charge was made. Billing is not connected to a payment processor.
        Generated {{ now()->format('d M Y') }}.
    </div>
</body>
</html>
