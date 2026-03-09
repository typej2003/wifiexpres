const express = require('express');
const app = express();

app.use(express.text({ type: '*/*', limit: '10mb' }));
app.use(express.json());

// --- ESTADO GLOBAL ---
let colasPorRouter = {};      
let comandosEnTransito = {};  
let buzonResultados = {};     
let routersEnLinea = {};      

const log = (msg) => console.log(`[${new Date().toLocaleTimeString()}] ${msg}`);

// --- LIMPIADOR INTEGRAL (Cada 5 segundos) ---
setInterval(() => {
    const ahora = Date.now();

    // 1. Limpieza de Comandos en Tránsito (TIMEOUT)
    Object.keys(comandosEnTransito).forEach(tid => {
        const item = comandosEnTransito[tid];
        if (ahora - item.timestampInicio > 55000) {
            log(`🗑️ EXPIRADO (TIMEOUT): [${item.mac}] TID: ${tid}.`);
            delete comandosEnTransito[tid];
            return;
        }

        if (ahora - item.timestampUltimoEnvio > 10000) { 
            log(`⚠️ REINTENTO (10s): [${item.mac}] TID: ${tid}. Reencolando...`);
            item.timestampUltimoEnvio = ahora;
            if (!colasPorRouter[item.mac]) colasPorRouter[item.mac] = [];
            
            // Verificamos que los datos existan antes de reencolar
            if(item.cmd) {
                colasPorRouter[item.mac].unshift({ 
                    tid: item.tid, 
                    cmd: item.cmd, 
                    timestampInicio: item.timestampInicio 
                });
            }
            delete comandosEnTransito[tid];
        }
    });

    // 2. Limpieza de Buzón de Resultados
    Object.keys(buzonResultados).forEach(llave => {
        if (ahora - buzonResultados[llave].timestamp > 300000) { 
            log(`🧹 LIMPIEZA BUZÓN: Borrando resultado huérfano ${llave}`);
            delete buzonResultados[llave];
        }
    });

    // 3. NUEVO: Limpieza de Routers Offline y sus Colas (Mantenimiento de RAM)
    Object.keys(routersEnLinea).forEach(mac => {
        // Si el router no ha hecho "check-task" en 2 minutos, lo consideramos muerto
        if (ahora - routersEnLinea[mac].lastSeen > 120000) {
            log(`💀 OFFLINE CRÍTICO: Limpiando rastro de [${mac}]`);
            delete routersEnLinea[mac];
            delete colasPorRouter[mac]; // <-- IMPORTANTE: Borra la cola acumulada
            
            // Borramos también sus comandos en tránsito específicos
            Object.keys(comandosEnTransito).forEach(tid => {
                if (comandosEnTransito[tid].mac === mac) delete comandosEnTransito[tid];
            });
        }
    });

}, 5000);

// --- ENDPOINTS ---

app.post('/set-command', (req, res) => {
    const mac = req.headers['x-mac']?.toUpperCase();
    const tid = req.headers['x-id']; 
    if (!mac || !tid) return res.status(400).send("Faltan Headers");

    if (!colasPorRouter[mac]) colasPorRouter[mac] = [];
    colasPorRouter[mac].push({ 
        tid: tid, 
        cmd: req.body, 
        timestampInicio: Date.now() 
    });
    
    log(`📥 NUEVO COMANDO [${tid}] PARA [${mac}].`);
    res.send("OK");
});

app.get('/check-task', (req, res) => {
    const { mac, identity } = req.query;
    if (!mac) return res.send("WAIT");
    const macKey = mac.toUpperCase();
    
    // REGISTRO DE ACTIVIDAD (Heartbeat)
    routersEnLinea[macKey] = { 
        identity, 
        lastSeen: Date.now(), 
        ip: req.ip.replace('::ffff:', '') 
    };

    if (colasPorRouter[macKey] && colasPorRouter[macKey].length > 0) {
        const item = colasPorRouter[macKey].shift();
        
        comandosEnTransito[item.tid] = {
            mac: macKey,
            cmd: item.cmd,
            tid: item.tid,
            timestampInicio: item.timestampInicio,
            timestampUltimoEnvio: Date.now()
        };

        log(`📡 ENTREGANDO A ${identity} TID: ${item.tid}`);
        res.send(item.cmd);
    } else {
        res.send("WAIT");
    }
});

app.all('/post-result', (req, res) => {
    const mac = req.query.mac?.toUpperCase();
    const tid = req.query.tid;
    const data = (typeof req.body === 'string' && req.body.length > 0) ? req.body : req.query.data;

    if (mac && tid && data) {
        log(`📩 RESULTADO [${mac}] TID: ${tid}`);
        delete comandosEnTransito[tid];
        buzonResultados[`${mac}_${tid}`] = {
            data: data,
            timestamp: Date.now()
        };
        res.send("OK");
    } else {
        res.send("ERROR");
    }
});

app.get('/api/check-task-result', (req, res) => {
    const { mac, tid } = req.query;
    const llave = `${mac?.toUpperCase()}_${tid}`;
    const r = buzonResultados[llave];

    if (r) {
        delete buzonResultados[llave]; 
        res.json({ status: 'ready', data: r.data });
    } else {
        res.json({ status: 'waiting' });
    }
});

app.get('/api/routers-online', (req, res) => {
    try {
        const ahora = Date.now();
        const MARGEN_ONLINE = 45000; // 45 segundos

        const lista = Object.keys(routersEnLinea)
            .filter(mac => (ahora - routersEnLinea[mac].lastSeen) < MARGEN_ONLINE)
            .map(mac => {
                const macKey = mac.toUpperCase();
                const comandos = (colasPorRouter[macKey] || []);
                const enTransito = Object.values(comandosEnTransito).filter(i => i.mac === macKey);

                return {
                    mac: macKey,
                    identity: routersEnLinea[macKey].identity,
                    ip: routersEnLinea[macKey].ip,
                    lastSeen: Math.round((ahora - routersEnLinea[macKey].lastSeen) / 1000) + 's ago',
                    queueSize: comandos.length,
                    transitSize: enTransito.length,
                    comandosDetalle: comandos.map(c => c.cmd),
                    transitoDetalle: enTransito.map(i => ({ tid: i.tid, age: Math.round((ahora - i.timestampInicio) / 1000) + 's' }))
                };
            });
        res.json(lista);
    } catch (e) {
        res.status(500).json([]);
    }
});

app.listen(3000, '0.0.0.0', () => log(`🚀 BRIDGE v2.9 (COLAS LIMPIAS) ONLINE`));