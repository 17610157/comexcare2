const { Server } = require('socket.io');
const { createClient } = require('redis');

const PORT = process.env.WS_PORT || 6001;
const REDIS_URL = process.env.REDIS_URL || 'redis://127.0.0.1:6379';

const io = new Server(PORT, {
    cors: {
        origin: '*',
    }
});

const redisSubscriber = createClient({ url: REDIS_URL });
const redisPublisher = createClient({ url: REDIS_URL });

const userSockets = new Map();

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
