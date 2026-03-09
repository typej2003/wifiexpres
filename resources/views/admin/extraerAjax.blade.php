<div>
    <head>
        <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script> -->
    </head> 
    <style>
        .font-costo{
            font-size: 24px; font-weight: bold;
        }
        .font-total{
            font-size: x-large; font-weight: bold;   
        }
        .table{
            font-size: 24px;
        }
        .table th{
            font-weight: bold;
            background-color: #bb97c7;
            color: white;
        }
        .modal-lg { 
            max-width: 60% !important; 
        }

        .botonvisible{
            visibility: visible;
        }
        .botonnovisible{
            visibility: hidden;
        }

    </style>
    <style>
    .ui-autocomplete {
        max-height: 220px;
        overflow-y: auto;
        /* prevent horizontal scrollbar */
        overflow-x: hidden;
    }
    /* IE 6 doesn't support max-height
     * we use height instead, but this forces the menu to always be this tall
     */
    * html .ui-autocomplete {
        height: 220px;
    }
    </style>
    <style>
        /* Small devices (tablets, 768px and up) */
        @media (min-width: @screen-sm-min) {
            
        }

        /* Medium devices (desktops, 992px and up) */
        @media (min-width: @screen-md-min) {
        }

        /* Large devices (large desktops, 1200px and up) */
        @media (min-width: @screen-lg-min) {
        }
    </style>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-purple">Agregar Estudios</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <!-- <li class="breadcrumb-item"><a href="/admin/dashboard">Tablero</a></li> -->
                        <li class="breadcrumb-item active text-purple">Estudios</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <!-- Main content -->
    <div class="content">

        <div class="container-fluid">

            <form autocomplete="off" wire:submit.prevent="createEstudio">
                
            <div class="row">
                {{ csrf_field() }}
                <div class="col-lg-12">
                    <!-- Datos del Paciente -->
                    <div class="card" style="width: 100%;">
                        <div class="card-header">
                            <span class="p-2 bg-danger h5 rounded">Estudio <span class="h3 data">{{$correlacion}}</span></span>
                            <input type="hidden" class="bg-danger h5 rounded border-0" wire:model.defer="forma.correlacion" name="" readonly="">
                            <script>
                                let correlacion = "";
                                document.addEventListener('livewire:load', () => {
                                    @this.on('correlacion', () => {
                                        correlacion = @this.correlacion;
                                        $('.data').text(correlacion);
                                   })
                               })
                            </script>
                        </div>
                        <div class="card-body">
                            <div class="row"><!-- Cedula Nombre -->
                                <div class="col-sm-4 col-lg-4">
                                    <div class="form-group">
                                        <label for="tipo_id">Tipo</label>
                                        <br>
                                        <select class="campo-select @error('tipo_id') is-invalid @enderror"  id="tipo_id" name="tipo_id" wire:change="changeTipo($event.target.value)">
                                            <option value="0" data="">SELECCIONE</option>
                                            @foreach($tipos as $tipo)
                                                <option value="{{$tipo->id}}" datatipo="{{$tipo->name}}">{{$tipo->name}}</option>      
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-lg-4" >
                                    <div class="form-group">
                                        <label for="cedula">Cédula</label>
                                        <input wire:model.defer="forma.cedula" type="text" autofocus class="cedula form-control @error('cedula') is-invalid @enderror" id="cedula" aria-describedby="cedulaHelp" placeholder="Cédula" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" value="{{ old('cedula') }}">
                                        @error('cedula')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>  
                                    <script>
                                        $('#cedula').on('input', function () { 
                                            this.value = this.value.replace(/[^0-9]/g,'');
                                        });
                                    </script>                                  
                                    
                                </div>
                                <div class="col-sm-4 col-lg-4">
                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input wire:model.defer="forma.nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" aria-describedby="nombreHelp" placeholder="Nombre Completo" value="{{ old('nombre') }}">
                                        @error('nombre')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>  
                                </div>
                            </div>
                            <div class="row"><!-- Edad Sexo -->
                                <div class="col-sm-4 col-lg-4">
                                    <div class="form-group">
                                        <label for="edad">Edad</label>
                                        <input type="number" wire:model.defer="forma.edad" class="bg-white form-control @error('edad') is-invalid @enderror" id="edad" aria-describedby="edadHelp" min="0" value="{{ old('edad') }}" min="0" readonly>
                                        @error('edad')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-sm-4 col-lg-4">
                                    <div class="form-group">
                                        <label for="edad">Sexo</label><br>
                                        <div class="btn-group">
                                        <input wire:model.defer="forma.sexo" class="form-control mx-2" type="radio" name="sexo" value="femenino" {{$sexo=="femenino"?"checked":""}} id="sexoF"><span class="border-0 form-control" value="{{ old('sexo') }}">Femenino</span>
                                        <input wire:model.defer="forma.sexo" class="form-control mx-2" type="radio" name="sexo" value="masculino" value="{{ old('sexo') }}" {{$sexo=="femenino"?"checked":""}} id="sexoM"><span class="border-0 form-control">Masculino</span>
                                        </div>
                                        @error('sexo')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-sm-4 col-lg-4">
                                    <div class="form-group">
                                        <label for="prioridad">Prioridad</label>
                                        <br>
                                        <select class="campo-select prioridad" id="prioridad_id" name="prioridad_id" wire:model="prioridad_id">
                                            <option value="1">EMERGENCIA</option>
                                            <option value="2">URGENCIA</option>
                                            <option value="3">URGENCIA MENOR</option>
                                            <option value="4" selected>SIN URGENCIA</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row"><!-- procedencia -->
                                <div class="col-sm-4 col-lg-4">
                                    <div class="form-group">
                                        <label for="nombreM">Médico</label>
                                        <input wire:model.defer="forma.nombreM" type="text" class="typeahead form-control @error('nombreM') is-invalid @enderror" id="nombreM" name="nombreM" aria-describedby="nombreMHelp" placeholder="Nombre Completo" value="{{ old('nombreM') }}">
                                        @error('nombreM')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>  
                                </div>
                                <div class="col-sm-4 col-lg-5">
                                    <div class="form-group">
                                        <label for="proce">Procedencia</label>
                                        <input type="hidden" wire:model.defer="forma.tipoprocedencia_id" name="" id="proce_id">
                                        <input wire:model.defer="forma.nombreProcedencia" type="text" class="typeahead form-control @error('nombreProcedencia') is-invalid @enderror" id="nombreProcedencia" aria-describedby="cedulaHelp" placeholder="Nombre Procedencia" value="{{ old('nombreProcedencia') }}">
                                        @error('nombreProcedencia')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row" wire:ignore><!--  -->
                                <div class="col-md-4 tipoestudio">
                                    <div class="form-group ">
                                            <label for="tipoestudio_id">Tipo Estudio</label>
                                            <br>
                                            <select class="campo-select" id="tipoestudio_id" wire:change="changeTipoEstudio($event.target.value)">
                                            </select>
                                    </div>  
                                </div>
                                <div class="col-md-4 muestra">
                                    <div class="form-group ">
                                        <label for="muestra_id">Muestra</label>
                                        <br>
                                        <select class="campo-select" wire:change="changeMuestra($event.target.value)" id="muestra_id">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="muestra_id"></label>
                                        <br>
                                        <a wire:click="agregarDetalle()" class="boton form-control" id="agregarDetalle" style="display: none;">
                                            <i class="fas fa-solid fa-hand-holding-medical fa-2xl"></i>
                                            <span class="m-2">
                                                Agregar
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fin Datos del Paciente -->

                    <!-- Datos de las consultas -->
                    <div class="card w-100">
                        <div class="card-body w-100">
                            <div class="row" wire:ignore>
                                <div class="col-lg-12 d-flex">
                                </div>
                            </div>
                            <script>
                                const $select = document.querySelector("#tipo_id");
                                let lastSelectedIndex = $select.selectedIndex;
                                $select.addEventListener("click", function () {
                                    lastSelectedIndex = $select.selectedIndex;
                                });

                                const $selectEstudio = document.querySelector("#estudio_id");
                                
                                function ejecutar()
                                {

                                    Livewire.emit('cambioStatusReserva', @this.correlacion)
                                    @this.correlacion = ''
                                }

                                $('#tipo_id').change(function(){
                                    
                                    if($(this).val()!='0')
                                    //if(@this.correlacion != '')
                                    {
                                        ejecutar()
                                    }

                                    tabla = document.getElementById('tablaEstudio')

                                    $("#agregarDetalle").css('display','none')

                                    if(tabla.rows.length > 1){
                                        var confirmacion = confirm("Desea eliminar los estudios seleccionados");
                                        if(confirmacion){
                                            cancelRowInTable()

                                            @this.totalCosto = '0,00'
                                            @this.correlacion = ''
                                            $('#cobrar').css('display','none');
                                            location.reload();

                                        }
                                        else{
                                            $select.selectedIndex = lastSelectedIndex;
                                            return;
                                        }
                                    }
                                    else
                                    {
                                        var datatipo =   $('#tipo_id option:selected').attr('datatipo');

                                        if(datatipo == 'BIOPSIA'){
                                            $(".titulo").text('Muestra');
                                        }
                                        else
                                        {
                                            $(".titulo").text('Estudio');
                                        }

                                        switch(datatipo)
                                        {
                                            case 'BIOPSIA':
                                                $('.muestra').css('display','none');
                                                $("#botones").css('visibility','visible');
                                                $('.titulo').text('Muestra');
                                                break;
                                            case 'CITOLOGÍA':
                                                $('.muestra').css('display','none');
                                                $("#botones").css('visibility','visible');
                                                $('.titulo').text('Estudio')
                                                break;
                                            case 'INMUNOHISTOQUIMICA':
                                                $('.muestra').css('display','none');
                                                $("#botones").css('visibility','visible');
                                                $('.titulo').text('Estudio');
                                                break;
                                            default:
                                                $("#botones").css('visibility','hidden');
                                                break;
                                        }    

                                        //@this.listmuestras = "";
                                    }
                                    //ocultar estudio y muestra
                                    // if($(this).val()=="0")
                                    // {
                                    //    $('#agregarDetalle').css('display','none')
                                    // }

                                    
                                })
                            </script>

                            <script>
                                $("#muestra_id").on('change', function(){
                                    if ($(this).val() == "0")
                                    {
                                        $('#agregarDetalle').css('display','none')
                                        let tabla = document.getElementById('tablaEstudio')
                                        if(tabla.rows > 1)
                                        {
                                            $('#cobrar').css('display','none');    
                                        }
                                    }
                                    else
                                    {                                        
                                        $('#agregarDetalle').css('display','block')
                                        
                                        // Borrar para la fase 2
                                        // if(tabla.rows.length == 2)
                                        // {
                                        //     $('#agregarDetalle').css('display','none');    
                                        // }
                                        //
                                        if(tabla.rows > 1)
                                        {
                                            $('#cobrar').css('display','none');    
                                        }
                                        //$('#tablaEstudio tr:not(:first-child)').remove();
                                    }
                                })
                                $('.tipoestudio').css('display', 'none')
                                $('.muestra').css('display', 'none')
                                
                                $('.tipoestudio_id1').on('change', function(){
                                    if($(this).val()!='0')
                                    {
                                        $('.muestra').css('display', 'block')
                                    }
                                    else
                                    {
                                        $('.muestra').css('display', 'none')
                                    }
                                    //cancelRowInTable()
                                    //@this.listmuestras = "";
                                    //@this.totalCosto = '0,00'
                                })
                            </script>
                            
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-4" wire:ignore>
                                            <div class="form-group" wire:ignore>
                                                <a class="botonCobrar form-control cobrar" wire:click.prevent="cobrarEstudio" style="display: none;" id="cobrar">
                                                    <i class="fas fa-light fa-money-bill-wave">
                                                    </i><span class="m-2">Continuar</span>
                                                </a>  
                                            </div>
                                        </div>
                                        <div class="col-lg-4 text-center" style="display: none;">
                                            <div class="form-group">
                                                <a class="boton form-control" onclick="cancelRowInTable()"><i class="fas fa-solid fa-ban"></i><span class="m-2"><span class="m-2">Cancelar</span></a>    
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="display: none;">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <a class="boton" wire:click="mostrarDetalletabla">Mostrar DetalleTabla</a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <span class="h4">Total: </span><span wire:ignore.self class="h2 totalcosto">{{$totalCosto}}</span><span class="h2"> USD</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card" style="width: 100% !important;">
                                        <div class="card-body">
                                            <table class="table table-hover table-bordered table-responsive" id="tablaEstudio" wire:ignore>
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <!-- <th scope="col">
                                                            Descripción
                                                        </th> -->
                                                        <th scope="col"> <span class="text-white titulo">Muestra</span></th>
                                                        <th scope="col" class="deuda">Costo</th>
                                                        <th scope="col">Modo</th>
                                                        <th scope="col">Opciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody wire:loading.class="text-muted">
                                                    
                                                </tbody>
                                            </table>

                                            @push('js')
                                                <script>
                                                    var muestra_id = '';
                                                    
                                                    //Operaciones con tabla
                                                    function deleteFila(r)
                                                    {             
                                                        var i=r.parentNode.parentNode.rowIndex
                                                        //alert(i)
                                                        //Livewire.emit('enviarIndexRow', i)
                                                        Livewire.emit('deleteFila', i)
                                                        //document.getElementById('tablaEstudio').deleteRow(i)
                                                    }

                                                    window.addEventListener('emitDeleteFila', event => {
                                                        let fila = event.detail.fila
                                                        let totalcosto = event.detail.totalcosto
                                                        $('.totalcosto').html(totalcosto);

                                                        document.getElementById('tablaEstudio').deleteRow(fila) 

                                                        $('#agregarDetalle').css('display','none');
                                                        if(tabla.rows > 1)
                                                        {
                                                            $('#cobrar').css('display','none');    
                                                        }
                                                        $(".tipoestudio").css('display', 'block')
                                                        $("#tipoestudio_id").val('0')
                                                        $(".muestra").css('display', 'none')
                                                    });

                                                    function cancelRowInTable(){
                                                        $('#tablaEstudio tr:not(:first-child)').remove();
                                                        $('#agregarDetalle').css('display','none');
                                                        let tabla = document.getElementById('tablaEstudio')
                                                        if(tabla.rows > 1)
                                                        {
                                                            $('#cobrar').css('display','none');    
                                                        }
                                                        $('#cobrar').css('display','none');
                                                        $("#tipoestudio_id").val('0')
                                                    }
                                                    function cancelRowInTable2(){
                                                        $('#tablaEstudio tr:not(:first-child)').remove();
                                                        if(tabla.rows > 1)
                                                        {
                                                            $('#cobrar').css('display','none');    
                                                        }
                                                        $("#tipoestudio_id").val('0')
                                                    }
                                                    window.addEventListener('emitAgregarFila', event => {
                                                        let tipoestudio = event.detail.tipoestudio
                                                        let muestra = event.detail.muestra
                                                        let costo = event.detail.costo
                                                        let modo = event.detail.modo
                                                        let totalcosto = event.detail.totalcosto
                                                        let fila_id = event.detail.fila_id
                                                        let autorizado = event.detail.autorizado

                                                        // //Ocultar para la fase 2
                                                        $('#agregarDetalle').css('display','none');

                                                        var datatipo =   $('#tipo_id option:selected').attr('datatipo');

                                                        if(datatipo == 'BIOPSIA'){
                                                            $("#tipoestudio_id").val('0');
                                                        }
                                                        else
                                                        {
                                                            $('.tipoestudio').css('display', 'none');
                                                        }
                                                        
                                                        $(".muestra").css('display', 'none');
                                                        // //

                                                        $('.totalcosto').html(totalcosto);

                                                        var table = document.getElementById("tablaEstudio");
                                                        var newRow = table.insertRow(-1);
                                                        newRow.insertCell().innerHTML = newRow.rowIndex;
                                                        let fila=newRow.rowIndex
                                                        if(datatipo == 'BIOPSIA'){
                                                            newRow.insertCell().innerHTML = muestra
                                                            $('.tituloCelda').innerHTML = "Muestra"
                                                        }
                                                        else
                                                        {
                                                            $('.tituloCelda').innerHTML = "Estudio"
                                                            newRow.insertCell().innerHTML = tipoestudio
                                                        }
                                                        if(autorizado)
                                                        {
                                                            newRow.insertCell().innerHTML = '<span>'+costo+'</span>' + '<input class="mx-2 boton bg-warning botoncelda botonnovisible" style=" padding:0; font:6px; color:black; width:100px; height:35px;" data-id="'+fila_id+'" value="Cambiar" onclick="cambiarCosto(this)">'    
                                                        }
                                                        else
                                                        {
                                                            newRow.insertCell().innerHTML = '<span>'+costo+'</span>'
                                                        }
                                                        
                                                        newRow.insertCell().innerHTML = modo
                                                        newRow.insertCell (-1) .innerHTML = "<a class='' value = 'Delete' onclick = 'deleteFila (this)' style='cursor: pointer'><i class='fa fa-trash' aria-hidden='true' style='color:red'></i></a>";
                                                    
                                                    }) //fin de Livewire.on 

                                                    function cambiarCosto(fila)
                                                    {
                                                        Livewire.emit('cambiarCosto', fila.dataset.id, fila.value);
                                                    }


                                                    $(".valor").on('keypress', function(){
                                                        alert($(this).val())
                                                    })

                                                    window.addEventListener('activarBotonCobrar', event => {
                                                                $('.agregarDetalle').css('display','none')
                                                                $('.cobrar').css('display','block')
                                                            })
                                                    window.addEventListener('desactivarBotonCobrar', event => {
                                                                //$("#tipo_id").val('0')

                                                                $("#tipoestudio_id").val('0')

                                                                //$('.tipoestudio').css('display','none')
                                                                $('.muestra').css('display','none')

                                                                $("#muestra_id").val('0')

                                                                $('.cobrar').css('display','none')
                                                            })

                                                    $('#tablaEstudio').hover(function(){
                                                        $(".botoncelda").addClass("botonvisible");
                                                        $(".botoncelda").removeClass("botonnovisible");
                                                        
                                                    }, function() {
                                                        $(".botoncelda").addClass("botonnovisible");
                                                        $(".botoncelda").removeClass("botonvisible");
                                                    });
                                                    function enableCellSelector() {
                                                        // obteniendo celdas de la tabla HTML
                                                        let tabla = document.getElementById('tablaEstudio')
                                                        boton = document.getElementsByClassName('botonvisible')

                                                        const td = tabla.querySelectorAll('td');
                                                        for(let i = 0, j = td.length; i < j; i++) {
                                                            //aplicando la clase css a las celdas 
                                                            celdavisible.classList.add('td-hover');
                                                        }
                                                    }
           
                                                </script>
                                            @endpush
                                        </div>
                                        <div class="card-footer d-flex justify-content-end">
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <!-- /.row -->

            </form>
            
        </div><!-- /.container-fluid -->

        <!-- Hidden div to load the dynamic content -->
        

    </div>
    <!-- /.content -->

    <!-- Modal -->
    <div class="modal fade" id="form-cobrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="confirmarPago">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: #6C2689;">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span>Cobrar Estudio </span><span class="p-2 bg-danger h5 rounded data">{{$correlacion}}</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <label for="modopago">Modo de Pago</label>
                                <select wire:change="resetPagago" wire:model="modopago" class="campo-select @error('modopago') is-invalid @enderror" id="modopago">
                                    <option value="efectivo">Efectivo Bs.</option>
                                    <option value="dolarefectivo">Efectivo USD</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="puntoventa">Punto de Venta</option>
                                    <option value="pagomovil">Pago Movil</option>
                                    <option value="zelle">Zelle</option>
                                </select>
                                @error('modopago')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="tasa">
                                        BCV: Bs {{$tasa}} / $
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="tipo">Total Pagado</label>
                                    <span class="form-control font-total">{{$totalPagado}}</span>
                                    
                                </div>
                                
                            </div>
                        </div>

                        <div class="row pagomovil" style="display: none;" wire:ignore>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="referencia">Referencia</label>
                                    <input type="number" wire:model.defer="forma.referencia" class="font-costo form-control @error('referencia') is-invalid @enderror" id="referencia" aria-describedby="tipoHelp" min="0">
                                    @error('referencia')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div> 

                                
                            </div>
                        </div>

                        <div class="row zelle" style="display: none;" wire:ignore>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <span>
                                        <h3>Pagar por Zelle</h3>
                                        <p>
                                            Realiza tu pago por zelle a la siguiente cuenta de correo electrónico y no olvides reportar tu pago.
                                        </p>
                                        <h3><strong>Correo : @if($zelle)<h4>{{ $zelle->email }}</h4>@endif</strong></h3>
                                    </span>
                                </div>
                                <div class="form-group">
                                    <label for="pagadoDolares">USD</label>
                                    <input type="text" wire:model.defer="forma.pagadoDolares" autofocus class="pagadoDolares bg-success font-costo form-control @error('pagadoDolares') is-invalid @enderror" aria-describedby="pagadoDolaresHelp" min="0" step="0.01" onkeypress="return check(this, event)">
                                    @error('pagadoDolares')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                    <div class="form-group">
                                        <label for="pagadoBolivares">Bs.</label>
                                        <input type="text" wire:model.defer="forma.pagadoBolivares"  class="font-costo form-control pagadoBolivares1" readonly style="background-color: white;">
                                    </div>
                                </div>
                                <div class="form-group" wire:ignore>
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <label for="cambio">Cambio</label>         
                                        </div>
                                        <div class="col-lg-4">
                                            <input wire:click="functionsearchCambio" wire:model="cambio" type="radio" class="" name="modocambio3" value="dolar">
                                            <span class="mx-1">USD</span>  
                                        </div>
                                        <div class="col-lg-4">
                                            <input wire:click="functionsearchCambio" type="radio" wire:model="cambio" class="" name="modocambio3" value="bolivar">
                                            <span class="mx-1">Bs</span>  
                                        </div>
                                    </div>
                                    <input type="text" wire:model.defer="forma.cambio"  class="font-costo form-control" readonly style="background-color: white;">
                                </div>
                                <div class="form-group" wire:ignore>
                                    <label for="cambio">Resta.</label> 
                                    <input type="text" wire:model.defer="forma.resta"  class="font-costo form-control" readonly style="background-color: white;">
                                </div>
                                <div class="form-group">
                                    <label for="referencia">@if($modopago!='zelle')<span>Referencia</span>  @else <span> Código de confirmación</span> @endif</label>
                                    <input type="text" wire:model.defer="forma.referencia" class="form-control @error('referencia') is-invalid @enderror" id="referencia" aria-describedby="tipoHelp">
                                    @error('referencia')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div> 

                                <div class="form-group">
                                    <label for="banco">Banco </label>
                                    <input type="text" wire:model.defer="forma.banco" class="form-control @error('banco') is-invalid @enderror" id="banco" aria-describedby="tipoHelp">
                                    @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div> 

                                <div class="form-group">
                                    <label for="email">Email </label>
                                    <input type="email" wire:model.defer="forma.email" class="form-control @error('email') is-invalid @enderror" id="email" aria-describedby="tipoHelp">
                                    @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div> 

                                
                            </div>
                        </div>

                        <div class="row efectivo" wire:ignore>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="pagadoBolivares">Monto Bs.</label>
                                    <input type="text" wire:model.defer="forma.pagadoBolivares" autofocus class="col-lg-6 pagadoBolivares bg-success font-costo form-control @error('pagadoBolivares') is-invalid @enderror" aria-describedby="tipoHelp" min="0" step="0.01" onkeypress="return check(this, event)">
                                    @error('pagadoBolivares')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                    <button style="display: none;" class="my-2" wire:click.prevent="functionsearchCambio">Calcular</button>

                                </div>
                                <div class="form-group">
                                    <label for="pagadoDolares">USD</label>
                                    <input type="text" wire:model.defer="forma.pagadoDolares"  class="pagadoDolares1 font-costo form-control" readonly style="background-color: white;">
                                </div>
                                <div class="form-group" wire:ignore>
                                    <label for="cambio">Cambio Bs.</label> 
                                    <input type="text" wire:model.defer="forma.cambio"  class="font-costo form-control" readonly style="background-color: white;">
                                </div>

                                <div class="form-group" wire:ignore>
                                    <label for="cambio">Resta.</label> 
                                    <input type="text" wire:model.defer="forma.resta"  class="font-costo form-control" readonly style="background-color: white;">
                                </div>

                            </div>
                        </div>

                        <div class="row dolarefectivo" wire:ignore style="display: none;">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="pagadoDolares">USD</label>
                                    <input type="text" wire:model.defer="forma.pagadoDolares" autofocus class="pagadoDolares bg-success font-costo form-control @error('pagadoDolares') is-invalid @enderror" aria-describedby="pagadoDolaresHelp" min="0" step="0.01" onkeypress="return check(this, event)">
                                    @error('pagadoDolares')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                    <div class="form-group">
                                        <label for="pagadoBolivares">Bs.</label>
                                        <input type="text" wire:model.defer="forma.pagadoBolivares"  class="font-costo form-control pagadoBolivares1" readonly style="background-color: white;">
                                    </div>
                                    <div class="form-group" wire:ignore>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <label for="cambio">Cambio</label>         
                                            </div>
                                            <div class="col-lg-4">
                                                <input wire:click="functionsearchCambio" wire:model="cambio" type="radio" class="" name="modocambio2" value="dolar">
                                                <span class="mx-1">USD</span>  
                                            </div>
                                            <div class="col-lg-4">
                                                <input wire:click="functionsearchCambio" type="radio" wire:model="cambio" class="" name="modocambio2" value="bolivar">
                                                <span class="mx-1">Bs</span>  
                                            </div>
                                        </div>
                                        <input type="text" wire:model.defer="forma.cambio"  class="font-costo form-control" readonly style="background-color: white;">
                                    </div>
                                    <div class="form-group" wire:ignore>
                                        <label for="cambio">Resta.</label> 
                                        <input type="text" wire:model.defer="forma.resta"  class="font-costo form-control" readonly style="background-color: white;">
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="tipo">Total USD</label>
                                        <span class="form-control font-costo">{{$totalCosto}}</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="tipo">Total Bs.</label>
                                        <span class="form-control font-costo">{{$totalCostoBolivares}}</span>
                                    </div>
                                </div>
                            </div>                            
                        </div>

                        <script>
                            $('.pagadoBolivares').focus(function(){
                                if(parseFloat($(this).val())==0)
                                {
                                    $('.pagadoBolivares').val('');
                                }
                            })
                            $('.pagadoBolivares').blur(function(){
                                if(parseFloat($(this).val())==0||$(this).val()=='')
                                {
                                    $('.pagadoBolivares').val('0,00');
                                }
                            })

                            $('.pagadoDolares').focus(function(){
                                if(parseFloat($(this).val())==0)
                                {
                                    $('.pagadoDolares').val('');
                                }
                            })
                            $('.pagadoDolares').blur(function(){
                                if(parseFloat($(this).val())==0||$(this).val()=='')
                                {
                                    $('.pagadoDolares').val('0,00');
                                }
                            })

                            // $('.pagadoBolivares1').focus(function(){
                            //     if(parseFloat($(this).val())==0)
                            //     {
                            //         $('.pagadoBolivares1').val('');
                            //     }
                            // })
                            // $('.pagadoBolivares1').blur(function(){
                            //     if(parseFloat($(this).val())==0||$(this).val()=='')
                            //     {
                            //         $('.pagadoBolivares1').val('0,00');
                            //     }
                            // })

                            // $('.pagadoDolares1').focus(function(){
                            //     if(parseFloat($(this).val())==0)
                            //     {
                            //         $('.pagadoDolares1').val('');
                            //     }
                            // })
                            // $('.pagadoDolares1').blur(function(){
                            //     if(parseFloat($(this).val())==0||$(this).val()=='')
                            //     {
                            //         $('.pagadoDolares').val('0,00');
                            //     }
                            // })

                            $('#referencia').focus(function(){
                                if(parseFloat($(this).val())==0)
                                {
                                    $('#referencia').val('');
                                }
                            })
                            $('#referencia').blur(function(){
                                if(parseFloat($(this).val())==0||$(this).val()=='')
                                {
                                    $('#referencia').val('');
                                }
                            })

                            window.addEventListener('showCambio', event => {
                                let cambio = event.detail.cambio
                                let modopago = event.detail.modopago

                                if(modopago=='efectivo'){
                                    let pagadoDolares = event.detail.pagadoDolares
                                    //$(".pagadoDolares").val(pagadoDolares)
                                    $("#cambio").val(cambio)    
                                }
                                if(modopago=='dolarefectivo'){
                                    let pagadoBolivares = event.detail.pagadoBolivares
                                    //$(".pagadoBolivares").val(pagadoBolivares)
                                    $("#cambio").val(cambio)    
                                }
                                
                            })
                            
                        </script>

                        <script>
                            $(document).ready(function()
                            {
                                $(".pagadoDolares").on("blur", function() 
                                {
                                    Livewire.emit('searchCambio', $(this).val())     
                                });

                                $(".pagadoBolivares").on("blur", function() 
                                {
                                    Livewire.emit('searchCambio', $(this).val())     
                                });

                                $(".pagadoDolares1").on("keyup", function() 
                                {
                                    //Livewire.emit('searchCambio', $(this).val())     
                                });

                                $(".pagadoBolivares1").on("keyup", function() 
                                {
                                    //Livewire.emit('searchCambio', $(this).val())     
                                });

                            });
                        </script>

                        <script>
                            $('#modopago').on('change', function(){
                                switch ($(this).val())
                                {
                                    case 'efectivo':
                                        $('.efectivo').show();
                                        $('.dolarefectivo').hide();
                                        $('.pagomovil').hide();
                                        $('.zelle').hide();
                                        @this.modopagorecibo = 'Efectivo';
                                        break;
                                    case 'dolarefectivo':
                                        $('.efectivo').hide();
                                        $('.dolarefectivo').show();
                                        $('.pagomovil').hide();
                                        $('.zelle').hide();
                                        @this.modopagorecibo = 'Dolares';
                                        break;
                                    
                                    case 'pagomovil':
                                        $('.efectivo').show();
                                        $('.dolarefectivo').hide();
                                        $('.pagomovil').show();
                                        $('.zelle').hide();
                                        @this.modopagorecibo = 'Pago Movil';
                                        break;
                                    case 'transferencia':
                                        $('.efectivo').show();
                                        $('.dolarefectivo').hide();
                                        $('.pagomovil').show();
                                        $('.zelle').hide();
                                        @this.modopagorecibo = 'Transferencia';
                                        break;
                                    case 'puntoventa':
                                        $('.efectivo').show();
                                        $('.dolarefectivo').hide();
                                        $('.pagomovil').show();
                                        $('.zelle').hide();
                                        @this.modopagorecibo = 'Punto de Venta';
                                        break;
                                    
                                    case 'zelle':
                                        $('.zelle').show();
                                        $('.efectivo').hide();
                                        $('.pagomovil').hide();
                                        @this.modopagorecibo = 'Zelle';
                                        break;                                    
                                }
                            })
                        </script>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="boton">
                            <span>Pagar</span>
                        </button>
                    </div>
                    <script>
                        
                    </script>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        $('#form-cobrar').on('shown.bs.modal', function () {
          $('#pagadoBolivares').focus();
        })
    </script>

    <!-- Modal Paciente -->
    <div class="modal fade" id="form-paciente" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updatePaciente' : 'createPaciente' }}">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: #6C2689;">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Paciente</span>
                            @else
                            <span>Nuevo Paciente</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cedula">Cédula</label>
                            <input wire:model.defer="state.cedula" type="text" autofocus class="cedula form-control @error('cedulap') is-invalid @enderror" id="cedulap" aria-describedby="cedulaHelp" placeholder="Cédula" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" value="{{ old('cedula') }}">
                            @error('cedulap')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name">Nombre</label>
                            <input type="text" wire:model.defer="state.name" class="form-control @error('name') is-invalid @enderror" id="name" aria-describedby="nameHelp" placeholder="Nombre completo">
                            @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="fechanacimiento">Fecha nacimiento</label>
                            <input type="date" wire:model.defer="state.fechanacimiento" class="form-control @error('fechanacimiento') is-invalid @enderror" id="fechanacimiento" aria-describedby="fechanacimientoHelp">
                            @error('fechanacimiento')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="sexo">Sexo</label><br>
                            <div class="btn-group">
                            <input class="form-control mx-2" type="radio" wire:model.defer="state.sexo" name="sexo" value="femenino"><span class="border-0 form-control">Femenino</span>
                            <input class="form-control mx-2" type="radio" wire:model.defer="state.sexo" name="sexo" value="masculino"><span class="border-0 form-control">Masculino</span>
                            </div>
                            @error('sexo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="direccion">Dirección de habitación</label>
                            <input type="text" wire:model.defer="state.direccion" class="form-control" id="direccion" aria-describedby="direccionHelp">
                            
                        </div>

                        <div class="form-group">
                            <label for="telefonoP">Teléfono</label>
                            <input type="tel" wire:model.defer="state.telefono" class="form-control" id="telefono" aria-describedby="telefonoHelp" onKeypress="if (event.keyCode < 45 || event.keyCode > 57 || event.keyCode == 189) event.returnValue = false;">
                            
                        </div>

                        <div class="form-group">
                            <label for="email">Correo electrónico</label>
                            <input type="email" wire:model.defer="state.email" class="form-control @error('email') is-invalid @enderror" id="email" aria-describedby="emailHelp">
                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                        <button type="submit" class="boton"><i class="fa fa-save mr-1"></i>
                            @if($showEditModal)
                            <span>Guardar Cambios</span>
                            @else
                            <span>Guardar</span>
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Medico -->
    <div class="modal fade" id="form-medico" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateMedico' : 'createMedico' }}">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: #6C2689;">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Medico</span>
                            @else
                            <span>Nuevo Medico</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cedulaMM">Cédula</label>
                            <input type="text" wire:model.defer="stateMedico.cedula" class="cedula form-control @error('cedulaMM') is-invalid @enderror" id="cedulaMM" aria-describedby="cedulaPHelp" placeholder="Cédula" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;">
                            @error('cedulaMM')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="nombreMM">Nombre</label>
                            <input type="text" wire:model.defer="stateMedico.name" class="form-control @error('nombreMM') is-invalid @enderror" id="nombreMM" aria-describedby="nombreMMHelp" placeholder="Nombre completo">
                            @error('nombreMM')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="especialidad">Especialidad</label>
                            <br>
                            <select wire:model="especialidad_id" class="campo-select @error('especialidad') is-invalid @enderror" id="especialidad" aria-describedby="especialidadHelp" placeholder="Nombre completo">
                                <option value="0" selected>SELECCIONE</option>
                                @foreach($especialidades as $especialidad)
                                    <option value="{{$especialidad->id}}" {{ ($especialidad_id=== $especialidad->id) ? 'selected' : '' }}>{{$especialidad->name}}</option>
                                @endforeach
                            </select>
                            @error('especialidad')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="telefonoP">Teléfono</label>
                            <input type="tel" wire:model.defer="stateMedico.telefono" class="form-control @error('telefonoP') is-invalid @enderror" id="telefonoP" aria-describedby="telefonoPHelp" onKeypress="if (event.keyCode < 45 || event.keyCode > 57 || event.keyCode == 189) event.returnValue = false;">
                            @error('telefonoP')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="emailP">Correo electrónico</label>
                            <input type="email" wire:model.defer="stateMedico.email" class="form-control @error('emailP') is-invalid @enderror" id="emailP" aria-describedby="emailPHelp">
                            @error('emailP')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                        <button type="submit" class="boton"><i class="fa fa-save mr-1"></i>
                            @if($showEditModal)
                            <span>Guardar Cambios</span>
                            @else
                            <span>Guardar</span>
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Procedencia -->
    <div class="modal fade" id="form-procedencia" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateMedico' : 'createProcedencia' }}">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: #6C2689;">
                        <h5 class="modal-title" id="exampleModalLabel">
                            @if($showEditModal)
                            <span>Editar Procedencia</span>
                            @else
                            <span>Nuevo Procedencia</span>
                            @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nombreMP">Nombre</label>
                            <input type="text" wire:model.defer="stateProcedencia.name" class="form-control @error('nombreMP') is-invalid @enderror" id="nombreMP" aria-describedby="nombreMPHelp" placeholder="Nombre completo">
                            @error('nombreMP')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-lg-6">
                                    <label for="rif">CI/RIF</label>
                                    <input type="text" wire:model.defer="stateProcedencia.rif" class="form-control @error('rif') is-invalid @enderror" id="rif" aria-describedby="rifHelp">
                                    @error('rif')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror  
                                </div>
                                <div class="col-lg-6">
                                    <label for="nit">NIT</label>
                                    <input type="text" wire:model.defer="stateProcedencia.nit" class="form-control @error('nit') is-invalid @enderror" id="nit" aria-describedby="nitHelp">
                                    @error('nit')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror        
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descuento">DESCUENTO</label>
                            <input type="number" wire:model.defer="stateProcedencia.descuento" class="form-control @error('descuento') is-invalid @enderror" id="descuento" aria-describedby="descuentoHelp" value="0" step="0.01">
                            @error('descuento')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror  
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-lg-6">
                                    <input type="checkbox" wire:model="excento" class="mycheck2" id="excento">
                                    <label for="excento">EXCENTO</label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="checkbox" wire:model="credito" class="mycheck2" id="credito" >
                                    <label class="mx-2" for="credito"> PERMITE CREDITO</label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="radio" wire:model="precio" name="precio" value="precioA">
                                    <label for="precio">PRECIO A</label>
                                    <input type="radio" wire:model="precio" name="precio" value="precioB" checked>
                                    <label for="precio">PRECIO B</label>
                                </div>
                            </div>

                            
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                        <button type="submit" class="boton"><i class="fa fa-save mr-1"></i>
                            @if($showEditModal)
                            <span>Guardar Cambios</span>
                            @else
                            <span>Guardar</span>
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Recibo -->
    <div class="modal fade" id="form-cambiarCosto" data-backdrop="static" 
  data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #6C2689;">
                    <h5 class="modal-title" id="exampleModalLabel">
                        <span>Cambiar Valor</span>
                    </h5>
                    <button type="button" wire:click.prevent=""  class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cerrar</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <label for="costoanterior">Costo Anterior</label>
                            <input type="text" wire:model.defer="state.costoanterior" class="form-control" aria-describedby="costoanteriorHelp" readonly style="background-color: white;">
                        </div>
                        <div class="col-lg-6">
                            <label for="costonuevo">Costo</label>
                            <input type="text" wire:model.defer="state.costonuevo" class="form-control @error('costonuevo') is-invalid @enderror" id="costonuevo" aria-describedby="costonuevoHelp" placeholder="Costo">
                            @error('costonuevo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                        <button type="button" wire:click="updateCosto" class="boton"><i class="fa fa-save mr-1"></i>
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
            </div>
        </div>
    </div>

    <!-- Modal Recibo -->
    <div class="modal fade" id="form-recibo" data-backdrop="static" 
  data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg" role="document">
            <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateMedico' : 'createMedico' }}">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: #6C2689;">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span>Recibo de Pago</span>
                        </h5>
                        <button type="button" wire:click.prevent="suspenderRecibo"  class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cerrar</button>
                    </div>
                    <div class="modal-body">
                        <embed src="{{Storage::url('estudio').'/recibo/'.$laboratorio_id.'/'.$filename}}" type="application/pdf" width="100%" height="500px;" id="reportePDF"></embed>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="form-confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #6C2689;">
                    <h5>Realizar Pago</h5>
                </div>

                <div class="modal-body">
                    <h4>Esta usted seguro de de los datos introducidos?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                    <button type="button" wire:click.prevent="createEstudio" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Continuar</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        
        var path = "{{ route('autocomplete-paciente') }}";
      
        $( "#cedula" ).autocomplete({
            source: function( request, response ) {
              $.ajax({
                url: path,
                type: 'GET',
                dataType: "json",
                data: {
                   search: request.term,
                   campo: 'cedula',
                   'laboratorio_id':@this.laboratorio_id,
                },
                success: function( data2 ) {
                   response( data2 );
                }
              });
            },
            select: function (event, ui) {
               
               $('#cedula').val(ui.item.cedula);

               $('#temp').val(ui.item.cedula);

               $('#nombre').val(ui.item.nombre);

               if(ui.item.sexo=='masculino')
               {
                 
               		//$("#sexoM").prop("checked", true);
                    //$("#sexoM").val() = 'masculino'
                    $("#sexoM").attr('checked', true);
               }
               else
               {
               		//$("#sexoF").prop("checked", true);
                    $("#sexoF").attr('checked', true);
               }

               let edad = calcularEdad(ui.item.fechanacimiento)
               $('#edad').val(edad)

               Livewire.emit('enviarDataPaciente',ui.item.identi, ui.item.cedula, ui.item.sexo, edad)
               $('#nombre').focus()

               return false;
            }
          });

        function calcularEdad(fecha) {
        	if(fecha){
        		var hoy = new Date();
			    var cumpleanos = new Date(fecha);
			    var edad = hoy.getFullYear() - cumpleanos.getFullYear();
			    var m = hoy.getMonth() - cumpleanos.getMonth();

			    if (m < 0 || (m === 0 && hoy.getDate() < cumpleanos.getDate())) {
			        edad--;
			    }

			    return edad;
        	}
		    else
		    {
		    	return 0;	
		    }
		}

        var path = "{{ route('autocomplete-paciente') }}";
      
        $( "#nombre" ).autocomplete({
            source: function( request, response ) {
              $.ajax({
                url: path,
                type: 'GET',
                dataType: "json",
                data: {
                   search: request.term,
                   campo: 'name',
                   'laboratorio_id':@this.laboratorio_id,
                },
                success: function( data5 ) {
                   response( data5 );
                }
              });
            },
            select: function (event, ui) {
               
               $('#cedula').val(ui.item.cedula);

               $('#temp').val(ui.item.cedula);

               $('#nombre').val(ui.item.nombre);

               if(ui.item.sexo=='masculino')
               {
                    //$("#sexoM").prop("checked", true);
                    $("#sexoM").attr('checked', true);
               }
               else
               {
                    //$("#sexoF").prop("checked", true);
                    $("#sexoF").attr('checked', true);
               }

               let edad = calcularEdad(ui.item.fechanacimiento)
               $('#edad').val(edad)

               Livewire.emit('enviarDataPaciente',ui.item.identi, ui.item.cedula, ui.item.sexo, edad)
               $('#nombre').focus()

               return false;
            }
        });
    
        var path1 = "{{ route('autocomplete-procedencia') }}";
      
        $( "#nombreProcedencia" ).autocomplete({
            source: function( request, response ) {
              $.ajax({
                url: path1,
                type: 'GET',
                dataType: "json",
                data: {
                   search: request.term,
                   'laboratorio_id':@this.laboratorio_id,
                },
                success: function( data4 ) {
                   response( data4 );
                }
              });
            },
            select: function (event, ui) {
                
                Livewire.emit('enviarDataProcedencia',ui.item.identi)

                return false;
            }
        });

        var path3 = "{{ route('autocomplete-medicoNombre') }}";

        $( "#nombreM" ).autocomplete({
            source: function( request, response ) {
              $.ajax({
                url: path3,
                type: 'GET',
                dataType: "json",
                data: {
                   search: request.term,
                   'laboratorio_id':@this.laboratorio_id,
                },
                success: function( data3 ) {
                   response( data3 );
                }
              });
            },
            select: function (event, ui) {
                
                $('#cedulaM').val(ui.item.cedula);

                $('#nombreM').val(ui.item.nombre);

                Livewire.emit('enviarDataMedico',ui.item.identi)

                $('#nombreM').focus()

                return false;
            }
        });

        //cargar tipo      
        $(function(){
            $('#tipo_id').on('change', onSelectTipoChange);
        });
        function onSelectTipoChange()
        {
            var tipo_id = $(this).val();

            var laboratorio_id = @this.laboratorio_id;

            var path = "select_tipoA/"+ tipo_id+"/"+laboratorio_id;
            //Ajax
            $.get(path, function(result){
                var html_select = '<option value="0">SELECCIONE..</option>';

                for(var i=0; i<result.length; ++i)
                {
                    html_select += '<option value="'+result[i].identi+'">'+result[i].nombre+'</option>';
                }
                $('#tipoestudio_id').html(html_select);
                $('.tipoestudio').css('display', 'block');
                $('.muestra').css('display', 'NONE')
            })
        }

        //cargar tipoestudio      
        $(function(){
            $('#tipoestudio_id').on('change', onSelectTipoEstudioChange);
        });
        function onSelectTipoEstudioChange()
        {
            var datatipo =   $('#tipo_id option:selected').attr('datatipo');

            //if($("#tipo_id").val()==1 )
            if(datatipo=='BIOPSIA')
            {
                var tipoestudio_id = $(this).val();

                var laboratorio_id = @this.laboratorio_id;

                var path = "select_tipoestudioA/"+ tipoestudio_id+"/"+laboratorio_id;
                //Ajax
                $.get(path, function(result){
                    
                    var html_select = '<option value="0">SELECCIONE..</option>';

                    for(var i=0; i<result.length; ++i)
                    {
                        html_select += '<option value="'+result[i].identi+'">'+result[i].nombre+'</option>';
                    }
                    $('#muestra_id').html(html_select);
                })

                if($(this).val()=='0'){
                    $('.muestra').css('display','none')
                    //$('#agregarDetalle').css('display','none')
                    let tabla = document.getElementById('tablaEstudio')
                    if(tabla.rows > 1)
                    {
                        $('#cobrar').css('display','none');    
                    }
                }
                else
                {
                    $('.muestra').css('display','block')
                    //$('#agregarDetalle').css('display','none')

                    
                }
                $('.agregarDetalle').css('display','none')
                let tabla = document.getElementById('tablaEstudio')
                if(tabla.rows > 1)
                {
                    $('#cobrar').css('display','none');    
                }

                //cancelRowInTable()
                
                //@this.totalCosto = '0,00'
            }
            else
            {
                //$('.agregarDetalle').css('display','block')
                //$('.cobrar').css('display','none')

                //cancelRowInTable2()

                //@this.totalCosto = '0,00'
            }
            $('#agregarDetalle').css('display','none')
            if($(this).val()!=0)
            {
                if($('#tipo_id option:selected').html()=='CITOLOGÍA')
                {
                    $('#agregarDetalle').css('display','block')
                    $('.muestra').css('display','none')
                    //@this.listmuestras = "";
                    //@this.muestra_id = 0;
                }
                if($('#tipo_id option:selected').html()=='INMUNOHISTOQUIMICA')
                {

                    $('#agregarDetalle').css('display','block')
                    $('.muestra').css('display','none')
                    //@this.muestra_id = 0;
                }
            }
            else
            {
                $('#agregarDetalle').css('display','none')
            }
                
        }

        $(document).ready(function()
        {
            $("#cedula").blur(function(){
                    Livewire.emit('searchPaciente', 1 ,$(this).val())
            });

            $("#nombre").blur(function(){
                Livewire.emit('searchPaciente', 2, $(this).val())
            });

            $("#nombreProcedencia").blur(function(){
                    Livewire.emit('searchProcedencia',$(this).val())
            });
            $("#edad").blur(function(){
                    //Livewire.emit('searchPaciente',$(this).val())
            });
        });

        window.addEventListener('updatePaciente', event => {

            $('#nombre').val(event.detail.nombre);

            if(event.detail.sexo=='femenino')
            {
                //$("#sexoF").prop("checked", true);
                $("#sexoF").attr('checked', true);
            }
            else
            {
                //$("#sexoF").prop("checked", true);
                $("#sexoM").attr('checked', true);
            }
            
            $('#edad').val(event.detail.edad);    
            
        });

        $(document).ready(function()
        {
            $("#cedulamedico").blur(function(){
                    Livewire.emit('searchMedico',$(this).val())
            });

            $("#nombreM").blur(function(){
                    Livewire.emit('searchMedico',$(this).val())
            });

            $("#cedula").keyup(function(event){
                if($(this).val()=='')
                {
                    $("#nombre").val('')
                    $("#edad").val('0')
                    
                }
            });

            $("#cedulaM").keyup(function(event){
                if($(this).val()=='')
                {
                    $("#nombreM").val('')
                    
                }
            });
        });

    </script>

    <script src="/js/recursos.js"></script>
    

    <script type="text/javascript">

        window.addEventListener('beforeunload', function(event) {
          if (@this.correlacion != '') {
            var confirmationMessage = '¿Seguro que quieres salir?';
            Livewire.emit('cambioStatusReserva', @this.correlacion)
            event.returnValue = confirmationMessage;
            @this.correlacion = ''
            $('#tipo_id option').eq(0).prop('selected', true);

            return confirmationMessage;
          }
        });

        window.addEventListener('show-form-recibo', function (event) {
            $('#form-recibo').modal('show');
        });
        window.addEventListener('hide-form-recibo', function (event) {
            $('#form-recibo').modal('hide');
        });

        window.addEventListener('show-form-cambiarCosto', function (event) {
            $('#form-cambiarCosto').modal('show');
        });
        window.addEventListener('hide-form-cambiarCosto', function (event) {
            $('#form-cambiarCosto').modal('hide');
        });

        window.addEventListener('show-form-confirmationModal', function (event) {
            $('#form-confirmationModal').modal('show');
        });
        window.addEventListener('hide-form-confirmationModal', function (event) {
            $('#form-confirmationModal').modal('hide');
        });

        window.addEventListener('cambiarDatos', event => {
            let nrofila = event.detail.nrofila
            let costonuevo = event.detail.costonuevo
            
            let fila = document.getElementById('tablaEstudio').rows[nrofila].cells;

            let i = 0;
            for(const celda of fila){
                i += 1;
                if(i==3)
                {

                    celda.innerHTML = '<span>'+costonuevo+'</span>' + '<input class="mx-2 boton bg-warning botoncelda botonnovisible" style=" padding:0; font:6px; color:black; width:100px; height:35px;" data-id="'+nrofila+'" value="Cambiar" onclick="cambiarCosto(this)">'
                
                }
            }
        
        }) //fin de Livewire.on        

        document.getElementById('cedula').addEventListener('keypress', function(evt) {
            if (filterInteger(evt, evt.target) === false) {
                evt.preventDefault();
            }
        });

        document.getElementById('cedulap').addEventListener('keypress', function(evt) {
            if (filterInteger(evt, evt.target) === false) {
                evt.preventDefault();
            }
        });

        document.getElementById('cedulaMM').addEventListener('keypress', function(evt) {
            if (filterInteger(evt, evt.target) === false) {
                evt.preventDefault();
            }
        });
            
    </script>

    
</div>
