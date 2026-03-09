@component('mail::message')
# Introducción

The body of your message.
Esto es el Mensaje

@component('mail::button', ['url' => ''])
Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
