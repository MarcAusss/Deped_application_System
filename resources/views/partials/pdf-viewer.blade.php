{{--
    Shared document viewer for the admin and evaluator panels.
    Any link inside an element with [data-pdf-preview] opens here instead of a new tab;
    the link's text (or data-pdf-title) is used as the heading.
    Ctrl/Cmd/Shift/middle-click still open the file in a new tab as usual.
--}}
<div
    id="pdf-viewer"
    role="dialog"
    aria-modal="true"
    aria-labelledby="pdf-viewer-title"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.7);padding:24px;"
>
    <div style="display:flex;flex-direction:column;height:100%;max-width:1200px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,.4);">
        <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:#1e3a8a;color:#fff;">
            <p id="pdf-viewer-title" style="flex:1;min-width:0;margin:0;font-weight:700;font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></p>

            <a
                id="pdf-viewer-newtab"
                href="#"
                target="_blank"
                rel="noopener"
                style="flex-shrink:0;color:#fff;font-size:13px;font-weight:600;text-decoration:underline;"
            >
                Open in new tab
            </a>

            <button
                type="button"
                id="pdf-viewer-close"
                aria-label="Close"
                style="flex-shrink:0;background:rgba(255,255,255,.15);border:0;border-radius:8px;color:#fff;cursor:pointer;font-size:14px;font-weight:700;padding:6px 12px;"
            >
                ✕ Close
            </button>
        </div>

        <iframe id="pdf-viewer-frame" title="Document preview" style="flex:1;width:100%;border:0;background:#f1f5f9;"></iframe>
    </div>
</div>

<script>
    (function () {
        const viewer = document.getElementById('pdf-viewer');
        const frame = document.getElementById('pdf-viewer-frame');
        const title = document.getElementById('pdf-viewer-title');
        const newTab = document.getElementById('pdf-viewer-newtab');

        function open(url, heading) {
            title.textContent = heading || 'Document';
            newTab.href = url;
            frame.src = url;
            viewer.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function close() {
            viewer.style.display = 'none';
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }

        // Capture phase, so this runs before Filament/Livewire handle the click.
        document.addEventListener('click', function (event) {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
                return;
            }

            const marker = event.target.closest('[data-pdf-preview]');
            const link = marker && (event.target.closest('a[href]') || marker.querySelector('a[href]'));

            if (! link) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            open(link.href, marker.dataset.pdfTitle || link.textContent.trim());
        }, true);

        document.getElementById('pdf-viewer-close').addEventListener('click', close);

        viewer.addEventListener('click', function (event) {
            if (event.target === viewer) {
                close();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && viewer.style.display !== 'none') {
                event.stopPropagation();
                close();
            }
        }, true);
    })();
</script>
