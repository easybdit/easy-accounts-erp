<x-mail::message>
# Payment Reminder

Dear {{ $invoice->customer->name }},

This is a reminder that invoice **{{ $invoice->invoice_number }}**, dated {{ $invoice->invoice_date->toFormattedDateString() }}, was due on {{ $invoice->due_date->toFormattedDateString() }} and is now **{{ $daysOverdue }} day(s) overdue**.

<x-mail::table>
| | |
|:---|---:|
| Invoice Total | {{ $invoice->total }} |
| Amount Due | {{ $amountDue }} |
| Due Date | {{ $invoice->due_date->toFormattedDateString() }} |
</x-mail::table>

@if ($paymentLinkUrl)
<x-mail::button :url="$paymentLinkUrl">
Pay Online
</x-mail::button>
@endif

Please arrange payment at your earliest convenience. If you've already paid, kindly disregard this reminder.

{{ config('app.name') }}
</x-mail::message>
