<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * IER (Initial Evaluation Result) export controls for the Applications,
 * Qualified and Disqualified lists. Expects $ierScope ('applications'|
 * 'qualified'|'disqualified'), $ierCount, and optionally $ierStatus/$ierJob
 * (the page's current filters) in scope.
 *
 * Rows opt in with a checkbox carrying form="ier-selected-form" — the rows
 * already hold their own action forms, so the table can't sit inside this one.
 */
$ierStatus = $ierStatus ?? '';
$ierJob = (string) ($ierJob ?? '');
?>
<form method="POST" action="<?= admin_url('ier-preview.php') ?>" id="ier-selected-form" class="hidden">
    <?= admin_csrf_field() ?>
    <input type="hidden" name="source" value="selected">
</form>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <label class="flex items-center gap-2 text-sm font-bold text-slate-600">
        <input type="checkbox" id="ier-select-all" class="h-4 w-4 accent-admin-indigo" <?= $ierCount ? '' : 'disabled' ?>>
        Select all &middot; <?= (int) $ierCount ?> application(s)
    </label>

    <div class="flex flex-wrap items-center gap-2">
        <button type="submit" form="ier-selected-form" id="ier-export-selected" disabled class="inline-flex items-center gap-1.5 rounded-lg border-2 border-emerald-600 px-3 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-40">
            Preview &amp; Export Selected IER
        </button>

        <form method="POST" action="<?= admin_url('ier-preview.php') ?>">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="source" value="filtered">
            <input type="hidden" name="scope" value="<?= admin_e($ierScope) ?>">
            <input type="hidden" name="status" value="<?= admin_e($ierStatus) ?>">
            <input type="hidden" name="job" value="<?= admin_e($ierJob) ?>">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 6.75-3-3m3 3 3-3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                Preview &amp; Export IER
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        var selectAll = document.getElementById('ier-select-all');
        var exportBtn = document.getElementById('ier-export-selected');
        if (!selectAll || !exportBtn) return;

        function rowChecks() {
            return Array.prototype.slice.call(document.querySelectorAll('.ier-row-check'));
        }

        // Rows hidden by the page's search box are skipped by Select all
        // (rows inside a collapsed position group still count).
        function visible(check) {
            return !check.closest('.app-row.hidden, .position-group.hidden');
        }

        // Header checkbox of one position table: its row checkboxes (search-visible ones).
        function tableChecks(groupCheck) {
            return Array.prototype.slice.call(groupCheck.closest('table').querySelectorAll('.ier-row-check')).filter(visible);
        }

        function refresh() {
            exportBtn.disabled = !rowChecks().some(function (c) { return c.checked; });

            // Keep each table's header checkbox in step: checked when all of its
            // rows are, a dash when only some are.
            document.querySelectorAll('.ier-group-check').forEach(function (g) {
                var rows = tableChecks(g);
                var checked = rows.filter(function (c) { return c.checked; }).length;
                g.checked = rows.length > 0 && checked === rows.length;
                g.indeterminate = checked > 0 && checked < rows.length;
            });
        }

        selectAll.addEventListener('change', function () {
            rowChecks().forEach(function (c) { if (visible(c)) c.checked = selectAll.checked; });
            refresh();
        });
        document.addEventListener('change', function (e) {
            if (!e.target.classList) return;
            if (e.target.classList.contains('ier-group-check')) {
                tableChecks(e.target).forEach(function (c) { c.checked = e.target.checked; });
                refresh();
            } else if (e.target.classList.contains('ier-row-check')) {
                refresh();
            }
        });
    })();
</script>
