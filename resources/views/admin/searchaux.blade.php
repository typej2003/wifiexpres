<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" integrity="sha384-WJUUqfoMmnfkBLne5uxXj+na/c7sesSJ32gI7GfCk4zO4GthUKhSEGyvQ839BC51" crossorigin="anonymous">
<script src="https://kit.fontawesome.com/03cf5139f1.js"></script>
<script src="/js/reportarPagomovil.js"></script>
<link rel="stylesheet" href="/css/modopago.css" class="rel">
<script>
    var datosCliente = {
        'userId': 0,
        'codigoFactura': '',
        'monto': 0,
    }
    var transaccion = {
        'modopago': '',
        'referencia': '',
        'cedula': '',
        'telefono': '',
        'banco': '',
        'codigo': '',
        'fechaPago': '',
        'fecha': '',
        'monto': '',
    }
    var modoPago =  [
        {
            'modo': 'efectivo', 
            'nombre': 'Efectivo'
        },
        {
            'modo': 'transferencia', 
            'nombre': 'Transferencia'
        },
        {
            'modo': 'puntodeventa', 
            'nombre': 'Punto de Venta'
        },
        {
            'modo': 'biopago', 
            'nombre': 'Biopago'
        },
        {
            'modo': 'pagomovil', 
            'nombre': 'Pago Movil'
        },
        {
            'modo': 'divisa', 
            'nombre': 'Divisa'
        },
        {
            'modo': 'zelle', 
            'nombre': 'Zelle'
        }
    ]
    
</script>
<title>Diseño y Soluciones</title>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if(!$existe )
                <span class="h4 bg-red">No esta registrado, <span ><a style="color: red; text-decoration:none;" href="/register">¿Desea crear una cuenta de Afiliación?</a></span></span>
            @else
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <p class="fw-bold"><span>Comercio: </span><span>{{$comercio->name}}</span></p>
                            <p class="fw-bold"><span>Propietario: </span><span>{{$comercio->getPropietario()->name}}</span></p>
                            <p class="fw-bold"><span>Teléfono: </span><span>{{$comercio->name}}</span></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="grupocedula">Cédula</label>                            
                            <div id="grupocedula" class="input-group">
                                <input wire:model.defer="forma.cedula" type="text" autofocus class="cedula form-control col-lg-2 @error('cedula') is-invalid @enderror" id="cedula" aria-describedby="cedulaHelp" placeholder="Cédula" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" value="{{ old('cedula') }}">
                                <span class="input-group-btn">
                                    <button id="show_password" class="btn btn-success h-100 mx-2" type="button" onclick="mostrarPassword()"> 
                                        <i class="fas fa-search"></i>
                                    </button>
                                </span>
                            </div>
                            @error('cedula')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>  
                        <script>
                            
                        </script>                                  
                    </div>
                </div>
                <div class="row my-2">
                    <div class="col-lg-4">
                        <div class="card">
                            <p class="fw-bold"><span>Cédula: </span><span>{{$comercio->name}}</span></p>
                            <p class="fw-bold"><span>Nombre: </span><span>{{$comercio->getPropietario()->name}}</span></p>
                            <p class="fw-bold"><span>Teléfono: </span><span>{{$comercio->name}}</span></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- ZONA DEL PEDIDO Y PAGO -->
                    <div class="col-lg-4">
                        <div class="row">
                            <div class="col-lg-12 text-center">
                                <div class="card">
                                    <h4 class="text-center my-3">TU PEDIDO</h4>
                                    <div class="card-body">
                                        <table class="table" borde='0'>
                                            <thead>
                                                <tr>
                                                <th class="text-left" scope="col" style="width: 80%;">Producto</th>
                                                <th class="text-center" scope="col" style="width: 20%;">SubTotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="descripcion">
                                                    <td class="text-left" scope="row">Set de Coctelera Europea 500ML 
                                                    × 1</td>
                                                    <td class="text-right"><span>30.00</span><span id="moneda">$</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-left">Subtotal</td>
                                                    <td class="text-right">30.00 $</td>
                                                </tr>
                                                <tr id="envio">
                                                    <td colspan="2">
                                                        <div class="row">
                                                            <div class="col-md-2 text-left">Envío</div>
                                                            <div class="col-md-10 text-justify">Introduce tu dirección para ver las opciones de envío.</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-left">Total</td>
                                                    <td class="text-right">30.00 $</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>                                
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                    <h4 class="text-center my-3">MÉTODOS DE PAGOS</h4>
                        <div class="row" style="heigth:300px;"> 
                            <div class="col-md-12">
                                <label for="metodo">Seleccione su metodo de pago</label>
                                <select class="form-control input-lg" id="exampleFormControlSelect1" onChange="imageChanged()">
                                    <option meta-img="/img/dedo.png" value="0">Seleccione..</option>
                                    <option  value="tarjeta" meta-img="/img/visa-mastercard.png">
                                        Tarjetas
                                    </option>
                                    <option value = "transferencia" meta-img="/img/transferencia.png">
                                        Transferencia
                                    </option>
                                    <option value="pagomovil" meta-img="/img/pagomovil.png">
                                        Pago Movil
                                    </option>
                                    <option value="zelle"  meta-img="/img/zelle.png">
                                        Zelle
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row my-2">
                            <div class="col-lg-4">
                                    <div id="imageSelected"></div>
                            </div>
                        </div>
                        <hr>
                        <script>
                            function imageChanged()
                            {
                                let selector = document.querySelector("#exampleFormControlSelect1");
                                let divImage =   document.querySelector("#imageSelected");
                                let selectedOption = selector.options[selector.selectedIndex];
                                let image = selectedOption.getAttribute("meta-img");
                                divImage.innerHTML = "<img src='"+image+"'>"
                            }
                            imageChanged()

                            document.getElementById('exampleFormControlSelect1').addEventListener('change', selectModo)
                            function selectModo(e) {
                                let index = e.target.selectedIndex;
                                transaccion.modopago = e.target.value
                                //transaccion.banco = e.target.options[index].text  
                                
                                switch (e.target.value) {
                                    case 'zelle':
                                        reportarZelle(datosCliente, transaccion)
                                        break;
                                
                                    default:
                                        reportarPagomovil(datosCliente, transaccion)
                                        break;
                                }
                            }
                        </script>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div id="principal">

                        </div>
                    </div>
                </div>
                
                
            @endif
        </div>
    </div>

</div>
