<?php
/**
 * Interfaz para conversión masiva de imágenes WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Bulk_Converter {
    
    private $converter;
    
    public function __construct() {
        $this->converter = new SCP_WebP_Core_Converter();
        
        // AJAX para conversión por lotes
        add_action('wp_ajax_scp_webp_convert_missing', [$this, 'ajax_convert_missing']);
        add_action('wp_ajax_scp_webp_convert_single', [$this, 'ajax_convert_single']);
    }

    /**
     * Renderiza la pestaña de conversión masiva
     */
    public function render_bulk_tab() {
        ?>
        <div class="scp-webp-tab-content">
            <h2>Conversión en Biblioteca Existente</h2>
            <p>Escanea todas las imágenes JPEG/PNG de la biblioteca y crea los <code>.webp</code> faltantes (original y todas las medidas registradas).</p>

            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">🚀 Conversión Automática</h3>
                    <p>Procesamiento por lotes con progreso en tiempo real:</p>
                    
                    <button class="button button-primary button-large" id="scp-webp-start" style="margin-bottom: 15px;">
                        🔄 Escanear y Convertir Faltantes
                    </button>
                    <span id="scp-webp-status" style="margin-left: 10px; font-weight: bold;"></span>

                    <div id="scp-webp-log" style="margin-top: 15px; max-height: 400px; overflow: auto; background: #fff; border: 1px solid #ccd0d4; padding: 12px; display: none; font-family: monospace; font-size: 12px;"></div>
                </div>
            </div>

            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">⚡ WP-CLI (Recomendado para sitios grandes)</h3>
                    <p>Para bibliotecas muy grandes, usa el comando WP-CLI que es más eficiente:</p>
                    <pre style="background: #2c3338; color: #fff; padding: 15px; border-radius: 4px; overflow-x: auto;">
<span style="color: #50c878;"># Conversión básica</span>
wp scp-webp/convert-missing --batch=50

<span style="color: #50c878;"># Ajustar según recursos del servidor</span>
wp scp-webp/convert-missing --batch=100  <span style="color: #888;"># Más rápido</span>
wp scp-webp/convert-missing --batch=25   <span style="color: #888;"># Más conservativo</span></pre>
                    
                    <div class="notice notice-info inline" style="margin: 15px 0; padding: 12px;">
                        <p><strong>💡 Consejo:</strong> Presiona <kbd>Ctrl+Shift+W</kbd> para iniciar conversión rápida desde cualquier lugar de esta página.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
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
            'no_found_rows'  => false,
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

    /**
     * Convierte un conjunto de adjuntos (por IDs) generando .webp faltantes
     * Devuelve array con totales.
     */
    public function convert_batch(array $attachment_ids): array {
        $converted = 0;
        $checked   = 0;

        foreach ($attachment_ids as $att_id) {
            $mime = get_post_mime_type($att_id);
            if (!$this->converter->is_convertible_mime($mime)) continue;

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

            $q = $this->converter->quality_for_mime($mime);

            // Original
            $orig = $this->converter->path_from_metadata_item($meta, null);
            if ($orig) {
                $checked++;
                if (!$this->converter->has_webp_file($orig)) {
                    if ($this->converter->ensure_webp($orig, $q)) $converted++;
                }
            }

            // Todas las medidas registradas (no solo las presentes en metadata)
            $all_sizes = get_intermediate_image_sizes();
            foreach ($all_sizes as $size) {
                $p = $this->converter->path_from_metadata_item($meta, $size);
                if (!$p) continue;
                $checked++;
                if (!$this->converter->has_webp_file($p)) {
                    if ($this->converter->ensure_webp($p, $q)) $converted++;
                }
            }
        }

        return [
            'checked'   => $checked,
            'converted' => $converted,
        ];
    }

    /** AJAX: convierte una sola imagen y todas sus medidas a WebP */
    public function ajax_convert_single() {
        // Log de depuración
        error_log('SCP WebP: ajax_convert_single called with POST: ' . print_r($_POST, true));
        
        if (!current_user_can('upload_files')) {
            error_log('SCP WebP: Permission denied for user');
            wp_send_json_error(['message' => 'Permiso denegado'], 403);
        }
        
        check_ajax_referer('scp_webp_nonce', 'nonce');

        $attachment_id = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;
        if (!$attachment_id) {
            error_log('SCP WebP: Invalid attachment ID: ' . $attachment_id);
            wp_send_json_error(['message' => 'ID de imagen no válido']);
        }

        // Verificar que es una imagen convertible
        $mime = get_post_mime_type($attachment_id);
        if (!$this->converter->is_convertible_mime($mime)) {
            error_log('SCP WebP: Non-convertible mime type: ' . $mime);
            wp_send_json_error(['message' => 'Tipo de archivo no compatible para conversión WebP']);
        }

        $force_reconvert = isset($_POST['force']) && $_POST['force'] === '1';
        error_log('SCP WebP: Converting attachment ' . $attachment_id . ' (force: ' . ($force_reconvert ? 'yes' : 'no') . ')');
        
        $result = $this->convert_single_attachment($attachment_id, $force_reconvert);
        
        if ($result['success']) {
            // Obtener el estado WebP actualizado para el JavaScript
            $webp_status = $this->get_attachment_webp_status($attachment_id);
            
            error_log('SCP WebP: Conversion successful for attachment ' . $attachment_id);
            wp_send_json_success([
                'message' => $result['message'],
                'converted' => $result['converted'],
                'checked' => $result['checked'],
                'webp_status' => $webp_status
            ]);
        } else {
            error_log('SCP WebP: Conversion failed for attachment ' . $attachment_id . ': ' . $result['message']);
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /** Convierte un adjunto específico y devuelve resultado */
    public function convert_single_attachment(int $attachment_id, bool $force = false): array {
        $mime = get_post_mime_type($attachment_id);
        if (!$this->converter->is_convertible_mime($mime)) {
            return ['success' => false, 'message' => 'Tipo de archivo no compatible'];
        }

        $meta = wp_get_attachment_metadata($attachment_id);
        if (empty($meta)) {
            return ['success' => false, 'message' => 'No se pudieron obtener los metadatos de la imagen'];
        }

        $q = $this->converter->quality_for_mime($mime);
        $converted = 0;
        $checked = 0;
        
        // Original
        $orig = $this->converter->path_from_metadata_item($meta, null);
        if ($orig) {
            $checked++;
            if ($force || !$this->converter->has_webp_file($orig)) {
                if ($force) {
                    // Eliminar archivos WebP existentes según configuración y fallback
                    $webp_paths = $this->converter->get_webp_path($orig);
                    foreach ($webp_paths as $webp_path) {
                        if (file_exists($webp_path)) {
                            unlink($webp_path);
                        }
                    }
                }
                if ($this->converter->ensure_webp($orig, $q)) $converted++;
            }
        }

        // Todas las medidas activas
        $active_sizes = get_intermediate_image_sizes();
        foreach ($active_sizes as $size) {
            $p = $this->converter->path_from_metadata_item($meta, $size);
            if (!$p) continue;
            
            $checked++;
            if ($force || !$this->converter->has_webp_file($p)) {
                if ($force) {
                    // Eliminar archivos WebP existentes según configuración y fallback
                    $webp_paths = $this->converter->get_webp_path($p);
                    foreach ($webp_paths as $webp_path) {
                        if (file_exists($webp_path)) {
                            unlink($webp_path);
                        }
                    }
                }
                if ($this->converter->ensure_webp($p, $q)) $converted++;
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
}