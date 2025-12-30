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

    // Sincronizar productos (OPTIMIZADO)
    $('#sync-products').on('click', function() {
        const $btn = $(this);
        const $progress = $('#product-sync-progress');
        const $results = $('#product-sync-results');
        
        if (!confirm('¿Querés sincronizar los productos de iPos ahora? Esto puede tardar varios minutos dependiendo de la cantidad de productos.')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        $progress.show();
        $results.hide().removeClass('success error').html('');
        
        // contenedor de logs
        let $logsContainer = $('.sync-logs-container');
        if ($logsContainer.length === 0) {
            $logsContainer = $('<div class="sync-logs-container"></div>');
            $progress.after($logsContainer);
        } else {
            $logsContainer.empty().show();
        }
        
        // Variables de tracking
        let offset = 0;
        let totalProcessed = 0;
        let totalActive = 0;
        let totalProducts = 0;
        let allCreated = 0;
        let allUpdated = 0;
        let allSkipped = 0;
        let allErrors = [];
        let batchCount = 0;
        let startTime = Date.now();
        let lastLogCount = 0; // NUEVO: para trackear logs nuevos
        
        // agregar log
        function addLog(message, className = 'info') {
            const timestamp = new Date().toLocaleTimeString('es-AR');
            const $logEntry = $('<div class="log-entry ' + className + '"></div>')
                .html(message);
            $logsContainer.append($logEntry);
            
            // Auto-scroll (pero permitir scroll manual)
            if ($logsContainer[0].scrollHeight - $logsContainer.scrollTop() < $logsContainer.height() + 100) {
                $logsContainer.scrollTop($logsContainer[0].scrollHeight);
            }
        }
        
        // NUEVO: procesar y mostrar logs del servidor
        function processServerLogs(logs) {
            if (!logs || !Array.isArray(logs)) {
                return;
            }
            
            // Solo mostrar logs nuevos desde la última vez
            const newLogs = logs.slice(lastLogCount);
            lastLogCount = logs.length;
            
            newLogs.forEach(function(logObj) {
                if (logObj.message) {
                    addLog(logObj.message, logObj.class || 'info');
                }
            });
        }
        
        // formatear duración
        function formatDuration(ms) {
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            
            if (minutes > 0) {
                return `${minutes}m ${remainingSeconds}s`;
            }
            return `${seconds}s`;
        }
        
        // procesar un lote
        function processBatch() {
            batchCount++;
            const batchStartTime = Date.now();
            
            addLog(`<strong>🚀 Lote #${batchCount} - Iniciando procesamiento...</strong> (offset: ${offset})`, 'batch-start');
            
            $.ajax({
                url: iposAdmin.ajax_url,
                type: 'POST',
                dataType: 'json',
                timeout: 300000, // 5 minutos de timeout
                data: {
                    action: 'sync_ipos_products',
                    nonce: iposAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    const batchDuration = Date.now() - batchStartTime;
                    
                    if (response.success) {
                        const data = response.data;
                        
                        // NUEVO: Mostrar logs del servidor PRIMERO
                        if (data.logs && Array.isArray(data.logs)) {
                            processServerLogs(data.logs);
                        }
                        
                        // Guardar valores globales
                        totalProducts = data.total;
                        totalActive = data.active;
                        totalProcessed = data.processed;
                        allCreated += data.created || 0;
                        allUpdated += data.updated || 0;
                        allSkipped += data.skipped || 0;
                        
                        if (data.errors && data.errors.length > 0) {
                            allErrors = allErrors.concat(data.errors);
                        }
                        
                        // Actualizar barra de progreso
                        const percentage = totalActive > 0 ? (totalProcessed / totalActive) * 100 : 0;
                        $('#product-progress-fill').css('width', percentage + '%');
                        
                        const elapsedTime = Date.now() - startTime;
                        const estimatedTotal = totalProcessed > 0 ? (elapsedTime / totalProcessed) * totalActive : 0;
                        const remainingTime = estimatedTotal - elapsedTime;
                        
                        $('#product-progress-text').html(
                            `<strong>${totalProcessed} / ${totalActive}</strong> productos procesados ` +
                            `(<strong>${percentage.toFixed(1)}%</strong>)<br>` +
                            `<small>Tiempo transcurrido: ${formatDuration(elapsedTime)} | ` +
                            `Estimado restante: ${remainingTime > 0 ? formatDuration(remainingTime) : 'calculando...'}</small>`
                        );
                        
                        $('#product-sync-message').html(
                            `<strong>${data.message}</strong><br>` +
                            `✅ Creados: <strong>${allCreated}</strong> | ` +
                            `🔄 Actualizados: <strong>${allUpdated}</strong> | ` +
                            `⏭️ Omitidos: <strong>${allSkipped}</strong> | ` +
                            `❌ Errores: <strong>${allErrors.length}</strong>`
                        );
                        
                        addLog(
                            `<strong>✅ Lote #${batchCount} completado en ${formatDuration(batchDuration)}</strong> - ` +
                            `Creados: ${data.created}, Actualizados: ${data.updated}, Omitidos: ${data.skipped}`,
                            'batch-complete'
                        );
                        
                        // Si no está completado, procesar siguiente lote
                        if (!data.completed && data.next_offset !== null) {
                            offset = data.next_offset;
                            
                            addLog(
                                `⏳ Esperando 1 segundo antes del siguiente lote...`,
                                'waiting'
                            );
                            
                            // Delay de 1 segundo entre lotes
                            setTimeout(processBatch, 1000);
                        } else {
                            // Sincronización completa
                            completeSyncProcess(data);
                        }
                    } else {
                        $progress.hide();
                        const errorMsg = (response.data && response.data.message) ? response.data.message : 'Error desconocido';
                        addLog(`<strong>❌ Error en sincronización:</strong> ${errorMsg}`, 'error');
                        
                        $results.addClass('error')
                            .html(`<h3>❌ Error en la sincronización</h3><p>${errorMsg}</p>`)
                            .show();
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $progress.hide();
                    
                    let errorDetail = error;
                    if (status === 'timeout') {
                        errorDetail = 'Timeout de servidor (el proceso tardó demasiado). Intenta reducir el batch_size en class-product-sync.php';
                    } else if (xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            errorDetail = parsed.message || error;
                        } catch(e) {
                            errorDetail = xhr.responseText.substring(0, 200);
                        }
                    }
                    
                    addLog(`<strong>❌ Error AJAX:</strong> ${errorDetail}`, 'error');
                    
                    $results.addClass('error')
                        .html(`<h3>❌ Error de conexión</h3><p>${errorDetail}</p>`)
                        .show();
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        }
        
        // Función para completar el proceso
        function completeSyncProcess(finalData) {
            $progress.hide();
            
            const totalTime = Date.now() - startTime;
            
            addLog(`<strong>🎉 ¡Sincronización completada!</strong>`, 'final-success');
            addLog(`⏱️ Tiempo total: <strong>${formatDuration(totalTime)}</strong>`, 'final-info');
            addLog(`📊 <strong>Resumen final:</strong>`, 'final-info');
            addLog(`  • Total productos en iPos: <strong>${totalProducts}</strong>`, 'final-detail');
            addLog(`  • Productos activos: <strong>${totalActive}</strong>`, 'final-detail');
            addLog(`  • ✅ Creados: <strong>${allCreated}</strong>`, 'final-detail');
            addLog(`  • 🔄 Actualizados: <strong>${allUpdated}</strong>`, 'final-detail');
            addLog(`  • ⏭️ Omitidos: <strong>${allSkipped}</strong>`, 'final-detail');
            addLog(`  • ❌ Errores: <strong>${allErrors.length}</strong>`, 'final-detail');
            
            let html = '<h3>🎉 ¡Sincronización completada!</h3>';
            html += `<p><strong>⏱️ Tiempo total:</strong> ${formatDuration(totalTime)}</p>`;
            html += '<ul>';
            html += `<li><strong>Total de productos en iPos:</strong> ${totalProducts}</li>`;
            html += `<li><strong>Productos activos sincronizados:</strong> ${totalActive}</li>`;
            html += `<li><strong>✅ Productos creados:</strong> ${allCreated}</li>`;
            html += `<li><strong>🔄 Productos actualizados:</strong> ${allUpdated}</li>`;
            html += `<li><strong>⏭️ Productos omitidos:</strong> ${allSkipped}</li>`;
            html += `<li><strong>❌ Errores:</strong> ${allErrors.length}</li>`;
            html += '</ul>';
            
            if (allErrors.length > 0) {
                html += '<h4>⚠️ Errores encontrados:</h4><ul class="error-list">';
                allErrors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
            }
            
            html += '<p style="margin-top: 20px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">' +
                    '✅ La sincronización se completó exitosamente. La página se recargará en 20 segundos para actualizar las estadísticas.' +
                    '</p>';
            
            $results.addClass('success').html(html).show();
            
            // Recargar después de 20 segundos
            setTimeout(function() {
                location.reload();
            }, 20000);
            
            $btn.removeClass('loading').prop('disabled', false);
        }
        
        // Iniciar el procesamiento
        addLog(`<strong>🚀 Iniciando sincronización de productos...</strong>`, 'start');
        addLog(`📅 ${new Date().toLocaleString('es-AR')}`, 'start');
        processBatch();
    });


    $('#sync-stock').on('click', function() {
        const $btn = $(this);
        const $progress = $('#stock-sync-progress');
        const $results = $('#stock-sync-results');
        
        if (!confirm('¿Querés sincronizar el stock de iPos ahora? Esto puede tardar varios minutos dependiendo de la cantidad de productos.')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        $progress.show();
        $results.hide().removeClass('success error').html('');
        
        let $logsContainer = $('.sync-logs-container');
        if ($logsContainer.length === 0) {
            $logsContainer = $('<div class="sync-logs-container"></div>');
            $progress.after($logsContainer);
        } else {
            $logsContainer.empty().show();
        }
        
        let offset = 0;
        let totalProcessed = 0;
        let totalStock = 0;
        let allUpdated = 0;
        let allFailed = 0;
        let allErrors = [];
        let batchCount = 0;
        let startTime = Date.now();
        let lastLogCount = 0;
        
        function addLog(message, className = 'info') {
            const timestamp = new Date().toLocaleTimeString('es-AR');
            const $logEntry = $('<div class="log-entry ' + className + '"></div>')
                .html(message);
            $logsContainer.append($logEntry);
            
            if ($logsContainer[0].scrollHeight - $logsContainer.scrollTop() < $logsContainer.height() + 100) {
                $logsContainer.scrollTop($logsContainer[0].scrollHeight);
            }
        }
        
        function processServerLogs(logs) {
            if (!logs || !Array.isArray(logs)) {
                return;
            }
            
            const newLogs = logs.slice(lastLogCount);
            lastLogCount = logs.length;
            
            newLogs.forEach(function(logObj) {
                if (logObj.message) {
                    addLog(logObj.message, logObj.class || 'info');
                }
            });
        }
        
        function formatDuration(ms) {
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            
            if (minutes > 0) {
                return `${minutes}m ${remainingSeconds}s`;
            }
            return `${seconds}s`;
        }
        
        function processBatch() {
            batchCount++;
            const batchStartTime = Date.now();
            
            addLog(`<strong>🚀 Lote #${batchCount} - Sincronizando stock...</strong> (offset: ${offset})`, 'batch-start');
            
            $.ajax({
                url: iposAdmin.ajax_url,
                type: 'POST',
                dataType: 'json',
                timeout: 300000,
                data: {
                    action: 'sync_ipos_stock',
                    nonce: iposAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    const batchDuration = Date.now() - batchStartTime;
                    
                    if (response.success) {
                        const data = response.data;
                        
                        if (data.logs && Array.isArray(data.logs)) {
                            processServerLogs(data.logs);
                        }
                        
                        totalStock = data.total;
                        totalProcessed = data.processed;
                        allUpdated += data.updated || 0;
                        allFailed += data.failed || 0;
                        
                        if (data.errors && data.errors.length > 0) {
                            allErrors = allErrors.concat(data.errors);
                        }
                        
                        const percentage = totalStock > 0 ? (totalProcessed / totalStock) * 100 : 0;
                        $('#stock-progress-fill').css('width', percentage + '%');
                        
                        const elapsedTime = Date.now() - startTime;
                        const estimatedTotal = totalProcessed > 0 ? (elapsedTime / totalProcessed) * totalStock : 0;
                        const remainingTime = estimatedTotal - elapsedTime;
                        
                        $('#stock-progress-text').html(
                            `<strong>${totalProcessed} / ${totalStock}</strong> productos procesados ` +
                            `(<strong>${percentage.toFixed(1)}%</strong>)<br>` +
                            `<small>Tiempo transcurrido: ${formatDuration(elapsedTime)} | ` +
                            `Estimado restante: ${remainingTime > 0 ? formatDuration(remainingTime) : 'calculando...'}</small>`
                        );
                        
                        $('#stock-sync-message').html(
                            `<strong>${data.message}</strong><br>` +
                            `✅ Actualizados: <strong>${allUpdated}</strong> | ` +
                            `❌ Fallidos: <strong>${allFailed}</strong> | ` +
                            `⚠️ Errores: <strong>${allErrors.length}</strong>`
                        );
                        
                        addLog(
                            `<strong>✅ Lote #${batchCount} completado en ${formatDuration(batchDuration)}</strong> - ` +
                            `Actualizados: ${data.updated}, Fallidos: ${data.failed}`,
                            'batch-complete'
                        );
                        
                        if (!data.completed && data.next_offset !== null) {
                            offset = data.next_offset;
                            addLog('⏳ Esperando 1 segundo antes del siguiente lote...', 'waiting');
                            setTimeout(processBatch, 1000);
                        } else {
                            completeSyncProcess(data);
                        }
                    } else {
                        $progress.hide();
                        const errorMsg = (response.data && response.data.message) ? response.data.message : 'Error desconocido';
                        addLog(`<strong>❌ Error en sincronización:</strong> ${errorMsg}`, 'error');
                        
                        $results.addClass('error')
                            .html(`<h3>❌ Error en la sincronización</h3><p>${errorMsg}</p>`)
                            .show();
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $progress.hide();
                    
                    let errorDetail = error;
                    if (status === 'timeout') {
                        errorDetail = 'Timeout de servidor';
                    }
                    
                    addLog(`<strong>❌ Error AJAX:</strong> ${errorDetail}`, 'error');
                    
                    $results.addClass('error')
                        .html(`<h3>❌ Error de conexión</h3><p>${errorDetail}</p>`)
                        .show();
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        }
        
        function completeSyncProcess(finalData) {
            $progress.hide();
            
            const totalTime = Date.now() - startTime;
            
            addLog(`<strong>🎉 ¡Sincronización de stock completada!</strong>`, 'final-success');
            addLog(`⏱️ Tiempo total: <strong>${formatDuration(totalTime)}</strong>`, 'final-info');
            
            let html = '<h3>🎉 ¡Sincronización de stock completada!</h3>';
            html += `<p><strong>⏱️ Tiempo total:</strong> ${formatDuration(totalTime)}</p>`;
            html += '<ul>';
            html += `<li><strong>Productos sincronizados:</strong> ${totalStock}</li>`;
            html += `<li><strong>✅ Stock actualizado:</strong> ${allUpdated}</li>`;
            html += `<li><strong>❌ Errores:</strong> ${allFailed}</li>`;
            html += '</ul>';
            
            if (allErrors.length > 0) {
                html += '<h4>⚠️ Errores encontrados:</h4><ul class="error-list">';
                allErrors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
            }
            
            html += '<p style="margin-top: 20px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">' +
                    '✅ La sincronización de stock se completó exitosamente.' +
                    '</p>';
            
            $results.addClass('success').html(html).show();
            $btn.removeClass('loading').prop('disabled', false);
        }
        
        addLog(`<strong>🚀 Iniciando sincronización de stock...</strong>`, 'start');
        addLog(`📅 ${new Date().toLocaleString('es-AR')}`, 'start');
        processBatch();
    });    
    
    // ============================================
    // WEBHOOK HANDLERS
    // ============================================
    
    // Actualizar estado del webhook al cargar
    function updateWebhookStatus() {
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'get_webhook_status',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                const status = response.data;
                
                // Si hay webhook_id, mostrar info (activo o inactivo)
                if (status.webhook_id) {
                    $('#webhook-info').show();
                    $('#webhook-inactive').hide();
                    
                    // Actualizar badge
                    let badgeHTML = '';
                    if (status.active) {
                        badgeHTML = '<span class="webhook-status-active" style="background: #28a745; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;">✅ Activo</span>';
                    } else {
                        badgeHTML = '<span class="webhook-status-inactive" style="background: #ffc107; color: #000; padding: 4px 8px; border-radius: 3px; font-weight: bold;">⚠️ ' + status.status.toUpperCase() + '</span>';
                    }
                    $('#webhook-status-badge').html(badgeHTML);
                    
                    // Actualizar información
                    $('#webhook-id').text(status.webhook_id);
                    $('#webhook-url').text(status.delivery_url || 'N/A');
                    
                    // Mostrar botones según estado
                    let actionButtons = '';
                    if (status.active) {
                        actionButtons = '<button type="button" class="button button-danger" id="delete-webhook" style="background-color: #dc3545; border-color: #dc3545; color: white;">🗑️ Eliminar Webhook</button>';
                    } else {
                        actionButtons = '<button type="button" class="button button-primary" id="reactivate-webhook">🔄 Reactivar Webhook</button> ' +
                                       '<button type="button" class="button button-danger" id="delete-webhook" style="background-color: #dc3545; border-color: #dc3545; color: white; margin-left: 10px;">🗑️ Eliminar Webhook</button>';
                    }
                    
                    $('#webhook-info div:last-child').html(actionButtons);
                } else {
                    // No hay webhook, mostrar opción de crear
                    $('#webhook-info').hide();
                    $('#webhook-inactive').show();
                }
            },
            error: function() {
                $('#webhook-status-container').html(
                    '<div class="notice notice-error"><p>Error al cargar el estado del webhook.</p></div>'
                );
            }
        });
    }
    // function updateWebhookStatus() {
    //     $.ajax({
    //         url: iposAdmin.ajax_url,
    //         type: 'POST',
    //         data: {
    //             action: 'get_webhook_status',
    //             nonce: iposAdmin.nonce
    //         },
    //         success: function(response) {
    //             const status = response.data;
                
    //             if (status.active) {
    //                 // Mostrar información del webhook
    //                 $('#webhook-info').show();
    //                 $('#webhook-inactive').hide();
                    
    //                 // Actualizar badge de estado
    //                 const badge = $('<span class="webhook-status-active">✅ Activo</span>');
    //                 $('#webhook-status-badge').html(badge);
                    
    //                 // Actualizar información
    //                 $('#webhook-id').text(status.webhook_id);
    //                 $('#webhook-url').text(status.delivery_url);
    //             } else {
    //                 // Mostrar opción de crear webhook
    //                 $('#webhook-info').hide();
    //                 $('#webhook-inactive').show();
    //                 $('#webhook-status-container').html(
    //                     '<button type="button" class="button button-secondary" id="refresh-webhook-status">' +
    //                     '🔄 Actualizar Estado</button>'
    //                 );
    //             }
    //         },
    //         error: function() {
    //             $('#webhook-status-container').html(
    //                 '<div class="notice notice-error"><p>Error al cargar el estado del webhook.</p></div>'
    //             );
    //         }
    //     });
    // }

    // Reactivar webhook
    $(document).on('click', '#reactivate-webhook', function() {
        const $btn = $(this);
        
        if (!confirm('¿Querés reactivar el webhook?')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'reactivate_ipos_webhook',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Webhook reactivado exitosamente!');
                    updateWebhookStatus();
                } else {
                    alert('❌ Error: ' + (response.data?.message || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                alert('❌ Error al reactivar webhook: ' + error);
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });    
    
    // Crear webhook
    $(document).on('click', '#create-webhook', function() {
        const $btn = $(this);
        
        if (!confirm('¿Querés crear el webhook para sincronización automática de ventas?')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'create_ipos_webhook',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    alert('✅ Webhook creado exitosamente!\n\nID: ' + data.webhook_id);
                    updateWebhookStatus();
                } else {
                    alert('❌ Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('❌ Error al crear webhook: ' + error);
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Eliminar webhook
    $(document).on('click', '#delete-webhook', function() {
        const $btn = $(this);
        
        if (!confirm('⚠️ ¿Estás seguro que querés eliminar el webhook?\n\nLa sincronización automática de ventas se desactivará.')) {
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_ipos_webhook',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Webhook eliminado correctamente.');
                    updateWebhookStatus();
                } else {
                    alert('❌ Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('❌ Error al eliminar webhook: ' + error);
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Actualizar estado del webhook
    $(document).on('click', '#refresh-webhook-status', function() {
        const $btn = $(this);
        $btn.addClass('loading').prop('disabled', true);
        
        setTimeout(function() {
            updateWebhookStatus();
            $btn.removeClass('loading').prop('disabled', false);
        }, 500);
    });
    
    // Cargar estado inicial del webhook
    updateWebhookStatus();

    // Render System Health
    function renderSystemHealth(data) {
        const $container = $('#system-health');
        if (!$container.length) return;

        const overall = data.summary?.overall || 'warn';
        const msg = data.summary?.message || 'Estado del sistema';

        let html = '';
        html += `<div class="system-health-summary"><span class="status-dot ${overall === 'ok' ? 'ok' : (overall === 'error' ? 'error' : 'warn')}"></span>${msg}</div>`;
        html += '<ul class="health-list">';
        (data.items || []).forEach(function(item) {
            const st = item.status || 'warn';
            html += `
                <li class="health-item">
                    <div class="label">${item.label}</div>
                    <div class="value">
                        <span class="health-badge ${st}">${st.toUpperCase()}</span>
                        <span class="detail">${item.detail || ''}</span>
                    </div>
                </li>`;
        });
        html += '</ul>';

        $container.html(html);
    }

    // Actualizar System Health automáticamente
    function updateSystemHealth() {
        const $container = $('#system-health');
        if (!$container.length) return;

        $container.html('<div class="health-loading"><span class="sync-spinner"></span> Comprobando estado del sistema...</div>');

        $.ajax({
            url: iposAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_ipos_system_health',
                nonce: iposAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderSystemHealth(response.data);
                } else {
                    $container.html('<div class="connection-error">❌ No se pudo cargar el System Health.</div>');
                }
            },
            error: function() {
                $container.html('<div class="connection-error">❌ Error de conexión al cargar el System Health.</div>');
            }
        });
    }

    // Ejecutar al cargar
    updateSystemHealth();
});