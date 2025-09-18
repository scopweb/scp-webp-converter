(function($) {
    'use strict';

    let currentFiles = [];
    let isProcessing = false;

    /**
     * Logs a message to the rename log display
     */
    function logRename(msg, type = 'info') {
        var logBox = $('#rename-log');
        var timestamp = new Date().toLocaleTimeString();
        var cssClass = 'log-' + type;
        
        logBox.show();
        logBox.append(
            $('<div/>')
                .addClass(cssClass)
                .html('<span class="timestamp">[' + timestamp + ']</span> ' + msg)
        );
        logBox.scrollTop(logBox[0].scrollHeight);
    }

    /**
     * Updates the rename status display
     */
    function updateRenameStatus(status, type = 'processing') {
        var statusEl = $('#rename-status');
        statusEl
            .removeClass('status-processing status-completed status-error')
            .addClass('status-' + type)
            .text(status);
    }

    /**
     * Formats file size for display
     */
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let unit = 0;
        let size = bytes;
        
        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }
        
        return Math.round(size * 100) / 100 + ' ' + units[unit];
    }

    /**
     * Updates the statistics display
     */
    function updateStats(stats) {
        $('#stat-total').text(stats.total);
        $('#stat-double').text(stats.double_extension);
        $('#stat-single').text(stats.single_extension);
        $('#stat-size').text(formatFileSize(stats.total_size));
        $('#stat-duplicates').text(stats.duplicates_found || 0);
        $('#stat-recoverable').text(formatFileSize(stats.duplicates_size || 0));
        
        // Show recommendations based on current distribution
        var recommendationHtml = '';
        var doublePercent = stats.total > 0 ? Math.round((stats.double_extension / stats.total) * 100) : 0;
        var singlePercent = stats.total > 0 ? Math.round((stats.single_extension / stats.single_extension) * 100) : 0;
        
        if (stats.total === 0) {
            recommendationHtml = '<div class="notice notice-info inline"><p>📁 No se encontraron archivos WebP para procesar.</p></div>';
        } else if (stats.duplicates_found > 0) {
            recommendationHtml = '<div class="notice notice-warning inline"><p>⚠️ Se encontraron <strong>' + stats.duplicates_found + 
                               ' archivos duplicados</strong> que ocupan ' + formatFileSize(stats.duplicates_size) + 
                               '. La unificación eliminará automáticamente los redundantes y recuperará espacio en disco.</p></div>';
        } else if (stats.double_extension === stats.total) {
            recommendationHtml = '<div class="notice notice-success inline"><p>✅ Todos los archivos ya están en formato de doble extensión.</p></div>';
        } else if (stats.single_extension === stats.total) {
            recommendationHtml = '<div class="notice notice-success inline"><p>✅ Todos los archivos ya están en formato de extensión única.</p></div>';
        } else {
            recommendationHtml = '<div class="notice notice-warning inline"><p>⚠️ Archivos con formatos mixtos detectados. Considera unificar el formato para mejor consistencia.</p></div>';
        }
        
        $('#webp-recommendations').html(recommendationHtml);
        
        // Enable/disable buttons based on stats
        $('#convert-to-double').prop('disabled', stats.double_extension === stats.total || stats.total === 0);
        $('#convert-to-single').prop('disabled', stats.single_extension === stats.total || stats.total === 0);
    }

    /**
     * Scans for WebP files
     */
    function scanWebPFiles() {
        $('#webp-stats-loading').show();
        $('#webp-stats-content').hide();
        
        $.post(SCP_WEBP_RENAMER.ajax_url, {
            action: 'scp_webp_scan_files',
            nonce: SCP_WEBP_RENAMER.nonce
        })
        .done(function(response) {
            if (response.success) {
                currentFiles = response.data.files;
                updateStats(response.data.stats);
                
                $('#webp-stats-loading').hide();
                $('#webp-stats-content').show();
                
                console.log('Scan completed:', response.data);
            } else {
                alert('Error al escanear archivos: ' + (response.data ? response.data.message : 'Error desconocido'));
            }
        })
        .fail(function(xhr, textStatus, errorThrown) {
            alert('Error de conexión al escanear: ' + textStatus);
            $('#webp-stats-loading').hide();
        });
    }

    /**
     * Performs batch rename operation
     */
    function performRename(targetFormat, confirmMessage) {
        if (isProcessing) {
            return;
        }
        
        if (!currentFiles || currentFiles.length === 0) {
            alert(SCP_WEBP_RENAMER.strings.no_files);
            return;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        isProcessing = true;
        
        // Filter files that need renaming
        var filesToRename = currentFiles.filter(function(file) {
            return file.format !== targetFormat && file.can_rename;
        });
        
        if (filesToRename.length === 0) {
            alert('No hay archivos que necesiten renombrado para este formato.');
            isProcessing = false;
            return;
        }
        
        // Clear previous log
        $('#rename-log').empty().hide();
        
        // Disable buttons
        $('#convert-to-double, #convert-to-single').prop('disabled', true);
        
        updateRenameStatus('Iniciando renombrado de ' + filesToRename.length + ' archivos...', 'processing');
        logRename('Iniciando proceso de renombrado masivo...', 'info');
        logRename('Archivos a procesar: ' + filesToRename.length, 'info');
        logRename('Formato destino: ' + (targetFormat === 'double_extension' ? 'Doble extensión' : 'Extensión única'), 'info');
        
        // Process in batches to avoid timeouts
        processRenameBatch(filesToRename, targetFormat, 0);
    }

    /**
     * Processes a batch of files for renaming
     */
    function processRenameBatch(files, targetFormat, startIndex) {
        var batchSize = 20; // Process 20 files at a time
        var batch = files.slice(startIndex, startIndex + batchSize);
        
        if (batch.length === 0) {
            // All done
            finishRenameProcess();
            return;
        }
        
        var progress = Math.round(((startIndex + batch.length) / files.length) * 100);
        updateRenameStatus('Procesando lote ' + Math.floor(startIndex / batchSize + 1) + '... (' + progress + '%)', 'processing');
        
        $.post(SCP_WEBP_RENAMER.ajax_url, {
            action: 'scp_webp_rename_batch',
            nonce: SCP_WEBP_RENAMER.nonce,
            files: JSON.stringify(batch),
            target_format: targetFormat
        })
        .done(function(response) {
            if (response.success) {
                var result = response.data;
                
                // Log batch results
                logRename('Lote completado - Éxito: ' + result.success + ', Errores: ' + result.errors + ', Saltados: ' + result.skipped, 'info');
                
                // Log individual messages
                if (result.messages && result.messages.length > 0) {
                    result.messages.forEach(function(message) {
                        var logType = 'info';
                        if (message.indexOf('Error') !== -1) {
                            logType = 'error';
                        } else if (message.indexOf('Saltado') !== -1) {
                            logType = 'warning';
                        } else if (message.indexOf('Renombrado') !== -1) {
                            logType = 'success';
                        } else if (message.indexOf('Eliminado archivo redundante') !== -1) {
                            logType = 'success';
                        }
                        logRename(message, logType);
                    });
                }
                
                // Continue with next batch
                processRenameBatch(files, targetFormat, startIndex + batchSize);
            } else {
                logRename('Error en el lote: ' + (response.data ? response.data.message : 'Error desconocido'), 'error');
                finishRenameProcess(true);
            }
        })
        .fail(function(xhr, textStatus, errorThrown) {
            logRename('Error de conexión: ' + textStatus, 'error');
            finishRenameProcess(true);
        });
    }

    /**
     * Finishes the rename process
     */
    function finishRenameProcess(hasErrors = false) {
        isProcessing = false;
        
        if (hasErrors) {
            updateRenameStatus('Proceso completado con errores', 'error');
            logRename('Proceso finalizado con errores. Revisa el log anterior.', 'error');
        } else {
            updateRenameStatus('Renombrado completado exitosamente', 'completed');
            logRename('✅ Proceso de renombrado completado exitosamente.', 'success');
        }
        
        // Re-enable buttons
        $('#convert-to-double, #convert-to-single').prop('disabled', false);
        
        // Refresh stats
        setTimeout(function() {
            logRename('Actualizando estadísticas...', 'info');
            scanWebPFiles();
        }, 2000);
    }

    /**
     * Initialize the renamer interface
     */
    function initRenamer() {
        // Scan files on page load
        scanWebPFiles();
        
        // Bind click events
        $('#convert-to-double').on('click', function(e) {
            e.preventDefault();
            performRename('double_extension', SCP_WEBP_RENAMER.strings.confirm_double);
        });
        
        $('#convert-to-single').on('click', function(e) {
            e.preventDefault();
            performRename('single_extension', SCP_WEBP_RENAMER.strings.confirm_single);
        });
        
        // Add refresh button functionality if needed
        $(document).on('click', '.refresh-scan', function(e) {
            e.preventDefault();
            scanWebPFiles();
        });
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if we're on the renamer tab
        if ($('#webp-stats-loading').length > 0) {
            initRenamer();
        }
    });

})(jQuery);