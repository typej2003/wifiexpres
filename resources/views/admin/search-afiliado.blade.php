    <!-- Bootstrap CSS -->
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" integrity="sha384-WJUUqfoMmnfkBLne5uxXj+na/c7sesSJ32gI7GfCk4zO4GthUKhSEGyvQ839BC51" crossorigin="anonymous"> -->
<!-- <script src="https://kit.fontawesome.com/03cf5139f1.js"></script> -->

<link rel="stylesheet" href="/css/modopago.css" class="rel">

<head>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> -->
    <script src="/js/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

    <script src="/js/variables.js"></script>
    <script src="/js/selectMetodoPago.js"></script>
    
</head> 
<section class="banner">
    <div class="row">
        <div class="col-lg-12">
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <a href="/"><img class="img_logo" src="/img/logo_01.png" alt=""></a>
        </div>
    </div>
</section>
<div class="container-fluid d-flex flex-row">
    <div class="card mx-auto cardModoPago" style="width: 32rem;">
        <div class="card-body">
            @if($comercio)
            <div class="row">
                <div class="col-12">
                    <span>Comercio Id: </span><span>{{$comercio}}</span>
                    <script>
                        var comercioId = {{$comercio->id}};
                    </script>
                </div>
            </div>
            <div class="row">
                <div class="form-group">
                    <label for="cedula">Cédula</label>
                    <input id="identificationNumber0" type="text" autofocus class="cedula form-control inputForm @error('identificationNumber0') is-invalid @enderror" aria-describedby="cedulaHelp" placeholder="Cédula" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" value="{{ old('identificationNumber0') }}">
                    @error('identificationNumber0')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>  
                <script>
                    $('#identificationNumber0').on('input', function () { 
                        this.value = this.value.replace(/[^0-9]/g,'');
                    });
                </script>
                <div class="group-control">
                    <label for="clienteName">Nombre</label>
                    <input type="text" id="clienteName" class="form-control inputForm" placeholder="Nombre">
                </div>
                <div id="verifica" class="group-control text-center negrita d-none"><a href="#">Crear Cuenta</a></div>
            </div>
            <div class="row my-3">
                <div class="col-lg-12" wire:ignore>
                    <div class="divPrincipal" id="divPrincipal"></div>
                </div>
            </div>
            @else
                <span>El Comercio Referido no existe</span>
            @endif
        </div>
    </div>

</div>

<script>
    let divPrincipal = document.getElementById('divPrincipal')
    divPrincipal.appendChild(selectMetodoPago(0))        
</script>

<script>
    $("#identificationNumber0").keyup(function(){
        if($(this).val().length > 0 ){
            $("#identificationNumber0").css("background-color", "#B3B4E2");
        }else{
            $("#identificationNumber0").css("background-color", "#fff");
        }
        
    });
    var path = "{{ route('autocomplete-cliente') }}";
      
      $( "#identificationNumber0" ).autocomplete({
          source: function( request, response ) {
            $.ajax({
              url: path,
              type: 'GET',
              dataType: "json",
              data: {
                   search: request.term,
                   campo: 'cedula',
                },
              success: function( data2 ) {
                response( data2 );                
                let verifica = document.querySelector('#verifica')
                if(data2.length == 0){
                    verifica.classList.remove('d-none')
                }else{
                    verifica.classList.add('d-none')            
                }
              }
            });
          },
          select: function (event, ui) {            
             $('#identificationNumber0').val(ui.item.identificationNumber);
             clienteId = ui.item.identi
             cellphone =  ui.item.telefono
             email =  ui.item.email
             identificationNac = ui.item.identificationNac
             identificationNumber = ui.item.identificationNumber
             $('#temp').val(ui.item.identificationNumber);
             $('#clienteName').val(ui.item.nombre);
          }
        });
</script>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script> -->
<script src="/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

