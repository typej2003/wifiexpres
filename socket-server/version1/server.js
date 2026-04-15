const express = require('express');
const app = express();

app.use(express.text({ type: '*/*', limit: '10mb' }));
app.use(express.json());

let colasPorRouter = {};      
let comandosEnTransito = {};  
let buzonResultados = {};    
let routersEnLinea = {};      

const log = (msg) => console.log(`[${new Date().toLocaleTimeString()}] ${msg}`);

// --- CONFIGURACIÓN DE TIEMPOS ---
const TIMEOUT_TRANSITO = 25000; // 25 segundos para que el MikroTik responda
const EXPIRACION_RESULTADOS = 120000; // 2 minutos
const EXPIRACION_ROUTERS = 60000; // 1 minuto

// LIMPIADOR CADA 5 SEGUNDOS (Más frecuente para mayor precisión)
setInterval(() => {
    const ahora = Date.now();

    // 1. Limpiar resultados viejos
    Object.keys(buzonResultados).forEach(key => {
        if (ahora - buzonResultados[key].timestamp > EXPIRACION_RESULTADOS) delete buzonResultados[key];
    });

    // 2. Limpiar routers offline
    Object.keys(routersEnLinea).forEach(mac => {
        if (ahora - routersEnLinea[mac].lastSeen > EXPIRACION_ROUTERS) delete routersEnLinea[mac];
    });

    // 3. NUEVO: Limpiador de Comandos en Tránsito (Zombie Commands)
    Object.keys(comandosEnTransito).forEach(tid => {
        const item = comandosEnTransito[tid];
        if (ahora - item.ts > TIMEOUT_TRANSITO) {
            log(`⚠️ TIMEOUT: Comando ${tid} de ${item.mac} expiró en tránsito (${Math.round((ahora - item.ts)/1000)}s)`);
            
            // Opcional: Notificar a Laravel que esto fue un error de conexión
            const llaveResult = `${item.mac}_${tid}`;
            buzonResultados[llaveResult] = { data: "TIMEOUT_ERROR", timestamp: ahora };
            
            delete comandosEnTransito[tid];
        }
    });
}, 5000);

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

    routersEnLinea[mac] = { 
        lastSeen: Date.now(), 
        identity: req.query.identity || "Sin nombre",
        ip: req.ip.replace('::ffff:', '') 
    };

    if (colasPorRouter[mac] && colasPorRouter[mac].length > 0) {
        const item = colasPorRouter[mac].shift();
        // Al pasar a tránsito, guardamos el TID y la MAC
        comandosEnTransito[item.tid] = { mac, cmd: item.cmd, ts: Date.now(), tid: item.tid };
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
        // Si el resultado llega, lo sacamos de tránsito inmediatamente
        if (comandosEnTransito[tid]) delete comandosEnTransito[tid];
        
        buzonResultados[`${mac}_${tid}`] = { data, timestamp: Date.now() };
        log(`✅ OK: ${mac} - ${tid}`);
        res.send("OK");
    } else {
        res.send("ERROR");
    }
});

app.get('/api/check-task-result', (req, res) => {
    const mac = req.query.mac?.toUpperCase();
    const tid = req.query.tid;
    const llave = `${mac}_${tid}`;

    if (buzonResultados[llave]) {
        const data = buzonResultados[llave].data;
        delete buzonResultados[llave];
        res.json({ status: 'ready', data });
    } else {
        // Si no está en resultados Y tampoco está en tránsito ni en cola, significa que se perdió
        const enCola = (colasPorRouter[mac] || []).some(c => c.tid === tid);
        const enTransito = comandosEnTransito[tid];

        if (!enCola && !enTransito) {
            res.json({ status: 'ready', data: 'LOST_COMMAND' });
        } else {
            res.json({ status: 'waiting' });
        }
    }
});

app.get('/api/routers-online', (req, res) => {
    try {
        const ahora = Date.now();
        const lista = Object.keys(routersEnLinea).map(macKey => {
            const r = routersEnLinea[macKey];
            return {
                mac: macKey,
                identity: r.identity,
                ip: r.ip,
                lastSeen: Math.round((ahora - r.lastSeen) / 1000) + 's ago',
                queueSize: (colasPorRouter[macKey] || []).length,
                transitSize: Object.values(comandosEnTransito).filter(i => i.mac === macKey).length,
                comandosDetalle: (colasPorRouter[macKey] || []).map(c => c.cmd),
                transitoDetalle: Object.values(comandosEnTransito)
                    .filter(i => i.mac === macKey)
                    .map(i => ({ 
                        tid: i.tid, 
                        cmd: i.cmd, 
                        age: Math.round((ahora - i.ts) / 1000) + 's' 
                    }))
            };
        });
        res.json(lista);
    } catch (e) { 
        log(`Error: ${e.message}`);
        res.status(500).json([]); 
    }
});

app.listen(3000, '0.0.0.0', () => log(`🚀 BRIDGE v3.9 ONLINE (Auto-Cleanup Active)`));