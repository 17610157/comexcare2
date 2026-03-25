#!/bin/bash

cd "$(dirname "$0")"

echo "Stopping existing WebSocket server..."
fuser -k 6001/tcp 2>/dev/null
sleep 1

echo "Starting WebSocket server..."
node websocket-server.js > storage/logs/websocket.log 2>&1 &
WS_PID=$!

echo "WebSocket server started (PID: $WS_PID)"

sleep 2
if nc -z localhost 6001 2>/dev/null; then
    echo "✓ WebSocket server is running on port 6001"
else
    echo "Checking logs..."
    tail storage/logs/websocket.log
fi
