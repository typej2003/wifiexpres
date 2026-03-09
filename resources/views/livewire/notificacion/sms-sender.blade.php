<div>
    
<div class="container row">
    <div class="col-md-12 col-12">
        <form wire:submit.prevent="sendSms">
            <div class="mb-3">
                <label for="to" class="form-label">Número de Teléfono</label>
                <input wire:model="to" type="text" class="form-control @error('to') is-invalid @enderror" id="to" placeholder="+1234567890">
                @error('to')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Mensaje</label>
                <textarea wire:model="message" class="form-control @error('message') is-invalid @enderror" id="message" rows="4">Esta es una prueba</textarea>
                @error('message')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Enviar SMS</button>
            
            @if ($status)
                <div class="alert {{ $status == 'success' ? 'alert-success' : 'alert-danger' }} mt-4" role="alert">
                    @if ($status == 'success')
                        ¡SMS enviado con éxito!
                    @else
                        Error al enviar el SMS. Por favor, inténtelo de nuevo.
                    @endif
                </div>
            @endif
        </form>
    </div>
</div>
</div>