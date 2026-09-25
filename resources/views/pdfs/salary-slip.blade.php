<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip {{ $slip->year }}-{{ $slip->month }}</title>
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
                <h1>SALARY SLIP</h1>
                <p class="muted">{{ $slip->year }} - {{ str_pad($slip->month, 2, '0', STR_PAD_LEFT) }}</p>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <p class="label">Employee</p>
                <p><strong>{{ $employee->name }}</strong></p>
                <p class="muted">{{ $employee->employee_code }}</p>
            </td>
            <td style="width: 25%;">
                <p class="label">Present Days</p>
                <p>{{ $slip->present_days }}</p>
            </td>
            <td style="width: 25%;">
                <p class="label">Absent / Late Days</p>
                <p>{{ $slip->absent_days }} / {{ $slip->late_days }}</p>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Earning</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="num">{{ $slip->basic_salary }}</td>
            </tr>
            @foreach ($slip->allowances ?? [] as $label => $amount)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $label)) }}</td>
                    <td class="num">{{ $amount }}</td>
                </tr>
            @endforeach
            @if ((float) $slip->overtime_amount > 0)
                <tr>
                    <td>Overtime ({{ $slip->overtime_hours }} hrs)</td>
                    <td class="num">{{ $slip->overtime_amount }}</td>
                </tr>
            @endif
            @if ((float) $slip->special_pay_amount > 0)
                <tr>
                    <td>Special Pay</td>
                    <td class="num">{{ $slip->special_pay_amount }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Gross</td><td class="num">{{ $slip->gross_salary }}</td></tr>
        <tr><td>Deduction</td><td class="num">-{{ $slip->deduction_amount }}</td></tr>
        <tr class="grand"><td>Net Salary</td><td class="num">{{ $slip->net_salary }}</td></tr>
    </table>
</body>
</html>
