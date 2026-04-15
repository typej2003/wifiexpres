// Comenta los logs de las consultas normales (WAIT)
// Solo deja logs para comandos reales o errores.
app.get('/check-task', (req, res) => {
    const mac = req.query.mac?.toUpperCase();
    if (!mac) return res.send("WAIT");

    routersEnLinea[mac] = { 
        lastSeen: Date.now(), 
        identity: req.query.identity || "Sin nombre",
        ip: req.ip.replace('::ffff:', '') 
    };

    const cola = colasPorRouter[mac];
    if (cola && cola.length > 0) {
        const item = cola.shift();
        comandosEnTransito[item.tid] = { mac, cmd: item.cmd, ts: Date.now(), tid: item.tid };
        log(`🚀 ENVIANDO COMANDO: ${mac} - ${item.tid}`); // Solo log de acción
        res.send(item.cmd);
    } else {
        res.send("WAIT"); // Sin log para no saturar
    }
});