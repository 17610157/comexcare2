@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css')
<link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
<style>
    /* ===== Tema oscuro global Comexcare ===== */
    body { background:#0b1220 !important; color:#e2e8f0; }
    ::-webkit-scrollbar { width:10px; height:10px; }
    ::-webkit-scrollbar-thumb { background:#1e293b; border-radius:6px; }
    ::-webkit-scrollbar-track { background:#0b1220; }

    .main-header.navbar { background:#0e1729 !important; border-bottom:1px solid rgba(148,163,184,.12); }
    .main-header .nav-link { color:#cbd5e1 !important; }
    .main-header .nav-item.user-menu .dropdown-menu,
    .dropdown-menu { background:#0e1729; border:1px solid rgba(148,163,184,.15); color:#e2e8f0; }
    .dropdown-item { color:#cbd5e1; }
    .dropdown-item:hover { background:rgba(59,130,246,.15); color:#fff; }
    .main-header li.user-header { background:linear-gradient(135deg,#1d4ed8,#0ea5e9); }

    .main-sidebar, .main-sidebar::before { background:#0e1729 !important; border-right:1px solid rgba(148,163,184,.08); }
    [class*="sidebar-dark"] .nav-sidebar .nav-link { color:#94a3b8 !important; border-radius:8px; margin:1px 8px; transition:all .18s ease; }
    [class*="sidebar-dark"] .nav-sidebar .nav-link:hover,
    [class*="sidebar-dark"] .nav-sidebar .nav-item.menu-open > .nav-link,
    [class*="sidebar-dark"] .nav-sidebar .nav-item > .nav-link.active { color:#fff !important; background:rgba(59,130,246,.16) !important; }
    [class*="sidebar-dark"] .nav-sidebar .nav-header { color:#475569; padding-left:16px; }
    .brand-link { border-bottom:1px solid rgba(148,163,184,.08) !important; }
    .brand-text { color:#f1f5f9 !important; }
    .sidebar .user-panel { border-bottom:1px solid rgba(148,163,184,.08); }

    /* ===== Scroll del sidebar visible ===== */
    .main-sidebar { overflow-y: auto !important; }
    .main-sidebar::-webkit-scrollbar { width: 8px; }
    .main-sidebar::-webkit-scrollbar-thumb { background:#1e293b; border-radius:6px; }
    .sidebar .os-scrollbar-vertical { opacity:.7 !important; visibility:visible !important; }
    .sidebar .os-scrollbar-vertical .os-scrollbar-handle { background:#334155 !important; border-radius:6px; }
    .sidebar .os-scrollbar-vertical .os-scrollbar-handle:hover,
    .sidebar .os-scrollbar-vertical .os-scrollbar-handle.active { background:#60a5fa !important; }

    .content-wrapper { background:#0b1220 !important; }
    .main-footer { background:#0e1729 !important; border-top:1px solid rgba(148,163,184,.1); color:#64748b; }
    .breadcrumb { background:transparent; }
    .breadcrumb-item a, .breadcrumb-item + .breadcrumb-item::before { color:#64748b; }

    .card {
        background:linear-gradient(180deg,rgba(17,24,39,.94),rgba(13,20,35,.94));
        border:1px solid rgba(148,163,184,.14);
        box-shadow:0 10px 30px rgba(2,6,23,.5);
        border-radius:12px;
        transition:border-color .25s ease;
    }
    .card:hover { border-color:rgba(96,165,250,.35); }
    .card-header { background:transparent; border-bottom:1px solid rgba(148,163,184,.1); }
    .card-title { color:#f1f5f9; font-weight:600; }
    .card-tools .btn-tool { color:#64748b; }
    .card-tools .btn-tool:hover { color:#93c5fd; }
    .card-body { padding:.7rem .95rem; }
    .card-header { padding:.55rem .9rem; }

    .table thead th { color:#94a3b8; border-color:rgba(148,163,184,.15) !important; background:transparent; font-size:.78rem; text-transform:uppercase; letter-spacing:.4px; }
    .table td { border-color:rgba(148,163,184,.09) !important; color:#cbd5e1; }
    .table-hover tbody tr:hover { background:rgba(59,130,246,.08); color:#fff; }
    .table a, a:not(.btn):not(.nav-link):not(.brand-link) { color:#60a5fa; }
    .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_paginate { color:#94a3b8; }
    .page-link { background:#0f172a; border-color:rgba(148,163,184,.2); color:#cbd5e1; }
    .page-item.active .page-link { background:#2563eb; border-color:#2563eb; }

    .info-box { background:rgba(30,41,59,.5); border:1px solid rgba(148,163,184,.12); border-radius:10px; box-shadow:none; }
    .info-box-content { color:#cbd5e1; }
    .info-box-text { color:#94a3b8; }

    .small-box { border-radius:12px; box-shadow:0 10px 26px rgba(2,6,23,.45); overflow:hidden; position:relative; }
    .small-box .inner { color:#fff; }
    .small-box h3 { font-weight:700; font-variant-numeric:tabular-nums; }
    .small-box-footer { background:rgba(0,0,0,.18) !important; color:rgba(255,255,255,.85) !important; }
    .bg-success { background-image:linear-gradient(135deg,#059669,#10b981) !important; }
    .bg-danger  { background-image:linear-gradient(135deg,#dc2626,#f87171) !important; }
    .bg-info    { background-image:linear-gradient(135deg,#0284c7,#38bdf8) !important; }
    .bg-primary { background-image:linear-gradient(135deg,#1d4ed8,#3b82f6) !important; }
    .bg-warning { background-image:linear-gradient(135deg,#b45309,#f59e0b) !important; }

    .form-control, .custom-select { background:#0f172a; border-color:rgba(148,163,184,.28); color:#e2e8f0; }
    .form-control:focus, .custom-select:focus { background:#0f172a; border-color:#3b82f6; color:#f1f5f9; box-shadow:0 0 0 2px rgba(59,130,246,.25); }
    select option { background:#0f172a; color:#e2e8f0; }
    .input-group-text { background:#1e293b; border-color:rgba(148,163,184,.28); color:#94a3b8; }
    label { color:#94a3b8; }
    .custom-control-input:checked ~ .custom-control-label::before { background-color:#2563eb; border-color:#2563eb; }
    .custom-switch .custom-control-label::before { background-color:#334155; }

    .progress { background:rgba(148,163,184,.16); border-radius:99px; }
    .text-muted { color:#64748b !important; }
    .badge-secondary { background:#334155; color:#e2e8f0; }
    .alert-dark, .alert-secondary { background:#1e293b; border-color:rgba(148,163,184,.2); color:#cbd5e1; }
    /* ===== Modales 100% oscuros ===== */
    .modal-content,
    .modal-header,
    .modal-body,
    .modal-footer { background:#0e1729 !important; color:#fff; }
    .modal-content { border:1px solid rgba(148,163,184,.18); box-shadow:0 20px 60px rgba(0,0,0,.6); }
    .modal-header, .modal-footer { border-color:rgba(148,163,184,.12); }
    .modal-title { color:#fff; font-weight:600; }
    .modal-header .close, .modal-footer button.close { color:#cbd5e1; text-shadow:none; opacity:.85; }
    .modal-header .close:hover { color:#fff; opacity:1; }
    .modal-content .form-control, .modal-content .custom-select { background:#0f172a; color:#fff; border-color:rgba(148,163,184,.28); }
    .modal-content label { color:#cbd5e1; }
    .modal-content .text-muted { color:#94a3b8 !important; }
    .modal-content .bg-white { background:#0e1729 !important; color:#fff; }
    .list-group-item { background:#0f172a; border-color:rgba(148,163,184,.12); color:#cbd5e1; }
    .list-group-item:hover, .list-group-item:focus { background:rgba(59,130,246,.1); color:#fff; }
    .list-group-item-action { color:#cbd5e1; }

    /* ===== SweetAlert2 oscuro ===== */
    .swal2-popup { background:#0e1729 !important; color:#fff !important; border:1px solid rgba(148,163,184,.18); border-radius:14px !important; }
    .swal2-title { color:#fff !important; }
    .swal2-html-container, .swal2-content { color:#cbd5e1 !important; }
    .swal2-popup .swal2-styled.swal2-confirm { background:#2563eb !important; box-shadow:none; }
    .swal2-popup .swal2-styled.swal2-deny { background:#dc2626 !important; box-shadow:none; }
    .swal2-popup .swal2-styled.swal2-cancel { background:#334155 !important; color:#fff !important; box-shadow:none; }
    .swal2-popup .swal2-input, .swal2-popup .swal2-select, .swal2-popup .swal2-textarea { background:#0f172a; border:1px solid rgba(148,163,184,.28); color:#fff; }
    .swal2-popup .swal2-validation-message { background:#1e293b; color:#fca5a5; }
    .swal2-timer-progress-bar { background:#3b82f6; }
    .nav-pills .nav-link { color:#94a3b8; }
    .nav-pills .nav-link.active { background:#2563eb; }
    .callout { background:rgba(30,41,59,.5); border:1px solid rgba(148,163,184,.12); border-radius:10px; }
</style>
    @stack('css')
    @yield('css')
@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="wrapper">

        {{-- Preloader Animation (fullscreen mode) --}}
        @if($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Main Sidebar --}}
        @if(!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        @empty($iFrameEnabled)
            @include('adminlte::partials.cwrapper.cwrapper-default')
        @else
            @include('adminlte::partials.cwrapper.cwrapper-iframe')
        @endempty

        {{-- Footer --}}
        @hasSection('footer')
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Control Sidebar --}}
        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>

    {{-- Widget global de alertas --}}
    @include('admin.partials.alerts-widget')
@stop

@section('adminlte_js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('[data-lte-toggle="fullscreen"]');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var maxim = btn.querySelector('[data-lte-icon="maximize"]');
            var minim = btn.querySelector('[data-lte-icon="minimize"]');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(function () {
                    if (maxim) maxim.classList.add('d-none');
                    if (minim) minim.classList.remove('d-none');
                }).catch(function () {});
            } else {
                document.exitFullscreen().then(function () {
                    if (maxim) maxim.classList.remove('d-none');
                    if (minim) minim.classList.add('d-none');
                }).catch(function () {});
            }
        });
        document.addEventListener('fullscreenchange', function () {
            var maxim = btn.querySelector('[data-lte-icon="maximize"]');
            var minim = btn.querySelector('[data-lte-icon="minimize"]');
            var on = Boolean(document.fullscreenElement);
            if (maxim) maxim.classList.toggle('d-none', on);
            if (minim) minim.classList.toggle('d-none', !on);
        });
    });
</script>
    @stack('js')
    @yield('js')
@stop
