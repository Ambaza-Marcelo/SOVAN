
@extends('backend.layouts.master')

@section('title')
    @lang('messages.profit_and_loss_statement_title') - @lang('messages.admin_panel')
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

        .balance-table {
            width: 100%;
        }

        .balance-table thead th {
            background-color: #eef2f7;
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .05em;
            color: #2f3a4c;
            border-bottom: 2px solid #d9dde2;
            vertical-align: middle;
        }

        .balance-table tbody td {
            font-size: .85rem;
            padding: .6rem .75rem;
        }

        .balance-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }

        .balance-table tfoot td {
            background-color: #eef2f7;
            font-weight: 600;
            font-size: .85rem;
            border-top: 2px solid #d9dde2;
        }

        .balance-table .text-right {
            text-align: right;
        }

        .profit-loss-table {
            width: 100%;
        }

        .profit-loss-table thead th {
            background-color: #eef2f7;
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .05em;
            color: #2f3a4c;
            border-bottom: 2px solid #d9dde2;
            vertical-align: middle;
        }

        .profit-loss-table tbody td {
            font-size: .85rem;
            padding: .6rem .75rem;
        }

        .profit-loss-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }

        .profit-loss-table tbody tr.subtotal-row {
            background-color: #eef2f7;
            font-weight: 600;
        }

        .profit-loss-table tbody tr.total-row {
            background-color: #d9dde2;
            font-weight: 700;
            font-size: .95rem;
        }

        .profit-loss-table .libelle-col {
            text-align: left;
            padding-left: 1rem;
        }

        .profit-loss-table .montant-col {
            text-align: right;
            padding-right: 1rem;
        }
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.profit_and_loss_statement_title')</h4>
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
                        <h4 class="header-title mb-0">@lang('messages.profit_and_loss_statement_title')</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.profit-loss-statement.export-pdf') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-file-pdf"></i> @lang('messages.export_pdf', ['default' => 'Export PDF'])
                            </a>
                            <a href="{{ route('admin.profit-loss-statement.export-excel') }}" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel"></i> @lang('messages.export_excel')
                            </a>
                            <span class="text-muted small">@lang('messages.profit_and_loss_generated_at') {{ $reportGeneratedAt }}</span>
                        </div>
                    </div>

                    <div class="balance-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>@lang('messages.profit_and_loss_statement_title')</h5>
                                <p class="meta-line mb-1"><strong>@lang('messages.profit_and_loss_structure') :</strong> {{ $companyName }}</p>
                                <p class="meta-line mb-0"><strong>@lang('messages.profit_and_loss_report_type') :</strong> @lang('messages.profit_and_loss_report_type_value')</p>
                            </div>
                            <div class="col-md-6 text-md-right">
                                <p class="meta-line mb-1"><strong>@lang('messages.profit_and_loss_fiscal_year') :</strong> {{ $fiscalYear }}</p>
                                <p class="meta-line mb-1"><strong>@lang('messages.profit_and_loss_currency') :</strong> {{ config('app.currency', 'BIF') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table class="table table-bordered profit-loss-table">
                            <thead>
                                <tr>
                                    <th class="libelle-col" style="width: 70%;">@lang('messages.profit_and_loss_labels')</th>
                                    <th class="montant-col" style="width: 30%;">@lang('messages.profit_and_loss_amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statementData as $row)
                                    <tr class="{{ $row['type'] === 'subtotal' ? 'subtotal-row' : ($row['type'] === 'total' ? 'total-row' : '') }}">
                                        <td class="libelle-col">{{ $row['libelle'] }}</td>
                                        <td class="montant-col">{{ number_format($row['montant'], 0, ',', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">@lang('messages.no_data_found')</td>
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