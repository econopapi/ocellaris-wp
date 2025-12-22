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
                    
                    // Recargar la página después de 2 segundos para actualizar stats
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    
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
        
        if (!confirm('¿Estás seguro? Esto va a borrar el mapeo de categorías y vas a tener que sincronizar de nuevo.')) {
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
});