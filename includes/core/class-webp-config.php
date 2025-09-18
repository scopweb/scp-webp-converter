<?php
/**
 * Configuración y constantes del plugin WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Config {
    
    // Opciones de calidad
    const OPT_QUALITY_JPEG = 'scp_webp_quality_jpeg'; // 0-100
    const OPT_QUALITY_PNG  = 'scp_webp_quality_png';  // 0-100
    const OPT_PROCESS_CONTENT = 'scp_webp_process_content'; // Procesar contenido hardcodeado
    const OPT_ENABLE_AVIF  = 'scp_webp_enable_avif';  // Preparado para futuro
    
    // Opción de formato WebP
    const OPT_WEBP_FORMAT = 'scp_webp_format'; // 'double_extension' o 'single_extension'
    
    const DEFAULT_Q_JPEG   = 82;
    const DEFAULT_Q_PNG    = 82;

    /**
     * Inicializa las opciones por defecto
     */
    public static function init_default_options() {
        // Ajustes de calidad
        add_option(self::OPT_QUALITY_JPEG, self::DEFAULT_Q_JPEG);
        add_option(self::OPT_QUALITY_PNG,  self::DEFAULT_Q_PNG);
        add_option(self::OPT_PROCESS_CONTENT, 1); // Activado por defecto
        
        // Opción de formato WebP por defecto (radio button)
        add_option(self::OPT_WEBP_FORMAT, 'double_extension'); // Formato por defecto
    }

    /**
     * Registra los settings del plugin
     */
    public static function register_settings() {
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
        register_setting('scp_webp_options', self::OPT_PROCESS_CONTENT, [
            'type' => 'boolean',
            'sanitize_callback' => function($v){ return !empty($v) ? 1 : 0; },
            'default' => 1,
        ]);
        register_setting('scp_webp_options', self::OPT_WEBP_FORMAT, [
            'type' => 'string',
            'sanitize_callback' => function($v){ 
                return in_array($v, ['double_extension', 'single_extension']) ? $v : 'double_extension'; 
            },
            'default' => 'double_extension',
        ]);
    }

    /**
     * Obtiene la configuración de formato actual
     */
    public static function get_format(): string {
        return get_option(self::OPT_WEBP_FORMAT, 'double_extension');
    }

    /**
     * Obtiene la calidad configurada para un tipo MIME
     */
    public static function get_quality_for_mime(string $mime): int {
        if ($mime === 'image/png')  return (int) get_option(self::OPT_QUALITY_PNG,  self::DEFAULT_Q_PNG);
        if ($mime === 'image/jpeg') return (int) get_option(self::OPT_QUALITY_JPEG, self::DEFAULT_Q_JPEG);
        return self::DEFAULT_Q_JPEG;
    }

    /**
     * Verifica si el procesamiento de contenido está habilitado
     */
    public static function is_content_processing_enabled(): bool {
        return (bool) get_option(self::OPT_PROCESS_CONTENT, 1);
    }
}