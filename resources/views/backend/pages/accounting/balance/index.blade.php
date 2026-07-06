
@extends('backend.layouts.master')

@section('title')
    @lang('messages.accounts_balance_title') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            padding: 2px 8px;
        }
        .select2-container {
            width: 100% !important;
        }
        .ledger-account-mode .btn {
            font-size: .8rem;
        }
        .ledger-account-mode .btn.active {
            font-weight: 600;
        }
        .select2-results__option .ledger-opt-parent {
            color: #1a5f7a;
            font-weight: 600;
        }
        .select2-results__option .ledger-opt-child {
            color: #2f3a4c;
        }
        .balance-filter-card {
            border: 1px solid #e1e5eb;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            background-color: #f9fbfd;
            margin-bottom: 1.25rem;
        }
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
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.accounts_balance_title')</h4>
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
                        <h4 class="header-title mb-0">{{ __('messages.accounts_balance_title') }}</h4>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <button type="button" id="exportBalancePdfBtn" class="btn btn-primary btn-sm">
                                <i class="fa fa-file-pdf"></i> @lang('messages.export_pdf')
                            </button>
                            <button type="button" id="exportBalanceExcelBtn" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel"></i> @lang('messages.export_excel')
                            </button>
                            <span class="text-muted small">@lang('messages.accounts_balance_generated_at') {{ $reportGeneratedAt }}</span>
                        </div>
                    </div>

                    <form id="balanceFilterForm" method="GET" action="{{ route('admin.accounts-balance.index') }}">
                        <div class="balance-filter-card">
                            <div class="form-group mb-2">
                                <label class="mb-2">@lang('messages.Compte(s) (optionnel)')</label>
                                <div class="btn-group btn-group-toggle btn-group-sm d-flex ledger-account-mode mb-2" data-toggle="buttons">
                                    <label class="btn btn-outline-secondary flex-fill {{ ($ledgerAccountMode ?? '') === '' ? 'active' : '' }}">
                                        <input type="radio" name="ledger_account_mode" value="" autocomplete="off" {{ ($ledgerAccountMode ?? '') === '' ? 'checked' : '' }}> Tous les comptes
                                    </label>
                                    <label class="btn btn-outline-primary flex-fill {{ ($ledgerAccountMode ?? '') === 'parent' ? 'active' : '' }}">
                                        <input type="radio" name="ledger_account_mode" value="parent" autocomplete="off" {{ ($ledgerAccountMode ?? '') === 'parent' ? 'checked' : '' }}> Compte(s) parent(s)
                                    </label>
                                    <label class="btn btn-outline-info flex-fill {{ ($ledgerAccountMode ?? '') === 'child' ? 'active' : '' }}">
                                        <input type="radio" name="ledger_account_mode" value="child" autocomplete="off" {{ ($ledgerAccountMode ?? '') === 'child' ? 'checked' : '' }}> Compte(s) enfant(s)
                                    </label>
                                </div>
                                <div id="balanceAccountsWrapper" style="{{ ($ledgerAccountMode ?? '') === '' ? 'display: none;' : '' }}">
                                    <select class="form-control" id="ledger_account_keys" name="ledger_account_keys[]" multiple="multiple" {{ ($ledgerAccountMode ?? '') === '' ? 'disabled' : '' }}>
                                        @foreach ($selectedLedgerAccounts ?? [] as $option)
                                            <option value="{{ $option['id'] }}" selected>{{ $option['text'] }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted mode-hint" id="balanceAccountModeHint"></small>
                                </div>
                                <small class="form-text text-muted" id="balanceAccountGlobalHint" style="{{ ($ledgerAccountMode ?? '') !== '' ? 'display: none;' : '' }}">Par défaut, la balance de tous les comptes est affichée.</small>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fa fa-filter"></i> Filtrer
                                </button>
                                <a href="{{ route('admin.accounts-balance.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fa fa-undo"></i> Réinitialiser
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="balance-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>{{ __('messages.accounts_balance_title') }}</h5>
                                <p class="meta-line mb-1"><strong>{{ __('messages.accounts_balance_structure') }} :</strong> {{ $companyName }}</p>
                                <p class="meta-line mb-0"><strong>{{ __('messages.accounts_balance_report_type') }} :</strong> {{ __('messages.accounts_balance_report_type_value') }}</p>
                            </div>
                            <div class="col-md-6 text-md-right">
                                <p class="meta-line mb-1"><strong>{{ __('messages.accounts_balance_fiscal_year') }} :</strong> {{ $fiscalYear }}</p>
                                <p class="meta-line mb-1"><strong>{{ __('messages.accounts_balance_currency') }} :</strong> {{ config('app.currency', 'BIF') }}</p>
                                @if (!empty($accountFilterLabel) && $accountFilterLabel !== 'Tous les comptes')
                                    <p class="meta-line mb-0"><strong>Filtre comptes :</strong> {{ $accountFilterLabel }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="balanceTable" class="table table-bordered table-hover balance-table">
                            <thead>
                                <tr>
                                    <th rowspan="2">{{ __('messages.accounts_balance_account_number') }}</th>
                                    <th rowspan="2">{{ __('messages.accounts_balance_account_name') }}</th>
                                    <th colspan="2" class="text-center">{{ __('messages.accounts_balance_movements') }}</th>
                                    <th colspan="2" class="text-center">{{ __('messages.accounts_balance_balances') }}</th>
                                </tr>
                                <tr>
                                    <th>{{ __('messages.accounts_balance_debit') }}</th>
                                    <th>{{ __('messages.accounts_balance_credit') }}</th>
                                    <th>{{ __('messages.accounts_balance_debit') }}</th>
                                    <th>{{ __('messages.accounts_balance_credit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($accountsData as $row)
                                    <tr>
                                        <td>{{ $row['account_number'] }}</td>
                                        <td>{{ $row['account_name'] ?: __('messages.accounts_balance_undefined_account') }}</td>
                                        <td class="text-right movement-debit" data-raw="{{ $row['movement_debit'] }}">{{ number_format($row['movement_debit'], 0, ',', ' ') }}</td>
                                        <td class="text-right movement-credit" data-raw="{{ $row['movement_credit'] }}">{{ number_format($row['movement_credit'], 0, ',', ' ') }}</td>
                                        <td class="text-right balance-debit" data-raw="{{ $row['balance_debit'] }}">{{ number_format($row['balance_debit'], 0, ',', ' ') }}</td>
                                        <td class="text-right balance-credit" data-raw="{{ $row['balance_credit'] }}">{{ number_format($row['balance_credit'], 0, ',', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">@lang('messages.no_data_found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right"><strong>{{ __('messages.accounts_balance_carry_forward') }} :</strong></td>
                                    <td class="text-right total-movement-debit"><strong>{{ number_format($grandTotals['movement_debit'], 0, ',', ' ') }}</strong></td>
                                    <td class="text-right total-movement-credit"><strong>{{ number_format($grandTotals['movement_credit'], 0, ',', ' ') }}</strong></td>
                                    <td class="text-right total-balance-debit"><strong>{{ number_format($grandTotals['balance_debit'], 0, ',', ' ') }}</strong></td>
                                    <td class="text-right total-balance-credit"><strong>{{ number_format($grandTotals['balance_credit'], 0, ',', ' ') }}</strong></td>
                                </tr>
                            </tfoot>
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
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
     
     <script>
         /*================================
        datatable active
        ==================================*/
        if ($('#balanceTable').length) {
            function formatNumber(num) {
                var number = parseFloat(num) || 0;
                return number.toLocaleString('fr-FR');
            }

            function parseNumber(value) {
                if (typeof value === 'number') {
                    return value;
                }

                if (value === null || value === undefined) {
                    return 0;
                }

                var asString = value.toString();
                var cleaned = asString
                    .replace(/<[^>]*>/g, '')
                    .replace(/\u00A0/g, '')
                    .replace(/\s+/g, '')
                    .replace(',', '.');

                var parsed = parseFloat(cleaned);
                return isNaN(parsed) ? 0 : parsed;
            }

            function calculateColumnTotal(api, columnIndex) {
                return api
                    .column(columnIndex, { search: 'applied' })
                    .data()
                    .reduce(function (total, value) {
                        return total + parseNumber(value);
                    }, 0);
            }

            function updateFooterTotals(api) {
                var totalMovementDebit = calculateColumnTotal(api, 2);
                var totalMovementCredit = calculateColumnTotal(api, 3);
                var totalBalanceDebit = calculateColumnTotal(api, 4);
                var totalBalanceCredit = calculateColumnTotal(api, 5);

                $('.total-movement-debit').html('<strong>' + formatNumber(totalMovementDebit) + '</strong>');
                $('.total-movement-credit').html('<strong>' + formatNumber(totalMovementCredit) + '</strong>');
                $('.total-balance-debit').html('<strong>' + formatNumber(totalBalanceDebit) + '</strong>');
                $('.total-balance-credit').html('<strong>' + formatNumber(totalBalanceCredit) + '</strong>');
            }

            var table = $('#balanceTable').DataTable({
                responsive: true,
                ordering: true,
                order: [[0, 'asc']],
                pageLength: 25,
                columnDefs: [
                    { targets: [2, 3, 4, 5], className: 'text-right' }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json'
                }
            });

            table.on('draw', function () {
                updateFooterTotals(table);
            });

            updateFooterTotals(table);
        }

        function preventBack() {
            window.history.forward();
        }
        setTimeout("preventBack()", 0);
        window.onunload = function () {
            null
        };

        var balanceAccountHints = {
            parent: 'Sélectionnez un ou plusieurs comptes parents — tous leurs comptes enfants seront inclus dans la balance.',
            child: 'Sélectionnez un ou plusieurs comptes enfants individuellement.'
        };
        var balanceAccountPlaceholders = {
            parent: 'Rechercher un ou plusieurs comptes parents…',
            child: 'Rechercher un ou plusieurs comptes enfants…'
        };

        function getBalanceAccountScope() {
            return $('input[name="ledger_account_mode"]:checked').val() || '';
        }

        function formatBalanceAccountOption(data) {
            if (!data.id) {
                return data.text;
            }
            if (String(data.id).indexOf('parent-') === 0) {
                return $('<span class="ledger-opt-parent"><i class="fa fa-folder-open-o"></i> ' + data.text + '</span>');
            }
            return $('<span class="ledger-opt-child"><i class="fa fa-file-text-o"></i> ' + data.text + '</span>');
        }

        function formatBalanceAccountSelection(data) {
            if (!data.id) {
                return data.text;
            }
            var text = data.text || '';
            if (String(data.id).indexOf('parent-') === 0) {
                text = text.replace(/^\[Parent\]\s*/, '');
            } else {
                text = text.split(' ← ')[0];
            }
            return text.length > 60 ? text.substring(0, 57) + '…' : text;
        }

        function updateBalanceAccountModeUi() {
            var scope = getBalanceAccountScope();
            var $wrapper = $('#balanceAccountsWrapper');
            var $select = $('#ledger_account_keys');
            var $globalHint = $('#balanceAccountGlobalHint');

            if (scope === '') {
                $wrapper.hide();
                $select.prop('disabled', true);
                $globalHint.show();
                $('#balanceAccountModeHint').text('');
            } else {
                $wrapper.show();
                $select.prop('disabled', false);
                $globalHint.hide();
                $('#balanceAccountModeHint').text(balanceAccountHints[scope] || '');
            }
        }

        function initBalanceAccountSelect2() {
            var $select = $('#ledger_account_keys');
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            var scope = getBalanceAccountScope();
            if (scope === '') {
                return;
            }

            $select.select2({
                ajax: {
                    url: '{{ route("admin.journal-entry.search-accounts") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            scope: getBalanceAccountScope()
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: false
                },
                placeholder: balanceAccountPlaceholders[scope] || 'Rechercher…',
                minimumInputLength: 0,
                allowClear: true,
                closeOnSelect: false,
                theme: 'default',
                width: '100%',
                dropdownParent: $('#balanceFilterForm'),
                templateResult: formatBalanceAccountOption,
                templateSelection: formatBalanceAccountSelection
            });
        }

        function validateBalanceFilterForm() {
            var scope = getBalanceAccountScope();
            if (scope !== '') {
                var selected = $('#ledger_account_keys').val();
                if (!selected || selected.length === 0) {
                    alert('Veuillez sélectionner au moins un compte ' + (scope === 'parent' ? 'parent' : 'enfant') + '.');
                    return false;
                }
            }
            return true;
        }

        $('#balanceFilterForm').on('submit', function (e) {
            if (!validateBalanceFilterForm()) {
                e.preventDefault();
            }
        });

        $('input[name="ledger_account_mode"]').on('change', function () {
            var $select = $('#ledger_account_keys');
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.val(null).trigger('change');
                $select.select2('destroy');
            } else {
                $select.empty();
            }
            updateBalanceAccountModeUi();
            initBalanceAccountSelect2();
        });

        $(document).on('select2:open', '#ledger_account_keys', function () {
            var $search = $('.select2-container--open .select2-search__field');
            if ($search.length && !$search.val()) {
                $search.trigger('input');
            }
        });

        $(document).on('select2:selecting', '#ledger_account_keys', function (e) {
            var scope = getBalanceAccountScope();
            var id = String(e.params.args.data.id || '');
            if (scope === 'parent' && id.indexOf('child-') === 0) {
                e.preventDefault();
            }
            if (scope === 'child' && id.indexOf('parent-') === 0) {
                e.preventDefault();
            }
        });

        $('#exportBalancePdfBtn').on('click', function () {
            if (!validateBalanceFilterForm()) {
                return;
            }
            var params = $('#balanceFilterForm').serialize();
            window.open('{{ route('admin.accounts-balance.export-pdf') }}?' + params, '_blank');
        });

        $('#exportBalanceExcelBtn').on('click', function () {
            if (!validateBalanceFilterForm()) {
                return;
            }
            var params = $('#balanceFilterForm').serialize();
            window.open('{{ route('admin.accounts-balance.export-excel') }}?' + params, '_blank');
        });

        updateBalanceAccountModeUi();
        initBalanceAccountSelect2();

     </script>
@endsection