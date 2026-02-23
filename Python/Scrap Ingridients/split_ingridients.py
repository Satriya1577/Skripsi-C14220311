import pandas as pd
import re

# ==========================================
# 1. SETUP & LOAD DATA
# ==========================================
# Prioritas membaca file mentah yang ada kolom 'Ingridients' asli dan 'Kemasan'
input_files = [
    'Book1.xlsx'
]

df = None
for f in input_files:
    try:
        if f.endswith('.csv'):
            df = pd.read_csv(f)
        else:
            df = pd.read_excel(f)
        # Cek apakah kolom wajib ada
        if 'Ingridients' in df.columns and 'Kemasan' in df.columns:
            print(f"Berhasil membaca file: {f}")
            break
    except:
        continue

if df is None:
    raise ValueError("Tidak dapat menemukan file input yang sesuai (harus ada kolom 'Ingridients' dan 'Kemasan')")

# ==========================================
# 2. DEFINISI FUNGSI HELPER
# ==========================================

def parse_total_weight(kemasan_str):
    """Menghitung total berat dalam gram dari string kemasan."""
    if pd.isna(kemasan_str): return 0
    kemasan_str = str(kemasan_str).lower()
    
    # Pola 1: Quantity x Weight (misal: 24pakx300gr)
    match_qx = re.search(r'(\d+)\s*.*\s*x\s*(\d+)\s*gr', kemasan_str)
    if match_qx:
        return int(match_qx.group(1)) * int(match_qx.group(2))
    
    # Pola 2: Weight in Kg (misal: 4kg / bal)
    match_kg = re.search(r'(\d+)\s*kg', kemasan_str)
    if match_kg:
        return int(match_kg.group(1)) * 1000
    return 0

def split_ingredients_smart(text):
    """Memisahkan bahan berdasarkan koma, mengabaikan koma dalam kurung."""
    if pd.isna(text) or not isinstance(text, str): return []
    text = text.strip()
    if "tidak ditemukan" in text.lower() or "error" in text.lower(): return []
    text = text.rstrip('.')
    
    ingredients = []
    current_ing = ""
    paren_depth = 0
    for char in text:
        if char == '(': paren_depth += 1; current_ing += char
        elif char == ')': paren_depth -= 1; current_ing += char
        elif char == ',' and paren_depth == 0:
            ingredients.append(current_ing.strip())
            current_ing = ""
        else: current_ing += char
    if current_ing: ingredients.append(current_ing.strip())
    
    return [ing.lower() for ing in ingredients if ing.strip()]

def get_unit_type(material_name):
    """Menentukan satuan gr atau ml."""
    material_name = material_name.lower()
    if 'bubuk' in material_name or 'powder' in material_name: return 'gr'
    liquid_keywords = ['minyak', 'air', 'sirup', 'kecap', 'sari', 'pasta', 'lemak reroti']
    # Lemak reroti (shortening) semi-padat tapi sering dihitung volume/berat setara air
    if any(keyword in material_name for keyword in liquid_keywords): return 'ml'
    return 'gr'

def expand_variants(text):
    """Memecah varian seperti 'Perisa (A, B)' menjadi list."""
    # 1. Hapus (mengandung ...)
    text_clean = re.sub(r'\(\s*(?:mengandung|contain|terbuat dari).*?\)', '', text, flags=re.IGNORECASE).strip()
    
    # 2. Cek Pola: "Prefix (Item1, Item2)"
    match = re.search(r'^(.+?)\s*\((.+)\)$', text_clean)
    if match:
        prefix = match.group(1).strip()
        content = match.group(2).strip()
        content = re.sub(r'\s+dan\s+', ',', content, flags=re.IGNORECASE)
        variants = [v.strip() for v in content.split(',') if v.strip()]
        
        results = []
        for v in variants:
            v_clean = re.sub(r'\s*\d+(?:[.,]\d+)?\s*%', '', v).strip() # Hapus %
            if v_clean: results.append(f"{prefix} {v_clean}")
        if results: return results

    # Fallback
    final_text = re.sub(r'\s*\d+(?:[.,]\d+)?\s*%', '', text_clean).strip()
    return [final_text]

def standardize_ingredient(text):
    """Menyeragamkan nama material."""
    if pd.isna(text): return text
    text = text.lower().strip()
    text = re.sub(r'\s+', ' ', text)
    text = re.sub(r'\(\)', '', text)
    
    # Mapping Rules
    if 'tepung terigu' in text or 'terigu' in text: return 'tepung terigu'
    if 'tapioka' in text: return 'tepung tapioka'
    if 'oat' in text: return 'oat'
    if 'gula' in text and not any(x in text for x in ['glukosa', 'fruktosa', 'merah', 'jagung', 'stevia']): return 'gula pasir'
    if 'dekstrosa' in text: return 'dekstrosa'
    if 'susu' in text:
        if 'kental manis' in text: return 'susu kental manis'
        return 'susu bubuk'
    if any(x in text for x in ['kakao', 'cokelat bubuk', 'coklat bubuk']): return 'kakao bubuk'
    if 'minyak' in text:
        if 'mentega' in text or 'butter' in text: return 'butter oil'
        return 'minyak nabati'
    if 'lemak reroti' in text or 'shortening' in text: return 'lemak reroti'
    if 'margarin' in text: return 'margarin'
    if any(x in text for x in ['pengembang', 'bikarbonat', 'baking powder']): return 'pengembang'
    
    if 'perisa' in text:
        clean = re.sub(r'(sintetik|artifisial|identik alami)', '', text).replace('perisa', '').strip()
        clean = re.sub(r'[&,]', '', clean)
        mapping = {
            'cok': 'cokelat', 'vanila': 'vanila', 'vanili': 'vanila', 'susu': 'vanila', 
            'stroberi': 'stroberi', 'pandan': 'pandan', 'durian': 'durian', 
            'kelapa': 'kelapa', 'coconut': 'kelapa', 'ayam': 'ayam', 
            'butter': 'butter', 'mentega': 'butter', 'lemon': 'lemon', 
            'nanas': 'nanas', 'jagung': 'jagung'
        }
        for key, val in mapping.items():
            if key in clean: return f'perisa {val}'
        return f'perisa {clean}'

    if 'pewarna' in text or 'ci.' in text:
        if any(x in text for x in ['kuning', 'tartrazin', '15985', '19140']): return 'pewarna kuning'
        if any(x in text for x in ['merah', 'ponceau', '16255']): return 'pewarna merah'
        if any(x in text for x in ['biru', 'berlian', '42090']): return 'pewarna biru'
        if any(x in text for x in ['cokelat', 'coklat', 'karamel']): return 'pewarna cokelat'
        return 'pewarna'
        
    if 'garam' in text: return 'garam'
    if 'ragi' in text: return 'ragi'
    if 'telur' in text: return 'telur bubuk'
    if 'malt' in text: return 'ekstrak malt'
    if 'keju' in text: return 'keju bubuk'
    if 'kelapa kering' in text: return 'kelapa kering'
    if 'pengemulsi' in text or 'lesitin' in text: return 'pengemulsi'
    if 'asam sitrat' in text: return 'pengatur keasaman'
    if 'mononatrium' in text or 'glutamat' in text or 'penguat rasa' in text: return 'penguat rasa'
    
    return text

# ==========================================
# 3. PROSES UTAMA (Main Loop)
# ==========================================
processed_rows = []

print("Memulai pemrosesan data...")

for index, row in df.iterrows():
    # 1. Parse Berat Total
    total_weight = parse_total_weight(row['Kemasan'])
    
    # 2. Split Ingredient Dasar
    raw_ingredients = split_ingredients_smart(row['Ingridients'])
    
    if not raw_ingredients:
        continue
        
    # 3. Hitung Berat Dasar (Logic 70% Tepung)
    num_base = len(raw_ingredients)
    has_flour = any('tepung terigu' in ing for ing in raw_ingredients)
    
    # List berat sementara untuk setiap bahan di raw_ingredients
    base_weights = []
    
    for ing in raw_ingredients:
        w = 0
        if total_weight > 0:
            if has_flour:
                if 'tepung terigu' in ing:
                    w = total_weight * 0.70 # 70% untuk tepung terigu
                else:
                    # Sisa 30% dibagi ke bahan lain
                    divisor = num_base - 1 if num_base > 1 else 1
                    w = (total_weight * 0.30) / divisor
            else:
                # Jika tidak ada tepung terigu, bagi rata
                w = total_weight / num_base
        base_weights.append(w)
        
    # 4. Ekspansi & Standardisasi
    for raw_ing, weight in zip(raw_ingredients, base_weights):
        
        # Expand Variants (misal: Perisa A, B)
        expanded_list = expand_variants(raw_ing)
        
        # Bagi berat ke item hasil ekspansi
        num_expanded = len(expanded_list)
        final_weight = weight / num_expanded if num_expanded > 0 else 0
        
        for item in expanded_list:
            # Standardisasi Nama
            std_name = standardize_ingredient(item)
            
            # Tentukan Satuan
            unit = get_unit_type(std_name)
            
            processed_rows.append({
                'Kode': row['Kode'],
                'Nama Produk': row['Nama Produk'],
                'Link': row['Link'],
                'Kemasan': row['Kemasan'],
                'Material (Standar)': std_name,
                'Estimasi Jumlah': final_weight,
                'Satuan': unit
            })

# ==========================================
# 4. AGREGASI & OUTPUT
# ==========================================
df_result = pd.DataFrame(processed_rows)

# Jumlahkan berat jika ada material yang sama dalam satu produk (setelah distandarisasi)
final_cols = ['Kode', 'Nama Produk', 'Link', 'Kemasan', 'Material (Standar)', 'Satuan']
df_agg = df_result.groupby(final_cols)['Estimasi Jumlah'].sum().reset_index()

# Sort & Rounding
df_agg = df_agg.sort_values(by=['Nama Produk', 'Estimasi Jumlah'], ascending=[True, False])
df_agg['Estimasi Jumlah'] = df_agg['Estimasi Jumlah'].round(2)

# Simpan
output_file = 'Hasil_Estimasi_Bahan_Baku_Final.csv'
df_agg.to_csv(output_file, index=False)

print(f"\nSelesai! File berhasil disimpan: {output_file}")
print("\nPreview 10 Data Teratas:")
print(df_agg[['Nama Produk', 'Material (Standar)', 'Estimasi Jumlah', 'Satuan']].head(10).to_markdown(index=False))