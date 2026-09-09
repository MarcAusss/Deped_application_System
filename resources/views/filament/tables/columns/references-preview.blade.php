@php
    $paths = $paths ?? [];
@endphp

@if(empty($paths))
    <div style="padding:24px;text-align:center;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
        No reference documents have been uploaded yet.
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:20px;">
        @foreach($paths as $path)
            <div>
                <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#123B6D;word-break:break-all;">
                    {{ basename($path) }}
                    <a href="{{ route('public-file', $path) }}" target="_blank" rel="noopener" style="margin-left:8px;font-size:12px;font-weight:600;color:#1D4E89;text-decoration:underline;">
                        Open in new tab
                    </a>
                </p>

                <iframe
                    src="{{ route('public-file', $path) }}"
                    style="width:100%;height:65vh;border:1px solid #e2e8f0;border-radius:12px;"
                ></iframe>
            </div>
        @endforeach
    </div>
@endif
