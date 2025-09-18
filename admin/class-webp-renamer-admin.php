<?php
/**
 * Interfaz administrativa para la utilidad de renombrado WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Renamer_Admin {
    
    private $renamer;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->renamer = new SCP_WebP_Renamer();
    }
    
    /**
     * Renderiza la pestaña de renombrado
     */
    public function render_renamer_tab() {
        ?>
        <div class="scp-webp-tab-content">
            <h2>🔄 Unificar Formato de Archivos WebP</h2>
            <p>Esta utilidad te permite renombrar masivamente todos los archivos WebP existentes para unificar el formato de nomenclatura en todo el sitio.</p>
            
            <!-- Panel de Estadísticas -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">📊 Estado Actual de Archivos WebP</h3>
                    
                    <div id="webp-stats-loading" style="text-align: center; padding: 20px;">
                        <p>🔍 Escaneando archivos WebP...</p>
                        <div class="spinner is-active" style="float: none; margin: 10px auto;"></div>
                    </div>
                    
                    <div id="webp-stats-content" style="display: none;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <div class="webp-stat-box" style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">
                                <h4 style="margin: 0 0 5px 0; color: #0073aa;">Total de Archivos</h4>
                                <span id="stat-total" class="webp-stat-number">0</span>
                            </div>
                            
                            <div class="webp-stat-box" style="background: #f0f9f0; padding: 15px; border-radius: 4px; border-left: 4px solid #46b450;">
                                <h4 style="margin: 0 0 5px 0; color: #46b450;">Doble Extensión</h4>
                                <span id="stat-double" class="webp-stat-number">0</span>
                                <small style="display: block; color: #666;">imagen.jpg.webp</small>
                            </div>
                            
                            <div class="webp-stat-box" style="background: #fff8e1; padding: 15px; border-radius: 4px; border-left: 4px solid #ffb900;">
                                <h4 style="margin: 0 0 5px 0; color: #ffb900;">Extensión Única</h4>
                                <span id="stat-single" class="webp-stat-number">0</span>
                                <small style="display: block; color: #666;">imagen.webp</small>
                            </div>
                            
                            <div class="webp-stat-box" style="background: #fdf2f2; padding: 15px; border-radius: 4px; border-left: 4px solid #dc3232;">
                                <h4 style="margin: 0 0 5px 0; color: #dc3232;">Tamaño Total</h4>
                                <span id="stat-size" class="webp-stat-number">0 B</span>
                            </div>
                            
                            <div class="webp-stat-box" style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffb900;">
                                <h4 style="margin: 0 0 5px 0; color: #ffb900;">Duplicados</h4>
                                <span id="stat-duplicates" class="webp-stat-number">0</span>
                                <small style="display: block; color: #666;">archivos redundantes</small>
                            </div>
                            
                            <div class="webp-stat-box" style="background: #e8f5e8; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745;">
                                <h4 style="margin: 0 0 5px 0; color: #28a745;">Espacio Recuperable</h4>
                                <span id="stat-recoverable" class="webp-stat-number">0 B</span>
                                <small style="display: block; color: #666;">eliminando duplicados</small>
                            </div>
                        </div>
                        
                        <div id="webp-recommendations" style="margin-top: 15px;"></div>
                    </div>
                </div>
            </div>

            <!-- Panel de Acciones -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">⚡ Acciones de Renombrado</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <!-- Convertir a Doble Extensión -->
                        <div class="rename-action-box" style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                            <h4 style="color: #46b450; margin-top: 0;">📁 Formato Doble Extensión</h4>
                            <p style="margin: 10px 0;">Convierte todos los archivos a:</p>
                            <code style="background: #f0f9f0; padding: 5px 10px; border-radius: 4px; font-weight: bold;">imagen.jpg.webp</code>
                            <p style="margin: 15px 0 20px 0; font-size: 13px; color: #666;">
                                ✅ Mantiene información de extensión original<br>
                                ✅ Compatible con el formato actual del plugin<br>
                                ✅ Mejor para depuración y análisis<br>
                                🗑️ Elimina automáticamente archivos .webp redundantes
                            </p>
                            <button id="convert-to-double" class="button button-primary button-large" disabled>
                                🔄 Convertir a Doble Extensión
                            </button>
                        </div>
                        
                        <!-- Convertir a Extensión Única -->
                        <div class="rename-action-box" style="border: 2px solid #ffb900; border-radius: 8px; padding: 20px; text-align: center;">
                            <h4 style="color: #ffb900; margin-top: 0;">📄 Formato Extensión Única</h4>
                            <p style="margin: 10px 0;">Convierte todos los archivos a:</p>
                            <code style="background: #fff8e1; padding: 5px 10px; border-radius: 4px; font-weight: bold;">imagen.webp</code>
                            <p style="margin: 15px 0 20px 0; font-size: 13px; color: #666;">
                                ⚡ Nombres de archivo más cortos<br>
                                🔧 Compatible con plugins como Optimus<br>
                                💾 Ligeramente menos espacio en nombres<br>
                                🗑️ Elimina automáticamente archivos .jpg.webp redundantes
                            </p>
                            <button id="convert-to-single" class="button button-primary button-large" disabled>
                                🔄 Convertir a Extensión Única
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estado del Proceso -->
                    <div id="rename-status" style="margin: 20px 0; font-weight: bold;"></div>
                    
                    <!-- Log de Progreso -->
                    <div id="rename-log" style="display: none; margin-top: 20px; max-height: 300px; overflow: auto; background: #fff; border: 1px solid #ccd0d4; padding: 15px; font-family: monospace; font-size: 12px; line-height: 1.4;"></div>
                </div>
            </div>

            <!-- Panel de Información -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">ℹ️ Información Importante</h3>
                    
                    <div class="notice notice-info inline" style="margin: 15px 0;">
                        <p><strong>🔄 Proceso Reversible:</strong> Puedes cambiar entre formatos las veces que necesites. La utilidad detecta automáticamente el formato actual de cada archivo.</p>
                    </div>
                    
                    <div class="notice notice-warning inline" style="margin: 15px 0;">
                        <p><strong>⚠️ Precauciones:</strong></p>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Se recomienda hacer una copia de seguridad antes del renombrado masivo</li>
                            <li>El proceso puede tardar varios minutos con muchos archivos</li>
                            <li>Los archivos en uso podrían no poder renombrarse</li>
                        </ul>
                    </div>
                    
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">🔧 Detalles Técnicos</summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <p><strong>Formatos soportados:</strong></p>
                            <ul>
                                <li><strong>Doble extensión:</strong> <code>imagen.jpg.webp</code>, <code>imagen.png.webp</code></li>
                                <li><strong>Extensión única:</strong> <code>imagen.webp</code></li>
                            </ul>
                            <p><strong>Detección inteligente:</strong> La utilidad analiza el nombre de cada archivo para determinar su formato actual y calcular el nombre destino apropiado.</p>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <style>
            .webp-stat-number {
                font-size: 24px;
                font-weight: bold;
                display: block;
            }
            
            .rename-action-box {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .rename-action-box:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            #rename-log .log-success { color: #46b450; }
            #rename-log .log-error { color: #dc3232; }
            #rename-log .log-warning { color: #ffb900; }
            #rename-log .log-info { color: #666; }
            #rename-log .timestamp { color: #999; font-size: 11px; }
            
            .rename-progress {
                background: #f1f1f1;
                border-radius: 10px;
                overflow: hidden;
                height: 20px;
                margin: 10px 0;
            }
            
            .rename-progress-bar {
                background: linear-gradient(90deg, #0073aa, #46b450);
                height: 100%;
                transition: width 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: bold;
            }
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts para la utilidad de renombrado
     */
    public function enqueue_renamer_assets() {
        wp_enqueue_script(
            'scp-webp-renamer',
            SCP_WEBP_PLUGIN_URL . 'assets/js/renamer.js',
            ['jquery'],
            SCP_WEBP_VERSION,
            true
        );
        
        wp_localize_script('scp-webp-renamer', 'SCP_WEBP_RENAMER', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(SCP_WebP_Renamer::NONCE_ACTION),
            'strings' => [
                'scanning' => 'Escaneando archivos...',
                'scan_complete' => 'Escaneo completado',
                'renaming' => 'Renombrando archivos...',
                'rename_complete' => 'Renombrado completado',
                'confirm_double' => '¿Estás seguro de convertir todos los archivos WebP al formato de doble extensión (imagen.jpg.webp)?',
                'confirm_single' => '¿Estás seguro de convertir todos los archivos WebP al formato de extensión única (imagen.webp)?',
                'no_files' => 'No se encontraron archivos para procesar',
                'error' => 'Error durante el proceso'
            ]
        ]);
    }
}