<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $estimate->estimate_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .header { width: 100%; margin-bottom: 24px; }
        .header td { vertical-align: top; }
        .header .right { text-align: right; }
        .meta { width: 100%; margin-bottom: 24px; }
        .meta td { vertical-align: top; padding-bottom: 4px; }
        .meta .label { color: #6b7280; text-transform: uppercase; font-size: 9px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th { text-align: left; border-bottom: 1px solid #d1d5db; padding: 6px 4px; font-size: 9px; text-transform: uppercase; color: #6b7280; }
        table.items td { padding: 6px 4px; border-bottom: 1px solid #f3f4f6; }
        table.items .num { text-align: right; }
        table.totals { width: 240px; margin-left: auto; }
        table.totals td { padding: 3px 4px; }
        table.totals .num { text-align: right; }
        table.totals .grand td { border-top: 1px solid #d1d5db; font-weight: bold; padding-top: 6px; }
        .status { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 10px; text-transform: uppercase; }
        .status-draft { background: #fef9c3; color: #854d0e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #dcfce7; color: #15803d; }
        .status-declined { background: #fee2e2; color: #b91c1c; }
        .status-converted { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ $appName }}</h1>
            </td>
            <td class="right">
                <h1>ESTIMATE</h1>
                <p class="muted">{{ $estimate->estimate_number }}</p>
                <span class="status status-{{ $estimate->status }}">{{ $estimate->status }}</span>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <p class="label">Prepared For</p>
                <p><strong>{{ $estimate->customer->name }}</strong></p>
                @if ($estimate->customer->billing_address ?? $estimate->customer->address)
                    <p class="muted">{{ $estimate->customer->billing_address ?? $estimate->customer->address }}</p>
                @endif
            </td>
            <td style="width: 25%;">
                <p class="label">Estimate Date</p>
                <p>{{ $estimate->estimate_date->format('M d, Y') }}</p>
            </td>
            <td style="width: 25%;">
                <p class="label">Valid Until</p>
                <p>{{ $estimate->expiry_date?->format('M d, Y') ?? '—' }}</p>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Discount</th>
                <th class="num">Tax</th>
                <th class="num">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estimate->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $item->unit_price }}</td>
                    <td class="num">{{ $item->discount }}</td>
                    <td class="num">{{ $item->tax_amount }}</td>
                    <td class="num">{{ $item->line_total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ $estimate->subtotal }}</td></tr>
        <tr><td>Discount</td><td class="num">{{ $estimate->discount_total }}</td></tr>
        <tr><td>Tax</td><td class="num">{{ $estimate->tax_total }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">{{ $estimate->total }}</td></tr>
    </table>

    @if ($estimate->notes)
        <p class="label">Notes</p>
        <p>{{ $estimate->notes }}</p>
    @endif
</body>
</html>
