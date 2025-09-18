<?php
/**
 * Información sobre medidas activas de imágenes y estado WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Sizes_Info {
    
    /**
     * Renderiza la información de medidas activas
     */
    public function render_active_sizes_info() {
        $active_sizes = $this->get_active_image_sizes();
        $stats = $this->get_sizes_statistics();
        $theme_support = current_theme_supports('post-thumbnails');
        
        ?>
        <h2>📐 Medidas de Imagen Activas</h2>
        <p>Información sobre las medidas de imagen <strong>realmente utilizadas</strong> en el sistema y su estado de conversión WebP.</p>

        <!-- Panel de Información General -->
        <div class="card" style="max-width: none; margin-top: 20px;">
            <div style="padding: 20px;">
                <h3 style="margin-top: 0;">ℹ️ Información General</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div class="info-box" style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">
                        <h4 style="margin: 0 0 5px 0; color: #0073aa;">Medidas Activas</h4>
                        <span class="info-number" style="font-size: 24px; font-weight: bold; display: block;"><?php echo $stats['total_active']; ?></span>
                        <small style="display: block; color: #666; margin-top: 5px;">Realmente en uso</small>
                    </div>
                    
                    <div class="info-box" style="background: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #666;">
                        <h4 style="margin: 0 0 5px 0; color: #666;">Total Registradas</h4>
                        <span class="info-number" style="font-size: 24px; font-weight: bold; display: block;"><?php echo $stats['total_registered']; ?></span>
                        <small style="display: block; color: #666; margin-top: 5px;">Definidas en el sistema</small>
                    </div>
                    
                    <?php if ($stats['disabled_count'] > 0): ?>
                    <div class="info-box" style="background: #fff8e1; padding: 15px; border-radius: 4px; border-left: 4px solid #ffb900;">
                        <h4 style="margin: 0 0 5px 0; color: #ffb900;">Medidas Desactivadas</h4>
                        <span class="info-number" style="font-size: 24px; font-weight: bold; display: block;"><?php echo $stats['disabled_count']; ?></span>
                        <small style="display: block; color: #666; margin-top: 5px;">Optimizadas por plugins</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-box" style="background: <?php echo $theme_support ? '#f0f9f0' : '#fdf2f2'; ?>; padding: 15px; border-radius: 4px; border-left: 4px solid <?php echo $theme_support ? '#46b450' : '#dc3232'; ?>;">
                        <h4 style="margin: 0 0 5px 0; color: <?php echo $theme_support ? '#46b450' : '#dc3232'; ?>;">Soporte de Tema</h4>
                        <span class="info-number" style="font-size: 16px; font-weight: bold; display: block;">
                            <?php echo $theme_support ? '✅ Activado' : '❌ Desactivado'; ?>
                        </span>
                        <small style="display: block; color: #666; margin-top: 5px;">
                            <?php echo $theme_support ? 'El tema soporta miniaturas' : 'Tema sin soporte post-thumbnails'; ?>
                        </small>
                    </div>
                    
                    <div class="info-box" style="background: #fff8e1; padding: 15px; border-radius: 4px; border-left: 4px solid #ffb900;">
                        <h4 style="margin: 0 0 5px 0; color: #ffb900;">Formato WebP Actual</h4>
                        <span class="info-number" style="font-size: 16px; font-weight: bold; display: block;">
                            <?php 
                            $format = SCP_WebP_Config::get_format();
                            echo $format === 'double_extension' ? '📁 Doble Extensión' : '📄 Extensión Única';
                            ?>
                        </span>
                        <small style="display: block; color: #666; margin-top: 5px;">
                            <?php echo $format === 'double_extension' ? 'imagen.jpg.webp' : 'imagen.webp'; ?>
                        </small>
                    </div>
                </div>
                
                <?php if ($stats['disabled_count'] > 0): ?>
                <div class="notice notice-info inline" style="margin-top: 15px;">
                    <p><strong>ℹ️ Optimización detectada:</strong> Se han desactivado <strong><?php echo $stats['disabled_count']; ?> medidas</strong> 
                    que estaban registradas pero no se están utilizando. Esto es una buena práctica para:</p>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>🗜️ Reducir el espacio de almacenamiento</li>
                        <li>⚡ Acelerar la subida de imágenes</li>
                        <li>🔧 Optimizar el rendimiento del servidor</li>
                        <li>💾 Usar menos recursos de procesamiento</li>
                    </ul>
                    <p style="margin-top: 10px;"><small>Las medidas listadas abajo son <strong>solo las que realmente están en uso</strong> y serán convertidas a WebP.</small></p>
                </div>
                <?php endif; ?>
                
                <?php if (!$theme_support): ?>
                <div class="notice notice-warning inline">
                    <p><strong>⚠️ Advertencia:</strong> Tu tema actual no tiene habilitado el soporte para miniaturas de posts. 
                    Algunas medidas podrían no generarse automáticamente. 
                    Contacta con el desarrollador del tema o añade <code>add_theme_support('post-thumbnails');</code> 
                    al archivo <code>functions.php</code> del tema.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel de Medidas Activas -->
        <div class="card" style="max-width: none; margin-top: 20px;">
            <div style="padding: 20px;">
                <h3 style="margin-top: 0;">📏 Medidas Activas (En Uso)</h3>
                
                <div class="sizes-table-container" style="overflow-x: auto;">
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 200px;"><strong>Nombre de Medida</strong></th>
                                <th scope="col" style="width: 100px;"><strong>Ancho</strong></th>
                                <th scope="col" style="width: 100px;"><strong>Alto</strong></th>
                                <th scope="col" style="width: 80px;"><strong>Recorte</strong></th>
                                <th scope="col" style="width: 120px;"><strong>Estado WebP</strong></th>
                                <th scope="col"><strong>Descripción/Origen</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_sizes as $size_name => $size_data): ?>
                            <tr>
                                <td><strong><code><?php echo esc_html($size_name); ?></code></strong></td>
                                <td><?php echo $size_data['width'] === 0 ? '<em>Auto</em>' : esc_html($size_data['width']) . 'px'; ?></td>
                                <td><?php echo $size_data['height'] === 0 ? '<em>Auto</em>' : esc_html($size_data['height']) . 'px'; ?></td>
                                <td>
                                    <?php if ($size_data['crop']): ?>
                                        <span style="color: #46b450;">✂️ Sí</span>
                                    <?php else: ?>
                                        <span style="color: #666;">📐 No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: #46b450; font-weight: bold;">🔄 Auto</span>
                                    <br><small style="color: #666;">Al subir imagen</small>
                                </td>
                                <td>
                                    <div>
                                        <?php echo esc_html($size_data['description'] ?? $this->get_size_description($size_name)); ?>
                                    </div>
                                    <?php if (!empty($size_data['source'])): ?>
                                    <small style="color: #666; font-style: italic;">
                                        Definida por: <?php echo esc_html($size_data['source']); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Panel de Información Técnica -->
        <div class="card" style="max-width: none; margin-top: 20px;">
            <div style="padding: 20px;">
                <h3 style="margin-top: 0;">🔧 Información Técnica</h3>
                
                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        🔍 Cómo Funciona la Conversión por Medidas
                    </summary>
                    <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                        <p><strong>Proceso automático:</strong></p>
                        <ol>
                            <li>Cuando subes una imagen a la Media Library, WordPress genera automáticamente todas las medidas listadas arriba</li>
                            <li>Este plugin intercepta ese proceso y convierte cada medida generada a formato WebP</li>
                            <li>Se mantienen tanto los archivos originales como las versiones WebP</li>
                            <li>En el frontend, se sirve automáticamente la versión WebP si el navegador la soporta</li>
                        </ol>
                        
                        <p><strong>Medidas especiales:</strong></p>
                        <ul>
                            <li><strong>full/original:</strong> La imagen en su tamaño original siempre se convierte</li>
                            <li><strong>Medidas con ancho/alto 0:</strong> Se redimensionan manteniendo proporción</li>
                            <li><strong>Medidas con recorte:</strong> Se ajustan exactamente al tamaño especificado</li>
                        </ul>
                    </div>
                </details>

                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        🎯 Optimización de Medidas
                    </summary>
                    <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                        <p><strong>Recomendaciones:</strong></p>
                        <ul>
                            <li><strong>Eliminar medidas innecesarias:</strong> Si hay medidas que tu tema no usa, considera eliminarlas para ahorrar espacio</li>
                            <li><strong>Usar medidas apropiadas:</strong> Asegúrate de que las medidas coinciden con los espacios donde se mostrarán las imágenes</li>
                            <li><strong>Responsive design:</strong> Las medidas se usan para generar srcset automáticamente</li>
                        </ul>
                        
                        <p><strong>Control de medidas:</strong></p>
                        <p>Las medidas se pueden controlar a través de:</p>
                        <ul>
                            <li><code>add_image_size()</code> en functions.php del tema</li>
                            <li>Configuración de WordPress (Ajustes > Medios)</li>
                            <li>Plugins que añaden sus propias medidas</li>
                        </ul>
                    </div>
                </details>

                <details>
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        📊 Información de Almacenamiento
                    </summary>
                    <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                        <p><strong>Estructura de archivos:</strong></p>
                        <p>Para cada imagen subida se generan múltiples archivos:</p>
                        <ul>
                            <li><strong>Archivos originales:</strong> imagen.jpg, imagen-150x150.jpg, imagen-300x300.jpg, etc.</li>
                            <li><strong>Archivos WebP:</strong> 
                                <?php if (SCP_WebP_Config::get_format() === 'double_extension'): ?>
                                imagen.jpg.webp, imagen-150x150.jpg.webp, imagen-300x300.jpg.webp, etc.
                                <?php else: ?>
                                imagen.webp, imagen-150x150.webp, imagen-300x300.webp, etc.
                                <?php endif; ?>
                            </li>
                        </ul>
                        
                        <p><strong>Beneficios del WebP:</strong></p>
                        <ul>
                            <li>🗜️ Reduce el tamaño de archivo entre 25-50% comparado con JPEG</li>
                            <li>⚡ Mejora significativamente la velocidad de carga</li>
                            <li>🎨 Mantiene calidad visual superior</li>
                            <li>📱 Especialmente efectivo en dispositivos móviles</li>
                        </ul>
                    </div>
                </details>
            </div>
        </div>
        
        <style>
        .info-number {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, sans-serif;
        }
        
        .sizes-table-container {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .wp-list-table th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 1px solid #c3c4c7;
        }
        
        .wp-list-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        details summary:hover {
            background: #e9ecef !important;
        }
        
        details[open] summary {
            border-radius: 4px 4px 0 0;
        }
        </style>
        <?php
    }
    
    /**
     * Obtiene todas las medidas de imagen activas (realmente utilizadas)
     */
    private function get_active_image_sizes(): array {
        global $_wp_additional_image_sizes;
        
        $sizes = [];
        
        // Obtener medidas que realmente están activas usando el método del backup
        $active_sizes = $this->get_actually_active_sizes();
        $all_registered = get_intermediate_image_sizes();
        
        // Medida especial: full (imagen original) - siempre activa
        $sizes['full'] = [
            'width' => 0,
            'height' => 0,
            'crop' => false,
            'description' => 'Imagen original sin redimensionar',
            'source' => 'WordPress Core',
            'status' => 'always_active'
        ];
        
        // Medidas por defecto de WordPress - solo las realmente activas
        $default_sizes = [
            'thumbnail' => [
                'width' => get_option('thumbnail_size_w'),
                'height' => get_option('thumbnail_size_h'),
                'crop' => get_option('thumbnail_crop'),
                'description' => 'Miniatura por defecto de WordPress',
                'source' => 'WordPress Core'
            ],
            'medium' => [
                'width' => get_option('medium_size_w'),
                'height' => get_option('medium_size_h'),
                'crop' => false,
                'description' => 'Medida media por defecto de WordPress',
                'source' => 'WordPress Core'
            ],
            'medium_large' => [
                'width' => get_option('medium_large_size_w'),
                'height' => get_option('medium_large_size_h'),
                'crop' => false,
                'description' => 'Medida media-grande (añadida en WordPress 4.4)',
                'source' => 'WordPress Core'
            ],
            'large' => [
                'width' => get_option('large_size_w'),
                'height' => get_option('large_size_h'),
                'crop' => false,
                'description' => 'Medida grande por defecto de WordPress',
                'source' => 'WordPress Core'
            ]
        ];
        
        // Recorrer TODAS las medidas registradas, pero solo incluir las activas
        foreach ($all_registered as $size_name) {
            $is_active = in_array($size_name, $active_sizes);
            if (!$is_active) continue; // Solo mostrar las activas
            
            if (isset($default_sizes[$size_name])) {
                // Medidas por defecto de WordPress
                $data = $default_sizes[$size_name];
                if ($data['width'] > 0 || $data['height'] > 0) {
                    $data['status'] = 'active';
                    $sizes[$size_name] = $data;
                }
            } elseif (isset($_wp_additional_image_sizes[$size_name])) {
                // Medidas personalizadas
                $data = $_wp_additional_image_sizes[$size_name];
                $sizes[$size_name] = [
                    'width' => $data['width'],
                    'height' => $data['height'],
                    'crop' => $data['crop'],
                    'description' => $this->get_size_description($size_name),
                    'source' => 'Tema/Plugin',
                    'status' => 'active'
                ];
            }
        }
        
        return $sizes;
    }
    
    /**
     * Detecta las medidas que realmente están activas (método del backup)
     */
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
            if ($custom_sizes) {
                foreach ($custom_sizes as $size_name => $size_data) {
                    if (in_array($size_name, $all_sizes)) {
                        $active_sizes[] = $size_name;
                    }
                }
            }
        }
        
        return array_unique($active_sizes);
    }
    
    /**
     * Obtiene estadísticas sobre medidas registradas vs activas
     */
    private function get_sizes_statistics(): array {
        global $_wp_additional_image_sizes;
        
        $actually_active = $this->get_actually_active_sizes();
        $all_registered = get_intermediate_image_sizes();
        
        $total_active = count($actually_active) + 1; // +1 para 'full'
        $total_registered = 0;
        
        // Contar medidas por defecto registradas
        $default_sizes = ['thumbnail', 'medium', 'medium_large', 'large'];
        foreach ($default_sizes as $size) {
            $width = get_option($size . '_size_w');
            $height = get_option($size . '_size_h');
            if ($width > 0 || $height > 0) {
                $total_registered++;
            }
        }
        
        // Contar medidas adicionales registradas
        if (!empty($_wp_additional_image_sizes)) {
            $total_registered += count($_wp_additional_image_sizes);
        }
        
        $total_registered += 1; // +1 para 'full'
        
        return [
            'total_registered' => $total_registered,
            'total_active' => $total_active,
            'disabled_count' => max(0, $total_registered - $total_active),
            'active_sizes' => $actually_active,
            'all_registered' => $all_registered
        ];
    }
    
    /**
     * Obtiene descripción para una medida específica
     */
    private function get_size_description(string $size_name): string {
        $descriptions = [
            'full' => 'Imagen original sin redimensionar',
            'thumbnail' => 'Miniatura por defecto de WordPress',
            'medium' => 'Medida media por defecto de WordPress',
            'medium_large' => 'Medida media-grande (añadida en WordPress 4.4)',
            'large' => 'Medida grande por defecto de WordPress',
            'post-thumbnail' => 'Miniatura de entradas (definida por el tema)',
            'featured' => 'Imagen destacada (definida por el tema)',
            'hero' => 'Imagen héroe/banner (definida por el tema)',
            'gallery' => 'Imagen para galería (definida por el tema)',
            'slider' => 'Imagen para slider (definida por el tema)',
            'widget' => 'Imagen para widgets (definida por el tema)',
        ];
        
        return $descriptions[$size_name] ?? 'Medida personalizada definida por el tema o plugin';
    }
}