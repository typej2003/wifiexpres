const express = require('express');
const app = express();

// Configuración de middlewares para capturar datos planos y JSON
app.use(express.text({ type: '*/*', limit: '10mb' }));
app.use(express.json());

// --- ESTADO GLOBAL ---
let colasPorRouter = {};      
let comandosEnTransito = {};  
let buzonResultados = {};    
let routersEnLinea = {};      
let comandosLargos = {}; 

const log = (msg) => console.log(`[${new Date().toLocaleTimeString()}] ${msg}`);

// --- LIMPIADOR INTEGRAL (Cada 5 segundos) ---
setInterval(() => {
    const ahora = Date.now();

    // 1. Limpieza de Comandos en Tránsito (TIMEOUT)
    Object.keys(comandosEnTransito).forEach(tid => {
        const item = comandosEnTransito[tid];
        if (ahora - item.timestampInicio > 55000) {
            delete comandosEnTransito[tid];
            return;
        }
        // Re-encolar si no hubo respuesta en 10s
        if (ahora - item.timestampUltimoEnvio > 10000) {
            item.timestampUltimoEnvio = ahora;
            if (!colasPorRouter[item.mac]) colasPorRouter[item.mac] = [];
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

    // 2. Limpieza de Buzón de Resultados (5 min)
    Object.keys(buzonResultados).forEach(llave => {
        if (ahora - buzonResultados[llave].timestamp > 300000) delete buzonResultados[llave];
    });

    // 3. Limpieza de Routers por inactividad (2 minutos)
    Object.keys(routersEnLinea).forEach(mac => {
        if (ahora - routersEnLinea[mac].lastSeen > 120000) {
            log(`💀 OFFLINE: Eliminando [${mac}] por inactividad.`);
            delete routersEnLinea[mac];
            delete colasPorRouter[mac];
            delete comandosLargos[mac];
        }
    });
}, 5000);

// --- ENDPOINTS ---

app.post('/set-command', (req, res) => {
    const mac = req.headers['x-mac']?.toUpperCase();
    const tid = req.headers['x-id'];
    if (!mac || !tid) return res.status(400).send("Faltan Headers");

    if (!colasPorRouter[mac]) colasPorRouter[mac] = [];
    colasPorRouter[mac].push({ tid, cmd: req.body, timestampInicio: Date.now() });
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
            mac: macKey, cmd: item.cmd, tid: item.tid,
            timestampInicio: item.timestampInicio, timestampUltimoEnvio: Date.now()
        };

        if (item.cmd.length > 2500) {
            comandosLargos[macKey] = item.cmd;
            res.send("FILE:task.txt");
        } else {
            res.send(item.cmd);
        }
    } else {
        res.send("WAIT");
    }
});

app.get('/get-long-task', (req, res) => {
    const mac = req.query.mac?.toUpperCase();
    if (mac && comandosLargos[mac]) {
        const cmd = comandosLargos[mac];
        delete comandosLargos[mac];
        res.send(cmd);
    } else {
        res.status(404).send("NOT_FOUND");
    }
});

/**
 * ENDPOINT CRÍTICO: Recibe los resultados de MikroTik
 * Corregido para detectar mac/tid en Query y Body simultáneamente
 */
app.all('/post-result', (req, res) => {
    // Intenta obtener MAC y TID de la URL o del cuerpo (en caso de que venga como JSON/Form)
    const mac = (req.query.mac || req.body?.mac)?.toUpperCase();
    const tid = req.query.tid || req.body?.tid;
    
    // Captura el contenido: Si el body es texto plano, lo usa; si no, busca en query.data
    const data = (typeof req.body === 'string' && req.body.length > 0) ? req.body : req.query.data;
    
    if (mac && tid && data) {
        delete comandosEnTransito[tid];
        buzonResultados[`${mac}_${tid}`] = { data, timestamp: Date.now() };
        log(`✅ RESULTADO OK: MAC:${mac} | TID:${tid} | Len:${data.length}`);
        res.send("OK");
    } else { 
        // Log detallado para diagnosticar qué falta exactamente
        log(`⚠️ RESULTADO INCOMPLETO: MAC:${mac || 'FALTA'}, TID:${tid || 'FALTA'}, DATA:${data ? 'PRESENTE' : 'FALTA'}`);
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
        const lista = Object.keys(routersEnLinea).map(mac => {
            const macKey = mac.toUpperCase();
            
            // Filtramos comandos en tránsito que pertenecen a este router
            const transito = Object.values(comandosEnTransito)
                .filter(i => i.mac === macKey)
                .map(i => ({
                    tid: i.tid,
                    cmd: i.cmd,
                    age: Math.round((ahora - i.timestampInicio) / 1000) + 's'
                }));

            // Filtramos resultados recientes para este router
            const resultados = Object.keys(buzonResultados)
                .filter(key => key.startsWith(macKey))
                .map(key => ({
                    tid: key.split('_')[1],
                    data: buzonResultados[key].data,
                    timestamp: buzonResultados[key].timestamp
                }));

            return {
                mac: macKey,
                identity: routersEnLinea[macKey].identity,
                ip: routersEnLinea[macKey].ip,
                lastSeen: Math.round((ahora - routersEnLinea[macKey].lastSeen) / 1000) + 's ago',
                // Enviamos los arrays detallados que el HTML espera
                comandosDetalle: colasPorRouter[macKey] || [],
                transitoDetalle: transito,
                resultadosDetalle: resultados
            };
        });
        res.json(lista);
    } catch (e) { 
        log(`❌ Error Auditoria: ${e.message}`);
        res.status(500).json([]); 
    }
});

app.listen(3000, '0.0.0.0', () => log(`🚀 BRIDGE v3.6 (HYBRID-PARSE) ONLINE`));