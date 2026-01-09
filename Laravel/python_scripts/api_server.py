from flask import Flask, request, jsonify
import pandas as pd
import numpy as np
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_squared_error, mean_absolute_percentage_error
from dateutil.relativedelta import relativedelta
import warnings
import itertools 
import time     

# Bersihkan warning
warnings.simplefilter(action='ignore', category=FutureWarning)
warnings.simplefilter(action='ignore', category=UserWarning)
from statsmodels.tools.sm_exceptions import ConvergenceWarning
warnings.simplefilter('ignore', ConvergenceWarning)

app = Flask(__name__)

@app.route('/forecast', methods=['POST'])
def forecast():
    try:
        # ==========================================
        # 1. TERIMA INPUT & PRE-PROCESSING
        # ==========================================
        req_data = request.json
        
        sales_list = req_data['sales_data']
        target_date_str = req_data['target_date']
        params = req_data['params'] 
        cutoff_flag = int(req_data['cutoff']) 
        
        if not sales_list:
            return jsonify({"error": "No sales data provided"}), 400

        # Convert ke DataFrame
        df = pd.DataFrame(sales_list)
        df['date'] = pd.to_datetime(df['date'])
        
        # --- LOGIKA PRE-PROCESSING ---
        df.set_index('date', inplace=True)
        df.rename(columns={'qty': 'quantity_sold'}, inplace=True)
        
        # Resample Bulanan
        time_series = df['quantity_sold'].resample('MS').sum().fillna(0)
      
        # =========================================================
        # [DEBUG DIAGNOSTIC] - CEK DATA YANG MASUK KE MODEL
        # =========================================================
        print("\n" + "="*40)
        print("  DEBUG: CHECK DATA INPUT (PYTHON API)")
        print("="*40)
        print(f"1. Total Baris Data Mentah (JSON) : {len(sales_list)}")
        print(f"2. Total Bulan (setelah resample) : {len(time_series)}")
        print(f"3. TOTAL QUANTITY (SUM)           : {time_series.sum()}")
        print("-" * 40)
        print("4. Data 5 Bulan Pertama (API):")
        print(time_series.head(5).to_string())
        print("="*40 + "\n")
        # =========================================================
        
        # Cek ketersediaan data
        if len(time_series) < 24:
             return jsonify({"error": "Data too short (need min 24 months)"}), 400

        # Setup Parameter
        my_order = (params[0], params[1], params[2])
        my_seasonal_order = (params[3], params[4], params[5], params[6])

        # ==========================================
        # 2. VALIDATION PHASE
        # ==========================================
        test_size = 12
        train = time_series.iloc[:-test_size]
        test = time_series.iloc[-test_size:]
        
        # Fit Model
        model = SARIMAX(train, 
                        order=my_order, 
                        seasonal_order=my_seasonal_order,
                        enforce_stationarity=False,
                        enforce_invertibility=False)
        model_fit = model.fit(disp=False)
        
        # Forecast
        forecast_obj = model_fit.get_forecast(steps=len(test))
        pred = np.maximum(forecast_obj.predicted_mean, 0) 

        # Hitung Metrics (SKLEARN)
        rmse = np.sqrt(mean_squared_error(test, pred))
        mape = mean_absolute_percentage_error(test, pred) * 100

        # Debug Hasil Prediksi di Terminal
        print(f"DEBUG: RMSE API = {rmse}")
        print(f"DEBUG: Prediksi API (First 5): {pred.head(5).tolist()}")

        # Siapkan data validasi
        validation_data = []
        for date, val in pred.items():
            if date in test.index:
                validation_data.append({
                    'date': date.strftime('%Y-%m-%d'),
                    'actual': float(test.loc[date]),
                    'predicted': float(val)
                })

        # ==========================================
        # 3. FINAL FORECAST PHASE
        # ==========================================
        final_prediction = 0
        forecast_date_res = target_date_str

        if cutoff_flag == 1:
            final_prediction = max(0, round(pred.iloc[-1]))
        else:
            model_full = SARIMAX(time_series, 
                                 order=my_order, 
                                 seasonal_order=my_seasonal_order,
                                 enforce_stationarity=False,
                                 enforce_invertibility=False)
            results_full = model_full.fit(disp=False)
            
            target_date = pd.to_datetime(target_date_str)
            last_data_date = time_series.index[-1]
            diff = relativedelta(target_date, last_data_date)
            steps_needed = diff.years * 12 + diff.months
            if steps_needed <= 0: steps_needed = 1
            
            future_forecast = results_full.get_forecast(steps=steps_needed)
            future_pred = np.maximum(future_forecast.predicted_mean, 0)
            final_prediction = max(0, round(future_pred.iloc[-1]))

        return jsonify({
            "status": "success",
            "metrics": {
                "rmse": float(rmse), 
                "mape": float(mape)
            },
            "forecast": {
                "date": forecast_date_res, 
                "value": int(final_prediction)
            },
            "validation_data": validation_data,
            "mode": "Backtest (Split)" if cutoff_flag == 1 else "Future (Full Train)"
        })

    except Exception as e:
        return jsonify({"error": str(e)}), 500
    

@app.route('/grid-search', methods=['POST'])
def grid_search():
    try:
        start_time = time.time()
        req_data = request.json
        sales_list = req_data['sales_data']
        
        # 1. Olah Data (Sama persis dengan forecast)
        if not sales_list:
            return jsonify({"error": "No sales data provided"}), 400

        df = pd.DataFrame(sales_list)
        df['date'] = pd.to_datetime(df['date'])
        df.set_index('date', inplace=True)
        df.rename(columns={'qty': 'quantity_sold'}, inplace=True)
        
        time_series = df['quantity_sold'].resample('MS').sum().fillna(0)
        
        if len(time_series) < 24:
             return jsonify({"error": "Data too short (need min 24 months)"}), 400

        print(f"\n[GRID SEARCH START] Processing {len(time_series)} months data...")

        # 2. Setup Data Latih & Uji (Split 12 bulan terakhir untuk validasi RMSE)
        test_size = 12
        train = time_series.iloc[:-test_size]
        test = time_series.iloc[-test_size:]

        # 3. Tentukan Range Parameter
        # Range kecil agar cepat (p,q: 0-2 | d: 0-1)
        # Total kombinasi = 3*2*3 * 2*2*2 = 144 Model per produk
        p = range(0, 3) 
        d = range(0, 2) 
        q = range(0, 3) 
        
        P = range(0, 2) 
        D = range(0, 2) 
        Q = range(0, 2) 
        s = 12 # Monthly Seasonality

        pdq = list(itertools.product(p, d, q))
        seasonal_pdq = list(itertools.product(P, D, Q, [s]))

        best_score = float("inf") # RMSE Terkecil
        best_params = None
        best_mape = None
        
        count = 0
        total_combinations = len(pdq) * len(seasonal_pdq)

        # 4. Looping
        for param in pdq:
            for param_seasonal in seasonal_pdq:
                count += 1
                try:
                    # Log setiap 20 model agar terminal tidak sepi
                    if count % 20 == 0:
                        print(f"Checking Model {count}/{total_combinations}...")

                    # Fit Model
                    mod = SARIMAX(train,
                                  order=param,
                                  seasonal_order=param_seasonal,
                                  enforce_stationarity=False,
                                  enforce_invertibility=False)
                    
                    results = mod.fit(disp=False, maxiter=50) # maxiter kecil agar cepat skip jika susah konvergen

                    # Prediksi
                    pred_obj = results.get_forecast(steps=len(test))
                    pred = np.maximum(pred_obj.predicted_mean, 0)

                    # RMSE
                    rmse = np.sqrt(mean_squared_error(test, pred))

                    if rmse < best_score:
                        best_score = rmse
                        best_params = (param, param_seasonal)
                        best_mape = mean_absolute_percentage_error(test, pred) * 100
                        print(f"  -> New Best: RMSE={rmse:.2f} | Order={param}x{param_seasonal}")

                except Exception:
                    continue 

        if best_params is None:
            return jsonify({"error": "No suitable model found"}), 500

        best_order = best_params[0]
        best_season = best_params[1]
        
        elapsed = time.time() - start_time
        print(f"[FINISHED] Time: {elapsed:.2f}s | Best RMSE: {best_score}")

        # Return format yang sesuai dengan Job Laravel
        return jsonify({
            "status": "success",
            "best_params": [
                best_order[0], best_order[1], best_order[2],           # p, d, q
                best_season[0], best_season[1], best_season[2], best_season[3] # P, D, Q, s
            ],
            "metrics": {
                "rmse": float(best_score),
                "mape": float(best_mape)
            }
        })

    except Exception as e:
        print(f"[ERROR GRID SEARCH] {str(e)}")
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True, threaded=True)