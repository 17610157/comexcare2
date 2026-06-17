@extends('adminlte::page')

@section('title', 'Comexcare')

@section('content_header')
    <h1><i class="fas fa-chart-line text-primary"></i> Comexcare</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $onlineComputers }}</h3>
                <p>Computadoras En línea</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $offlineComputers }}</h3>
                <p>Computadoras Fuera de línea</p>
            </div>
            <div class="icon">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalComputers }}</h3>
                <p>Total Computadoras</p>
            </div>
            <div class="icon">
                <i class="fas fa-desktop"></i>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title">Resumen por Plaza</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead class="bg-success">
                        <tr>
                            <th>Plaza</th>
                            <th class="text-center">Total Equipos</th>
                            <th class="text-center">En Línea</th>
                            <th class="text-center">Fuera de Línea</th>
                            <th class="text-center">Avance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plazaSummary as $plaza)
                        <tr>
                            <td><strong>{{ $plaza->plaza }}</strong></td>
                            <td class="text-center">{{ $plaza->total }}</td>
                            <td class="text-center text-success"><strong>{{ $plaza->online }}</strong></td>
                            <td class="text-center text-danger"><strong>{{ $plaza->total - $plaza->online }}</strong></td>
                            <td class="text-center">
                                @php $pct = $plaza->total > 0 ? round(($plaza->online / $plaza->total) * 100, 1) : 0; @endphp
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct }}%">{{ $pct }}%</div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title">Versiones de Agente por Plaza</h3>
            </div>
            <div class="card-body p-0" style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead class="bg-info">
                        <tr>
                            <th>Plaza</th>
                            @foreach($allAgentVersions as $ver)
                            <th class="text-center" style="font-size: 0.85em;">{{ $ver }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plazaSummary as $plaza)
                        <tr>
                            <td><strong>{{ $plaza->plaza }}</strong></td>
                            @foreach($allAgentVersions as $ver)
                            <td class="text-center">{{ $agentLookup[$plaza->plaza][$ver] ?? 0 }}</td>
                            @endforeach
                        </tr>
                        @empty
                        <tr><td colspan="{{ count($allAgentVersions) + 1 }}" class="text-center">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">Versiones PVSI por Plaza</h3>
            </div>
            <div class="card-body p-0" style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead class="bg-primary">
                        <tr>
                            <th>Plaza</th>
                            @foreach($allPvsiVersions as $ver)
                            <th class="text-center" style="font-size: 0.85em;">{{ $ver }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plazaSummary as $plaza)
                        <tr>
                            <td><strong>{{ $plaza->plaza }}</strong></td>
                            @foreach($allPvsiVersions as $ver)
                            <td class="text-center">{{ $pvsiLookup[$plaza->plaza][$ver] ?? 0 }}</td>
                            @endforeach
                        </tr>
                        @empty
                        <tr><td colspan="{{ count($allPvsiVersions) + 1 }}" class="text-center">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
(function() {
    'use strict';

    function updateDashboard() {
        fetch('{{ route('home.stats') }}')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var onlineEl = document.querySelector('.small-box.bg-success h3');
                var offlineEl = document.querySelector('.small-box.bg-danger h3');
                var totalEl = document.querySelector('.small-box.bg-primary h3');

                if (onlineEl) onlineEl.textContent = data.online_computers;
                if (offlineEl) offlineEl.textContent = data.offline_computers;
                if (totalEl) totalEl.textContent = data.total_computers;

                var tbody = document.querySelector('.table-bordered tbody');
                if (tbody && data.plaza_summary && data.plaza_summary.length) {
                    var rows = tbody.querySelectorAll('tr');
                    data.plaza_summary.forEach(function(plaza, i) {
                        if (rows[i] && !rows[i].querySelector('td[colspan]')) {
                            var cells = rows[i].querySelectorAll('td');
                            if (cells.length >= 5) {
                                cells[1].textContent = plaza.total;
                                cells[2].innerHTML = '<strong class="text-success">' + plaza.online + '</strong>';
                                cells[3].innerHTML = '<strong class="text-danger">' + plaza.offline + '</strong>';
                                var pctEl = cells[4].querySelector('.progress-bar');
                                if (pctEl) {
                                    var pct = plaza.percentage;
                                    pctEl.style.width = pct + '%';
                                    pctEl.textContent = pct + '%';
                                }
                            }
                        }
                    });
                }
            })
            .catch(function() {});
    }

    setInterval(updateDashboard, 300000);
})();
</script>
@endpush