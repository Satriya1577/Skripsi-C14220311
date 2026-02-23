import pandas as pd
import requests
from bs4 import BeautifulSoup
import time
import re

# --- KONFIGURASI ---
INPUT_FILE = 'Data Ingridients AIM dari website.xlsx'
OUTPUT_FILE = 'Data_Ingridients_AIM_Fixed.xlsx'

def get_ingredients(url):
    # 1. Bersihkan URL dari spasi (Mengatasi error 404)
    if pd.isna(url) or str(url).strip() == "":
        return "Link Kosong"
    
    clean_url = str(url).strip()
    
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    }

    try:
        response = requests.get(clean_url, headers=headers, timeout=20)
        
        if response.status_code != 200:
            return f"Error: Status Code {response.status_code}"

        soup = BeautifulSoup(response.content, 'html.parser')

        # --- METODE 1: Cari Tab Ingredients (Cara Standar) ---
        # Mencari link yang mengandung kata "Ingredients" (tidak harus persis, case insensitive)
        tab_link = soup.find('a', string=re.compile(r'ingredients', re.IGNORECASE))
        
        if tab_link and tab_link.get('href'):
            target_id = tab_link.get('href').replace('#', '')
            content_div = soup.find('div', id=target_id)
            if content_div:
                return content_div.get_text(separator=" ", strip=True)

        # --- METODE 2: Cari Div dengan ID khusus (Cara Bypass) ---
        # Website AIM biasanya menamai ID tabnya dengan pola 'tab-ingredients-XXX'
        # Kita cari div manapun yang ID-nya mengandung kata 'tab-ingredients'
        content_div = soup.find('div', id=re.compile(r'tab-ingredients', re.IGNORECASE))
        if content_div:
            return content_div.get_text(separator=" ", strip=True)

        # --- METODE 3: Cari Kata Kunci Bahan Baku (Cara Manual) ---
        # Karena ini biskuit, hampir pasti mengandung "Tepung Terigu".
        # Kita cari paragraf <p> yang mengandung kata "Tepung"
        p_tag = soup.find('p', string=re.compile(r'Tepung|Sugar|Flour', re.IGNORECASE))
        if p_tag:
            return p_tag.get_text(strip=True)

        return "Ingredients tidak ditemukan (Cek Manual)"

    except Exception as e:
        return f"Error: {str(e)}"

def main():
    print("Membaca file Excel...")
    try:
        df = pd.read_excel(INPUT_FILE)
    except FileNotFoundError:
        print(f"File '{INPUT_FILE}' tidak ditemukan.")
        return

    print(f"Total data: {len(df)}")
    print("Mulai proses scraping perbaikan...")

    for index, row in df.iterrows():
        link = row['Link']
        nama = row['Nama Produk']
        
        print(f"[{index+1}/{len(df)}] {nama}...", end=" ", flush=True)
        
        result = get_ingredients(link)
        
        # Tampilkan status singkat di terminal
        if "Tepung" in str(result) or "Flour" in str(result) or len(str(result)) > 20:
            print("OK ✅")
        elif "Error" in str(result):
            print("GAGAL ❌ (Link Error)")
        else:
            print("KOSONG ⚠️")

        df.at[index, 'Ingridients'] = result
        
        # Jeda sedikit biar aman
        time.sleep(1)

    print("\nMenyimpan hasil...")
    df.to_excel(OUTPUT_FILE, index=False)
    print(f"Selesai! File tersimpan: {OUTPUT_FILE}")

if __name__ == "__main__":
    main()