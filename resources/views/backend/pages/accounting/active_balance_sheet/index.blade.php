
@extends('backend.layouts.master')

@section('title')
@lang('messages.Bilan Actif') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <style>
        .balance-meta {
            border: 1px solid #e1e5eb;
            border-radius: 6px;
            padding: 1.25rem 1.5rem;
            background-color: #f9fbfd;
            margin-bottom: 1.5rem;
        }

        .balance-meta h5 {
            font-size: 1.125rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: .35rem;
        }

        .balance-meta .meta-line {
            font-size: .875rem;
            margin-bottom: .2rem;
            color: #4d5969;
        }

        .active-balance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .active-balance-table thead th {
            background-color: #eef2f7;
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .05em;
            color: #2f3a4c;
            border-bottom: 2px solid #d9dde2;
            vertical-align: middle;
            padding: .75rem;
            text-align: center;
        }

        .active-balance-table tbody td {
            font-size: .85rem;
            padding: .6rem .75rem;
            border-bottom: 1px solid #e1e5eb;
        }

        .active-balance-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }

        .active-balance-table .libelle-col {
            text-align: left;
            padding-left: 1rem;
        }

        .active-balance-table .montant-col {
            text-align: right;
            padding-right: 1rem;
        }

        .active-balance-table tbody tr.title-row {
            background-color: #eef2f7;
            font-weight: 700;
            font-size: .95rem;
        }

        .active-balance-table tbody tr.title-row .libelle-col {
            padding-left: 0.5rem;
        }

        .active-balance-table tbody tr.subtitle-row {
            background-color: #f9fbfd;
            font-weight: 500;
        }

        .active-balance-table tbody tr.subtitle-row .libelle-col {
            padding-left: 2rem;
        }

        .active-balance-table tbody tr.subtotal-row {
            background-color: #eef2f7;
            font-weight: 600;
            border-top: 2px solid #d9dde2;
        }

        .active-balance-table tbody tr.subtotal-row .libelle-col {
            padding-left: 1rem;
        }

        .active-balance-table tbody tr.total-row {
            background-color: #d9dde2;
            font-weight: 700;
            font-size: 1rem;
            border-top: 3px solid #2f3a4c;
        }

        .active-balance-table tbody tr.total-row .libelle-col {
            padding-left: 0.5rem;
        }

        .active-balance-table tbody tr.level-2 .libelle-col {
            padding-left: 3rem;
        }
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Bilan Actif')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<!-- page title area end -->

<div class="main-content-inner">
    <div class="row">
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <h4 class="header-title mb-0">@lang('messages.Bilan Actif')</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.active-balance-sheet.export-pdf') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-file-pdf"></i> Exporter PDF
                            </a>
                            <a href="{{ route('admin.active-balance-sheet.export-excel') }}" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel"></i> Exporter Excel
                            </a>
                            <span class="text-muted small">@lang('messages.Rapport généré le') {{ $reportGeneratedAt }}</span>
                        </div>
                    </div>

                    <div class="balance-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>@lang('messages.BILAN ACTIF')</h5>
                                <p class="meta-line mb-1"><strong>@lang('messages.Structure :')</strong> {{ $companyName }}</p>
                                <p class="meta-line mb-0"><strong>@lang('messages.Type de rapport :')</strong> @lang('messages.Bilan Actif')</p>
                            </div>
                            <div class="col-md-6 text-md-right">
                                <p class="meta-line mb-1"><strong>@lang('messages.Année fiscale :')</strong> {{ $fiscalYear }}</p>
                                <p class="meta-line mb-1"><strong>@lang('messages.Devise :')</strong> {{ config('app.currency', 'BIF') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table class="table table-bordered active-balance-table">
                            <thead>
                                <tr>
                                    <th class="libelle-col" style="width: 50%;">@lang('messages.ACTIFS')</th>
                                    <th class="montant-col" style="width: 25%;">@lang('messages.MONTANT BRUT')</th>
                                    <th class="montant-col" style="width: 25%;">@lang('messages.AMORTISSEMENT/DEPRECIATION')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($balanceData as $row)
                                    @if($row['type'] === 'title')
                                        <tr class="title-row">
                                            <td class="libelle-col" colspan="3"><strong>{{ $row['libelle'] }}</strong></td>
                                        </tr>
                                    @elseif($row['type'] === 'subtitle')
                                        <tr class="subtitle-row level-{{ $row['level'] }}">
                                            <td class="libelle-col">{{ $row['libelle'] }}</td>
                                            <td class="montant-col">{{ number_format($row['montant_brut'], 0, ',', ' ') }}</td>
                                            <td class="montant-col">{{ number_format($row['amortissement'], 0, ',', ' ') }}</td>
                                        </tr>
                                    @elseif($row['type'] === 'subtotal')
                                        <tr class="subtotal-row">
                                            <td class="libelle-col"><strong>{{ $row['libelle'] }}</strong></td>
                                            <td class="montant-col"><strong>{{ number_format($row['montant_brut'], 0, ',', ' ') }}</strong></td>
                                            <td class="montant-col"><strong>{{ number_format($row['amortissement'], 0, ',', ' ') }}</strong></td>
                                        </tr>
                                    @elseif($row['type'] === 'total')
                                        <tr class="total-row">
                                            <td class="libelle-col"><strong>{{ $row['libelle'] }}</strong></td>
                                            <td class="montant-col"><strong>{{ number_format($row['montant_brut'], 0, ',', ' ') }}</strong></td>
                                            <td class="montant-col"><strong>{{ number_format($row['amortissement'], 0, ',', ' ') }}</strong></td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">@lang('messages.no_data_found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection


@section('scripts')
     <script>
        function preventBack() {
            window.history.forward();
        }
        setTimeout("preventBack()", 0);
        window.onunload = function () {
            null
        };
     </script>
@endsection
