<?php

namespace App\Http\Livewire\Notificacion;

use Livewire\Component;
use Illuminate\Http\Request;
use App\Mail\TestMail;
use App\Models\Notificacion;
use Mail;
use App\Models\User;
use App\Models\Pedido;
use App\Models\PedidoDetalles;

class EmailController extends Component
{
    public $index;

    public function mount($index = 0)
    {
        $this->index = $index;
    }

    public function render()
    {
        switch ($this->index) {
            case '1':
                # code...
                break;
            
            default:
                return view('livewire.notificacion.email-controller');
                break;
        }        
    }

    public function sendEmail($operacion, User $user, $nropedido = 0 )
    {
        switch ($operacion) {
            case 'welcome':
                $this->sendMailWelcome($user);
                break;
            case 'welcomeComercio':
                $this->sendEmailCliente('welcomeComercio', $user);
                break;
            case 'compra':
                $this->compraRealizada($user, $nropedido);
                break;
            
            case 'compraRealizadaWithImages':
                $this->compraRealizadaWithImages($user, $nropedido);
                break;
            
            case 'confirmacionPago':
                $this->confirmacionPago($user, $nropedido);
                break;
            
            case 'confirmacionFallida':
                $this->confirmacionFallida($user, $nropedido);
                break;
            
            default:
                return view('livewire.notificacion.email-controller');
                break;
        }

        $this->dispatchBrowserEvent('updated', ['message' => "Notificación enviada."]);
    }

    /**
     * Métodos originales mantenidos...
     */

    public function sendMailWithAttachment($user, $title = '', $body = '')
    {
        $data["email"] = $user->email;
        $data["title"] = "Techsolutionstuff";
        $data["body"] = "This is test mail with attachment";
        $files = [public_path('img/regalo.png'), public_path('img/test_pdf.pdf')];
        Mail::send('emails.test_mail', $data, function($message) use ($data, $files) {
            $message->to($data["email"])->subject($data["title"]);
            foreach ($files as $file){ $message->attach($file); }            
        });
    }

    public function sendMailNotificacion($user, Notificacion $notificacion)
    {
        $data["email"] = $user->email;
        $data["title"] = $notificacion->title;
        $data["body"] = $notificacion->content;
        $files = [$notificacion->file_url];
        Mail::send('emails.test_mail', $data, function($message) use ($data, $files) {
            $message->to($data["email"])->subject($data["title"]);
            foreach ($files as $file){ $message->attach($file); }            
        });
    }

    public function sendMailWelcome(User $user, $file = null)
    {
        $data["user"] = $user;
        $data["names"] = $user->names;
        $data["surnames"] = $user->surnames;
        $data["email"] = $user->email;
        $data["title"] = 'Bienvenid(@) a PanExpres.com';
        $data["body"] = 'Te damos la bienvenida';
        
        if($file !== null) {
            $files = [$notificacion->file_url];
            Mail::send('emails.welcome-panexpres', $data, function($message) use ($data, $files) {
                $message->to($data["email"])->subject($data["title"]);
                foreach ($files as $file){ $message->attach($file); }            
            });
        } else {
            Mail::send('emails.welcome-panexpres', $data, function($message) use ($data) {
                $message->to($data["email"])->subject($data["title"]);
            });
        }
    }

    public function compraRealizada(User $user, $nropedido)
    {
        $data["user"] = $user;
        $data["names"] = $user->names;
        $data["surnames"] = $user->surnames;
        $data["email"] = $user->email;
        $data["title"] = 'Compra realizada';
        $data["nropedido"] = $nropedido;
        $pedido = Pedido::where('nropedido', $nropedido)->first();
        $data["body"] = 'Pedido ' . $nropedido . ', con referencia ' . $pedido->reference . ' fue recibido.' .'<br>';
        $data["body"] .= 'Nuestro equipo de venta atenderá su pedido, en espera de validación, Gracias por su compra';
        Mail::send('emails.compra-realizada', $data, function($message) use ($data) {
            $message->to($data["email"])->subject($data["title"]);
        });
    }

    public function compraRealizadaWithImages(User $user, $nropedido)
    {
        $data["user"] = $user;
        $data["names"] = $user->names;
        $data["surnames"] = $user->surnames;
        $data["email"] = $user->email;
        $data["title"] = 'Compra realizada';
        $data["nropedido"] = $nropedido;
        $detalles = PedidoDetalles::where('nropedido', $nropedido)->get();
        $files = [];
        foreach($detalles as $detalle) { $files[] = $detalle->image; }
        $data['detalles'] = $detalles;
        Mail::send('emails.compra-realizada-img', $data, function($message) use ($data, $files) {
            $message->to($data["email"])->subject($data["title"]);
            foreach ($files as $file){ $message->attach($file); }            
        });
    }

    public function confirmacionPago(User $user, $nropedido)
    {
        $data["user"] = $user;
        $data["names"] = $user->names;
        $data["surnames"] = $user->surnames;
        $data["email"] = $user->email;
        $data["title"] = 'Confirmación del pago';
        $data["nropedido"] = $nropedido;
        $pedido = Pedido::where('nropedido', $nropedido)->first();
        $data["body"] = 'La compra ' . $nropedido . ', con referencia ' . $pedido->reference . ' fue procesado satisfactoriamente!';
        Mail::send('emails.pago-confirmado', $data, function($message) use ($data) {
            $message->to($data["email"])->subject($data["title"]);    
        });
    }

    public function confirmacionFallida(User $user, $nropedido)
    {
        $data["user"] = $user;
        $data["names"] = $user->names;
        $data["surnames"] = $user->surnames;
        $data["email"] = $user->email;
        $data["title"] = 'Confirmacion Pago';
        $data["nropedido"] = $nropedido;
        $pedido = Pedido::where('nropedido', $nropedido)->first();
        $data["body"] = 'La compra ' . $nropedido . ', con referencia ' . $pedido->reference . ' no fue procesado.' .'<br>';
        $data["body"] .= 'Lo invitamos a ponerse en contacto con nuestro personal de soporte por los teléfonos '. $pedido->comercio->telefono ;
        Mail::send('emails.pago-fallido', $data, function($message) use ($data) {
            $message->to($data["email"])->subject($data["title"]);    
        });
    }

    /**
     * NUEVO MÉTODO PARA WELCOME COMERCIO
     */
    public function sendEmailCliente($operacion, User $user)
    {
        if($operacion == 'welcomeComercio') {
            $data["names"] = $user->names ?? 'Cliente';
            $data["surnames"] = $user->surnames ?? '';
            $data["email"] = $user->email;
            $data["username"] = $user->username ?? $user->email;
            // Nota: La contraseña solo se puede enviar si el usuario se acaba de crear 
            // y tienes el valor plano o si usas el campo de la BD (si no está encriptado)
            $data["password"] = $user->password_plain ?? '********'; 
            
            $data["title"] = 'Tus credenciales de acceso - ' . config('app.name');
            $data["body"] = 'Es un placer darte la bienvenida a nuestra red. A continuación, tus datos para conectarte:';


            Mail::send('emails.welcome-comercio', $data, function($message) use ($data) {
                $message->to($data["email"])
                        ->from('ventas@wifiexpres.com', 'Administrador Wifiexprés') // <--- Aquí cambias el remitente
                        ->subject($data["title"]);    
            });
        }
    }
}