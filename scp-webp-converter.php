
<?php
/**
 * Plugin Name: SCP WebP Converter
 * Description: Convierte fotos a WebP (todas las medidas) y sirve WebP si el navegador lo permite, sin duplicar conversiones existentes. Incluye conversor en lote para la biblioteca existente.
 * Version: 1.2.0
 * Author: Tu nombre
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Definir constantes del plugin
define('SCP_WEBP_VERSION', '1.2.0');
define('SCP_WEBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCP_WEBP_PLUGIN_URL', plugin_dir_url(__FILE__));

class SCP_WebP_Converter {
    // Opciones de calidad
    const OPT_QUALITY_JPEG = 'scp_webp_quality_jpeg'; // 0-100
    const OPT_QUALITY_PNG  = 'scp_webp_quality_png';  // 0-100
    const OPT_ENABLE_AVIF  = 'scp_webp_enable_avif';  // Preparado para futuro
    const DEFAULT_Q_JPEG   = 82;
    const DEFAULT_Q_PNG    = 82;

    public function __construct() {
        // Verificar capacidades del servidor al inicializar
        add_action('admin_init', [$this, 'check_server_capabilities']);
        
        // Ajustes
        add_option(self::OPT_QUALITY_JPEG, self::DEFAULT_Q_JPEG);
        add_option(self::OPT_QUALITY_PNG,  self::DEFAULT_Q_PNG);

        // 1) Generar WebP al subir (todas las medidas)
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_webp_for_all_sizes'], 20, 2);

        // 2) Servir WebP si el navegador lo soporta (src & srcset)
        add_filter('wp_get_attachment_image_src', [$this, 'maybe_swap_to_webp_src'], 20, 4);
        add_filter('wp_calculate_image_srcset',  [$this, 'maybe_swap_srcset_to_webp'], 20, 5);

        // 3) Ajustes y pantalla de conversión masiva
        add_action('admin_menu',  [$this, 'add_settings_page']);
        add_action('admin_init',  [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);

        // 4) AJAX para conversión por lotes
        add_action('wp_ajax_scp_webp_convert_missing', [$this, 'ajax_convert_missing']);
        
        // 4b) AJAX para conversión individual desde media library
        add_action('wp_ajax_scp_webp_convert_single', [$this, 'ajax_convert_single']);

        // 5) WP-CLI (opcional) para lotes grandes
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('scp-webp/convert-missing', [$this, 'wpcli_convert_missing']);
        }
        
        // 6) Integración con Media Library
        add_filter('attachment_fields_to_edit', [$this, 'add_webp_field_to_edit'], 10, 2);
        add_action('wp_enqueue_media', [$this, 'enqueue_media_assets']);
        add_filter('media_row_actions', [$this, 'add_media_row_actions'], 10, 2);
        add_filter('bulk_actions-upload', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_actions'], 10, 3);

        // 7) Mostrar mensajes de bulk actions
        add_action('admin_notices', [$this, 'show_bulk_action_notices']);

        // 8) Activación del plugin
        register_activation_hook(__FILE__, [$this, 'on_activation']);
    }

    /** Hook de activación del plugin */
    public function on_activation() {
        $this->check_server_capabilities();
        $this->log_message('Plugin SCP WebP Converter activado correctamente', 'info');
    }

    /** Muestra mensajes de las acciones en lote */
    public function show_bulk_action_notices() {
        // Solo mostrar en la página de medios
        if (!isset($_GET['scp_webp_bulk_result'])) {
            return;
        }

        $message = urldecode($_GET['scp_webp_bulk_result']);
        $converted = isset($_GET['scp_webp_converted']) ? (int)$_GET['scp_webp_converted'] : 0;
        $errors = isset($_GET['scp_webp_errors']) ? (int)$_GET['scp_webp_errors'] : 0;
        
        $notice_class = 'notice-success';
        if ($errors > 0 && $converted === 0) {
            $notice_class = 'notice-error';
        } elseif ($errors > 0) {
            $notice_class = 'notice-warning';
        }
        
        echo '<div class="notice ' . $notice_class . ' is-dismissible">';
        echo '<p><strong>Conversión WebP completada:</strong> ' . esc_html($message) . '</p>';
        echo '</div>';
    }

    /** Verifica las capacidades del servidor para WebP */
    public function check_server_capabilities() {
        $capabilities = [];
        $errors = [];

        // Verificar GD
        if (extension_loaded('gd')) {
            $gd_info = gd_info();
            $capabilities['gd'] = [
                'loaded' => true,
                'webp_support' => isset($gd_info['WebP Support']) ? $gd_info['WebP Support'] : false,
                'version' => $gd_info['GD Version'] ?? 'Unknown'
            ];
        } else {
            $capabilities['gd'] = ['loaded' => false];
            $errors[] = 'Extensión GD no está disponible';
        }

        // Verificar Imagick
        if (extension_loaded('imagick')) {
            $capabilities['imagick'] = [
                'loaded' => true,
                'webp_support' => in_array('WEBP', \Imagick::queryFormats(), true),
                'version' => \Imagick::getVersion()['versionString'] ?? 'Unknown'
            ];
        } else {
            $capabilities['imagick'] = ['loaded' => false];
        }

        // Verificar función imagewebp
        $capabilities['imagewebp_function'] = function_exists('imagewebp');

        // Verificar permisos de escritura
        $upload_dir = wp_get_upload_dir();
        $capabilities['upload_writable'] = is_writable($upload_dir['basedir']);
        
        if (!$capabilities['upload_writable']) {
            $errors[] = 'Directorio de uploads no tiene permisos de escritura: ' . $upload_dir['basedir'];
        }

        // Guardar capacidades
        update_option('scp_webp_capabilities', $capabilities);
        
        // Log errores críticos
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->log_message($error, 'error');
            }
        }

        // Verificar si al menos una extensión soporta WebP
        $webp_support = ($capabilities['gd']['webp_support'] ?? false) || 
                       ($capabilities['imagick']['webp_support'] ?? false);
        
        if (!$webp_support) {
            $this->log_message('Advertencia: Ni GD ni Imagick soportan WebP. La conversión podría fallar.', 'warning');
        }

        return $capabilities;
    }

    /** Sistema de logging mejorado */
    private function log_message(string $message, string $level = 'info', array $context = []) {
        if (!WP_DEBUG_LOG) return;

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_message = sprintf(
            '[%s] SCP WebP Converter [%s]: %s',
            $timestamp,
            strtoupper($level),
            $message
        );

        if (!empty($context)) {
            $formatted_message .= ' Context: ' . wp_json_encode($context);
        }

        error_log($formatted_message);
    }

    /** Detecta si el navegador acepta WebP */
    private function browser_supports_webp(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return (stripos($accept, 'image/webp') !== false);
    }

    private function is_convertible_mime($mime): bool {
        return in_array($mime, ['image/jpeg', 'image/png'], true);
    }

    private function path_from_metadata_item(array $meta, string $size = null): ?string {
        $upload_dir = wp_get_upload_dir();
        if (empty($upload_dir['basedir'])) return null;

        if ($size === null) {
            if (empty($meta['file'])) return null;
            return trailingslashit($upload_dir['basedir']) . $meta['file'];
        }

        if (empty($meta['sizes'][$size]['file'])) return null;

        $base_dir = trailingslashit($upload_dir['basedir']) . dirname($meta['file']);
        return trailingslashit($base_dir) . $meta['sizes'][$size]['file'];
    }

    /** Calcula calidad según MIME */
    private function quality_for_mime(string $mime): int {
        if ($mime === 'image/png')  return (int) get_option(self::OPT_QUALITY_PNG,  self::DEFAULT_Q_PNG);
        if ($mime === 'image/jpeg') return (int) get_option(self::OPT_QUALITY_JPEG, self::DEFAULT_Q_JPEG);
        return self::DEFAULT_Q_JPEG;
    }

    /** Crea .webp al lado del original con doble extensión (file.jpg.webp) solo si no existe */
    private function ensure_webp(string $source_path, int $quality): bool {
        if (!file_exists($source_path)) {
            $this->log_message("Archivo fuente no existe: {$source_path}", 'warning');
            return false;
        }

        $ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $this->log_message("Extensión no soportada: {$ext} para {$source_path}", 'warning');
            return false;
        }

        $target_path = $source_path . '.webp';
        if (file_exists($target_path)) {
            return true; // Ya existe
        }

        // Verificar capacidades antes de proceder
        $capabilities = get_option('scp_webp_capabilities', []);
        $can_convert = ($capabilities['gd']['webp_support'] ?? false) || 
                      ($capabilities['imagick']['webp_support'] ?? false) || 
                      ($capabilities['imagewebp_function'] ?? false);

        if (!$can_convert) {
            $this->log_message('Sin capacidades WebP disponibles para conversión', 'error');
            return false;
        }

        $start_time = microtime(true);
        
        // Intento 1: API de WordPress (GD/Imagick)
        $editor = wp_get_image_editor($source_path);
        if (!is_wp_error($editor)) {
            $size = $editor->get_size();
            if (!is_wp_error($size) && !empty($size['width']) && !empty($size['height'])) {
                if (method_exists($editor, 'set_quality')) {
                    $editor->set_quality(max(0, min(100, (int)$quality)));
                }

                $saved = $editor->save($target_path, 'image/webp');
                if (!is_wp_error($saved) && !empty($saved['path']) && file_exists($saved['path'])) {
                    $duration = round((microtime(true) - $start_time) * 1000, 2);
                    $this->log_message("WebP creado exitosamente: {$target_path} ({$duration}ms)", 'info');
                    return true;
                } else {
                    $error_msg = is_wp_error($saved) ? $saved->get_error_message() : 'Error desconocido';
                    $this->log_message("Falló conversión con editor WP: {$error_msg}", 'warning');
                }
            }
        } else {
            $this->log_message("No se pudo crear editor para: {$source_path} - " . $editor->get_error_message(), 'warning');
        }

        // Intento 2: Fallback con GD directo
        if (function_exists('imagewebp')) {
            $data = file_get_contents($source_path);
            if ($data === false) {
                $this->log_message("No se pudo leer archivo: {$source_path}", 'error');
                return false;
            }
            
            $img = imagecreatefromstring($data);
            if (!$img) {
                $this->log_message("No se pudo crear imagen desde string: {$source_path}", 'error');
                return false;
            }

            // Preservar alfa cuando sea posible (PNG)
            if (in_array($ext, ['png'], true)) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }

            $ok = imagewebp($img, $target_path, max(0, min(100, (int)$quality)));
            imagedestroy($img);
            
            if ($ok) {
                $duration = round((microtime(true) - $start_time) * 1000, 2);
                $this->log_message("WebP creado con GD fallback: {$target_path} ({$duration}ms)", 'info');
                return true;
            } else {
                $this->log_message("Falló creación WebP con GD: {$target_path}", 'error');
            }
        }

        $this->log_message("Falló conversión WebP completa para: {$source_path}", 'error');
        return false;
    }

    /** Hook subida: convertir a WEBP todas las medidas */
    public function generate_webp_for_all_sizes($metadata, $attachment_id) {
        $mime = get_post_mime_type($attachment_id);
        if (!$this->is_convertible_mime($mime)) return $metadata;

        $q = $this->quality_for_mime($mime);

        // Original
        $original_path = $this->path_from_metadata_item($metadata, null);
        if ($original_path) {
            $this->ensure_webp($original_path, $q);
        }

        // Todas las medidas registradas
        $all_sizes = get_intermediate_image_sizes();
        foreach ($all_sizes as $size) {
            $path = $this->path_from_metadata_item($metadata, $size);
            if ($path) $this->ensure_webp($path, $q);
        }

        return $metadata;
    }

    /** Reemplaza URL por su .webp si existe */
    private function to_webp_url_if_exists(string $url): string {
        if (stripos($url, '.webp') !== false) return $url;

        $uploads = wp_get_upload_dir();
        if (empty($uploads['baseurl']) || empty($uploads['basedir'])) return $url;
        if (strpos($url, $uploads['baseurl']) !== 0) return $url;

        $rel  = ltrim(str_replace($uploads['baseurl'], '', $url), '/');
        $path = trailingslashit($uploads['basedir']) . $rel;

        $candidate = $path . '.webp';
        if (file_exists($candidate)) return $url . '.webp';

        return $url;
    }

    /** Hook: cambiar src si el navegador soporta WebP */
    public function maybe_swap_to_webp_src($image, $attachment_id, $size, $icon) {
        if (!$image || !is_array($image) || empty($image[0])) return $image;
        if (!$this->browser_supports_webp()) return $image;

        $mime = get_post_mime_type($attachment_id);
        if (!$this->is_convertible_mime($mime)) return $image;

        $image[0] = $this->to_webp_url_if_exists($image[0]);
        return $image;
    }

    /** Hook: cambiar srcset si el navegador soporta WebP */
    public function maybe_swap_srcset_to_webp($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (!$this->browser_supports_webp()) return $sources;

        $mime = get_post_mime_type($attachment_id);
        if (!$this->is_convertible_mime($mime)) return $sources;
        if (!is_array($sources)) return $sources;

        foreach ($sources as $w => $data) {
            if (!empty($data['url'])) {
                $sources[$w]['url'] = $this->to_webp_url_if_exists($data['url']);
            }
        }
        return $sources;
    }

    /* =========================
     *   AJUSTES + PANTALLA UI
     * ========================= */

    public function add_settings_page() {
        add_options_page(
            'SCP WebP Converter',
            'SCP WebP Converter',
            'manage_options',
            'scp-webp-converter',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('scp_webp_options', self::OPT_QUALITY_JPEG, [
            'type' => 'integer',
            'sanitize_callback' => function($v){ $v=(int)$v; return max(0,min(100,$v)); },
            'default' => self::DEFAULT_Q_JPEG,
        ]);
        register_setting('scp_webp_options', self::OPT_QUALITY_PNG, [
            'type' => 'integer',
            'sanitize_callback' => function($v){ $v=(int)$v; return max(0,min(100,$v)); },
            'default' => self::DEFAULT_Q_PNG,
        ]);

        add_settings_section('scp_webp_section', 'Ajustes de calidad', function(){
            echo '<p>Configura la calidad de compresión WebP por tipo de imagen.</p>';
        }, 'scp-webp-converter');

        add_settings_field(self::OPT_QUALITY_JPEG, 'Calidad WebP para JPEG', function(){
            $val = (int)get_option(self::OPT_QUALITY_JPEG, self::DEFAULT_Q_JPEG);
            echo '<input type="number" min="0" max="100" name="'.esc_attr(self::OPT_QUALITY_JPEG).'" value="'.esc_attr($val).'" /> ';
            echo '<small>Recomendado 80–85</small>';
        }, 'scp-webp-converter', 'scp_webp_section');

        add_settings_field(self::OPT_QUALITY_PNG, 'Calidad WebP para PNG', function(){
            $val = (int)get_option(self::OPT_QUALITY_PNG, self::DEFAULT_Q_PNG);
            echo '<input type="number" min="0" max="100" name="'.esc_attr(self::OPT_QUALITY_PNG).'" value="'.esc_attr($val).'" /> ';
            echo '<small>Si buscas <em>visualmente</em> casi sin pérdidas, 90–100</small>';
        }, 'scp-webp-converter', 'scp_webp_section');
    }

    public function admin_assets($hook) {
        if ($hook !== 'settings_page_scp-webp-converter') return;
        
        wp_enqueue_script(
            'scp-webp-admin', 
            plugins_url('scp-webp-admin.js', __FILE__), 
            ['jquery'], 
            SCP_WEBP_VERSION, 
            true
        );
        
        wp_localize_script('scp-webp-admin', 'SCP_WEBP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scp_webp_nonce'),
            'capabilities' => get_option('scp_webp_capabilities', []),
        ]);
    }

    /** Renderiza información sobre las medidas activas que se convertirán */
    private function render_active_sizes_info() {
        // Obtener una imagen real para probar qué medidas se generan realmente
        $active_sizes = $this->get_actually_active_sizes();
        $global_sizes = wp_get_additional_image_sizes();
        
        echo '<h2>Medidas activas para conversión WebP</h2>';
        echo '<p>El plugin convertirá automáticamente las siguientes medidas de imagen:</p>';
        echo '<div class="notice notice-info" style="padding: 10px;">';
        
        // Tabla con información detallada
        echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">';
        echo '<thead><tr><th style="width: 200px;">Medida</th><th style="width: 100px;">Ancho</th><th style="width: 100px;">Alto</th><th style="width: 100px;">Recortar</th><th>Estado</th><th>Fuente</th></tr></thead>';
        echo '<tbody>';
        
        // Imagen original
        echo '<tr>';
        echo '<td><strong>Original</strong></td>';
        echo '<td>Variable</td>';
        echo '<td>Variable</td>';
        echo '<td>No</td>';
        echo '<td><span style="color: #46b450;">✅ Activa</span></td>';
        echo '<td>Imagen subida original</td>';
        echo '</tr>';
        
        // Medidas predefinidas por WordPress
        $default_sizes = [
            'thumbnail' => 'Miniatura',
            'medium' => 'Medio', 
            'medium_large' => 'Medio-Grande',
            'large' => 'Grande'
        ];
        
        $count = 1; // Empezamos con 1 porque ya contamos el original
        $all_registered = get_intermediate_image_sizes();
        
        foreach ($all_registered as $size_name) {
            $is_active = in_array($size_name, $active_sizes);
            if (!$is_active) continue; // Solo mostrar las activas
            
            $count++;
            
            if (isset($default_sizes[$size_name])) {
                // Medidas por defecto de WordPress
                $width = get_option($size_name . '_size_w', 0);
                $height = get_option($size_name . '_size_h', 0);
                $crop = get_option($size_name . '_crop', 0) ? 'Sí' : 'No';
                $source = 'WordPress (configuración estándar)';
                $display_name = $default_sizes[$size_name] . ' (' . $size_name . ')';
            } elseif (isset($global_sizes[$size_name])) {
                // Medidas personalizadas
                $size_data = $global_sizes[$size_name];
                $width = $size_data['width'] ?? 0;
                $height = $size_data['height'] ?? 0;
                $crop = !empty($size_data['crop']) ? 'Sí' : 'No';
                $source = 'Tema/Plugin personalizado';
                $display_name = ucfirst(str_replace(['_', '-'], ' ', $size_name));
            } else {
                // Medida registrada pero sin información detallada
                $width = '?';
                $height = '?';
                $crop = '?';
                $source = 'Registrado por tema/plugin';
                $display_name = ucfirst(str_replace(['_', '-'], ' ', $size_name));
            }
            
            echo '<tr>';
            echo '<td><code>' . esc_html($display_name) . '</code></td>';
            echo '<td>' . esc_html($width) . 'px</td>';
            echo '<td>' . esc_html($height) . 'px</td>';
            echo '<td>' . esc_html($crop) . '</td>';
            echo '<td><span style="color: #46b450;">✅ Activa</span></td>';
            echo '<td><em>' . esc_html($source) . '</em></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        // Mostrar medidas desactivadas si las hay
        $disabled_sizes = array_diff($all_registered, $active_sizes);
        if (!empty($disabled_sizes)) {
            echo '<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 4px solid #6c757d;">';
            echo '<p><strong>📋 Medidas registradas pero desactivadas (' . count($disabled_sizes) . '):</strong></p>';
            echo '<p style="font-family: monospace; font-size: 12px; color: #666;">';
            echo implode(', ', array_map('esc_html', $disabled_sizes));
            echo '</p>';
            echo '<small><em>Estas medidas no se generarán ni convertirán a WebP.</em></small>';
            echo '</div>';
        }
        
        echo '<div style="margin-top: 15px; padding: 10px; background: #f0f6fc; border-left: 4px solid #0073aa;">';
        echo '<p><strong>📊 Resumen de conversión:</strong></p>';
        echo '<ul style="margin: 5px 0 0 20px;">';
        echo '<li><strong>Total de medidas a convertir:</strong> ' . $count . ' (incluyendo original)</li>';
        echo '<li><strong>Por cada imagen subida:</strong> Se crearán hasta ' . $count . ' archivos .webp</li>';
        echo '<li><strong>Conversión:</strong> Solo se crean archivos .webp que no existan previamente</li>';
        echo '<li><strong>Almacenamiento:</strong> Los archivos .webp se guardan junto a las imágenes originales con extensión .webp adicional</li>';
        echo '</ul>';
        echo '</div>';
        
        // Información adicional sobre medidas innecesarias
        if ($count > 6) {
            echo '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffb900;">';
            echo '<p><strong>⚠️ Advertencia:</strong> Se detectaron muchas medidas activas (' . $count . '). ';
            echo 'Si algunas no son necesarias, considera desactivarlas para optimizar el espacio en disco y el tiempo de conversión.</p>';
            echo '<p><em>Puedes gestionar las medidas desde tu tema o mediante plugins como "Disable Media Sizes".</em></p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /** Detecta qué medidas se generan realmente usando el filtro intermediate_image_sizes_advanced */
    private function get_actually_active_sizes(): array {
        // Simular la generación de metadatos para detectar medidas activas
        $active_sizes = [];
        
        // Hook temporal para capturar las medidas que realmente se procesan
        add_filter('intermediate_image_sizes_advanced', function($sizes) use (&$active_sizes) {
            $active_sizes = array_keys($sizes);
            return $sizes;
        }, 10, 1);
        
        // Crear un array de metadata simulado para activar el filtro
        $fake_metadata = ['width' => 1920, 'height' => 1080, 'file' => 'test.jpg'];
        
        // Esto activará el filtro y nos permitirá capturar las medidas reales
        apply_filters('intermediate_image_sizes_advanced', 
            wp_get_additional_image_sizes(), 
            $fake_metadata, 
            'test'
        );
        
        // Si no se capturó nada con el filtro, usar método alternativo
        if (empty($active_sizes)) {
            $all_sizes = get_intermediate_image_sizes();
            $active_sizes = [];
            
            // Verificar medidas por defecto de WordPress
            foreach (['thumbnail', 'medium', 'medium_large', 'large'] as $size) {
                $width = get_option($size . '_size_w', 0);
                $height = get_option($size . '_size_h', 0);
                
                // Si tiene dimensiones configuradas y está en la lista, está activa
                if (($width > 0 || $height > 0) && in_array($size, $all_sizes)) {
                    $active_sizes[] = $size;
                }
            }
            
            // Añadir medidas personalizadas que están registradas
            $custom_sizes = wp_get_additional_image_sizes();
            foreach ($custom_sizes as $size_name => $size_data) {
                if (in_array($size_name, $all_sizes)) {
                    $active_sizes[] = $size_name;
                }
            }
        }
        
        return array_unique($active_sizes);
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>SCP WebP Converter <span style="font-size: 0.6em; color: #666;">v<?php echo SCP_WEBP_VERSION; ?></span></h1>
            
            <?php $this->render_server_status(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('scp_webp_options');
                do_settings_sections('scp-webp-converter');
                submit_button('Guardar ajustes');
                ?>
            </form>

            <hr>
            <?php $this->render_active_sizes_info(); ?>
            
            <hr>
            <h2>Conversión en biblioteca existente</h2>
            <p>Escanea todas las imágenes JPEG/PNG de la biblioteca y crea los <code>.webp</code> faltantes (original y todas las medidas registradas).</p>

            <button class="button button-primary" id="scp-webp-start">Escanear y convertir faltantes</button>
            <span id="scp-webp-status" style="margin-left:8px;"></span>

            <div id="scp-webp-log" style="margin-top:12px; max-height:320px; overflow:auto; background:#fff; border:1px solid #ccd0d4; padding:8px; display:none;"></div>

            <p style="margin-top:12px;">
                <strong>Consejo:</strong> para librerías muy grandes, usa también el comando WP-CLI:<br>
                <code>wp scp-webp/convert-missing --batch=50</code><br>
                <small>Presiona <kbd>Ctrl+Shift+W</kbd> para iniciar conversión rápida.</small>
            </p>
        </div>
        <?php
    }

    /** Renderiza el estado del servidor */
    private function render_server_status() {
        $capabilities = get_option('scp_webp_capabilities', []);
        
        echo '<div class="notice" style="padding: 10px; margin: 10px 0;">';
        echo '<h3>Estado del servidor</h3>';
        
        // GD Status
        if ($capabilities['gd']['loaded'] ?? false) {
            $webp_support = $capabilities['gd']['webp_support'] ?? false;
            $icon = $webp_support ? '✅' : '⚠️';
            $status = $webp_support ? 'con soporte WebP' : 'sin soporte WebP';
            echo "<p>{$icon} <strong>GD:</strong> Disponible {$status} (" . ($capabilities['gd']['version'] ?? 'Unknown') . ")</p>";
        } else {
            echo "<p>❌ <strong>GD:</strong> No disponible</p>";
        }
        
        // Imagick Status
        if ($capabilities['imagick']['loaded'] ?? false) {
            $webp_support = $capabilities['imagick']['webp_support'] ?? false;
            $icon = $webp_support ? '✅' : '⚠️';
            $status = $webp_support ? 'con soporte WebP' : 'sin soporte WebP';
            echo "<p>{$icon} <strong>Imagick:</strong> Disponible {$status}</p>";
        } else {
            echo "<p>⚠️ <strong>Imagick:</strong> No disponible</p>";
        }
        
        // Upload directory permissions
        $writable = $capabilities['upload_writable'] ?? false;
        $icon = $writable ? '✅' : '❌';
        $status = $writable ? 'escribible' : 'sin permisos de escritura';
        echo "<p>{$icon} <strong>Directorio uploads:</strong> {$status}</p>";
        
        // Overall status
        $can_convert = (($capabilities['gd']['webp_support'] ?? false) || 
                       ($capabilities['imagick']['webp_support'] ?? false)) && 
                       ($capabilities['upload_writable'] ?? false);
        
        if ($can_convert) {
            echo '<p style="color: #46b450; font-weight: bold;">🎉 <strong>Estado:</strong> Listo para convertir WebP</p>';
        } else {
            echo '<p style="color: #dc3232; font-weight: bold;">⚠️ <strong>Estado:</strong> Configuración incompleta - revisa los requisitos</p>';
        }
        
        echo '</div>';
    }

    /* =========================
     *   CONVERSIÓN POR LOTES
     * ========================= */

    /**
     * Convierte un conjunto de adjuntos (por IDs) generando .webp faltantes
     * Devuelve array con totales.
     */
    private function convert_batch(array $attachment_ids): array {
        $converted = 0;
        $checked   = 0;

        foreach ($attachment_ids as $att_id) {
            $mime = get_post_mime_type($att_id);
            if (!$this->is_convertible_mime($mime)) continue;

            $meta = wp_get_attachment_metadata($att_id);
            if (empty($meta)) {
                // Intentar generar metadata si falta
                $file = get_attached_file($att_id);
                if ($file && file_exists($file)) {
                    $generated = wp_generate_attachment_metadata($att_id, $file);
                    if (!is_wp_error($generated) && !empty($generated)) {
                        wp_update_attachment_metadata($att_id, $generated);
                        $meta = $generated;
                    }
                }
            }
            if (empty($meta)) continue;

            $q = $this->quality_for_mime($mime);

            // Original
            $orig = $this->path_from_metadata_item($meta, null);
            if ($orig) {
                $checked++;
                if (!file_exists($orig . '.webp')) {
                    if ($this->ensure_webp($orig, $q)) $converted++;
                }
            }

            // Todas las medidas registradas (no solo las presentes en metadata)
            $all_sizes = get_intermediate_image_sizes();
            foreach ($all_sizes as $size) {
                $p = $this->path_from_metadata_item($meta, $size);
                if (!$p) continue;
                $checked++;
                if (!file_exists($p . '.webp')) {
                    if ($this->ensure_webp($p, $q)) $converted++;
                }
            }
        }

        return [
            'checked'   => $checked,
            'converted' => $converted,
        ];
    }

    /** AJAX: procesa por páginas (offset/limit) para no agotar tiempo */
    public function ajax_convert_missing() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permiso denegado'], 403);
        check_ajax_referer('scp_webp_nonce', 'nonce');

        $offset = isset($_POST['offset']) ? max(0, (int)$_POST['offset']) : 0;
        $limit  = isset($_POST['limit'])  ? max(1, min(200, (int)$_POST['limit'])) : 50;

        $q = new WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => ['image/jpeg','image/png'],
            'fields'         => 'ids',
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => false, // para obtener found_posts
        ]);

        $total = (int)$q->found_posts;
        $ids   = $q->posts;

        $result = $this->convert_batch($ids);
        $next_offset = $offset + count($ids);
        $done = $next_offset >= $total;

        wp_send_json_success([
            'processed' => count($ids),
            'checked'   => $result['checked'],
            'converted' => $result['converted'],
            'total'     => $total,
            'offset'    => $next_offset,
            'done'      => $done,
        ]);
    }

    /** AJAX: convierte una sola imagen y todas sus medidas a WebP */
    public function ajax_convert_single() {
        if (!current_user_can('upload_files')) wp_send_json_error(['message' => 'Permiso denegado'], 403);
        check_ajax_referer('scp_webp_nonce', 'nonce');

        $attachment_id = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;
        if (!$attachment_id) {
            wp_send_json_error(['message' => 'ID de imagen no válido']);
        }

        // Verificar que es una imagen convertible
        $mime = get_post_mime_type($attachment_id);
        if (!$this->is_convertible_mime($mime)) {
            wp_send_json_error(['message' => 'Tipo de archivo no compatible para conversión WebP']);
        }

        $force_reconvert = isset($_POST['force']) && $_POST['force'] === '1';
        $result = $this->convert_single_attachment($attachment_id, $force_reconvert);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'converted' => $result['converted'],
                'checked' => $result['checked'],
                'webp_status' => $this->get_attachment_webp_status($attachment_id)
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /** Convierte un adjunto específico y devuelve resultado */
    private function convert_single_attachment(int $attachment_id, bool $force = false): array {
        $mime = get_post_mime_type($attachment_id);
        if (!$this->is_convertible_mime($mime)) {
            return ['success' => false, 'message' => 'Tipo de archivo no compatible'];
        }

        $meta = wp_get_attachment_metadata($attachment_id);
        if (empty($meta)) {
            // Intentar generar metadata si falta
            $file = get_attached_file($attachment_id);
            if ($file && file_exists($file)) {
                $generated = wp_generate_attachment_metadata($attachment_id, $file);
                if (!is_wp_error($generated) && !empty($generated)) {
                    wp_update_attachment_metadata($attachment_id, $generated);
                    $meta = $generated;
                }
            }
        }
        
        if (empty($meta)) {
            return ['success' => false, 'message' => 'No se pudieron obtener los metadatos de la imagen'];
        }

        $q = $this->quality_for_mime($mime);
        $converted = 0;
        $checked = 0;
        
        // Original
        $orig = $this->path_from_metadata_item($meta, null);
        if ($orig) {
            $checked++;
            if ($force || !file_exists($orig . '.webp')) {
                if ($force && file_exists($orig . '.webp')) {
                    unlink($orig . '.webp');
                }
                if ($this->ensure_webp($orig, $q)) $converted++;
            }
        }

        // Todas las medidas activas
        $active_sizes = $this->get_actually_active_sizes();
        foreach ($active_sizes as $size) {
            $p = $this->path_from_metadata_item($meta, $size);
            if (!$p) continue;
            
            $checked++;
            if ($force || !file_exists($p . '.webp')) {
                if ($force && file_exists($p . '.webp')) {
                    unlink($p . '.webp');
                }
                if ($this->ensure_webp($p, $q)) $converted++;
            }
        }

        $total_possible = $checked;
        $action = $force ? 'reconvertida' : 'convertida';
        
        if ($converted > 0) {
            return [
                'success' => true, 
                'message' => "Imagen {$action} exitosamente: {$converted}/{$total_possible} archivos WebP creados",
                'converted' => $converted,
                'checked' => $checked
            ];
        } else {
            $existing_msg = $force ? 'Error durante la reconversión' : 'Todos los archivos WebP ya existen';
            return [
                'success' => true, 
                'message' => $existing_msg,
                'converted' => $converted,
                'checked' => $checked
            ];
        }
    }

    /* =========================
     *   INTEGRACIÓN MEDIA LIBRARY
     * ========================= */

    /** Añade botón en las acciones de fila de Media Library (vista lista) */
    public function add_media_row_actions($actions, $post) {
        if (!$this->is_convertible_mime($post->post_mime_type)) {
            return $actions;
        }

        $webp_status = $this->get_attachment_webp_status($post->ID);
        
        if ($webp_status['has_webp']) {
            $actions['reconvert_webp'] = sprintf(
                '<a href="#" class="scp-webp-reconvert" data-id="%d" data-force="1">🔄 Reconvertir WebP</a>',
                $post->ID
            );
        } else {
            $actions['convert_webp'] = sprintf(
                '<a href="#" class="scp-webp-convert" data-id="%d">🖼️ Convertir a WebP</a>',
                $post->ID
            );
        }

        return $actions;
    }

    /** Añade campo WebP en el modal de edición de medios */
    public function add_webp_field_to_edit($fields, $post) {
        if (!$this->is_convertible_mime($post->post_mime_type)) {
            return $fields;
        }

        $webp_status = $this->get_attachment_webp_status($post->ID);
        $status_html = '';
        
        if ($webp_status['has_webp']) {
            $status_html .= '<p style="color: #46b450; font-weight: bold;">✅ WebP disponible</p>';
            $status_html .= '<p><small>Archivos WebP: ' . $webp_status['webp_count'] . '/' . $webp_status['total_count'] . '</small></p>';
            $status_html .= '<button type="button" class="button scp-webp-reconvert" data-id="' . $post->ID . '" data-force="1">🔄 Reconvertir WebP</button>';
        } else {
            $status_html .= '<p style="color: #dc3232;">❌ Sin WebP</p>';
            $status_html .= '<button type="button" class="button button-primary scp-webp-convert" data-id="' . $post->ID . '">🖼️ Convertir a WebP</button>';
        }
        
        $status_html .= '<div class="scp-webp-result" style="margin-top: 10px;"></div>';

        $fields['scp_webp_status'] = [
            'label' => 'Estado WebP',
            'input' => 'html',
            'html' => $status_html,
            'show_in_edit' => true,
        ];

        return $fields;
    }

    /** Obtiene el estado WebP de un adjunto */
    private function get_attachment_webp_status(int $attachment_id): array {
        $meta = wp_get_attachment_metadata($attachment_id);
        if (empty($meta)) {
            return ['has_webp' => false, 'webp_count' => 0, 'total_count' => 0];
        }

        $webp_count = 0;
        $total_count = 0;

        // Verificar original
        $orig = $this->path_from_metadata_item($meta, null);
        if ($orig) {
            $total_count++;
            if (file_exists($orig . '.webp')) $webp_count++;
        }

        // Verificar medidas activas
        $active_sizes = $this->get_actually_active_sizes();
        foreach ($active_sizes as $size) {
            $p = $this->path_from_metadata_item($meta, $size);
            if (!$p) continue;
            
            $total_count++;
            if (file_exists($p . '.webp')) $webp_count++;
        }

        return [
            'has_webp' => $webp_count > 0,
            'webp_count' => $webp_count,
            'total_count' => $total_count,
            'is_complete' => $webp_count === $total_count && $total_count > 0
        ];
    }

    /** Enqueue scripts para Media Library */
    public function enqueue_media_assets() {
        wp_enqueue_script(
            'scp-webp-media', 
            plugins_url('scp-webp-media.js', __FILE__), 
            ['jquery'], 
            SCP_WEBP_VERSION, 
            true
        );
        
        wp_localize_script('scp-webp-media', 'SCP_WEBP_MEDIA', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scp_webp_nonce'),
        ]);
    }

    /** Añade acciones en lote a Media Library */
    public function add_bulk_actions($actions) {
        $actions['convert_to_webp'] = 'Convertir a WebP';
        $actions['reconvert_to_webp'] = 'Reconvertir a WebP (forzar)';
        return $actions;
    }

    /** Maneja las acciones en lote */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action !== 'convert_to_webp' && $action !== 'reconvert_to_webp') {
            return $redirect_to;
        }

        if (empty($post_ids)) {
            return $redirect_to;
        }

        $force = ($action === 'reconvert_to_webp');
        $converted = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($post_ids as $post_id) {
            $mime = get_post_mime_type($post_id);
            
            // Solo procesar imágenes convertibles
            if (!$this->is_convertible_mime($mime)) {
                $skipped++;
                continue;
            }

            $result = $this->convert_single_attachment($post_id, $force);
            if ($result['success'] && $result['converted'] > 0) {
                $converted++;
            } elseif (!$result['success']) {
                $errors++;
            }
        }

        // Preparar mensaje de resultado
        $messages = [];
        if ($converted > 0) {
            $messages[] = sprintf('%d imagen(es) convertida(s) a WebP', $converted);
        }
        if ($skipped > 0) {
            $messages[] = sprintf('%d imagen(es) omitida(s) (formato no compatible)', $skipped);
        }
        if ($errors > 0) {
            $messages[] = sprintf('%d error(es) durante la conversión', $errors);
        }

        $message = implode(', ', $messages);
        if (empty($message)) {
            $message = 'Todas las imágenes seleccionadas ya tienen archivos WebP';
        }

        // Añadir mensaje a la URL de redirección
        $redirect_to = add_query_arg([
            'scp_webp_bulk_result' => urlencode($message),
            'scp_webp_converted' => $converted,
            'scp_webp_errors' => $errors,
            'scp_webp_skipped' => $skipped
        ], $redirect_to);

        return $redirect_to;
    }

    /** WP-CLI: wp scp-webp/convert-missing --batch=50 */
    public function wpcli_convert_missing($args, $assoc_args) {
        $batch = isset($assoc_args['batch']) ? max(1, (int)$assoc_args['batch']) : 50;

        $offset = 0;
        $total_converted = 0;
        $total_checked   = 0;

        do {
            $q = new WP_Query([
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => ['image/jpeg','image/png'],
                'fields'         => 'ids',
                'posts_per_page' => $batch,
                'offset'         => $offset,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'no_found_rows'  => false,
            ]);

            if (!$q->have_posts()) break;

            $res = $this->convert_batch($q->posts);
            $total_converted += $res['converted'];
            $total_checked   += $res['checked'];

            \WP_CLI::log(sprintf(
                'Procesados %d adjuntos (checked %d, converted %d) [%d/%d]',
                count($q->posts),
                $res['checked'],
                $res['converted'],
                min($offset + count($q->posts), (int)$q->found_posts),
                (int)$q->found_posts
            ));

            $offset += count($q->posts);
            if ($offset >= (int)$q->found_posts) break;
        } while (true);

        \WP_CLI::success(sprintf('Listo. Revisadas %d imágenes/medidas, creados %d WEBP.', $total_checked, $total_converted));
    }
}

// Inicializar el plugin
new SCP_WebP_Converter();

/* =========================
 *   FILTROS Y HOOKS EXTRAS
 * ========================= */

// Filtro para personalizar calidad por imagen específica
add_filter('scp_webp_quality', function($quality, $attachment_id, $mime) {
    // Los desarrolladores pueden usar este filtro para ajustar calidad
    return apply_filters('scp_webp_custom_quality', $quality, $attachment_id, $mime);
}, 10, 3);

// Hook informativo sobre el progreso del plugin
add_action('wp_loaded', function() {
    if (is_admin() && current_user_can('manage_options')) {
        $capabilities = get_option('scp_webp_capabilities');
        if (empty($capabilities)) {
            // Trigger capabilities check on first load
            $instance = new SCP_WebP_Converter();
            $instance->check_server_capabilities();
        }
    }
});

/* =========================
 *   PREPARACIÓN AVIF (v2.0)
 * ========================= */
// Placeholder para futuras funcionalidades AVIF
// - Detección de soporte AVIF en navegadores
// - Conversión a formato AVIF
// - Fallback inteligente WebP -> AVIF -> Original
// - Configuraciones de calidad específicas para AVIF
