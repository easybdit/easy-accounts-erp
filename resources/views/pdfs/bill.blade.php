<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $bill->bill_number }}</title>
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
        .status-posted { background: #dcfce7; color: #15803d; }
        .status-draft { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ $appName }}</h1>
            </td>
            <td class="right">
                <h1>BILL</h1>
                <p class="muted">{{ $bill->bill_number }}</p>
                <span class="status status-{{ $bill->status }}">{{ $bill->status }}</span>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <p class="label">Vendor</p>
                <p><strong>{{ $bill->vendor->name }}</strong></p>
                @if ($bill->vendor->billing_address ?? $bill->vendor->address)
                    <p class="muted">{{ $bill->vendor->billing_address ?? $bill->vendor->address }}</p>
                @endif
            </td>
            <td style="width: 25%;">
                <p class="label">Bill Date</p>
                <p>{{ $bill->bill_date->format('M d, Y') }}</p>
            </td>
            <td style="width: 25%;">
                <p class="label">Due Date</p>
                <p>{{ $bill->due_date?->format('M d, Y') ?? '—' }}</p>
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
            @foreach ($bill->items as $item)
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
        <tr><td>Subtotal</td><td class="num">{{ $bill->subtotal }}</td></tr>
        <tr><td>Discount</td><td class="num">{{ $bill->discount_total }}</td></tr>
        <tr><td>Tax</td><td class="num">{{ $bill->tax_total }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">{{ $bill->total }}</td></tr>
        @if ($bill->status === 'posted')
            <tr><td>Paid</td><td class="num">{{ $amountPaid }}</td></tr>
            <tr><td><strong>Amount Due</strong></td><td class="num"><strong>{{ $amountDue }}</strong></td></tr>
        @endif
    </table>

    @if ($bill->notes)
        <p class="label">Notes</p>
        <p>{{ $bill->notes }}</p>
    @endif
</body>
</html>
