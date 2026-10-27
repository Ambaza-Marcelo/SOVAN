@extends('backend.layouts.master')

@section('title')
Plan d'amortissement - {{ $material->name }} - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .depreciation-kpi {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 14px 16px;
        height: 100%;
    }
    .depreciation-kpi-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; }
    .depreciation-kpi-value { font-size: 22px; font-weight: 700; color: #1f2d3d; margin-top: 4px; }
    .depreciation-schedule-table tr.depreciation-current-year {
        background: rgba(23, 162, 184, 0.12) !important;
        outline: 2px solid #17a2b8;
    }
    .depreciation-meta { color: #6c757d; font-size: 13px; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Plan d'amortissement</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.materials.index') }}">@lang('messages.materials')</a></li>
                    <li><span>{{ $material->name }}</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="header-title mb-1">{{ $material->name }} <small class="text-muted">({{ $material->code }})</small></h4>
                            <div class="depreciation-meta">
                                Mise en service :
                                {{ optional($material->service_start_date)->format('d/m/Y') ?? 'N/A' }}
                                | Durée : {{ $material->duration_years }} an(s)
                                | Taux : {{ number_format(100 / max(1, (int) $material->duration_years), 2, ',', ' ') }} %
                            </div>
                            <div class="depreciation-meta mt-1">
                                Charge :
                                @if($material->depreciationExpenseAccount)
                                    {{ $material->depreciationExpenseAccount->account_number }} - {{ $material->depreciationExpenseAccount->account_name }}
                                @else
                                    N/A
                                @endif
                                |
                                Cumulés :
                                @if($material->depreciationAssetAccount)
                                    {{ $material->depreciationAssetAccount->account_number }} - {{ $material->depreciationAssetAccount->account_name }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div>
                            @if (Auth::guard('admin')->user()->can('material.view'))
                                <a href="{{ route('admin.materials.depreciation.export-excel', $material->id) }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-file-excel-o"></i> Excel
                                </a>
                                <a href="{{ route('admin.materials.depreciation.export-pdf', $material->id) }}" class="btn btn-info btn-sm" target="_blank">
                                    <i class="fa fa-file-pdf-o"></i> PDF
                                </a>
                            @endif
                            <a href="{{ route('admin.materials.edit', $material->id) }}" class="btn btn-success">@lang('messages.edit')</a>
                            <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">@lang('messages.list')</a>
                        </div>
                    </div>

                    @include('backend.layouts.partials.messages')
                    @include('backend.pages.material.partials.depreciation_schedule', ['material' => $material])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var currentYear = new Date().getFullYear();
        $('#depreciationScheduleTable tbody tr').each(function () {
            if (parseInt($(this).data('year'), 10) === currentYear) {
                $(this).addClass('depreciation-current-year');
            }
        });
    })();
</script>
@endsection
