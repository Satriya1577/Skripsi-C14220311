import pandas as pd
import re

# 1. Load Data
# Menggunakan file input yang terakhir Anda berikan
filename = 'Book1.xlsx'
try:
    df = pd.read_csv(filename)
except:
    # Fallback jika file asli adalah excel
    try:
        df = pd.read_excel('Book1.xlsx')
    except:
        df = pd.read_excel('Updated_Estimasi_Bahan_Baku.csv') # Fallback terakhir

# Fungsi Parsing Berat Total dari Kemasan
def parse_total_weight(kemasan_str):
    if pd.isna(kemasan_str): return 0
    kemasan_str = str(kemasan_str).lower()
    
    # Pola: 24Pak x 300Gr -> 24 * 300
    match_qx = re.search(r'(\d+)\s*.*\s*x\s*(\d+)\s*gr', kemasan_str)
    if match_qx:
        return int(match_qx.group(1)) * int(match_qx.group(2))
    
    # Pola: 4kg / bal -> 4 * 1000
    match_kg = re.search(r'(\d+)\s*kg', kemasan_str)
    if match_kg:
        return int(match_kg.group(1)) * 1000
    
    # Fallback: 300gr
    match_simple = re.search(r'(\d+)\s*gr', kemasan_str)
    if match_simple:
         return int(match_simple.group(1))

    return 0

# Proses Perhitungan Ulang
# Kita pastikan kolom grouping lengkap
group_cols = ['Kode', 'Nama Produk', 'Link', 'Kemasan']
df[group_cols] = df[group_cols].fillna('')

new_amounts = []
grouped = df.groupby(group_cols)

for name, group in grouped:
    kemasan = name[3]
    total_weight = parse_total_weight(kemasan)
    
    ingredients = group['Material (Standar)'].tolist()
    num_ingredients = len(ingredients)
    
    # Cek keberadaan tepung terigu
    has_flour = any('tepung terigu' in str(ing).lower() for ing in ingredients)
    
    # Hitung jumlah tepung (jika ada lebih dari 1 entri tepung, bagi 70% ke mereka)
    flour_count = sum('tepung terigu' in str(i).lower() for i in ingredients)
    
    # Sisa bahan bukan tepung
    non_flour_count = num_ingredients - flour_count
    
    for idx, row in group.iterrows():
        ing_name = str(row['Material (Standar)']).lower()
        estimated_amount = 0
        
        if total_weight > 0:
            if has_flour:
                if 'tepung terigu' in ing_name:
                    # 70% Berat Total untuk Tepung
                    # Jika ada 2 baris tepung, dibagi rata
                    if flour_count > 0:
                        estimated_amount = (total_weight * 0.70) / flour_count
                else:
                    # 30% Sisanya dibagi rata ke bahan lain
                    if non_flour_count > 0:
                        estimated_amount = (total_weight * 0.30) / non_flour_count
            else:
                # Jika tidak ada tepung, bagi rata semua
                estimated_amount = total_weight / num_ingredients
        
        new_amounts.append({
            'index': idx,
            'Estimasi Jumlah Baru': round(estimated_amount, 2)
        })

# Update DataFrame
amount_df = pd.DataFrame(new_amounts).set_index('index')
df['Estimasi Jumlah'] = amount_df['Estimasi Jumlah Baru']

# Simpan ke EXCEL (.xlsx) agar format angka aman
output_filename = 'Final_Estimasi_Bahan_Baku_AIM.xlsx'
df.to_excel(output_filename, index=False)

print(f"Selesai! File Excel tersimpan: {output_filename}")
print(df[['Nama Produk', 'Kemasan', 'Material (Standar)', 'Estimasi Jumlah', 'Satuan']].head(10))