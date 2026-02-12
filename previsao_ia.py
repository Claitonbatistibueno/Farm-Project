import mysql.connector
import pandas as pd
import json
import os
import sys
import warnings
from datetime import datetime, date

# --- 1. CONFIGURAÇÃO ---
warnings.filterwarnings('ignore') # Limpa avisos técnicos
DB_CONFIG = {'host': 'localhost', 'user': 'root', 'password': '', 'database': 'farmproject'}

# Caminhos Absolutos
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_FILE = os.path.join(BASE_DIR, 'ai_analysis.json')
ERROR_FILE = os.path.join(BASE_DIR, 'ai_error.log')

# Constantes Financeiras
MARKET_PRICE = 3.20      # Preço de Venda ($/kg)
FIXED_COST_DAY = 0.15    # Custo Fixo Operacional

# --- 2. FUNÇÕES AUXILIARES ---
class CustomEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, (date, datetime)):
            return obj.strftime('%Y-%m-%d')
        return super().default(obj)

def safe_float(val):
    try:
        return float(val) if val is not None else 0.0
    except:
        return 0.0

def log_error(msg):
    try:
        with open(ERROR_FILE, 'w') as f: f.write(str(msg))
    except: pass

def run_analysis():
    conn = None
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        
        # --- CONSULTAS SQL CORRIGIDAS (Baseado no seu farmproject.sql) ---
        
        # 1. Buscar Animais + Raça (animal_types) + Lote (lot_animals)
        q_animals = """
            SELECT 
                a.animal_id, 
                a.tag_number, 
                COALESCE(at.breed, 'Unknown') as breed_name,
                COALESCE(la.lot_id, 0) as lot_id,
                w.weight_kg, 
                w.weighing_date 
            FROM animal a
            LEFT JOIN animal_types at ON a.type_id = at.type_id
            LEFT JOIN lot_animals la ON a.animal_id = la.animal_id AND la.exit_date IS NULL
            JOIN weighing w ON a.animal_id = w.animal_id
            WHERE a.status = 'active'
            ORDER BY a.animal_id, w.weighing_date ASC
        """
        
        # 2. Custos Médicos (health_records)
        q_meds = "SELECT animal_id, SUM(cost) as cost FROM health_records GROUP BY animal_id"
        
        # 3. Custo de Ração (daily_feeding)
        # Calcula o custo EXATO por animal, cruzando com a tabela 'feed' para pegar o preço
        q_feed = """
            SELECT df.animal_id, SUM(df.quantity_kg * f.cost_per_kg) as total_feed_cost
            FROM daily_feeding df
            JOIN feed f ON df.feed_id = f.feed_id
            GROUP BY df.animal_id
        """

        # Carregar DataFrames
        df = pd.read_sql(q_animals, conn)
        df_meds = pd.read_sql(q_meds, conn)
        df_feed = pd.read_sql(q_feed, conn)

        if df.empty:
            print("No data found.")
            with open(JSON_FILE, 'w') as f: f.write('{"animals": [], "alerts": [], "breeds": []}')
            return

        # --- PROCESSAMENTO ---
        
        # Mapas de Custos (Indexados pelo ID do animal para busca rápida)
        med_map = df_meds.set_index('animal_id')['cost'].to_dict()
        feed_map = df_feed.set_index('animal_id')['total_feed_cost'].to_dict()

        results = []
        alerts = []
        breed_stats = {}

        # Loop por Animal
        for pid, group in df.groupby('animal_id'):
            if len(group) < 2: continue
            
            last = group.iloc[-1]
            first = group.iloc[0]
            
            # Dados
            tag = str(last['tag_number'])
            breed = str(last['breed_name'])
            curr_weight = safe_float(last['weight_kg'])
            
            # Ganho de Peso
            days = (last['weighing_date'] - first['weighing_date']).days
            days = max(1, days)
            gain = curr_weight - safe_float(first['weight_kg'])
            adg = gain / days
            
            # Financeiro
            med_cost = safe_float(med_map.get(pid, 0))
            feed_cost = safe_float(feed_map.get(pid, 0))
            
            # Se não tiver registro de ração, estima um valor fixo por dia ($2.00)
            if feed_cost == 0:
                feed_cost = 2.00 * days
            
            fixed_cost = FIXED_COST_DAY * days
            
            total_invested = med_cost + feed_cost + fixed_cost
            revenue = curr_weight * MARKET_PRICE
            profit = revenue - total_invested
            
            # Decisão IA
            status = "Normal"
            css = "neutral"
            msg = "Monitoring."

            if profit < 0:
                status = "Critical Loss"
                css = "danger"
                msg = "Costs exceed value."
            elif med_cost > (total_invested * 0.4):
                status = "Health Risk"
                css = "warning"
                msg = "High medical costs."
            elif profit > 300 and adg > 1.2:
                status = "Top Performer"
                css = "success"
                msg = "High profitability."

            item = {
                'tag': tag,
                'breed': breed,
                'weight': round(curr_weight, 1),
                'profit': round(profit, 2),
                'status': status,
                'css': css,
                'msg': msg
            }
            results.append(item)
            
            if css in ['danger', 'warning']: alerts.append(item)
            
            # Stats Raça
            if breed not in breed_stats: breed_stats[breed] = {'p': 0, 'c': 0}
            breed_stats[breed]['p'] += profit
            breed_stats[breed]['c'] += 1

        # Consolidar
        breed_list = []
        for b, d in breed_stats.items():
            breed_list.append({
                'name': b,
                'avg_profit': round(d['p'] / d['c'], 2)
            })
        
        breed_list.sort(key=lambda x: x['avg_profit'], reverse=True)
        results.sort(key=lambda x: x['profit'], reverse=True)

        # Salvar JSON
        final_data = {
            'updated': datetime.now().strftime("%Y-%m-%d %H:%M"),
            'animals': results[:10],
            'alerts': alerts,
            'breeds': breed_list
        }

        with open(JSON_FILE, 'w', encoding='utf-8') as f:
            json.dump(final_data, f, cls=CustomEncoder, indent=4)
        
        print("Analysis success.")

    except Exception as e:
        log_error(str(e))
        print(f"Error: {e}")
    finally:
        if conn: conn.close()

if __name__ == "__main__":
    run_analysis()