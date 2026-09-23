<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->receipt_number }}</title>
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
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ $appName }}</h1>
            </td>
            <td class="right">
                <h1>SALES RECEIPT</h1>
                <p class="muted">{{ $receipt->receipt_number }}</p>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <p class="label">Sold To</p>
                <p><strong>{{ $receipt->customer->name }}</strong></p>
                @if ($receipt->customer->billing_address ?? $receipt->customer->address)
                    <p class="muted">{{ $receipt->customer->billing_address ?? $receipt->customer->address }}</p>
                @endif
            </td>
            <td style="width: 25%;">
                <p class="label">Receipt Date</p>
                <p>{{ $receipt->receipt_date->format('M d, Y') }}</p>
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
            @foreach ($receipt->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $item->unit_price }}</td>
                    <td class="num">{{ $item->discount }}</td>
                    <td class="num">{{ bcadd($item->tax_amount, $item->tax_amount_2, 4) }}</td>
                    <td class="num">{{ $item->line_total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ $receipt->subtotal }}</td></tr>
        <tr><td>Discount</td><td class="num">{{ $receipt->discount_total }}</td></tr>
        <tr><td>Tax</td><td class="num">{{ $receipt->tax_total }}</td></tr>
        <tr class="grand"><td>Total Paid</td><td class="num">{{ $receipt->total }}</td></tr>
    </table>

    @if ($receipt->notes)
        <p class="label">Notes</p>
        <p>{{ $receipt->notes }}</p>
    @endif
</body>
</html>
