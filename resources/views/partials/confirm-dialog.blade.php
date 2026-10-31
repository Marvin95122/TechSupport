{{-- Confirmación propia de TechSupport; los formularios mantienen su método, CSRF y rutas. --}}
<dialog class="ts-confirm-dialog" id="tsConfirmDialog" aria-labelledby="tsConfirmTitle" aria-describedby="tsConfirmMessage">
    <div class="ts-confirm-inner">
        <div class="ts-confirm-icon" aria-hidden="true">
            <svg class="ts-icon"><use href="#ts-icon-alert"/></svg>
        </div>
        <h2 class="ts-confirm-title" id="tsConfirmTitle">Confirmar acción</h2>
        <p class="ts-confirm-message" id="tsConfirmMessage">¿Deseas continuar?</p>
        <div class="ts-confirm-actions">
            <button type="button" class="btn btn-outline-secondary" data-ts-cancel>Cancelar</button>
            <button type="button" class="btn btn-danger" data-ts-accept>Confirmar</button>
        </div>
    </div>
</dialog>
