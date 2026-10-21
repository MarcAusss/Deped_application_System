<?php if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; } ?>
    <footer class="border-t-4 border-government-gold bg-government-dark text-white">
        <div class="px-4 py-6 text-center text-sm text-slate-300 sm:px-6 lg:px-8">
            &copy; <?= date('Y') ?> Department of Education. All rights reserved.
        </div>
    </footer>

    <div id="confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-black text-government-navy">Please confirm</h3>
            <p id="confirm-modal-message" class="mt-3 text-sm text-slate-600" style="white-space:pre-line;"></p>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" id="confirm-modal-cancel" class="rounded-lg px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="button" id="confirm-modal-ok" class="rounded-lg bg-government-navy px-5 py-2.5 text-sm font-bold text-white hover:bg-government-blue">Confirm</button>
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

            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    openModal(form.dataset.confirm, { type: 'form', form: form });
                });
            });

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

    <div id="alert-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/50 p-4">
        <div id="alert-modal-card" class="w-full max-w-md rounded-2xl border-2 bg-white p-6 shadow-2xl">
            <div class="flex items-center gap-3">
                <span id="alert-modal-icon-wrap" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                    <svg id="alert-modal-icon-error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <svg id="alert-modal-icon-success" class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
                <h3 id="alert-modal-title" class="text-lg font-black">Notice</h3>
            </div>
            <p id="alert-modal-message" class="mt-3 text-sm text-slate-600" style="white-space:pre-line;"></p>
            <div class="mt-6 flex items-center justify-end">
                <button type="button" id="alert-modal-ok" class="rounded-lg px-5 py-2.5 text-sm font-bold text-white">OK</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('alert-modal');
            var card = document.getElementById('alert-modal-card');
            var titleEl = document.getElementById('alert-modal-title');
            var messageEl = document.getElementById('alert-modal-message');
            var okBtn = document.getElementById('alert-modal-ok');
            var iconWrap = document.getElementById('alert-modal-icon-wrap');
            var iconError = document.getElementById('alert-modal-icon-error');
            var iconSuccess = document.getElementById('alert-modal-icon-success');
            if (!modal) return;

            // Drop-in replacement for window.alert() — same call signature,
            // but renders as this site's centered modal instead of the
            // browser's native dialog. type: 'error' (default, red) or
            // 'success' (green).
            window.showAlert = function (message, type) {
                var isSuccess = type === 'success';
                messageEl.textContent = message;
                titleEl.textContent = isSuccess ? 'Success' : 'Notice';

                card.classList.toggle('border-red-200', !isSuccess);
                card.classList.toggle('border-green-200', isSuccess);
                titleEl.classList.toggle('text-red-700', !isSuccess);
                titleEl.classList.toggle('text-green-700', isSuccess);
                iconWrap.classList.toggle('bg-red-100', !isSuccess);
                iconWrap.classList.toggle('text-red-600', !isSuccess);
                iconWrap.classList.toggle('bg-green-100', isSuccess);
                iconWrap.classList.toggle('text-green-600', isSuccess);
                iconError.classList.toggle('hidden', isSuccess);
                iconSuccess.classList.toggle('hidden', !isSuccess);
                okBtn.classList.toggle('bg-red-600', !isSuccess);
                okBtn.classList.toggle('hover:bg-red-700', !isSuccess);
                okBtn.classList.toggle('bg-green-600', isSuccess);
                okBtn.classList.toggle('hover:bg-green-700', isSuccess);

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            okBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

            // Every page on this site renders flash success/error messages
            // as the same inline banner (border-green-200/bg-green-50/
            // text-green-800, or the red equivalent). Rather than editing
            // every page individually, detect that banner here, hide it,
            // and show its message as this centered modal instead — this
            // automatically covers every current and future page using
            // that convention. This script sits at the end of <body>, so
            // the banner (rendered earlier in the page) is already in the
            // DOM — no need to wait for DOMContentLoaded.
            (function () {
                var successBanner = document.querySelector('.border-green-200.bg-green-50.text-green-800');
                var errorBanner = document.querySelector('.border-red-200.bg-red-50.text-red-800');
                var banner = successBanner || errorBanner;
                if (!banner) return;

                var text = (banner.innerText || banner.textContent || '').trim();
                banner.style.display = 'none';
                if (text) showAlert(text, successBanner ? 'success' : 'error');

                // apply.php autosaves an in-progress application (text fields in
                // localStorage, chosen PDFs in IndexedDB) so refreshing never loses
                // it. Once the application has actually been submitted/updated —
                // signalled by this exact flash banner, which only appears right
                // after apply.php's success redirect — the draft has served its
                // purpose and is cleared so it doesn't linger or get mistakenly
                // reapplied to a later, unrelated application.
                if (successBanner && /application was (submitted|updated) successfully/i.test(text)) {
                    try {
                        Object.keys(localStorage)
                            .filter(function (k) { return k.indexOf('depedcares_apply_draft_') === 0; })
                            .forEach(function (k) { localStorage.removeItem(k); });
                    } catch (e) {}
                    try {
                        indexedDB.deleteDatabase('depedcares_apply_drafts');
                    } catch (e) {}
                }
            })();
        })();
    </script>
</body>
</html>
