<?php

return [
    'ui' => [
        'login' => [
            'title' => 'Entrar',
            'email_label' => 'E-mail:',
            'email_placeholder' => 'joao@email.com',
            'password_label' => 'Senha',
            'forgot_password' => 'Esqueceu a senha?',
            'remember' => 'Manter conectado',
            'submit' => 'Entrar',
            'or_divider' => 'ou entrar com',
            'no_account' => 'Não tem uma conta?',
            'create_account' => 'Criar uma conta',
        ],

        'register' => [
            'title' => 'Cadastro',
            'name_label' => 'Nome',
            'name_placeholder' => 'João Silva',
            'email_label' => 'Endereço de e-mail',
            'email_placeholder' => 'ola@exemplo.com',
            'phone_label' => 'Telefone',
            'password_label' => 'Senha',
            'password_confirmation_label' => 'Confirmar senha',
            'terms_prefix' => 'Ao continuar, você concorda com nossos',
            'terms_link' => 'termos de serviço',
            'submit' => 'Cadastrar',
            'or_divider' => 'ou cadastrar-se com',
            'have_account' => 'Já tem uma conta?',
            'sign_in' => 'Entrar aqui',
        ],

        'social' => [
            'google' => 'Continuar com o Google',
            'google_not_configured' => 'O login com Google ainda não está configurado.',
        ],

        'forgot' => [
            'title' => 'Esqueceu a senha?',
            'subtitle' => 'Informe seu e-mail e enviaremos um código para redefinir a senha.',
            'email_label' => 'E-mail',
            'email_placeholder' => 'joao@email.com',
            'submit' => 'Enviar código',
            'back_to_login' => 'Voltar para o login',
            'sent' => 'Se o e-mail existir, enviamos um código de 6 dígitos.',
        ],

        'reset' => [
            'title' => 'Redefinir senha',
            'subtitle' => 'Digite o código de 6 dígitos enviado ao seu e-mail e a nova senha.',
            'email_label' => 'E-mail',
            'pin_label' => 'Código',
            'pin_hint' => 'Digite os 6 dígitos que enviamos.',
            'password_label' => 'Nova senha',
            'password_confirmation_label' => 'Confirmar nova senha',
            'verify_submit' => 'Verificar código',
            'set_new_password' => 'Código verificado. Defina a nova senha.',
            'submit' => 'Redefinir senha',
            'invalid_pin' => 'Código inválido ou expirado.',
            'success' => 'Senha redefinida. Faça login com a nova senha.',
            'email_subject' => 'Código de redefinição de senha',
            'email_greeting' => 'Olá, :name!',
            'email_intro' => 'Use o código abaixo para redefinir sua senha:',
            'email_expires' => 'O código expira em :minutes minutos.',
            'email_ignore' => 'Se você não solicitou, ignore este e-mail.',
        ],
    ],
];
