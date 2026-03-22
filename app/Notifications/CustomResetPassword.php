<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class CustomResetPassword extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Generamos la URL segura de reseteo
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
                    ->subject('🛡️ Restablecer Coordenadas de Acceso')
                    ->greeting('¡Atención, Soldado!')
                    ->line('Recibimos una solicitud de emergencia para restablecer tu contraseña en Finanzas de Combate.')
                    ->action('🔑 Generar Nueva Clave', $url)
                    ->line('Este enlace de seguridad se autodestruirá en 60 minutos.')
                    ->line('Si no solicitaste este rescate, ignora este mensaje. Tu trinchera sigue blindada.')
                    ->salutation('Cambio y fuera, El Alto Mando.');
    }
}