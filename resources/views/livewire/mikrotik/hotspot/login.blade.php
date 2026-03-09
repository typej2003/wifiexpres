<html>
    <head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
<body>
<div>
    <div class="row">
        <div class="col-md-12 col-12 bg-info">
            <button wire:click.prevent="login" class="btn btn-primary btnLogin mx-auto"> Entrar</button>
        </div>
    </div>
</div>
</body>
<script>
    window.onload = function() {
        let btnLogin = document.querySelector('.btnLogin')
        btnLogin.addEventListener('click', ()=> {          
            alert('koo')
            fetch('https://panexpres.com/api/enviardatos')
            .then(response => response.json())
            //.then(data => console.log(data))
            .then(data => {            
                if (data.success == true) {
                    console.log('ok')
                }
                else {
                    console.log(data.responseMessage)
                    console.log(data)
                }
            })
            .catch(error => {
                console.error('Error:', error)
                btnEnviarPagomovil.disabled = false;
            });
        })
</script>
</html>