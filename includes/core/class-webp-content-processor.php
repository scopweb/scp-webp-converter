<?php
/**
 * Procesador de contenido para reemplazar imágenes hardcodeadas por WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Content_Processor {
    
    private $converter;
    
    public function __construct($converter) {
        $this->converter = $converter;
    }

    /**
     * Hook: procesa contenido de posts/páginas para reemplazar URLs de imágenes hardcodeadas
     */
    public function process_content_images($content) {
        // Verificar si la funcionalidad está habilitada
        if (!SCP_WebP_Config::is_content_processing_enabled()) {
            return $content;
        }

        if (!$this->converter->browser_supports_webp()) {
            return $content;
        }

        // Solo procesar si hay imágenes en el contenido
        if (stripos($content, '<img') === false && stripos($content, 'src=') === false) {
            return $content;
        }

        // Buscar y reemplazar URLs en atributos src, srcset, y data-src
        $content = preg_replace_callback(
            '/(?:src|srcset|data-src)=(["\'])([^"\']*?(?:\.jpg|\.jpeg|\.png)(?:\?[^"\']*?)?)\1/i',
            [$this, 'replace_content_image_callback'],
            $content
        );

        // También procesar URLs en estilos CSS inline (background-image, etc.)
        $content = preg_replace_callback(
            '/background-image\s*:\s*url\s*\(\s*(["\']?)([^"\'()]*?(?:\.jpg|\.jpeg|\.png)(?:\?[^"\'()]*?)?)\1\s*\)/i',
            [$this, 'replace_css_background_callback'],
            $content
        );

        return $content;
    }

    /** Callback para reemplazar URLs de imágenes en atributos HTML */
    private function replace_content_image_callback($matches) {
        $quote = $matches[1]; // Comilla simple o doble
        $original_url = $matches[2];
        
        // Verificar si la URL pertenece a nuestro sitio
        $site_url = get_site_url();
        if (strpos($original_url, $site_url) !== 0 && strpos($original_url, '/') === 0) {
            // URL relativa, convertir a absoluta para verificación
            $original_url = rtrim($site_url, '/') . $original_url;
        } elseif (strpos($original_url, 'http') !== 0) {
            // No es una URL completa ni relativa válida
            return $matches[0];
        }

        // Verificar si pertenece a nuestro sitio
        if (strpos($original_url, $site_url) !== 0) {
            return $matches[0]; // URL externa, no procesar
        }

        $webp_url = $this->converter->to_webp_url_if_exists($original_url);
        
        if ($webp_url !== $original_url) {
            // Verificar doble que el archivo WebP realmente existe antes de reemplazar
            $uploads = wp_get_upload_dir();
            if (strpos($webp_url, $uploads['baseurl']) === 0) {
                $webp_path = str_replace($uploads['baseurl'], $uploads['basedir'], $webp_url);
                if (!file_exists($webp_path)) {
                    // El archivo WebP no existe realmente, no hacer el reemplazo
                    return $matches[0];
                }
            }
            
            // Convertir de vuelta a URL relativa si la original era relativa
            if (strpos($matches[2], '/') === 0) {
                $webp_url = str_replace($site_url, '', $webp_url);
            }
            return str_replace($matches[2], $webp_url, $matches[0]);
        }

        return $matches[0];
    }

    /** Callback para reemplazar URLs en CSS background-image */
    private function replace_css_background_callback($matches) {
        $quote = $matches[1]; // Comilla (puede estar vacía)
        $original_url = $matches[2];
        
        // Verificar si la URL pertenece a nuestro sitio
        $site_url = get_site_url();
        if (strpos($original_url, $site_url) !== 0 && strpos($original_url, '/') === 0) {
            $original_url = rtrim($site_url, '/') . $original_url;
        } elseif (strpos($original_url, 'http') !== 0) {
            return $matches[0];
        }

        if (strpos($original_url, $site_url) !== 0) {
            return $matches[0];
        }

        $webp_url = $this->converter->to_webp_url_if_exists($original_url);
        
        if ($webp_url !== $original_url) {
            // Verificar doble que el archivo WebP realmente existe antes de reemplazar
            $uploads = wp_get_upload_dir();
            if (strpos($webp_url, $uploads['baseurl']) === 0) {
                $webp_path = str_replace($uploads['baseurl'], $uploads['basedir'], $webp_url);
                if (!file_exists($webp_path)) {
                    // El archivo WebP no existe realmente, no hacer el reemplazo
                    return $matches[0];
                }
            }
            
            if (strpos($matches[2], '/') === 0) {
                $webp_url = str_replace($site_url, '', $webp_url);
            }
            return str_replace($matches[2], $webp_url, $matches[0]);
        }

        return $matches[0];
    }
}