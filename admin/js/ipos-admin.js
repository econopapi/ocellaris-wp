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
        
        // Crear contenedor de logs con mejor estilo
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
        
        // Función para agregar logs con mejor formato
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
        
        // Función para formatear tiempo
        function formatDuration(ms) {
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            
            if (minutes > 0) {
                return `${minutes}m ${remainingSeconds}s`;
            }
            return `${seconds}s`;
        }
        
        // Función para procesar un lote
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
                        
                        // Mostrar logs del servidor (NUEVO: con formato)
                        if (data.logs && Array.isArray(data.logs)) {
                            data.logs.forEach(function(log) {
                                const logClass = log.class || 'info';
                                addLog(log.message, logClass);
                            });
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
});