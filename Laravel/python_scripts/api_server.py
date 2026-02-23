from flask import Flask, request, jsonify
import pandas as pd
import numpy as np
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_squared_error, mean_absolute_percentage_error
from dateutil.relativedelta import relativedelta
from scipy import stats 
from scipy.signal import savgol_filter
import warnings
import itertools 
import time 

# Bersihkan warning
warnings.simplefilter(action='ignore', category=FutureWarning)
warnings.simplefilter(action='ignore', category=UserWarning)
from statsmodels.tools.sm_exceptions import ConvergenceWarning
warnings.simplefilter('ignore', ConvergenceWarning)

app = Flask(__name__)

# --- FUNGSI PREPROCESSING (Sama seperti sebelumnya) ---
def preprocess_data(series, method='raw'):
    series_clean = series.copy()
    param = None

    if method == 'ma': 
        series_clean = series_clean.rolling(window=3, min_periods=1).mean()
    
    elif method == 'sg': 
        window_length = 5
        if len(series_clean) < 5: window_length = 3
        if len(series_clean) >= window_length:
            series_clean[:] = savgol_filter(series_clean, window_length, 2)
            
    elif method == 'bc': 
        if (series_clean <= 0).any():
            offset = abs(series_clean.min()) + 1.0
            series_clean += offset
            series_clean, lmbda = stats.boxcox(series_clean)
            series_clean = pd.Series(series_clean, index=series.index)
            param = {'lambda': lmbda, 'offset': offset}
        else:
            series_clean, lmbda = stats.boxcox(series_clean)
            series_clean = pd.Series(series_clean, index=series.index)
            param = {'lambda': lmbda, 'offset': 0}

    elif method == 'yj': 
        series_clean, lmbda = stats.yeojohnson(series_clean)
        series_clean = pd.Series(series_clean, index=series.index)
        param = {'lambda': lmbda}

    return series_clean, param

# --- FUNGSI INVERSE (Sama seperti sebelumnya) ---
def inverse_transform(value, method, param):
    if method == 'bc':
        lmbda = param['lambda']
        offset = param['offset']
        from scipy.special import inv_boxcox
        inv_val = inv_boxcox(value, lmbda)
        return max(0, inv_val - offset)
    
    elif method == 'yj':
        lmbda = param['lambda']
        y = value
        if y >= 0 and lmbda != 0:
            x = np.power(y * lmbda + 1, 1 / lmbda) - 1
        elif y >= 0 and lmbda == 0:
            x = np.exp(y) - 1
        elif y < 0 and lmbda != 2:
            x = 1 - np.power(-(2 - lmbda) * y + 1, 1 / (2 - lmbda))
        else:
            x = 1 - np.exp(-y)
        return max(0, x)
        
    return max(0, value)

@app.route('/forecast', methods=['POST'])
def forecast():
    try:
        req_data = request.json
        sales_list = req_data['sales_data']
        target_date_str = req_data['target_date']
        params = req_data['params']
        # Ambil metode preprocessing dari request
        pre_proc_method = req_data.get('pre_processing', 'raw')
        if not sales_list:
            return jsonify({"error": "No sales data provided"}), 400
        # Convert ke DataFrame
        df = pd.DataFrame(sales_list)
        df['date'] = pd.to_datetime(df['date'])
        df.set_index('date', inplace=True)
        df.rename(columns={'qty': 'quantity_sold'}, inplace=True)

        # Resample Bulanan
        time_series = df['quantity_sold'].resample('MS').sum().fillna(0)

        # Check Length
        if len(time_series) < 24:
             return jsonify({"error": "Data too short (need min 24 months)"}), 400
        
        # ==========================================
        # APPLY PREPROCESSING
        # ==========================================
        # Kita simpan time_series asli untuk keperluan plotting/validasi jika perlu
        # Tapi model dilatih menggunakan processed_series

        processed_series, trans_param = preprocess_data(time_series, pre_proc_method)

        # Setup Parameter SARIMA
        my_order = (params[0], params[1], params[2])
        my_seasonal_order = (params[3], params[4], params[5], params[6])

        # ==========================================
        # 2. VALIDATION PHASE
        # ==========================================
        test_size = 12
        train = processed_series.iloc[:-test_size]
        test = processed_series.iloc[-test_size:]

        # Data Asli (Tanpa preprocessing) untuk menghitung RMSE/MAPE yang REAL
        real_test = time_series.iloc[-test_size:]

        # Fit Model pada Data Processed
        model = SARIMAX(train,
                        order=my_order,
                        seasonal_order=my_seasonal_order,
                        enforce_stationarity=False,
                        enforce_invertibility=False)
        model_fit = model.fit(disp=False)

        # Forecast (Hasil masih dalam skala Transformed jika pakai BC/YJ)
        forecast_obj = model_fit.get_forecast(steps=len(test))
        pred_transformed = forecast_obj.predicted_mean

        # Kembalikan ke Skala Asli (Inverse Transform)
        pred_final = []
        for val in pred_transformed:
            pred_final.append(inverse_transform(val, pre_proc_method, trans_param))
        pred_final = pd.Series(pred_final, index=test.index)

        # Hitung Metrics (Bandingkan Prediksi Final vs Data Asli)
        rmse = np.sqrt(mean_squared_error(real_test, pred_final))
        mape = mean_absolute_percentage_error(real_test, pred_final) * 100
        
        # Data Validasi untuk dikirim ke Laravel (Skala Asli)
        validation_data = []
        for date, val in pred_final.items():
            if date in real_test.index:
                validation_data.append({
                    'date': date.strftime('%Y-%m-%d'),
                    'actual': float(real_test.loc[date]), # Data Asli
                    'predicted': float(val)               # Prediksi (Inverted)
                })

        # ==========================================
        # 3. FINAL FORECAST PHASE (FULL TRAIN)
        # ==========================================
        final_prediction_val = 0
        forecast_date_res = target_date_str

        # Fit Model dengan SEMUA data (Processed)
        model_full = SARIMAX(processed_series,
                             order=my_order,
                             seasonal_order=my_seasonal_order,
                             enforce_stationarity=False,
                             enforce_invertibility=False)
        results_full = model_full.fit(disp=False)

        # Hitung steps ke masa depan
        target_date = pd.to_datetime(target_date_str)
        last_data_date = processed_series.index[-1]
        diff = relativedelta(target_date, last_data_date)
        steps_needed = diff.years * 12 + diff.months

        if steps_needed <= 0: steps_needed = 1

        future_forecast = results_full.get_forecast(steps=steps_needed)
        future_val_transformed = future_forecast.predicted_mean.iloc[-1]

        # Inverse hasil forecast masa depan
        final_prediction_val = inverse_transform(future_val_transformed, pre_proc_method, trans_param)
        final_prediction_val = round(final_prediction_val)

        return jsonify({
            "status": "success",
            "metrics": {
                "rmse": float(rmse),
                "mape": float(mape)
            },
            "forecast": {
                "date": forecast_date_res,
                "value": int(final_prediction_val)
            },
            "validation_data": validation_data,
            "preprocessing": pre_proc_method
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500

   

@app.route('/grid-search', methods=['POST'])
def grid_search():
    # 1. LOG AWAL - UNTUK MEMASTIKAN REQUEST MASUK
    print("\n" + "="*50, flush=True)
    print(">>> REQUEST RECEIVED: GRID SEARCH STARTED", flush=True)
    print("="*50 + "\n", flush=True)
    try:
        start_time = time.time()
        req_data = request.json
        sales_list = req_data['sales_data']
        
        # 1. Olah Data (Convert JSON ke Time Series)
        if not sales_list:
            return jsonify({"error": "No sales data provided"}), 400

        df = pd.DataFrame(sales_list)
        df['date'] = pd.to_datetime(df['date'])
        df.set_index('date', inplace=True)
        df.rename(columns={'qty': 'quantity_sold'}, inplace=True)
        
        # Resample Bulanan
        time_series = df['quantity_sold'].resample('MS').sum().fillna(0)
        
        if len(time_series) < 24:
             return jsonify({"error": "Data too short (need min 24 months)"}), 400

        print(f"\n[GRID SEARCH START] Processing {len(time_series)} months data...")

        # 2. Setup Data Latih & Uji (12 Bulan Terakhir untuk Test)
        test_size = 12
        train_raw = time_series.iloc[:-test_size]
        test_raw = time_series.iloc[-test_size:] # Data Asli untuk validasi akhir

        # ==============================================================================
        # STAGE 1: HYPERPARAMETER TUNING (Menggunakan Data RAW)
        # ==============================================================================
        # Tujuannya: Mencari (p,d,q)x(P,D,Q,s) terbaik murni dari pola data asli.
        # ==============================================================================
        

        p = range(0, 5) 
        d = range(0, 2) 
        q = range(0, 5) 
        P = range(0, 3) 
        D = range(0, 2) 
        Q = range(0, 3) 
        s = [2, 3, 6, 12]

        pdq = list(itertools.product(p, d, q))
        seasonal_pdq = list(itertools.product(P, D, Q, s))

        best_score_raw = float("inf")
        best_hyperparams = None # Akan menyimpan tuple (order, seasonal_order)
        
        total_combinations = len(pdq) * len(seasonal_pdq)
        count = 0

        print("--- STAGE 1: Hyperparameter Tuning (RAW Data) ---")

        for param in pdq:
            for param_seasonal in seasonal_pdq:
                count += 1
                try:
                    # Fit SARIMA pada Data RAW
                    mod = SARIMAX(train_raw,
                                  order=param,
                                  seasonal_order=param_seasonal,
                                  enforce_stationarity=False,
                                  enforce_invertibility=False)
                    
                    results = mod.fit(disp=False, maxiter=50)

                    # Forecast pada Data Test RAW
                    pred_obj = results.get_forecast(steps=len(test_raw))
                    pred = np.maximum(pred_obj.predicted_mean, 0)

                    # RMSE
                    rmse = np.sqrt(mean_squared_error(test_raw, pred))

                    if rmse < best_score_raw:
                        best_score_raw = rmse
                        best_hyperparams = (param, param_seasonal)
                        # print(f" -> New Best Raw: RMSE={rmse:.2f} | {param}x{param_seasonal}")

                except Exception:
                    continue

        if best_hyperparams is None:
            return jsonify({"error": "No suitable model found in Stage 1"}), 500

        print(f"Best Params from Raw: {best_hyperparams} with RMSE: {best_score_raw:.2f}")

        # ==============================================================================
        # STAGE 2: PREPROCESSING TUNING (Menggunakan Best Hyperparams)
        # ==============================================================================
        # Tujuannya: Cek apakah preprocessing (MA, BC, SG, YJ) bisa menurunkan RMSE
        # dengan menggunakan parameter model yang sudah ditemukan di Stage 1.
        # ==============================================================================
        
        preprocessing_methods = ['raw', 'ma', 'sg', 'bc', 'yj']
        
        final_best_method = 'raw'
        final_best_rmse = best_score_raw
        final_best_mape = mean_absolute_percentage_error(test_raw, np.full(len(test_raw), train_raw.mean())) * 100 # Dummy initial

        print("--- STAGE 2: Preprocessing Tuning ---")

        for method in preprocessing_methods:
            # Skip raw karena sudah dihitung di Stage 1
            if method == 'raw':
                # Hitung MAPE untuk Raw (tadi belum dihitung)
                # Re-run best raw model to get MAPE
                mod = SARIMAX(train_raw, order=best_hyperparams[0], seasonal_order=best_hyperparams[1],
                              enforce_stationarity=False, enforce_invertibility=False)
                res = mod.fit(disp=False)
                p_val = np.maximum(res.get_forecast(steps=len(test_raw)).predicted_mean, 0)
                mape_val = mean_absolute_percentage_error(test_raw, p_val) * 100
                
                final_best_mape = mape_val
                continue 

            try:
                # 1. Apply Preprocessing pada SELURUH data dulu baru split
                # (Agar continuity moving average/smoothing terjaga)
                series_processed, trans_param = preprocess_data(time_series, method)
                
                # 2. Split Train/Test (Data Processed)
                train_proc = series_processed.iloc[:-test_size]
                
                # 3. Fit SARIMA (Pakai Best Hyperparams dari Stage 1)
                mod = SARIMAX(train_proc,
                              order=best_hyperparams[0],
                              seasonal_order=best_hyperparams[1],
                              enforce_stationarity=False,
                              enforce_invertibility=False)
                
                results = mod.fit(disp=False, maxiter=50)

                # 4. Forecast (Hasil masih dalam bentuk Processed/Transformed)
                pred_obj = results.get_forecast(steps=len(test_raw))
                pred_transformed = pred_obj.predicted_mean

                # 5. Inverse Transform ke Skala Asli
                pred_final = []
                for val in pred_transformed:
                    pred_final.append(inverse_transform(val, method, trans_param))
                
                pred_final = pd.Series(pred_final, index=test_raw.index)

                # 6. Hitung RMSE terhadap DATA ASLI (Real Test)
                rmse_method = np.sqrt(mean_squared_error(test_raw, pred_final))
                
                print(f"Method: {method.upper()} -> RMSE: {rmse_method:.2f}")

                # 7. Bandingkan
                if rmse_method < final_best_rmse:
                    final_best_rmse = rmse_method
                    final_best_method = method
                    final_best_mape = mean_absolute_percentage_error(test_raw, pred_final) * 100
                    print(f" -> New Best Found: {method.upper()}")

            except Exception as e:
                print(f"Method {method} failed: {e}")
                continue

        elapsed = time.time() - start_time
        print(f"[FINISHED] Time: {elapsed:.2f}s | Best Method: {final_best_method} | RMSE: {final_best_rmse:.2f}")

        # Return Data
        best_order = best_hyperparams[0]
        best_season = best_hyperparams[1]

        return jsonify({
            "status": "success",
            "best_params": [
                best_order[0], best_order[1], best_order[2],           # p, d, q
                best_season[0], best_season[1], best_season[2], best_season[3] # P, D, Q, s
            ],
            "preprocessing": final_best_method,
            "metrics": {
                "rmse": float(final_best_rmse),
                "mape": float(final_best_mape)
            }
        })

    except Exception as e:
        print(f"[ERROR GRID SEARCH] {str(e)}")
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True, threaded=True)