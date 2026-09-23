<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement — {{ $customer->name }}</title>
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
        table.items .opening td, table.items .closing td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ $appName }}</h1>
            </td>
            <td class="right">
                <h1>STATEMENT OF ACCOUNT</h1>
                <p class="muted">{{ \Carbon\Carbon::parse($from ?? $statement['entries'][0]['date'] ?? now())->format('M d, Y') }} – {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <p class="label">Customer</p>
                <p><strong>{{ $customer->name }}</strong></p>
                @if ($customer->billing_address ?? $customer->address)
                    <p class="muted">{{ $customer->billing_address ?? $customer->address }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening">
                <td colspan="5">Opening Balance</td>
                <td class="num">{{ $statement['starting_balance'] }}</td>
            </tr>
            @foreach ($statement['entries'] as $entry)
                <tr>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ $entry['reference'] ?? "#{$entry['journal_id']}" }}</td>
                    <td>{{ $entry['description'] ?? '—' }}</td>
                    <td class="num">{{ $entry['debit'] }}</td>
                    <td class="num">{{ $entry['credit'] }}</td>
                    <td class="num">{{ $entry['running_balance'] }}</td>
                </tr>
            @endforeach
            <tr class="closing">
                <td colspan="5">Closing Balance</td>
                <td class="num">{{ $statement['ending_balance'] }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
