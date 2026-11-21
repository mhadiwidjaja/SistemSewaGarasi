{{--
    The shared confirmation pop-up. Any submit button with data-confirm="<question>" opens it
    instead of submitting; optional data-confirm-label and data-confirm-variant style the action button.
    Behaviour: public/js/confirm-modal.js
--}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="confirmModalTitle">Konfirmasi</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmModalAction">Ya</button>
            </div>
        </div>
    </div>
</div>
