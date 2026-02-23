<!DOCTYPE html>
<html>
<head>
    <title>{{ $docTitle }} - {{ $salesOrder->so_code }}</title>
    <style>
        /* Reset warna ke Hitam / Abu Gelap Profesional */
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        
        /* Header Border Hitam */
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* Styling Logo SVG */
        .logo img { 
            height: 60px; 
            width: auto;
            margin-bottom: 5px;
        } 
        
        .company-info { float: left; }
        .doc-info { float: right; text-align: right; color: #333; }
        
        .title { font-size: 20px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; color: #000; }
        
        .client-info { margin-bottom: 30px; }
        .client-box { width: 45%; float: left; }
        .meta-box { width: 45%; float: right; text-align: right; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        
        /* Tabel Header Hitam/Putih Standar */
        th { background-color: #f2f2f2; padding: 10px; text-align: left; border-bottom: 1px solid #000; color: #000; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        
        .totals { width: 45%; float: right; }
        .totals-row { border-bottom: 1px solid #ddd; padding: 5px 0; }
        .totals-label { display: inline-block; width: 60%; font-weight: bold; }
        .totals-value { display: inline-block; width: 35%; text-align: right; }
        
        /* Grand Total Hitam Tebal */
        .grand-total { font-size: 14px; font-weight: bold; color: #000; border-top: 2px solid #000; padding-top: 10px; margin-top: 5px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 50px; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
        
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <div class="company-info">
            <div class="logo">
                {{-- Menggunakan SVG --}}
                <img src="{{ public_path('images/AIM_Biscuits_logo.svg') }}" alt="AIM BISCUITS">
            </div>
            <div>JL Karang Bong No.2, Pucang, Kec. Sidoarjo, Kabupaten Sidoarjo, Jawa Timur 61254</div>
            <div>Phone: (031) 8913318 </div>
        </div>
        <div class="doc-info">
            <div class="title">{{ $docTitle }}</div>
            <div>No: <strong>{{ $salesOrder->so_code }}</strong></div>
            <div>Date: {{ \Carbon\Carbon::parse($salesOrder->transaction_date)->format('d M Y') }}</div>
        </div>
    </div>

    <div class="client-info clearfix">
        <div class="client-box">
            <strong>Bill To:</strong><br>
            {{ $salesOrder->company_name }}<br>
            {{ $salesOrder->address }}<br>
            Atas nama: {{ $salesOrder->person_name }}<br>
            Phone: {{ $salesOrder->phone }}
        </div>
        <div class="meta-box">
            <strong>Status:</strong> {{ strtoupper($salesOrder->status) }}<br>
            <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($salesOrder->due_date)->format('d M Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="35%">Product</th>
                <th width="15%" style="text-align: center;">Kemasan</th> 
                <th width="8%" style="text-align: center;">Qty</th>
                <th width="15%" style="text-align: right;">Unit Price</th>
                <th width="10%" style="text-align: center;">Disc</th> {{-- KOLOM BARU --}}
                <th width="17%" style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesOrder->items as $item)
            <tr>
                <td>
                    <b>{{ $item->product_name_snapshot ?? $item->product->name }}</b><br>
                    <span style="font-size: 10px; color: #555;">{{ $item->product->code }}</span>
                </td>
                <td style="text-align: center; font-size: 11px;">
                    {{ $item->product_packaging_snapshot ?? $item->product->packaging ?? '-' }}
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                
                {{-- TAMPILAN DISKON --}}
                <td style="text-align: center;">
                    @if($item->discount_percent > 0)
                        <span style="color: red; font-size: 11px;">{{ $item->discount_percent + 0 }}%</span>
                    @else
                        -
                    @endif
                </td>

                <td style="text-align: right;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">Rp {{ number_format($salesOrder->items->sum('subtotal'), 0, ',', '.') }}</span>
            </div>
            
            <div class="totals-row">
                <span class="totals-label">
                    Shipping Cost
                </span>
                <span class="totals-value">
                    @if($salesOrder->shipping_payment_type == 'bill_to_customer')
                        Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}
                    @else
                        Rp 0 (Free)
                    @endif
                </span>
            </div>

            <div class="totals-row grand-total">
                <span class="totals-label">GRAND TOTAL</span>
                <span class="totals-value">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div style="margin-top: 50px;">
        <strong>Notes:</strong><br>
        <p style="font-size: 11px; color: #333; line-height: 1.6;">
            1. Pembayaran ditransfer ke BCA 1234567890 a/n PT Production.<br>
            2. Barang yang sudah dibeli tidak dapat dikembalikan.<br>
            3. Dokumen ini sah dan diproses oleh komputer.<br>
            4. Harga barang sudah termasuk pajak.<br>
            5. 
            @if($salesOrder->shipping_payment_type == 'borne_by_company')
                Biaya pengiriman sepenuhnya <strong>DITANGGUNG OLEH PERUSAHAAN (Free Shipping)</strong>.
            @else
                Biaya pengiriman <strong>DIBEBANKAN KEPADA CUSTOMER</strong> atau <strong>DIURUS SENDIRI OLEH DISTRIBUTOR</strong> (Ex Works/FOB).
            @endif
        </p>
    </div>

    <div class="footer">
        Generated by Production Planning System - {{ date('Y-m-d H:i:s') }}
    </div>

</body>
</html>