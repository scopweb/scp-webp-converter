<?php
/**
 * Plugin Name: SCP WebP Converter for WordPress
 * Plugin URI: https://github.com/your-username/webp-converter
 * Description: Automatically converts JPEG and PNG images to WebP format with support for all image sizes. Features bulk conversion, smart format detection, and configurable file naming conventions.
 * Version: 1.3.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * Text Domain: scp-webp-converter
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if (!defined('ABSPATH')) exit;

// Definir constantes del plugin
define('SCP_WEBP_VERSION', '1.3.0');
define('SCP_WEBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCP_WEBP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Clase principal del plugin - Orchestrador
 */
class SCP_WebP_Converter_Plugin {
    
    private $converter;
    private $admin;
    private $renamer;
    private $content_processor;
    
    /**
     * Constructor - Inicializa el plugin
     */
    public function __construct() {
        // Cargar dependencias
        $this->load_dependencies();
        
        // Inicializar componentes
        $this->init_components();
        
        // Configurar hooks
        $this->setup_hooks();
    }
    
    /**
     * Carga todas las dependencias del plugin
     */
    private function load_dependencies() {
        // Configuración y utilidades
        require_once SCP_WEBP_PLUGIN_DIR . 'includes/core/class-webp-config.php';
        require_once SCP_WEBP_PLUGIN_DIR . 'includes/utils/class-webp-logger.php';
        require_once SCP_WEBP_PLUGIN_DIR . 'includes/utils/class-webp-capabilities.php';
        
        // Núcleo de conversión
        require_once SCP_WEBP_PLUGIN_DIR . 'includes/core/class-webp-converter.php';
        
        // Renombrador de archivos
        require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-renamer.php';
        require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-renamer-admin.php';
        
        // Interfaz administrativa
        if (is_admin()) {
            require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-admin.php';
            require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-bulk-converter.php';
            require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-orphan-cleaner.php';
            require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-orphan-cleaner-admin.php';
            require_once SCP_WEBP_PLUGIN_DIR . 'includes/core/class-webp-content-processor.php';
        }
        
        // Procesador de contenido (siempre necesario para frontend)
        require_once SCP_WEBP_PLUGIN_DIR . 'includes/core/class-webp-content-processor.php';
    }
    
    /**
     * Inicializa los componentes principales
     */
    private function init_components() {
        // Inicializar configuración
        SCP_WebP_Config::init_default_options();
        
        // Núcleo de conversión
        $this->converter = new SCP_WebP_Core_Converter();
        
        // Renombrador
        $this->renamer = new SCP_WebP_Renamer();
        
        // Procesador de contenido
        $this->content_processor = new SCP_WebP_Content_Processor($this->converter);
        
        // Admin (solo en admin)
        if (is_admin()) {
            $this->admin = new SCP_WebP_Admin();
            // Inicializar el bulk converter para los handlers AJAX
            new SCP_WebP_Bulk_Converter();
            // Inicializar el limpiador de huérfanos
            new SCP_WebP_Orphan_Cleaner_Admin();
        }
    }
    
    /**
     * Configura todos los hooks del WordPress
     */
    private function setup_hooks() {
        // Hooks de activación
        register_activation_hook(__FILE__, [$this, 'on_activation']);
        
        // Verificar capacidades del servidor
        add_action('admin_init', [SCP_WebP_Capabilities::class, 'check_server_capabilities']);
        
        // Hooks de conversión de imágenes
        add_filter('wp_generate_attachment_metadata', [$this->converter, 'generate_webp_for_all_sizes'], 20, 2);
        
        // Hooks de serving WebP
        add_filter('wp_get_attachment_image_src', [$this->converter, 'maybe_swap_to_webp_src'], 20, 4);
        add_filter('wp_calculate_image_srcset', [$this->converter, 'maybe_swap_srcset_to_webp'], 20, 5);
        
        // Hook de procesamiento de contenido
        add_filter('the_content', [$this->content_processor, 'process_content_images'], 20, 1);
        
        // Integración con Media Library
        if (is_admin()) {
            add_filter('attachment_fields_to_edit', [$this, 'add_webp_field_to_edit'], 10, 2);
            add_action('wp_enqueue_media', [$this, 'enqueue_media_assets']);
            add_filter('media_row_actions', [$this, 'add_media_row_actions'], 10, 2);
            add_filter('bulk_actions-upload', [$this, 'add_bulk_actions']);
            add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_actions'], 10, 3);
            add_action('admin_notices', [$this, 'show_bulk_action_notices']);
        }
        
        // WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('scp-webp/convert-missing', [$this, 'wpcli_convert_missing']);
            \WP_CLI::add_command('scp-webp/clean-orphans', [$this, 'wpcli_clean_orphans']);
        }
    }
    
    /**
     * Hook de activación del plugin
     */
    public function on_activation() {
        SCP_WebP_Capabilities::check_server_capabilities();
        SCP_WebP_Logger::info('Plugin SCP WebP Converter activado correctamente');
    }
    
    /**
     * Añade campo WebP en el modal de edición de medios
     */
    public function add_webp_field_to_edit($fields, $post) {
        if (!$this->converter->is_convertible_mime($post->post_mime_type)) {
            return $fields;
        }

        $webp_status = $this->get_attachment_webp_status($post->ID);
        $status_html = '';
        
        if ($webp_status['has_webp']) {
            $status_html .= '<p style="color: #46b450; font-weight: bold;">WebP disponible</p>';
            $status_html .= '<p><small>Archivos WebP: ' . $webp_status['webp_count'] . '/' . $webp_status['total_count'] . '</small></p>';
            $status_html .= '<button type="button" class="button scp-webp-reconvert" data-id="' . $post->ID . '" data-force="1">Reconvertir WebP</button>';
        } else {
            $status_html .= '<p style="color: #dc3232;">Sin WebP</p>';
            $status_html .= '<button type="button" class="button button-primary scp-webp-convert" data-id="' . $post->ID . '">Convertir WebP</button>';
        }
        
        $status_html .= '<hr><div class="scp-webp-result" style="margin-top: 10px;"></div>';

        $fields['scp_webp_status'] = [
            'label' => 'WebP',
            'input' => 'html',
            'html' => $status_html,
            'show_in_edit' => true,
        ];

        return $fields;
    }
    
    /**
     * Obtiene el estado WebP de un adjunto
     */
    private function get_attachment_webp_status(int $attachment_id): array {
        $meta = wp_get_attachment_metadata($attachment_id);
        if (empty($meta)) {
            return ['has_webp' => false, 'webp_count' => 0, 'total_count' => 0];
        }

        $webp_count = 0;
        $total_count = 0;

        // Verificar original
        $orig = $this->converter->path_from_metadata_item($meta, null);
        if ($orig) {
            $total_count++;
            if ($this->converter->has_webp_file($orig)) $webp_count++;
        }

        // Verificar medidas activas
        $active_sizes = get_intermediate_image_sizes();
        foreach ($active_sizes as $size) {
            $p = $this->converter->path_from_metadata_item($meta, $size);
            if (!$p) continue;
            
            $total_count++;
            if ($this->converter->has_webp_file($p)) $webp_count++;
        }

        return [
            'has_webp' => $webp_count > 0,
            'webp_count' => $webp_count,
            'total_count' => $total_count,
            'is_complete' => $webp_count === $total_count && $total_count > 0
        ];
    }
    
    /**
     * Enqueue scripts para Media Library
     */
    public function enqueue_media_assets() {
        wp_enqueue_script(
            'scp-webp-media', 
            SCP_WEBP_PLUGIN_URL . 'assets/js/media.js', 
            ['jquery'], 
            SCP_WEBP_VERSION, 
            true
        );
        
        wp_localize_script('scp-webp-media', 'SCP_WEBP_MEDIA', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scp_webp_nonce'),
        ]);
    }
    
    /**
     * Añade acciones en las filas de Media Library
     */
    public function add_media_row_actions($actions, $post) {
        if (!$this->converter->is_convertible_mime($post->post_mime_type)) {
            return $actions;
        }

        $webp_status = $this->get_attachment_webp_status($post->ID);
        
        if ($webp_status['has_webp']) {
            $actions['reconvert_webp'] = sprintf(
                '<a href="#" class="scp-webp-reconvert" data-id="%d" data-force="1">Reconvertir WebP</a>',
                $post->ID
            );
        } else {
            $actions['convert_webp'] = sprintf(
                '<a href="#" class="scp-webp-convert" data-id="%d">Convertir a WebP</a>',
                $post->ID
            );
        }

        return $actions;
    }
    
    /**
     * Añade acciones en lote a Media Library
     */
    public function add_bulk_actions($actions) {
        $actions['convert_to_webp'] = 'Convertir a WebP';
        $actions['reconvert_to_webp'] = 'Reconvertir a WebP (forzar)';
        return $actions;
    }
    
    /**
     * Maneja las acciones en lote
     */
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

        $bulk_converter = new SCP_WebP_Bulk_Converter();
        
        foreach ($post_ids as $post_id) {
            $mime = get_post_mime_type($post_id);
            
            if (!$this->converter->is_convertible_mime($mime)) {
                $skipped++;
                continue;
            }

            $result = $bulk_converter->convert_single_attachment($post_id, $force);
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

        $redirect_to = add_query_arg([
            'scp_webp_bulk_result' => urlencode($message),
            'scp_webp_converted' => $converted,
            'scp_webp_errors' => $errors,
            'scp_webp_skipped' => $skipped
        ], $redirect_to);

        return $redirect_to;
    }
    
    /**
     * Muestra mensajes de las acciones en lote
     */
    public function show_bulk_action_notices() {
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
    
    /** WP-CLI: wp scp-webp/convert-missing --batch=50 */
    public function wpcli_convert_missing($args, $assoc_args) {
        $batch = isset($assoc_args['batch']) ? max(1, (int)$assoc_args['batch']) : 50;

        $offset = 0;
        $total_converted = 0;
        $total_checked   = 0;

        $bulk_converter = new SCP_WebP_Bulk_Converter();

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

            $res = $bulk_converter->convert_batch($q->posts);
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

    /** WP-CLI: wp scp-webp/clean-orphans */
    public function wpcli_clean_orphans($args, $assoc_args) {
        $cleaner = new SCP_WebP_Orphan_Cleaner();

        \WP_CLI::log('Escaneando archivos WebP huérfanos...');
        $orphaned_files = $cleaner->find_orphaned_webp_files();

        if (empty($orphaned_files)) {
            \WP_CLI::success('No se encontraron archivos WebP huérfanos.');
            return;
        }

        $total_size = 0;
        foreach ($orphaned_files as $file) {
            $total_size += $file['size'];
        }

        \WP_CLI::log(sprintf('Encontrados %d archivos WebP huérfanos (%s)', count($orphaned_files), $cleaner->format_size($total_size)));

        // Mostrar algunos ejemplos
        $show_count = min(5, count($orphaned_files));
        \WP_CLI::log('Ejemplos de archivos huérfanos:');
        for ($i = 0; $i < $show_count; $i++) {
            $file = $orphaned_files[$i];
            $relative_path = str_replace(wp_get_upload_dir()['basedir'], '', $file['webp_path']);
            \WP_CLI::log("  - {$relative_path} ({$cleaner->format_size($file['size'])})");
        }

        if (count($orphaned_files) > $show_count) {
            \WP_CLI::log("  ... y " . (count($orphaned_files) - $show_count) . " archivos más");
        }

        // Confirmar antes de eliminar
        if (!isset($assoc_args['yes'])) {
            fwrite(STDERR, "\n¿Deseas eliminar estos archivos? (y/N): ");
            $confirmation = strtolower(trim(fgets(STDIN)));
            if ($confirmation !== 'y' && $confirmation !== 'yes') {
                \WP_CLI::log('Operación cancelada.');
                return;
            }
        }

        \WP_CLI::log('Eliminando archivos huérfanos...');
        $result = $cleaner->clean_orphaned_files($orphaned_files);

        \WP_CLI::success(sprintf(
            'Limpieza completada: %d archivos eliminados, %s liberados%s',
            $result['deleted'],
            $cleaner->format_size($result['total_size']),
            $result['errors'] > 0 ? ", {$result['errors']} errores" : ''
        ));
    }
}

// Inicializar el plugin
add_action('plugins_loaded', function() {
    new SCP_WebP_Converter_Plugin();
});

// Hook informativo sobre el progreso del plugin
add_action('wp_loaded', function() {
    if (is_admin() && current_user_can('manage_options')) {
        $capabilities = get_option('scp_webp_capabilities');
        if (empty($capabilities)) {
            SCP_WebP_Capabilities::check_server_capabilities();
        }
    }
});