<?php

if (!defined('ABSPATH')) {
    exit;
}

class MS_Donaciones_REST {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('donacion/v1', '/guardar', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'guardar_cliente'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('donacion/v1', '/crear-preferencia', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'crear_preferencia_mercado_pago'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('donacion/v1', '/crear-suscripcion', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'crear_suscripcion_mercado_pago'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('donacion/v1', '/retorno-suscripcion', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'retorno_suscripcion'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('donacion/v1', '/retorno-pago', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'retorno_pago'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('donacion/v1', '/webhook', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'webhook_mercado_pago'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function crear_preferencia_mercado_pago($request) {
        $params = $request->get_json_params();
        $settings = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        $access_token = sanitize_text_field($settings['mp_access_token'] ?? '');
        $monto        = (float) ($params['monto'] ?? 0);
        $nombre       = sanitize_text_field($params['nombre'] ?? '');
        $apellido     = sanitize_text_field($params['apellido'] ?? '');
        $email        = sanitize_email($params['email'] ?? '');
        $dni          = sanitize_text_field($params['dni'] ?? '');

        if (!$access_token) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Falta configurar el Access Token de Mercado Pago.',
            ], 500);
        }

        if ($monto < 100) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Monto invalido.',
            ], 400);
        }

        $external_reference = 'donacion-' . time() . '-' . wp_generate_password(6, false, false);

        // Every Checkout Pro outcome returns through the plugin, so no /gracias
        // page is required and the payment status can be verified server-side.
        $return_base = self::build_public_rest_url($settings, 'retorno-pago');
        $success_back = $return_base
            ? add_query_arg('resultado', 'success', $return_base)
            : self::configured_or_home_url($settings, 'approved');
        $failure_back = $return_base
            ? add_query_arg('resultado', 'failure', $return_base)
            : self::configured_or_home_url($settings, 'rejected');
        $pending_back = $return_base
            ? add_query_arg('resultado', 'pending', $return_base)
            : self::configured_or_home_url($settings, 'pending');

        $body = [
            'items' => [
                [
                    'title'       => sanitize_text_field($settings['mp_item_title'] ?? 'Donación Módulo Sanitario'),
                    'quantity'    => 1,
                    'unit_price'  => $monto,
                    'currency_id' => 'ARS',
                ],
            ],
            'payer' => [
                'name'           => $nombre,
                'surname'        => $apellido,
                'email'          => $email,
                'identification' => [
                    'type'   => 'DNI',
                    'number' => $dni,
                ],
            ],
            'back_urls' => [
                'success' => $success_back,
                'failure' => $failure_back,
                'pending' => $pending_back,
            ],
            'auto_return'          => 'approved',
            'statement_descriptor' => sanitize_text_field($settings['mp_statement_descriptor'] ?? 'MODULO SANITARIO'),
            'external_reference'   => $external_reference,
            'notification_url'     => esc_url_raw($settings['mp_webhook_url'] ?? ''),
            'metadata'             => [
                'donor_nombre'   => $nombre,
                'donor_apellido' => $apellido,
                'donor_email'    => $email,
                'donor_dni'      => $dni,
            ],
        ];

        $response = wp_remote_post('https://api.mercadopago.com/checkout/preferences', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Error conectando con Mercado Pago.',
                'detalle' => $response->get_error_message(),
            ], 500);
        }

        $data      = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);
        $init_point = str_starts_with($access_token, 'TEST-')
            ? ($data['sandbox_init_point'] ?? $data['init_point'] ?? null)
            : ($data['init_point'] ?? $data['sandbox_init_point'] ?? null);

        if ($init_point) {
            // Store donor data so the webhook can link the payment to a Salesforce Contact
            set_transient('ms_don_mp_' . $external_reference, [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'email'    => $email,
                'dni'      => $dni,
                'telefono' => '',
                'monto'    => $monto,
            ], 12 * HOUR_IN_SECONDS);

            return new WP_REST_Response([
                'success'            => true,
                'init_point'         => $init_point,
                'id'                 => sanitize_text_field($data['id'] ?? ''),
                'external_reference' => $external_reference,
            ], 200);
        }

        error_log('MS Donaciones - Error creando preferencia MP HTTP ' . $http_code . ': ' . substr(wp_remote_retrieve_body($response), 0, 1000));

        return new WP_REST_Response([
            'success'   => false,
            'error'     => 'Error creando preferencia.',
            'detalle'   => $data,
            'http_code' => $http_code,
        ], 500);
    }

    public static function crear_suscripcion_mercado_pago($request) {
        $params = $request->get_json_params();
        $settings = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        $access_token = sanitize_text_field($settings['mp_access_token'] ?? '');
        $monto        = (float) ($params['monto'] ?? 0);
        $nombre       = sanitize_text_field($params['nombre'] ?? '');
        $apellido     = sanitize_text_field($params['apellido'] ?? '');
        $email        = sanitize_email($params['email'] ?? '');
        $dni          = sanitize_text_field($params['dni'] ?? '');

        if (!$access_token) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Falta configurar el Access Token de Mercado Pago.',
            ], 500);
        }

        if ($monto < 100) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Monto inválido.',
            ], 400);
        }

        $external_reference = 'suscripcion-' . time() . '-' . wp_generate_password(6, false, false);

        // back_url points to our own return endpoint so we can process the subscription the moment the
        // donor comes back from Mercado Pago, without depending on the (often-delayed) webhook. MP requires
        // an HTTPS URL here, so we reuse the public host from the configured webhook URL (ngrok / prod domain).
        $back_url = self::build_public_rest_url($settings, 'retorno-suscripcion');
        if (!$back_url) {
            $back_url = esc_url_raw($settings['mp_success_url'] ?? '');
        }

        $body = [
            'reason'         => sanitize_text_field($settings['mp_item_title'] ?? 'Donación mensual Módulo Sanitario'),
            'external_reference' => $external_reference,
            'payer_email'    => $email,
            'back_url'       => $back_url,
            'notification_url' => esc_url_raw($settings['mp_webhook_url'] ?? ''),
            'auto_recurring' => [
                'frequency'          => 1,
                'frequency_type'     => 'months',
                'transaction_amount' => $monto,
                'currency_id'        => 'ARS',
            ],
        ];

        error_log('MS Donaciones - Preapproval request: ref=' . $external_reference . ' monto=' . $monto . ' ARS | token_prefix: ' . substr($access_token, 0, 8));

        $response = wp_remote_post('https://api.mercadopago.com/preapproval', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Error conectando con Mercado Pago.',
                'detalle' => $response->get_error_message(),
            ], 500);
        }

        $data      = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);
        $init_point = $data['init_point'] ?? null;

        if ($init_point) {
            $donor_data = [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'email'    => $email,
                'dni'      => $dni,
                'telefono' => '',
                'monto'    => $monto,
                'tipo'     => 'mensual',
                'external_reference' => $external_reference,
            ];
            set_transient('ms_don_mp_' . $external_reference, $donor_data, 12 * HOUR_IN_SECONDS);

            $preapproval_id = sanitize_text_field($data['id'] ?? '');
            if ($preapproval_id) {
                $donor_data['preapproval_id'] = $preapproval_id;
                // Persist donor data keyed by preapproval_id so recurring charges, which may not
                // include donor PII, can still be linked to the correct Salesforce Contact.
                update_option('ms_don_sub_donor_' . $preapproval_id, $donor_data, false);
            }

            return new WP_REST_Response([
                'success'            => true,
                'init_point'         => $init_point,
                'id'                 => sanitize_text_field($data['id'] ?? ''),
                'external_reference' => $external_reference,
            ], 200);
        }

        error_log('MS Donaciones - Error creando suscripción MP HTTP ' . $http_code . ': ' . substr(wp_remote_retrieve_body($response), 0, 1000));

        $api_error = sanitize_text_field($data['message'] ?? '');
        if (stripos($api_error, 'Both payer and collector must be real or test users') !== false) {
            $api_error = 'Para probar una suscripción, el email del formulario debe ser exactamente el de un usuario comprador de prueba de Mercado Pago, distinto del usuario vendedor. No mezcles usuarios reales y de prueba.';
        }

        return new WP_REST_Response([
            'success'   => false,
            'error'     => $api_error ?: 'Error creando suscripción.',
            'detalle'   => $data,
            'http_code' => $http_code,
        ], 500);
    }

    public static function webhook_mercado_pago($request) {
        $params = $request->get_json_params();
        $topic  = sanitize_text_field($params['type'] ?? $params['topic'] ?? $request->get_param('type') ?? $request->get_param('topic') ?? '');
        $id     = sanitize_text_field($params['data']['id'] ?? $params['id'] ?? $request->get_param('id') ?? '');

        $settings     = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        $access_token = sanitize_text_field($settings['mp_access_token'] ?? '');

        if ($topic === 'payment' && $id && $access_token) {
            $response = wp_remote_get('https://api.mercadopago.com/v1/payments/' . rawurlencode($id), [
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
                'timeout' => 15,
            ]);

            if (!is_wp_error($response)) {
                $payment            = json_decode(wp_remote_retrieve_body($response), true);
                $status             = $payment['status'] ?? 'unknown';
                $external_reference = sanitize_text_field($payment['external_reference'] ?? '');
                $operation_type     = sanitize_text_field($payment['operation_type'] ?? '');
                $is_recurring       = $operation_type === 'recurring_payment'
                    || !empty($payment['metadata']['preapproval_id']);

                error_log('MS Donaciones - MP Webhook payment ' . $id . ' status: ' . $status);

                // Recurring charges are handled by subscription_authorized_payment, which
                // provides the Preapproval ID and avoids recording the same charge twice.
                if ($is_recurring) {
                    error_log('MS Donaciones - Recurring payment event ' . $id . ' deferred to subscription_authorized_payment.');
                } elseif ($status === 'approved' && $external_reference) {
                    self::handle_approved_payment($settings, $payment, $external_reference);
                }
            }
        }

        // Suscripción autorizada por primera vez
        if ($topic === 'subscription_preapproval' && $id && $access_token) {
            $response = wp_remote_get('https://api.mercadopago.com/preapproval/' . rawurlencode($id), [
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
                'timeout' => 15,
            ]);

            if (!is_wp_error($response)) {
                $preapproval        = json_decode(wp_remote_retrieve_body($response), true);
                $status             = $preapproval['status'] ?? 'unknown';
                $external_reference = sanitize_text_field($preapproval['external_reference'] ?? '');

                error_log('MS Donaciones - MP Webhook subscription_preapproval ' . $id . ' status: ' . $status);

                if ($status === 'authorized' && $external_reference) {
                    self::handle_authorized_subscription($settings, $preapproval, $external_reference);
                } else {
                    self::handle_subscription_status_update($settings, $preapproval, $external_reference);
                }
            }
        }

        // Cobro mensual ejecutado dentro de una suscripción activa
        if ($topic === 'subscription_authorized_payment' && $id && $access_token) {
            $response = wp_remote_get('https://api.mercadopago.com/authorized_payments/' . rawurlencode($id), [
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
                'timeout' => 15,
            ]);

            if (!is_wp_error($response)) {
                $auth_payment       = json_decode(wp_remote_retrieve_body($response), true);
                $preapproval_id     = sanitize_text_field($auth_payment['preapproval_id'] ?? '');
                $status             = $auth_payment['status'] ?? 'unknown';

                error_log('MS Donaciones - MP Webhook subscription_authorized_payment ' . $id . ' status: ' . $status);

                if ($status === 'processed' && $preapproval_id) {
                    self::handle_subscription_payment($settings, $auth_payment, $preapproval_id);
                }
            }
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    private static function handle_approved_payment($settings, $payment, $external_reference) {
        $payment_id = sanitize_text_field((string) ($payment['id'] ?? ''));

        // Idempotency: use atomic INSERT IGNORE to block concurrent webhooks.
        global $wpdb;
        $lock_option = 'ms_don_lock_' . $payment_id;
        $inserted = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
            $lock_option, '1', 'no'
        ) );
        if ( ! $inserted ) {
            error_log( 'MS Donaciones - Payment ' . $payment_id . ' already processing, skipping.' );
            return;
        }

        // Donor data stored at preference-creation time; fall back to payer info from MP
        $donor_data = get_transient('ms_don_mp_' . $external_reference);
        if (!$donor_data) {
            $donor_data = [
                'nombre'   => sanitize_text_field($payment['payer']['first_name'] ?? ''),
                'apellido' => sanitize_text_field($payment['payer']['last_name'] ?? ''),
                'email'    => sanitize_email($payment['payer']['email'] ?? ''),
                'dni'      => sanitize_text_field($payment['payer']['identification']['number'] ?? ''),
                'telefono' => '',
                'monto'    => (float) ($payment['transaction_amount'] ?? 0),
            ];
        }

        $amount = (float) ($payment['transaction_amount'] ?? $donor_data['monto'] ?? 0);

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            return;
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            error_log('MS Donaciones - SF auth failed for payment ' . $payment_id);
            delete_option($lock_option);
            return;
        }

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        if (!($contact_result['success'] ?? false)) {
            error_log('MS Donaciones - SF Contact upsert failed for payment ' . $payment_id . ': ' . ($contact_result['sf_error'] ?? $contact_result['message'] ?? ''));
            delete_option($lock_option);
            return;
        }

        $contact_id = $contact_result['contact_id'];
        $account_id = $contact_id ? self::sf_get_account_id($auth, $contact_id) : null;

        $opportunity_created = self::sf_create_opportunity(
            $auth,
            $settings,
            $payment,
            $donor_data,
            $contact_id,
            $account_id,
            $amount,
            'unico'
        );
        if (!$opportunity_created) {
            delete_option($lock_option);
        }
    }

    private static function handle_authorized_subscription($settings, $preapproval, $external_reference) {
        $preapproval_id = sanitize_text_field((string) ($preapproval['id'] ?? ''));

        global $wpdb;
        $lock_option = 'ms_don_lock_sub_' . $preapproval_id;
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
            $lock_option, '1', 'no'
        ));
        if (!$inserted) {
            error_log('MS Donaciones - Subscription ' . $preapproval_id . ' already processed, skipping.');
            return;
        }

        $donor_data = get_transient('ms_don_mp_' . $external_reference);
        if (!$donor_data) {
            $donor_data = [
                'nombre'   => sanitize_text_field($preapproval['payer_first_name'] ?? ''),
                'apellido' => sanitize_text_field($preapproval['payer_last_name'] ?? ''),
                'email'    => sanitize_email($preapproval['payer_email'] ?? ''),
                'dni'      => '',
                'telefono' => '',
                'monto'    => (float) ($preapproval['auto_recurring']['transaction_amount'] ?? 0),
            ];
        }

        $amount = (float) ($preapproval['auto_recurring']['transaction_amount'] ?? $donor_data['monto'] ?? 0);

        // Make sure the donor mapping exists for future recurring charges (the charge events carry no PII).
        if ($preapproval_id) {
            $donor_data['preapproval_id'] = $preapproval_id;
            $donor_data['external_reference'] = $external_reference;
            update_option('ms_don_sub_donor_' . $preapproval_id, $donor_data, false);
        }

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            return;
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            error_log('MS Donaciones - SF auth failed for subscription ' . $preapproval_id);
            delete_option($lock_option);
            return;
        }

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        if (!($contact_result['success'] ?? false)) {
            error_log('MS Donaciones - SF Contact upsert failed for subscription ' . $preapproval_id);
            delete_option($lock_option);
            return;
        }

        self::sf_update_contact_subscription_fields(
            $auth,
            $settings,
            $contact_result['contact_id'] ?? '',
            $preapproval
        );

        // Authorization only confirms the subscription. It is not treated as money collected,
        // so no Opportunity is created here. Each processed authorized-payment callback creates
        // the corresponding recurring-payment Opportunity with its real payment ID.
        error_log(
            'MS Donaciones - SF Contact linked to authorized subscription '
            . $preapproval_id
            . '; waiting for authorized payment callback.'
        );
    }

    private static function handle_subscription_payment($settings, $auth_payment, $preapproval_id) {
        $payment_id = sanitize_text_field((string) ($auth_payment['id'] ?? ''));

        global $wpdb;
        $lock_option = 'ms_don_lock_subpay_' . $payment_id;
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
            $lock_option, '1', 'no'
        ));
        if (!$inserted) {
            error_log('MS Donaciones - Subscription payment ' . $payment_id . ' already processed, skipping.');
            return;
        }

        $amount = (float) ($auth_payment['transaction_amount'] ?? 0);

        error_log('MS Donaciones - Subscription payment ' . $payment_id . ' processed. Preapproval: ' . $preapproval_id . ' Amount: ' . $amount);

        // Versions prior to 1.0.1 used this flag to skip the first charge because an Opportunity
        // was created at subscription authorization time. Authorization is no longer counted as
        // collected money, so remove a stale flag without skipping the real payment.
        if (get_option('ms_don_sub_skipfirst_' . $preapproval_id)) {
            delete_option('ms_don_sub_skipfirst_' . $preapproval_id);
        }

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            return;
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            error_log('MS Donaciones - SF auth failed for subscription payment ' . $payment_id);
            delete_option($lock_option);
            return;
        }

        // Recurring charge, including the first one: the event may carry no donor PII, so recover it from
        // the mapping stored at subscription creation/authorization to link the right Salesforce Contact.
        $donor_data = get_option('ms_don_sub_donor_' . $preapproval_id);
        if (!is_array($donor_data) || empty($donor_data['email'])) {
            $donor_data = [
                'nombre'   => '',
                'apellido' => '',
                'email'    => sanitize_email($auth_payment['payer']['email'] ?? ''),
                'dni'      => '',
                'telefono' => '',
            ];
        }
        $donor_data['monto'] = $amount;

        $auth_payment['id'] = $payment_id;
        $auth_payment['preapproval_id'] = $preapproval_id;
        if (empty($auth_payment['external_reference'])) {
            $auth_payment['external_reference'] = sanitize_text_field(
                $donor_data['external_reference'] ?? ('suscripcion-cobro-' . $preapproval_id)
            );
        }

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        if (!($contact_result['success'] ?? false)) {
            error_log(
                'MS Donaciones - SF Contact upsert failed for recurring payment '
                . $payment_id
                . ': '
                . ($contact_result['sf_error'] ?? $contact_result['message'] ?? '')
            );
            delete_option($lock_option);
            return;
        }

        $contact_id = $contact_result['contact_id'] ?? null;
        $account_id = $contact_id ? self::sf_get_account_id($auth, $contact_id) : null;

        $opportunity_created = self::sf_create_opportunity(
            $auth,
            $settings,
            $auth_payment,
            $donor_data,
            $contact_id,
            $account_id,
            $amount,
            'recurrente'
        );
        if (!$opportunity_created) {
            delete_option($lock_option);
        }
    }

    /**
     * Donor returns here from the Mercado Pago subscription checkout (back_url).
     * Verifies the preapproval status and links the subscription to the Salesforce Contact.
     * The Opportunity is created only when Mercado Pago confirms a processed charge through
     * subscription_authorized_payment. Idempotency prevents duplicate subscription handling.
     */
    public static function retorno_suscripcion($request) {
        $preapproval_id = sanitize_text_field($request->get_param('preapproval_id') ?? '');
        $settings       = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        $access_token = sanitize_text_field($settings['mp_access_token'] ?? '');
        $result_state = 'subscription_error';

        if ($preapproval_id && $access_token) {
            $response = wp_remote_get('https://api.mercadopago.com/preapproval/' . rawurlencode($preapproval_id), [
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
                'timeout' => 15,
            ]);

            if (!is_wp_error($response)) {
                $preapproval        = json_decode(wp_remote_retrieve_body($response), true);
                $status             = $preapproval['status'] ?? 'unknown';
                $external_reference = sanitize_text_field($preapproval['external_reference'] ?? '');

                error_log('MS Donaciones - Retorno suscripcion ' . $preapproval_id . ' status: ' . $status);

                if ($status === 'authorized' && $external_reference) {
                    self::handle_authorized_subscription($settings, $preapproval, $external_reference);
                    $result_state = 'subscription_authorized';
                }
            }
        }

        wp_redirect(self::result_redirect_url(
            $settings,
            $result_state,
            ['preapproval_id' => $preapproval_id]
        ));
        exit;
    }

    /**
     * Donor returns here from the Mercado Pago Checkout Pro (single payment) success back_url.
     * Verifies the payment is approved and creates the Salesforce Opportunity right away, without
     * depending on the webhook. Idempotent: the lock in handle_approved_payment prevents a duplicate
     * if the webhook also fires.
     */
    public static function retorno_pago($request) {
        $payment_id = sanitize_text_field(
            $request->get_param('payment_id')
            ?? $request->get_param('collection_id')
            ?? ''
        );
        $settings     = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        $access_token     = sanitize_text_field($settings['mp_access_token'] ?? '');
        $requested_result = sanitize_key($request->get_param('resultado') ?? '');
        $result_state     = match ($requested_result) {
            'failure' => 'rejected',
            'pending' => 'pending',
            default   => 'error',
        };

        if ($payment_id && $access_token) {
            $response = wp_remote_get('https://api.mercadopago.com/v1/payments/' . rawurlencode($payment_id), [
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
                'timeout' => 15,
            ]);

            if (!is_wp_error($response)) {
                $payment            = json_decode(wp_remote_retrieve_body($response), true);
                $status             = $payment['status'] ?? 'unknown';
                $external_reference = sanitize_text_field($payment['external_reference'] ?? '');

                error_log('MS Donaciones - Retorno pago ' . $payment_id . ' status: ' . $status);

                if ($status === 'approved' && $external_reference) {
                    self::handle_approved_payment($settings, $payment, $external_reference);
                }

                $result_state = match ($status) {
                    'approved' => 'approved',
                    'pending', 'in_process', 'in_mediation' => 'pending',
                    'rejected', 'cancelled', 'refunded', 'charged_back' => 'rejected',
                    default => $result_state,
                };
            }
        }

        wp_redirect(self::result_redirect_url(
            $settings,
            $result_state,
            ['payment_id' => $payment_id]
        ));
        exit;
    }

    private static function configured_or_home_url($settings, $result_state) {
        $custom_urls_enabled = ($settings['mp_use_custom_result_urls'] ?? '0') === '1';
        $setting_key = match ($result_state) {
            'approved', 'subscription_authorized' => 'mp_success_url',
            'pending' => 'mp_pending_url',
            default => 'mp_failure_url',
        };
        $configured = $custom_urls_enabled
            ? esc_url_raw($settings[$setting_key] ?? '')
            : '';

        return $configured ?: home_url('/');
    }

    private static function result_redirect_url($settings, $result_state, $extra = []) {
        $destination = self::configured_or_home_url($settings, $result_state);

        if (($settings['mp_use_custom_result_urls'] ?? '0') === '1') {
            return $destination;
        }

        return add_query_arg(
            array_filter(
                array_merge(
                    ['donacion_estado' => $result_state],
                    $extra
                ),
                static fn($value) => $value !== '' && $value !== null
            ),
            $destination
        );
    }

    private static function handle_subscription_status_update($settings, $preapproval, $external_reference) {
        $preapproval_id = sanitize_text_field((string) ($preapproval['id'] ?? ''));
        $status = sanitize_text_field((string) ($preapproval['status'] ?? 'unknown'));

        if (!$preapproval_id) {
            return;
        }

        $donor_data = get_option('ms_don_sub_donor_' . $preapproval_id);
        if (!is_array($donor_data)) {
            $donor_data = get_transient('ms_don_mp_' . $external_reference);
        }
        if (!is_array($donor_data)) {
            $donor_data = [
                'nombre'   => sanitize_text_field($preapproval['payer_first_name'] ?? ''),
                'apellido' => sanitize_text_field($preapproval['payer_last_name'] ?? ''),
                'email'    => sanitize_email($preapproval['payer_email'] ?? ''),
                'dni'      => '',
                'telefono' => '',
            ];
        }

        $donor_data['preapproval_id'] = $preapproval_id;
        $donor_data['external_reference'] = $external_reference;
        update_option('ms_don_sub_donor_' . $preapproval_id, $donor_data, false);

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            error_log('MS Donaciones - Subscription ' . $preapproval_id . ' status changed to ' . $status . '; Salesforce disabled.');
            return;
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            error_log('MS Donaciones - SF auth failed updating subscription status ' . $preapproval_id);
            return;
        }

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        if (!($contact_result['success'] ?? false)) {
            error_log(
                'MS Donaciones - SF Contact update failed for subscription status '
                . $preapproval_id
                . ': '
                . ($contact_result['sf_error'] ?? $contact_result['message'] ?? '')
            );
            return;
        }

        self::sf_update_contact_subscription_fields(
            $auth,
            $settings,
            $contact_result['contact_id'] ?? '',
            $preapproval
        );
    }

    private static function sf_update_contact_subscription_fields($auth, $settings, $contact_id, $preapproval) {
        if (!$contact_id) {
            return false;
        }

        $preapproval_id = sanitize_text_field((string) ($preapproval['id'] ?? ''));
        $status = sanitize_text_field((string) ($preapproval['status'] ?? 'unknown'));
        $fields = [];
        $field_values = [
            'sf_contact_field_subscription_id' => $preapproval_id,
            'sf_contact_field_subscription_status' => $status,
        ];

        if (in_array($status, ['cancelled', 'canceled'], true)) {
            $cancelled_at = sanitize_text_field((string) (
                $preapproval['date_modified']
                ?? $preapproval['last_modified']
                ?? ''
            ));
            $field_values['sf_contact_field_subscription_cancelled_at'] = $cancelled_at ?: gmdate('c');
        }

        foreach ($field_values as $setting_key => $value) {
            $field_name = sanitize_text_field($settings[$setting_key] ?? '');
            if ($field_name && $value !== '' && self::sf_valid_field_name($field_name)) {
                $fields[$field_name] = $value;
            }
        }

        if (!$fields) {
            error_log(
                'MS Donaciones - Subscription '
                . $preapproval_id
                . ' status '
                . $status
                . ' received; no Contact subscription fields configured.'
            );
            return true;
        }

        $response = wp_remote_request(
            $auth['instance_url'] . '/services/data/v59.0/sobjects/Contact/' . rawurlencode($contact_id),
            [
                'method'  => 'PATCH',
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth['token'],
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode($fields),
                'timeout' => 12,
            ]
        );

        $http_status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        $success = $http_status >= 200 && $http_status < 300;

        if ($success) {
            error_log(
                'MS Donaciones - SF Contact '
                . $contact_id
                . ' subscription '
                . $preapproval_id
                . ' updated to '
                . $status
            );
        } else {
            $error = is_wp_error($response)
                ? $response->get_error_message()
                : substr(wp_remote_retrieve_body($response), 0, 500);
            error_log(
                'MS Donaciones - SF subscription status update failed for '
                . $preapproval_id
                . ' HTTP '
                . $http_status
                . ': '
                . $error
            );
        }

        return $success;
    }

    /**
     * Builds a public, HTTPS REST URL for the given route by reusing the host of the configured
     * webhook URL (ngrok in dev, the real domain in prod). Returns '' if no usable host is set.
     */
    private static function build_public_rest_url($settings, $route) {
        $webhook = $settings['mp_webhook_url'] ?? '';
        if (!$webhook) {
            return '';
        }
        $parts = wp_parse_url($webhook);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }
        $base = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        return esc_url_raw($base . '/wp-json/donacion/v1/' . $route);
    }

    public static function guardar_cliente($request) {
        $params = $request->get_json_params();

        $data = [
            'nombre'   => sanitize_text_field($params['nombre'] ?? ''),
            'apellido' => sanitize_text_field($params['apellido'] ?? ''),
            'email'    => sanitize_email($params['email'] ?? ''),
            'dni'      => sanitize_text_field($params['dni'] ?? ''),
            'telefono' => sanitize_text_field($params['telefono'] ?? ''),
            'monto'    => sanitize_text_field($params['monto'] ?? ''),
            'metodo'   => sanitize_text_field($params['metodo'] ?? ''),
        ];
        $crm_event = sanitize_text_field($params['crm_event'] ?? '');

        error_log('MS Donaciones - Cliente recibido: ' . wp_json_encode($data));

        $crm_result = $crm_event === 'step_1_completed'
            ? self::send_to_salesforce($data)
            : [
                'enabled' => false,
                'success' => null,
                'message' => 'CRM no disparado para este evento.',
            ];

        if (($crm_result['success'] ?? null) === false) {
            error_log('MS Donaciones - CRM error interno: ' . wp_json_encode($crm_result));
        }

        return new WP_REST_Response([
            'success'    => true,
            'message'    => 'Datos recibidos correctamente',
            'data'       => $data,
            'crm_result' => self::public_crm_result($crm_result),
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Salesforce integration
    // -------------------------------------------------------------------------

    private static function send_to_salesforce($data) {
        $settings = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            return [
                'enabled' => false,
                'success' => null,
                'message' => 'Salesforce desactivado.',
            ];
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            return [
                'enabled' => true,
                'success' => false,
                'message' => 'No se pudo autenticar con Salesforce. Verifica las credenciales en el panel de administracion.',
            ];
        }

        return self::sf_upsert_contact($auth, $settings, $data);
    }

    private static function public_crm_result($crm_result) {
        return [
            'enabled' => (bool) ($crm_result['enabled'] ?? false),
            'success' => $crm_result['success'] ?? null,
            'message' => (($crm_result['success'] ?? null) === false)
                ? 'No pudimos guardar tus datos. Intentá de nuevo.'
                : 'Datos recibidos correctamente.',
        ];
    }

    private static function get_sf_auth($settings) {
        $cached = get_transient('ms_donaciones_sf_auth');
        if (is_array($cached) && !empty($cached['token']) && !empty($cached['instance_url'])) {
            return $cached;
        }

        $consumer_key    = sanitize_text_field($settings['sf_consumer_key'] ?? '');
        $consumer_secret = sanitize_text_field($settings['sf_consumer_secret'] ?? '');
        $sandbox         = ($settings['sf_sandbox'] ?? '0') === '1';

        if (!$consumer_key || !$consumer_secret) {
            return null;
        }

        $auth_url = self::salesforce_auth_url($settings['sf_login_url'] ?? '', $sandbox);

        $response = wp_remote_post($auth_url, [
            'body' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $consumer_key,
                'client_secret' => $consumer_secret,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('MS Donaciones - SF auth error: ' . $response->get_error_message());
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['access_token']) || empty($body['instance_url'])) {
            error_log('MS Donaciones - SF auth failed: ' . ($body['error_description'] ?? $body['error'] ?? 'unknown'));
            return null;
        }

        $auth = [
            'token'        => $body['access_token'],
            'instance_url' => rtrim($body['instance_url'], '/'),
        ];

        set_transient('ms_donaciones_sf_auth', $auth, 55 * MINUTE_IN_SECONDS);

        return $auth;
    }

    private static function sanitize_sf_login_url($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }

        $parts = wp_parse_url($value);
        if (empty($parts['host'])) {
            return '';
        }

        $host = strtolower($parts['host']);
        return esc_url_raw('https://' . $host);
    }

    private static function salesforce_auth_url($login_url, $sandbox) {
        $login_url = self::sanitize_sf_login_url($login_url);

        if (!$login_url) {
            $login_url = $sandbox ? 'https://test.salesforce.com' : 'https://login.salesforce.com';
        }

        return rtrim($login_url, '/') . '/services/oauth2/token';
    }

    private static function sf_upsert_contact($auth, $settings, $data) {
        $api_base = $auth['instance_url'] . '/services/data/v59.0';
        $headers  = [
            'Authorization' => 'Bearer ' . $auth['token'],
            'Content-Type'  => 'application/json',
        ];

        $email       = strtolower(sanitize_email($data['email'] ?? ''));
        $email_field = sanitize_text_field($settings['sf_field_email'] ?? 'Email') ?: 'Email';
        $dni_field   = sanitize_text_field($settings['sf_field_dni'] ?? '');
        $dni_value   = sanitize_text_field($data['dni'] ?? '');
        $contact_id  = null;
        $match_by    = null;
        $lock_name   = null;

        // Email is the canonical identity for this integration. Always search it first
        // and fail closed if Salesforce cannot complete the lookup: creating a Contact
        // after a failed query could duplicate an existing donor.
        if ($email) {
            $email_lookup = self::sf_find_contact($api_base, $headers, $email_field, $email);
            if (!$email_lookup['success']) {
                return [
                    'enabled'  => true,
                    'success'  => false,
                    'message'  => 'No se pudo verificar si el Contact ya existe por email.',
                    'sf_error' => $email_lookup['error'],
                ];
            }
            $contact_id = $email_lookup['contact_id'];
            $match_by   = $contact_id ? 'email' : null;
        }

        // DNI remains a secondary identifier. It can connect a changed/new email to
        // an existing Contact, but it never takes precedence over an email match.
        if (!$contact_id && $dni_field && $dni_value) {
            if (!self::sf_valid_field_name($dni_field)) {
                return [
                    'enabled'  => true,
                    'success'  => false,
                    'message'  => 'El API Name configurado para DNI no es valido.',
                    'sf_error' => 'Invalid Salesforce field name: ' . $dni_field,
                ];
            }

            $dni_lookup = self::sf_find_contact($api_base, $headers, $dni_field, $dni_value);
            if (!$dni_lookup['success']) {
                return [
                    'enabled'  => true,
                    'success'  => false,
                    'message'  => 'No se pudo verificar si el Contact ya existe por DNI.',
                    'sf_error' => $dni_lookup['error'],
                ];
            }
            $contact_id = $dni_lookup['contact_id'];
            $match_by   = $contact_id ? 'dni' : null;
        }

        $fields = self::build_sf_contact_fields($settings, $data);

        if (!$fields) {
            return [
                'enabled' => true,
                'success' => false,
                'message' => 'No hay campos de Contact configurados para enviar a Salesforce.',
            ];
        }

        // Serialize Contact creation by normalized email and repeat the lookup after
        // acquiring the lock. This closes the common race between the step-1 save,
        // the return URL and the Mercado Pago webhook.
        if (!$contact_id && $email) {
            $lock_name = 'ms_don_sf_contact_lock_' . md5($email);
            if (!self::sf_acquire_lock($lock_name)) {
                return [
                    'enabled'  => true,
                    'success'  => false,
                    'message'  => 'El Contact para este email se esta procesando. Reintenta en unos segundos.',
                    'sf_error' => 'Concurrent Contact upsert blocked.',
                ];
            }

            $email_lookup = self::sf_find_contact($api_base, $headers, $email_field, $email);
            if (!$email_lookup['success']) {
                self::sf_release_lock($lock_name);
                return [
                    'enabled'  => true,
                    'success'  => false,
                    'message'  => 'No se pudo volver a verificar el Contact por email.',
                    'sf_error' => $email_lookup['error'],
                ];
            }

            if ($email_lookup['contact_id']) {
                $contact_id = $email_lookup['contact_id'];
                $match_by   = 'email';
            }
        }

        $contact_action = $contact_id ? 'updated' : 'created';

        if ($contact_id) {
            $response = wp_remote_request($api_base . '/sobjects/Contact/' . $contact_id, [
                'method'  => 'PATCH',
                'headers' => $headers,
                'body'    => wp_json_encode($fields),
                'timeout' => 12,
            ]);
        } else {
            $response = wp_remote_post($api_base . '/sobjects/Contact', [
                'headers' => $headers,
                'body'    => wp_json_encode($fields),
                'timeout' => 12,
            ]);
        }

        if (is_wp_error($response)) {
            self::sf_release_lock($lock_name);
            return ['enabled' => true, 'success' => false, 'message' => $response->get_error_message()];
        }

        $http_status = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);
        $result      = json_decode($body, true);
        $success     = in_array($http_status, [200, 201, 204], true);

        if (!$contact_id && $success) {
            $contact_id = $result['id'] ?? null;
        }

        self::sf_release_lock($lock_name);

        if (!$success) {
            error_log('MS Donaciones - SF Contact upsert HTTP ' . $http_status . ': ' . substr($body, 0, 500));
        }

        return [
            'enabled'    => true,
            'success'    => $success,
            'contact_id' => $contact_id,
            'action'     => $contact_action,
            'matched_by' => $match_by,
            'message'    => $success ? 'Contact guardado en Salesforce.' : 'Error al guardar Contact en Salesforce.',
            'sf_error'   => $success ? null : self::extract_sf_error($body),
        ];
    }

    private static function sf_find_contact($api_base, $headers, $field, $value) {
        if (!self::sf_valid_field_name($field)) {
            return [
                'success'    => false,
                'contact_id' => null,
                'error'      => 'Invalid Salesforce field name: ' . $field,
            ];
        }

        $soql = 'SELECT Id FROM Contact WHERE '
            . $field
            . " = '"
            . self::sf_escape($value)
            . "' ORDER BY CreatedDate ASC LIMIT 1";

        $response = wp_remote_get($api_base . '/query/?q=' . rawurlencode($soql), [
            'headers' => $headers,
            'timeout' => 12,
        ]);

        if (is_wp_error($response)) {
            return [
                'success'    => false,
                'contact_id' => null,
                'error'      => $response->get_error_message(),
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);

        if ($status < 200 || $status >= 300) {
            return [
                'success'    => false,
                'contact_id' => null,
                'error'      => self::extract_sf_error($body) ?: 'Salesforce query HTTP ' . $status,
            ];
        }

        $result = json_decode($body, true);

        return [
            'success'    => true,
            'contact_id' => $result['records'][0]['Id'] ?? null,
            'error'      => null,
        ];
    }

    private static function sf_valid_field_name($field) {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', (string) $field);
    }

    private static function sf_acquire_lock($lock_name) {
        if (!$lock_name) {
            return true;
        }

        $existing = (int) get_option($lock_name, 0);
        if ($existing && $existing < (time() - 60)) {
            delete_option($lock_name);
        }

        return add_option($lock_name, (string) time(), '', 'no');
    }

    private static function sf_release_lock($lock_name) {
        if ($lock_name) {
            delete_option($lock_name);
        }
    }

    private static function build_sf_contact_fields($settings, $data) {
        $field_map = [
            'sf_field_firstname' => ['nombre',   'FirstName'],
            'sf_field_lastname'  => ['apellido', 'LastName'],
            'sf_field_email'     => ['email',    'Email'],
            'sf_field_phone'     => ['telefono', 'MobilePhone'],
            'sf_field_dni'       => ['dni',      ''],
        ];

        $fields = [];
        foreach ($field_map as $setting_key => [$data_key, $default]) {
            $sf_field = sanitize_text_field($settings[$setting_key] ?? $default);
            $value    = $data[$data_key] ?? '';
            if ($sf_field && $value !== '') {
                $fields[$sf_field] = $value;
            }
        }

        return $fields;
    }

    private static function sf_get_account_id($auth, $contact_id) {
        $soql     = "SELECT AccountId FROM Contact WHERE Id = '" . self::sf_escape($contact_id) . "' LIMIT 1";
        $response = wp_remote_get(
            $auth['instance_url'] . '/services/data/v59.0/query/?q=' . rawurlencode($soql),
            [
                'headers' => ['Authorization' => 'Bearer ' . $auth['token']],
                'timeout' => 12,
            ]
        );

        if (is_wp_error($response)) {
            return null;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return $result['records'][0]['AccountId'] ?? null;
    }

    private static function sf_create_opportunity($auth, $settings, $payment, $donor_data, $contact_id, $account_id, $amount, $tipo = 'unico') {
        $api_base = $auth['instance_url'] . '/services/data/v59.0';
        $headers  = [
            'Authorization' => 'Bearer ' . $auth['token'],
            'Content-Type'  => 'application/json',
        ];

        $is_recurring       = ($tipo === 'recurrente');
        $payment_kind       = $is_recurring ? 'PAGO_RECURRENTE' : 'PAGO_PUNTUAL';
        $payment_kind_name  = $is_recurring ? 'Pago recurrente MP' : 'Pago puntual MP';
        $stage              = sanitize_text_field($settings['sf_opp_stage'] ?? 'Closed Won');
        $fullname           = trim(($donor_data['nombre'] ?? '') . ' ' . ($donor_data['apellido'] ?? ''));
        $authorized_payment_id = $is_recurring
            ? sanitize_text_field((string) ($payment['id'] ?? ''))
            : '';
        $payment_id = sanitize_text_field((string) (
            $payment['payment_id']
            ?? $payment['id']
            ?? ''
        ));
        $subscription_id    = sanitize_text_field((string) ($payment['preapproval_id'] ?? ''));
        $external_reference = sanitize_text_field((string) ($payment['external_reference'] ?? ''));
        $status             = sanitize_text_field((string) ($payment['status'] ?? ''));
        $status_detail      = sanitize_text_field((string) ($payment['status_detail'] ?? ''));
        $currency           = sanitize_text_field((string) ($payment['currency_id'] ?? 'ARS'));
        $payment_method     = sanitize_text_field((string) (
            $payment['payment_method_id']
            ?? $payment['payment_method']['id']
            ?? ''
        ));
        $payment_type = sanitize_text_field((string) (
            $payment['payment_type_id']
            ?? $payment['payment_method']['type']
            ?? ''
        ));
        $installments = absint($payment['installments'] ?? 0);
        $payment_date = sanitize_text_field((string) (
            $payment['date_approved']
            ?? $payment['payment_date']
            ?? $payment['date_created']
            ?? ''
        ));
        $close_date = self::sf_date_from_payment($payment_date);
        $id_suffix  = $payment_id ? ' #' . $payment_id : '';
        $opp_name   = substr(
            $payment_kind_name . $id_suffix . ' - ' . ($fullname ?: 'Donante'),
            0,
            120
        );

        $description_lines = [
            'Origen: Mercado Pago',
            'Tipo: ' . $payment_kind,
            'Payment ID: ' . ($payment_id ?: 'no informado'),
        ];
        if ($is_recurring) {
            $description_lines[] = 'Preapproval / Subscription ID: ' . ($subscription_id ?: 'no informado');
            if ($authorized_payment_id && $authorized_payment_id !== $payment_id) {
                $description_lines[] = 'Authorized Payment ID: ' . $authorized_payment_id;
            }
        }
        if ($external_reference) {
            $description_lines[] = 'External reference: ' . $external_reference;
        }
        $description_lines[] = 'Estado: ' . ($status ?: 'no informado');
        if ($status_detail) {
            $description_lines[] = 'Detalle de estado: ' . $status_detail;
        }
        $description_lines[] = 'Importe: ' . number_format((float) $amount, 2, '.', '') . ' ' . $currency;
        if ($payment_method) {
            $description_lines[] = 'Medio de pago: ' . $payment_method;
        }
        if ($payment_type) {
            $description_lines[] = 'Tipo de medio de pago: ' . $payment_type;
        }
        if ($installments) {
            $description_lines[] = 'Cuotas: ' . $installments;
        }
        if ($payment_date) {
            $description_lines[] = 'Fecha informada por Mercado Pago: ' . $payment_date;
        }

        $opp_fields = [
            'Name'        => $opp_name,
            'Amount'      => $amount,
            'CloseDate'   => $close_date,
            'StageName'   => $stage,
            'Description' => implode("\n", $description_lines),
        ];

        $opp_type_key = $is_recurring ? 'sf_opp_type_recurrente' : 'sf_opp_type_unico';
        $opp_type = sanitize_text_field(
            $settings[$opp_type_key]
            ?: ($is_recurring ? 'Donación recurrente' : 'Donación puntual')
        );
        if ($opp_type) {
            $opp_fields['Type'] = $opp_type;
        }
        if ($account_id) {
            $opp_fields['AccountId'] = $account_id;
        }

        $custom_field_map = [
            'sf_opp_field_payment_id'         => $payment_id,
            'sf_opp_field_subscription_id'    => $subscription_id,
            'sf_opp_field_external_reference' => $external_reference,
            'sf_opp_field_payment_kind'       => $payment_kind,
        ];
        foreach ($custom_field_map as $setting_key => $value) {
            $field_name = sanitize_text_field($settings[$setting_key] ?? '');
            if ($field_name && $value !== '' && self::sf_valid_field_name($field_name)) {
                $opp_fields[$field_name] = $value;
            }
        }

        $response = wp_remote_post($api_base . '/sobjects/Opportunity', [
            'headers' => $headers,
            'body'    => wp_json_encode($opp_fields),
            'timeout' => 15,
        ]);
        $http_status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        $success     = $http_status >= 200 && $http_status < 300;

        if ($success) {
            $result         = json_decode(wp_remote_retrieve_body($response), true);
            $opportunity_id = sanitize_text_field((string) ($result['id'] ?? ''));
            error_log(
                'MS Donaciones - SF Opportunity '
                . $opportunity_id
                . ' created for '
                . $payment_kind
                . ' payment '
                . $payment_id
                . ($subscription_id ? ' subscription ' . $subscription_id : '')
            );

            // Opportunity has no standard ContactId field.
            if ($opportunity_id && $contact_id) {
                self::sf_create_opportunity_contact_role(
                    $api_base,
                    $headers,
                    $opportunity_id,
                    $contact_id
                );
            }
        } else {
            $err = is_wp_error($response) ? $response->get_error_message() : substr(wp_remote_retrieve_body($response), 0, 500);
            error_log('MS Donaciones - SF Opportunity failed for payment ' . $payment_id . ' HTTP ' . $http_status . ': ' . $err);
        }

        return $success;
    }

    private static function sf_create_opportunity_contact_role($api_base, $headers, $opportunity_id, $contact_id) {
        $response = wp_remote_post($api_base . '/sobjects/OpportunityContactRole', [
            'headers' => $headers,
            'body'    => wp_json_encode([
                'OpportunityId' => $opportunity_id,
                'ContactId'     => $contact_id,
                'IsPrimary'     => true,
            ]),
            'timeout' => 12,
        ]);

        $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            $error = is_wp_error($response)
                ? $response->get_error_message()
                : substr(wp_remote_retrieve_body($response), 0, 500);
            error_log(
                'MS Donaciones - SF OpportunityContactRole failed for Opportunity '
                . $opportunity_id
                . ' and Contact '
                . $contact_id
                . ': '
                . $error
            );
        }
    }

    private static function sf_date_from_payment($payment_date) {
        if ($payment_date) {
            $timestamp = strtotime($payment_date);
            if ($timestamp !== false) {
                return gmdate('Y-m-d', $timestamp);
            }
        }

        return current_time('Y-m-d');
    }

    private static function sf_escape($value) {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value);
    }

    private static function extract_sf_error($body) {
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            if (!empty($decoded[0]['message'])) {
                return $decoded[0]['message'];
            }
            if (!empty($decoded[0]['errorCode'])) {
                return $decoded[0]['errorCode'];
            }
        }

        return $body ? substr($body, 0, 500) : null;
    }
}
