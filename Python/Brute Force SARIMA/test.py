import pandas as pd
import numpy as np
from datetime import datetime
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_squared_error
from scipy.signal import savgol_filter
from scipy.stats import boxcox, yeojohnson
from scipy.special import inv_boxcox
import itertools
import warnings
import csv
import sys
import os

# Suppress warnings agar output console tidak penuh
warnings.filterwarnings("ignore")

def calculate_metrics(y_true, y_pred):
    """Menghitung RMSE dan MAPE."""
    y_true = np.array(y_true)
    y_pred = np.array(y_pred)
    mse = mean_squared_error(y_true, y_pred)
    rmse = np.sqrt(mse)
    
    # Menghindari pembagian dengan nol untuk MAPE
    mask = y_true != 0
    if np.sum(mask) == 0:
        mape = np.nan
    else:
        mape = np.mean(np.abs((y_true[mask] - y_pred[mask]) / y_true[mask])) * 100
        
    return rmse, mape

def optimize_sarima(train, test, p_range, d_range, q_range, P_range, D_range, Q_range, s_list):
    """
    Grid Search untuk mencari parameter SARIMA terbaik berdasarkan RMSE.
    """
    best_rmse = float('inf')
    best_params = None
    
    # Generate kombinasi parameter (p, d, q)
    pdq = list(itertools.product(p_range, d_range, q_range))
    
    # Generate kombinasi seasonal (P, D, Q, s)
    seasonal_pdq = list(itertools.product(P_range, D_range, Q_range, s_list))
    
    # Hitung total iterasi
    total_combinations = len(pdq) * len(seasonal_pdq)
    print(f"  -> Mencari di antara {total_combinations} kombinasi parameter...")
    
    counter = 0
    
    for param in pdq:
        for param_seasonal in seasonal_pdq:
            counter += 1
            # Optional: Print progress setiap 500 iterasi
            if counter % 500 == 0:
                print(f"     Progress: {counter}/{total_combinations} checked...", end='\r')

            try:
                # seasonal_order format: (P, D, Q, s)
                mod = SARIMAX(train,
                              order=param,
                              seasonal_order=param_seasonal,
                              enforce_stationarity=False,
                              enforce_invertibility=False)
                
                # Gunakan lbfgs (default) biasanya paling cepat. maxiter dibatasi.
                results = mod.fit(disp=False, maxiter=50)
                
                # Forecast
                pred = results.get_forecast(steps=len(test))
                y_pred = pred.predicted_mean
                
                rmse, _ = calculate_metrics(test, y_pred)
                
                if rmse < best_rmse:
                    best_rmse = rmse
                    best_params = (param, param_seasonal)
                    
            except:
                continue
    
    print(f"     Selesai. Best RMSE: {best_rmse:.4f}")            
    return best_params

# ==========================================
# MAIN EXECUTION
# ==========================================

# 1. Load Data Penjualan
file_path_sales = "data_penjualan_aim_harian_all_products.xlsx"
# Jika menggunakan file CSV hasil upload sebelumnya, ganti nama file di atas.
# Contoh: file_path_sales = "data_penjualan_aim_harian_all_products.xlsx - Data Penjualan Merge.csv"

print(f"Membaca file penjualan: {file_path_sales}")
# Deteksi apakah excel atau csv
if file_path_sales.endswith('.csv'):
    df = pd.read_csv(file_path_sales)
else:
    df = pd.read_excel(file_path_sales)

df['Tanggal'] = pd.to_datetime(df['Tanggal'])

# 2. Load Data List Produk Forecasting (FILTER)
file_path_list = "list_produk_forecasting.xlsx"
# Contoh jika CSV: file_path_list = "list_produk_forecasting.xlsx - Sheet1.csv"

print(f"Membaca file filter produk: {file_path_list}")
try:
    if file_path_list.endswith('.csv'):
        df_list = pd.read_csv(file_path_list)
    else:
        df_list = pd.read_excel(file_path_list)
    
    # Pastikan kolom boolean dibaca dengan benar
    # Mengisi NaN dengan False agar aman
    df_list['Forecast'] = df_list['Forecast'].fillna(False).astype(bool)
    df_list['Buang'] = df_list['Buang'].fillna(False).astype(bool)

    # ==============================================================================
    # LOGIC FILTER:
    # Ambil Kode produk dimana: (Forecast == True) DAN (Buang == False)
    # ==============================================================================
    valid_products_df = df_list[
        (df_list['Forecast'] == True) & 
        (df_list['Buang'] == False)
    ]
    
    # Buat set kode unik untuk pencarian cepat (O(1))
    valid_codes = set(valid_products_df['Kode'].astype(str).unique())
    print(f"Jumlah produk valid untuk diforecast (Forecast=True & Buang=False): {len(valid_codes)} produk.")

except Exception as e:
    print(f"ERROR: Gagal membaca atau memproses file list produk: {e}")
    sys.exit()

# 3. Preprocessing (Grouping Bulanan)
print("Melakukan grouping bulanan...")
df['Month'] = df['Tanggal'].dt.to_period('M')
monthly_data = df.groupby(['Kode', 'Nama Barang', 'Month'])['Quantity'].sum().reset_index()
monthly_data['Month'] = monthly_data['Month'].dt.to_timestamp()

# Ambil list unik produk dari data penjualan
products = monthly_data[['Kode', 'Nama Barang']].drop_duplicates().values

results_list = []

# ==========================================
# DEFINISI RANGE PARAMETER
# ==========================================
p_range = range(0, 5)  # 0, 1, 2, 3, 4
d_range = range(0, 2)  # 0, 1
q_range = range(0, 5)  # 0, 1, 2, 3, 4

P_range = range(0, 3)  # 0, 1, 2
D_range = range(0, 2)  # 0, 1
Q_range = range(0, 3)  # 0, 1, 2

s_list = [2, 3, 6, 12] 

print(f"Memulai proses SARIMA Brute-force.")
print("Tekan Ctrl+C untuk menghentikan paksa jika terlalu lama.")

# Nama file output partial
partial_output_file = 'SARIMA_bruteforce_partial.csv'

for i, (kode, nama) in enumerate(products):
    
    # Konversi kode ke string untuk pencocokan yang aman
    kode_str = str(kode)
    
    print(f"\n[{i+1}/{len(products)}] Checking: {kode} - {nama}")

    # ==============================================================================
    # CEK APAKAH PRODUK ADA DI LIST VALID
    # ==============================================================================
    if kode_str not in valid_codes:
        print(f"  -> SKIP: Produk tidak masuk kriteria (Forecast=False atau Buang=True).")
        continue

    # Jika lolos filter, lanjutkan proses
    start_time = datetime.now()
    print(f"  -> Mulai Grid Search: {start_time.strftime('%H:%M:%S')}")

    try:
        # Filter data
        prod_data = monthly_data[monthly_data['Kode'] == kode].set_index('Month')['Quantity']
        
        # Fill missing months
        full_idx = pd.date_range(start=prod_data.index.min(), end=prod_data.index.max(), freq='MS')
        prod_data = prod_data.reindex(full_idx, fill_value=0)
        
        # Split Data
        if len(prod_data) <= 12:
            print(f"  -> SKIP: Data kurang dari 12 bulan (Total: {len(prod_data)} bulan).")
            continue
            
        train = prod_data.iloc[:-12]
        test = prod_data.iloc[-12:]
        
        # --- 1. Brute Force Search ---
        print("  -> Menjalankan optimasi parameter...")
        best_params = optimize_sarima(train, test, p_range, d_range, q_range, 
                                      P_range, D_range, Q_range, s_list)
        
        if best_params is None:
            print("  -> Gagal menemukan parameter yang valid. Menggunakan default.")
            order = (1, 1, 1)
            seasonal_order = (0, 1, 1, 12)
        else:
            order, seasonal_order = best_params
            print(f"  -> Parameter Terbaik: Order={order}, Seasonal={seasonal_order}")

        # --- 2. Fit & Evaluasi Model ---
        
        # Helper function
        def fit_evaluate(train_data, test_data, ord, s_ord, transform_type='none', lam=None):
            try:
                model = SARIMAX(train_data, order=ord, seasonal_order=s_ord, 
                                enforce_stationarity=False, enforce_invertibility=False)
                res = model.fit(disp=False, maxiter=100)
                pred_trans = res.get_forecast(steps=len(test_data)).predicted_mean
                
                # Inverse Transform logic
                if transform_type == 'boxcox':
                    pred = inv_boxcox(pred_trans, lam) - 1.0 
                elif transform_type == 'yeojohnson':
                    pred = np.zeros_like(pred_trans)
                    for idx, val in enumerate(pred_trans):
                        if val >= 0 and lam != 0:
                            pred[idx] = np.power(val * lam + 1, 1 / lam) - 1
                        elif val >= 0 and lam == 0:
                            pred[idx] = np.exp(val) - 1
                        else:
                            denom = 2 - lam
                            term = -val * denom + 1
                            pred[idx] = 1 - np.power(term, 1/denom) if term >= 0 else 0
                else:
                    pred = pred_trans
                
                return calculate_metrics(test_data, pred)
            except:
                return np.nan, np.nan

        # A. Baseline (Raw)
        rmse_raw, mape_raw = fit_evaluate(train, test, order, seasonal_order)

        # B. Moving Average (MA)
        try:
            train_ma = train.rolling(window=3).mean().fillna(method='bfill')
            rmse_ma, mape_ma = fit_evaluate(train_ma, test, order, seasonal_order)
        except:
            rmse_ma, mape_ma = np.nan, np.nan

        # C. Savitzky-Golay (SG)
        try:
            w_len = 5 if len(train) >= 5 else 3
            train_sg = pd.Series(savgol_filter(train, window_length=w_len, polyorder=2), index=train.index)
            rmse_sg, mape_sg = fit_evaluate(train_sg, test, order, seasonal_order)
        except:
            rmse_sg, mape_sg = np.nan, np.nan

        # D. Box-Cox (BC)
        try:
            train_bc_input = train + 1.0 
            train_bc, lam_bc = boxcox(train_bc_input)
            train_bc = pd.Series(train_bc, index=train.index)
            rmse_bc, mape_bc = fit_evaluate(train_bc, test, order, seasonal_order, 'boxcox', lam_bc)
        except:
            rmse_bc, mape_bc = np.nan, np.nan

        # E. Yeo-Johnson (YJ)
        try:
            train_yj, lam_yj = yeojohnson(train)
            train_yj = pd.Series(train_yj, index=train.index)
            rmse_yj, mape_yj = fit_evaluate(train_yj, test, order, seasonal_order, 'yeojohnson', lam_yj)
        except:
            rmse_yj, mape_yj = np.nan, np.nan

        end_time = datetime.now()
        duration = end_time - start_time
        
        print(f"  -> Selesai. Durasi: {duration}")

        # Simpan Hasil ke Memory List
        row = [
            kode, nama,
            order[0], order[1], order[2], 
            seasonal_order[0], seasonal_order[1], seasonal_order[2], seasonal_order[3], # s
            rmse_raw, mape_raw,
            rmse_ma, mape_ma,
            rmse_sg, mape_sg,
            rmse_bc, mape_bc,
            rmse_yj, mape_yj
        ]
        results_list.append(row)
        
        # ==============================================================================
        # SIMPAN PARTIAL SETIAP 1 PRODUK
        # ==============================================================================
        # Sangat disarankan untuk menyimpan setiap 1 produk selesai karena proses lama
        temp_df = pd.DataFrame(results_list)
        # Tambahkan header manual karena list raw tidak punya header
        temp_df.to_csv(partial_output_file, index=False, header=False) 
        print("  -> [Checkpoint] Hasil sementara disimpan.")

    except Exception as e:
        print(f"  -> Error pada produk {kode}: {e}")

# ==========================================
# SAVING FINAL OUTPUT
# ==========================================
output_file = 'SARIMA_bruteforce_result_final.csv'

header1 = ['Kode', 'Nama Produk', 'Parameters', '', '', '', '', '', '', 'Baseline (Raw)', '', 'Smoothing (MA)', '', 'Smoothing (SG)', '', 'Dist. Normalization (BC)', '', 'Dist. Normalization (YJ)', '']
header2 = ['', '', 'p', 'd', 'q', 'P', 'D', 'Q', 's', 'RMSE', 'MAPE', 'RMSE', 'MAPE', 'RMSE', 'MAPE', 'RMSE', 'MAPE', 'RMSE', 'MAPE']

try:
    with open(output_file, 'w', newline='') as f:
        writer = csv.writer(f)
        writer.writerow(header1)
        writer.writerow(header2)
        writer.writerows(results_list)
    print(f"\nSelesai! Hasil lengkap disimpan di {output_file}")
    
    # Hapus file partial jika final berhasil disimpan
    if os.path.exists(partial_output_file):
        os.remove(partial_output_file)
        
except Exception as e:
    print(f"Gagal menyimpan file CSV Final: {e}")
    print("Cek file partial 'SARIMA_bruteforce_partial.csv' untuk data yang sudah tersimpan.")