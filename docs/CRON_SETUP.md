# AegisZ Sentinel - Cron Job Setup (v0.3.0)
# Schedule these workers to run automatically for passive data ingestion.

# ============================================================
# WINDOWS (XAMPP / PowerShell)
# ============================================================

# 1. Open PowerShell as Administrator
# 2. Navigate to project directory:
#    cd C:\Users\PC ELECTRONICS\Documents\Tools\XAMP\htdocs\aegisz-sentinel
#
# 3. Run a worker manually:
#    php app\Workers\NvdWorker.php
#
# 4. View logs (PowerShell equivalent of tail -f):
#    Get-Content -Wait storage\logs\app.log
#    Get-Content -Wait storage\logs\cron.log
#
# 5. To stop watching logs, press Ctrl+C

# ============================================================
# WINDOWS TASK SCHEDULER (For automated runs)
# ============================================================

# 1. Open Task Scheduler (taskschd.msc)
# 2. Create Basic Task for each worker
# 3. Set trigger frequency:
#    - URLHaus: Every 15 minutes
#    - OTX: Every 30 minutes
#    - NVD: Every hour
#    - CISA: Daily at 06:00
# 4. Action: Start a program
# 5. Program: C:\xampp\php\php.exe
# 6. Arguments: C:\Users\PC ELECTRONICS\Documents\Tools\XAMP\htdocs\aegisz-sentinel\app\Workers\NvdWorker.php
# 7. Start in: C:\Users\PC ELECTRONICS\Documents\Tools\XAMP\htdocs\aegisz-sentinel

# ============================================================
# LINUX / VPS / DEDICATED SERVER
# ============================================================

# Edit crontab:
#   crontab -e

# Add these lines:

# URLHaus - Every 15 minutes
*/15 * * * * cd /var/www/html/aegisz-sentinel && php app/Workers/UrlHausWorker.php >> storage/logs/cron.log 2>&1

# OTX - Every 30 minutes
*/30 * * * * cd /var/www/html/aegisz-sentinel && php app/Workers/OTXWorker.php >> storage/logs/cron.log 2>&1

# NVD - Every hour
0 * * * * cd /var/www/html/aegisz-sentinel && php app/Workers/NvdWorker.php >> storage/logs/cron.log 2>&1

# CISA KEV - Daily at 06:00
0 6 * * * cd /var/www/html/aegisz-sentinel && php app/Workers/CisaWorker.php >> storage/logs/cron.log 2>&1

# ============================================================
# SHARED HOSTING (cPanel)
# ============================================================

# 1. Log in to cPanel
# 2. Go to "Cron Jobs" (Advanced section)
# 3. Set email for notifications (optional)
# 4. Add New Cron Job with these settings:

# URLHaus Worker:
#   Command: php /home/yourusername/public_html/aegisz-sentinel/app/Workers/UrlHausWorker.php
#   Common Settings: Every 15 minutes

# OTX Worker:
#   Command: php /home/yourusername/public_html/aegisz-sentinel/app/Workers/OTXWorker.php
#   Common Settings: Every 30 minutes

# NVD Worker:
#   Command: php /home/yourusername/public_html/aegisz-sentinel/app/Workers/NvdWorker.php
#   Common Settings: Once per hour

# CISA Worker:
#   Command: php /home/yourusername/public_html/aegisz-sentinel/app/Workers/CisaWorker.php
#   Common Settings: Once per day (06:00)

# ============================================================
# MANUAL TESTING (One-time run)
# ============================================================

# From project root, run:
#   php app/Workers/UrlHausWorker.php
#   php app/Workers/OTXWorker.php
#   php app/Workers/NvdWorker.php
#   php app/Workers/CisaWorker.php

# Check logs:
#   Linux: tail -f storage/logs/app.log
#   Windows: Get-Content -Wait storage\logs\app.log

# ============================================================
# WORKER FREQUENCY RATIONALE
# ============================================================

# URLHaus (15 min): Malicious URLs are highly volatile, frequent updates
# OTX (30 min): Community pulses update regularly but not as fast as URLs
# NVD (1 hour): CVE database updates on a slower cycle
# CISA KEV (daily): Catalog is updated once per day by CISA

# All workers are SAFE:
# - No API keys required (all public endpoints)
# - Duplicate detection prevents database bloat
# - Timeout handling prevents hanging
# - Retry logic handles transient failures
# - Graceful error logging (never crashes)

## Intelligence Worker (v0.4.0)

Run AFTER all ingestion workers. Correlates data, scores risk, maps MITRE techniques.

```bash
# Manual run
php /path/to/aegisz-sentinel/cli/intelligence_worker.php

# CRON — run 5 minutes after ingestion workers complete
# (if ingestion workers run at *:00, schedule intelligence at *:05)
5 * * * * php /path/to/aegisz-sentinel/cli/intelligence_worker.php >> /path/to/storage/logs/intel.log 2>&1
```
