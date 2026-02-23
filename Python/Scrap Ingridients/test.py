import pandas as pd
import numpy as np

def melengkapi_data_material(input_file, output_file):
    # 1. Membaca file Excel
    try:
        df = pd.read_excel(input_file)
    except FileNotFoundError:
        try:
            # Mencoba membaca sebagai CSV jika Excel gagal
            df = pd.read_csv(input_file)
        except Exception as e:
            print(f"Gagal membaca file: {e}")
            return

    print("File berhasil dibaca. Sedang memproses...")

    # 2. Fungsi Logika untuk mengisi data berdasarkan nama
    def get_specs(row):
        # Jika data sudah ada (tidak kosong), biarkan saja (jangan ditimpa)
        if pd.notna(row['packaging_size']):
            return row

        name = str(row['name']).lower()
        
        # Default values
        specs = {
            'category_type': 'mass',
            'unit': 'gram',
            'purchase_unit': 'Sak @25kg',
            'packaging_size': 25,
            'packaging_unit': 'kg',
            'price_per_unit': 100000,
            'current_stock': 10 # Default stock
        }

        # Logika Penentuan Spesifikasi
        if 'tepung terigu' in name:
            specs.update({'purchase_unit': 'Sak @25kg', 'packaging_size': 25, 'packaging_unit': 'kg', 'price_per_unit': 215000})
        elif 'dekstrosa' in name:
            specs.update({'purchase_unit': 'Sak @25kg', 'packaging_size': 25, 'packaging_unit': 'kg', 'price_per_unit': 360000})
        elif 'garam' in name:
            specs.update({'purchase_unit': 'Sak @50kg', 'packaging_size': 50, 'packaging_unit': 'kg', 'price_per_unit': 200000})
        elif 'gula pasir' in name:
            specs.update({'purchase_unit': 'Sak @50kg', 'packaging_size': 50, 'packaging_unit': 'kg', 'price_per_unit': 800000})
        elif 'kakao' in name or 'cokelat bubuk' in name:
            specs.update({'purchase_unit': 'Sak @25kg', 'packaging_size': 25, 'packaging_unit': 'kg', 'price_per_unit': 2500000})
        elif 'minyak' in name:
            specs.update({'category_type': 'volume', 'unit': 'ml', 'purchase_unit': 'Jerigen @18L', 'packaging_size': 18, 'packaging_unit': 'L', 'price_per_unit': 300000})
        elif 'oat' in name:
            specs.update({'purchase_unit': 'Sak @25kg', 'packaging_size': 25, 'packaging_unit': 'kg', 'price_per_unit': 450000})
        elif 'pengembang' in name or 'baking powder' in name:
            specs.update({'purchase_unit': 'Karton @10kg', 'packaging_size': 10, 'packaging_unit': 'kg', 'price_per_unit': 300000})
        elif 'susu bubuk' in name:
            specs.update({'purchase_unit': 'Sak @25kg', 'packaging_size': 25, 'packaging_unit': 'kg', 'price_per_unit': 1500000})
        elif 'susu kental manis' in name:
            specs.update({'purchase_unit': 'Karton @10kg', 'packaging_size': 10, 'packaging_unit': 'kg', 'price_per_unit': 250000})
        elif 'butter' in name or 'mentega' in name:
            specs.update({'purchase_unit': 'Pail @18kg', 'packaging_size': 18, 'packaging_unit': 'kg', 'price_per_unit': 2000000})
        elif 'lemak' in name or 'shortening' in name or 'margarin' in name:
            specs.update({'purchase_unit': 'Karton @15kg', 'packaging_size': 15, 'packaging_unit': 'kg', 'price_per_unit': 350000})
        elif 'tapioka' in name or 'kanji' in name:
            specs.update({'purchase_unit': 'Sak @25kg', 'packaging_size': 25, 'packaging_unit': 'kg', 'price_per_unit': 240000})
        elif 'ragi' in name or 'yeast' in name:
            specs.update({'purchase_unit': 'Karton @10kg', 'packaging_size': 10, 'packaging_unit': 'kg', 'price_per_unit': 640000})
        elif 'telur bubuk' in name:
            specs.update({'purchase_unit': 'Karton @20kg', 'packaging_size': 20, 'packaging_unit': 'kg', 'price_per_unit': 2000000})
        elif 'perisa' in name or 'essence' in name or 'pasta' in name:
            specs.update({'category_type': 'volume', 'unit': 'ml', 'purchase_unit': 'Botol @1L', 'packaging_size': 1, 'packaging_unit': 'L', 'price_per_unit': 150000})
        elif 'pewarna' in name:
            specs.update({'category_type': 'volume', 'unit': 'ml', 'purchase_unit': 'Botol @1L', 'packaging_size': 1, 'packaging_unit': 'L', 'price_per_unit': 120000})
        elif 'bumbu' in name or 'penguat rasa' in name:
            specs.update({'purchase_unit': 'Pack @1kg', 'packaging_size': 1, 'packaging_unit': 'kg', 'price_per_unit': 50000})
        
        # Hitung Conversion Factor
        # Jika unit kemasan kg/L, faktor dikali 1000 (karena unit dasar gram/ml)
        multiplier = 1000 
        specs['conversion_factor'] = specs['packaging_size'] * multiplier

        # Update row dengan data baru
        for key, value in specs.items():
            row[key] = value
            
        return row

    # 3. Terapkan fungsi ke setiap baris
    df = df.apply(get_specs, axis=1)

    # 4. Generate Material Code (M-001, M-002, dst) jika kosong
    if 'material_code' not in df.columns:
        df['material_code'] = None
    
    # Isi kode hanya jika kosong
    for i in range(len(df)):
        if pd.isna(df.loc[i, 'material_code']):
            df.loc[i, 'material_code'] = f"M-{i+1:03d}"

    # 5. Simpan ke Excel baru
    df.to_excel(output_file, index=False)
    print(f"Selesai! File disimpan sebagai: {output_file}")

# --- Cara Menjalankan ---
# Pastikan nama file input sesuai dengan file Anda
input_filename = 'materials_all.xlsx' 
output_filename = 'materials_completed.xlsx'

melengkapi_data_material(input_filename, output_filename)