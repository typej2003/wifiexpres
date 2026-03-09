<div>
    <div class="row my-3">
        <div class="col-md-12 col-12">
            Dominio: {{ $dominio }} Ip del dominio: {{ $ip_del_dominio }}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
                <h1>Integración del Router: {{ $identity}}</h1>
                <button wire:click.prevent="showIntegracion()" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Obtener</button>
        </div>
    </div>
    <div class="row container">
        <div class="col-md-12 col-12">
            {!! $response !!}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
                <h1>Buscar id 04165800403</h1>
                <button wire:click.prevent="verIdUser()" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Ver id user</button>
        </div>
    </div>
    
    

</div>

