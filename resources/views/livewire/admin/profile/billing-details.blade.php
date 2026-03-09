<div class="container-fluid d-flex">
    <div class="card my-3 mx-auto" style="width: 80%!important;">
        <form wire:submit.prevent="updateBillingDetails" class="form-horizontal p-2">
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-6 col-md-4 col-sm-4 col-4">
                        <label for="identificationNac">Tipo de documento <span class="text-danger">*</span></label>
                        <select class="form-control @error('identificationNac') is-invalid @enderror" name="identificationNac" id="identificationNac" placeholder="Tipo">
                            <option value="J">J-</option>
                            <option value="E">E-</option>
                            <option value="G">G-</option>
                            <option value="P">P-</option>
                            <option value="V" selected>V-</option>
                        </select>
                    </div>
                    <div class="col-xs-6 col-md-8 col=sm-8 col-8">
                        <label for="identificationNumber">Documento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('identificationNumber') is-invalid @enderror" name="identificationNumber" id="identificationNumber" placeholder="Documento">
                    </div>
                    @error('identificationNumber')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>                            
            </div>
            <div class="group-control my-3">
                <label for="names" for="">Nombres <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('names') is-invalid @enderror" id="names">
                @error('names')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="group-control my-3">
                <label for="surnames" for="">Apellidos <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('surnames') is-invalid @enderror" id="surnames">
                @error('surnames')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="cellphonecode">Teléfono <span class="text-danger">*</span></label>        
                <div class="row ">
                    <div class="col-xs-6 col-md-5 col-sm-4 col-4">
                        <select wire:model.defer="state.cellphonecode" class="form-control" name="cellphonecode" id="cellphonecode">
                            <option value="0">Seleccione</option>
                            <option value="0412">0412</option>
                            <option value="0414">0414</option>
                            <option value="0424">0424</option>
                            <option value="0416">0416</option>
                            <option value="0426">0426</option>
                        </select>
                    </div>
                    <div class="col-xs-6 col-md-7 col-sm-8 col-8">
                        <input wire:model.defer="state.cellphone" type="text" class="form-control" name="cellphone" id="cellphone">
                    </div>
                </div>                
            </div>
            <div class="form-group">
                <label for="address" class="">Dirección postal (Calle, Nº de Casa) <span class="text-danger">*</span></label>
                <textarea wire:model.defer="state.address" type="text" class="form-control @error('address') is-invalid @enderror" id="inpuAddress" placeholder="Dirección"></textarea>
                @error('address')
                <div class="invalid-feedback">
                    {{ $message}}
                </div>
                @enderror
            </div>
            <div class="form-group">
                <label for="country" class="">País <span class="text-danger">*</span></label>
                <select wire:model="country" class="form-control @error('country') is-invalid @enderror" id="country">
                    <option value="0">Seleccione una opción</option>
                    @foreach($countries as $country)
                        @if($country->name == 'Venezuela')
                        <option value="{{ $country->id }}" selected>{{ $country->name }}</option>
                        @else
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endif
                    @endforeach
                </select>
                @error('country')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="form-group">
                <label for="province" class="">Estado/Provincia </label>
                <select wire:model="province" class="form-control @error('province') is-invalid @enderror" id="province">
                <option value="0">Seleccione una opción</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
                @error('province')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="city" class="">Ciudad/Sector <span class="text-danger">*</span></label>
                <select wire:model="city" class="form-control @error('city') is-invalid @enderror" id="city">
                    <option value="0">Por favor seleccione una ciudad</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                    @endforeach
                </select>
                @error('city')
                <div class="invalid-feedback">
                    {{ $message}}
                </div>
                @enderror
            </div>
            <div class="form-group">
                <label for="zona" class="">Zona de entrega <span class="text-danger">*</span></label>
                <select wire:model="zona" class="form-control @error('zona') is-invalid @enderror" id="zona">
                    <option value="0">Por favor seleccione una ciudad</option>
                    @foreach($zonas as $zona)
                        <option value="{{ $zona->id }}">{{ $zona->name }}</option>
                    @endforeach
                </select>
                @error('zona')
                <div class="invalid-feedback">
                    {{ $message}}
                </div>
                @enderror
            </div>
            <div class="form-group">
                <label for="zipcode" class="">Código Postal <span class="text-danger">*</span></label>
                <input type="text" wire:model.defer="state.zipcode" type="text" class="form-control @error('zipcode') is-invalid @enderror" id="zipcode" placeholder="Código Postal">
                @error('zipcode')
                <div class="invalid-feedback">
                    {{ $message}}
                </div>
                @enderror
            </div>
            <div class="form-group row">
                <div class="offset-sm-2 col-sm-10 d-flex">
                    <button type="submit" class="btn btn-app mx-auto"><i class="fa fa-save mr-1"></i> Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>
