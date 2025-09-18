<?php
/**
 * Limpieza de archivos WebP huérfanos
 * Detecta y elimina archivos WebP que ya no tienen su imagen original
 *
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Orphan_Cleaner {

    private $converter;
    private $upload_dir;

    public function __construct() {
        $this->converter = new SCP_WebP_Core_Converter();
        $this->upload_dir = wp_get_upload_dir();
    }

    /**
     * Escanea directorios de uploads buscando archivos WebP huérfanos
     */
    public function find_orphaned_webp_files(): array {
        error_log('SCP WebP: Iniciando búsqueda de archivos huérfanos');

        $orphaned_files = [];
        $upload_path = $this->upload_dir['basedir'];

        error_log('SCP WebP: Directorio de uploads: ' . $upload_path);

        if (!is_dir($upload_path)) {
            error_log('SCP WebP: El directorio de uploads no existe');
            return $orphaned_files;
        }

        // Buscar todos los archivos .webp recursivamente
        $webp_files = $this->find_webp_files($upload_path);
        error_log('SCP WebP: Encontrados ' . count($webp_files) . ' archivos WebP totales');

        foreach ($webp_files as $webp_file) {
            $original_file = $this->get_original_file_path($webp_file);

            // Si no existe el archivo original, es huérfano
            if (!file_exists($original_file)) {
                $orphaned_files[] = [
                    'webp_path' => $webp_file,
                    'original_path' => $original_file,
                    'size' => filesize($webp_file),
                    'modified' => filemtime($webp_file)
                ];
            }
        }

        error_log('SCP WebP: Encontrados ' . count($orphaned_files) . ' archivos huérfanos');
        return $orphaned_files;
    }

    /**
     * Busca archivos .webp recursivamente en un directorio
     */
    private function find_webp_files(string $directory): array {
        $webp_files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                try {
                    if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                        $webp_files[] = $file->getPathname();
                    }
                } catch (Exception $e) {
                    error_log('SCP WebP: Error procesando archivo: ' . $e->getMessage());
                    continue;
                }
            }
        } catch (Exception $e) {
            error_log('SCP WebP: Error escaneando directorio ' . $directory . ': ' . $e->getMessage());
        }

        return $webp_files;
    }

    /**
     * Obtiene la ruta del archivo original a partir de un archivo WebP
     */
    private function get_original_file_path(string $webp_file): string {
        $webp_filename = basename($webp_file);
        $webp_dir = dirname($webp_file);

        // Formato doble extensión: imagen.jpg.webp → imagen.jpg
        if (preg_match('/^(.+\.(jpe?g|png))\.webp$/i', $webp_filename, $matches)) {
            return $webp_dir . '/' . $matches[1];
        }

        // Formato extensión única: imagen.webp → buscar imagen.jpg o imagen.png
        if (preg_match('/^(.+)\.webp$/i', $webp_filename, $matches)) {
            $base_name = $matches[1];

            // Probar con diferentes extensiones
            $possible_extensions = ['jpg', 'jpeg', 'png'];
            foreach ($possible_extensions as $ext) {
                $candidate = $webp_dir . '/' . $base_name . '.' . $ext;
                if (file_exists($candidate)) {
                    return $candidate;
                }
            }

            // Si no encuentra ninguno, devolver el más probable (.jpg)
            return $webp_dir . '/' . $base_name . '.jpg';
        }

        return '';
    }

    /**
     * Elimina archivos WebP huérfanos
     */
    public function clean_orphaned_files(array $orphaned_files = null): array {
        if ($orphaned_files === null) {
            $orphaned_files = $this->find_orphaned_webp_files();
        }

        $result = [
            'deleted' => 0,
            'errors' => 0,
            'total_size' => 0,
            'files' => []
        ];

        foreach ($orphaned_files as $file_data) {
            $webp_path = $file_data['webp_path'];

            if (file_exists($webp_path)) {
                $size = filesize($webp_path);

                if (unlink($webp_path)) {
                    $result['deleted']++;
                    $result['total_size'] += $size;
                    $result['files'][] = $webp_path;

                    SCP_WebP_Logger::info("Eliminado WebP huérfano: {$webp_path}");
                } else {
                    $result['errors']++;
                    SCP_WebP_Logger::warning("Error eliminando WebP huérfano: {$webp_path}");
                }
            }
        }

        return $result;
    }

    /**
     * Verifica si un archivo WebP específico es huérfano
     */
    public function is_webp_orphaned(string $webp_path): bool {
        if (!file_exists($webp_path)) {
            return false;
        }

        $original_path = $this->get_original_file_path($webp_path);
        return !file_exists($original_path);
    }

    /**
     * Obtiene estadísticas de archivos WebP
     */
    public function get_webp_statistics(): array {
        $upload_path = $this->upload_dir['basedir'];
        $webp_files = $this->find_webp_files($upload_path);
        $orphaned_files = $this->find_orphaned_webp_files();

        $total_webp_size = 0;
        $orphaned_size = 0;

        foreach ($webp_files as $file) {
            $total_webp_size += filesize($file);
        }

        foreach ($orphaned_files as $file_data) {
            $orphaned_size += $file_data['size'];
        }

        return [
            'total_webp_files' => count($webp_files),
            'orphaned_files' => count($orphaned_files),
            'total_webp_size' => $total_webp_size,
            'orphaned_size' => $orphaned_size,
            'orphaned_percentage' => count($webp_files) > 0 ? round((count($orphaned_files) / count($webp_files)) * 100, 2) : 0
        ];
    }

    /**
     * Formatea el tamaño en bytes a formato legible
     */
    public function format_size(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Verifica archivos WebP específicos de un attachment
     */
    public function check_attachment_webp_files(int $attachment_id): array {
        $meta = wp_get_attachment_metadata($attachment_id);
        if (empty($meta)) {
            return ['valid' => [], 'orphaned' => []];
        }

        $valid_files = [];
        $orphaned_files = [];

        // Verificar archivo original
        $original_path = $this->converter->path_from_metadata_item($meta, null);
        if ($original_path) {
            $webp_paths = $this->converter->get_webp_path($original_path);
            foreach ($webp_paths as $webp_path) {
                if (file_exists($webp_path)) {
                    if (file_exists($original_path)) {
                        $valid_files[] = $webp_path;
                    } else {
                        $orphaned_files[] = $webp_path;
                    }
                }
            }
        }

        // Verificar medidas
        if (!empty($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $size_data) {
                $size_path = $this->converter->path_from_metadata_item($meta, $size);
                if ($size_path) {
                    $webp_paths = $this->converter->get_webp_path($size_path);
                    foreach ($webp_paths as $webp_path) {
                        if (file_exists($webp_path)) {
                            if (file_exists($size_path)) {
                                $valid_files[] = $webp_path;
                            } else {
                                $orphaned_files[] = $webp_path;
                            }
                        }
                    }
                }
            }
        }

        return [
            'valid' => $valid_files,
            'orphaned' => $orphaned_files
        ];
    }
}