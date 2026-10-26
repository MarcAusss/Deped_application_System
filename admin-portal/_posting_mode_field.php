<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * "Same posting or a new one?" choice for the Job Postings Manage modal and
 * the Closed Postings Repost modal (posting_mode, read by
 * job-position-publish.php). Expects $postingModeId in scope. Hidden until
 * adminPostingMode() is called for a position that already has a JP number.
 */
?>
<fieldset id="<?= admin_e($postingModeId) ?>" class="hidden sm:col-span-3">
    <legend class="mb-1.5 block text-sm font-bold text-slate-700">Is this the same posting or a new one? <span class="text-rose-500">*</span></legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 px-4 py-3 transition hover:bg-slate-50 has-[:checked]:border-admin-indigo has-[:checked]:bg-admin-indigoLight">
            <input type="radio" name="posting_mode" value="reopen" checked class="mt-1 h-4 w-4 accent-admin-indigo">
            <span>
                <span class="block text-sm font-bold text-slate-800">Re-open the same posting</span>
                <span class="block text-xs text-slate-500">Keeps <strong class="posting-mode-jp"></strong>. New and edited applications stay in the same table.</span>
            </span>
        </label>
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 px-4 py-3 transition hover:bg-slate-50 has-[:checked]:border-admin-indigo has-[:checked]:bg-admin-indigoLight">
            <input type="radio" name="posting_mode" value="new" class="mt-1 h-4 w-4 accent-admin-indigo">
            <span>
                <span class="block text-sm font-bold text-slate-800">New posting (new memo)</span>
                <span class="block text-xs text-slate-500">Gets a new JP number. Its applications go to a new table.</span>
            </span>
        </label>
    </div>
</fieldset>

<script>
    /**
     * Resets the choice for a newly selected position: shown only when it
     * already has a JP number, defaulting to Re-open. Choosing New posting
     * dates the posting today; switching back restores its original date.
     */
    window.adminPostingMode = window.adminPostingMode || function (fieldId, jpNumber, postedInput) {
        var field = document.getElementById(fieldId);
        if (!field) return;

        var originalPostedAt = postedInput.value;
        var today = '<?= date('Y-m-d') ?>';

        field.classList.toggle('hidden', !jpNumber);
        field.querySelectorAll('.posting-mode-jp').forEach(function (el) { el.textContent = jpNumber || ''; });
        field.querySelectorAll('input[name="posting_mode"]').forEach(function (radio) {
            radio.checked = radio.value === 'reopen';
            radio.onchange = function () {
                postedInput.value = radio.value === 'new' ? today : originalPostedAt;
            };
        });
    };
</script>
