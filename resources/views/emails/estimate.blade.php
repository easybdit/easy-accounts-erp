<x-mail::message>
# Estimate {{ $estimate->estimate_number }}

Dear {{ $estimate->customer->name }},

Please find attached estimate **{{ $estimate->estimate_number }}**, dated {{ $estimate->estimate_date->toFormattedDateString() }}.

<x-mail::table>
| | |
|:---|---:|
| Estimate Total | {{ $estimate->total }} |
| Valid Until | {{ $estimate->expiry_date?->toFormattedDateString() ?? '—' }} |
</x-mail::table>

Please let us know if you'd like to proceed.

{{ config('app.name') }}
</x-mail::message>
