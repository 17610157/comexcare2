#!/bin/bash

# ComexCare2 Queue Workers Manager
# Usage: ./queue-workers.sh start|stop|restart|status

APP_DIR="/var/www/comexcare2"
LOG_DIR="$APP_DIR/storage/logs"
PID_DIR="$APP_DIR/storage/pids"

mkdir -p "$LOG_DIR" "$PID_DIR"

QUEUE_DISTRIBUTIONS_PIDS=()
QUEUE_DEFAULT_PIDS=()

start_workers() {
    echo "Starting queue workers..."
    
    # Start distributions queue workers (2 processes)
    for i in 1 2; do
        php "$APP_DIR/artisan" queue:work redis --queue=distributions --sleep=3 --tries=3 --timeout=300 >> "$LOG_DIR/queue-distributions.log" 2>&1 &
        PID=$!
        QUEUE_DISTRIBUTIONS_PIDS+=($PID)
        echo $PID > "$PID_DIR/queue-distributions-$i.pid"
        echo "Started distributions worker $i (PID: $PID)"
    done
    
    # Start default queue workers (2 processes)
    for i in 1 2; do
        php "$APP_DIR/artisan" queue:work redis --queue=default --sleep=3 --tries=3 >> "$LOG_DIR/queue-default.log" 2>&1 &
        PID=$!
        QUEUE_DEFAULT_PIDS+=($PID)
        echo $PID > "$PID_DIR/queue-default-$i.pid"
        echo "Started default worker $i (PID: $PID)"
    done
    
    echo "All workers started!"
    echo "Distributions: ${QUEUE_DISTRIBUTIONS_PIDS[*]}"
    echo "Default: ${QUEUE_DEFAULT_PIDS[*]}"
}

stop_workers() {
    echo "Stopping queue workers..."
    
    for pidfile in "$PID_DIR"/*.pid; do
        if [ -f "$pidfile" ]; then
            PID=$(cat "$pidfile")
            if kill -0 "$PID" 2>/dev/null; then
                kill "$PID"
                echo "Stopped process $PID"
            fi
            rm "$pidfile"
        fi
    done
    
    echo "All workers stopped."
}

status_workers() {
    echo "Queue Worker Status:"
    echo "===================="
    
    for pidfile in "$PID_DIR"/*.pid; do
        if [ -f "$pidfile" ]; then
            NAME=$(basename "$pidfile" .pid)
            PID=$(cat "$pidfile")
            if kill -0 "$PID" 2>/dev/null; then
                echo "✓ $NAME - Running (PID: $PID)"
            else
                echo "✗ $NAME - Not running (stale PID file)"
                rm "$pidfile"
            fi
        fi
    done
    
    echo ""
    echo "Queue Stats:"
    php "$APP_DIR/artisan" tinker --execute="echo 'Distributions queue: ' . \Illuminate\Support\Facades\Redis::llen('queues:distributions') . ' jobs' . PHP_EOL; echo 'Default queue: ' . \Illuminate\Support\Facades\Redis::llen('queues:default') . ' jobs' . PHP_EOL;" 2>/dev/null
}

case "$1" in
    start)
        start_workers
        ;;
    stop)
        stop_workers
        ;;
    restart)
        stop_workers
        sleep 2
        start_workers
        ;;
    status)
        status_workers
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac

exit 0
