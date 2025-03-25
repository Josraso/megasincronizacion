/**
 * JavaScript global para la administración del módulo MegaSincronización
 */

// Función para mostrar mensajes de éxito
function showSuccessMessage(message) {
    $.growl.notice({
        title: '',
        message: message,
        duration: 5000
    });
}

// Función para mostrar mensajes de error
function showErrorMessage(message) {
    $.growl.error({
        title: '',
        message: message,
        duration: 5000
    });
}

// Función para mostrar mensajes de advertencia
function showWarningMessage(message) {
    $.growl.warning({
        title: '',
        message: message,
        duration: 5000
    });
}

// Función para confirmar acciones peligrosas
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Función para manejar errores AJAX
function handleAjaxError(xhr, status, error) {
    console.error('Error AJAX:', error);
    console.error('Status:', status);
    console.error('Response:', xhr.responseText);
    
    showErrorMessage('Error de comunicación con el servidor. Por favor, inténtelo de nuevo más tarde.');
}

// Función para formatear fechas
function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

// Función para formatear números con separadores de miles
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Función para formatear precios
function formatPrice(price, currencySign = '€') {
    return formatNumber(parseFloat(price).toFixed(2)) + ' ' + currencySign;
}

// Función para inicializar datepickers en formularios
function initDatepickers() {
    if (typeof $.datepicker !== 'undefined') {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
}

// Función para validar URLs
function isValidUrl(url) {
    var pattern = new RegExp('^(https?:\\/\\/)?' + // protocolo
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // dominio
        '((\\d{1,3}\\.){3}\\d{1,3}))' + // O dirección IP
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // puerto y ruta
        '(\\?[;&a-z\\d%_.~+=-]*)?' + // parámetros de consulta
        '(\\#[-a-z\\d_]*)?$', 'i'); // fragmento
    return pattern.test(url);
}

// Inicializar elementos comunes al cargar la página
$(document).ready(function() {
    // Inicializar tooltips
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Inicializar datepickers
    initDatepickers();
    
    // Añadir confirmación para botones peligrosos
    $('.btn-danger[data-confirm]').click(function(e) {
        var message = $(this).data('confirm');
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Manejar copiar al portapapeles
    $('.copy-to-clipboard').click(function() {
        var text = $(this).data('copy');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(text).select();
        document.execCommand('copy');
        tempInput.remove();
        showSuccessMessage('Texto copiado al portapapeles');
    });
    
    // Marcar campo activo en formularios
    $('.form-control').focus(function() {
        $(this).closest('.form-group').addClass('focus');
    }).blur(function() {
        $(this).closest('.form-group').removeClass('focus');
    });
    
    // Contador de caracteres para campos de texto
    $('textarea[maxlength]').each(function() {
        var max = parseInt($(this).attr('maxlength'));
        var counterHtml = '<div class="char-counter"><span class="current">0</span>/<span class="maximum">' + max + '</span></div>';
        $(this).after(counterHtml);
        
        $(this).on('input', function() {
            var current = $(this).val().length;
            $(this).next('.char-counter').find('.current').text(current);
            
            if (current > max * 0.8) {
                $(this).next('.char-counter').addClass('warning');
            } else {
                $(this).next('.char-counter').removeClass('warning');
            }
        });
        
        // Inicializar contador
        $(this).trigger('input');
    });
    
    // Botones de alternar visibilidad de contraseñas
    $('.toggle-password').click(function() {
        var input = $(this).closest('.input-group').find('input');
        var icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('icon-eye').addClass('icon-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('icon-eye-slash').addClass('icon-eye');
        }
    });
});