<?php
/**
 * Utilidad para renombrar masivamente archivos WebP
 * Permite unificar el formato de nomenclatura en todo el sitio
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Renamer {
    
    const NONCE_ACTION = 'scp_webp_renamer_nonce';
    
    /**
     * Constructor - Inicializa hooks y acciones
     */
    public function __construct() {
        add_action('wp_ajax_scp_webp_scan_files', [$this, 'ajax_scan_files']);
        add_action('wp_ajax_scp_webp_rename_batch', [$this, 'ajax_rename_batch']);
    }
    
    /**
     * Escanea todos los archivos WebP en el directorio de uploads
     * 
     * @return array Lista de archivos WebP encontrados con información
     */
    public function scan_webp_files(): array {
        $upload_dir = wp_get_upload_dir();
        $base_dir = $upload_dir['basedir'];
        
        if (!is_dir($base_dir) || !is_readable($base_dir)) {
            return [];
        }
        
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'webp') {
                continue;
            }
            
            $filepath = $file->getPathname();
            $filename = $file->getFilename();
            $relative_path = str_replace($base_dir, '', $filepath);
            $relative_path = ltrim($relative_path, '\\/');
            
            // Determinar el formato actual del archivo
            $format_info = $this->analyze_webp_format($filename);
            
            $files[] = [
                'path' => $filepath,
                'relative_path' => $relative_path,
                'filename' => $filename,
                'format' => $format_info['format'],
                'original_name' => $format_info['original_name'],
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'can_rename' => $format_info['can_rename']
            ];
        }
        
        // Ordenar por path para consistencia
        usort($files, function($a, $b) {
            return strcmp($a['path'], $b['path']);
        });
        
        return $files;
    }
    
    /**
     * Analiza el formato de un archivo WebP
     * 
     * @param string $filename Nombre del archivo
     * @return array Información del formato
     */
    private function analyze_webp_format(string $filename): array {
        $info = [
            'format' => 'unknown',
            'original_name' => '',
            'can_rename' => false
        ];
        
        // Formato doble extensión: imagen.jpg.webp
        if (preg_match('/^(.+\.(jpe?g|png))\.webp$/i', $filename, $matches)) {
            $info['format'] = 'double_extension';
            $info['original_name'] = $matches[1];
            $info['can_rename'] = true;
            return $info;
        }
        
        // Formato extensión única: imagen.webp (que originalmente era imagen.jpg/png)
        if (preg_match('/^(.+)\.webp$/i', $filename, $matches)) {
            $info['format'] = 'single_extension';
            $info['original_name'] = $matches[1];
            $info['can_rename'] = true;
            return $info;
        }
        
        return $info;
    }
    
    /**
     * Renombra un lote de archivos WebP
     * 
     * @param array $files Lista de archivos a renombrar
     * @param string $target_format Formato destino ('double_extension' o 'single_extension')
     * @return array Resultado del proceso
     */
    public function rename_batch(array $files, string $target_format): array {
        $results = [
            'success' => 0,
            'errors' => 0,
            'skipped' => 0,
            'messages' => []
        ];
        
        if (!in_array($target_format, ['double_extension', 'single_extension'])) {
            $results['messages'][] = 'Formato destino no válido: ' . $target_format;
            return $results;
        }
        
        foreach ($files as $file_info) {
            $result = $this->rename_single_file($file_info, $target_format);
            
            if ($result['success']) {
                $results['success']++;
            } elseif ($result['skipped']) {
                $results['skipped']++;
            } else {
                $results['errors']++;
            }
            
            if (!empty($result['message'])) {
                $results['messages'][] = $result['message'];
            }
        }
        
        return $results;
    }
    
    /**
     * Renombra un archivo individual
     * 
     * @param array $file_info Información del archivo
     * @param string $target_format Formato destino
     * @return array Resultado del renombrado
     */
    private function rename_single_file(array $file_info, string $target_format): array {
        $current_path = $file_info['path'];
        $current_format = $file_info['format'];
        $filename = $file_info['filename'];
        
        // Si ya está en el formato correcto, saltar
        if ($current_format === $target_format) {
            return [
                'success' => false,
                'skipped' => true,
                'message' => "Saltado (ya está en formato {$target_format}): {$filename}"
            ];
        }
        
        // Calcular nuevo nombre
        $new_name = $this->calculate_new_name($file_info, $target_format);
        if (empty($new_name)) {
            return [
                'success' => false,
                'skipped' => false,
                'message' => "No se pudo calcular nuevo nombre para: {$filename}"
            ];
        }
        
        $new_path = dirname($current_path) . DIRECTORY_SEPARATOR . $new_name;
        
        // Si el archivo destino ya existe, eliminamos el origen (más eficiente)
        if (file_exists($new_path)) {
            // Verificar que el archivo destino tiene contenido válido
            if (filesize($new_path) > 0) {
                // El destino existe y es válido, eliminamos el origen
                if (unlink($current_path)) {
                    return [
                        'success' => true,
                        'skipped' => false,
                        'message' => "Eliminado archivo redundante: {$filename} (destino {$new_name} ya existía)"
                    ];
                } else {
                    return [
                        'success' => false,
                        'skipped' => false,
                        'message' => "Error al eliminar archivo redundante: {$filename}"
                    ];
                }
            } else {
                // El archivo destino existe pero está corrupto/vacío, eliminarlo y proceder con renombrado
                unlink($new_path);
            }
        }
        
        // Realizar el renombrado
        if (rename($current_path, $new_path)) {
            return [
                'success' => true,
                'skipped' => false,
                'message' => "Renombrado: {$filename} → {$new_name}"
            ];
        } else {
            return [
                'success' => false,
                'skipped' => false,
                'message' => "Error al renombrar: {$filename}"
            ];
        }
    }
    
    /**
     * Calcula el nuevo nombre según el formato destino
     * 
     * @param array $file_info Información del archivo
     * @param string $target_format Formato destino
     * @return string Nuevo nombre o cadena vacía si hay error
     */
    private function calculate_new_name(array $file_info, string $target_format): string {
        $current_format = $file_info['format'];
        $filename = $file_info['filename'];
        
        if ($target_format === 'double_extension') {
            // Convertir a formato doble extensión
            if ($current_format === 'single_extension') {
                // imagen.webp → imagen.jpg.webp (necesitamos adivinar la extensión original)
                $base_name = $file_info['original_name'];
                
                // Buscar el archivo original para determinar la extensión correcta
                $possible_extensions = ['jpg', 'jpeg', 'png'];
                $dir = dirname($file_info['path']);
                
                foreach ($possible_extensions as $ext) {
                    $original_file = $dir . DIRECTORY_SEPARATOR . $base_name . '.' . $ext;
                    if (file_exists($original_file)) {
                        return $base_name . '.' . $ext . '.webp';
                    }
                }
                
                // Si no encontramos el original, asumir jpg por defecto
                return $base_name . '.jpg.webp';
            }
        } elseif ($target_format === 'single_extension') {
            // Convertir a formato extensión única
            if ($current_format === 'double_extension') {
                // imagen.jpg.webp → imagen.webp
                $original_name = $file_info['original_name'];
                // Remover la extensión original (jpg, png, etc.)
                $base_name = preg_replace('/\.(jpe?g|png)$/i', '', $original_name);
                return $base_name . '.webp';
            }
        }
        
        return '';
    }
    
    /**
     * Obtiene estadísticas de archivos WebP por formato
     * 
     * @param array $files Lista de archivos WebP
     * @return array Estadísticas por formato
     */
    public function get_format_statistics(array $files): array {
        $stats = [
            'double_extension' => 0,
            'single_extension' => 0,
            'unknown' => 0,
            'total' => count($files),
            'total_size' => 0,
            'duplicates_found' => 0,
            'duplicates_size' => 0
        ];
        
        $path_map = [];
        
        foreach ($files as $file) {
            $stats[$file['format']]++;
            $stats['total_size'] += $file['size'];
            
            // Detectar duplicados potenciales
            $base_path = dirname($file['path']);
            $original_name = $file['original_name'];
            
            if ($file['format'] === 'double_extension') {
                // Para imagen.jpg.webp, buscar imagen.webp
                $base_name = preg_replace('/\.(jpe?g|png)$/i', '', $original_name);
                $alternative_name = $base_name . '.webp';
            } else {
                // Para imagen.webp, buscar imagen.jpg.webp o imagen.png.webp
                $alternative_name = $original_name . '.jpg.webp'; // Simplificado
            }
            
            $alternative_path = $base_path . DIRECTORY_SEPARATOR . $alternative_name;
            
            // Verificar si existe el archivo alternativo en nuestra lista
            foreach ($files as $other_file) {
                if ($other_file['path'] === $alternative_path) {
                    $stats['duplicates_found']++;
                    $stats['duplicates_size'] += min($file['size'], $other_file['size']);
                    break;
                }
            }
        }
        
        // Dividir duplicados por 2 ya que cada par se cuenta dos veces
        $stats['duplicates_found'] = intval($stats['duplicates_found'] / 2);
        $stats['duplicates_size'] = intval($stats['duplicates_size'] / 2);
        
        return $stats;
    }
    
    /**
     * AJAX: Escanear archivos WebP
     */
    public function ajax_scan_files() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado'], 403);
        }
        
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        
        $files = $this->scan_webp_files();
        $stats = $this->get_format_statistics($files);
        
        wp_send_json_success([
            'files' => $files,
            'stats' => $stats,
            'message' => sprintf('Encontrados %d archivos WebP', count($files))
        ]);
    }
    
    /**
     * AJAX: Renombrar lote de archivos
     */
    public function ajax_rename_batch() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado'], 403);
        }
        
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        
        $files = isset($_POST['files']) ? json_decode(wp_unslash($_POST['files']), true) : [];
        $target_format = isset($_POST['target_format']) ? sanitize_text_field($_POST['target_format']) : '';
        
        if (empty($files) || empty($target_format)) {
            wp_send_json_error(['message' => 'Datos incompletos para el renombrado']);
        }
        
        $results = $this->rename_batch($files, $target_format);
        
        wp_send_json_success($results);
    }
    
    /**
     * Formatear tamaño de archivo legible
     * 
     * @param int $size Tamaño en bytes
     * @return string Tamaño formateado
     */
    public static function format_file_size(int $size): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
}