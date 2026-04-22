<?php
/**
 * Shared Quill rich-text editor partial
 *
 * Include ONCE per page. Requires $editorCfg array:
 *
 *   $editorCfg = [
 *       'textarea_name' => 'body',          // form field name that POSTs (required)
 *       'textarea_id'   => 'bodyEditor',    // id on the hidden <textarea>
 *       'quill_id'      => 'quillEditor',   // id on the Quill mount <div>
 *       'preview_id'    => 'previewFrame',  // id on the <iframe>
 *       'height'        => '400px',         // editor / iframe height
 *       'initial_html'  => '',              // pre-existing HTML content
 *       'instance_key'  => 'default',       // unique key when multiple editors on same page
 *   ];
 *
 * The partial emits the full 3-tab block (Visual / HTML Source / Preview)
 * plus the required <link> and <script> tags exactly once per instance_key.
 */

$_eq_name    = $editorCfg['textarea_name'] ?? 'body';
$_eq_tid     = $editorCfg['textarea_id']   ?? 'bodyEditor';
$_eq_qid     = $editorCfg['quill_id']      ?? 'quillEditor';
$_eq_pid     = $editorCfg['preview_id']    ?? 'previewFrame';
$_eq_h       = $editorCfg['height']        ?? '400px';
$_eq_html    = $editorCfg['initial_html']  ?? '';
$_eq_key     = $editorCfg['instance_key']  ?? 'default';

// Emit Quill CSS once globally (guarded by a static flag via a JS global)
?>
<link rel="stylesheet" href="../backoffice/app-assets/vendors/css/quill.snow.css">
<style>
/* ── Shared Quill editor panel ─────────────────────────────── */
.eq-tabs {
    display: flex;
    gap: 2px;
    margin-bottom: 0;
    border-bottom: 2px solid rgba(255,255,255,.08);
    padding: 0 1rem;
    background: rgba(255,255,255,.03);
}
.eq-tab-btn {
    padding: .55rem 1.1rem;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: rgba(255,255,255,.4);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    transition: color .15s, border-color .15s;
}
.eq-tab-btn.active {
    color: #b700e0;
    border-bottom-color: #b700e0;
}
.eq-tab-btn:hover:not(.active) { color: rgba(255,255,255,.75); }

.eq-panel { display: none; padding: 1rem; }
.eq-panel.active { display: block; }

/* Quill overrides for dark theme */
.ql-toolbar.ql-snow {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.1) !important;
    border-radius: 6px 6px 0 0;
}
.ql-container.ql-snow {
    border-color: rgba(255,255,255,.1) !important;
    border-radius: 0 0 6px 6px;
    background: rgba(255,255,255,.03);
}
.ql-editor {
    color: #e0e0e0;
    font-size: .93rem;
    line-height: 1.65;
}
.ql-editor.ql-blank::before { color: rgba(255,255,255,.3) !important; }
.ql-snow .ql-stroke { stroke: rgba(255,255,255,.6) !important; }
.ql-snow .ql-fill   { fill:   rgba(255,255,255,.6) !important; }
.ql-snow .ql-picker-label { color: rgba(255,255,255,.6) !important; }
.ql-snow .ql-picker-options {
    background: #1a1040 !important;
    border-color: rgba(255,255,255,.15) !important;
}
.ql-snow .ql-picker-item { color: rgba(255,255,255,.8) !important; }

.eq-source-ta {
    width: 100%;
    font-family: 'Fira Mono', 'Courier New', monospace;
    font-size: 12px;
    background: #0d0d1a;
    color: #c8e6c9;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 6px;
    padding: .75rem 1rem;
    resize: vertical;
    line-height: 1.6;
}
.eq-source-ta:focus { outline: none; border-color: rgba(183,0,224,.4); }

.eq-preview-frame {
    width: 100%;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 6px;
    background: #fff;
}
.eq-preview-hint {
    font-size: .75rem;
    color: rgba(255,255,255,.3);
    margin-top: .4rem;
    text-align: right;
}
</style>

<!-- ── Editor tab bar ──────────────────────────────────────── -->
<div class="eq-tabs" id="eqTabs_<?= $_eq_key ?>">
    <button type="button" class="eq-tab-btn active"
            onclick="eqSwitchTab('<?= $_eq_key ?>','visual',this)">
        <i class="ft-edit-2" style="margin-right:.3rem;"></i> Visual
    </button>
    <button type="button" class="eq-tab-btn"
            onclick="eqSwitchTab('<?= $_eq_key ?>','source',this)">
        <i class="ft-code" style="margin-right:.3rem;"></i> HTML Source
    </button>
    <button type="button" class="eq-tab-btn"
            onclick="eqSwitchTab('<?= $_eq_key ?>','preview',this)">
        <i class="ft-eye" style="margin-right:.3rem;"></i> Preview
    </button>
</div>

<!-- ── Visual (Quill) ────────────────────────────────────────── -->
<div class="eq-panel active" id="eqVisual_<?= $_eq_key ?>">
    <div id="<?= $_eq_qid ?>" style="height:<?= $_eq_h ?>;"></div>
</div>

<!-- ── HTML Source (textarea) ────────────────────────────────── -->
<div class="eq-panel" id="eqSource_<?= $_eq_key ?>">
    <textarea id="<?= $_eq_tid ?>"
              name="<?= htmlspecialchars($_eq_name) ?>"
              class="eq-source-ta"
              style="height:<?= $_eq_h ?>;"><?= htmlspecialchars($_eq_html) ?></textarea>
</div>

<!-- ── Preview (iframe) ──────────────────────────────────────── -->
<div class="eq-panel" id="eqPreview_<?= $_eq_key ?>">
    <iframe id="<?= $_eq_pid ?>"
            class="eq-preview-frame"
            style="height:<?= $_eq_h ?>; min-height:<?= $_eq_h ?>;"
            frameborder="0"></iframe>
    <p class="eq-preview-hint">
        <i class="ft-edit" style="margin-right:.2rem;"></i>
        Click inside the preview to edit text directly.
    </p>
</div>

<script src="../backoffice/app-assets/vendors/js/quill.min.js"></script>
<script>
(function () {
    /* ── Per-instance state ─────────────────────────────────────── */
    const KEY      = <?= json_encode($_eq_key) ?>;
    const QUILL_ID = <?= json_encode($_eq_qid) ?>;
    const TA_ID    = <?= json_encode($_eq_tid) ?>;
    const PRV_ID   = <?= json_encode($_eq_pid) ?>;
    const BASEURL  = <?= json_encode(isset($baseurl) ? $baseurl : '') ?>;

    /* ── Init Quill ──────────────────────────────────────────────── */
    const quill = new Quill('#' + QUILL_ID, {
        theme: 'snow',
        placeholder: 'Write your content here…',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ align: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['blockquote', 'code-block'],
                ['clean']
            ]
        }
    });

    /* ── Load initial content ───────────────────────────────────── */
    const initialHtml = document.getElementById(TA_ID).value;
    if (initialHtml.trim()) {
        quill.clipboard.dangerouslyPasteHTML(initialHtml);
    }

    /* ── Dirty-Tracking: Quill strippt <html>/<body>/<style>,
          daher nur bei echter User-Eingabe zurückschreiben ─────────── */
    let quillDirty = false;
    quill.on('text-change', function(_, __, source) {
        if (source === 'user') quillDirty = true;
    });

    /* ── Tab state ───────────────────────────────────────────────── */
    let activeTab = 'visual';

    window['eqInstance_' + KEY] = { quill, getHtml, syncBodyField };

    /* ── Get current HTML from active source ─────────────────────── */
    function getHtml() {
        if (activeTab === 'visual') {
            return quill.root.innerHTML;
        } else if (activeTab === 'source') {
            return document.getElementById(TA_ID).value;
        } else {
            // from preview iframe
            const frame = document.getElementById(PRV_ID);
            try {
                return frame.contentDocument.body.innerHTML;
            } catch(e) {
                return document.getElementById(TA_ID).value;
            }
        }
    }

    /* ── Sync Quill → textarea (call before form submit) ──────────── */
    function syncBodyField() {
        document.getElementById(TA_ID).value = getHtml();
    }

    /* ── Make preview editable ──────────────────────────────────── */
    function enablePreviewEditing() {
        const frame = document.getElementById(PRV_ID);
        try {
            frame.contentDocument.designMode = 'on';
        } catch(e) {}
    }

    /* ── Render preview iframe ───────────────────────────────────── */
    function renderPreview() {
        // NICHT getHtml() verwenden — activeTab ist bereits 'preview', dann würde aus dem
        // noch leeren iframe gelesen. Stattdessen direkt aus Textarea (wurde vor Tab-Switch synced).
        let html = document.getElementById(TA_ID).value || '';

        // Localize banner URL (prod → local) for correct image display in preview
        if (BASEURL) {
            const localBanner = BASEURL + '/backoffice/app-assets/img/banner/newleademailheader.jpg';
            html = html
                .replace(/https:\/\/www\.simple2success\.com\/backoffice\/app-assets\/img\/banner\/newleademailheader\.jpg/g, localBanner)
                .replace(/https:\/\/simple2success\.com\/backoffice\/app-assets\/img\/banner\/newleademailheader\.jpg/g, localBanner);
        }

        const frame = document.getElementById(PRV_ID);
        // If body already contains full HTML document, render directly (avoid double-wrap)
        const isFullDoc = /<html[\s>]/i.test(html) || /<body[\s>]/i.test(html);
        frame.onload = enablePreviewEditing;
        frame.srcdoc = isFullDoc
            ? html
            : ('<!DOCTYPE html><html><head>'
                + '<meta charset="utf-8">'
                + '<style>body{font-family:Arial,sans-serif;padding:16px;color:#333;line-height:1.6;}'
                + 'a{color:#b700e0;} img{max-width:100%;}</style>'
                + '</head><body>' + html + '</body></html>');
    }

    /* ── Sync preview iframe → textarea ─────────────────────────── */
    function syncFromPreview() {
        const frame = document.getElementById(PRV_ID);
        try {
            const body = frame.contentDocument.body;
            if (body) {
                document.getElementById(TA_ID).value = body.innerHTML;
            }
        } catch(e) {}
    }

    /* ── Tab switcher (global so onclick= can call it) ───────────── */
    window['eqSwitchTab'] = window['eqSwitchTab'] || function(key, tab, btn) {
        const inst = window['eqInstance_' + key];
        if (inst) inst._switchTab(tab, btn);
    };

    /* ── Instance tab switch ─────────────────────────────────────── */
    function _switchTab(tab, btn) {
        // Sync away from current tab first
        if (activeTab === 'visual') {
            // Nur schreiben wenn User tatsächlich editiert hat — sonst würde der
            // Original-HTML-Wrapper (<html>/<body>/<style>) zerstört.
            if (quillDirty) {
                document.getElementById(TA_ID).value = quill.root.innerHTML;
            }
        } else if (activeTab === 'source') {
            quill.clipboard.dangerouslyPasteHTML(document.getElementById(TA_ID).value);
            quillDirty = false;
        } else if (activeTab === 'preview') {
            syncFromPreview();
            quill.clipboard.dangerouslyPasteHTML(document.getElementById(TA_ID).value);
            quillDirty = false;
        }

        // Hide all panels
        ['visual','source','preview'].forEach(function(p) {
            document.getElementById('eqVisual_'   + KEY).classList.remove('active');
            document.getElementById('eqSource_'   + KEY).classList.remove('active');
            document.getElementById('eqPreview_'  + KEY).classList.remove('active');
        });

        // Show target panel
        const panelMap = { visual: 'eqVisual_', source: 'eqSource_', preview: 'eqPreview_' };
        document.getElementById(panelMap[tab] + KEY).classList.add('active');

        // Update tab buttons
        document.querySelectorAll('#eqTabs_' + KEY + ' .eq-tab-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');

        activeTab = tab;

        // Render preview when switching to it
        if (tab === 'preview') renderPreview();
    }

    // Expose for external use
    window['eqInstance_' + KEY]._switchTab = _switchTab;

    /* ── Auto-sync textarea before any form submit on this page ──── */
    document.addEventListener('submit', function() {
        // Wenn User nicht in Quill editiert hat und gerade 'visual' aktiv ist,
        // NICHT überschreiben — sonst wird Original-HTML zerstört.
        if (activeTab === 'visual' && !quillDirty) return;
        document.getElementById(TA_ID).value = getHtml();
    }, true);

})();
</script>
