@extends('adminlte::page')

@section('title', 'Centro de Alertas')

@section('content_header')
    <h1><i class="far fa-bell text-danger"></i> Centro de Alertas <small class="text-muted" style="font-size:.6em">reglas, sonidos y eventos</small></h1>
@stop

@section('css')
    <style>
        .al-card { background:#0e1729; border:1px solid #1f2c47; border-radius:12px; margin-bottom:14px; }
        .al-card .card-header { background:transparent; border-bottom:1px solid #1f2c47; color:#e2e8f0; font-weight:700; padding:.85rem 1.1rem; }
        .stat-mini { background:#0e1729; border:1px solid #1f2c47; border-radius:12px; padding:14px 18px; display:flex; align-items:center; gap:14px; margin-bottom:14px; }
        .stat-mini .sm-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
        .stat-mini .sm-val { font-size:1.5rem; font-weight:800; color:#e2e8f0; line-height:1; }
        .stat-mini .sm-lbl { font-size:.75rem; color:#7c8db5; text-transform:uppercase; letter-spacing:.05em; margin-top:3px; }
        .alert-row {
            display:flex; align-items:flex-start; gap:10px;
            background:#111b30; border:1px solid #1f2c47; border-left-width:4px;
            border-radius:8px; padding:10px 12px; margin-bottom:8px;
        }
        .alert-row.sev-critical { border-left-color:#ef4444; }
        .alert-row.sev-warning { border-left-color:#f59e0b; }
        .alert-row.sev-info { border-left-color:#60a5fa; }
        .alert-row .ar-icon { font-size:1.25rem; margin-top:2px; }
        .sev-critical .ar-icon { color:#ef4444; }
        .sev-warning .ar-icon { color:#f59e0b; }
        .sev-info .ar-icon { color:#60a5fa; }
        .alert-row .ar-body { flex:1; min-width:0; }
        .alert-row .ar-title { color:#e2e8f0; font-weight:600; font-size:.92rem; }
        .alert-row .ar-msg { color:#94a3b8; font-size:.82rem; margin-top:2px; word-wrap:break-word; }
        .alert-row .ar-time { color:#54607a; font-size:.72rem; margin-top:4px; display:flex; gap:12px; flex-wrap:wrap; }
        .alert-row.ar-ackd { opacity:.55; }
        .cfg-table { width:100%; color:#cbd5e1; font-size:.83rem; min-width:820px; }
        .cfg-table th { color:#7c8db5; font-weight:600; font-size:.74rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #1f2c47; padding:6px 8px; white-space:nowrap; }
        .cfg-table td { padding:10px 8px; border-bottom:1px solid #16203a; vertical-align:middle; }
        .cfg-input { width:64px; background:#0b1220; border:1px solid #26365a; color:#e2e8f0; border-radius:6px; padding:5px 7px; font-size:.84rem; }
        .cfg-sound-select { background:#0b1220; border:1px solid #26365a; color:#e2e8f0; border-radius:6px; padding:5px 7px; font-size:.78rem; max-width:190px; }
        .cfg-switch { position:relative; width:38px; height:20px; appearance:none; -webkit-appearance:none; background:#334155; border-radius:10px; outline:none; cursor:pointer; transition:.2s; }
        .cfg-switch:checked { background:#059669; }
        .cfg-switch::before { content:''; position:absolute; top:2px; left:2px; width:16px; height:16px; border-radius:50%; background:#fff; transition:.2s; }
        .cfg-switch:checked::before { left:20px; }
        .cfg-sev-badge { font-size:.68rem; padding:2px 8px; border-radius:999px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .cfg-sev-badge.critical { background:rgba(239,68,68,.15); color:#f87171; }
        .cfg-sev-badge.warning { background:rgba(245,158,11,.15); color:#fbbf24; }
        .cfg-sev-badge.info { background:rgba(96,165,250,.15); color:#93c5fd; }
        .cfg-upload-label { font-size:.78rem; color:#60a5fa; cursor:pointer; white-space:nowrap; }
        .cfg-upload-label:hover { text-decoration:underline; }
        @keyframes alertsPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.25)} }
        .alerts-badge.pulsing { animation: alertsPulse 1s ease-in-out infinite; }
        .table-wrap { overflow-x:auto; }
    </style>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="stat-mini">
                <div class="sm-icon" style="background:rgba(239,68,68,.14);color:#ef4444"><i class="bi bi-exclamation-octagon-fill"></i></div>
                <div><div class="sm-val" id="stat-active">0</div><div class="sm-lbl">Alertas activas</div></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="stat-mini">
                <div class="sm-icon" style="background:rgba(5,150,105,.14);color:#10b981"><i class="bi bi-sliders"></i></div>
                <div><div class="sm-val" id="stat-rules">{{ $rules->where('enabled', true)->count() }}/{{ $rules->count() }}</div><div class="sm-lbl">Reglas activas</div></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="stat-mini">
                <div class="sm-icon" style="background:rgba(245,158,11,.14);color:#f59e0b"><i class="bi bi-clock-history"></i></div>
                <div><div class="sm-val" id="stat-history">{{ $eventsHistory->count() }}</div><div class="sm-lbl">Eventos recientes</div></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="stat-mini">
                <div class="sm-icon" style="background:rgba(96,165,250,.14);color:#60a5fa"><i class="bi bi-volume-up-fill"></i></div>
                <div><div class="sm-val" id="stat-sounds">—</div><div class="sm-lbl">Audios disponibles</div></div>
            </div>
        </div>
    </div>

    @if($canConfigure)
    <div class="al-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px">
            <span><i class="bi bi-sliders"></i> Reglas y sonidos</span>
            <button type="button" class="btn btn-sm btn-outline-danger" id="alerts-simulate-btn"><i class="bi bi-broadcast"></i> Simular alerta</button>
        </div>
        <div class="card-body p-2">
            <div class="table-wrap">
                <table class="cfg-table">
                    <thead><tr><th>Métrica</th><th>Umbral %</th><th>Activa</th><th>Enfriamiento</th><th>Sonido</th><th>Subir audio</th><th>Acciones</th></tr></thead>
                    <tbody id="rules-tbody">
                        @foreach($rules as $rule)
                            @continue($rule->comparator === 'event')
                            <tr data-rule-id="{{ $rule->id }}">
                                <td>
                                    <div style="color:#e2e8f0;font-weight:600">{{ $rule->label }}</div>
                                    <div class="mt-1"><span class="cfg-sev-badge {{ $rule->severity }}">{{ $rule->severity }}</span></div>
                                </td>
                                <td><span style="color:#7c8db5;margin-right:3px">{{ $rule->comparator === 'lt' ? '<' : '>' }}</span>
                                    <input type="number" class="cfg-input cfg-threshold" min="0" max="100" value="{{ $rule->threshold_pct }}"></td>
                                <td><input type="checkbox" class="cfg-switch cfg-enabled"{{ $rule->enabled ? ' checked' : '' }}></td>
                                <td><input type="number" class="cfg-input cfg-cooldown" min="1" max="240" value="{{ $rule->cooldown_min }}"> min</td>
                                <td>
                                    <select class="cfg-sound-select cfg-sound" data-current="{{ $rule->sound_path }}">
                                        <option value="">— Sin sonido —</option>
                                    </select>
                                </td>
                                <td><label class="cfg-upload-label" title="mp3/wav/ogg máx 2MB"><i class="bi bi-upload"></i> subir<input type="file" accept=".mp3,.wav,.ogg,audio/*" class="cfg-file" hidden></label></td>
                                <td style="white-space:nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary cfg-save" title="Guardar cambios"><i class="bi bi-check-lg"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-warning cfg-test" title="Probar sonido"><i class="bi bi-play-fill"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary cfg-sim" title="Simular esta alerta"><i class="bi bi-broadcast"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-2" style="color:#54607a;font-size:.78rem">
                El evaluador corre cada 15 s en cada sesión abierta. El enfriamiento evita alertas repetidas dentro del periodo indicado.
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="al-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-exclamation-circle-fill text-danger"></i> Alertas activas</span>
                    <button type="button" class="btn btn-xs btn-outline-success" id="ack-all-btn"><i class="bi bi-check2-all"></i> Reconocer todas</button>
                </div>
                <div class="card-body p-3" id="active-list">@include('admin.alertas._events', ['events' => $eventsActive, 'showAck' => true])</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="al-card">
                <div class="card-header"><i class="bi bi-clock-history"></i> Historial</div>
                <div class="card-body p-3" id="history-list">@include('admin.alertas._events', ['events' => $eventsHistory, 'showAck' => false])</div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
(function () {
    var base = '{{ url('alerts') }}';
    var soundsCache = [];
    var playedIds = {};

    function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    function csrf() { return (document.querySelector('meta[name="csrf-token"]') || {}).content || ''; }

    function jsonFetch(url, opts) {
        opts = opts || {};
        opts.headers = Object.assign({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, opts.headers || {});
        return fetch(url, opts).then(function (r) {
            return r.text().then(function (t) {
                var j = null;
                try { j = t ? JSON.parse(t) : null; } catch (e) {}
                if (j === null) throw new Error('El servidor devolvio una respuesta invalida (HTTP ' + r.status + ')');
                return j;
            });
        });
    }

    function fmtTime(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear()+' '+('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2)+':'+('0'+d.getSeconds()).slice(-2);
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

    function renderList(el, events, showAck) {
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
                    '<div class="ar-title">' + esc(ev.label) + '</div>' +
                    '<div class="ar-msg">' + esc(ev.message) + val + '</div>' +
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

    function ackEvent(id) {
        jsonFetch(base + '/ack', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}, body:JSON.stringify({id:parseInt(id,10)}) }).then(poll).catch(function(){});
    }

    function fillSoundSelects() {
        document.querySelectorAll('.cfg-sound').forEach(function (sel) {
            var cur = sel.dataset.current || sel.value || '';
            sel.querySelectorAll('option:not(:first-child)').forEach(function (o) { o.remove(); });
            soundsCache.forEach(function (p) {
                var o = document.createElement('option'); o.value = p; o.textContent = p.split('/').pop(); sel.appendChild(o);
            });
            sel.value = cur;
        });
        document.getElementById('stat-sounds').textContent = soundsCache.length;
    }

    function loadSounds(force) {
        if (soundsCache.length && !force) { fillSoundSelects(); return Promise.resolve(); }
        return jsonFetch(base + '/sounds').then(function (j) {
            soundsCache = j.sounds || [];
            fillSoundSelects();
        }).catch(function () {});
    }

    function saveRule(tr, forcedPath) {
        var payload = {
            threshold_pct: tr.querySelector('.cfg-threshold').value === '' ? null : parseInt(tr.querySelector('.cfg-threshold').value, 10),
            enabled: tr.querySelector('.cfg-enabled').checked,
            cooldown_min: parseInt(tr.querySelector('.cfg-cooldown').value, 10) || 15,
            sound_path: forcedPath !== undefined ? forcedPath : tr.querySelector('.cfg-sound').value
        };
        return jsonFetch(base + '/rules/' + tr.dataset.ruleId, {
            method:'PATCH', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}, body:JSON.stringify(payload)
        }).then(function (j) { if (j.rule && j.rule.sound_path !== undefined) tr.querySelector('.cfg-sound').dataset.current = j.rule.sound_path || ''; return j; })
          .catch(function (e) { alert((e && e.message) || 'No se pudo guardar'); });
    }

    function uploadSound(input) {
        var file = input.files && input.files[0];
        if (!file) return;
        var tr = input.closest('tr');
        var fd = new FormData();
        fd.append('sound', file);
        fd.append('_token', csrf());
        var label = input.parentElement;
        label.innerHTML = '<i class="bi bi-arrow-repeat"></i> subiendo...';
        jsonFetch(base + '/sounds', { method:'POST', body:fd })
            .then(function (j) { if (!j.success) throw j; return j; })
            .then(function (j) {
                loadSounds(true).then(function () {
                    tr.querySelector('.cfg-sound').value = j.path;
                    saveRule(tr, j.path);
                });
            })
            .catch(function (e) {
                var msg = (e && e.errors && e.errors.sound && e.errors.sound.join('\n')) || (e && e.message) || 'Error al subir el archivo';
                alert(msg);
            })
            .finally(function () {
                label.innerHTML = '<i class="bi bi-upload"></i> subir<input type="file" accept=".mp3,.wav,.ogg,audio/*" class="cfg-file" hidden>';
                bindFile(label.querySelector('.cfg-file'));
            });
    }

    function bindFile(inp) { inp.addEventListener('change', function () { uploadSound(inp); }); }

    function poll() {
        jsonFetch(base + '/state', { credentials:'same-origin' })
            .then(function (j) {
                document.getElementById('stat-active').textContent = j.active_count;
                document.getElementById('stat-history').textContent = j.history.length;
                var badge = document.getElementById('alerts-badge');
                if (badge) {
                    if (j.active_count > 0) {
                        badge.textContent = j.active_count > 99 ? '99+' : j.active_count;
                        badge.classList.remove('d-none');
                        badge.classList.toggle('pulsing', j.active.some(function(e){return e.severity==='critical';}));
                    } else { badge.classList.add('d-none'); }
                }
                renderList(document.getElementById('active-list'), j.active, true);
                renderList(document.getElementById('history-list'), j.history, false);
            })
            .catch(function () {});
    }

    loadSounds(false);
    poll();
    setInterval(poll, 10000);

    document.getElementById('ack-all-btn').addEventListener('click', function () {
        jsonFetch(base + '/ack', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}, body:JSON.stringify({all:true}) }).then(poll).catch(function(){});
    });

    document.querySelectorAll('.cfg-file').forEach(bindFile);

    document.querySelectorAll('.cfg-save').forEach(function (b) {
        b.addEventListener('click', function () { b.disabled = true; saveRule(b.closest('tr')).finally(function () { b.disabled = false; }); });
    });

    document.querySelectorAll('.cfg-test').forEach(function (b) {
        b.addEventListener('click', function () {
            var path = b.closest('tr').querySelector('.cfg-sound').value;
            if (path) {
                try { var a = new Audio('/' + path.replace(/^\/+/, '')); a.volume = 0.9; a.play().catch(function(){ beep('warning'); }); } catch (e) { beep('warning'); }
            } else { beep('warning'); }
        });
    });

    document.querySelectorAll('.cfg-sim').forEach(function (b) {
        b.addEventListener('click', function () {
            b.disabled = true;
            jsonFetch(base + '/simulate', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}, body:JSON.stringify({ rule_id: parseInt(b.closest('tr').dataset.ruleId, 10) }) })
                .then(function () { setTimeout(poll, 500); })
                .catch(function (e) { alert((e && e.message) || 'No se pudo simular'); })
                .finally(function () { b.disabled = false; });
        });
    });

    var simBtn = document.getElementById('alerts-simulate-btn');
    simBtn.addEventListener('click', function () {
        simBtn.disabled = true;
        jsonFetch(base + '/simulate', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}, body:JSON.stringify({}) })
            .then(function () { setTimeout(poll, 500); })
            .catch(function (e) { alert((e && e.message) || 'No se pudo simular'); })
            .finally(function () { simBtn.disabled = false; });
    });
})();
</script>
@stop
