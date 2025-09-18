(function($) {
    'use strict';

    /**
     * Logs a message to the conversion log display
     * @param {string} msg - Message to log
     * @param {string} type - Type of message (info, success, error, warning)
     */
    function log(msg, type = 'info') {
        var box = $('#scp-webp-log');
        var timestamp = new Date().toLocaleTimeString();
        var cssClass = 'log-' + type;
        
        box.show();
        box.append(
            $('<div/>')
                .addClass(cssClass)
                .html('<span class="timestamp">[' + timestamp + ']</span> ' + msg)
        );
        box.scrollTop(box[0].scrollHeight);
    }

    /**
     * Updates the status display
     * @param {string} status - Status message
     * @param {string} type - Type of status (processing, completed, error)
     */
    function updateStatus(status, type = 'processing') {
        var statusEl = $('#scp-webp-status');
        statusEl
            .removeClass('status-processing status-completed status-error')
            .addClass('status-' + type)
            .text(status);
    }

    /**
     * Runs a single batch of WebP conversion
     * @param {number} offset - Starting offset for this batch
     * @param {object} totals - Running totals object
     */
    function runBatch(offset, totals = {processed: 0, checked: 0, converted: 0}) {
        var limit = 50;
        updateStatus('Procesando batch ' + Math.floor(offset / limit + 1) + '...', 'processing');

        $.post(SCP_WEBP.ajax_url, {
            action: 'scp_webp_convert_missing',
            nonce: SCP_WEBP.nonce,
            offset: offset,
            limit: limit
        })
        .done(function(resp) {
            if (!resp || !resp.success) {
                var errorMsg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Respuesta inesperada del servidor';
                log('Error: ' + errorMsg, 'error');
                updateStatus('Error en la conversión', 'error');
                return;
            }

            var d = resp.data;
            
            // Update running totals
            totals.processed += d.processed;
            totals.checked += d.checked;
            totals.converted += d.converted;

            // Log batch results
            var progress = Math.min(d.offset, d.total) + '/' + d.total;
            var batchMsg = 'Batch completado: ' + d.processed + ' procesados | ' + 
                          d.checked + ' revisados | ' + d.converted + ' convertidos | ' + 
                          'Progreso: ' + progress;
            log(batchMsg, 'info');

            if (d.done) {
                var finalMsg = '<strong>Conversión completada</strong><br>' +
                              'Total procesado: ' + totals.processed + ' archivos<br>' +
                              'Total revisado: ' + totals.checked + ' imágenes<br>' +
                              'Total convertido: ' + totals.converted + ' archivos WebP';
                log(finalMsg, 'success');
                updateStatus('Completado - ' + totals.converted + ' archivos WebP creados', 'completed');
                
                // Enable the start button again
                $('#scp-webp-start').prop('disabled', false);
            } else {
                // Continue with next batch
                runBatch(d.offset, totals);
            }
        })
        .fail(function(xhr, textStatus, errorThrown) {
            var errorMsg = 'Error AJAX: ' + textStatus;
            
            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMsg = xhr.responseJSON.data.message;
            } else if (errorThrown) {
                errorMsg += ' - ' + errorThrown;
            }

            log('Error de conexión: ' + errorMsg, 'error');
            updateStatus('Error de conexión', 'error');
            
            // Enable the start button again
            $('#scp-webp-start').prop('disabled', false);
        });
    }

    /**
     * Handles the start conversion button click
     */
    function handleStartConversion(e) {
        e.preventDefault();
        
        // Clear previous logs and reset UI
        $('#scp-webp-log').empty().hide();
        updateStatus('Iniciando conversión...', 'processing');
        
        // Disable button during processing
        $(this).prop('disabled', true);
        
        log('Iniciando conversión en lote de imágenes faltantes...', 'info');
        
        // Start the batch conversion process
        runBatch(0);
    }

    /**
     * Initialize the admin interface
     */
    function initAdmin() {
        // Add CSS styles for better log display and tabbed interface
        $('<style>')
            .text(`
                /* Log styles */
                #scp-webp-log {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .log-info { color: #333; }
                .log-success { color: #46b450; font-weight: bold; }
                .log-error { color: #dc3232; font-weight: bold; }
                .log-warning { color: #ffb900; font-weight: bold; }
                .timestamp { color: #666; font-size: 11px; }
                .status-processing { color: #0073aa; }
                .status-completed { color: #46b450; font-weight: bold; }
                .status-error { color: #dc3232; font-weight: bold; }
                #scp-webp-start:disabled { opacity: 0.6; cursor: not-allowed; }
                
                /* Tab interface improvements */
                .scp-webp-tab-content {
                    margin-top: 20px;
                }
                
                .scp-webp-tab-content h2 {
                    margin-top: 0;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #ddd;
                }
                
                .scp-webp-tab-content .card {
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    border: 1px solid #e5e5e5;
                    border-radius: 4px;
                }
                
                .scp-webp-tab-content .card h3 {
                    color: #23282d;
                    font-size: 16px;
                }
                
                .scp-webp-tab-content .card h4 {
                    color: #0073aa;
                    margin-bottom: 8px;
                    font-size: 14px;
                }
                
                .scp-webp-tab-content pre {
                    font-size: 13px;
                    line-height: 1.5;
                }
                
                .scp-webp-tab-content kbd {
                    background: #f1f1f1;
                    border: 1px solid #ccc;
                    border-radius: 3px;
                    padding: 2px 6px;
                    font-family: monospace;
                    font-size: 12px;
                }
                
                /* Navigation tabs enhancement */
                .nav-tab-wrapper .nav-tab {
                    position: relative;
                }
                
                .nav-tab-wrapper .nav-tab-active::after {
                    content: '';
                    position: absolute;
                    bottom: -1px;
                    left: 0;
                    right: 0;
                    height: 2px;
                    background: #0073aa;
                }
                
                /* Responsive grid for help section */
                @media (max-width: 782px) {
                    .scp-webp-tab-content div[style*="grid-template-columns"] {
                        display: block !important;
                    }
                }
                
                /* Button improvements */
                .button-large {
                    padding: 8px 16px !important;
                    height: auto !important;
                    font-size: 14px !important;
                }
                
                /* Status indicators */
                .scp-webp-status-indicator {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    margin-right: 8px;
                }
                
                .status-success .scp-webp-status-indicator {
                    background-color: #46b450;
                }
                
                .status-warning .scp-webp-status-indicator {
                    background-color: #ffb900;
                }
                
                .status-error .scp-webp-status-indicator {
                    background-color: #dc3232;
                }
            `)
            .appendTo('head');

        // Bind click event to start button
        $(document).on('click', '#scp-webp-start', handleStartConversion);

        // Add keyboard shortcut (Ctrl+Shift+W) for advanced users
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.which === 87) { // Ctrl+Shift+W
                e.preventDefault();
                $('#scp-webp-start').trigger('click');
            }
        });

        // Validación para radio buttons de formato WebP
        function validateWebPFormatOptions() {
            var formatRadios = $('input[name="scp_webp_format"]');
            var warningDiv = $('#webp-format-warning');
            
            if (formatRadios.length > 0) {
                var hasSelection = formatRadios.is(':checked');
                
                if (!hasSelection) {
                    if (warningDiv.length === 0) {
                        formatRadios.closest('fieldset').after(
                            '<div id="webp-format-warning" class="notice notice-warning inline" style="margin: 10px 0;">' +
                            '<p><strong>⚠️ Advertencia:</strong> Debes seleccionar un formato de archivo WebP. ' +
                            'Se usará el formato de doble extensión por defecto.</p>' +
                            '</div>'
                        );
                    }
                } else {
                    warningDiv.remove();
                }
            }
        }
        
        // Bind validation to radio button changes
        $(document).on('change', 'input[name="scp_webp_format"]', validateWebPFormatOptions);
        
        // Initial validation
        validateWebPFormatOptions();

        log('Interfaz de administración lista. Presiona "Escanear y convertir faltantes" para comenzar.', 'info');
    }

    // Initialize when DOM is ready
    $(document).ready(initAdmin);

})(jQuery);