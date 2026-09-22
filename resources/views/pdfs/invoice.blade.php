<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
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
                <h1>INVOICE</h1>
                <p class="muted">{{ $invoice->invoice_number }}</p>
                <span class="status status-{{ $invoice->status }}">{{ $invoice->status }}</span>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <p class="label">Bill To</p>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                @if ($invoice->customer->billing_address ?? $invoice->customer->address)
                    <p class="muted">{{ $invoice->customer->billing_address ?? $invoice->customer->address }}</p>
                @endif
            </td>
            <td style="width: 25%;">
                <p class="label">Invoice Date</p>
                <p>{{ $invoice->invoice_date->format('M d, Y') }}</p>
            </td>
            <td style="width: 25%;">
                <p class="label">Due Date</p>
                <p>{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</p>
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
            @foreach ($invoice->items as $item)
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
        <tr><td>Subtotal</td><td class="num">{{ $invoice->subtotal }}</td></tr>
        <tr><td>Discount</td><td class="num">{{ $invoice->discount_total }}</td></tr>
        <tr><td>Tax</td><td class="num">{{ $invoice->tax_total }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">{{ $invoice->total }}</td></tr>
        @if ($invoice->status === 'posted')
            <tr><td>Paid</td><td class="num">{{ $amountPaid }}</td></tr>
            <tr><td><strong>Amount Due</strong></td><td class="num"><strong>{{ $amountDue }}</strong></td></tr>
        @endif
    </table>

    @if ($invoice->notes)
        <p class="label">Notes</p>
        <p>{{ $invoice->notes }}</p>
    @endif
</body>
</html>
