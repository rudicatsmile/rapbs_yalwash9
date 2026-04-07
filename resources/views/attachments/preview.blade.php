<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview Lampiran</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
        .page { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid rgba(148,163,184,.35); }
        .title { display: flex; flex-direction: column; min-width: 240px; flex: 1; }
        .title strong { font-size: 14px; line-height: 1.3; }
        .meta { font-size: 12px; opacity: .75; line-height: 1.4; }
        .controls { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .btn { border: 1px solid rgba(148,163,184,.45); background: transparent; color: inherit; padding: 8px 10px; border-radius: 10px; font-size: 12px; cursor: pointer; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-primary { border-color: rgba(59,130,246,.65); }
        .viewer-wrap { position: relative; flex: 1; background: rgba(2,6,23,.02); }
        .viewer-stage { position: absolute; inset: 0; overflow: auto; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .viewer-inner { transform-origin: center center; will-change: transform; max-width: 100%; }
        .viewer-inner iframe { width: min(1100px, 92vw); height: min(78vh, 900px); border: 0; background: white; border-radius: 12px; box-shadow: 0 12px 30px rgba(2,6,23,.12); }
        .viewer-inner img { max-width: min(1100px, 92vw); max-height: min(78vh, 900px); border-radius: 12px; box-shadow: 0 12px 30px rgba(2,6,23,.12); }
        .notice { padding: 16px; max-width: 900px; margin: 0 auto; font-size: 13px; line-height: 1.5; opacity: .9; }
        .error { background: rgba(239,68,68,.10); border: 1px solid rgba(239,68,68,.25); border-radius: 12px; padding: 12px 14px; }
        .muted { opacity: .75; }
        @media (max-width: 640px) {
            .viewer-inner iframe { width: 92vw; height: 72vh; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="title">
            <strong>{{ $media->name ?? $media->file_name ?? 'Lampiran' }}</strong>
            <div class="meta">
                <span class="muted">{{ $mime ?: 'unknown' }}</span>
                <span class="muted"> • </span>
                <span class="muted">{{ $media->created_at?->format('d-m-Y H:i') ?? '-' }}</span>
            </div>
        </div>

        <div class="controls">
            <button type="button" class="btn" id="zoomOut">Zoom -</button>
            <button type="button" class="btn" id="zoomIn">Zoom +</button>
            <button type="button" class="btn" id="rotateLeft">Rotate ⟲</button>
            <button type="button" class="btn" id="rotateRight">Rotate ⟳</button>
            <button type="button" class="btn" id="reset">Reset</button>
            <button type="button" class="btn btn-primary" id="fullscreen">Fullscreen</button>
            <a class="btn" href="{{ $fileUrl }}">Buka File</a>
        </div>
    </div>

    @if ($type === 'office' && ! $isLikelyPublic)
        <div class="notice">
            <div class="error">
                Preview dokumen Office membutuhkan URL aplikasi yang dapat diakses publik. Silakan gunakan tombol “Buka File” untuk melihat / mengunduh lampiran.
            </div>
        </div>
    @endif

    @if ($type === 'download')
        <div class="notice">
            <div class="error">
                Format file ini belum didukung untuk preview langsung. Silakan gunakan tombol “Buka File”.
            </div>
        </div>
    @endif

    <div class="viewer-wrap">
        <div class="viewer-stage" id="stage">
            <div class="viewer-inner" id="inner">
                @if ($type === 'image')
                    <img id="viewer" src="{{ $fileUrl }}" alt="Lampiran">
                @elseif ($type === 'pdf')
                    <iframe id="viewer" src="{{ $embedUrl }}" title="Preview PDF"></iframe>
                @elseif ($type === 'office' && $embedUrl)
                    <iframe id="viewer" src="{{ $embedUrl }}" title="Preview Office"></iframe>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const inner = document.getElementById('inner');
        const stage = document.getElementById('stage');
        const viewer = document.getElementById('viewer');
        const zoomIn = document.getElementById('zoomIn');
        const zoomOut = document.getElementById('zoomOut');
        const rotateLeft = document.getElementById('rotateLeft');
        const rotateRight = document.getElementById('rotateRight');
        const reset = document.getElementById('reset');
        const fullscreen = document.getElementById('fullscreen');

        let scale = 1;
        let rotation = 0;

        function applyTransform() {
            inner.style.transform = `scale(${scale}) rotate(${rotation}deg)`;
        }

        function clampScale(next) {
            return Math.max(0.25, Math.min(4, next));
        }

        function setDisabled() {
            const hasViewer = !!viewer;
            [zoomIn, zoomOut, rotateLeft, rotateRight, reset, fullscreen].forEach((btn) => {
                if (!btn) return;
                btn.disabled = !hasViewer;
            });
        }

        setDisabled();

        if (zoomIn) zoomIn.addEventListener('click', () => {
            scale = clampScale(scale + 0.15);
            applyTransform();
        });

        if (zoomOut) zoomOut.addEventListener('click', () => {
            scale = clampScale(scale - 0.15);
            applyTransform();
        });

        if (rotateLeft) rotateLeft.addEventListener('click', () => {
            rotation = (rotation - 90) % 360;
            applyTransform();
        });

        if (rotateRight) rotateRight.addEventListener('click', () => {
            rotation = (rotation + 90) % 360;
            applyTransform();
        });

        if (reset) reset.addEventListener('click', () => {
            scale = 1;
            rotation = 0;
            applyTransform();
        });

        if (fullscreen) fullscreen.addEventListener('click', async () => {
            const el = document.documentElement;
            if (!document.fullscreenElement) {
                await el.requestFullscreen?.();
            } else {
                await document.exitFullscreen?.();
            }
        });

        if (viewer && viewer.tagName === 'IMG') {
            viewer.addEventListener('error', () => {
                stage.innerHTML = '<div class="notice"><div class="error">Gagal memuat file. File mungkin rusak atau tidak tersedia.</div></div>';
            });
        }
    })();
</script>
</body>
</html>

