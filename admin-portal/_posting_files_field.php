<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * D.M Notice of Vacancy / CSC Publication of Vacancy uploads for the Job
 * Postings Manage modal and the Closed Postings Repost modal (read by
 * job-position-publish.php). Expects $postingFilesPrefix in scope. Call
 * adminPostingFiles.load(prefix, position) whenever a position is selected
 * to list its current files (each removable) and clear any new selection.
 */
$pf = $postingFilesPrefix;
$uploads = [
    'attachment' => ['label' => 'D.M Notice of Vacancy', 'name' => 'attachment_paths[]'],
    'csc' => ['label' => 'CSC Publication of Vacancy', 'name' => 'csc_publication_paths[]'],
];
?>
<?php foreach ($uploads as $key => $upload): ?>
    <div class="sm:col-span-3">
        <label for="<?= admin_e("{$pf}-{$key}-input") ?>" class="mb-1.5 block text-sm font-bold text-slate-700"><?= admin_e($upload['label']) ?></label>
        <ul id="<?= admin_e("{$pf}-existing-{$key}") ?>" class="mb-2 space-y-1"></ul>
        <div class="posting-dropzone relative rounded-lg border-2 border-dashed border-slate-300 px-4 py-6 text-center transition" data-preview="<?= admin_e("{$pf}-{$key}-selected") ?>">
            <input type="file" name="<?= admin_e($upload['name']) ?>" id="<?= admin_e("{$pf}-{$key}-input") ?>" multiple accept="application/pdf" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
            <p class="pointer-events-none text-sm text-slate-500">Drag &amp; Drop your files or <span class="font-bold text-admin-indigo underline">Browse</span></p>
        </div>
        <p class="mt-2 text-xs text-slate-500">Upload the official <?= admin_e($upload['label']) ?>. You can select or drag in multiple PDF files (or an entire folder of PDFs) at once. Applicants will be able to download each of these from the job listing.</p>
        <ul id="<?= admin_e("{$pf}-{$key}-selected") ?>" class="mt-2 space-y-1 text-sm text-slate-600"></ul>
    </div>
<?php endforeach; ?>

<script>
    window.adminPostingFiles = window.adminPostingFiles || (function () {
        var documentUrl = <?= json_encode(admin_url('document.php')) ?>;

        function renderExisting(listId, removeFieldName, paths) {
            var list = document.getElementById(listId);
            list.innerHTML = '';
            paths.forEach(function (path) {
                var li = document.createElement('li');
                li.className = 'file-remove-row flex items-center gap-2 text-sm';

                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = removeFieldName;
                checkbox.value = path;
                checkbox.className = 'file-remove-checkbox hidden';

                var link = document.createElement('a');
                link.href = documentUrl + '?path=' + encodeURIComponent(path);
                link.target = '_blank';
                link.className = 'file-remove-link text-admin-indigo underline';
                link.textContent = path.split('/').pop();

                var toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'file-remove-toggle font-bold text-rose-600 hover:underline';
                toggle.textContent = 'Remove';
                toggle.addEventListener('click', function () {
                    var marked = checkbox.checked;
                    checkbox.checked = !marked;
                    link.classList.toggle('line-through', !marked);
                    link.classList.toggle('text-slate-400', !marked);
                    link.classList.toggle('text-admin-indigo', marked);
                    toggle.textContent = marked ? 'Remove' : 'Undo';
                    toggle.classList.toggle('text-rose-600', marked);
                    toggle.classList.toggle('text-slate-500', !marked);
                });

                li.appendChild(checkbox);
                li.appendChild(link);
                li.appendChild(toggle);
                list.appendChild(li);
            });
        }

        function wire(dropzoneEl) {
            var input = dropzoneEl.querySelector('input[type="file"]');
            var preview = document.getElementById(dropzoneEl.dataset.preview);

            function renderPreview() {
                preview.innerHTML = '';
                Array.prototype.forEach.call(input.files, function (file) {
                    var li = document.createElement('li');
                    li.textContent = file.name;
                    preview.appendChild(li);
                });
            }

            input.addEventListener('change', renderPreview);

            ['dragover', 'dragleave', 'drop'].forEach(function (evtName) {
                dropzoneEl.addEventListener(evtName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzoneEl.classList.toggle('border-admin-indigo', evtName === 'dragover');
                    dropzoneEl.classList.toggle('bg-admin-indigoLight', evtName === 'dragover');
                });
            });

            dropzoneEl.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    renderPreview();
                }
            });
        }

        return {
            wireAll: function () {
                document.querySelectorAll('.posting-dropzone:not([data-wired])').forEach(function (zone) {
                    zone.setAttribute('data-wired', '1');
                    wire(zone);
                });
            },
            load: function (prefix, position) {
                renderExisting(prefix + '-existing-attachment', 'remove_attachment_paths[]', (position && position.attachment_paths) || []);
                renderExisting(prefix + '-existing-csc', 'remove_csc_publication_paths[]', (position && position.csc_publication_paths) || []);
                ['attachment', 'csc'].forEach(function (key) {
                    document.getElementById(prefix + '-' + key + '-input').value = '';
                    document.getElementById(prefix + '-' + key + '-selected').innerHTML = '';
                });
            }
        };
    })();
    adminPostingFiles.wireAll();
</script>
