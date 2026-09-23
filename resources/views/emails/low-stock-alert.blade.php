<x-mail::message>
# Low Stock Alert

The following {{ $products->count() }} item(s) are at or below their reorder level:

<x-mail::table>
| SKU | Product | Current Stock | Reorder Level |
|:---|:---|---:|---:|
@foreach ($products as $product)
| {{ $product->sku }} | {{ $product->name }} | {{ $product->currentStock() }} | {{ $product->low_stock_threshold }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('reports.inventory')">
View Inventory Report
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
