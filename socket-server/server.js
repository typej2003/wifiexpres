const express = require('express');
const app = express();

app.use(express.text({ type: '*/*', limit: '10mb' }));
app.use(express.json());

let colasPorRouter = {};      
let comandosEnTransito = {};  
let buzonResultados = {};    
let routersEnLinea = {};      

const log = (msg) => console.log(`[${new Date().toLocaleTimeString()}] ${msg}`);

// LIMPIADOR CADA 10 SEGUNDOS
setInterval(() => {
    const ahora = Date.now();
    // Limpiar resultados viejos (2 minutos)
    Object.keys(buzonResultados).forEach(key => {
        if (ahora - buzonResultados[key].timestamp > 120000) delete buzonResultados[key];
    });
    // Limpiar routers offline
    Object.keys(routersEnLinea).forEach(mac => {
        if (ahora - routersEnLinea[mac].lastSeen > 60000) delete routersEnLinea[mac];
    });
}, 10000);

app.post('/set-command', (req, res) => {
    const mac = req.headers['x-mac']?.toUpperCase();
    const tid = req.headers['x-id'];
    if (!mac || !tid) return res.status(400).send("MISSING_HEADERS");

    if (!colasPorRouter[mac]) colasPorRouter[mac] = [];
    colasPorRouter[mac].push({ tid, cmd: req.body, ts: Date.now() });
    res.send("OK");
});

app.get('/check-task', (req, res) => {
    const mac = req.query.mac?.toUpperCase();
    if (!mac) return res.send("WAIT");

    routersEnLinea[mac] = { lastSeen: Date.now(), identity: req.query.identity };

    if (colasPorRouter[mac] && colasPorRouter[mac].length > 0) {
        const item = colasPorRouter[mac].shift();
        comandosEnTransito[item.tid] = { mac, cmd: item.cmd, ts: Date.now() };
        res.send(item.cmd);
    } else {
        res.send("WAIT");
    }
});

app.all('/post-result', (req, res) => {
    const mac = (req.query.mac || req.body?.mac)?.toUpperCase();
    const tid = req.query.tid || req.body?.tid;
    const data = (typeof req.body === 'string' && req.body.length > 0) ? req.body : (req.query.data || "OK");

    if (mac && tid) {
        delete comandosEnTransito[tid];
        buzonResultados[`${mac}_${tid}`] = { data, timestamp: Date.now() };
        log(`✅ OK: ${mac} - ${tid}`);
        res.send("OK");
    } else {
        res.send("ERROR");
    }
});

app.get('/api/check-task-result', (req, res) => {
    const llave = `${req.query.mac?.toUpperCase()}_${req.query.tid}`;
    if (buzonResultados[llave]) {
        const data = buzonResultados[llave].data;
        delete buzonResultados[llave];
        res.json({ status: 'ready', data });
    } else {
        res.json({ status: 'waiting' });
    }
});

app.listen(3000, '0.0.0.0', () => log(`🚀 BRIDGE v3.7 ONLINE`));