<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex align-items-center mb-4">
                <button wire:click="back" class="btn btn-light rounded-circle me-3 shadow-sm border d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="bi bi-arrow-left fs-4 text-dark"></i>
                </button>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Configurar Portal Cautivo</h4>
                    <p class="text-muted small mb-0">Gestión de identidad y estado del servicio</p>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom p-3">
                    <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-sliders me-2 text-primary"></i>Ajustes del Dispositivo</h5>
                </div>
                <div class="card-body p-4">
                    @if (session()->has("message"))
                        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert" style="background-color: #28a745;">
                            <span class="text-white fw-bold"><i class="bi bi-check-circle me-2"></i>{{ session("message") }}</span>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has("error"))
                        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert" style="background-color: #dc3545;">
                            <span class="text-white fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>{{ session("error") }}</span>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">ESTADO DEL SERVICIO</label>
                            <select wire:model="status" class="form-select border-0 bg-light rounded-3 p-2">
                                <option value="Habilitado">🟢 Habilitado (Online)</option>
                                <option value="Mantenimiento">🟠 Mantenimiento (Offline)</option>
                                <option value="Suspendido">🔴 Suspendido (Offline)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">TÍTULO DEL PORTAL (HEADER)</label>
                            <input type="text" wire:model="comercio_nombre" class="form-control border-0 bg-light rounded-3 p-2">
                        </div>

                        {{-- SECCIÓN BANNER DEL COMERCIO CON BOTÓN ELIMINAR --}}
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">BANNER PRINCIPAL DEL PORTAL</label>
                            <div class="d-flex align-items-center p-3 bg-light rounded-4 border-0">
                                <div class="me-4 position-relative">
                                    <img src="{{ $router->banner_router }}" 
                                         class="rounded-3 shadow-sm object-fit-cover bg-white" 
                                         style="width: 140px; height: 80px; border: 2px solid white;">
                                    
                                    {{-- Botón para eliminar banner --}}
                                    @if($comercio_banner)
                                        <button wire:click="removeBanner" 
                                                class="btn btn-danger btn-sm position-absolute top-0 start-0 m-1 rounded-circle d-flex align-items-center justify-content-center shadow" 
                                                style="width: 25px; height: 25px; padding: 0; border: 1px solid white;"
                                                title="Eliminar banner actual">
                                            <i class="bi bi-trash fs-6"></i>
                                        </button>
                                    @endif

                                    <div wire:loading wire:target="banner_photo, removeBanner" class="position-absolute top-50 start-50 translate-middle">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                    </div>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <label class="btn btn-dark btn-sm rounded-pill px-4 shadow-sm mb-0" style="cursor: pointer;">
                                        <i class="bi bi-camera me-2"></i> Cambiar Banner
                                        <input type="file" wire:model="banner_photo" hidden accept="image/*">
                                    </label>
                                    <p class="text-muted small mt-2 mb-0">Esta imagen aparecerá como cabecera en el inicio de sesión.</p>
                                </div>
                            </div>
                            @error('banner_photo') <small class="text-danger d-block mt-2">{{ $message }}</small> @enderror
                        </div>

                        <hr class="my-4">

                        <div class="col-12">
                            <div class="form-check form-switch p-3 bg-light rounded-4 border-0 mb-3">
                                <input class="form-check-input ms-0 me-3" type="checkbox" wire:model="is_trial" id="is_trial" style="width: 40px; height: 20px;">
                                <label class="form-check-label fw-bold text-dark" for="is_trial">Ofrecer Prueba Gratuita (Cortesía)</label>
                            </div>

                            <div class="form-check form-switch p-3 bg-light rounded-4 border-0">
                                <input class="form-check-input ms-0 me-3" type="checkbox" wire:model="is_promotion" id="is_promotion" style="width: 40px; height: 20px;">
                                <label class="form-check-label fw-bold text-dark" for="is_promotion">Activar Carrusel de Publicidad</label>
                            </div>

                            @if($is_promotion)
                                <div class="mt-3 p-4 border rounded-4 bg-white shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" style="cursor: pointer;">
                                            <i class="bi bi-upload me-2"></i>Cargar Imagen
                                            <input type="file" wire:model="photo" hidden accept="image/*">
                                        </label>
                                        <div wire:loading wire:target="photo" class="text-primary small">
                                            <div class="spinner-border spinner-border-sm me-1"></div> Subiendo...
                                        </div>
                                    </div>
                                    @error("photo") <small class="text-danger d-block mb-2">{{ $message }}</small> @enderror

                                    <div class="d-flex flex-wrap gap-3 mt-2">
                                        @foreach((is_array($path_imgs) ? $path_imgs : []) as $index => $img)
                                            <div class="position-relative shadow-sm rounded-3 overflow-hidden" 
                                                wire:key="carrousel-img-{{ $index }}-{{ $loop->iteration }}"
                                                style="width: 150px; height: 100px; border: 2px solid #f8f9fa;">
                                                
                                                <img src="{{ asset("storage/carruselhotspot/" . $img) }}" class="w-100 h-100 object-fit-cover">
                                                
                                                <button wire:click="removeImage({{ $index }})" 
                                                        wire:loading.attr="disabled"
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle d-flex align-items-center justify-content-center" 
                                                        style="width: 25px; height: 25px; padding: 0;">
                                                    <i class="bi bi-trash fs-6"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <div class="col-12">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" wire:model="is_store" id="is_store" style="width: 40px; height: 20px;">
                                <label class="form-check-label fw-bold text-dark ms-2" for="is_store">Mostrar información de Punto de Venta</label>
                            </div>

                            @if($is_store)
                                <div class="row g-3 p-4 bg-light rounded-4 border-0">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">NOMBRE DEL COMERCIO (VENTA)</label>
                                        <input type="text" wire:model="store" class="form-control border-0 rounded-3" placeholder="Ej: Bodega El Socio">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">DIRECCIÓN / UBICACIÓN DETALLADA</label>
                                        <textarea wire:model="address" class="form-control border-0 rounded-3" rows="3" placeholder="Describe dónde encontrar la tienda física..."></textarea>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top p-4 text-end">
                    <button wire:click="save" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow hover-lift" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">GUARDAR Y SINCRONIZAR</span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-2"></span> SINCRONIZANDO...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>