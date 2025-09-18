<?php
/**
 * Interfaz administrativa para la limpieza de archivos WebP huérfanos
 *
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Orphan_Cleaner_Admin {

    private $cleaner;

    public function __construct() {
        // Verificar que la clase cleaner exista
        if (!class_exists('SCP_WebP_Orphan_Cleaner')) {
            require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-orphan-cleaner.php';
        }

        $this->cleaner = new SCP_WebP_Orphan_Cleaner();

        // Registrar handlers AJAX
        add_action('wp_ajax_scp_webp_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_scp_webp_scan_orphans', [$this, 'ajax_scan_orphans']);
        add_action('wp_ajax_scp_webp_clean_orphans', [$this, 'ajax_clean_orphans']);

        // Debug: Confirmar registro
        error_log('SCP WebP Orphan Cleaner Admin: Constructor ejecutado, handlers AJAX registrados');
    }

    /**
     * Renderiza la página de limpieza de huérfanos
     */
    public function render_orphan_cleaner_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        $stats = $this->cleaner->get_webp_statistics();
        ?>
        <div class="wrap">
            <h1>🗑️ Limpieza de WebP Huérfanos</h1>

            <div class="notice notice-info">
                <p><strong>¿Qué son los archivos WebP huérfanos?</strong></p>
                <p>Son archivos .webp que ya no tienen su imagen original correspondiente. Esto puede ocurrir cuando eliminas imágenes desde WordPress pero los archivos WebP quedan en el servidor.</p>
            </div>

            <!-- Estadísticas -->
            <div class="webp-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="webp-stat-card">
                    <div class="webp-stat-number"><?php echo number_format($stats['total_webp_files']); ?></div>
                    <div class="webp-stat-label">Total archivos WebP</div>
                </div>
                <div class="webp-stat-card">
                    <div class="webp-stat-number" style="color: #dc3232;"><?php echo number_format($stats['orphaned_files']); ?></div>
                    <div class="webp-stat-label">Archivos huérfanos</div>
                </div>
                <div class="webp-stat-card">
                    <div class="webp-stat-number"><?php echo $this->cleaner->format_size($stats['total_webp_size']); ?></div>
                    <div class="webp-stat-label">Tamaño total WebP</div>
                </div>
                <div class="webp-stat-card">
                    <div class="webp-stat-number" style="color: #dc3232;"><?php echo $this->cleaner->format_size($stats['orphaned_size']); ?></div>
                    <div class="webp-stat-label">Espacio desperdiciado</div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="webp-actions" style="margin: 30px 0;">
                <button id="test-connection" class="button button-secondary" style="margin-right: 10px;">
                    🔧 Test Conexión AJAX
                </button>
                <button id="scan-orphans" class="button button-secondary">
                    🔍 Escanear archivos huérfanos
                </button>
                <button id="clean-orphans" class="button button-primary" style="display: none;">
                    🗑️ Limpiar archivos huérfanos
                </button>
            </div>

            <!-- Área de resultados -->
            <div id="scan-results" style="margin: 20px 0;"></div>

            <!-- Lista de archivos huérfanos -->
            <div id="orphaned-files-list" style="display: none;">
                <h3>Archivos WebP huérfanos encontrados:</h3>
                <div id="orphaned-files-table"></div>
            </div>

            <!-- Progreso de limpieza -->
            <div id="cleaning-progress" style="display: none; margin: 20px 0;">
                <h3>Progreso de limpieza:</h3>
                <div style="background: #f1f1f1; border-radius: 4px; padding: 10px; margin: 10px 0;">
                    <div id="progress-bar" style="background: #0073aa; height: 20px; border-radius: 2px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <div id="progress-text">Preparando...</div>
            </div>
        </div>

        <style>
            .webp-stat-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }

            .webp-stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 5px;
            }

            .webp-stat-label {
                font-size: 14px;
                color: #646970;
            }

            .orphaned-file {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                border-bottom: 1px solid #f0f0f1;
                background: #fff;
            }

            .orphaned-file:hover {
                background: #f6f7f7;
            }

            .file-info {
                flex: 1;
            }

            .file-path {
                font-family: monospace;
                font-size: 13px;
                color: #0073aa;
            }

            .file-details {
                font-size: 12px;
                color: #646970;
                margin-top: 5px;
            }

            .file-size {
                color: #dc3232;
                font-weight: bold;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let orphanedFiles = [];

            // Configuración AJAX
            const scpWebpAjax = {
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('scp_webp_orphan_nonce'); ?>'
            };

            // Verificar que ajaxurl esté definido (fallback)
            if (typeof ajaxurl === 'undefined') {
                ajaxurl = scpWebpAjax.url;
            }

            console.log('SCP WebP: Configuración AJAX:', scpWebpAjax);

            // Test de conexión AJAX
            $('#test-connection').on('click', function() {
                const button = $(this);
                const originalText = button.text();

                button.prop('disabled', true).text('🔧 Probando...');

                $.ajax({
                    url: scpWebpAjax.url,
                    type: 'POST',
                    data: {
                        action: 'scp_webp_test_connection',
                        nonce: scpWebpAjax.nonce
                    },
                    success: function(response) {
                        console.log('Test response:', response);
                        if (response.success) {
                            $('#scan-results').html('<div class="notice notice-success"><p>✅ Conexión AJAX funcionando correctamente</p></div>');
                        } else {
                            $('#scan-results').html('<div class="notice notice-error"><p>❌ Error en la conexión: ' + response.data + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Test error:', xhr, status, error);
                        $('#scan-results').html('<div class="notice notice-error"><p>❌ Error de conexión AJAX: ' + error + '</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Escanear archivos huérfanos
            $('#scan-orphans').on('click', function() {
                const button = $(this);
                const originalText = button.text();

                button.prop('disabled', true).text('🔍 Escaneando...');
                $('#scan-results').html('<div class="notice notice-info"><p>Escaneando archivos WebP huérfanos...</p></div>');

                console.log('Iniciando escaneo AJAX...');

                $.ajax({
                    url: scpWebpAjax.url,
                    type: 'POST',
                    data: {
                        action: 'scp_webp_scan_orphans',
                        nonce: scpWebpAjax.nonce
                    },
                    success: function(response) {
                        console.log('Respuesta AJAX recibida:', response);
                        if (response.success) {
                            orphanedFiles = response.data.files;
                            displayScanResults(response.data);
                        } else {
                            $('#scan-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', xhr, status, error);
                        $('#scan-results').html('<div class="notice notice-error"><p>Error de conexión durante el escaneo: ' + error + '</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Limpiar archivos huérfanos
            $('#clean-orphans').on('click', function() {
                if (!confirm('¿Estás seguro de que quieres eliminar todos los archivos WebP huérfanos? Esta acción no se puede deshacer.')) {
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).text('🗑️ Limpiando...');
                $('#cleaning-progress').show();

                $.ajax({
                    url: scpWebpAjax.url,
                    type: 'POST',
                    data: {
                        action: 'scp_webp_clean_orphans',
                        nonce: scpWebpAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            displayCleaningResults(response.data);
                            $('#clean-orphans').hide();
                            $('#orphaned-files-list').hide();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error de conexión durante la limpieza.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('🗑️ Limpiar archivos huérfanos');
                        $('#cleaning-progress').hide();
                    }
                });
            });

            function displayScanResults(data) {
                const { files, total_size, count } = data;

                if (count === 0) {
                    $('#scan-results').html('<div class="notice notice-success"><p>🎉 ¡Perfecto! No se encontraron archivos WebP huérfanos.</p></div>');
                    $('#clean-orphans').hide();
                    return;
                }

                $('#scan-results').html(
                    '<div class="notice notice-warning">' +
                    '<p><strong>Se encontraron ' + count + ' archivos WebP huérfanos</strong> (' + data.total_size_formatted + ')</p>' +
                    '</div>'
                );

                // Mostrar lista de archivos
                let filesHtml = '<div style="border: 1px solid #c3c4c7; border-radius: 4px; background: #fff; max-height: 400px; overflow-y: auto;">';
                files.forEach(function(file) {
                    const fileName = file.webp_path.split('/').pop();
                    const pathParts = file.webp_path.split('/');
                    const relativeDir = '/' + pathParts.slice(-4, -1).join('/'); // Mostrar últimas carpetas

                    filesHtml += '<div class="orphaned-file">';
                    filesHtml += '<div class="file-info">';
                    filesHtml += '<div class="file-path">' + fileName + '</div>';
                    filesHtml += '<div class="file-details">';
                    filesHtml += '<span>📁 ' + relativeDir + '</span> • ';
                    filesHtml += '<span class="file-size">' + file.size_formatted + '</span> • ';
                    filesHtml += '<span>📅 ' + file.date_formatted + '</span>';
                    filesHtml += '</div>';
                    filesHtml += '</div>';
                    filesHtml += '</div>';
                });
                filesHtml += '</div>';

                $('#orphaned-files-table').html(filesHtml);
                $('#orphaned-files-list').show();
                $('#clean-orphans').show();
            }

            function displayCleaningResults(data) {
                const { deleted, errors, total_size_formatted } = data;

                let message = '🎉 Limpieza completada:\n\n';
                message += '✅ Archivos eliminados: ' + deleted + '\n';
                message += '💾 Espacio liberado: ' + total_size_formatted + '\n';

                if (errors > 0) {
                    message += '⚠️ Errores: ' + errors + '\n';
                }

                $('#scan-results').html('<div class="notice notice-success"><p><strong>Limpieza completada</strong><br>' +
                    'Archivos eliminados: ' + deleted + '<br>' +
                    'Espacio liberado: ' + total_size_formatted +
                    (errors > 0 ? '<br>⚠️ Errores: ' + errors : '') +
                    '</p></div>');

                // Actualizar estadísticas
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX: Test de conexión
     */
    public function ajax_test_connection() {
        check_ajax_referer('scp_webp_orphan_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $upload_dir = wp_get_upload_dir();
        $test_info = [
            'upload_dir_exists' => is_dir($upload_dir['basedir']),
            'upload_dir_path' => $upload_dir['basedir'],
            'upload_dir_writable' => is_writable($upload_dir['basedir']),
            'cleaner_class_exists' => class_exists('SCP_WebP_Orphan_Cleaner'),
            'converter_class_exists' => class_exists('SCP_WebP_Core_Converter'),
            'timestamp' => current_time('Y-m-d H:i:s')
        ];

        wp_send_json_success($test_info);
    }

    /**
     * AJAX: Escanear archivos huérfanos
     */
    public function ajax_scan_orphans() {
        // Debug
        error_log('SCP WebP: ajax_scan_orphans llamado');

        check_ajax_referer('scp_webp_orphan_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        try {
            $orphaned_files = $this->cleaner->find_orphaned_webp_files();
            error_log('SCP WebP: Encontrados ' . count($orphaned_files) . ' archivos huérfanos');
            $total_size = 0;

            foreach ($orphaned_files as &$file) {
                $total_size += $file['size'];
                $file['size_formatted'] = $this->cleaner->format_size($file['size']);
                $file['date_formatted'] = date('Y-m-d H:i', $file['modified']);
            }

            wp_send_json_success([
                'files' => $orphaned_files,
                'count' => count($orphaned_files),
                'total_size' => $total_size,
                'total_size_formatted' => $this->cleaner->format_size($total_size)
            ]);

        } catch (Exception $e) {
            wp_send_json_error('Error durante el escaneo: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Limpiar archivos huérfanos
     */
    public function ajax_clean_orphans() {
        check_ajax_referer('scp_webp_orphan_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        try {
            $result = $this->cleaner->clean_orphaned_files();
            $result['total_size_formatted'] = $this->cleaner->format_size($result['total_size']);

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error('Error durante la limpieza: ' . $e->getMessage());
        }
    }
}