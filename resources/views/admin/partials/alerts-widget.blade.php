<div id="alerts-widget" data-url="{{ url('alerts') }}"></div>

<div class="modal fade" id="alertsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bell-fill text-danger"></i> Centro de Alertas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-2" id="alertsTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="tab-active" href="#pane-active" data-toggle="pill">Activas <span class="badge badge-danger" id="tab-active-count">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" id="tab-history" href="#pane-history" data-toggle="pill">Historial</a></li>
                    <li class="nav-item" id="tab-config-li" style="display:none"><a class="nav-link" id="tab-config" href="#pane-config" data-toggle="pill"><i class="bi bi-gear"></i> Configuración</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active p-3" id="pane-active"></div>
                    <div class="tab-pane fade p-3" id="pane-history"></div>
                    <div class="tab-pane fade p-3" id="pane-config"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="alerts-mute-btn"></button>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary mr-2" id="alerts-ack-all"><i class="bi bi-check2-all"></i> Reconocer todas</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="alert-pill" style="display:none">
    <div class="ap-body">
        <div class="ap-icon"></div>
        <div class="ap-text">
            <div class="ap-title"></div>
            <div class="ap-msg"></div>
        </div>
        <div class="ap-actions">
            <button type="button" class="ap-btn ap-ack" title="Reconocer"><i class="bi bi-check2"></i></button>
            <button type="button" class="ap-btn ap-close" title="Ocultar"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
</div>

<style>
    #alerts-bell { font-size: 1.1rem; }
    .alerts-badge {
        position: absolute; top: 2px; right: 0;
        min-width: 17px; height: 17px; padding: 0 4px;
        border-radius: 9px; background: #ef4444; color: #fff;
        font-size: 10.5px; line-height: 17px; text-align: center;
        font-weight: 700; box-shadow: 0 0 6px rgba(239,68,68,.7);
    }
    @keyframes alertsPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.25)} }
    .alerts-badge.pulsing { animation: alertsPulse 1s ease-in-out infinite; }
    #alertsModal .nav-tabs .nav-link { color:#94a3b8; border:none; border-bottom:2px solid transparent; background:transparent; padding:.5rem .9rem; }
    #alertsModal .nav-tabs .nav-link.active { color:#e2e8f0; border-bottom-color:#60a5fa; background:transparent; }
    .alert-row {
        display:flex; align-items:flex-start; gap:10px;
        background:#111b30; border:1px solid #1f2c47; border-left-width:4px;
        border-radius:8px; padding:10px 12px; margin-bottom:8px;
    }
    .alert-row.sev-critical { border-left-color:#ef4444; }
    .alert-row.sev-warning { border-left-color:#f59e0b; }
    .alert-row.sev-info { border-left-color:#60a5fa; }
    .alert-row .ar-icon { font-size:1.25rem; margin-top:2px; }
    .sev-critical .ar-icon, .ar-val.crit { color:#ef4444; }
    .sev-warning .ar-icon, .ar-val.warn { color:#f59e0b; }
    .sev-info .ar-icon { color:#60a5fa; }
    .alert-row .ar-body { flex:1; min-width:0; }
    .alert-row .ar-title { color:#e2e8f0; font-weight:600; font-size:.92rem; }
    .alert-row .ar-msg { color:#94a3b8; font-size:.82rem; margin-top:2px; word-wrap:break-word; }
    .alert-row .ar-time { color:#54607a; font-size:.72rem; margin-top:4px; display:flex; gap:12px; flex-wrap:wrap; }
    .alert-row.ar-ackd { opacity:.55; }
    .ar-ack-btn { align-self:center; white-space:nowrap; }
    #alert-pill {
        position:fixed; right:18px; bottom:18px; z-index:10500;
        max-width:380px; width:calc(100% - 36px);
    }
    #alert-pill .ap-body {
        display:flex; align-items:center; gap:10px;
        background:#111b30; border:1px solid #26365a; border-radius:12px;
        box-shadow:0 10px 32px rgba(0,0,0,.55); padding:11px 13px;
    }
    #alert-pill.sev-critical .ap-body { border-color:#7f1d1d; box-shadow:0 10px 32px rgba(239,68,68,.28); }
    #alert-pill.sev-warning .ap-body { border-color:#78350f; box-shadow:0 10px 32px rgba(245,158,11,.22); }
    #alert-pill .ap-icon { font-size:1.45rem; }
    #alert-pill.sev-critical .ap-icon { color:#ef4444; }
    #alert-pill.sev-warning .ap-icon { color:#f59e0b; }
    #alert-pill.sev-info .ap-icon { color:#60a5fa; }
    #alert-pill .ap-text { flex:1; min-width:0; cursor:pointer; }
    #alert-pill .ap-title { color:#e2e8f0; font-weight:700; font-size:.85rem; }
    #alert-pill .ap-msg { color:#94a3b8; font-size:.76rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    #alert-pill .ap-actions { display:flex; gap:6px; }
    #alert-pill .ap-btn {
        width:28px; height:28px; border-radius:8px; border:1px solid #26365a;
        background:#0e1729; color:#94a3b8; font-size:.85rem; line-height:1;
    }
    #alert-pill .ap-btn:hover { color:#fff; border-color:#60a5fa; }
    .cfg-table { width:100%; color:#cbd5e1; font-size:.83rem; }
    .cfg-table th { color:#7c8db5; font-weight:600; font-size:.74rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #1f2c47; padding:6px 8px; }
    .cfg-table td { padding:9px 8px; border-bottom:1px solid #16203a; vertical-align:middle; }
    .cfg-input {
        width:64px; background:#0b1220; border:1px solid #26365a; color:#e2e8f0;
        border-radius:6px; padding:4px 7px; font-size:.82rem;
    }
    .cfg-sound-select { background:#0b1220; border:1px solid #26365a; color:#e2e8f0; border-radius:6px; padding:4px 7px; font-size:.78rem; max-width:170px; }
    .cfg-switch { position:relative; width:38px; height:20px; appearance:none; background:#334155; border-radius:10px; outline:none; cursor:pointer; transition:.2s; }
    .cfg-switch:checked { background:#059669; }
    .cfg-switch::before { content:''; position:absolute; top:2px; left:2px; width:16px; height:16px; border-radius:50%; background:#fff; transition:.2s; }
    .cfg-switch:checked::before { left:20px; }
    .cfg-sev-badge { font-size:.68rem; padding:2px 8px; border-radius:999px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
    .cfg-sev-badge.critical { background:rgba(239,68,68,.15); color:#f87171; }
    .cfg-sev-badge.warning { background:rgba(245,158,11,.15); color:#fbbf24; }
    .cfg-sev-badge.info { background:rgba(96,165,250,.15); color:#93c5fd; }
    .cfg-upload-label { font-size:.75rem; color:#60a5fa; cursor:pointer; }
    .cfg-upload-label:hover { text-decoration:underline; }
</style>

<script>
(function () {
    if (window.__dashAlertsInit) return;
    window.__dashAlertsInit = true;

    function boot() {

    var base = (document.getElementById('alerts-widget') || {}).dataset?.url || '/alerts';
    var POLL_MS = 15000;
    var LS_MUTE = 'dash-alerts-muted';
    var LS_SEEN = 'dash-alerts-last-seen';
    var playedIds = {};

    var stateData = null;
    var soundsCache = [];
    var muted = localStorage.getItem(LS_MUTE) === '1';
    var pillDismissedFor = parseInt(localStorage.getItem(LS_SEEN) || '0', 10);

    function fmtTime(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+' '+('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
    }

    function ago(iso) {
        var s = Math.floor((Date.now() - new Date(iso)) / 1000);
        if (s < 60) return 'hace '+s+'s';
        if (s < 3600) return 'hace '+Math.floor(s/60)+' min';
        if (s < 86400) return 'hace '+Math.floor(s/3600)+' h';
        return 'hace '+Math.floor(s/86400)+' d';
    }

    function sevIcon(sev) { return sev === 'critical' ? 'bi-exclamation-octagon-fill' : sev === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill'; }

    function beep(severity) {
        try {
            var ctx = beep._ctx || (beep._ctx = new (window.AudioContext || window.webkitAudioContext)());
            var seq = severity === 'critical' ? [[0,880],[180,880],[360,880]] : [[0,620],[200,620]];
            seq.forEach(function (t) {
                var o = ctx.createOscillator(), g = ctx.createGain();
                o.type = 'square'; o.frequency.value = t[1];
                g.gain.setValueAtTime(0.08, ctx.currentTime + t[0]/1000);
                g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t[0]/1000 + 0.14);
                o.connect(g); g.connect(ctx.destination);
                o.start(ctx.currentTime + t[0]/1000); o.stop(ctx.currentTime + t[0]/1000 + 0.15);
            });
        } catch (e) {}
    }

    function playSound(rule, eventId) {
        if (muted) return;
        if (eventId != null && playedIds[eventId]) return;
        if (eventId != null) { playedIds[eventId] = true; }
        if (rule && rule.sound_path) {
            try { var a = new Audio('/'+rule.sound_path.replace(/^\/+/, '')); a.volume = 0.9; a.play().catch(function(){ beep(rule.severity); }); return; } catch (e) {}
        }
        beep(rule ? rule.severity : 'warning');
    }

    function updateMuteBtn() {
        document.getElementById('alerts-mute-btn').innerHTML = muted
            ? '<i class="bi bi-volume-mute-fill text-danger"></i> Silenciado'
            : '<i class="bi bi-volume-up-fill text-success"></i> Sonido activo';
    }

    function renderEvents(el, events, showAck) {
        el.innerHTML = '';
        if (!events.length) {
            el.innerHTML = '<div class="text-center py-4" style="color:#54607a"><i class="bi bi-check-circle" style="font-size:2rem"></i><div class="mt-2">Sin alertas</div></div>';
            return;
        }
        events.forEach(function (ev) {
            var row = document.createElement('div');
            row.className = 'alert-row sev-' + ev.severity + (ev.acknowledged_at ? ' ar-ackd' : '');
            var val = ev.value_pct != null ? ' — valor actual: <b>' + parseFloat(ev.value_pct).toFixed(1).replace('.0','') + '%</b>' : '';
            row.innerHTML =
                '<div class="ar-icon"><i class="bi ' + sevIcon(ev.severity) + '"></i></div>' +
                '<div class="ar-body">' +
                    '<div class="ar-title">' + ev.label + '</div>' +
                    '<div class="ar-msg">' + (ev.message ? esc(ev.message) : '') + val + '</div>' +
                    '<div class="ar-time"><span><i class="bi bi-clock"></i> ' + fmtTime(ev.triggered_at) + '</span><span>' + ago(ev.triggered_at) + '</span>' + (ev.acknowledged_at ? '<span><i class="bi bi-check2-all"></i> reconocida</span>' : '') + '</div>' +
                '</div>' +
                (showAck && !ev.acknowledged_at ? '<button type="button" class="btn btn-sm btn-outline-success ar-ack-btn" data-id="' + ev.id + '" title="Reconocer"><i class="bi bi-check2"></i></button>' : '');
            el.appendChild(row);
        });
        if (showAck) {
            el.querySelectorAll('.ar-ack-btn').forEach(function (b) {
                b.addEventListener('click', function () { ackEvent(b.dataset.id); });
            });
        }
    }

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function ackEvent(id) {
        jsonFetch(base + '/ack', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ id: parseInt(id, 10) })
        }).then(refreshNow).catch(function () {});
    }

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : (window.Laravel && window.Laravel.csrfToken) || '';
    }

    function jsonFetch(url, opts) {
        opts = opts || {};
        opts.headers = Object.assign({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, opts.headers || {});
        return fetch(url, opts).then(function (r) {
            return r.text().then(function (t) {
                var j = null;
                try { j = t ? JSON.parse(t) : null; } catch (e) {}
                if (j === null) throw new Error('El servidor devolvió una respuesta inválida (HTTP ' + r.status + ')');
                return j;
            });
        });
    }

    function refreshNow() { poll(); renderTabs(); }

    function renderTabs() {
        if (!stateData) return;
        renderEvents(document.getElementById('pane-active'), stateData.active, true);
        renderEvents(document.getElementById('pane-history'), stateData.history, false);
        document.getElementById('tab-active-count').textContent = stateData.active_count;

        var badge = document.getElementById('alerts-badge');
        if (stateData.active_count > 0) {
            badge.textContent = stateData.active_count > 99 ? '99+' : stateData.active_count;
            badge.classList.remove('d-none');
            badge.classList.toggle('pulsing', stateData.active.some(function (e) { return e.severity === 'critical'; }));
        } else {
            badge.classList.add('d-none');
        }
    }

    function updatePill() {
        var pill = document.getElementById('alert-pill');
        if (!stateData || !stateData.active.length) { pill.style.display = 'none'; return; }
        var top = stateData.active[0];
        if (top.id <= pillDismissedFor) { pill.style.display = 'none'; return; }
        pill.className = 'sev-' + top.severity;
        pill.querySelector('.ap-icon').innerHTML = '<i class="bi ' + sevIcon(top.severity) + '"></i>';
        pill.querySelector('.ap-title').textContent = top.label;
        pill.querySelector('.ap-msg').textContent = top.message || '';
        pill.dataset.eventId = top.id;
        pill.style.display = 'block';
    }

    function poll() {
        if (document.hidden) return;
        jsonFetch(base + '/state', { credentials: 'same-origin' })
            .then(function (j) {
                stateData = j;
                var seenId = parseInt(localStorage.getItem(LS_SEEN) || '0', 10);
                var fresh = j.active.filter(function (e) { return e.id > seenId; });
                if (fresh.length) playSound(fresh[0], fresh[0].id);
                renderTabs();
                updatePill();
            })
            .catch(function () {});
    }

    function renderConfig() {
        if (!stateData || !stateData.can_configure) return;
        var pane = document.getElementById('pane-config');
        pane.innerHTML = '';

        var table = document.createElement('table');
        table.className = 'cfg-table';
        table.innerHTML = '<thead><tr><th>Métrica</th><th>Umbral %</th><th>Activa</th><th>Enfriamiento</th><th>Sonido</th><th></th><th></th></tr></thead>';
        var tbody = document.createElement('tbody');

        stateData.rules.forEach(function (rule) {
            if (rule.comparator === 'event') return;
            var tr = document.createElement('tr');
            tr.dataset.ruleId = rule.id;

            var sevBadge = '<span class="cfg-sev-badge ' + rule.severity + '">' + rule.severity + '</span>';
            var compTxt = rule.comparator === 'lt' ? '&lt;' : '&gt;';
            tr.innerHTML =
                '<td><div style="color:#e2e8f0;font-weight:600">' + rule.label + '</div><div class="mt-1">' + sevBadge + '</div></td>' +
                '<td><span style="color:#7c8db5;margin-right:3px">' + compTxt + '</span><input type="number" class="cfg-input cfg-threshold" min="0" max="100" value="' + (rule.threshold_pct ?? '') + '"></td>' +
                '<td><input type="checkbox" class="cfg-switch cfg-enabled"' + (rule.enabled ? ' checked' : '') + '></td>' +
                '<td><input type="number" class="cfg-input cfg-cooldown" min="1" max="240" value="' + rule.cooldown_min + '"> min</td>' +
                '<td><select class="cfg-sound-select cfg-sound"><option value="">— Sin sonido —</option></select></td>' +
                '<td><label class="cfg-upload-label" title="Subir audio (mp3/wav/ogg máx 2MB)"><i class="bi bi-upload"></i> subir<input type="file" accept=".mp3,.wav,.ogg,audio/*" class="cfg-file" hidden></label></td>' +
                '<td style="white-space:nowrap"><button type="button" class="btn btn-sm btn-outline-primary cfg-save mr-1" title="Guardar"><i class="bi bi-check-lg"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-warning cfg-test mr-1" title="Probar sonido"><i class="bi bi-play-fill"></i></button></td>';

            var sel = tr.querySelector('.cfg-sound');
            sel.value = rule.sound_path || '';
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        pane.appendChild(table);

        fillSoundSelects();

        pane.insertAdjacentHTML('beforeend',
            '<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap" style="gap:8px">' +
            '<div style="color:#54607a;font-size:.75rem">El evaluador corre cada 15 s en cada sesión abierta; el enfriamiento evita alertas repetidas.</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" id="alerts-simulate-btn"><i class="bi bi-broadcast"></i> Simular alerta</button></div>');

        pane.querySelectorAll('.cfg-file').forEach(function (inp) {
            inp.addEventListener('change', function () { uploadSound(inp, inp.closest('tr')); });
        });
        pane.querySelectorAll('.cfg-save').forEach(function (b) {
            b.addEventListener('click', function () { saveRule(b.closest('tr')); });
        });
        pane.querySelectorAll('.cfg-enabled').forEach(function (sw) {
            sw.addEventListener('change', function () {
                sw.disabled = true;
                saveRule(sw.closest('tr')).finally(function () { sw.disabled = false; });
            });
        });
        pane.querySelectorAll('.cfg-test').forEach(function (b) {
            b.addEventListener('click', function () {
                var path = b.closest('tr').querySelector('.cfg-sound').value;
                playSound({ sound_path: path, severity: b.closest('tr').querySelector('.cfg-sev-badge').textContent.trim() }, null);
            });
        });

        var simBtn = pane.querySelector('#alerts-simulate-btn');
        simBtn.addEventListener('click', function () {
            simBtn.disabled = true;
            jsonFetch(base + '/simulate', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({})
            }).then(function () { setTimeout(refreshNow, 400); })
              .catch(function () {})
              .finally(function () { simBtn.disabled = false; });
        });
    }

    function fillSoundSelects() {
        document.querySelectorAll('#pane-config .cfg-sound').forEach(function (sel) {
            var cur = sel.value;
            sel.querySelectorAll('option:not(:first-child)').forEach(function (o) { o.remove(); });
            soundsCache.forEach(function (p) {
                var o = document.createElement('option'); o.value = p; o.textContent = p.split('/').pop(); sel.appendChild(o);
            });
            sel.value = cur;
        });
    }

    function loadSounds(force) {
        if (soundsCache.length && !force) { fillSoundSelects(); return Promise.resolve(); }
        return jsonFetch(base + '/sounds').then(function (j) {
            soundsCache = j.sounds || [];
            fillSoundSelects();
        });
    }

    function uploadSound(input, tr) {
        var file = input.files && input.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('_method', '');
        fd.append('sound', file);
        fd.append('_token', csrfToken());
        var label = input.parentElement;
        label.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> subiendo...';
        jsonFetch(base + '/sounds', { method: 'POST', body: fd })
            .then(function (j) { if (!j.success) throw j; return j; })
            .then(function (j) { loadSounds(true); tr.querySelector('.cfg-sound').value = j.path; saveRule(tr, j.path); })
            .catch(function (e) {
                var msg = (e && e.errors && e.errors.sound && e.errors.sound.join('\n')) || (e && e.message) || 'Error al subir el archivo';
                alert(msg);
            })
            .finally(function () { label.innerHTML = '<i class="bi bi-upload"></i> subir<input type="file" accept=".mp3,.wav,.ogg,audio/*" class="cfg-file" hidden>'; bindFile(label.querySelector('.cfg-file')); });
    }

    function bindFile(inp) {
        inp.addEventListener('change', function () { uploadSound(inp, inp.closest('tr')); });
    }

    function saveRule(tr, forcedPath) {
        var id = parseInt(tr.dataset.ruleId, 10);
        var payload = {
            threshold_pct: tr.querySelector('.cfg-threshold').value === '' ? null : parseInt(tr.querySelector('.cfg-threshold').value, 10),
            enabled: tr.querySelector('.cfg-enabled').checked,
            cooldown_min: parseInt(tr.querySelector('.cfg-cooldown').value, 10) || 15,
            sound_path: forcedPath !== undefined ? forcedPath : tr.querySelector('.cfg-sound').value
        };
        jsonFetch(base + '/rules/' + id, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify(payload)
        }).then(function (j) { if (stateData && j.rule) { stateData.rules = stateData.rules.map(function (r) { return r.id === j.rule.id ? j.rule : r; }); } })
          .catch(function () {});
    }

    document.getElementById('alerts-bell').addEventListener('click', function (ev) {
        ev.preventDefault();
        $('#alertsModal').modal('show');
        loadSounds(false).then(function () { renderConfig(); });
        renderTabs();
    });

    document.getElementById('alerts-mute-btn').addEventListener('click', function () {
        muted = !muted;
        localStorage.setItem(LS_MUTE, muted ? '1' : '0');
        updateMuteBtn();
    });

    document.getElementById('alerts-ack-all').addEventListener('click', function () {
        jsonFetch(base + '/ack', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ all: true })
        }).then(refreshNow).catch(function () {});
    });

    document.querySelector('#alert-pill .ap-ack').addEventListener('click', function () {
        var id = document.getElementById('alert-pill').dataset.eventId;
        if (id) ackEvent(id);
        document.getElementById('alert-pill').style.display = 'none';
    });
    document.querySelector('#alert-pill .ap-close').addEventListener('click', function () {
        var pill = document.getElementById('alert-pill');
        pillDismissedFor = parseInt(pill.dataset.eventId || '0', 10);
        localStorage.setItem(LS_SEEN, String(pillDismissedFor));
        pill.style.display = 'none';
    });
    document.querySelector('#alert-pill .ap-text').addEventListener('click', function () {
        $('#alertsModal').modal('show');
        loadSounds(false).then(function () { renderConfig(); });
        renderTabs();
    });

    if (stateData === null) poll();
    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    updateMuteBtn();

        var modalEl = document.getElementById('alertsModal');

        function closeModal() {
            try { $(modalEl).modal('hide'); } catch (e) {}
            setTimeout(function () {
                if (!modalEl.classList.contains('show')) return;
                modalEl.classList.remove('show', 'fade');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.parentNode.removeChild(b); });
                try { $(modalEl).removeData('bs.modal'); } catch (e) {}
            }, 450);
        }

        modalEl.querySelectorAll('[data-dismiss="modal"]').forEach(function (b) {
            b.addEventListener('click', function (ev) { ev.preventDefault(); closeModal(); });
        });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closeModal(); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
