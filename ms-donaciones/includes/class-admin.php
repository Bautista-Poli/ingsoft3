<?php

if (!defined('ABSPATH')) {
    exit;
}

class MS_Donaciones_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('wp_ajax_ms_donaciones_test_salesforce', [__CLASS__, 'ajax_test_salesforce']);
        add_action('wp_ajax_ms_donaciones_test_mercadopago', [__CLASS__, 'ajax_test_mercadopago']);
    }

    public static function add_menu_page() {
        add_menu_page(
            'Donaciones MS',
            'Donaciones MS',
            'manage_options',
            'ms-donaciones',
            [__CLASS__, 'render_page'],
            'dashicons-heart',
            56
        );

        foreach (self::get_sections() as $slug => $section) {
            add_submenu_page(
                'ms-donaciones',
                'Donaciones MS - ' . $section['title'],
                $section['menu'],
                'manage_options',
                'ms-donaciones-' . $slug,
                [__CLASS__, 'render_page']
            );
        }
    }

    public static function register_settings() {
        register_setting(
            'ms_donaciones_settings',
            'ms_donaciones_labels',
            [
                'type'              => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize_labels'],
                'default'           => MS_Donaciones_Shortcodes::default_labels(),
            ]
        );
    }

    public static function sanitize_labels($input) {
        $defaults = MS_Donaciones_Shortcodes::default_labels();
        $input = is_array($input) ? $input : [];
        $output = array_merge(
            $defaults,
            get_option('ms_donaciones_labels', [])
        );

        foreach ($input as $key => $value) {
            if (!array_key_exists($key, $defaults) && !preg_match('/^impact_tier_\d+$/', $key)) {
                continue;
            }

            if (in_array($key, ['mp_success_url', 'mp_failure_url', 'mp_pending_url'], true)) {
                $output[$key] = esc_url_raw($value);
                continue;
            }

            if ($key === 'sf_login_url') {
                $sanitized = self::sanitize_sf_login_url($value);
                if (($output[$key] ?? '') !== $sanitized) {
                    $output['sf_connection_status'] = 'unknown';
                    $output['sf_connection_message'] = '';
                    delete_transient('ms_donaciones_sf_auth');
                }
                $output[$key] = $sanitized;
                continue;
            }

            if (str_ends_with($key, '_url') || $key === 'foto_url') {
                $value = trim((string) $value);
                $output[$key] = str_starts_with($value, '/') || str_starts_with($value, '#')
                    ? sanitize_text_field($value)
                    : esc_url_raw($value);
                continue;
            }

            if ($key === 'bank_email') {
                $output[$key] = sanitize_email($value);
                continue;
            }

            if (in_array($key, ['default_amount', 'min_amount'], true)) {
                $output[$key] = (string) max(0, absint($value));
                continue;
            }

            if (in_array($key, ['sf_enabled', 'sf_sandbox'], true)) {
                $sanitized = !empty($value) ? '1' : '0';
                if (($output[$key] ?? '') !== $sanitized) {
                    $output['sf_connection_status'] = 'unknown';
                    $output['sf_connection_message'] = '';
                    delete_transient('ms_donaciones_sf_auth');
                }
                $output[$key] = $sanitized;
                continue;
            }

            if ($key === 'mp_use_custom_result_urls') {
                $output[$key] = !empty($value) ? '1' : '0';
                continue;
            }

            if ($key === 'amount_presets') {
                $amounts = array_filter(array_map('absint', preg_split('/[\s,;]+/', (string) $value)));
                $output[$key] = implode(',', $amounts);
                continue;
            }

            if (in_array($key, ['sf_consumer_key', 'sf_consumer_secret', 'sf_username', 'sf_password_token'], true)) {
                $sanitized = sanitize_text_field($value);
                if (($output[$key] ?? '') !== $sanitized) {
                    $output['sf_connection_status'] = 'unknown';
                    $output['sf_connection_message'] = '';
                    delete_transient('ms_donaciones_sf_auth');
                }
                $output[$key] = $sanitized;
                continue;
            }

            if ($key === 'mp_access_token') {
                $sanitized = sanitize_text_field($value);
                if (($output[$key] ?? '') !== $sanitized) {
                    $output['mp_connection_status'] = 'unknown';
                    $output['mp_connection_message'] = '';
                }
                $output[$key] = $sanitized;
                continue;
            }

            $output[$key] = sanitize_text_field($value);
        }

        return $output;
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.');
        }

        $labels = array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
        foreach ([
            'sf_opp_type_unico' => 'Donación puntual',
            'sf_opp_type_recurrente' => 'Donación recurrente',
        ] as $key => $default_value) {
            if (($labels[$key] ?? '') === '') {
                $labels[$key] = $default_value;
            }
        }
        $sections = self::get_sections($labels);
        $current_slug = self::current_section_slug($sections);
        $section = $sections[$current_slug];
        $text_sections = self::get_text_sections();
        $current_text_slug = self::current_text_section_slug($text_sections);

        if ($current_slug === 'textos') {
            $text_section = $text_sections[$current_text_slug];
            $section['title'] = 'Textos visibles - ' . $text_section['title'];
            $section['description'] = $text_section['description'];
            $section['fields'] = $text_section['fields'];
        }

        $prev_next = self::get_prev_next($sections, $current_slug);
        ?>
        <div class="wrap ms-donaciones-admin">
            <h1>Donaciones MS</h1>

            <p>
                Configuración del formulario embebido con <code>[formulario_donacion]</code>.
                Cada página corresponde a una parte del recorrido del donante.
            </p>

            <?php settings_errors('ms_donaciones_settings'); ?>
            <?php self::render_styles(); ?>
            <?php self::render_tabs($sections, $current_slug); ?>

            <form method="post" action="options.php">
                <?php settings_fields('ms_donaciones_settings'); ?>

                <section class="ms-card">
                    <div class="ms-section-head">
                        <div>
                            <span class="ms-step-label"><?php echo esc_html($section['step']); ?></span>
                            <h2><?php echo esc_html($section['title']); ?></h2>
                            <p><?php echo esc_html($section['description']); ?></p>
                        </div>
                        <button type="submit" class="button button-primary button-hero">
                            Guardar esta sección
                        </button>
                    </div>

                    <?php if ($current_slug === 'textos') : ?>
                        <?php self::render_text_section_selector($text_sections, $current_text_slug); ?>
                        <?php if (!empty($text_section['notice'])) : ?>
                            <p class="ms-field-note"><?php echo wp_kses_post($text_section['notice']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($current_slug === 'crm') : ?>
                        <div class="notice notice-warning inline ms-section-reminder">
                            <p>
                                <strong>Antes de terminar:</strong>
                                guarda esta sección y revisa al final
                                <a href="#ms-salesforce-custom-fields-guide">la guía para crear campos custom</a>
                                y
                                <a href="#ms-crm-connection-test">la prueba de conexión con Salesforce</a>.
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($current_slug === 'mercadopago') : ?>
                        <div class="notice notice-info inline ms-section-reminder">
                            <p>
                                <strong>Configuración recomendada:</strong>
                                revisa al final
                                <a href="#ms-mercadopago-setup-guide">la guía de Mercado Pago Developers</a>
                                y luego ejecuta
                                <a href="#ms-mercadopago-connection-test">la prueba de conexión</a>.
                            </p>
                        </div>
                    <?php endif; ?>

                    <table class="form-table" role="presentation">
                        <?php foreach ($section['fields'] as $key => $field) : ?>
                            <?php
                            $label = $field[0] ?? $key;
                            $type = $field[1] ?? 'text';
                            $description = $field[2] ?? '';
                            ?>
                            <?php if ($current_slug === 'crm' && $key === 'sf_field_firstname') : ?>
                                <tr class="ms-field-group-row">
                                    <th colspan="2">
                                        <div class="ms-field-group">
                                            <span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
                                            <div>
                                                <h3>Campos de Contact</h3>
                                                <p>Datos personales del donante. DNI pertenece a Contact y se usa, después del email, para localizar y actualizar una persona existente.</p>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            <?php endif; ?>
                            <?php if ($current_slug === 'crm' && $key === 'sf_opp_stage') : ?>
                                <tr class="ms-field-group-row">
                                    <th colspan="2">
                                        <div class="ms-field-group">
                                            <span class="dashicons dashicons-chart-line" aria-hidden="true"></span>
                                            <div>
                                                <h3>Campos de Opportunity</h3>
                                                <p>Datos de cada cobro confirmado. Se crea una Opportunity por pago puntual o por cada cobro procesado de una suscripción.</p>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th scope="row">
                                    <label for="ms-donaciones-<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($label); ?>
                                    </label>
                                </th>
                                <td>
                                    <?php self::render_input($key, $type, $labels[$key] ?? '', $description); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <?php if ($current_slug === 'crm') : ?>
                        <?php self::render_salesforce_opportunity_guide(); ?>
                    <?php endif; ?>

                    <?php if ($current_slug === 'mercadopago') : ?>
                        <?php self::render_mercadopago_guide(); ?>
                    <?php endif; ?>

                    <?php if (!empty($section['help'])) : ?>
                        <?php self::render_help_box($section['help']); ?>
                    <?php endif; ?>

                    <?php self::render_connection_panel($current_slug, $labels); ?>
                </section>

                <div class="ms-save-bar">
                    <div class="ms-step-nav">
                        <?php if ($prev_next['prev']) : ?>
                            <a class="button" href="<?php echo esc_url(self::section_url($prev_next['prev'])); ?>">
                                &larr; Anterior
                            </a>
                        <?php endif; ?>

                        <?php if ($prev_next['next']) : ?>
                            <a class="button" href="<?php echo esc_url(self::section_url($prev_next['next'])); ?>">
                                Siguiente &rarr;
                            </a>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="button button-primary button-hero">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function get_sections($labels = null) {
        $impact_fields = self::get_impact_fields($labels);

        return [
            'textos' => [
                'menu' => 'Textos visibles',
                'step' => 'Contenido',
                'title' => 'Textos visibles',
                'description' => 'Textos editables del formulario agrupados por seccion.',
                'fields' => [],
            ],
            'media-links' => [
                'menu' => 'Media y links',
                'step' => 'General',
                'title' => 'Media y links',
                'description' => 'Imagen principal y URLs visibles del formulario.',
                'fields' => [
                    'foto_url' => ['URL de foto principal', 'url'],
                    'site_back_url' => ['URL volver al sitio', 'text', 'Puede ser relativa, por ejemplo /inicio.'],
                    'footer_link_1_url' => ['Footer link 1 - URL', 'text', 'Puede ser relativa o #.'],
                    'footer_link_2_url' => ['Footer link 2 - URL', 'text', 'Puede ser relativa o #.'],
                    'footer_link_3_url' => ['Footer link 3 - URL', 'text', 'Puede ser relativa o #.'],
                ],
            ],
            'crm' => [
                'menu' => 'Datos personales a CRM',
                'step' => 'Paso 1',
                'title' => 'Datos personales a CRM',
                'description' => 'Configuracion del envio automatico de los datos del primer paso a Salesforce (NPSP).',
                'fields' => [
                    'sf_enabled'         => ['Activar envio a Salesforce', 'checkbox'],
                    'sf_sandbox'         => ['Usar sandbox de Salesforce', 'checkbox', 'Actívalo únicamente si esta integración apunta a una organización Sandbox de Salesforce. Al activarlo y dejar vacía la URL de login, el plugin autentica contra test.salesforce.com. Normalmente se deja desactivado porque las credenciales productivas y las External Client Apps de producción usan login.salesforce.com o el My Domain productivo.'],
                    'sf_login_url'       => ['URL/Dominio de login', 'text', 'Opcional. Usa el My Domain de Salesforce si aplica. Ej: https://tu-dominio.my.salesforce.com. Si lo dejas vacio usa produccion o sandbox.'],
                    'sf_consumer_key'    => ['Consumer Key', 'password', 'Consumer Key de la Connected App en Salesforce.'],
                    'sf_consumer_secret' => ['Consumer Secret', 'password', 'Consumer Secret de la Connected App en Salesforce.'],
                    'sf_username'        => ['Usuario de Salesforce (no requerido)', 'text', 'No se utiliza con Client Credentials. Déjalo vacío si Consumer Key, Consumer Secret y Run As User están correctamente configurados en Salesforce.'],
                    'sf_password_token'  => ['Contraseña + Security Token (no requerido)', 'password', 'No se utiliza con Client Credentials. No hace falta completar contraseña ni Security Token cuando la aplicación tiene un Run As User configurado.'],
                    'sf_field_firstname' => ['API Name: Nombre (Contact)', 'text', 'Por defecto: FirstName. Cambialo solo si el campo es custom.'],
                    'sf_field_lastname'  => ['API Name: Apellido (Contact)', 'text', 'Por defecto: LastName.'],
                    'sf_field_email'     => ['API Name: Email (Contact)', 'text', 'Por defecto: Email.'],
                    'sf_field_phone'     => ['API Name: Telefono (Contact)', 'text', 'Por defecto: MobilePhone.'],
                    'sf_field_dni'       => ['API Name: DNI', 'text', 'Objeto: Contact. Campo custom usado para identificar al donante. Ej: npe01__Numero_de_Documento__c.'],
                    'sf_contact_field_subscription_id' => ['API Name: Mercado Pago Preapproval ID', 'text', 'Objeto: Contact. Opcional; guarda el ID de la suscripción activa o cancelada.'],
                    'sf_contact_field_subscription_status' => ['API Name: Estado de suscripción', 'text', 'Objeto: Contact. Opcional; guarda estados como authorized, paused, pending o cancelled.'],
                    'sf_contact_field_subscription_cancelled_at' => ['API Name: Fecha de cancelación', 'text', 'Objeto: Contact. Opcional; campo Fecha/Hora que se completa cuando Mercado Pago informa cancelled.'],
                    'sf_opp_stage'       => ['Stage', 'text', 'Objeto: Opportunity. Stage name para donaciones aprobadas por Mercado Pago. Por defecto: Closed Won.'],
                    'sf_opp_type_unico'  => ['Valor de Opportunity Type para pago puntual (opcional)', 'text', 'Escribe un valor que ya exista en el picklist Type de Salesforce, por ejemplo: Donación puntual. Aunque este input sea de texto, no crea una opción nueva en Salesforce. Déjalo vacío si no utilizas Opportunity Type.'],
                    'sf_opp_type_recurrente' => ['Valor de Opportunity Type para pago recurrente (opcional)', 'text', 'Escribe un valor que ya exista en el picklist Type de Salesforce, por ejemplo: Donación recurrente. Debe coincidir exactamente, incluyendo mayúsculas y acentos. Déjalo vacío si no utilizas Opportunity Type.'],
                    'sf_opp_field_payment_id' => ['API Name: Mercado Pago Payment ID', 'text', 'Objeto: Opportunity. Campo custom opcional donde guardar el ID del cobro.'],
                    'sf_opp_field_subscription_id' => ['API Name: Mercado Pago Preapproval ID', 'text', 'Objeto: Opportunity. Campo custom opcional donde guardar el ID de la suscripcion.'],
                    'sf_opp_field_external_reference' => ['API Name: External Reference', 'text', 'Objeto: Opportunity. Campo custom opcional para la referencia generada por WordPress.'],
                    'sf_opp_field_payment_kind' => ['API Name: Tipo de pago', 'text', 'Objeto: Opportunity. Campo custom opcional donde guardar PAGO_PUNTUAL o PAGO_RECURRENTE.'],
                ],
                'help' => [
                    'title' => 'Como conectar WordPress con Salesforce',
                    'items' => [
                        'En Salesforce crea una External Client App o Connected App y habilita OAuth.',
                        'Activa solamente el flujo de credenciales de cliente y el scope API.',
                        'La URL de callback puede ser cualquier URL HTTPS estable; este flujo no la utiliza.',
                        'Asigna un Client Credentials User / Run As User en las politicas OAuth.',
                        'Copia Consumer Key y Consumer Secret al panel de arriba.',
                        'Usa el My Domain terminado en my.salesforce.com; no uses lightning.force.com.',
                        'Usuario, contraseña y Security Token no se utilizan con client_credentials.',
                        'El API Name del campo DNI debe incluir __c si es un campo custom. Buscalo en Setup → Object Manager → Contact → Fields.',
                    ],
                    'link' => 'https://help.salesforce.com/s/articleView?id=sf.connected_app_create.htm',
                    'link_label' => 'Ver guia oficial de Salesforce',
                ],
            ],
            'montos' => [
                'menu' => 'Montos',
                'step' => 'Paso 2',
                'title' => 'Montos',
                'description' => 'Montos predefinidos y limites numericos.',
                'fields' => [
                    'amount_presets' => ['Montos predefinidos', 'text', 'Separar por coma. Ej: 1500,5000,15000,50000'],
                    'default_amount' => ['Monto inicial', 'number'],
                    'min_amount' => ['Monto minimo', 'number'],
                ],
            ],
            'impacto' => [
                'menu' => 'Impacto',
                'step' => 'Paso 2',
                'title' => 'Impacto por monto',
                'description' => 'Mensajes dinamicos del bloque "Con $X ARS...".',
                'fields' => $impact_fields,
            ],
            'mercadopago' => [
                'menu' => 'Mercado Pago',
                'step' => 'Paso 3',
                'title' => 'Mercado Pago',
                'description' => 'Configuracion server-side para crear preferencias de Checkout Pro.',
                'fields' => [
                    'mp_access_token' => ['Access Token', 'password', 'Copia el Access Token desde la sección de credenciales de prueba o producción de Mercado Pago Developers. El prefijo APP_USR- también puede aparecer en credenciales de prueba; verifica siempre la sección de origen. No se expone en el navegador.'],
                    'mp_webhook_url'  => ['URL de Webhook', 'url', 'URL publica para notificaciones de pago. En produccion: https://tu-sitio.com/wp-json/donacion/v1/webhook. En dev: URL de ngrok.'],
                    'mp_item_title' => ['Título del item'],
                    'mp_statement_descriptor' => ['Descriptor en resumen'],
                    'mp_use_custom_result_urls' => ['Usar páginas personalizadas de resultado', 'checkbox', 'Déjalo desactivado para que el plugin reconozca el resultado y vuelva automáticamente al formulario con un mensaje de aprobado, pendiente o rechazado. Actívalo sólo si ya existen páginas propias para esos estados.'],
                    'mp_success_url' => ['URL éxito (opcional)', 'url', 'Se usa únicamente si activas páginas personalizadas.'],
                    'mp_failure_url' => ['URL fallo (opcional)', 'url', 'Se usa únicamente si activas páginas personalizadas.'],
                    'mp_pending_url' => ['URL pendiente (opcional)', 'url', 'Se usa únicamente si activas páginas personalizadas.'],
                ],
            ],
            'transferencia' => [
                'menu' => 'Transferencia',
                'step' => 'Paso 3',
                'title' => 'Datos de transferencia',
                'description' => 'Datos bancarios y correo para comprobantes.',
                'fields' => [
                    'bank_holder' => ['Titular'],
                    'bank_cuit' => ['CUIT'],
                    'bank_name' => ['Banco'],
                    'bank_cbu' => ['CBU'],
                    'bank_alias' => ['Alias'],
                    'bank_email' => ['Email comprobantes', 'email'],
                ],
            ],
        ];
    }

    private static function get_impact_fields($labels = null) {
        $defaults = MS_Donaciones_Shortcodes::default_labels();
        $labels = is_array($labels) ? array_merge($defaults, $labels) : $defaults;
        $amounts = self::parse_amount_presets($labels['amount_presets'] ?? $defaults['amount_presets']);
        $fields = [];

        foreach ($amounts as $index => $amount) {
            $key = 'impact_tier_' . ($index + 1);
            $formatted_amount = number_format($amount, 0, ',', '.');

            $fields[$key] = [
                'Impacto para $' . $formatted_amount,
                'text',
                'Se muestra cuando la donación seleccionada es de $' . $formatted_amount . ' ARS o más.',
            ];
        }

        return $fields ?: [
            'impact_tier_1' => ['Impacto por defecto'],
        ];
    }

    private static function parse_amount_presets($value) {
        $amounts = array_filter(array_map('absint', preg_split('/[\s,;]+/', (string) $value)));
        $amounts = array_values(array_unique($amounts));
        sort($amounts, SORT_NUMERIC);

        return $amounts;
    }

    private static function get_text_sections() {
        return [
            'navegacion' => [
                'title' => 'Navegación',
                'description' => 'Textos superiores y etiquetas del progreso.',
                'notice' => 'Acá se edita el texto del link. Para cambiar la URL de destino, ir a <strong>Media y links</strong>.',
                'fields' => [
                    'site_back_label' => ['Texto volver al sitio'],
                    'stepper_1_label' => ['Paso 1'],
                    'stepper_2_label' => ['Paso 2'],
                    'stepper_3_label' => ['Paso 3'],
                ],
            ],
            'hero' => [
                'title' => 'Hero lateral',
                'description' => 'Textos de imagen, métricas y cita lateral.',
                'fields' => [
                    'hero_image_alt' => ['Alt de imagen'],
                    'hero_caption' => ['Texto sobre la foto principal'],
                    'hero_stat_1_number' => ['Métrica 1 - numero'],
                    'hero_stat_1_label' => ['Métrica 1 - texto'],
                    'hero_stat_2_number' => ['Métrica 2 - numero'],
                    'hero_stat_2_label' => ['Métrica 2 - texto'],
                    'hero_quote_text' => ['Cita'],
                    'hero_quote_author' => ['Autor de la cita'],
                ],
            ],
            'datos' => [
                'title' => 'Datos personales',
                'description' => 'Títulos, etiquetas, ayudas y botones del primer paso.',
                'fields' => [
                    'step1_eyebrow' => ['Etiqueta superior'],
                    'step1_title_before' => ['Título antes del destacado'],
                    'step1_title_highlight' => ['Título destacado'],
                    'step1_title_after' => ['Título después del destacado'],
                    'step1_lede' => ['Bajada'],
                    'saved_banner_text' => ['Banner datos guardados'],
                    'step1_impact_text' => ['Mensaje de impacto fijo'],
                    'nombre' => ['Label Nombre'],
                    'apellido' => ['Label Apellido'],
                    'email' => ['Label Email'],
                    'dni' => ['Label DNI'],
                    'telefono' => ['Label Teléfono'],
                    'email_hint' => ['Ayuda Email'],
                    'dni_hint' => ['Ayuda DNI'],
                    'telefono_hint' => ['Ayuda Teléfono'],
                    'step1_button' => ['Botón continuar'],
                    'step1_reassure' => ['Texto de seguridad'],
                    'step1_save_error' => ['Error al guardar datos'],
                ],
            ],
            'montos' => [
                'title' => 'Monto y frecuencia',
                'description' => 'Textos visibles del paso de monto y frecuencia.',
                'fields' => [
                    'step2_back_label' => ['Botón volver'],
                    'step2_eyebrow' => ['Etiqueta superior'],
                    'step2_title' => ['Título'],
                    'step2_lede_before_name' => ['Bajada antes del nombre'],
                    'step2_lede_after_name' => ['Bajada después del nombre'],
                    'anonymous_name' => ['Nombre fallback'],
                    'frequency_legend' => ['Título frecuencia'],
                    'frequency_once_label' => ['Frecuencia única'],
                    'frequency_monthly_label' => ['Frecuencia mensual'],
                    'frequency_monthly_badge' => ['Badge mensual'],
                    'amount_legend' => ['Título montos'],
                    'amount_monthly_suffix' => ['Sufijo mensual'],
                    'custom_amount_placeholder' => ['Placeholder otro monto'],
                    'amount_error' => ['Error monto invalido'],
                    'methods_title' => ['Título métodos'],
                ],
            ],
            'metodos' => [
                'title' => 'Métodos de pago',
                'description' => 'Nombres, descripciones y tags visibles de los métodos.',
                'fields' => [
                    'method_mp_name' => ['Mercado Pago - nombre'],
                    'method_mp_desc' => ['Mercado Pago - descripción'],
                    'method_mp_tags' => ['Mercado Pago - tags'],
                    'method_bank_name' => ['Transferencia - nombre'],
                    'method_bank_desc' => ['Transferencia - descripción'],
                    'method_bank_tags' => ['Transferencia - tags'],
                ],
            ],
            'confirmacion' => [
                'title' => 'Confirmación',
                'description' => 'Textos visibles del paso final y transferencia.',
                'fields' => [
                    'step3_back_label' => ['Boton cambiar método'],
                    'step3_loading_title' => ['Título cargando'],
                    'step3_loading_text_prefix' => ['Texto cargando antes del método'],
                    'step3_loading_text_suffix' => ['Texto cargando después del método'],
                    'step3_error_title' => ['Título error'],
                    'step3_error_text' => ['Error Mercado Pago'],
                    'step3_connection_error_text' => ['Error conexion'],
                    'step3_retry_label' => ['Boton reintentar'],
                    'bank_title' => ['Título transferencia'],
                    'bank_lede_prefix' => ['Texto transferencia antes del monto'],
                    'bank_lede_middle' => ['Texto entre monto y nombre'],
                    'bank_block_title' => ['Título bloque bancario'],
                    'bank_note' => ['Texto comprobante'],
                    'restart_button' => ['Boton otra donacion'],
                ],
            ],
            'modal' => [
                'title' => 'Modal post datos',
                'description' => 'Textos del modal que aparece después de guardar los datos personales.',
                'fields' => [
                    'modal_title_prefix' => ['Título antes del nombre'],
                    'modal_title_suffix' => ['Título después del nombre'],
                    'modal_lede_prefix' => ['Bajada antes del email'],
                    'modal_lede_suffix' => ['Bajada después del email'],
                    'modal_card_title' => ['Título tarjeta'],
                    'modal_card_text' => ['Texto tarjeta'],
                    'modal_donate_now' => ['Boton donar ahora'],
                    'modal_donate_later' => ['Boton donar mas tarde'],
                    'modal_footer' => ['Texto seguridad'],
                ],
            ],
            'footer' => [
                'title' => 'Confianza y footer',
                'description' => 'Sellos, textos y labels del footer.',
                'notice' => 'Acá se editan los textos visibles de los links. Para cambiar las URLs, ir a <strong>Media y links</strong>.',
                'fields' => [
                    'trust_1_title' => ['Confianza 1 - título'],
                    'trust_1_text' => ['Confianza 1 - texto'],
                    'trust_2_title' => ['Confianza 2 - título'],
                    'trust_2_text' => ['Confianza 2 - texto'],
                    'trust_3_title' => ['Confianza 3 - título'],
                    'trust_3_text' => ['Confianza 3 - texto'],
                    'footer_text' => ['Texto footer'],
                    'footer_seal_1' => ['Sello 1'],
                    'footer_seal_2' => ['Sello 2'],
                    'footer_seal_3' => ['Sello 3'],
                    'footer_link_1_label' => ['Link 1 - texto'],
                    'footer_link_2_label' => ['Link 2 - texto'],
                    'footer_link_3_label' => ['Link 3 - texto'],
                ],
            ],
        ];
    }

    private static function current_text_section_slug($text_sections) {
        $slug = sanitize_key($_GET['text_section'] ?? array_key_first($text_sections));
        return isset($text_sections[$slug]) ? $slug : array_key_first($text_sections);
    }

    private static function current_section_slug($sections) {
        $page = sanitize_key($_GET['page'] ?? 'ms-donaciones');

        if ($page === 'ms-donaciones') {
            return array_key_first($sections);
        }

        $slug = preg_replace('/^ms-donaciones-/', '', $page);
        return isset($sections[$slug]) ? $slug : array_key_first($sections);
    }

    private static function section_url($slug) {
        return admin_url('admin.php?page=ms-donaciones-' . $slug);
    }

    private static function get_prev_next($sections, $current_slug) {
        $slugs = array_keys($sections);
        $index = array_search($current_slug, $slugs, true);

        return [
            'prev' => $index > 0 ? $slugs[$index - 1] : null,
            'next' => $index < count($slugs) - 1 ? $slugs[$index + 1] : null,
        ];
    }

    private static function render_tabs($sections, $current_slug) {
        ?>
        <nav class="nav-tab-wrapper ms-tabs" aria-label="Secciones de configuracion">
            <?php foreach ($sections as $slug => $section) : ?>
                <a
                    class="nav-tab <?php echo $slug === $current_slug ? 'nav-tab-active' : ''; ?>"
                    href="<?php echo esc_url(self::section_url($slug)); ?>"
                >
                    <?php echo esc_html($section['menu']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    private static function render_text_section_selector($text_sections, $current_text_slug) {
        ?>
        <div class="ms-inline-selector">
            <label for="ms-text-section">Sección de textos</label>
            <select
                id="ms-text-section"
                onchange="if (this.value) window.location.href = this.value;"
            >
                <?php foreach ($text_sections as $slug => $text_section) : ?>
                    <option
                        value="<?php echo esc_url(add_query_arg('text_section', $slug, self::section_url('textos'))); ?>"
                        <?php selected($slug, $current_text_slug); ?>
                    >
                        <?php echo esc_html($text_section['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private static function render_connection_panel($current_slug, $labels) {
        $configs = [
            'crm' => [
                'action' => 'ms_donaciones_test_salesforce',
                'label' => 'Probar conexión y campos personalizados',
                'status_key' => 'sf_connection_status',
                'message_key' => 'sf_connection_message',
                'hint' => 'Guarda los cambios antes de probar. La prueba valida OAuth, campos configurados de Contact y Opportunity, Stage y valores de Opportunity Type.',
            ],
            'mercadopago' => [
                'action' => 'ms_donaciones_test_mercadopago',
                'label' => 'Probar conexion con Mercado Pago',
                'status_key' => 'mp_connection_status',
                'message_key' => 'mp_connection_message',
                'hint' => 'Guarda los cambios antes de probar. Si esta prueba falla, Mercado Pago se muestra como no disponible en el formulario.',
            ],
        ];

        if (empty($configs[$current_slug])) {
            return;
        }

        $config = $configs[$current_slug];
        $status = sanitize_key($labels[$config['status_key']] ?? 'unknown');
        $message = sanitize_text_field($labels[$config['message_key']] ?? '');
        $nonce = wp_create_nonce('ms_donaciones_connection_test');
        ?>
        <aside
            class="ms-connection-box"
            data-status="<?php echo esc_attr($status); ?>"
            <?php
            if ($current_slug === 'crm') {
                echo 'id="ms-crm-connection-test"';
            } elseif ($current_slug === 'mercadopago') {
                echo 'id="ms-mercadopago-connection-test"';
            }
            ?>
        >
            <div>
                <h3>Estado de conexión</h3>
                <p><?php echo esc_html($config['hint']); ?></p>
                <p class="ms-connection-result">
                    <?php echo esc_html(self::connection_status_label($status, $message)); ?>
                </p>
                <ul class="ms-connection-checks" hidden></ul>
            </div>
            <button
                type="button"
                class="button button-secondary ms-test-connection"
                data-action="<?php echo esc_attr($config['action']); ?>"
                data-nonce="<?php echo esc_attr($nonce); ?>"
            >
                <?php echo esc_html($config['label']); ?>
            </button>
        </aside>
        <script>
            (function(){
                const box = document.currentScript.previousElementSibling;
                if (!box) return;
                const button = box.querySelector(".ms-test-connection");
                const result = box.querySelector(".ms-connection-result");
                const checks = box.querySelector(".ms-connection-checks");
                if (!button || !result) return;

                button.addEventListener("click", async function(){
                    button.disabled = true;
                    result.textContent = "Probando conexion...";
                    if (checks) {
                        checks.hidden = true;
                        checks.replaceChildren();
                    }

                    const formData = new FormData();
                    formData.append("action", button.dataset.action);
                    formData.append("_ajax_nonce", button.dataset.nonce);

                    try {
                        const response = await fetch(ajaxurl, { method: "POST", body: formData });
                        const payload = await response.json();
                        const data = payload.data || {};
                        result.textContent = data.message || (payload.success ? "Conexión válida." : "No se pudo conectar.");
                        if (checks && Array.isArray(data.checks) && data.checks.length) {
                            data.checks.forEach(function(check){
                                const item = document.createElement("li");
                                item.className = check.status === "ok"
                                    ? "is-ok"
                                    : (check.status === "warning" ? "is-warning" : "is-error");
                                item.textContent = check.message || "";
                                checks.appendChild(item);
                            });
                            checks.hidden = false;
                        }
                        box.dataset.status = payload.success ? "valid" : "invalid";
                    } catch (error) {
                        result.textContent = "No se pudo ejecutar la prueba.";
                        box.dataset.status = "invalid";
                    } finally {
                        button.disabled = false;
                    }
                });
            })();
        </script>
        <?php
    }

    private static function connection_status_label($status, $message) {
        if ($status === 'valid') {
            return $message ?: 'Conexion valida.';
        }

        if ($status === 'invalid') {
            return $message ?: 'Conexion invalida.';
        }

        return 'Conexion no verificada.';
    }

    public static function ajax_test_salesforce() {
        self::assert_ajax_permissions();

        $labels          = self::labels_with_defaults();
        $consumer_key    = sanitize_text_field($labels['sf_consumer_key'] ?? '');
        $consumer_secret = sanitize_text_field($labels['sf_consumer_secret'] ?? '');
        $sandbox         = ($labels['sf_sandbox'] ?? '0') === '1';

        if (!$consumer_key || !$consumer_secret) {
            self::save_connection_status('sf', 'invalid', 'Faltan Consumer Key y/o Consumer Secret.');
            wp_send_json_error(['message' => 'Faltan Consumer Key y/o Consumer Secret.']);
        }

        $auth_url = self::salesforce_auth_url($labels['sf_login_url'] ?? '', $sandbox);

        $response = wp_remote_post($auth_url, [
            'body' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $consumer_key,
                'client_secret' => $consumer_secret,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            self::save_connection_status('sf', 'invalid', $message);
            wp_send_json_error(['message' => $message]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body        = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && !empty($body['access_token']) && !empty($body['instance_url'])) {
            $instance = rtrim($body['instance_url'], '/');
            $checks = [[
                'status'  => 'ok',
                'message' => 'OAuth: conexión válida con ' . $instance . '.',
            ]];
            $validation = self::validate_salesforce_schema(
                $instance,
                $body['access_token'],
                $labels
            );
            $checks = array_merge($checks, $validation['checks']);

            delete_transient('ms_donaciones_sf_auth');

            if (!$validation['success']) {
                $message = 'Conexión válida, pero hay campos o valores configurados que no existen en Salesforce.';
                self::save_connection_status('sf', 'invalid', $message);
                wp_send_json_error([
                    'message' => $message,
                    'checks'  => $checks,
                ]);
            }

            $message = 'Conexión y configuración de campos válidas.';
            self::save_connection_status('sf', 'valid', $message);
            wp_send_json_success([
                'message' => $message,
                'checks'  => $checks,
            ]);
        }

        $error   = $body['error_description'] ?? $body['error'] ?? ('HTTP ' . $status_code);
        if (stripos($error, 'authentication failure') !== false || stripos($error, 'invalid_client') !== false) {
            $error .= '. Revisa Consumer Key, Consumer Secret y que la Connected App tenga un usuario en Ejecutar como (OAuth Policies).';
        }
        $message = 'Error autenticando con Salesforce: ' . $error;
        self::save_connection_status('sf', 'invalid', $message);
        wp_send_json_error(['message' => $message]);
    }

    private static function validate_salesforce_schema($instance_url, $token, $labels) {
        $contact_describe = self::salesforce_describe_object($instance_url, $token, 'Contact');
        $opportunity_describe = self::salesforce_describe_object($instance_url, $token, 'Opportunity');
        $checks = [];
        $success = true;

        if (!$contact_describe['success']) {
            $checks[] = [
                'status'  => 'error',
                'message' => 'Contact: no se pudo consultar el esquema. ' . $contact_describe['error'],
            ];
            $success = false;
        } else {
            $contact_fields = self::salesforce_field_map($contact_describe['body']);
            foreach ([
                'Nombre'   => sanitize_text_field($labels['sf_field_firstname'] ?? 'FirstName'),
                'Apellido' => sanitize_text_field($labels['sf_field_lastname'] ?? 'LastName'),
                'Email'    => sanitize_text_field($labels['sf_field_email'] ?? 'Email'),
                'Teléfono' => sanitize_text_field($labels['sf_field_phone'] ?? 'MobilePhone'),
                'DNI'      => sanitize_text_field($labels['sf_field_dni'] ?? ''),
                'Mercado Pago Preapproval ID' => sanitize_text_field($labels['sf_contact_field_subscription_id'] ?? ''),
                'Estado de suscripción' => sanitize_text_field($labels['sf_contact_field_subscription_status'] ?? ''),
                'Fecha de cancelación' => sanitize_text_field($labels['sf_contact_field_subscription_cancelled_at'] ?? ''),
            ] as $label => $api_name) {
                if ($api_name === '') {
                    if ($label === 'DNI') {
                        $checks[] = [
                            'status'  => 'warning',
                            'message' => 'Contact · DNI: no configurado; se deduplicará únicamente por email.',
                        ];
                    } elseif (in_array($label, ['Mercado Pago Preapproval ID', 'Estado de suscripción', 'Fecha de cancelación'], true)) {
                        $checks[] = [
                            'status'  => 'warning',
                            'message' => 'Contact · ' . $label . ': no configurado; los cambios de suscripción sólo quedarán en el log.',
                        ];
                    }
                    continue;
                }

                if (isset($contact_fields[$api_name])) {
                    $checks[] = [
                        'status'  => 'ok',
                        'message' => 'Contact · ' . $label . ': existe ' . $api_name . '.',
                    ];
                } else {
                    $checks[] = [
                        'status'  => 'error',
                        'message' => 'Contact · ' . $label . ': no existe ' . $api_name . '. Revisa el API Name, incluyendo __c.',
                    ];
                    $success = false;
                }
            }
        }

        if (!$opportunity_describe['success']) {
            $checks[] = [
                'status'  => 'error',
                'message' => 'Opportunity: no se pudo consultar el esquema. ' . $opportunity_describe['error'],
            ];
            $success = false;
        } else {
            $opportunity_fields = self::salesforce_field_map($opportunity_describe['body']);
            foreach ([
                'Mercado Pago Payment ID'     => sanitize_text_field($labels['sf_opp_field_payment_id'] ?? ''),
                'Mercado Pago Preapproval ID' => sanitize_text_field($labels['sf_opp_field_subscription_id'] ?? ''),
                'External Reference'           => sanitize_text_field($labels['sf_opp_field_external_reference'] ?? ''),
                'Tipo de pago'                 => sanitize_text_field($labels['sf_opp_field_payment_kind'] ?? ''),
            ] as $label => $api_name) {
                if ($api_name === '') {
                    $checks[] = [
                        'status'  => 'warning',
                        'message' => 'Opportunity · ' . $label . ': no configurado; el dato quedará en Description.',
                    ];
                    continue;
                }

                if (isset($opportunity_fields[$api_name])) {
                    $checks[] = [
                        'status'  => 'ok',
                        'message' => 'Opportunity · ' . $label . ': existe ' . $api_name . '.',
                    ];
                } else {
                    $checks[] = [
                        'status'  => 'error',
                        'message' => 'Opportunity · ' . $label . ': no existe ' . $api_name . '. Revisa el API Name, incluyendo __c.',
                    ];
                    $success = false;
                }
            }

            foreach ([
                'Stage' => [
                    'field' => 'StageName',
                    'value' => sanitize_text_field($labels['sf_opp_stage'] ?? 'Closed Won'),
                ],
                'Type para pago puntual' => [
                    'field' => 'Type',
                    'value' => sanitize_text_field($labels['sf_opp_type_unico'] ?? ''),
                ],
                'Type para pago recurrente' => [
                    'field' => 'Type',
                    'value' => sanitize_text_field($labels['sf_opp_type_recurrente'] ?? ''),
                ],
            ] as $label => $config) {
                if ($config['value'] === '') {
                    $checks[] = [
                        'status'  => 'warning',
                        'message' => 'Opportunity · ' . $label . ': sin valor configurado.',
                    ];
                    continue;
                }

                $values = self::salesforce_active_picklist_values(
                    $opportunity_fields[$config['field']] ?? []
                );
                if (in_array($config['value'], $values, true)) {
                    $checks[] = [
                        'status'  => 'ok',
                        'message' => 'Opportunity · ' . $label . ': existe "' . $config['value'] . '".',
                    ];
                } else {
                    $checks[] = [
                        'status'  => 'error',
                        'message' => 'Opportunity · ' . $label . ': no existe el valor "' . $config['value'] . '" en Salesforce.',
                    ];
                    $success = false;
                }
            }
        }

        return [
            'success' => $success,
            'checks'  => $checks,
        ];
    }

    private static function salesforce_describe_object($instance_url, $token, $object_name) {
        $response = wp_remote_get(
            rtrim($instance_url, '/') . '/services/data/v59.0/sobjects/' . rawurlencode($object_name) . '/describe',
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'body'    => [],
                'error'   => $response->get_error_message(),
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);

        return [
            'success' => $status >= 200 && $status < 300 && is_array($body),
            'body'    => is_array($body) ? $body : [],
            'error'   => ($status >= 200 && $status < 300)
                ? ''
                : (self::extract_api_error($raw_body) ?: 'HTTP ' . $status),
        ];
    }

    private static function salesforce_field_map($describe) {
        $fields = [];
        foreach (($describe['fields'] ?? []) as $field) {
            if (!empty($field['name'])) {
                $fields[$field['name']] = $field;
            }
        }

        return $fields;
    }

    private static function salesforce_active_picklist_values($field) {
        $values = [];
        foreach (($field['picklistValues'] ?? []) as $option) {
            if (($option['active'] ?? false) && isset($option['value'])) {
                $values[] = (string) $option['value'];
            }
        }

        return $values;
    }

    public static function ajax_test_mercadopago() {
        self::assert_ajax_permissions();

        $labels = self::labels_with_defaults();
        $token = sanitize_text_field($labels['mp_access_token'] ?? '');

        if (!$token) {
            self::save_connection_status('mp', 'invalid', 'Falta Access Token.');
            wp_send_json_error(['message' => 'Falta Access Token.']);
        }

        $response = wp_remote_get('https://api.mercadopago.com/v1/customers/search?email=test_payer_123@testuser.com', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 12,
        ]);

        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            self::save_connection_status('mp', 'invalid', $message);
            wp_send_json_error(['message' => $message]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code >= 200 && $status_code < 300) {
            self::save_connection_status('mp', 'valid', 'Conexión válida con Mercado Pago.');
            wp_send_json_success(['message' => 'Conexión válida con Mercado Pago.']);
        }

        $message = self::extract_api_error($body) ?: 'Mercado Pago respondio con HTTP ' . $status_code . '. Verifica el Access Token.';
        self::save_connection_status('mp', 'invalid', $message);
        wp_send_json_error(['message' => $message, 'status' => $status_code]);
    }

    private static function assert_ajax_permissions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No autorizado.'], 403);
        }

        check_ajax_referer('ms_donaciones_connection_test');
    }

    private static function labels_with_defaults() {
        return array_merge(
            MS_Donaciones_Shortcodes::default_labels(),
            get_option('ms_donaciones_labels', [])
        );
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

    private static function save_connection_status($service, $status, $message) {
        $labels = self::labels_with_defaults();
        $prefix = $service === 'mp' ? 'mp' : 'sf';
        $labels[$prefix . '_connection_status'] = $status;
        $labels[$prefix . '_connection_message'] = sanitize_text_field($message);

        update_option('ms_donaciones_labels', $labels);
    }

    private static function extract_api_error($body) {
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return $body ? substr($body, 0, 300) : '';
        }

        if (!empty($decoded['error']['message'])) {
            return $decoded['error']['message'];
        }

        if (!empty($decoded['error']['type'])) {
            return $decoded['error']['type'];
        }

        if (!empty($decoded['message'])) {
            return $decoded['message'];
        }

        if (!empty($decoded['error'])) {
            return is_string($decoded['error']) ? $decoded['error'] : wp_json_encode($decoded['error']);
        }

        return substr($body, 0, 300);
    }

    private static function render_help_box($help) {
        ?>
        <aside class="ms-help-box">
            <h3><?php echo esc_html($help['title'] ?? 'Ayuda'); ?></h3>

            <?php if (!empty($help['items']) && is_array($help['items'])) : ?>
                <ol>
                    <?php foreach ($help['items'] as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if (!empty($help['link'])) : ?>
                <a href="<?php echo esc_url($help['link']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($help['link_label'] ?? $help['link']); ?>
                </a>
            <?php endif; ?>
        </aside>
        <?php
    }

    private static function render_salesforce_opportunity_guide() {
        ?>
        <details class="ms-setup-guide" id="ms-salesforce-custom-fields-guide">
            <summary>
                <span>
                    <strong>Cómo crear campos custom en Salesforce</strong>
                    <small>Guía para crear el DNI en Contact y los datos de Mercado Pago en Opportunity.</small>
                </span>
            </summary>

            <div class="ms-setup-guide-content">
                <ol>
                    <li>En Salesforce ve a <strong>Configuración → Gestor de objetos</strong>.</li>
                    <li>Abre el objeto indicado en cada tabla: <strong>Contact</strong> u <strong>Opportunity</strong>.</li>
                    <li>Entra a <strong>Campos y relaciones → Nuevo → Texto</strong> y crea el campo.</li>
                    <li>En <strong>Nombre de campo</strong> escribe el nombre sin <code>__c</code>; Salesforce agrega ese sufijo automáticamente.</li>
                    <li>Asigna visibilidad y permiso de edición al usuario configurado como <strong>Run As User</strong>.</li>
                    <li>Copia el <strong>API Name real</strong> de cada campo y pégalo en su casilla de esta pantalla.</li>
                </ol>

                <h4>Contact: identificación del donante</h4>
                <p class="ms-guide-intro">
                    Este campo se guarda en la persona. El plugin busca primero por email y luego por DNI para evitar contactos duplicados.
                </p>

                <div class="ms-guide-table-wrap">
                    <table class="widefat striped ms-guide-table">
                        <thead>
                            <tr>
                                <th>Etiqueta sugerida</th>
                                <th>Nombre al crear</th>
                                <th>API Name final esperado</th>
                                <th>Tipo / longitud</th>
                                <th>Valor guardado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>DNI</td>
                                <td><code>DNI</code></td>
                                <td><code>DNI__c</code></td>
                                <td>Texto, 20</td>
                                <td>Documento del Contact</td>
                            </tr>
                            <tr>
                                <td>Mercado Pago Preapproval ID</td>
                                <td><code>Mercado_Pago_Preapproval_ID</code></td>
                                <td><code>Mercado_Pago_Preapproval_ID__c</code></td>
                                <td>Texto, 100</td>
                                <td>ID de la suscripción más reciente</td>
                            </tr>
                            <tr>
                                <td>Estado de suscripción</td>
                                <td><code>Estado_Suscripcion</code></td>
                                <td><code>Estado_Suscripcion__c</code></td>
                                <td>Texto, 30</td>
                                <td><code>authorized</code>, <code>paused</code>, <code>pending</code> o <code>cancelled</code></td>
                            </tr>
                            <tr>
                                <td>Fecha de cancelación</td>
                                <td><code>Fecha_Cancelacion_Suscripcion</code></td>
                                <td><code>Fecha_Cancelacion_Suscripcion__c</code></td>
                                <td>Fecha/Hora</td>
                                <td>Momento informado al cancelar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h4>Opportunity: trazabilidad de los cobros</h4>
                <p class="ms-guide-intro">
                    Estos campos son opcionales. Permiten filtrar y reportar pagos sin depender únicamente de la descripción.
                </p>

                <div class="ms-guide-table-wrap">
                    <table class="widefat striped ms-guide-table">
                        <thead>
                            <tr>
                                <th>Etiqueta sugerida</th>
                                <th>Nombre al crear</th>
                                <th>API Name final esperado</th>
                                <th>Tipo / longitud</th>
                                <th>Valor guardado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mercado Pago Payment ID</td>
                                <td><code>Mercado_Pago_Payment_ID</code></td>
                                <td><code>Mercado_Pago_Payment_ID__c</code></td>
                                <td>Texto, 100</td>
                                <td>ID del cobro confirmado</td>
                            </tr>
                            <tr>
                                <td>Mercado Pago Preapproval ID</td>
                                <td><code>Mercado_Pago_Preapproval_ID</code></td>
                                <td><code>Mercado_Pago_Preapproval_ID__c</code></td>
                                <td>Texto, 100</td>
                                <td>ID de la suscripción</td>
                            </tr>
                            <tr>
                                <td>External Reference</td>
                                <td><code>External_Reference</code></td>
                                <td><code>External_Reference__c</code></td>
                                <td>Texto, 150</td>
                                <td>Referencia generada por WordPress</td>
                            </tr>
                            <tr>
                                <td>Tipo de pago</td>
                                <td><code>Tipo_de_Pago</code></td>
                                <td><code>Tipo_de_Pago__c</code></td>
                                <td>Texto, 50</td>
                                <td><code>PAGO_PUNTUAL</code> o <code>PAGO_RECURRENTE</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="ms-guide-tip">
                    <strong>Importante:</strong> Salesforce puede ajustar el API Name al guardar.
                    No escribas <code>__c</code> al crear el campo. Copia después el API Name definitivo,
                    que Salesforce mostrará terminado en <code>__c</code>, y pégalo en WordPress.
                    El DNI debe configurarse en la sección <strong>Campos de Contact</strong>.
                    Los campos de estado de suscripción también pertenecen a <strong>Contact</strong> y permiten reflejar
                    pausas o cancelaciones notificadas por Mercado Pago.
                    Los campos de cobro se configuran en <strong>Campos de Opportunity</strong>; si no los creas,
                    la integración sigue funcionando y deja esos datos en la descripción de la Opportunity.
                </p>
            </div>
        </details>
        <?php
    }

    private static function render_mercadopago_guide() {
        ?>
        <details class="ms-setup-guide" id="ms-mercadopago-setup-guide">
            <summary>
                <span>
                    <strong>Cómo configurar Mercado Pago Developers</strong>
                    <small>Aplicación, credenciales, notificaciones y usuarios de prueba.</small>
                </span>
            </summary>

            <div class="ms-setup-guide-content">
                <ol>
                    <li>Entra a <strong>Mercado Pago Developers → Tus integraciones</strong> y crea o abre una aplicación.</li>
                    <li>En <strong>Credenciales</strong>, copia el <strong>Access Token</strong> desde la sección de prueba o producción correspondiente. No determines el entorno sólo por el prefijo: Mercado Pago también puede entregar tokens <code>APP_USR-...</code> en credenciales de prueba.</li>
                    <li>Pega el token arriba, guarda esta sección y ejecuta <strong>Probar conexión con Mercado Pago</strong>.</li>
                    <li>Configura como URL pública del webhook: <code>https://tu-dominio.com/wp-json/donacion/v1/webhook</code>.</li>
                    <li>Activa notificaciones de <strong>pagos</strong> y de <strong>suscripciones</strong>. El plugin procesa <code>payment</code>, <code>subscription_preapproval</code> y <code>subscription_authorized_payment</code>.</li>
                    <li>En <strong>Pruebas → Cuentas de prueba</strong>, verifica que existan al menos una cuenta <strong>Vendedor</strong> y una cuenta <strong>Comprador</strong>, ambas de Argentina.</li>
                    <li>Para probar una suscripción, usa en el formulario el usuario/email generado para la cuenta Comprador y, al abrirse el checkout, inicia sesión con el <strong>Usuario</strong> y la <strong>Contraseña</strong> de esa cuenta.</li>
                    <li>Si Mercado Pago solicita validación por email, utiliza el <strong>Código de verificación de 6 dígitos</strong> mostrado en la tabla de Cuentas de prueba.</li>
                    <li>No uses la cuenta Vendedor como pagador y no mezcles cuentas reales con cuentas de prueba.</li>
                </ol>

                <h4>Checklist para probar una suscripción</h4>
                <div class="ms-guide-table-wrap">
                    <table class="widefat striped ms-guide-table">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Qué utilizar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Access Token</td>
                                <td>El de <strong>Credenciales de prueba</strong> de la aplicación.</td>
                            </tr>
                            <tr>
                                <td>Email del formulario</td>
                                <td>El usuario/email asociado a la cuenta de prueba <strong>Comprador</strong>.</td>
                            </tr>
                            <tr>
                                <td>Inicio de sesión en Checkout</td>
                                <td>Usuario y contraseña generados para el Comprador.</td>
                            </tr>
                            <tr>
                                <td>Verificación</td>
                                <td>Código de 6 dígitos de la misma cuenta de prueba.</td>
                            </tr>
                            <tr>
                                <td>País</td>
                                <td>Comprador y Vendedor deben ser del mismo país.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h4>Retornos después del checkout</h4>
                <p class="ms-guide-intro">
                    El plugin recibe primero la respuesta de Mercado Pago, consulta el estado real y vuelve al formulario mostrando
                    si la operación fue aprobada, quedó pendiente o fue rechazada.
                </p>

                <div class="ms-guide-tip">
                    <strong>No necesitas crear <code>/gracias</code>.</strong>
                    Deja desactivado <em>Usar páginas personalizadas de resultado</em>.
                    Las tres URLs de resultado sólo se utilizan si quieres reemplazar los mensajes del plugin por páginas propias que ya existan.
                </div>

                <h4>Desarrollo local con ngrok</h4>
                <p class="ms-guide-intro">Para este proyecto, inicia el túnel con:</p>
                <p><code>ngrok http 10005 --host-header=sandbox-modulo-sanitario.local</code></p>
                <p class="ms-guide-intro">
                    Si cambia el dominio gratuito de ngrok, actualiza la URL del webhook en este panel y en Mercado Pago Developers.
                    Puedes inspeccionar las llamadas en <code>http://localhost:4040</code>.
                </p>

                <p>
                    <a href="https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/your-integrations/notifications/webhooks" target="_blank" rel="noopener noreferrer">
                        Ver documentación oficial de Webhooks de Mercado Pago
                    </a>
                </p>
            </div>
        </details>
        <?php
    }

    private static function render_styles() {
        ?>
        <style>
            .ms-donaciones-admin .ms-tabs {
                margin-top: 18px;
                display: flex;
                flex-wrap: wrap;
                gap: 0;
            }
            .ms-donaciones-admin .ms-card {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 8px;
                margin: 18px 0;
                padding: 22px;
                max-width: 1080px;
            }
            .ms-donaciones-admin .ms-section-head {
                align-items: flex-start;
                border-bottom: 1px solid #dcdcde;
                display: flex;
                gap: 18px;
                justify-content: space-between;
                margin: 0 0 10px;
                padding: 0 0 16px;
            }
            .ms-donaciones-admin .ms-section-head h2 {
                margin: 3px 0 4px;
            }
            .ms-donaciones-admin .ms-section-head p {
                color: #646970;
                margin: 0;
            }
            .ms-donaciones-admin .ms-step-label {
                color: #2271b1;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
            }
            .ms-donaciones-admin input.regular-text {
                max-width: 720px;
                width: 100%;
            }
            .ms-donaciones-admin .ms-section-reminder {
                margin: 16px 0 4px;
                max-width: 860px;
            }
            .ms-donaciones-admin .ms-section-reminder p {
                font-size: 13px;
                margin: 10px 12px;
            }
            .ms-donaciones-admin .ms-section-reminder a {
                font-weight: 600;
            }
            .ms-donaciones-admin #ms-salesforce-custom-fields-guide,
            .ms-donaciones-admin #ms-crm-connection-test,
            .ms-donaciones-admin #ms-mercadopago-setup-guide,
            .ms-donaciones-admin #ms-mercadopago-connection-test {
                scroll-margin-top: 40px;
            }
            .ms-donaciones-admin .ms-field-group-row th {
                padding: 24px 0 6px;
            }
            .ms-donaciones-admin .ms-field-group {
                align-items: flex-start;
                background: #f6f7f7;
                border: 1px solid #dcdcde;
                border-left: 4px solid #2271b1;
                border-radius: 6px;
                display: flex;
                gap: 12px;
                max-width: 900px;
                padding: 13px 15px;
            }
            .ms-donaciones-admin .ms-field-group .dashicons {
                color: #2271b1;
                margin-top: 2px;
            }
            .ms-donaciones-admin .ms-field-group h3 {
                margin: 0 0 4px;
            }
            .ms-donaciones-admin .ms-field-group p {
                color: #50575e;
                margin: 0;
                max-width: 760px;
            }
            .ms-donaciones-admin .ms-inline-selector {
                align-items: center;
                background: #f6f7f7;
                border: 1px solid #dcdcde;
                border-radius: 6px;
                display: flex;
                gap: 12px;
                margin: 16px 0 6px;
                max-width: 520px;
                padding: 12px;
            }
            .ms-donaciones-admin .ms-inline-selector label {
                font-weight: 700;
            }
            .ms-donaciones-admin .ms-inline-selector select {
                min-width: 260px;
            }
            .ms-donaciones-admin .ms-field-note {
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                color: #1d2327;
                margin: 12px 0 6px;
                max-width: 720px;
                padding: 10px 12px;
            }
            .ms-donaciones-admin .ms-connection-box {
                align-items: center;
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 8px;
                display: flex;
                gap: 16px;
                justify-content: space-between;
                margin-top: 18px;
                max-width: 760px;
                padding: 14px 16px;
            }
            .ms-donaciones-admin .ms-connection-box h3 {
                margin: 0 0 4px;
            }
            .ms-donaciones-admin .ms-connection-box p {
                margin: 0 0 6px;
            }
            .ms-donaciones-admin .ms-connection-box[data-status="valid"] .ms-connection-result {
                color: #008a20;
                font-weight: 700;
            }
            .ms-donaciones-admin .ms-connection-box[data-status="invalid"] .ms-connection-result {
                color: #b32d2e;
                font-weight: 700;
            }
            .ms-donaciones-admin .ms-connection-checks {
                display: grid;
                gap: 5px;
                list-style: none;
                margin: 10px 0 0;
                max-width: 720px;
                padding: 0;
            }
            .ms-donaciones-admin .ms-connection-checks[hidden] {
                display: none;
            }
            .ms-donaciones-admin .ms-connection-checks li {
                margin: 0;
                padding-left: 22px;
                position: relative;
            }
            .ms-donaciones-admin .ms-connection-checks li::before {
                font-weight: 700;
                left: 0;
                position: absolute;
            }
            .ms-donaciones-admin .ms-connection-checks .is-ok {
                color: #008a20;
            }
            .ms-donaciones-admin .ms-connection-checks .is-ok::before {
                content: "✓";
            }
            .ms-donaciones-admin .ms-connection-checks .is-warning {
                color: #996800;
            }
            .ms-donaciones-admin .ms-connection-checks .is-warning::before {
                content: "!";
            }
            .ms-donaciones-admin .ms-connection-checks .is-error {
                color: #b32d2e;
            }
            .ms-donaciones-admin .ms-connection-checks .is-error::before {
                content: "×";
            }
            .ms-donaciones-admin .ms-help-box {
                background: #f6f7f7;
                border-left: 4px solid #2271b1;
                margin-top: 18px;
                max-width: 760px;
                padding: 14px 16px;
            }
            .ms-donaciones-admin .ms-help-box h3 {
                margin: 0 0 8px;
            }
            .ms-donaciones-admin .ms-help-box ol {
                margin: 0 0 10px 20px;
            }
            .ms-donaciones-admin .ms-help-box li {
                margin-bottom: 4px;
            }
            .ms-donaciones-admin .ms-setup-guide {
                background: #f0f6fc;
                border: 1px solid #c3d9ed;
                border-radius: 8px;
                margin-top: 18px;
                max-width: 900px;
                overflow: hidden;
            }
            .ms-donaciones-admin .ms-setup-guide summary {
                align-items: center;
                cursor: pointer;
                display: flex;
                padding: 15px 18px;
            }
            .ms-donaciones-admin .ms-setup-guide summary:hover {
                background: #e8f2fb;
            }
            .ms-donaciones-admin .ms-setup-guide summary span {
                display: flex;
                flex-direction: column;
                gap: 3px;
            }
            .ms-donaciones-admin .ms-setup-guide summary strong {
                color: #135e96;
                font-size: 14px;
            }
            .ms-donaciones-admin .ms-setup-guide summary small {
                color: #50575e;
                font-size: 12px;
            }
            .ms-donaciones-admin .ms-setup-guide-content {
                border-top: 1px solid #c3d9ed;
                padding: 16px 18px 18px;
            }
            .ms-donaciones-admin .ms-setup-guide-content > ol {
                margin: 0 0 16px 20px;
            }
            .ms-donaciones-admin .ms-setup-guide-content > ol li {
                margin-bottom: 6px;
            }
            .ms-donaciones-admin .ms-setup-guide-content h4 {
                color: #135e96;
                font-size: 14px;
                margin: 20px 0 4px;
            }
            .ms-donaciones-admin .ms-guide-intro {
                color: #50575e;
                margin: 0 0 10px;
            }
            .ms-donaciones-admin .ms-guide-table-wrap {
                overflow-x: auto;
            }
            .ms-donaciones-admin .ms-guide-table {
                min-width: 900px;
            }
            .ms-donaciones-admin .ms-guide-table th {
                font-weight: 700;
            }
            .ms-donaciones-admin .ms-guide-tip {
                background: #fff;
                border-left: 4px solid #dba617;
                margin: 14px 0 0;
                padding: 10px 12px;
            }
            .ms-donaciones-admin .ms-save-bar {
                align-items: center;
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 8px;
                bottom: 14px;
                box-shadow: 0 6px 20px rgba(0,0,0,.08);
                display: flex;
                justify-content: space-between;
                margin: 18px 0;
                max-width: 1080px;
                padding: 14px 16px;
                position: sticky;
                z-index: 5;
            }
            .ms-donaciones-admin .ms-step-nav {
                display: flex;
                gap: 8px;
            }
        </style>
        <?php
    }

    private static function render_input($key, $type, $value, $description = '') {
        if ($type === 'checkbox') {
            ?>
            <input
                type="hidden"
                name="ms_donaciones_labels[<?php echo esc_attr($key); ?>]"
                value="0"
            >
            <label>
                <input
                    id="ms-donaciones-<?php echo esc_attr($key); ?>"
                    type="checkbox"
                    name="ms_donaciones_labels[<?php echo esc_attr($key); ?>]"
                    value="1"
                    <?php checked($value, '1'); ?>
                >
                Activado
            </label>
            <?php if ($description) : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            <?php
            return;
        }

        $input_type = in_array($type, ['url', 'email', 'number', 'password'], true) ? $type : 'text';
        ?>
        <input
            id="ms-donaciones-<?php echo esc_attr($key); ?>"
            type="<?php echo esc_attr($input_type); ?>"
            name="ms_donaciones_labels[<?php echo esc_attr($key); ?>]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
        >
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
}
