{*
* Plantilla para diálogos de confirmación
*}

<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon icon-question-circle"></i> {l s='Confirmación requerida' mod='megasincronizacion'}
        </div>
        
        <div class="panel-body">
            <div class="alert alert-warning">
                <p>{$message}</p>
            </div>
            
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="confirm-checkbox"> {l s='Confirmo que deseo realizar esta acción' mod='megasincronizacion'}
                    </label>
                </div>
            </div>
        </div>
        
        <div class="panel-footer">
            <a href="{$link->getAdminLink($controller)}" class="btn btn-default">
                <i class="icon icon-arrow-left"></i> {l s='Cancelar' mod='megasincronizacion'}
            </a>
            
            <a href="{$link->getAdminLink($controller)}&{$action}=1&confirm=1&token={$token}" class="btn btn-danger pull-right" id="confirm-btn" disabled>
                <i class="icon icon-check"></i> {l s='Confirmar' mod='megasincronizacion'}
            </a>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#confirm-checkbox').change(function() {
        $('#confirm-btn').prop('disabled', !$(this).is(':checked'));
    });
});
</script>
