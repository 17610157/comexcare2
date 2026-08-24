@if (empty($events))
    <div class="text-center py-4" style="color:#54607a"><i class="bi bi-check-circle" style="font-size:2rem"></i><div class="mt-2">Sin alertas</div></div>
@else
    @foreach ($events as $ev)
        <div class="alert-row sev-{{ $ev->severity }}{{ $ev->acknowledged_at ? ' ar-ackd' : '' }}">
            <div class="ar-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <div class="ar-body">
                <div class="ar-title">{{ $ev->label }}</div>
                <div class="ar-msg">{{ $ev->message }}@if($ev->value_pct !== null) — valor actual: <b>{{ rtrim(rtrim(number_format((float)$ev->value_pct, 1, '.', ''), '0'), '.') }}%</b>@endif</div>
                <div class="ar-time">
                    <span><i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($ev->triggered_at)->format('d/m/Y H:i:s') }}</span>
                    <span>{{ \Carbon\Carbon::parse($ev->triggered_at)->locale('es')->diffForHumans() }}</span>
                    @if($ev->acknowledged_at)<span><i class="bi bi-check2-all"></i> reconocida</span>@endif
                </div>
            </div>
        </div>
    @endforeach
@endif
