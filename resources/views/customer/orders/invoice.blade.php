<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number ?? $order->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1f2937; background: #fff; font-size: 14px; line-height: 1.5; padding: 40px; }
        .invoice { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 2px solid #1f2937; margin-bottom: 24px; }
        .header h1 { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; color: #111827; }
        .header .meta { text-align: right; }
        .header .meta p { color: #6b7280; font-size: 13px; }
        .header .meta strong { color: #111827; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
        .info-box h3 { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 6px; }
        .info-box p { color: #374151; font-size: 13px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #f9fafb; text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        thead th:last-child, tbody td:last-child { text-align: right; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 13px; }
        tbody td:first-child { font-weight: 600; color: #111827; }
        .totals { margin-left: auto; width: 280px; }
        .totals .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #6b7280; }
        .totals .row:last-child { border-top: 2px solid #111827; margin-top: 6px; padding-top: 10px; font-size: 16px; font-weight: 700; color: #111827; }
        .footer { text-align: center; padding-top: 32px; border-top: 1px solid #e5e7eb; margin-top: 32px; color: #9ca3af; font-size: 12px; }
        .no-print { text-align: center; margin-bottom: 24px; }
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            .header { border-bottom-color: #000; }
            .totals .row:last-child { border-top-color: #000; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 24px; background: #1f2937; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">Print Invoice</button>
    </div>

    <div class="invoice">
        <div class="header">
            <div>
                <h1>INVOICE</h1>
            </div>
            <div class="meta">
                <p><strong>Order #{{ $order->order_number ?? $order->id }}</strong></p>
                <p>Date: {{ $order->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Bill To</h3>
                @if($order->billingAddress)
                    <p>
                        {{ $order->billingAddress->first_name ?? $order->billingAddress->name }}<br>
                        {{ $order->billingAddress->street }}<br>
                        @if($order->billingAddress->apt){{ $order->billingAddress->apt }}<br>@endif
                        {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->zip }}
                    </p>
                @elseif($order->shippingAddress)
                    <p>
                        {{ $order->shippingAddress->first_name ?? $order->shippingAddress->name }}<br>
                        {{ $order->shippingAddress->street }}<br>
                        @if($order->shippingAddress->apt){{ $order->shippingAddress->apt }}<br>@endif
                        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->zip }}
                    </p>
                @endif
            </div>
            <div class="info-box">
                <h3>Ship To</h3>
                @if($order->shippingAddress)
                    <p>
                        {{ $order->shippingAddress->first_name ?? $order->shippingAddress->name }}<br>
                        {{ $order->shippingAddress->street }}<br>
                        @if($order->shippingAddress->apt){{ $order->shippingAddress->apt }}<br>@endif
                        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->zip }}
                    </p>
                @else
                    <p>&mdash;</p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th style="text-align:center">Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name ?? $item->product?->name }}</td>
                        <td>{{ $item->sku ?? '—' }}</td>
                        <td style="text-align:center">{{ $item->quantity }}</td>
                        <td>${{ number_format($item->price, 2) }}</td>
                        <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="row">
                <span>Shipping</span>
                <span>{{ ($order->shipping_cost ?? 0) > 0 ? '$' . number_format($order->shipping_cost, 2) : 'Free' }}</span>
            </div>
            @if($order->discount ?? false)
                <div class="row">
                    <span>Discount</span>
                    <span>-${{ number_format($order->discount, 2) }}</span>
                </div>
            @endif
            <div class="row">
                <span>Total</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            Thank you for your purchase!
        </div>
    </div>
</body>
</html>
