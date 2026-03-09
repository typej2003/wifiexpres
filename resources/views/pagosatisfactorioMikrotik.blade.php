<link rel="stylesheet" href="/css/bootstrap.min.css">
<link rel="stylesheet" href="/css/app.css">
<div class="container-fluid">
    <div>
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card" style="width: 100% !important;">
                            <div class="card-body text-center">
                                <h3>WifiExprés</h3>
                                <input type="hidden" id="user" name="user" value="{{ $user }}">
                                <h4>Operación procesada con éxito</h4>
                                <p>
                                  Inicio de sesión en <span class="h4 text-danger" id="contador">2 segundos</span>
                                </p>
                            </div>
                            <div class="card-footer d-flex justify-content-end">
                                <button class="d-none" onclick="enviarDatoAlPadre()">Enviar Variable al Padre</button>
                                
                                <script>
                                function enviarDatoAlPadre() {
                                    const miObjeto = {
                                        status: 'pago_exitoso',
                                        user: document.getElementById('user').value,
                                    };
                                    // Envía el estatus al portal principal para iniciar la activación
                                    window.parent.postMessage(miObjeto, '*');
                                }
                                                                
                                // Ajustado a 2 segundos
                                var tiempoInicial = 2;
                                document.getElementById('contador').textContent = tiempoInicial + " segundos";
                                cuentaRegresiva(tiempoInicial);
                                
                                function cuentaRegresiva(segundos){                                    
                                    const idIntervalo = setInterval(() => {
                                        segundos--; 
                                        
                                        if (segundos <= 0) {
                                            document.getElementById('contador').textContent = "0 segundos";
                                            clearInterval(idIntervalo);
                                            console.log("¡Cuenta regresiva terminada!");
                                        } else {
                                            document.getElementById('contador').textContent = segundos + " segundos";
                                        }
                                    }, 1000);                                
                                }

                                // El mensaje se envía al padre tras 2 segundos exactos
                                const timeoutId = setTimeout(() => {
                                    enviarDatoAlPadre();
                                    clearTimeout(timeoutId);
                                }, 2000);
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
        </div>
</div>

<script src="/js/app.js"></script>
<script src="/js/backend.js"></script>

@stack('js')
@stack('before-livewire-scripts')
<livewire:scripts />
@stack('after-livewire-scripts')

@stack('alpine-plugins')
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@push('js')
@endpush

<SCRIPT LANGUAGE="JavaScript">
// history.forward()
</SCRIPT>