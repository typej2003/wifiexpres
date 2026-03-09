<div>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Perfil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active">Perfil del Usuario</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">

                    <!-- Profile Image -->
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center" x-data="{ imagePreview: '{{ auth()->user()->avatar_url }}' }">
                                <input wire:model="image" type="file" class="d-none" x-ref="image" x-on:change="
                                        reader = new FileReader();
                                        reader.onload = (event) => {
                                            imagePreview = event.target.result;
                                            document.getElementById('profileImage').src = `${imagePreview}`;
                                        };
                                        reader.readAsDataURL($refs.image.files[0]);
                                    " />
                                <img x-on:click="$refs.image.click()" class="profile-user-img img-circle" x-bind:src="imagePreview ? imagePreview : '/backend/dist/img/user4-128x128.jpg'" alt="User profile picture">
                            </div>

                            <h3 class="profile-username text-center">{{ auth()->user()->name }}</h3>

                            <p class="text-muted text-center">{{ auth()->user()->rol() }}</p>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
                <div class="col-md-9">
                    <div class="card" x-data="{ currentTab: $persist('profile') }">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills" wire:ignore>
                                <li @click.prevent="currentTab = 'profile'" class="nav-item mx-2"><a class="nav-link" :class="currentTab === 'profile' ? 'active' : ''" href="#profile" data-toggle="tab"><i class="fa fa-user mr-1"></i> Editar Perfil</a></li>
                                <li @click.prevent="currentTab = 'changePassword'" class="nav-item mx-2"><a class="nav-link" :class="currentTab === 'changePassword' ? 'active' : ''" href="#changePassword" data-toggle="tab"><i class="fa fa-key mr-1"></i> Cambiar Contraseña</a></li>
                                <li @click.prevent="currentTab = 'changeBasicData'" class="nav-item mx-2"><a class="nav-link" :class="currentTab === 'changeBasicData' ? 'active' : ''" href="#changeBasicData" data-toggle="tab"><i class="fa fa-key mr-1"></i> Datos Básicos</a></li>
                                <li @click.prevent="currentTab = 'changeBillingDetails'" class="nav-item mx-2"><a class="nav-link" :class="currentTab === 'changeBillingDetails' ? 'active' : ''" href="#changeBillingDetails" data-toggle="tab"><i class="fa fa-key mr-1"></i> Facturación</a></li>
                            </ul>
                        </div><!-- /.card-header -->
                        <div class="card-body">
                            <div class="tab-content">

                                <div class="tab-pane" :class="currentTab === 'profile' ? 'active' : ''" id="profile" wire:ignore.self>
                                    <form wire:submit.prevent="updateProfile" class="form-horizontal">

                                        <div class="form-group row">
                                            <label for="inputName" class="col-sm-2 col-form-label">Usuario</label>
                                            <div class="col-sm-10">
                                                <input wire:model.defer="state.name" type="text" class="form-control @error('name') is-invalid @enderror" id="inputName" placeholder="Usuario">
                                                @error('name')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="identificationNac"class="col-sm-2 col-form-label">Tipo </label>                                                    
                                            <div class="col-sm-10">
                                                <select wire:model.defer="state.identificationNac" class="form-control @error('identificationNac') is-invalid @enderror" name="identificationNac" id="identificationNac" placeholder="Tipo">
                                                    <option value="J">J-</option>
                                                    <option value="E">E-</option>
                                                    <option value="G">G-</option>
                                                    <option value="P">P-</option>
                                                    <option value="V" selected>V-</option>
                                                </select>
                                                @error('identificationNac')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="identificationNumber" class="col-sm-2 col-form-label">Documento</label>
                                            <div class="col-sm-10">
                                                <input type="text" wire:model.defer="state.identificationNumber" class="form-control @error('identificationNumber') is-invalid @enderror" name="identificationNumber" id="identificationNumber" placeholder="Documento">
                                                @error('identificationNumber')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="names" class="col-sm-2 col-form-label">Nombres</label>
                                            <div class="col-sm-10">
                                                <input wire:model.defer="state.names" type="text" class="form-control @error('names') is-invalid @enderror" id="inputNames" placeholder="Nombres">
                                                @error('names')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="surnames" class="col-sm-2 col-form-label">Apellidos</label>
                                            <div class="col-sm-10">
                                                <input wire:model.defer="state.surnames" type="text" class="form-control @error('surnames') is-invalid @enderror" id="inputSurName" placeholder="Apellidos">
                                                @error('surnames')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input wire:model.defer="state.email" type="email" class="form-control @error('email') is-invalid @enderror" id="inputEmail" placeholder="Email">
                                                @error('email')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i> Guardar Cambios</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" :class="currentTab === 'changePassword' ? 'active' : ''" id="changePassword" wire:ignore.self>
                                    <form wire:submit.prevent="changePassword" class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="currentPassword" class="col-sm-3 col-form-label">Contraseña actual</label>
                                            <div class="col-sm-9">
                                                <input wire:model.defer="state.current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" id="currentPassword" placeholder="Contraseña actual">
                                                @error('current_password')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="newPassword" class="col-sm-3 col-form-label">Nueva Contraseña</label>
                                            <div class="col-sm-9">
                                                <input wire:model.defer="state.password" type="password" class="form-control @error('password') is-invalid @enderror" id="newPassword" placeholder="Nueva Contraseña">
                                                @error('password')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="passwordConfirmation" class="col-sm-3 col-form-label">Confirme la Contraseña</label>
                                            <div class="col-sm-9">
                                                <input wire:model.defer="state.password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="passwordConfirmation" placeholder="Confirme la Contraseña">
                                                @error('password_confirmation')
                                                <div class="invalid-feedback">
                                                    {{ $message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-3 col-sm-9">
                                                <button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i> Guardar Cambios</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" :class="currentTab === 'changeBasicData' ? 'active' : ''" id="changeBasicData" wire:ignore.self>
                                    @livewire('admin.profile.basic-data', ['user_id' => auth()->user()->id ])
                                </div>

                                <div class="tab-pane" :class="currentTab === 'changeBillingDetails' ? 'active' : ''" id="changeBillingDetails" wire:ignore.self>
                                    @livewire('admin.profile.billing-details', ['user_id' => auth()->user()->id ])
                                </div>
                                <!-- /.tab-pane -->
                            </div>
                            <!-- /.tab-content -->
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

</div>

@push('styles')
<style>
    .profile-user-img:hover {
        background-color: blue;
        cursor: pointer;
    }
</style>
@endpush

@push('alpine-plugins')
<!-- Alpine Plugins -->
<script defer src="https://unpkg.com/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
@endpush

@push('js')
<script>
    $(document).ready(function () {
        Livewire.on('nameChanged', (changedName) => {
            $('[x-ref="username"]').text(changedName);
        })
    });
</script>
@endpush
