const express = require('express');
const app = express();

app.use(express.text({ type: '*/*', limit: '10mb' }));
app.use(express.json());

// --- ESTADO GLOBAL ---
let colasPorRouter = {};      
let comandosEnTransito = {};  
let buzonResultados = {};     // AHORA: { 'MAC_TID': { data, timestamp } }
let routersEnLinea = {};      

const log = (msg) => console.log(`[${new Date().toLocaleTimeString()}] ${msg}`);

// --- LIMPIADOR Y REINTENTO ---
setInterval(() => {
    const ahora = Date.now();

    // 1. Limpieza de Comandos en Tránsito (Lógica existente)
    Object.keys(comandosEnTransito).forEach(tid => {
        const item = comandosEnTransito[tid];
        if (ahora - item.timestampInicio > 55000) {
            log(`🗑️ EXPIRADO (TIMEOUT): [${item.mac}] TID: ${tid}. Limpiando cola.`);
            delete comandosEnTransito[tid];
            return;
        }

        if (ahora - item.timestampUltimoEnvio > 10000) { 
            log(`⚠️ REINTENTO (10s): [${item.mac}] TID: ${tid}. Reencolando...`);
            item.timestampUltimoEnvio = ahora;
            if (!colasPorRouter[item.mac]) colasPorRouter[item.mac] = [];
            colasPorRouter[item.mac].unshift({ 
                tid: item.tid, 
                cmd: item.cmd, 
                timestampInicio: item.timestampInicio 
            });
            delete comandosEnTransito[tid];
        }
    });

    // 2. MEJORA: Limpieza de Buzón de Resultados (Max 5 minutos de vida)
    Object.keys(buzonResultados).forEach(llave => {
        if (ahora - buzonResultados[llave].timestamp > 300000) { // 5 minutos
            log(`🧹 LIMPIEZA BUZÓN: Borrando resultado huérfano ${llave}`);
            delete buzonResultados[llave];
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
    
    routersEnLinea[macKey] = { identity, lastSeen: Date.now(), ip: req.ip.replace('::ffff:', '') };

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
        // MEJORA: Guardamos con timestamp
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
        res.json({ status: 'ready', data: r.data }); // Extraemos .data
    } else {
        res.json({ status: 'waiting' });
    }
});

app.get('/api/routers-online', (req, res) => {
    try {
        const lista = Object.keys(routersEnLinea).map(mac => {
            const macKey = mac.toUpperCase();
            const comandos = (colasPorRouter[macKey] || []).map(c => c.cmd);
            const enTransito = Object.values(comandosEnTransito)
                .filter(item => item.mac === macKey)
                .map(item => ({
                    tid: item.tid,
                    cmd: item.cmd,
                    age: Math.round((Date.now() - item.timestampInicio) / 1000) + 's'
                }));

            const resultados = Object.keys(buzonResultados)
                .filter(key => key.startsWith(macKey + "_"))
                .map(key => ({
                    tid: key.split('_')[1],
                    data: String(buzonResultados[key].data).substring(0, 50) // Extraemos .data
                }));

            return {
                mac: macKey,
                identity: routersEnLinea[macKey].identity,
                ip: routersEnLinea[macKey].ip,
                queueSize: comandos.length,
                transitSize: enTransito.length,
                comandosDetalle: comandos,
                transitoDetalle: enTransito,
                resultadosDetalle: resultados
            };
        });
        res.json(lista);
    } catch (e) {
        res.status(500).json([]);
    }
});

app.listen(3000, '0.0.0.0', () => log(`🚀 BRIDGE v2.8 (CLEAN BUZON) ONLINE`));