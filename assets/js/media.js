(function($) {
    'use strict';

    /**
     * Muestra el resultado de la conversión
     * @param {jQuery} container - Contenedor donde mostrar el resultado
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de mensaje (success, error, info)
     */
    function showResult(container, message, type = 'info') {
        var cssClass = 'notice notice-' + type;
        if (type === 'success') cssClass = 'notice notice-success';
        if (type === 'error') cssClass = 'notice notice-error';

        container.html(
            '<div class="' + cssClass + ' is-dismissible" style="padding: 8px 12px; margin: 5px 0;">' +
            '<p><strong>' + message + '</strong></p>' +
            '</div>'
        );

        // Auto-ocultar después de 5 segundos para mensajes de éxito
        if (type === 'success') {
            setTimeout(function() {
                container.fadeOut();
            }, 5000);
        }
    }

    /**
     * Actualiza el botón después de la conversión
     * @param {jQuery} button - Botón a actualizar
     * @param {Object} webp_status - Estado WebP actualizado
     */
    function updateButton(button, webp_status) {
        if (webp_status.has_webp) {
            button
                .removeClass('scp-webp-convert button-primary')
                .addClass('scp-webp-reconvert')
                .attr('data-force', '1')
                .html('🔄 Reconvertir WebP');
        } else {
            button
                .removeClass('scp-webp-reconvert')
                .addClass('scp-webp-convert button-primary')
                .removeAttr('data-force')
                .html('🖼️ Convertir a WebP');
        }
    }

    /**
     * Realiza la conversión de una imagen
     * @param {number} attachmentId - ID del adjunto
     * @param {boolean} force - Forzar reconversión
     * @param {jQuery} button - Botón que inició la acción
     * @param {jQuery} resultContainer - Contenedor para mostrar resultados
     */
    function convertImage(attachmentId, force, button, resultContainer) {
        // Deshabilitar botón y mostrar estado de carga
        button.prop('disabled', true);
        var originalText = button.html();
        button.html('⏳ Procesando...');

        // Limpiar resultado anterior
        if (resultContainer.length) {
            resultContainer.empty();
        }

        // Log de depuración
        console.log('SCP WebP: Iniciando conversión:', {
            attachmentId: attachmentId,
            force: force,
            ajaxUrl: SCP_WEBP_MEDIA.ajax_url,
            nonce: SCP_WEBP_MEDIA.nonce ? 'present' : 'missing'
        });

        $.post(SCP_WEBP_MEDIA.ajax_url, {
            action: 'scp_webp_convert_single',
            nonce: SCP_WEBP_MEDIA.nonce,
            attachment_id: attachmentId,
            force: force ? '1' : '0'
        })
        .done(function(response) {
            if (response.success && response.data) {
                var data = response.data;
                showResult(resultContainer, data.message, 'success');
                
                // Actualizar botón según el nuevo estado
                updateButton(button, data.webp_status);
                
                // En vista de lista, actualizar también el enlace de acción
                var $row = button.closest('tr');
                if ($row.length) {
                    var $actionLink = $row.find('.scp-webp-convert, .scp-webp-reconvert');
                    if ($actionLink.length && !$actionLink.is(button)) {
                        updateButton($actionLink, data.webp_status);
                    }
                }
            } else {
                var errorMsg = response.data && response.data.message ? 
                              response.data.message : 
                              'Error desconocido durante la conversión';
                showResult(resultContainer, errorMsg, 'error');
                button.html(originalText);
            }
        })
        .fail(function(xhr, textStatus, errorThrown) {
            var errorMsg = 'Error de conexión: ' + textStatus;
            
            // Mejor manejo de errores para depuración
            console.error('SCP WebP Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                textStatus: textStatus,
                errorThrown: errorThrown,
                responseText: xhr.responseText
            });
            
            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMsg = xhr.responseJSON.data.message;
            } else if (xhr && xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    }
                } catch (e) {
                    // Si no es JSON válido, usar respuesta raw si es corta
                    if (xhr.responseText.length < 200) {
                        errorMsg += ' - ' + xhr.responseText;
                    }
                }
            } else if (errorThrown) {
                errorMsg += ' - ' + errorThrown;
            }
            
            // Información adicional para errores de red
            if (xhr.status === 0) {
                errorMsg = 'Error de conexión: No se puede conectar con el servidor. Verifica tu conexión a internet.';
            } else if (xhr.status === 403) {
                errorMsg = 'Error de permisos: No tienes autorización para realizar esta acción.';
            } else if (xhr.status === 404) {
                errorMsg = 'Error 404: El endpoint AJAX no fue encontrado. Verifica que el plugin esté activado correctamente.';
            } else if (xhr.status === 500) {
                errorMsg = 'Error interno del servidor. Revisa los logs de PHP para más detalles.';
            }

            showResult(resultContainer, errorMsg, 'error');
            button.html(originalText);
        })
        .always(function() {
            button.prop('disabled', false);
        });
    }

    /**
     * Inicializa los event handlers para Media Library
     */
    function initMediaLibrary() {
        // Event delegation para botones en vista lista y modal
        $(document).on('click', '.scp-webp-convert, .scp-webp-reconvert', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var attachmentId = parseInt($button.data('id'));
            var force = $button.data('force') === '1' || $button.data('force') === 1;
            
            if (!attachmentId) {
                alert('Error: ID de imagen no válido');
                return;
            }

            // Buscar contenedor para resultados
            var $resultContainer;
            
            // En modal de medios
            var $modalResult = $button.siblings('.scp-webp-result');
            if ($modalResult.length) {
                $resultContainer = $modalResult;
            }
            // En vista de lista - crear contenedor temporal
            else {
                var $row = $button.closest('tr');
                if ($row.length) {
                    $resultContainer = $row.find('.scp-webp-temp-result');
                    if (!$resultContainer.length) {
                        $resultContainer = $('<div class="scp-webp-temp-result"></div>');
                        $row.find('td').first().append($resultContainer);
                    }
                }
            }

            if (!$resultContainer) {
                $resultContainer = $('<div></div>');
            }

            // Confirmación para reconversión
            if (force) {
                var confirmMsg = '¿Estás seguro de que quieres reconvertir esta imagen? ' +
                               'Se reemplazarán todos los archivos WebP existentes.';
                if (!confirm(confirmMsg)) {
                    return;
                }
            }

            convertImage(attachmentId, force, $button, $resultContainer);
        });

        // Limpiar contenedores temporales cuando se cambie de página en lista
        $(document).on('click', '.tablenav-pages a, .manage-column.sortable a', function() {
            $('.scp-webp-temp-result').remove();
        });

        // CSS para mejorar la presentación
        $('<style>')
            .text(`
                .scp-webp-convert, .scp-webp-reconvert {
                    text-decoration: none;
                    cursor: pointer;
                }
                .scp-webp-convert:disabled, .scp-webp-reconvert:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                .scp-webp-result .notice {
                    margin: 5px 0 !important;
                    padding: 8px 12px !important;
                }
                .scp-webp-temp-result {
                    margin-top: 5px;
                }
                .scp-webp-temp-result .notice {
                    font-size: 12px;
                    padding: 5px 8px !important;
                }
            `)
            .appendTo('head');
    }

    /**
     * Integración específica con el Media Grid (vista cuadrícula)
     */
    function initMediaGrid() {
        // Para futuras mejoras - integración con vista de cuadrícula
        // WordPress usa Backbone.js para la vista grid, requeriría más trabajo
        console.log('SCP WebP: Vista de cuadrícula detectada - funcionalidad limitada');
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        // Detectar si estamos en Media Library
        if (typeof pagenow !== 'undefined' && (pagenow === 'upload' || pagenow === 'media')) {
            initMediaLibrary();
        }
        
        // Detectar vista de cuadrícula
        if ($('.media-frame').length || $('.media-modal').length) {
            initMediaGrid();
        }

        // Para modal de medios que se carga dinámicamente
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).hasClass('media-modal') || $(e.target).find('.media-modal').length) {
                // Delay pequeño para asegurar que el DOM esté completamente cargado
                setTimeout(initMediaLibrary, 100);
            }
        });
    });

    // Hacer funciones disponibles globalmente para debugging
    window.SCP_WebP_Media = {
        convertImage: convertImage,
        showResult: showResult,
        updateButton: updateButton
    };

})(jQuery);