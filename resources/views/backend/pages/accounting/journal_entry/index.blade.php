
@extends('backend.layouts.master')

@section('title')
@lang('messages.Journal Entry') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowgroup/1.1.1/css/rowGroup.dataTables.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px;
            border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
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
        #ledgerAccountsWrapper .mode-hint {
            font-size: .8rem;
        }
    </style>

    <style>
        .ledger-meta {
            border: 1px solid #e1e5eb;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            background-color: #f9fbfd;
            margin-bottom: 1.5rem;
        }

        .ledger-meta h5 {
            font-size: 1.125rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: .25rem;
        }

        .ledger-meta .meta-line {
            font-size: .875rem;
            margin-bottom: .15rem;
            color: #4d5969;
        }

        .ledger-table thead th {
            background-color: #f0f3f7;
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .05em;
            color: #2f3a4c;
            border-bottom: 2px solid #d9dde2;
            vertical-align: middle;
        }

        .ledger-table thead th[rowspan="2"] {
            vertical-align: middle;
        }

        .ledger-table thead th[colspan] {
            text-align: center;
            font-weight: 600;
        }

        .ledger-table tbody td {
            font-size: .85rem;
            padding: .6rem .75rem;
        }

        .ledger-table tbody tr.account-header-row td,
        .ledger-table tbody tr.dtrg-start td {
            background-color: #e9f1fb;
            font-weight: 600;
            border-top: 2px solid #c8d5eb;
            color: #1b3a68;
            padding: .65rem .75rem;
        }

        .ledger-table tbody tr.account-header-row td .group-title,
        .ledger-table tbody tr.dtrg-start td .group-title {
            font-size: .9rem;
        }

        .ledger-table tbody tr.account-subtotal-row td,
        .ledger-table tbody tr.dtrg-end td {
            background-color: #f7f9fc;
            font-weight: 600;
            border-top: 1px solid #d9dde2;
            border-bottom: 2px solid #d9dde2;
        }

        .ledger-table tbody tr.entry-row:nth-child(even) {
            background-color: #fcfdff;
        }

        .ledger-table .amount-cell {
            white-space: nowrap;
            word-break: keep-all;
        }

        .ledger-table .label-cell {
            white-space: normal;
            word-break: break-word;
        }

        #ledgerTable_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        #ledgerTable_wrapper .dataTables_filter input {
            margin-left: .5rem;
            padding: .35rem .6rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .ledger-table tfoot td {
            background-color: #f0f3f7;
            font-weight: 600;
            font-size: .85rem;
            border-top: 2px solid #d9dde2;
        }

        .ledger-table .text-right {
            text-align: right;
        }

        .d-none {
            display: none;
        }
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Journal Entry')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Grand livre des comptes')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('account_journal_entry.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.journal-entry.create') }}">@lang('messages.new')</a>
                        @endif
                        <button type="button" class="btn btn-success text-white" data-toggle="modal" data-target="#printLedgerModal">
                            <i class="fa fa-print"></i> Imprimer (PDF)
                        </button>
                        <button type="button" class="btn btn-outline-success text-success" data-toggle="modal" data-target="#printLedgerModal">
                            <i class="fa fa-file-excel"></i> Exporter Excel
                        </button>
                    </p>
                    <div class="clearfix"></div>
                    
                    {{-- Filtres de période pour limiter / étendre les données affichées --}}
                    <form method="GET" action="{{ route('admin.journal-entry.index') }}" class="mb-3">
                        <div class="form-row align-items-end">
                            <div class="col-md-3">
                                <label for="date_debut">@lang('messages.Date début')</label>
                                <input type="date"
                                       class="form-control"
                                       id="date_debut"
                                       name="date_debut"
                                       value="{{ request('date_debut') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_fin">@lang('messages.Date fin')</label>
                                <input type="date"
                                       class="form-control"
                                       id="date_fin"
                                       name="date_fin"
                                       value="{{ request('date_fin') }}">
                            </div>
                            <div class="col-md-3 d-flex mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary mr-2">
                                    Filtrer
                                </button>
                                <a href="{{ route('admin.journal-entry.index') }}" class="btn btn-secondary">
                                    Réinitialiser
                                </a>
                            </div>
                        </div>
                        <small class="form-text text-muted mt-1">
                            Sans période sélectionnée, seules les 100 écritures les plus récentes sont affichées.
                        </small>
                    </form>
                    <div class="ledger-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>@lang('messages.Grand livre des comptes')</h5>
                                <p class="meta-line mb-0">{{ config('app.name', 'Entreprise') }}</p>
                            </div>
                            <div class="col-md-6 text-md-right">
                                <p class="meta-line mb-1"><strong>@lang('messages.Période :')</strong> {{ $periodStartFormatted }} au {{ $periodEndFormatted }}</p>
                                <p class="meta-line mb-1"><strong>@lang('messages.Date de tirage :')</strong> {{ now()->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="ledgerTable" class="table table-bordered table-hover ledger-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="d-none">@lang('messages.Compte')</th>
                                    <th rowspan="2">@lang('messages.date') <small>(Opération)</small></th>
                                    <th rowspan="2">@lang('messages.created_at_label')</th>
                                    <th rowspan="2">@lang('messages.N° pièce')</th>
                                    <th rowspan="2">@lang('messages.N° Document')</th>
                                    <th rowspan="2">@lang('messages.Libellé écriture')</th>
                                    <th rowspan="2">@lang('messages.Let')</th>
                                    <th colspan="2" class="text-center">@lang('messages.MOUVEMENTS')</th>
                                    <th colspan="2" class="text-center">@lang('messages.SOLDES')</th>
                                </tr>
                                <tr>
                                    <th>@lang('messages.DÉBIT')</th>
                                    <th>@lang('messages.CRÉDIT')</th>
                                    <th>@lang('messages.DÉBIT')</th>
                                    <th>@lang('messages.CRÉDIT')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ledgerRows as $row)
                                    @php
                                        $ledgerSearchText = trim(implode(' ', array_filter([
                                            $row['account_label'] ?? '',
                                            $row['operation_date'] ?? '',
                                            $row['date'] ?? '',
                                            $row['created_at'] ?? '',
                                            (string) ($row['cj'] ?? ''),
                                            $row['piece'] ?? '',
                                            $row['document_no'] ?? '',
                                            $row['label'] ?? '',
                                            $row['let'] ?? '',
                                            (string) ($row['debit'] ?? ''),
                                            (string) ($row['credit'] ?? ''),
                                            (string) ($row['balance_debit'] ?? ''),
                                            (string) ($row['balance_credit'] ?? ''),
                                            number_format($row['debit'], 0, ',', ' '),
                                            number_format($row['credit'], 0, ',', ' '),
                                            number_format($row['balance_debit'], 0, ',', ' '),
                                            number_format($row['balance_credit'], 0, ',', ' '),
                                        ])));
                                    @endphp
                                    <tr class="entry-row" data-search="{{ $ledgerSearchText }}">
                                        <td class="d-none">{{ $row['account_label'] }}</td>
                                        <td>{{ $row['operation_date'] ?? $row['date'] }}</td>
                                        <td>{{ $row['created_at'] ?? '--/--/----' }}</td>
                                        <td class="amount-cell">{{ $row['cj'] }}</td>
                                        <td>{{ $row['piece'] }}</td>
                                        <td class="label-cell">{{ $row['label'] }}</td>
                                        <td class="label-cell">{{ $row['let'] }}</td>
                                        <td class="debit-amount text-right amount-cell" data-raw="{{ $row['debit'] }}">{{ number_format($row['debit'], 0, ',', ' ') }}</td>
                                        <td class="credit-amount text-right amount-cell" data-raw="{{ $row['credit'] }}">{{ number_format($row['credit'], 0, ',', ' ') }}</td>
                                        <td class="balance-debit-amount text-right amount-cell" data-raw="{{ $row['balance_debit'] }}">{{ number_format($row['balance_debit'], 0, ',', ' ') }}</td>
                                        <td class="balance-credit-amount text-right amount-cell" data-raw="{{ $row['balance_credit'] }}">{{ number_format($row['balance_credit'], 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="summary-row" style="background-color: #f8f9fa; font-weight: bold;">
                                    <td class="d-none"></td>
                                    <td colspan="6" class="text-right"><strong>@lang('messages.A reporter :')</strong></td>
                                    <td class="total-debit text-right"><strong>{{ number_format($grandTotalDebit, 0, ',', ' ') }}</strong></td>
                                    <td class="total-credit text-right"><strong>{{ number_format($grandTotalCredit, 0, ',', ' ') }}</strong></td>
                                    <td class="total-balance-debit text-right"><strong>{{ number_format($grandBalanceDebit, 0, ',', ' ') }}</strong></td>
                                    <td class="total-balance-credit text-right"><strong>{{ number_format($grandBalanceCredit, 0, ',', ' ') }}</strong></td>
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

<!-- Print Ledger Modal -->
<div class="modal fade" id="printLedgerModal" tabindex="-1" role="dialog" aria-labelledby="printLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printLedgerModalLabel">@lang('messages.Imprimer le Grand Livre')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="printLedgerForm" action="{{ route('admin.journal-entry.print') }}" method="GET" target="_blank">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_debut">@lang('messages.Date de début') <strong style="color: red;">*</strong></label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">@lang('messages.Date de fin') <strong style="color: red;">*</strong></label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                    </div>
                    <div class="form-group">
                        <label>@lang('messages.Compte(s) (optionnel)')</label>
                        <div class="btn-group btn-group-toggle btn-group-sm d-flex ledger-account-mode mb-2" data-toggle="buttons">
                            <label class="btn btn-outline-secondary active flex-fill">
                                <input type="radio" name="ledger_account_mode" value="" autocomplete="off" checked> Tous les comptes
                            </label>
                            <label class="btn btn-outline-primary flex-fill">
                                <input type="radio" name="ledger_account_mode" value="parent" autocomplete="off"> Compte(s) parent(s)
                            </label>
                            <label class="btn btn-outline-info flex-fill">
                                <input type="radio" name="ledger_account_mode" value="child" autocomplete="off"> Compte(s) enfant(s)
                            </label>
                        </div>
                        <div id="ledgerAccountsWrapper" style="display: none;">
                            <select class="form-control" id="ledger_account_keys" name="ledger_account_keys[]" multiple="multiple" disabled></select>
                            <small class="form-text text-muted mode-hint" id="ledgerAccountModeHint"></small>
                        </div>
                        <small class="form-text text-muted" id="ledgerAccountGlobalHint">Par défaut, toutes les écritures de tous les comptes sont exportées.</small>
                    </div>
                    <div class="form-group">
                        <label for="piece_document_filter">@lang('messages.N° pièce / N° document (optionnel)')</label>
                        <textarea class="form-control" id="piece_document_filter" name="piece_document_filter" rows="3" placeholder="@lang('messages.ledger_piece_document_filter_placeholder')"></textarea>
                        <small class="form-text text-muted">Filtre les écritures dont le N° pièce (champ code) ou le N° document correspond à l'un des numéros indiqués. Séparez par virgule, point-virgule ou retour à la ligne. Laissez vide pour ne pas filtrer par pièce.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.Fermer')</button>
                    <button type="submit" id="printPdfBtn" class="btn btn-success">
                        <i class="fa fa-print"></i> Imprimer PDF
                    </button>
                    <button type="button" id="exportExcelBtn" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Exporter Excel
                    </button>
                </div>
            </form>
        </div>
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
     <script src="https://cdn.datatables.net/rowgroup/1.1.1/js/dataTables.rowGroup.min.js"></script>
     <!-- Select2 JS -->
     <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
     
     <script>
         /*================================
        datatable active
        ==================================*/
        if ($('#ledgerTable').length) {
            function formatNumber(num) {
                var number = parseFloat(num) || 0;
                return number.toLocaleString('fr-FR');
            }

            function sumVisibleEntryRows(api) {
                var totalDebit = 0;
                var totalCredit = 0;

                api.rows({ search: 'applied', page: 'all' }).every(function () {
                    var $row = $(this.node());
                    if (!$row.hasClass('entry-row')) {
                        return;
                    }
                    totalDebit += parseFloat($row.find('.debit-amount').data('raw')) || 0;
                    totalCredit += parseFloat($row.find('.credit-amount').data('raw')) || 0;
                });

                var balance = totalDebit - totalCredit;

                return {
                    totalDebit: totalDebit,
                    totalCredit: totalCredit,
                    totalBalanceDebit: balance > 0 ? balance : 0,
                    totalBalanceCredit: balance < 0 ? Math.abs(balance) : 0
                };
            }

            function normalizeLedgerSearch(value) {
                return (value || '')
                    .toString()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[\s\u00A0.,;:/\\-]+/g, '');
            }

            var ledgerGlobalSearchTerm = '';

            function ledgerRowMatchesSearch(node, term) {
                if (!term) {
                    return true;
                }

                var haystack = normalizeLedgerSearch($(node).attr('data-search') || $(node).text());
                return haystack.indexOf(term) !== -1;
            }

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'ledgerTable') {
                    return true;
                }

                var term = normalizeLedgerSearch(ledgerGlobalSearchTerm);
                if (!term) {
                    return true;
                }

                var node = settings.aoData[dataIndex].nTr;
                if (!node || !$(node).hasClass('entry-row')) {
                    return true;
                }

                return ledgerRowMatchesSearch(node, term);
            });

            var table = $('#ledgerTable').DataTable({
                responsive: false,
                searching: true,
                ordering: true,
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Tous']],
                dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                columnDefs: [
                    { targets: 0, visible: false, searchable: true, orderable: true },
                    { targets: [1, 2, 3, 4, 5, 6], orderable: false },
                    { targets: [7, 8, 9, 10], className: 'text-right', orderable: false }
                ],
                rowGroup: {
                    dataSrc: 0,
                    startRender: function (rows, group) {
                        return $('<tr class="account-header-row dtrg-start"/>')
                            .append(
                                $('<td colspan="11"/>').html(
                                    '<span class="group-title"><strong>Compte : ' + group + '</strong></span>'
                                )
                            );
                    },
                    endRender: function (rows, group) {
                        var totalDebit = 0;
                        var totalCredit = 0;
                        var balanceDebit = 0;
                        var balanceCredit = 0;
                        var lastNode = null;

                        rows.every(function () {
                            var $row = $(this.node());
                            totalDebit += parseFloat($row.find('.debit-amount').data('raw')) || 0;
                            totalCredit += parseFloat($row.find('.credit-amount').data('raw')) || 0;
                            lastNode = this.node();
                        });

                        if (lastNode) {
                            var $lastRow = $(lastNode);
                            balanceDebit = parseFloat($lastRow.find('.balance-debit-amount').data('raw')) || 0;
                            balanceCredit = parseFloat($lastRow.find('.balance-credit-amount').data('raw')) || 0;
                        }

                        return $('<tr class="account-subtotal-row dtrg-end"/>')
                            .append('<td class="d-none">' + group + '</td>')
                            .append('<td colspan="6" class="text-right"><strong>Total ' + group + '</strong></td>')
                            .append('<td class="text-right amount-cell"><strong>' + formatNumber(totalDebit) + '</strong></td>')
                            .append('<td class="text-right amount-cell"><strong>' + formatNumber(totalCredit) + '</strong></td>')
                            .append('<td class="text-right amount-cell"><strong>' + formatNumber(balanceDebit) + '</strong></td>')
                            .append('<td class="text-right amount-cell"><strong>' + formatNumber(balanceCredit) + '</strong></td>');
                    }
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/French.json',
                    search: 'Rechercher :'
                }
            });

            var $ledgerSearchInput = $('#ledgerTable_wrapper .dataTables_filter input');
            $ledgerSearchInput.attr('placeholder', 'Tapez pour rechercher...');
            $ledgerSearchInput.off('.DT');
            $ledgerSearchInput.on('keyup change input', function () {
                ledgerGlobalSearchTerm = this.value;
                table.search('');
                table.draw();
            });
            
            table.on('draw', function () {
                updateFooterTotals(table);
            });

            updateFooterTotals(table);

            function updateFooterTotals(api) {
                var totals = sumVisibleEntryRows(api);

                $('.total-debit').html('<strong>' + formatNumber(totals.totalDebit) + '</strong>');
                $('.total-credit').html('<strong>' + formatNumber(totals.totalCredit) + '</strong>');
                $('.total-balance-debit').html('<strong>' + formatNumber(totals.totalBalanceDebit) + '</strong>');
                $('.total-balance-credit').html('<strong>' + formatNumber(totals.totalBalanceCredit) + '</strong>');
            }
        }

        // Handle Excel export using the same modal form (period + accounts + piece/document filter)
        $('#exportExcelBtn').on('click', function () {
            var $form = $('#printLedgerForm');
            if (!validateLedgerPrintForm()) {
                return;
            }
            var originalAction = $form.attr('action');

            $form.attr('action', '{{ route('admin.journal-entry.export-excel') }}');
            $form.attr('target', '_blank');
            $form.trigger('submit');

            // Restore original PDF action for next time
            $form.attr('action', originalAction);
        });

        function preventBack() {
            window.history.forward();
        }
        setTimeout("preventBack()", 0);
        window.onunload = function () {
            null
        };

        function validateLedgerPrintForm() {
            var scope = getLedgerAccountScope();
            if (scope !== '') {
                var selected = $('#ledger_account_keys').val();
                if (!selected || selected.length === 0) {
                    alert('Veuillez sélectionner au moins un compte ' + (scope === 'parent' ? 'parent' : 'enfant') + '.');
                    return false;
                }
            }
            return true;
        }

        $('#printLedgerForm').on('submit', function (e) {
            if (!validateLedgerPrintForm()) {
                e.preventDefault();
            }
        });

        // Comptes : mode parent OU enfant (multi-sélection, recherche dynamique)
        var ledgerAccountHints = {
            parent: 'Sélectionnez un ou plusieurs comptes parents — toutes les écritures de leurs comptes enfants seront incluses.',
            child: 'Sélectionnez un ou plusieurs comptes enfants individuellement.'
        };
        var ledgerAccountPlaceholders = {
            parent: 'Rechercher un ou plusieurs comptes parents…',
            child: 'Rechercher un ou plusieurs comptes enfants…'
        };

        function getLedgerAccountScope() {
            return $('input[name="ledger_account_mode"]:checked').val() || '';
        }

        function formatLedgerAccountOption(data) {
            if (!data.id) {
                return data.text;
            }
            if (String(data.id).indexOf('parent-') === 0) {
                return $('<span class="ledger-opt-parent"><i class="fa fa-folder-open-o"></i> ' + data.text + '</span>');
            }
            return $('<span class="ledger-opt-child"><i class="fa fa-file-text-o"></i> ' + data.text + '</span>');
        }

        function formatLedgerAccountSelection(data) {
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

        function updateLedgerAccountModeUi() {
            var scope = getLedgerAccountScope();
            var $wrapper = $('#ledgerAccountsWrapper');
            var $select = $('#ledger_account_keys');
            var $globalHint = $('#ledgerAccountGlobalHint');

            if (scope === '') {
                $wrapper.hide();
                $select.prop('disabled', true);
                $globalHint.show();
                $('#ledgerAccountModeHint').text('');
            } else {
                $wrapper.show();
                $select.prop('disabled', false);
                $globalHint.hide();
                $('#ledgerAccountModeHint').text(ledgerAccountHints[scope] || '');
            }
        }

        function initLedgerAccountSelect2() {
            var $select = $('#ledger_account_keys');
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            var scope = getLedgerAccountScope();
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
                            scope: getLedgerAccountScope()
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: false
                },
                placeholder: ledgerAccountPlaceholders[scope] || 'Rechercher…',
                minimumInputLength: 0,
                allowClear: true,
                closeOnSelect: false,
                theme: 'default',
                width: '100%',
                dropdownParent: $('#printLedgerModal'),
                templateResult: formatLedgerAccountOption,
                templateSelection: formatLedgerAccountSelection
            });
        }

        $('input[name="ledger_account_mode"]').on('change', function () {
            var $select = $('#ledger_account_keys');
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.val(null).trigger('change');
                $select.select2('destroy');
            }
            updateLedgerAccountModeUi();
            initLedgerAccountSelect2();
        });

        $(document).on('select2:open', '#ledger_account_keys', function () {
            var $search = $('.select2-container--open .select2-search__field');
            if ($search.length && !$search.val()) {
                $search.trigger('input');
            }
        });

        $(document).on('select2:selecting', '#ledger_account_keys', function (e) {
            var scope = getLedgerAccountScope();
            var id = String(e.params.args.data.id || '');
            if (scope === 'parent' && id.indexOf('child-') === 0) {
                e.preventDefault();
            }
            if (scope === 'child' && id.indexOf('parent-') === 0) {
                e.preventDefault();
            }
        });

        updateLedgerAccountModeUi();
     </script>
@endsection