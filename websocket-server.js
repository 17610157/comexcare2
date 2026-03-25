import { WebSocketServer } from 'ws';
import { createClient } from 'redis';

const PORT = process.env.WS_PORT || 6001;
const REDIS_URL = process.env.REDIS_URL || 'redis://127.0.0.1:6379';

const CHANNELS = [
    'laravel_database_distributions',
    'private-distribution',
];

const wss = new WebSocketServer({ port: PORT });

const redisSubscriber = createClient({ url: REDIS_URL });

const clients = new Map();

wss.on('connection', (ws) => {
    console.log('Client connected');
    
    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message.toString());
            if (data.event === 'subscribe' && data.channel) {
                const channelName = data.channel.replace('private-', '');
                console.log(`Client subscribed to: ${data.channel}`);
                
                if (!clients.has(channelName)) {
                    clients.set(channelName, new Set());
                }
                clients.get(channelName).add(ws);
                ws.subscribedChannels = ws.subscribedChannels || new Set();
                ws.subscribedChannels.add(channelName);
            }
        } catch (e) {
            console.error('Invalid message:', e);
        }
    });
    
    ws.on('close', () => {
        console.log('Client disconnected');
        if (ws.subscribedChannels) {
            ws.subscribedChannels.forEach(channel => {
                const channelClients = clients.get(channel);
                if (channelClients) {
                    channelClients.delete(ws);
                }
            });
        }
    });
    
    ws.on('error', (error) => {
        console.error('WebSocket error:', error);
    });
});

async function start() {
    try {
        await redisSubscriber.connect();
        console.log('Connected to Redis');
        
        for (const channel of CHANNELS) {
            await redisSubscriber.subscribe(channel, (message) => {
                try {
                    const data = JSON.parse(message);
                    console.log('Received broadcast on', channel, ':', data);
                    
                    let targetChannel = 'distributions';
                    if (data.distribution_id) {
                        targetChannel = 'distribution.' + data.distribution_id;
                    }
                    
                    const payload = JSON.stringify({
                        event: '.distribution.progress',
                        channel: 'private-' + targetChannel,
                        data: data
                    });
                    
                    const channelClients = clients.get(targetChannel);
                    if (channelClients) {
                        channelClients.forEach((client) => {
                            if (client.readyState === 1) {
                                client.send(payload);
                            }
                        });
                    }
                    
                    clients.forEach((clientSet, channelName) => {
                        if (channelName === 'distributions' || channelName.startsWith('distribution.')) {
                            clientSet.forEach((client) => {
                                if (client.readyState === 1) {
                                    client.send(payload);
                                }
                            });
                        }
                    });
                } catch (e) {
                    console.error('Error processing broadcast:', e);
                }
            });
            console.log(`Subscribed to Redis channel: ${channel}`);
        }
        
        console.log(`WebSocket server running on port ${PORT}`);
    } catch (error) {
        console.error('Failed to start:', error);
        process.exit(1);
    }
}

start();
