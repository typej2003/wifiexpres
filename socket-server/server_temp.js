const express = require('express');
const app = express();

app.use(express.text({ type: '*/*', limit: '10mb' }));
app.use(express.json());

// --- ESTADO GLOBAL ---
let colasPorRouter = {};      // { 'MAC': [ {tid, cmd, timestampInicio}, ... ] }
let comandosEnTransito = {};  // { 'TID': { mac, cmd, tid, timestampInicio, timestampUltimoEnvio } }
let buzonResultados = {};     // { 'MAC_TID': 'resultado' }
let routersEnLinea = {};      // Monitoreo visual

const log = (msg) => console.log(`[${new Date().toLocaleTimeString()}] ${msg}`);

// --- LIMPIADOR Y REINTENTO (Lógica de 10s y 55s) ---
setInterval(() => {
    const ahora = Date.now();
    Object.keys(comandosEnTransito).forEach(tid => {
        const item = comandosEnTransito[tid];
        
        // 1. LIMPIEZA TOTAL (55s): Si Laravel ya no escucha, borramos para no saturar al MK
        if (ahora - item.timestampInicio > 55000) {
            log(`🗑️ EXPIRADO (TIMEOUT): [${item.mac}] TID: ${tid}. Limpiando cola.`);
            delete comandosEnTransito[tid];
            return;
        }

        // 2. REINTENTO (10s): Si no ha respondido, reencolar para que el MK lo vuelva a intentar
        if (ahora - item.timestampUltimoEnvio > 10000) { 
            log(`⚠️ REINTENTO (10s): [${item.mac}] TID: ${tid}. Reencolando...`);
            item.timestampUltimoEnvio = ahora; // Resetear reloj de reintento
            
            if (!colasPorRouter[item.mac]) colasPorRouter[item.mac] = [];
            
            // Devolvemos a la cola principal con el timestamp de inicio original
            colasPorRouter[item.mac].unshift({ 
                tid: item.tid, 
                cmd: item.cmd, 
                timestampInicio: item.timestampInicio 
            });
            delete comandosEnTransito[tid];
        }
    });
}, 5000); // Revisión cada 5 segundos

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
    
    // Registro de presencia
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
        buzonResultados[`${mac}_${tid}`] = data;
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
        res.json({ status: 'ready', data: r });
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
                    data: String(buzonResultados[key]).substring(0, 50)
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

app.listen(3000, '0.0.0.0', () => log(`🚀 BRIDGE v2.7 (AUTO-CLEAN) ONLINE`));