<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .order-details {
            margin-bottom: 20px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .product-table th {
            background-color: #f2f2f2;
        }

        .footer {
            text-align: left;
            margin-top: 20px;
        }

        .footer span {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img class="logo" src="{{ public_path('media/stanbi-logo-old.png') }}" alt="Company Logo">
            <h2>Purchase Order</h2>
        </div>

        <div class="order-details">
            <p><strong>Order Date:</strong> {{ $poDetail->po_order_date }}</p>
            <p><strong>PO Name:</strong> {{ $poDetail->po_number }}</p>
            @if (isset($companyDetail) && !empty($companyDetail->shipping_address))
                <p><strong>Shipping Address:</strong>{{$companyDetail->shipping_address}}</p>
            @endif
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th>Product SKU</th>
                    <th>Supplier SKU</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price Per Unit</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumOfTotalPrice = 0;
                @endphp
                @forelse ($poDetail->purchaseOrderItems as $orderItems)
                    @php
                        // $totalPrice = $orderItems->supplierProduct->unit_price * $orderItems->order_qty;
                        $sumOfTotalPrice = $sumOfTotalPrice + $orderItems->total_price
                    @endphp
                    <tr>
                        <td>{{ $orderItems->product->sku }}</td>
                        <td>{{ $orderItems->supplierProduct->supplier_sku }}</td>
                        <td>{{ $orderItems->product->title }}</td>
                        <td>{{ $orderItems->order_qty }}</td>
                        <td>${{ $orderItems->unit_price }}</td>
                        <td>${{ $orderItems->total_price }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>No Products</td>
                    </tr>
                @endforelse
                <!-- Add more product rows here if needed -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;"><strong>Total:</strong></td>
                    <td>${{ $sumOfTotalPrice }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p><strong>Contact Information:</strong></p>
            <span><b>STANBI</b></span>
            @if (isset($companyDetail) && !empty($companyDetail->company_address))
                <p>{{$companyDetail->company_address}}</p>
            @endif
            @if (isset($companyDetail) && !empty($companyDetail->company_email))
                <p>{{$companyDetail->company_email}}</p>
            @endif
            @if (isset($companyDetail) && !empty($companyDetail->company_phone))
                <p>{{$companyDetail->company_phone}}</p>
            @endif

            <p>Thank you for your business!</p>
            
                @if (isset($companyDetail) && !empty($companyDetail->company_phone) && !empty($companyDetail->company_email))
                    <p>This Purchase Order is subject to our terms and conditions. If you have any questions or concerns, please contact us at <b>{{$companyDetail->company_email}} or {{$companyDetail->company_phone}}</b>.</p>
                @endif
            </p>
        </div>
    </div>
</body>
</html>
