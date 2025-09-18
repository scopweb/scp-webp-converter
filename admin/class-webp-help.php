<?php
/**
 * Pestaña de ayuda y documentación del plugin
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Help {
    
    /**
     * Renderiza la pestaña de ayuda
     */
    public function render_help_tab() {
        ?>
        <div class="scp-webp-tab-content">
            <h2>❓ Ayuda y Documentación</h2>
            <p>Guía completa para usar SCP WebP Converter de forma efectiva.</p>

            <!-- Inicio Rápido -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">🚀 Inicio Rápido</h3>
                    
                    <div class="quick-start-steps" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="step-box" style="border: 2px solid #0073aa; border-radius: 8px; padding: 20px; text-align: center;">
                            <h4 style="color: #0073aa; margin-top: 0;">1️⃣ Verificar Compatibilidad</h4>
                            <p>Ve a la pestaña <strong>"📊 Estado"</strong> para verificar que tu servidor soporta WebP.</p>
                            <div style="background: #f0f6fc; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <strong>Requisitos:</strong><br>
                                • GD o Imagick con soporte WebP<br>
                                • Directorio uploads escribible
                            </div>
                        </div>
                        
                        <div class="step-box" style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                            <h4 style="color: #46b450; margin-top: 0;">2️⃣ Configurar Calidad</h4>
                            <p>En la pestaña <strong>"⚙️ Configuración"</strong> ajusta la calidad WebP.</p>
                            <div style="background: #f0f9f0; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <strong>Recomendado:</strong><br>
                                • JPEG: 80-85 (buena compresión)<br>
                                • PNG: 85-95 (calidad preservada)
                            </div>
                        </div>
                        
                        <div class="step-box" style="border: 2px solid #ffb900; border-radius: 8px; padding: 20px; text-align: center;">
                            <h4 style="color: #ffb900; margin-top: 0;">3️⃣ Convertir Existentes</h4>
                            <p>Usa la pestaña <strong>"🔄 Conversión Masiva"</strong> para convertir imágenes ya subidas.</p>
                            <div style="background: #fff8e1; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <strong>Tip:</strong><br>
                                Procesa en lotes pequeños<br>
                                para evitar timeouts del servidor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preguntas Frecuentes -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">🤔 Preguntas Frecuentes</h3>
                    
                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            ❓ ¿Qué es WebP y por qué usarlo?
                        </summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <p><strong>WebP</strong> es un formato de imagen moderno desarrollado por Google que ofrece:</p>
                            <ul>
                                <li><strong>🗜️ Mejor compresión:</strong> Archivos 25-50% más pequeños que JPEG/PNG</li>
                                <li><strong>🎨 Calidad superior:</strong> Mantiene la calidad visual con menos espacio</li>
                                <li><strong>⚡ Carga más rápida:</strong> Mejora significativamente PageSpeed Insights</li>
                                <li><strong>📱 Optimización móvil:</strong> Especialmente efectivo en dispositivos móviles</li>
                                <li><strong>🌐 Soporte amplio:</strong> Compatible con todos los navegadores modernos</li>
                            </ul>
                        </div>
                    </details>

                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            ❓ ¿Cómo funciona la conversión automática?
                        </summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <p><strong>Proceso automático:</strong></p>
                            <ol>
                                <li>Subes una imagen JPEG/PNG a la Media Library</li>
                                <li>WordPress genera automáticamente todas las medidas (thumbnail, medium, large, etc.)</li>
                                <li>Este plugin convierte cada medida a WebP manteniendo los archivos originales</li>
                                <li>En el frontend, se sirve WebP a navegadores compatibles y el original a los demás</li>
                            </ol>
                            <p><strong>✅ Ventajas:</strong></p>
                            <ul>
                                <li>Totalmente transparente para el usuario final</li>
                                <li>Los archivos originales nunca se eliminan</li>
                                <li>Compatible con todos los temas y plugins existentes</li>
                                <li>Detección automática de soporte del navegador</li>
                            </ul>
                        </div>
                    </details>

                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            ❓ ¿Qué formato de archivo WebP debo elegir?
                        </summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 15px;">
                                    <h4 style="color: #46b450; margin-top: 0;">📁 Doble Extensión</h4>
                                    <p><code>imagen.jpg.webp</code></p>
                                    <p><strong>Recomendado para:</strong></p>
                                    <ul>
                                        <li>✅ Instalaciones nuevas</li>
                                        <li>✅ Mejor compatibilidad</li>
                                        <li>✅ Depuración más fácil</li>
                                        <li>✅ Conserva info original</li>
                                    </ul>
                                </div>
                                
                                <div style="border: 2px solid #ffb900; border-radius: 8px; padding: 15px;">
                                    <h4 style="color: #ffb900; margin-top: 0;">📄 Extensión Única</h4>
                                    <p><code>imagen.webp</code></p>
                                    <p><strong>Recomendado para:</strong></p>
                                    <ul>
                                        <li>🔧 Migración desde Optimus</li>
                                        <li>💾 Nombres más cortos</li>
                                        <li>🗂️ Organización simplificada</li>
                                        <li>⚡ Ligeramente más eficiente</li>
                                    </ul>
                                </div>
                            </div>
                            <p style="margin-top: 15px;"><strong>💡 Consejo:</strong> Puedes cambiar entre formatos en cualquier momento usando la pestaña <strong>"🏷️ Unificar Formato"</strong>.</p>
                        </div>
                    </details>

                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            ❓ ¿Qué pasa con la compatibilidad de navegadores?
                        </summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <p><strong>Detección automática:</strong> El plugin detecta automáticamente si el navegador del visitante soporta WebP.</p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                                <div style="background: #f0f9f0; padding: 15px; border-radius: 4px; border-left: 4px solid #46b450;">
                                    <h4 style="color: #46b450; margin-top: 0;">✅ Navegadores Compatibles</h4>
                                    <ul>
                                        <li>Chrome (todos)</li>
                                        <li>Firefox (desde v65)</li>
                                        <li>Safari (desde v14)</li>
                                        <li>Edge (todos)</li>
                                        <li>Opera (todos)</li>
                                        <li>Navegadores móviles modernos</li>
                                    </ul>
                                </div>
                                
                                <div style="background: #fff8e1; padding: 15px; border-radius: 4px; border-left: 4px solid #ffb900;">
                                    <h4 style="color: #ffb900; margin-top: 0;">⚠️ Navegadores Antiguos</h4>
                                    <ul>
                                        <li>Internet Explorer (todos)</li>
                                        <li>Safari (antes de v14)</li>
                                        <li>Firefox (antes de v65)</li>
                                        <li>Navegadores muy antiguos</li>
                                    </ul>
                                    <p><small>Automáticamente reciben el formato original (JPEG/PNG)</small></p>
                                </div>
                            </div>
                            
                            <p><strong>🔧 Funcionamiento técnico:</strong></p>
                            <ul>
                                <li>Se revisa el header <code>Accept: image/webp</code> de la petición HTTP</li>
                                <li>Si es compatible → se sirve la versión WebP</li>
                                <li>Si no es compatible → se sirve el archivo original</li>
                                <li>Todo es transparente para el usuario final</li>
                            </ul>
                        </div>
                    </details>

                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            ❓ ¿Cómo optimizo las configuraciones de calidad?
                        </summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <h4 style="color: #0073aa;">🖼️ Para JPEG</h4>
                                    <ul>
                                        <li><strong>60-70:</strong> Máxima compresión (pérdida visible)</li>
                                        <li><strong>75-80:</strong> Buena compresión (recomendado general)</li>
                                        <li><strong>85-90:</strong> Alta calidad (fotografías importantes)</li>
                                        <li><strong>90-95:</strong> Calidad premium (portfolios)</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 style="color: #46b450;">🎨 Para PNG</h4>
                                    <ul>
                                        <li><strong>70-80:</strong> Compresión agresiva (gráficos simples)</li>
                                        <li><strong>85-90:</strong> Buena calidad (recomendado general)</li>
                                        <li><strong>90-95:</strong> Alta calidad (imágenes con transparencia)</li>
                                        <li><strong>95-100:</strong> Sin pérdidas (logos, iconos)</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; margin-top: 15px; border-left: 4px solid #0073aa;">
                                <p><strong>💡 Consejo práctico:</strong></p>
                                <p>Empieza con <strong>JPEG: 80</strong> y <strong>PNG: 85</strong>. Después haz pruebas visuales subiendo diferentes tipos de imágenes y ajusta según necesites más compresión o mejor calidad.</p>
                            </div>
                        </div>
                    </details>

                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            ❓ ¿Qué pasa si desactivo el plugin?
                        </summary>
                        <div style="padding: 15px; background: #f9f9f9; margin-top: 5px; border-radius: 0 0 4px 4px;">
                            <p><strong>✅ Tranquilidad total:</strong></p>
                            <ul>
                                <li><strong>Archivos originales intactos:</strong> Nunca se eliminan los JPEG/PNG originales</li>
                                <li><strong>Sitio sigue funcionando:</strong> WordPress vuelve a servir los archivos originales</li>
                                <li><strong>Sin pérdida de datos:</strong> Todas las imágenes siguen disponibles</li>
                                <li><strong>Archivos WebP permanecen:</strong> Se quedan en el servidor por si reactivas</li>
                            </ul>
                            
                            <div style="background: #fff8e1; padding: 15px; border-radius: 4px; margin-top: 15px; border-left: 4px solid #ffb900;">
                                <p><strong>🧹 Para limpiar completamente:</strong></p>
                                <p>Si quieres eliminar todos los archivos WebP del servidor, tendrás que hacerlo manualmente o usar herramientas como WP-CLI. Los archivos WebP no interfieren con el funcionamiento normal.</p>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Comandos WP-CLI -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">⌨️ Comandos WP-CLI</h3>
                    <p>Si tienes acceso SSH al servidor, puedes usar estos comandos para operaciones masivas:</p>
                    
                    <div class="cli-commands">
                        <div style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; margin: 15px 0; font-family: monospace;">
                            <h4 style="color: #3498db; margin-top: 0;">🔄 Convertir imágenes faltantes</h4>
                            <code style="color: #2ecc71; background: none; padding: 0;">wp scp-webp/convert-missing</code>
                            <p style="margin: 10px 0 0 0; font-size: 14px;">Busca todas las imágenes JPEG/PNG que no tienen versión WebP y las convierte.</p>
                        </div>
                        
                        <div style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; margin: 15px 0; font-family: monospace;">
                            <h4 style="color: #3498db; margin-top: 0;">📦 Conversión en lotes</h4>
                            <code style="color: #2ecc71; background: none; padding: 0;">wp scp-webp/convert-missing --batch=100</code>
                            <p style="margin: 10px 0 0 0; font-size: 14px;">Procesa 100 imágenes por lote (útil para servidores con límites de tiempo).</p>
                        </div>
                    </div>
                    
                    <div style="background: #e8f4f8; padding: 15px; border-radius: 4px; margin-top: 15px; border-left: 4px solid #0073aa;">
                        <p><strong>💡 Ventajas del WP-CLI:</strong></p>
                        <ul>
                            <li>Procesa miles de imágenes sin timeouts del navegador</li>
                            <li>Progreso en tiempo real en la terminal</li>
                            <li>Más eficiente para sitios con muchas imágenes</li>
                            <li>Puede ejecutarse en segundo plano</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Resolución de Problemas -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">🛠️ Resolución de Problemas</h3>
                    
                    <div class="troubleshooting-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="problem-box" style="border: 2px solid #dc3232; border-radius: 8px; padding: 20px;">
                            <h4 style="color: #dc3232; margin-top: 0;">❌ Las imágenes no se convierten</h4>
                            <p><strong>Posibles causas:</strong></p>
                            <ul>
                                <li>GD/Imagick sin soporte WebP</li>
                                <li>Permisos de escritura insuficientes</li>
                                <li>Plugin de caché interferente</li>
                                <li>Límites de memoria del servidor</li>
                            </ul>
                            <p><strong>🔧 Solución:</strong> Revisa la pestaña "📊 Estado" y contacta a tu proveedor de hosting si es necesario.</p>
                        </div>
                        
                        <div class="problem-box" style="border: 2px solid #ffb900; border-radius: 8px; padding: 20px;">
                            <h4 style="color: #ffb900; margin-top: 0;">⚠️ Conversión lenta o timeouts</h4>
                            <p><strong>Síntomas:</strong></p>
                            <ul>
                                <li>La conversión masiva se detiene</li>
                                <li>Páginas web que no cargan</li>
                                <li>Errores 500 durante el proceso</li>
                            </ul>
                            <p><strong>🔧 Solución:</strong> Reduce el tamaño de lote a 10-20 imágenes y procesa en varias sesiones.</p>
                        </div>
                        
                        <div class="problem-box" style="border: 2px solid #0073aa; border-radius: 8px; padding: 20px;">
                            <h4 style="color: #0073aa; margin-top: 0;">🔍 WebP no se muestra en frontend</h4>
                            <p><strong>Posibles causas:</strong></p>
                            <ul>
                                <li>Plugin de caché sirviendo versiones antiguas</li>
                                <li>CDN que no soporta WebP</li>
                                <li>Tema con código personalizado de imágenes</li>
                            </ul>
                            <p><strong>🔧 Solución:</strong> Limpia cachés, verifica la configuración del CDN y activa "Procesar contenido hardcodeado".</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Soporte -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">📞 Soporte y Recursos</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">📚</div>
                            <h4>Documentación</h4>
                            <p>Esta pestaña de ayuda contiene toda la información necesaria para usar el plugin efectivamente.</p>
                        </div>
                        
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🐛</div>
                            <h4>Reportar Bugs</h4>
                            <p>Si encuentras un problema, puedes reportarlo a través del repositorio oficial del plugin.</p>
                        </div>
                        
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">💡</div>
                            <h4>Sugerencias</h4>
                            <p>¿Tienes ideas para mejorar el plugin? Las sugerencias son siempre bienvenidas.</p>
                        </div>
                        
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">⭐</div>
                            <h4>Valoraciones</h4>
                            <p>Si el plugin te resulta útil, considera dejarnos una valoración en WordPress.org.</p>
                        </div>
                    </div>
                    
                    <div style="background: #f0f6fc; padding: 20px; border-radius: 4px; margin-top: 20px; border-left: 4px solid #0073aa; text-align: center;">
                        <h4 style="color: #0073aa; margin-top: 0;">🚀 SCP WebP Converter v<?php echo SCP_WEBP_VERSION; ?></h4>
                        <p>Plugin desarrollado siguiendo los estándares de WordPress para ofrecer conversión WebP automática, eficiente y confiable.</p>
                        <p><strong>Características principales:</strong> Conversión automática • Formato configurable • Conversión masiva • Compatible WP-CLI • Detección de navegador • Calidad personalizable</p>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .scp-webp-tab-content details summary:hover {
            background: #e9ecef !important;
        }
        
        .scp-webp-tab-content details[open] summary {
            border-radius: 4px 4px 0 0;
        }
        
        .step-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        
        .problem-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        
        .cli-commands code {
            font-size: 16px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .quick-start-steps,
            .troubleshooting-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
}