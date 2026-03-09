<div>
    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    @endpush('js')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Tickets</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active">Tickets</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="col-md-2">
                            <label for="comment">Mostrar</label>
                            <select wire class="form-control" wire:model="comment" id="comment">
                                <option value="all">Todos</option>
                                <option value="activo">ACTIVO</option>
                                <option value="noactivo">NO ACTIVO</option>
                                <option value="suspendido">SUSPENDIDO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="desde">Desde</label>
                            <input type="date" wire:model="desde" class="form-control @error('desde') is-invalid @enderror" id="desde" aria-describedby="desdeHelp" placeholder="12/10/2025">
                            @error('desde')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="hasta">Hasta</label>
                            <input type="date" wire:model="hasta" class="form-control @error('hasta') is-invalid @enderror" id="hasta" aria-describedby="hastaHelp" placeholder="12/10/2025">
                            @error('hasta')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <x-search-input wire:model="searchTerm" />
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">
                                            N° Ticket
                                            <span wire:click="sortBy('nroTicket')" class="float-right text-sm" style="cursor: pointer;">
                                                <i class="fa fa-arrow-up {{ $sortColumnName === 'nroTicket' && $sortDirection === 'asc' ? '' : 'text-muted' }}"></i>
                                                <i class="fa fa-arrow-down {{ $sortColumnName === 'nroTicket' && $sortDirection === 'desc' ? '' : 'text-muted' }}"></i>
                                            </span>
                                        </th>
                                        <th scope="col">
                                            Aliado
                                        </th>
                                        <th scope="col">Usuario</th>
                                        <th scope="col">prefijo</th>
                                        <th scope="col">Costo</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Opciones</th>                                        
                                    </tr>
                                </thead>
                                <tbody wire:loading.class="text-muted">
                                    @forelse ($tickets as $index => $ticket)
                                    <tr>
                                        <th scope="row">{{ $tickets->firstItem() + $index }}</th>
                                        <td>
                                            {{ $ticket->nroTicket }} 
                                            <a href="/showQr/{{ $ticket->id }}">
                                                <i class="fa fa-solid fa-qrcode"></i>
                                            </a>                                            
                                        </td>
                                        <td>{{ $ticket->aliado()->name }}</td>
                                        <td>{{ $ticket->user }}</td>
                                        <td>{{ $ticket->prefijo }}</td>
                                        <td>{{ $ticket->monto }}</td>
                                        <td>
                                            <select class="form-control" wire:change="activar({{ $ticket }}, $event.target.value)">
                                                <option value="activo" {{ ($ticket->comment === 'activo') ? 'selected' : '' }}>ACTIVO</option>
                                                <option value="noactivo" {{ ($ticket->comment === 'noactivo') ? 'selected' : '' }}>NO ACTIVO</option>
                                                <option value="suspendido" {{ ($ticket->comment === 'suspendido') ? 'selected' : '' }}>SUSPENDIDO</option>
                                            </select>
                                        </td>
                                        <td>{{ $ticket->created_at->toFormattedDate() ?? 'N/A' }}</td>                                        
                                        <td>
                                            <a href="" wire:click.prevent="confirmTicketRemoval({{ $ticket->id }})">
                                                <i class="fa fa-trash text-danger"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr class="text-center">
                                        <td colspan="7">
                                            <img src="https://42f2671d685f51e10fc6-b9fcecea3e50b3b59bdc28dead054ebc.ssl.cf5.rackcdn.com/v2/assets/empty.svg" alt="No results found">
                                            <p class="mt-2">No se encontro resultados</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            {{ $tickets->links() }}
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->

    <!-- Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Anular Ticket</h5>
                </div>

                <div class="modal-body">
                    <h4>Esta seguro de querer anular este usuario?</h4>
                    <h4>Esta acción eliminar el cuenta del dispositivo</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                    <button type="button" wire:click.prevent="deleteTicket" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Anular Ticket</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="form" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span>Ver QR</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 col-12">
                                <div id="seccion-qr" class="row seccion-qr">
                                </div>                    
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancelar</button>
                    </div>
                </div>
        </div>
    </div>
    <script>
        function generateQr() {
            const text = document.getElementById("textInput").value;
            const qrcodeElement = document.getElementById("qrcode");
            
            // Clear previous QR code if it exists
            qrcodeElement.innerHTML = "";
            
            if (text.trim() !== '') {
                // Generate the QR code
                new QRCode(qrcodeElement, {
                    text: text,
                    width: 200,
                    height: 200,
                    colorDark : "#333333",
                    colorLight : "#FFFFFF",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } else {
                qrcodeElement.innerHTML = "<p>Please enter some text to generate a QR code.</p>";
            }
        }
        
        // Generate a QR code on page load with the default value
        window.onload = generateQr;
    </script>
    <script>
        window.addEventListener('crear-qr', event => {
            let usershotspot = event.detail.usershotspot
            let seccionQr = document.querySelector('.seccion-qr')
            seccionQr.innerHTML = ''
            let contenido ='';
            usershotspot.forEach((user) => {
                contenido = `<div class="col-md-12 col-12">
                                <div class="card shadow w-75">
                                    <div id="${user['name']}" class="card-body">
                                        <div class="text-start"><span></span>${user['aliado']}</div>
                                        <div class="text-start"><span>Usuario: </span>${user['name']}</div>
                                        <div class="text-start"><span>Password: </span>${user['password']}</div>
                                        <div style="width:200px; height:200px;" id="qr${user['name']}"></div>
                                    </div>
                                    <div class="card-footer btn-imprimir">
                                        <button onclick="imprimirDiv('${user['name']}')" class="btn btn-success">Imprimir</button>
                                    </div>
                                </div>                                    
                            </div>`
                    
                seccionQr.innerHTML += contenido                
            });

            contenido += `</div>`
            usershotspot.forEach((user) => {
                doQr(user)
            });
        
        }) 

        function imprimirDiv(user)
        {
            printdiv(user)
        }

        function printdiv(elem) {            
            var header_str = '<html><head><title>' + document.title  + '</title><link rel="stylesheet" href="bootstrap.min.css"></head><body>';
            var footer_str = '</body></html>';
            var new_str = document.getElementById(elem).innerHTML;
            var old_str = document.body.innerHTML;
            document.body.innerHTML = header_str + new_str + footer_str;
            window.print();
            document.body.innerHTML = old_str;
            return false;
        }

        function printdivAll(elem) {
            
            var buttonAll = document.querySelectorAll('btn-imprimir')
            buttonAll.forEach(button => {
                button.style.display = 'none';
            });
            style = `<style>button{display: none;}</style>`
            // var header_str = '<html><head><title>' + document.title  + '</title><link rel="stylesheet" href="bootstrap.min.css"></head><body>';
            var header_str = `<html><head><title>` + document.title  + `</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
            </head><body>`;
            
            var footer_str = '</body></html>';
            var new_str = '<div class="row">' + document.getElementById(elem).innerHTML + '</div>';
            var old_str = document.body.innerHTML;
            document.body.innerHTML = header_str + new_str + footer_str;
            window.print();
            document.body.innerHTML = old_str;
            return false;
        }

        function doQr(user)
        {
            //let text = user['name'] + '&' + user['password']
            let text = `http://wifi.wifiexpres/login.html?scan=1&user=${user['name']}&pass=${user['password']}`
            let qrcodeElement = document.getElementById("qr"+user['name']);
            
            // Clear previous QR code if it exists
            qrcodeElement.innerHTML = "";
            
            if (text.trim() !== '') {
                // Generate the QR code
                new QRCode(qrcodeElement, {
                    text: text,
                    width: qrcodeElement.offsetWidth, //200,
                    height: qrcodeElement.offsetHeight, //200,
                    colorDark : "#333333",
                    colorLight : "#FFFFFF",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } else {
                qrcodeElement.innerHTML = "<p>Please enter some text to generate a QR code.</p>";
            }
        }
        //doQr()

        function imprimirDivEnNuevaVentana(idDiv) {
            alert(idDiv)
            // 1. Obtiene el contenido del div
            var contenido = document.getElementById(idDiv).innerHTML;
            // 2. Crea una nueva ventana
            var ventanaImpresion = window.open('', '_blank');
            // 3. Escribe el contenido en la nueva ventana
            ventanaImpresion.document.write('<html><head><title>Imprimir</title></head><body>');
            ventanaImpresion.document.write(contenido);
            ventanaImpresion.document.write('</body></html>');
            // 4. Cierra la escritura
            ventanaImpresion.document.close();
            // 5. Llama a la ventana de impresión
            ventanaImpresion.print();
        }
    </script>
</div>
