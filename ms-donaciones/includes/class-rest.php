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
                'success' => esc_url_raw($settings['mp_success_url'] ?? ''),
                'failure' => esc_url_raw($settings['mp_failure_url'] ?? ''),
                'pending' => esc_url_raw($settings['mp_pending_url'] ?? ''),
            ],
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
            set_transient('ms_don_mp_' . $external_reference, [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'email'    => $email,
                'dni'      => $dni,
                'telefono' => '',
                'monto'    => $monto,
                'tipo'     => 'mensual',
            ], 12 * HOUR_IN_SECONDS);

            return new WP_REST_Response([
                'success'            => true,
                'init_point'         => $init_point,
                'id'                 => sanitize_text_field($data['id'] ?? ''),
                'external_reference' => $external_reference,
            ], 200);
        }

        error_log('MS Donaciones - Error creando suscripción MP HTTP ' . $http_code . ': ' . substr(wp_remote_retrieve_body($response), 0, 1000));

        return new WP_REST_Response([
            'success'   => false,
            'error'     => 'Error creando suscripción.',
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

                error_log('MS Donaciones - MP Webhook payment ' . $id . ' status: ' . $status);

                if ($status === 'approved' && $external_reference) {
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
            return;
        }

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        if (!($contact_result['success'] ?? false)) {
            error_log('MS Donaciones - SF Contact upsert failed for payment ' . $payment_id . ': ' . ($contact_result['sf_error'] ?? $contact_result['message'] ?? ''));
            return;
        }

        $contact_id = $contact_result['contact_id'];
        $account_id = $contact_id ? self::sf_get_account_id($auth, $contact_id) : null;

        self::sf_create_opportunity($auth, $settings, $payment, $donor_data, $contact_id, $account_id, $amount, 'unico');
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

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            return;
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            error_log('MS Donaciones - SF auth failed for subscription ' . $preapproval_id);
            return;
        }

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        if (!($contact_result['success'] ?? false)) {
            error_log('MS Donaciones - SF Contact upsert failed for subscription ' . $preapproval_id);
            return;
        }

        $contact_id = $contact_result['contact_id'];
        $account_id = $contact_id ? self::sf_get_account_id($auth, $contact_id) : null;

        // Build a fake payment-shaped array so sf_create_opportunity can reuse the same logic
        $payment_stub = [
            'id'                 => $preapproval_id,
            'external_reference' => $external_reference,
            'transaction_amount' => $amount,
            'status'             => 'authorized',
        ];

        self::sf_create_opportunity($auth, $settings, $payment_stub, $donor_data, $contact_id, $account_id, $amount, 'mensual');
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

        if (($settings['sf_enabled'] ?? '0') !== '1') {
            return;
        }

        $auth = self::get_sf_auth($settings);
        if (!$auth) {
            error_log('MS Donaciones - SF auth failed for subscription payment ' . $payment_id);
            return;
        }

        // Look up the Salesforce Contact via the preapproval external reference stored at subscription creation
        // The recurring payment doesn't carry donor PII — we use what's still cached or stored in SF
        $payment_stub = [
            'id'                 => $payment_id,
            'external_reference' => 'suscripcion-cobro-' . $preapproval_id,
            'transaction_amount' => $amount,
            'status'             => 'processed',
        ];

        $donor_data = [
            'nombre'   => '',
            'apellido' => '',
            'email'    => sanitize_email($auth_payment['payer']['email'] ?? ''),
            'dni'      => '',
            'telefono' => '',
            'monto'    => $amount,
        ];

        $contact_result = self::sf_upsert_contact($auth, $settings, $donor_data);
        $contact_id = ($contact_result['success'] ?? false) ? ($contact_result['contact_id'] ?? null) : null;
        $account_id = $contact_id ? self::sf_get_account_id($auth, $contact_id) : null;

        self::sf_create_opportunity($auth, $settings, $payment_stub, $donor_data, $contact_id, $account_id, $amount, 'mensual');
    }

    /**
     * Donor returns here from the Mercado Pago subscription checkout (back_url).
     * Verifies the preapproval status and processes it immediately, so the Salesforce
     * Opportunity is created without waiting for the webhook. Idempotent: if the webhook
     * also fires, the lock in handle_authorized_subscription prevents a duplicate.
     */
    public static function retorno_suscripcion($request) {
        $preapproval_id = sanitize_text_field($request->get_param('preapproval_id') ?? '');
        $settings       = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        $access_token = sanitize_text_field($settings['mp_access_token'] ?? '');
        $success_url  = esc_url_raw($settings['mp_success_url'] ?? '') ?: home_url('/');

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
                }
            }
        }

        // Use wp_redirect (not wp_safe_redirect): the success URL is an admin-configured external
        // domain (e.g. the org's thank-you page), which wp_safe_redirect would reject and bounce
        // back to the local site. The URL is trusted config, not user input, so this is safe.
        wp_redirect($success_url);
        exit;
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

        return new WP_REST_Response([
            'success'    => true,
            'message'    => 'Datos recibidos correctamente',
            'data'       => $data,
            'crm_result' => $crm_result,
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

        $dni_field = sanitize_text_field($settings['sf_field_dni'] ?? '');
        $dni_value = $data['dni'] ?? '';
        $contact_id = null;

        // Search by DNI first (configured custom field)
        if ($dni_field && $dni_value) {
            $soql       = 'SELECT Id FROM Contact WHERE ' . $dni_field . " = '" . self::sf_escape($dni_value) . "' LIMIT 1";
            $query_resp = wp_remote_get($api_base . '/query/?q=' . rawurlencode($soql), [
                'headers' => $headers,
                'timeout' => 12,
            ]);
            if (!is_wp_error($query_resp) && wp_remote_retrieve_response_code($query_resp) < 300) {
                $result     = json_decode(wp_remote_retrieve_body($query_resp), true);
                $contact_id = $result['records'][0]['Id'] ?? null;
            }
        }

        // Fallback: search by email
        if (!$contact_id && !empty($data['email'])) {
            $soql       = "SELECT Id FROM Contact WHERE Email = '" . self::sf_escape($data['email']) . "' LIMIT 1";
            $query_resp = wp_remote_get($api_base . '/query/?q=' . rawurlencode($soql), [
                'headers' => $headers,
                'timeout' => 12,
            ]);
            if (!is_wp_error($query_resp) && wp_remote_retrieve_response_code($query_resp) < 300) {
                $result     = json_decode(wp_remote_retrieve_body($query_resp), true);
                $contact_id = $result['records'][0]['Id'] ?? null;
            }
        }

        $fields = self::build_sf_contact_fields($settings, $data);

        if (!$fields) {
            return [
                'enabled' => true,
                'success' => false,
                'message' => 'No hay campos de Contact configurados para enviar a Salesforce.',
            ];
        }

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
            return ['enabled' => true, 'success' => false, 'message' => $response->get_error_message()];
        }

        $http_status = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);
        $result      = json_decode($body, true);
        $success     = in_array($http_status, [200, 201, 204], true);

        if (!$contact_id && $success) {
            $contact_id = $result['id'] ?? null;
        }

        if (!$success) {
            error_log('MS Donaciones - SF Contact upsert HTTP ' . $http_status . ': ' . substr($body, 0, 500));
        }

        return [
            'enabled'    => true,
            'success'    => $success,
            'contact_id' => $contact_id,
            'message'    => $success ? 'Contact guardado en Salesforce.' : 'Error al guardar Contact en Salesforce.',
            'sf_error'   => $success ? null : self::extract_sf_error($body),
        ];
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

        $es_mensual = ($tipo === 'mensual');
        $stage      = sanitize_text_field($settings['sf_opp_stage'] ?? 'Closed Won');
        $fullname   = trim(($donor_data['nombre'] ?? '') . ' ' . ($donor_data['apellido'] ?? ''));
        $prefix     = $es_mensual ? 'Donacion MP Mensual - ' : 'Donacion MP Unica - ';
        $opp_name   = substr($prefix . ($fullname ?: 'Donante'), 0, 120);
        $payment_id = sanitize_text_field((string) ($payment['id'] ?? ''));

        $opp_fields = [
            'Name'      => $opp_name,
            'Amount'    => $amount,
            'CloseDate' => date('Y-m-d'),
            'StageName' => $stage,
            'Description' => 'Donacion ' . ($es_mensual ? 'mensual (suscripción)' : 'única') . ' via Mercado Pago. Payment ID: ' . $payment_id,
        ];

        // Optional: map the donation type to a Salesforce Opportunity "Type" picklist value.
        // Left empty by default so it never clashes with a restricted picklist; configure per-org if needed.
        $opp_type = sanitize_text_field($settings[$es_mensual ? 'sf_opp_type_mensual' : 'sf_opp_type_unico'] ?? '');
        if ($opp_type) {
            $opp_fields['Type'] = $opp_type;
        }

        if ($contact_id) {
            $opp_fields['ContactId'] = $contact_id;
        }

        if ($account_id) {
            $opp_fields['AccountId'] = $account_id;
        }

        $response    = wp_remote_post($api_base . '/sobjects/Opportunity', [
            'headers' => $headers,
            'body'    => wp_json_encode($opp_fields),
            'timeout' => 15,
        ]);
        $http_status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        $success     = $http_status >= 200 && $http_status < 300;

        if ($success) {
            error_log('MS Donaciones - SF Opportunity created for payment ' . $payment_id);
        } else {
            $err = is_wp_error($response) ? $response->get_error_message() : substr(wp_remote_retrieve_body($response), 0, 500);
            error_log('MS Donaciones - SF Opportunity failed for payment ' . $payment_id . ' HTTP ' . $http_status . ': ' . $err);
        }
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
