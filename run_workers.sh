#!/bin/bash
cd /var/www/comexcare2

# Detener workers anteriores
pkill -f "queue:work" 2>/dev/null
sleep 2

# Cola de prioridad
nohup php artisan queue:work --queue=priority --sleep=1 --tries=3 --max-time=3600 >> storage/logs/worker_priority.log 2>&1 &
echo "Worker priority iniciado"

# Cola de distribución
nohup php artisan queue:work --queue=distribution --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker_distribution.log 2>&1 &
echo "Worker distribution iniciado"

# Cola de reportes
nohup php artisan queue:work --queue=reports --sleep=5 --tries=2 --max-time=3600 --memory=512 >> storage/logs/worker_reports.log 2>&1 &
echo "Worker reports iniciado"

# Cola default
nohup php artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker_default.log 2>&1 &
echo "Worker default iniciado"

echo ""
echo "=== Workers activos ==="
ps aux | grep "queue:work" | grep -v grep

echo ""
echo "=== Estado de colas Redis ==="
redis-cli LLEN queues:priority
redis-cli LLEN queues:distribution
redis-cli LLEN queues:reports
redis-cli LLEN queues:default
