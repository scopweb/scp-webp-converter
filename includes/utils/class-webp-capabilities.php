<?php
/**
 * Verificación de capacidades del servidor para WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Capabilities {

    /**
     * Verifica las capacidades del servidor para WebP
     */
    public static function check_server_capabilities(): array {
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
                SCP_WebP_Logger::error($error);
            }
        }

        // Verificar si al menos una extensión soporta WebP
        $webp_support = ($capabilities['gd']['webp_support'] ?? false) || 
                       ($capabilities['imagick']['webp_support'] ?? false);
        
        if (!$webp_support) {
            SCP_WebP_Logger::warning('Advertencia: Ni GD ni Imagick soportan WebP. La conversión podría fallar.');
        }

        return $capabilities;
    }

    /**
     * Verifica si hay capacidades WebP disponibles
     */
    public static function has_webp_support(): bool {
        $capabilities = get_option('scp_webp_capabilities', []);
        return ($capabilities['gd']['webp_support'] ?? false) || 
               ($capabilities['imagick']['webp_support'] ?? false) || 
               ($capabilities['imagewebp_function'] ?? false);
    }

    /**
     * Obtiene las capacidades guardadas
     */
    public static function get_capabilities(): array {
        return get_option('scp_webp_capabilities', []);
    }
}