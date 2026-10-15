<?php if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; } ?>
    <footer class="border-t-4 border-admin-gold bg-admin-indigoDark text-white">
        <div class="px-4 py-6 text-center text-sm text-indigo-200 sm:px-6 lg:px-8">
            &copy; <?= date('Y') ?> Department of Education. All rights reserved.
        </div>
    </footer>

    <div id="confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-black text-admin-indigoDark">Please confirm</h3>
            <p id="confirm-modal-message" class="mt-3 text-sm text-slate-600" style="white-space:pre-line;"></p>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" id="confirm-modal-cancel" class="rounded-lg px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="button" id="confirm-modal-ok" class="rounded-lg bg-admin-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('confirm-modal');
            var messageEl = document.getElementById('confirm-modal-message');
            var okBtn = document.getElementById('confirm-modal-ok');
            var cancelBtn = document.getElementById('confirm-modal-cancel');
            if (!modal) return;

            var pendingAction = null;

            function openModal(message, action) {
                messageEl.textContent = message;
                pendingAction = action;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                pendingAction = null;
            }

            // A whole form always confirms on submit.
            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    openModal(form.dataset.confirm, { type: 'form', form: form });
                });
            });

            // A single submit button inside a larger form (e.g. one of several
            // actions sharing a form) only confirms its own click, not every
            // submission of that form.
            document.querySelectorAll('button[data-confirm]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    openModal(btn.dataset.confirm, { type: 'button', form: btn.form, button: btn });
                });
            });

            okBtn.addEventListener('click', function () {
                var action = pendingAction;
                closeModal();
                if (!action) return;
                if (action.type === 'form') {
                    action.form.submit();
                } else if (action.type === 'button' && action.form) {
                    if (action.form.requestSubmit) {
                        action.form.requestSubmit(action.button);
                    } else {
                        action.form.submit();
                    }
                }
            });

            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
        })();
    </script>
</body>
</html>
