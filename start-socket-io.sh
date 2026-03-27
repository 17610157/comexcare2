#!/bin/bash

cd "$(dirname "$0")"

echo "Stopping existing Socket.io server..."
pkill -f "socket-server.cjs" 2>/dev/null
sleep 1

echo "Starting Socket.io server..."
nohup node socket-server.cjs > storage/logs/socket-io.log 2>&1 &
WS_PID=$!

echo "Socket.io server started (PID: $WS_PID)"

sleep 2
if nc -z localhost 6001 2>/dev/null; then
    echo "✓ Socket.io server is running on port 6001"
else
    echo "Checking logs..."
    tail storage/logs/socket-io.log
fi
