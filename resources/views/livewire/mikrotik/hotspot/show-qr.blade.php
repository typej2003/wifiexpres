<div>
    @push('js')
    <!-- <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script> -->
     <script src="/js/qrcode.min.js"></script>
     
    @endpush('js')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-3">
                    <h1 class="m-0 text-dark">Mostrar QR</h1>
                </div>
                <div class="col-sm-3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4>QR WifiExpres</h4>
                                </div>
                            </div>                            
                            <div class="row d-none">
                                <input type="text" id="textInput" placeholder="Enter text here" value="WIFI:S:wifiexpres;P:nopass;">
                            </div>
                            <div class="row">
                                <div class="col-md-12 col-12">
                                    <div id="qrcode"></div>        
                                </div>
                            </div>
                            
                        </div>     
                        <br><br>
                        @push('js')
                        <script src="/js/qr.js"></script>
                        <script>
                            generateQr('qrcode', 'WIFI:S:wifiexpres;P:nopass;')
                        </script>
                        @endpush('js')
                    </div>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Escritorio</a></li>
                        <li class="breadcrumb-item active"><a href="/listTicketsVendidos">Tickets Vendidos</a></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-12 col-12">
                    <div class="d-flex justify-content-between mb-2">
                        <button onclick="printdivAll('seccion-qr')" id="imprimirTodo" class="btn btn-success">Imprimir todo</button>
                    </div>        
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 col-12">
                    <div id="seccion-qr" class="row seccion-qr">
                    </div>                    
                </div>
            </div>

            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
    <script>
        window.addEventListener('crear-qr', event => {

            let usershotspot = event.detail.usershotspot
            console.log(usershotspot)

            let seccionQr = document.querySelector('.seccion-qr')
            seccionQr.innerHTML = ''
            
            let contenido ='';

            usershotspot.forEach((user) => {
                let fondo = ''
                switch (user['comment']) {
                    case 'activo':
                        fondo = 'bg-warning'
                        texto = 'Desactivar'
                        disabled = 'disabled'
                        break;
                    case 'noactivo':
                        fondo = ''
                        texto = 'Activar'
                        disabled = ''
                        break;
                }                
                contenido = `<div class="col-md-4 col-12">
                            <div class="card shadow w-75 ${fondo}">
                                <div id="${user['name']}" class="card-body">
                                    <div class="text-start"><span>Serial: </span>${user['nroTicket']}</div>
                                    <div class="text-start"><span></span>${user['aliado']}</div>
                                    <div class="text-start"><span>Usuario: </span>${user['name']}</div>
                                    <div class="text-start"><span>Password: </span>${user['password']}</div>                                    
                                    <div style="width:150px !important; height:150px !important;" id="qr${user['name']}"></div>
                                    <div class="text-start"><span>Costo: </span>${user['monto']}</div>                                    
                                </div>
                                <div class="card-footer btn-imprimir">                                
                                    <a onclick=enviar('${user['name']}','${user['comment']}') class="btn btn-success form-control ${disabled} my-1"><i class="fa fa-plus-circle mr-1"></i> ${texto}</a>
                                    <button onclick="imprimirDiv('${user['name']}')" class="btn btn-danger form-control my-1">Anular</button>
                                    <button onclick="imprimirDiv('${user['name']}')" class="btn btn-light form-control my-1"><i class="fa fa-solid fa-print"></i></button>
                                </div>

                            </div>                                    
                        </div>`
                seccionQr.innerHTML += contenido                
            });
            usershotspot.forEach((user) => {
                doQr(user)
            });
        
        }) 
        function enviar(user, comm)
        {
            let valor = 'noactivo'
            switch (comm) {
                case 'activo':
                    valor = 'noactivo'
                    break;
            
                case 'noactivo':
                    valor = 'activo'
                    break;
            }
            Livewire.emit('changeComment', { name: user, comment: valor });
        }
        function doQr(user)
        {
            let text = `http://wifi.wifiexpres/login.html?scan=1&user=${user['name']}&pass=${user['password']}`
            //let text = user['name'] + '&' + user['password']
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
    </script>
    <script>
        window.onload = function() {
            Livewire.emit('verQr');
        }
    </script>
</div>
