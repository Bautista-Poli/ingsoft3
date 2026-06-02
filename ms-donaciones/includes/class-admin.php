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

                    <table class="form-table" role="presentation">
                        <?php foreach ($section['fields'] as $key => $field) : ?>
                            <?php
                            $label = $field[0] ?? $key;
                            $type = $field[1] ?? 'text';
                            $description = $field[2] ?? '';
                            ?>
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
                    'sf_sandbox'         => ['Usar sandbox de Salesforce', 'checkbox'],
                    'sf_login_url'       => ['URL/Dominio de login', 'text', 'Opcional. Usa el My Domain de Salesforce si aplica. Ej: https://tu-dominio.my.salesforce.com. Si lo dejas vacio usa produccion o sandbox.'],
                    'sf_consumer_key'    => ['Consumer Key', 'password', 'Consumer Key de la Connected App en Salesforce.'],
                    'sf_consumer_secret' => ['Consumer Secret', 'password', 'Consumer Secret de la Connected App en Salesforce.'],
                    'sf_username'        => ['Usuario de Salesforce', 'text', 'Email de acceso a Salesforce.'],
                    'sf_password_token'  => ['Contraseña + Security Token', 'password', 'Concatena tu contraseña con el Security Token sin espacios. Ej: MiPassword123AbCdEfGhIjK.'],
                    'sf_field_firstname' => ['API Name: Nombre (Contact)', 'text', 'Por defecto: FirstName. Cambialo solo si el campo es custom.'],
                    'sf_field_lastname'  => ['API Name: Apellido (Contact)', 'text', 'Por defecto: LastName.'],
                    'sf_field_email'     => ['API Name: Email (Contact)', 'text', 'Por defecto: Email.'],
                    'sf_field_phone'     => ['API Name: Telefono (Contact)', 'text', 'Por defecto: MobilePhone.'],
                    'sf_field_dni'       => ['API Name: DNI (Contact)', 'text', 'API Name del campo custom de DNI. Ej: npe01__Numero_de_Documento__c.'],
                    'sf_opp_stage'       => ['Stage de Oportunidad', 'text', 'Stage name para donaciones aprobadas por Mercado Pago. Por defecto: Closed Won.'],
                ],
                'help' => [
                    'title' => 'Como conectar WordPress con Salesforce',
                    'items' => [
                        'En Salesforce → Setup → App Manager → New Connected App.',
                        'Habilita OAuth Settings. URL de callback: cualquier URL de tu sitio.',
                        'Scopes requeridos: api, refresh_token.',
                        'Copia Consumer Key y Consumer Secret al panel de arriba.',
                        'Si tu org exige My Domain, completa URL/Dominio de login con el dominio exacto de Salesforce.',
                        'Concatena tu contraseña con tu Security Token (sin espacio) en el campo Contraseña + Security Token.',
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
                    'mp_access_token' => ['Access Token', 'password', 'Puede ser TEST-... para pruebas o APP_USR-... para produccion. No se expone en el navegador.'],
                    'mp_webhook_url'  => ['URL de Webhook', 'url', 'URL publica para notificaciones de pago. En produccion: https://tu-sitio.com/wp-json/donacion/v1/webhook. En dev: URL de ngrok.'],
                    'mp_item_title' => ['Título del item'],
                    'mp_statement_descriptor' => ['Descriptor en resumen'],
                    'mp_success_url' => ['URL exito', 'url'],
                    'mp_failure_url' => ['URL fallo', 'url'],
                    'mp_pending_url' => ['URL pendiente', 'url'],
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
                    'method_local_name' => ['Tarjeta local - nombre'],
                    'method_local_desc' => ['Tarjeta local - descripción'],
                    'method_local_tags' => ['Tarjeta local - tags'],
                    'method_intl_name' => ['Tarjeta internacional - nombre'],
                    'method_intl_desc' => ['Tarjeta internacional - descripción'],
                    'method_intl_tags' => ['Tarjeta internacional - tags'],
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
                'label' => 'Probar conexion con Salesforce',
                'status_key' => 'sf_connection_status',
                'message_key' => 'sf_connection_message',
                'hint' => 'Guarda los cambios antes de probar. La prueba verifica las credenciales OAuth contra Salesforce.',
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
        <aside class="ms-connection-box" data-status="<?php echo esc_attr($status); ?>">
            <div>
                <h3>Estado de conexión</h3>
                <p><?php echo esc_html($config['hint']); ?></p>
                <p class="ms-connection-result">
                    <?php echo esc_html(self::connection_status_label($status, $message)); ?>
                </p>
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
                if (!button || !result) return;

                button.addEventListener("click", async function(){
                    button.disabled = true;
                    result.textContent = "Probando conexion...";

                    const formData = new FormData();
                    formData.append("action", button.dataset.action);
                    formData.append("_ajax_nonce", button.dataset.nonce);

                    try {
                        const response = await fetch(ajaxurl, { method: "POST", body: formData });
                        const payload = await response.json();
                        const data = payload.data || {};
                        result.textContent = data.message || (payload.success ? "Conexión válida." : "No se pudo conectar.");
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

        if ($status_code === 200 && !empty($body['access_token'])) {
            $instance = $body['instance_url'] ?? '';
            $message  = 'Conexion valida con Salesforce' . ($instance ? ' (' . $instance . ')' : '') . '.';
            delete_transient('ms_donaciones_sf_auth');
            self::save_connection_status('sf', 'valid', $message);
            wp_send_json_success(['message' => $message]);
        }

        $error   = $body['error_description'] ?? $body['error'] ?? ('HTTP ' . $status_code);
        if (stripos($error, 'authentication failure') !== false || stripos($error, 'invalid_client') !== false) {
            $error .= '. Revisa Consumer Key, Consumer Secret y que la Connected App tenga un usuario en Ejecutar como (OAuth Policies).';
        }
        $message = 'Error autenticando con Salesforce: ' . $error;
        self::save_connection_status('sf', 'invalid', $message);
        wp_send_json_error(['message' => $message]);
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
