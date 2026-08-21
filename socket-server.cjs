const { Server } = require('socket.io');
const { createClient } = require('redis');
const os = require('os');
const fs = require('fs');

const PORT = process.env.WS_PORT || 6001;
const REDIS_URL = process.env.REDIS_URL || 'redis://127.0.0.1:6379';
const METRICS_INTERVAL = parseInt(process.env.METRICS_INTERVAL, 10) || 2000;

const io = new Server(PORT, {
    cors: {
        origin: '*',
    }
});

const redisSubscriber = createClient({ url: REDIS_URL });
const redisPublisher = createClient({ url: REDIS_URL });

const userSockets = new Map();

// ---------- Server metrics collector ----------
let prevCpu = null;
let prevNet = null;

function cpuSnapshot() {
    const cpus = os.cpus();
    let idle = 0, total = 0;
    for (const c of cpus) {
        idle += c.times.idle;
        total += c.times.user + c.times.nice + c.times.sys + c.times.idle + c.times.irq;
    }
    return { idle, total };
}

function netSnapshot() {
    try {
        const raw = fs.readFileSync('/proc/net/dev', 'utf8');
        let rx = 0, tx = 0;
        for (const line of raw.split('\n').slice(2)) {
            const idx = line.indexOf(':');
            if (idx < 0) continue;
            const name = line.slice(0, idx).trim();
            if (name === 'lo' || name.startsWith('veth') || name.startsWith('docker') || name.startsWith('br-')) continue;
            const cols = line.slice(idx + 1).trim().split(/\s+/);
            rx += parseInt(cols[0], 10) || 0;
            tx += parseInt(cols[8], 10) || 0;
        }
        return { rx, tx };
    } catch (e) {
        return null;
    }
}

function diskStats(path) {
    try {
        const s = fs.statfsSync(path);
        const total = s.blocks * s.bsize;
        const free = s.bavail * s.bsize;
        const used = total - free;
        return {
            total,
            used,
            free,
            pct: total > 0 ? +((used / total) * 100).toFixed(1) : 0
        };
    } catch (e) {
        return null;
    }
}

function swapStats() {
    try {
        const raw = fs.readFileSync('/proc/meminfo', 'utf8');
        const get = (k) => {
            const m = raw.match(new RegExp('^' + k + ':\\s+(\\d+) kB', 'm'));
            return m ? parseInt(m[1], 10) * 1024 : 0;
        };
        const total = get('SwapTotal');
        const free = get('SwapFree');
        return { total, used: total - free, pct: total > 0 ? +(((total - free) / total) * 100).toFixed(1) : 0 };
    } catch (e) {
        return { total: 0, used: 0, pct: 0 };
    }
}

function collectMetrics() {
    const now = Date.now();
    const curCpu = cpuSnapshot();
    let cpuPct = 0;
    if (prevCpu) {
        const dTotal = curCpu.total - prevCpu.total;
        const dIdle = curCpu.idle - prevCpu.idle;
        if (dTotal > 0) cpuPct = Math.max(0, Math.min(100, +(100 * (1 - dIdle / dTotal)).toFixed(1)));
    }
    prevCpu = curCpu;

    const curNet = netSnapshot();
    let net = { rx_kbps: 0, tx_kbps: 0 };
    if (curNet && prevNet) {
        const dt = (now - prevNet.ts) / 1000;
        if (dt > 0 && curNet.rx >= prevNet.rx) {
            net.rx_kbps = +Math.max(0, ((curNet.rx - prevNet.rx) / dt / 1024)).toFixed(1);
            net.tx_kbps = +Math.max(0, ((curNet.tx - prevNet.tx) / dt / 1024)).toFixed(1);
        }
    }
    if (curNet) prevNet = { ...curNet, ts: now };

    const memTotal = os.totalmem();
    const memUsed = memTotal - os.freemem();

    return {
        ts: now,
        cpu: cpuPct,
        cores: os.cpus().length,
        load1: +(os.loadavg()[0]).toFixed(2),
        mem_pct: +((memUsed / memTotal) * 100).toFixed(1),
        mem_used: memUsed,
        mem_total: memTotal,
        swap: swapStats(),
        disk: diskStats('/'),
        net,
        uptime_s: Math.round(os.uptime())
    };
}

async function start() {
    try {
        await redisSubscriber.connect();
        await redisPublisher.connect();
        console.log('Connected to Redis');

        await redisSubscriber.subscribe('laravel-database-distributions', (message) => {
            try {
                const data = JSON.parse(message);
                console.log('Received broadcast:', data);

                const distributionId = data.distribution_id;
                if (distributionId) {
                    io.to(`distribution.${distributionId}`).emit('distribution.progress', data);
                }
                io.to('distributions').emit('distribution.progress', data);
            } catch (e) {
                console.error('Error processing broadcast:', e);
            }
        });

        await redisSubscriber.pSubscribe('laravel-database-private-distribution.*', (message, channel) => {
            try {
                const data = JSON.parse(message);
                console.log('Received private broadcast:', channel);

                const distributionId = channel.split('.').pop();
                if (distributionId) {
                    io.to(`distribution.${distributionId}`).emit('distribution.progress', data);
                }
            } catch (e) {
                console.error('Error processing private broadcast:', e);
            }
        });

        await redisSubscriber.subscribe('laravel-database-dashboard', (message) => {
            try {
                const data = JSON.parse(message);
                io.to('dashboard').emit('stats.updated', data.data || {});
                console.log('Dashboard stats update broadcast');
            } catch (e) {
                console.error('Error processing dashboard broadcast:', e);
            }
        });

        console.log(`Socket.io server running on port ${PORT}`);

        // Emit server metrics to the dashboard room in real time
        setInterval(() => {
            try {
                io.to('dashboard').emit('server.metrics', collectMetrics());
            } catch (e) {
                console.error('Error collecting metrics:', e);
            }
        }, METRICS_INTERVAL);

        io.on('connection', (socket) => {
            console.log('Client connected:', socket.id);

            socket.on('subscribe', (data) => {
                const { channel } = data;
                console.log(`Client ${socket.id} subscribing to: ${channel}`);
                
                const channelName = channel.replace('private-', '');
                socket.join(channelName);
                userSockets.set(socket.id, channelName);
            });

            socket.on('disconnect', () => {
                console.log('Client disconnected:', socket.id);
                userSockets.delete(socket.id);
            });
        });

    } catch (error) {
        console.error('Failed to start:', error);
        process.exit(1);
    }
}

start();
