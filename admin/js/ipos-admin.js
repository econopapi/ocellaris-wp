jQuery(document).ready(function($) {
    
    // Probar conexión con iPos
    $('#test-connection').on('click', function() {
        const $btn = $(this);
        const $status = $('#connection-status');
        
        $btn.addClass('loading').prop('disabled', true);
        $status.find('.connection-success, .connection-error').remove();
        
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'test_ipos_connection',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.append(
                        '<div class="connection-success">' + response.data + '</div>'
                    );
                } else {
                    $status.append(
                        '<div class="connection-error">❌ ' + response.data + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $status.append(
                    '<div class="connection-error">❌ Error: ' + error + '</div>'
                );
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Sincronizar categorías
    $('#sync-categories').on('click', function() {
        const $btn = $(this);
        const $progress = $('#sync-progress');
        const $results = $('#sync-results');
        
        if (!confirm('¿Querés sincronizar las categorías de iPos ahora? Esto puede tardar un toque.')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        $progress.show().find('#sync-message').text('Sincronizando categorías...');
        $results.hide().removeClass('success error');
        
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sync_ipos_categories',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                $progress.hide();
                
                if (response.success) {
                    const data = response.data;
                    let html = '<h3>' + data.message + '</h3>';
                    
                    html += '<ul>';
                    html += '<li>Total de categorías en iPos: ' + data.total + '</li>';
                    html += '<li>Categorías creadas: ' + data.created + '</li>';
                    html += '<li>Categorías actualizadas: ' + data.updated + '</li>';
                    html += '<li>Categorías omitidas: ' + data.skipped + '</li>';
                    html += '</ul>';
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<h4>Errores encontrados:</h4><ul>';
                        data.errors.forEach(function(error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    $results.addClass('success').html(html).show();
                    
                    // Recargar la página después de 20 segundos para actualizar stats
                    setTimeout(function() {
                        location.reload();
                    }, 45000);
                    
                } else {
                    $results.addClass('error')
                        .html('<h3>❌ Error en la sincronización</h3><p>' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function(xhr, status, error) {
                $progress.hide();
                $results.addClass('error')
                    .html('<h3>❌ Error</h3><p>Hubo un problema con la sincronización: ' + error + '</p>')
                    .show();
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Limpiar caché
    $('#clear-cache').on('click', function() {
        const $btn = $(this);
        
        if (!confirm('¿Estás seguro? Esto va a borrar el mapeo de categorías, productos y sesiones. Vas a tener que sincronizar de nuevo desde cero.')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_ipos_cache',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    location.reload();
                } else {
                    alert('❌ ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('❌ Error: ' + error);
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });

    // Sincronizar productos
    $('#sync-products').on('click', function() {
        const $btn = $(this);
        const $progress = $('#product-sync-progress');
        const $results = $('#product-sync-results');
        
        if (!confirm('¿Querés sincronizar los productos de iPos ahora? Esto puede tardar varios minutos.')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        $progress.show();
        $results.hide().removeClass('success error').html('');
        
        // Crear contenedor de logs
        const $logsContainer = $('<div class="sync-logs-container"></div>');
        $progress.after($logsContainer);
        
        // Variable para trackear el estado global
        let offset = 0;
        let totalProcessed = 0;
        let totalActive = 0;
        let totalProducts = 0;
        let allCreated = 0;
        let allUpdated = 0;
        let allSkipped = 0;
        let allErrors = [];
        
        // Función para agregar logs al UI
        function addLog(message) {
            const timestamp = new Date().toLocaleTimeString('es-AR');
            const $logEntry = $('<div class="log-entry"></div>')
                .html(`<span class="log-time">${timestamp}</span> ${message}`);
            $logsContainer.append($logEntry);
            $logsContainer.scrollTop($logsContainer[0].scrollHeight);
        }
        
        function processBatch() {
            addLog(`<strong>⏳ Iniciando descarga del lote...</strong> (offset: ${offset})`);
            
            $.ajax({
                url: iposAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sync_ipos_products',
                    nonce: iposAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Guardar valores globales
                        totalProducts = data.total;
                        totalActive = data.active;
                        totalProcessed = data.processed;
                        allCreated += data.created;
                        allUpdated += data.updated;
                        allSkipped += data.skipped;
                        
                        if (data.errors && data.errors.length > 0) {
                            allErrors = allErrors.concat(data.errors);
                        }
                        
                        // Mostrar logs del servidor
                        if (data.logs && Array.isArray(data.logs)) {
                            data.logs.forEach(function(log) {
                                addLog(log.message);
                            });
                        }
                        
                        // Actualizar barra de progreso
                        const percentage = totalActive > 0 ? (totalProcessed / totalActive) * 100 : 0;
                        $('#product-progress-fill').css('width', percentage + '%');
                        $('#product-progress-text').text(
                            `${totalProcessed} / ${totalActive} productos procesados (${percentage.toFixed(1)}%)`
                        );
                        $('#product-sync-message').html(
                            `<strong>${data.message}</strong><br>` +
                            `Creados: ${allCreated} | Actualizados: ${allUpdated} | Omitidos: ${allSkipped}`
                        );
                        
                        // Si no está completado, procesar siguiente lote
                        if (!data.completed && data.next_offset !== null) {
                            offset = data.next_offset;
                            addLog(`<strong>✅ Lote completado</strong> - Esperando 2 segundos antes del siguiente...`);
                            
                            // Pequeño delay para no saturar el servidor
                            setTimeout(processBatch, 2000);
                        } else {
                            // Sincronización completa
                            completeSyncProcess(data);
                        }
                    } else {
                        $progress.hide();
                        const errorMsg = (response.data && response.data.message) ? response.data.message : 'Error desconocido';
                        addLog(`<strong>❌ Error en sincronización:</strong> ${errorMsg}`);
                        $results.addClass('error')
                            .html(`<h3>❌ Error en la sincronización</h3><p>${errorMsg}</p>`)
                            .show();
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $progress.hide();
                    addLog(`<strong>❌ Error AJAX:</strong> ${error}`);
                    $results.addClass('error')
                        .html(`<h3>❌ Error de conexión</h3><p>Hubo un problema con la sincronización: ${error}</p>`)
                        .show();
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        }
        
        // Función para completar el proceso
        function completeSyncProcess(finalData) {
            $progress.hide();
            
            addLog(`<strong>✨ ¡Sincronización completada!</strong>`);
            addLog(`📊 Resumen final:`);
            addLog(`  • Total productos en iPos: ${totalProducts}`);
            addLog(`  • Productos activos: ${totalActive}`);
            addLog(`  • Creados: ${allCreated}`);
            addLog(`  • Actualizados: ${allUpdated}`);
            addLog(`  • Omitidos: ${allSkipped}`);
            
            let html = '<h3>✅ ¡Sincronización completada!</h3>';
            html += '<ul>';
            html += `<li>Total de productos en iPos: ${totalProducts}</li>`;
            html += `<li>Productos activos sincronizados: ${totalActive}</li>`;
            html += `<li>Productos creados: ${allCreated}</li>`;
            html += `<li>Productos actualizados: ${allUpdated}</li>`;
            html += `<li>Productos omitidos: ${allSkipped}</li>`;
            html += '</ul>';
            
            if (allErrors.length > 0) {
                html += '<h4>Errores encontrados:</h4><ul>';
                allErrors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
                addLog(`<strong>⚠️ Se encontraron ${allErrors.length} errores</strong>`);
            }
            
            html += '<p style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px;">' +
                    'La página se recargará en 30 segundos para actualizar las estadísticas...' +
                    '</p>';
            
            $results.addClass('success').html(html).show();
            
            // Recargar después de 30 segundos
            setTimeout(function() {
                location.reload();
            }, 30000);
            
            $btn.removeClass('loading').prop('disabled', false);
        }
        
        // Iniciar el procesamiento
        addLog(`<strong>🚀 Iniciando sincronización de productos...</strong>`);
        processBatch();
    });
});