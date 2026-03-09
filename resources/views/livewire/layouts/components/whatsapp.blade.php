<div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        a {
            text-decoration: none;
        }
        /* Estilos para el botón flotante de WhatsApp */
        .whatsapp-float {
            position: fixed; /* Mantiene el botón en su posición al hacer scroll */
            width: 60px; /* Ancho del botón */
            height: 60px; /* Alto del botón */
            bottom: 40px; /* Distancia desde la parte inferior */
            right: 40px; /* Distancia desde la parte derecha */
            background-color: #128C7E; /* Color de fondo especificado */
            color: #FFF; /* Color del icono blanco */
            border-radius: 50%; /* Lo hace completamente redondo/ovalado */
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 3px #999;
            z-index: 100; /* Asegura que esté por encima de otros elementos */
            display: flex; /* Para centrar el icono */
            justify-content: center; /* Centrar horizontalmente */
            align-items: center; /* Centrar verticalmente */
            transition: background-color 0.3s; /* Transición para el efecto hover */
        }

        /* Efecto al pasar el ratón (opcional) */
        .whatsapp-float:hover {
            background-color: #162661; /* Un color ligeramente más oscuro al pasar el ratón */
            border: 1px solid #ffff;
        }

        /* Ajuste del icono dentro del botón */
        .whatsapp-icon {
            margin-top: 0; /* Elimina cualquier margen superior por defecto de Font Awesome */
        }

        
    </style>

    <a href="https://api.whatsapp.com/send?phone=+58{{ $contactcellphone }}&text={{ $msgcontact }}" class="whatsapp-float" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-whatsapp whatsapp-icon"></i>
    </a>
</div>
