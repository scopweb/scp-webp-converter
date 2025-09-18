<?php
/**
 * Clase principal para conversión WebP
 * Maneja la lógica central de conversión de imágenes
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Core_Converter {
    
    /**
     * Detecta si el navegador acepta WebP
     */
    public function browser_supports_webp(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return (stripos($accept, 'image/webp') !== false);
    }

    /**
     * Verifica si el MIME type es convertible
     */
    public function is_convertible_mime($mime): bool {
        return in_array($mime, ['image/jpeg', 'image/png'], true);
    }

    /**
     * Obtiene path desde metadata
     */
    public function path_from_metadata_item(array $meta, string $size = null): ?string {
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

    /**
     * Calcula calidad según MIME
     */
    public function quality_for_mime(string $mime): int {
        if ($mime === 'image/png')  return (int) get_option(SCP_WebP_Config::OPT_QUALITY_PNG,  SCP_WebP_Config::DEFAULT_Q_PNG);
        if ($mime === 'image/jpeg') return (int) get_option(SCP_WebP_Config::OPT_QUALITY_JPEG, SCP_WebP_Config::DEFAULT_Q_JPEG);
        return SCP_WebP_Config::DEFAULT_Q_JPEG;
    }

    /**
     * Genera la ruta del archivo WebP según la configuración
     */
    public function get_webp_path(string $source_path): array {
        $format = get_option(SCP_WebP_Config::OPT_WEBP_FORMAT, 'double_extension');
        
        if ($format === 'single_extension') {
            // Formato extensión única (imagen.webp)
            return [preg_replace('/\.(jpe?g|png)$/i', '.webp', $source_path)];
        } else {
            // Formato doble extensión (imagen.jpg.webp) - por defecto
            return [$source_path . '.webp'];
        }
    }

    /**
     * Crea .webp al lado del original según la configuración seleccionada
     */
    public function ensure_webp(string $source_path, int $quality): bool {
        if (!file_exists($source_path)) {
            SCP_WebP_Logger::log("Archivo fuente no existe: {$source_path}", 'warning');
            return false;
        }

        $ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            SCP_WebP_Logger::log("Extensión no soportada: {$ext} para {$source_path}", 'warning');
            return false;
        }

        // Obtener todas las rutas posibles según la configuración
        $target_paths = $this->get_webp_path($source_path);
        
        // Verificar si alguno de los formatos ya existe
        foreach ($target_paths as $target_path) {
            if (file_exists($target_path)) {
                return true; // Ya existe
            }
        }

        // Verificar capacidades antes de proceder
        $capabilities = get_option('scp_webp_capabilities', []);
        $can_convert = ($capabilities['gd']['webp_support'] ?? false) || 
                      ($capabilities['imagick']['webp_support'] ?? false) || 
                      ($capabilities['imagewebp_function'] ?? false);

        if (!$can_convert) {
            SCP_WebP_Logger::log('Sin capacidades WebP disponibles para conversión', 'error');
            return false;
        }

        $start_time = microtime(true);
        $success = false;
        
        // Generar archivos WebP según configuración
        foreach ($target_paths as $target_path) {
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
                        SCP_WebP_Logger::log("WebP creado exitosamente: {$target_path} ({$duration}ms)", 'info');
                        $success = true;
                        continue; // Continuar con el siguiente formato si hay múltiples
                    } else {
                        $error_msg = is_wp_error($saved) ? $saved->get_error_message() : 'Error desconocido';
                        SCP_WebP_Logger::log("Falló conversión con editor WP para {$target_path}: {$error_msg}", 'warning');
                    }
                }
            } else {
                SCP_WebP_Logger::log("No se pudo crear editor para: {$source_path} - " . $editor->get_error_message(), 'warning');
            }

            // Intento 2: Fallback con GD directo para este target_path
            if (!file_exists($target_path) && function_exists('imagewebp')) {
                $data = file_get_contents($source_path);
                if ($data !== false) {
                    $img = imagecreatefromstring($data);
                    if ($img) {
                        // Preservar alfa cuando sea posible (PNG)
                        if (in_array($ext, ['png'], true)) {
                            imagealphablending($img, false);
                            imagesavealpha($img, true);
                        }

                        $ok = imagewebp($img, $target_path, max(0, min(100, (int)$quality)));
                        imagedestroy($img);
                        
                        if ($ok) {
                            $duration = round((microtime(true) - $start_time) * 1000, 2);
                            SCP_WebP_Logger::log("WebP creado con GD fallback: {$target_path} ({$duration}ms)", 'info');
                            $success = true;
                        } else {
                            SCP_WebP_Logger::log("Falló creación WebP con GD: {$target_path}", 'error');
                        }
                    }
                }
            }
        }
        
        if (!$success) {
            SCP_WebP_Logger::log("Falló conversión WebP completa para: {$source_path}", 'error');
        }
        
        return $success;
    }

    /**
     * Hook subida: convertir a WEBP todas las medidas
     */
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

    /**
     * Reemplaza URL por su .webp si existe
     */
    public function to_webp_url_if_exists(string $url): string {
        // Si ya es WebP, devolver tal cual
        if (stripos($url, '.webp') !== false) return $url;

        // Solo procesar JPG y PNG
        if (!preg_match('/\.(jpe?g|png)(\?.*)?$/i', $url)) return $url;

        $uploads = wp_get_upload_dir();
        if (empty($uploads['baseurl']) || empty($uploads['basedir'])) return $url;
        if (strpos($url, $uploads['baseurl']) !== 0) return $url;

        // Remover query string para el path del archivo
        $clean_url = preg_replace('/\?.*$/', '', $url);
        $rel = ltrim(str_replace($uploads['baseurl'], '', $clean_url), '/');
        $path = trailingslashit($uploads['basedir']) . $rel;

        // Conservar query string si la había
        $query_string = '';
        if (strpos($url, '?') !== false) {
            $query_string = substr($url, strpos($url, '?'));
        }

        // Obtener configuración actual
        $format = get_option(SCP_WebP_Config::OPT_WEBP_FORMAT, 'double_extension');

        // Buscar según configuración activa primero
        if ($format === 'double_extension') {
            $scp_candidate = $path . '.webp';
            if (file_exists($scp_candidate) && filesize($scp_candidate) > 0) {
                return $clean_url . '.webp' . $query_string;
            }
        } else {
            $optimus_candidate = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if (file_exists($optimus_candidate) && filesize($optimus_candidate) > 0) {
                $optimus_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $clean_url);
                return $optimus_url . $query_string;
            }
        }

        // Fallback: buscar en el formato opuesto (para compatibilidad con archivos existentes)
        if ($format === 'double_extension') {
            $optimus_candidate = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if (file_exists($optimus_candidate) && filesize($optimus_candidate) > 0) {
                $optimus_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $clean_url);
                return $optimus_url . $query_string;
            }
        } else {
            $scp_candidate = $path . '.webp';
            if (file_exists($scp_candidate) && filesize($scp_candidate) > 0) {
                return $clean_url . '.webp' . $query_string;
            }
        }

        return $url;
    }

    /**
     * Hook: cambiar src si el navegador soporta WebP
     */
    public function maybe_swap_to_webp_src($image, $attachment_id, $size, $icon) {
        if (!$image || !is_array($image) || empty($image[0])) return $image;
        if (!$this->browser_supports_webp()) return $image;

        $mime = get_post_mime_type($attachment_id);
        if (!$this->is_convertible_mime($mime)) return $image;

        $image[0] = $this->to_webp_url_if_exists($image[0]);
        return $image;
    }

    /**
     * Hook: cambiar srcset si el navegador soporta WebP
     */
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

    /**
     * Verifica si existe WebP para un archivo según configuración actual
     */
    public function has_webp_file(string $path): bool {
        $webp_paths = $this->get_webp_path($path);
        
        foreach ($webp_paths as $webp_path) {
            if (file_exists($webp_path) && filesize($webp_path) > 0) {
                return true;
            }
        }
        
        // Fallback: buscar formatos existentes aunque no estén configurados (para compatibilidad)
        $format = get_option(SCP_WebP_Config::OPT_WEBP_FORMAT, 'double_extension');
        
        // Si la configuración es doble extensión, también buscar extensión única como fallback
        if ($format === 'double_extension') {
            $optimus_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if (file_exists($optimus_path) && filesize($optimus_path) > 0) {
                return true;
            }
        } else {
            // Si la configuración es extensión única, también buscar doble extensión como fallback
            if (file_exists($path . '.webp') && filesize($path . '.webp') > 0) {
                return true;
            }
        }
        
        return false;
    }
}