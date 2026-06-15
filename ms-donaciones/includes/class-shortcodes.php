<?php

if (!defined('ABSPATH')) {
    exit;
}

class MS_Donaciones_Shortcodes {

    public static function init() {
        add_shortcode('formulario_donacion', [__CLASS__, 'render_formulario']);
    }

    public static function render_formulario() {
        self::enqueue_assets();

        return '<div id="ms-donacion-root"></div>';
    }

    private static function enqueue_assets() {
        wp_enqueue_script(
            'react',
            'https://unpkg.com/react@18.3.1/umd/react.development.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'react-dom',
            'https://unpkg.com/react-dom@18.3.1/umd/react-dom.development.js',
            ['react'],
            null,
            true
        );

        wp_enqueue_script(
            'babel',
            'https://unpkg.com/@babel/standalone@7.29.0/babel.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'ms-donaciones-form',
            MS_DONACIONES_URL . 'assets/donacion.js',
            ['react', 'react-dom', 'babel'],
            MS_DONACIONES_VERSION,
            true
        );

        $labels = array_merge(
            self::default_labels(),
            get_option('ms_donaciones_labels', [])
        );

        $frontend_labels = $labels;
        unset(
            $frontend_labels['sf_enabled'],
            $frontend_labels['sf_sandbox'],
            $frontend_labels['sf_login_url'],
            $frontend_labels['sf_consumer_key'],
            $frontend_labels['sf_consumer_secret'],
            $frontend_labels['sf_username'],
            $frontend_labels['sf_password_token'],
            $frontend_labels['sf_connection_message'],
            $frontend_labels['mp_access_token'],
            $frontend_labels['mp_connection_message']
        );

        wp_localize_script('ms-donaciones-form', 'MS_DONACIONES', [
            'restUrl' => esc_url_raw(rest_url('donacion/v1')),
            'labels'  => $frontend_labels,
        ]);
    }

    public static function default_labels() {
        return [
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'email' => 'Email',
            'dni' => 'DNI',
            'telefono' => 'Teléfono',
            'sf_enabled' => '0',
            'sf_sandbox' => '0',
            'sf_login_url' => '',
            'sf_consumer_key' => '',
            'sf_consumer_secret' => '',
            'sf_username' => '',
            'sf_password_token' => '',
            'sf_field_firstname' => 'FirstName',
            'sf_field_lastname' => 'LastName',
            'sf_field_email' => 'Email',
            'sf_field_phone' => 'MobilePhone',
            'sf_field_dni' => '',
            'sf_opp_stage' => 'Closed Won',
            'sf_connection_status' => 'unknown',
            'sf_connection_message' => '',
            'mp_access_token' => '',
            'mp_connection_status' => 'unknown',
            'mp_connection_message' => '',
            'mp_item_title' => 'Donación Módulo Sanitario',
            'mp_statement_descriptor' => 'MODULO SANITARIO',
            'mp_success_url' => 'https://modulosanitario.org/gracias',
            'mp_failure_url' => 'https://modulosanitario.org/donar',
            'mp_pending_url' => 'https://modulosanitario.org/gracias',
            'mp_webhook_url' => '',
            'foto_url' => 'https://modulosanitario.org/wp-content/uploads/2025/08/banos-portadad-_0003_IMG-20250209-WA0023-1-768x768.jpg',
            'hero_caption' => 'Familia Pereyra · Florencio Varela · 2025',
            'site_back_url' => '/inicio',
            'site_back_label' => 'Volver al sitio',
            'stepper_1_label' => 'Tus datos',
            'stepper_2_label' => 'Método de pago',
            'stepper_3_label' => 'Confirmar',
            'hero_image_alt' => 'Familia con su nuevo baño digno',
            'hero_stat_1_number' => '6M',
            'hero_stat_1_label' => 'de personas en Argentina viven sin baño',
            'hero_stat_2_number' => '+1.200',
            'hero_stat_2_label' => 'módulos sanitarios construidos desde 2014',
            'hero_quote_text' => '"Antes mis hijos hacían sus necesidades en una letrina afuera. Ahora tienen un baño que les da dignidad."',
            'hero_quote_author' => '— Carolina, beneficiaria, Quilmes',
            'step1_eyebrow' => 'Doná en 2 pasos',
            'step1_title_before' => 'Construyamos juntos un',
            'step1_title_highlight' => 'baño digno',
            'step1_title_after' => '.',
            'step1_lede' => 'Cada donación es una familia que deja de defecar al aire libre.',
            'saved_banner_text' => 'Tus datos están guardados. Cuando quieras, completá tu donación.',
            'step1_impact_text' => 'Con tu donación, una familia accede a un baño digno por primera vez.',
            'email_hint' => 'Te enviaremos el comprobante.',
            'dni_hint' => 'Requerido por Mercado Pago para identificar el pago.',
            'telefono_hint' => 'Solo si querés que te contactemos.',
            'step1_button' => 'Continuar',
            'step1_reassure' => 'Tus datos están protegidos. No los compartimos con terceros.',
            'amount_presets' => '1500,5000,15000,50000',
            'default_amount' => '5000',
            'min_amount' => '100',
            'step2_back_label' => 'Volver',
            'step2_eyebrow' => 'Paso 2 de 2',
            'step2_title' => 'Elegí tu monto y cómo donar',
            'step2_lede_before_name' => '¡Gracias',
            'step2_lede_after_name' => '! Definí cuánto querés aportar y elegí el método de pago.',
            'anonymous_name' => 'donante',
            'frequency_legend' => 'Frecuencia',
            'frequency_once_label' => 'Donación única',
            'frequency_monthly_label' => 'Mensual',
            'frequency_monthly_badge' => '+ impacto',
            'amount_legend' => 'Elegí un monto',
            'amount_monthly_suffix' => 'por mes',
            'custom_amount_placeholder' => 'Otro monto',
            'amount_error' => 'Elegí un monto válido',
            'methods_title' => 'Método de pago',
            'method_mp_name' => 'Mercado Pago',
            'method_mp_desc' => 'Tarjeta, dinero en cuenta o efectivo en Pago Fácil/Rapipago.',
            'method_mp_tags' => 'Recomendado,Sin comisión extra',
            'method_local_name' => 'Tarjeta local (Argentina)',
            'method_local_desc' => 'Crédito o débito emitida en Argentina. Hasta 3 cuotas sin interés.',
            'method_local_tags' => 'Crédito y débito',
            'method_intl_name' => 'Tarjeta internacional',
            'method_intl_desc' => 'Para donantes desde el exterior. Procesado en USD.',
            'method_intl_tags' => 'USD,Visa · Master · Amex',
            'method_bank_name' => 'Transferencia bancaria',
            'method_bank_desc' => 'Te mostramos los datos de la cuenta para hacer la transferencia.',
            'method_bank_tags' => 'CBU/Alias',
            'impact_tier_1' => 'una familia accede a productos de higiene por un mes',
            'impact_tier_2' => 'ayudás a financiar materiales para construir un baño digno',
            'impact_tier_3' => 'cubrís el inodoro y la ducha de un módulo sanitario',
            'impact_tier_4' => 'una familia accede a un baño digno por primera vez',
            'impact_tier_5' => 'construís un módulo sanitario completo para una familia',
            'step3_back_label' => 'Cambiar método de pago',
            'step3_loading_title' => 'Preparando tu donación...',
            'step3_loading_text_prefix' => 'Conectando con',
            'step3_loading_text_suffix' => 'Un segundo.',
            'step3_error_title' => 'Algo salió mal',
            'step3_error_text' => 'No pudimos conectar con Mercado Pago. Intentá de nuevo.',
            'step3_connection_error_text' => 'Error de conexión. Intentá de nuevo.',
            'step3_retry_label' => 'Volver a intentar',
            'bank_title' => 'Datos para transferencia',
            'bank_lede_prefix' => 'Donación de',
            'bank_lede_middle' => 'ARS a nombre de',
            'bank_block_title' => 'Datos de la cuenta',
            'bank_holder' => 'Asoc. Civil Módulo Sanitario',
            'bank_cuit' => '30-71234567-8',
            'bank_name' => 'Banco Galicia',
            'bank_cbu' => '0070123456789012345678',
            'bank_alias' => 'MODULO.SANITARIO.AR',
            'bank_note' => 'Enviá el comprobante a',
            'bank_email' => 'donaciones@modulosanitario.org',
            'restart_button' => 'Hacer otra donación',
            'modal_title_prefix' => '¡Listo,',
            'modal_title_suffix' => '! Guardamos tus datos.',
            'modal_lede_prefix' => 'Te enviamos un correo a',
            'modal_lede_suffix' => 'para que puedas retomar tu donación cuando quieras.',
            'modal_card_title' => '¿Querés donar ahora?',
            'modal_card_text' => 'Te lleva 1 minuto y tu aporte se convierte hoy mismo en materiales para construir un baño digno.',
            'modal_donate_now' => 'Sí, donar ahora',
            'modal_donate_later' => 'Donar más tarde',
            'modal_footer' => 'Tus datos están protegidos.',
            'trust_1_title' => 'Pago seguro',
            'trust_1_text' => 'SSL 256-bit',
            'trust_2_title' => 'Sitio verificado',
            'trust_2_text' => 'PCI-DSS · MP',
            'trust_3_title' => 'ONG inscripta',
            'trust_3_text' => 'IGJ · Ley 27.260',
            'footer_text' => 'Asoc. Civil sin fines de lucro · Buenos Aires, Argentina',
            'footer_seal_1' => 'SSL Seguro',
            'footer_seal_2' => 'PCI-DSS',
            'footer_seal_3' => 'ONG Verificada',
            'footer_link_1_label' => 'Términos',
            'footer_link_1_url' => '#',
            'footer_link_2_label' => 'Privacidad',
            'footer_link_2_url' => '#',
            'footer_link_3_label' => 'Contacto',
            'footer_link_3_url' => '#',
        ];
    }
}
