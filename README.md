# 🚜 Farm-Project

Welcome to **Farm-Project**! This is a complete agricultural management system focused on animal tracking, health monitoring, feeding control, financial management, comprehensive reporting, and Artificial Intelligence integration for predictions and visual analysis.

## 🌟 Main Features

* **Authentication & Access:** Secure login system (`login.php`) starting from the main entry point (`index.html`).
* **Central Dashboard:** System overview (`dashboard.php`).
* **Financial Hub:** Financial management and overview (`financial_dashboard.php`).
* **Comprehensive Reports (`reports.php`):**
  * **Animal Reports:** Detailed herd reports and history (`reports_animals.php`).
  * **Feeding Reports:** Animal nutrition history and management (`reports_feeding.php`).
  * **Health Reports:** Health data tracking (`reports_health.php`).
  * **Financial Reports:** Detailed financial tracking and summaries (`reports_financial.php`).
* **Artificial Intelligence (AI) Integration:**
  * Computer vision analysis (`ai_vision.php`).
  * AI prediction module (`previsao_ia.py`).
  * Data logging and analysis in JSON (`ai_analysis.json`).
  * Debug tools and logs (`debug_ai.php`, `ai_error_log.txt`).

## 🛠️ Technologies Used

* **Frontend:** HTML
* **Backend:** PHP
* **Artificial Intelligence/Scripts:** Python
* **Database:** SQL (MySQL/MariaDB)
* **Data Structure:** JSON

## 🚀 How to Setup and Run (Basic Guide)

1. **Database:** * Create a database on your local server (e.g., XAMPP, WAMP, or web server).
   * Import the `farmproject.sql` file to recreate the necessary tables.
2. **Web Server & Access:**
   * Place the project files in your server's public directory (e.g., the `htdocs` folder in XAMPP).
   * **System Entry:** Access the system through your web browser by opening `index.html`.
   * You will be directed to the login screen (`login.php`). Simply enter your credentials to authenticate and gain access to the dashboard and all other system routines.
3. **AI Modules (Python):**
   * Make sure you have Python installed in your environment.
   * Run the `previsao_ia.py` script as needed to feed data into the system.

## ⚠️ Development Notes

* Log files (`ai_error_log.txt`) and debug files (`debug_ai.php`) are included to help identify issues in AI integrations. 
* Ensure database credentials are correctly configured in the PHP files before using in production.

---
*Developed by Claitonbatistibueno
