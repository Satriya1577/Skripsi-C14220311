<!DOCTYPE html>
<html>
<head>
    <title>{{ $docTitle }} - {{ $purchaseOrder->po_number }}</title>
    <style>
        /* Menggunakan Style yang sama persis dengan Sales Order */
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        
        /* Header Border Hitam */
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* Styling Logo */
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
                <img src="{{ public_path('images/AIM_Biscuits_logo.svg') }}" alt="AIM BISCUITS">
            </div>
            <div>JL Karang Bong No.2, Pucang, Kec. Sidoarjo, Kabupaten Sidoarjo, Jawa Timur 61254</div>
            <div>Phone: (031) 8913318</div>
        </div>
        <div class="doc-info">
            <div class="title">{{ $docTitle }}</div>
            <div>No: <strong>{{ $purchaseOrder->po_number }}</strong></div>
            <div>Date: {{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d M Y') }}</div>
        </div>
    </div>

    <div class="client-info clearfix">
        <div class="client-box">
            <strong>Vendor / Supplier:</strong><br>
            <span style="font-size: 14px; font-weight: bold;">{{ $purchaseOrder->company_name }}</span><br>
            {{ $purchaseOrder->address }}<br>
            Attn: {{ $purchaseOrder->person_name }}<br>
            Phone: {{ $purchaseOrder->phone }}
        </div>
        <div class="meta-box">
            <strong>Status:</strong> {{ strtoupper($purchaseOrder->status) }}<br>
            @if($purchaseOrder->expected_arrival_date)
                <strong>Exp. Arrival:</strong> {{ \Carbon\Carbon::parse($purchaseOrder->expected_arrival_date)->format('d M Y') }}<br>
            @endif
            <strong>Payment Due:</strong> {{ $purchaseOrder->due_date ? \Carbon\Carbon::parse($purchaseOrder->due_date)->format('d M Y') : '-' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                {{-- Penyesuaian Lebar Kolom (Tanpa Diskon) --}}
                <th width="40%">Material / Description</th>
                <th width="15%" style="text-align: center;">Unit</th> 
                <th width="10%" style="text-align: center;">Qty</th>
                <th width="15%" style="text-align: right;">Unit Cost</th>
                <th width="20%" style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $item)
            <tr>
                <td>
                    <b>{{ $item->material_name_snapshot ?? $item->material->name }}</b><br>
                    <span style="font-size: 10px; color: #555;">{{ $item->material->code ?? '-' }}</span>
                </td>
                
                <td style="text-align: center; font-size: 11px;">
                    {{ $item->unit_snapshot ?? '-' }}
                </td>
                
                <td style="text-align: center;">{{ $item->quantity + 0 }}</td>
                
                <td style="text-align: right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                
                <td style="text-align: right;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">Rp {{ number_format($purchaseOrder->items->sum('subtotal'), 0, ',', '.') }}</span>
            </div>
            
            <div class="totals-row">
                <span class="totals-label">
                    Shipping Cost
                    <br><span style="font-size: 9px; font-weight: normal; color: #555;">
                        ({{ $purchaseOrder->shipping_terms == 'FOB_shipping_point' ? 'FOB Shipping Point' : 'FOB Destination' }})
                    </span>
                </span>
                <span class="totals-value">
                    @if($purchaseOrder->shipping_terms == 'FOB_shipping_point')
                        Rp {{ number_format($purchaseOrder->shipping_cost, 0, ',', '.') }}
                    @else
                        Rp 0 (Vendor)
                    @endif
                </span>
            </div>

            <div class="totals-row grand-total">
                <span class="totals-label">TOTAL ORDER</span>
                <span class="totals-value">Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div style="margin-top: 50px;">
        <strong>Terms & Conditions:</strong><br>
        <p style="font-size: 11px; color: #333; line-height: 1.6;">
            1. Harap lampirkan PO ini pada Surat Jalan dan Invoice/Faktur.<br>
            2. Kualitas barang harus sesuai dengan spesifikasi yang telah disepakati.<br>
            3. Kami berhak menolak barang yang cacat atau tidak sesuai.<br>
            4. 
            @if($purchaseOrder->shipping_terms == 'FOB_destination')
                Pengiriman menggunakan <strong>FOB Destination</strong> (Biaya & Resiko pengiriman ditanggung Supplier sampai barang diterima di gudang kami).
            @else
                Pengiriman menggunakan <strong>FOB Shipping Point</strong> (Biaya pengiriman ditanggung Pembeli).
            @endif
        </p>
    </div>

    <div class="footer">
        Generated by Production Planning System - {{ date('Y-m-d H:i:s') }}
    </div>

</body>
</html>