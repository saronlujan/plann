<?php

return [
    'ui' => [
        'login' => [
            'title' => 'Iniciar sesión',
            'email_label' => 'Correo electrónico:',
            'email_placeholder' => 'juan@email.com',
            'password_label' => 'Contraseña',
            'forgot_password' => '¿Olvidaste tu contraseña?',
            'remember' => 'Mantener sesión iniciada',
            'submit' => 'Iniciar sesión',
            'or_divider' => 'o inicia sesión con',
            'no_account' => '¿No tienes una cuenta?',
            'create_account' => 'Crear una cuenta',
        ],

        'register' => [
            'title' => 'Registro',
            'name_label' => 'Nombre',
            'name_placeholder' => 'Juan Pérez',
            'email_label' => 'Correo electrónico',
            'email_placeholder' => 'hola@ejemplo.com',
            'phone_label' => 'Teléfono',
            'password_label' => 'Contraseña',
            'password_confirmation_label' => 'Confirmar contraseña',
            'terms_prefix' => 'Al continuar, aceptas nuestros',
            'terms_link' => 'términos de servicio',
            'submit' => 'Registrarse',
            'or_divider' => 'o regístrate con',
            'have_account' => '¿Ya tienes una cuenta?',
            'sign_in' => 'Inicia sesión aquí',
        ],

        'social' => [
            'google' => 'Continuar con Google',
            'google_not_configured' => 'El inicio de sesión con Google aún no está configurado.',
        ],

        'forgot' => [
            'title' => '¿Olvidaste tu contraseña?',
            'subtitle' => 'Ingresa tu correo y te enviaremos un código para restablecer la contraseña.',
            'email_label' => 'Correo electrónico',
            'email_placeholder' => 'juan@correo.com',
            'submit' => 'Enviar código',
            'back_to_login' => 'Volver al inicio de sesión',
            'sent' => 'Si el correo existe, enviamos un código de 6 dígitos.',
        ],

        'reset' => [
            'title' => 'Restablecer contraseña',
            'subtitle' => 'Ingresa el código de 6 dígitos que enviamos a tu correo y la nueva contraseña.',
            'email_label' => 'Correo electrónico',
            'pin_label' => 'Código',
            'pin_hint' => 'Ingresa los 6 dígitos que enviamos.',
            'password_label' => 'Nueva contraseña',
            'password_confirmation_label' => 'Confirmar nueva contraseña',
            'verify_submit' => 'Verificar código',
            'set_new_password' => 'Código verificado. Define tu nueva contraseña.',
            'submit' => 'Restablecer contraseña',
            'invalid_pin' => 'Código inválido o expirado.',
            'success' => 'Contraseña restablecida. Inicia sesión con la nueva contraseña.',
            'email_subject' => 'Código para restablecer la contraseña',
            'email_greeting' => '¡Hola, :name!',
            'email_intro' => 'Usa el siguiente código para restablecer tu contraseña:',
            'email_expires' => 'El código expira en :minutes minutos.',
            'email_ignore' => 'Si no lo solicitaste, ignora este correo.',
        ],
    ],
];
