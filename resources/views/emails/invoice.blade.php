<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->customer->name }},

Please find attached invoice **{{ $invoice->invoice_number }}**, dated {{ $invoice->invoice_date->toFormattedDateString() }}.

<x-mail::table>
| | |
|:---|---:|
| Invoice Total | {{ $invoice->total }} |
| Amount Due | {{ $amountDue }} |
| Due Date | {{ $invoice->due_date?->toFormattedDateString() ?? '—' }} |
</x-mail::table>

@if ($paymentLinkUrl)
<x-mail::button :url="$paymentLinkUrl">
Pay Online
</x-mail::button>
@endif

Thank you for your business.

{{ config('app.name') }}
</x-mail::message>
