@php
    $filterRoute = $filterRoute ?? request()->route()->getName();
    $exportExcelRoute = $exportExcelRoute ?? null;
    $exportPdfRoute = $exportPdfRoute ?? null;
    $stores = $stores ?? collect();
    $articles = $articles ?? collect();
    $showDocumentNo = $showDocumentNo ?? false;
    $showPdf = $showPdf ?? (bool) $exportPdfRoute;
    $modalId = $modalId ?? 'stockReportModal';
    $storeContext = $storeContext ?? 'finished_goods';

    $defaultStore = $stores->firstWhere('code', request('code_store')) ?? $stores->first();
    $selectedStoreCode = request('code_store', $defaultStore->code ?? '');
    $selectedStoreLabel = $defaultStore
        ? trim(($defaultStore->code ?? '') . ' - ' . ($defaultStore->name ?? ''))
        : '';

    $selectedArticle = $articles->firstWhere('id', (int) request('article_id'));
    $selectedArticleLabel = $selectedArticle
        ? trim(($selectedArticle->code ?? '') . ' - ' . ($selectedArticle->name ?? ''))
        : '';

    $storeOptions = $stores->map(function ($store) {
        return [
            'id' => $store->code,
            'code' => $store->code,
            'name' => $store->name,
            'text' => trim(($store->code ?? '') . ' - ' . ($store->name ?? '')),
        ];
    })->values();
@endphp

@include('backend.layouts.partials.ajax_autocomplete')

<style>
    #{{ $modalId }} .modal-content,
    #{{ $modalId }} .modal-body,
    #{{ $modalId }} .modal-dialog {
        overflow: visible;
    }
    #{{ $modalId }} .ajax-ac-wrap { position: relative; z-index: 1; }
    #{{ $modalId }} .ajax-ac-results {
        z-index: 9999;
        position: absolute;
        width: 100%;
    }
    #{{ $modalId }} .form-group:focus-within { z-index: 2; }
</style>

<div class="mb-3">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#{{ $modalId }}">
        <i class="fa fa-filter"></i> @lang('messages.stock_report_filter_export_btn')
    </button>
    @if(!request('apply_filter') && !request('date_debut'))
        <span class="text-muted ml-2">@lang('messages.stock_report_recent_records_hint')</span>
    @endif
</div>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">@lang('messages.stock_report_modal_title')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    @lang('messages.stock_report_dates_required_help')
                </p>

                <form action="{{ route($filterRoute) }}" method="GET" id="{{ $modalId }}_filter_form">
                    <input type="hidden" name="apply_filter" value="1">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="{{ $modalId }}_date_debut">@lang('messages.Date Debut')</label>
                            <input type="date" class="form-control" id="{{ $modalId }}_date_debut" name="date_debut" value="{{ request('date_debut') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="{{ $modalId }}_date_fin">@lang('messages.Date Fin')</label>
                            <input type="date" class="form-control" id="{{ $modalId }}_date_fin" name="date_fin" value="{{ request('date_fin') }}" required>
                        </div>

                        @if($stores->count())
                        <div class="col-md-6 form-group stock-report-ac-group">
                            <label for="{{ $modalId }}_code_store_search">@lang('messages.Stock')</label>
                            <input type="text"
                                   class="form-control stock-report-store-search"
                                   id="{{ $modalId }}_code_store_search"
                                   value="{{ $selectedStoreLabel }}"
                                   placeholder="@lang('messages.Tous les stocks')"
                                   autocomplete="off">
                            <input type="hidden" name="code_store" id="{{ $modalId }}_code_store" value="{{ $selectedStoreCode }}">
                            <small class="text-muted">@lang('messages.stock_report_store_preselect_help')</small>
                        </div>
                        @endif

                        <div class="col-md-6 form-group stock-report-ac-group">
                            <label for="{{ $modalId }}_article_search">@lang('messages.item')</label>
                            <input type="text"
                                   class="form-control stock-report-article-search"
                                   id="{{ $modalId }}_article_search"
                                   value="{{ $selectedArticleLabel }}"
                                   placeholder="@lang('messages.Tous les articles')"
                                   autocomplete="off">
                            <input type="hidden" name="article_id" id="{{ $modalId }}_article_id" value="{{ request('article_id') }}">
                            <small class="text-muted d-block">@lang('messages.stock_report_article_search_help')</small>
                            <small class="text-warning d-none" id="{{ $modalId }}_article_store_warning">@lang('messages.stock_report_select_store_for_article')</small>
                        </div>

                        @if($showDocumentNo)
                        <div class="col-md-12 form-group">
                            <label for="{{ $modalId }}_document_no">@lang('messages.No Document')</label>
                            <input type="text" class="form-control" id="{{ $modalId }}_document_no" name="document_no" value="{{ request('document_no') }}" placeholder="@lang('messages.stock_report_document_no_placeholder')">
                        </div>
                        @endif
                    </div>
                </form>

                @if($exportExcelRoute)
                <form action="{{ route($exportExcelRoute) }}" method="GET" id="{{ $modalId }}_excel_form" class="d-none">
                    <input type="hidden" name="export_mode" value="1">
                </form>
                @endif

                @if($showPdf && $exportPdfRoute)
                <form action="{{ route($exportPdfRoute) }}" method="GET" id="{{ $modalId }}_pdf_form" class="d-none">
                    <input type="hidden" name="export_mode" value="1">
                </form>
                @endif
            </div>
            <div class="modal-footer flex-wrap">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="submit" form="{{ $modalId }}_filter_form" class="btn btn-info">@lang('messages.stock_report_show_lines_btn')</button>
                @if($exportExcelRoute)
                    <button type="button" class="btn btn-success" onclick="submitStockReportExport('{{ $modalId }}', 'excel')">@lang('messages.export_excel')</button>
                @endif
                @if($showPdf && $exportPdfRoute)
                    <button type="button" class="btn btn-success" onclick="submitStockReportExport('{{ $modalId }}', 'pdf')">@lang('messages.Export PDF')</button>
                @endif
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function(){
    if (window.__stockReportModalInit) return;
    window.__stockReportModalInit = true;

    var stockReportI18n = {
        noResults: @json(__('messages.Aucun résultat'))
    };

    function escHtml(s){
        return (s == null ? '' : String(s))
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

    window.resolveStockReportStoreCode = function($storeHidden, $storeInput, storeItems){
        var code = ($storeHidden.val() || '').trim();
        if (code) return code;

        var label = ($storeInput.val() || '').trim().toLowerCase();
        if (!label || !storeItems || !storeItems.length) return '';

        var exact = null;
        for (var i = 0; i < storeItems.length; i++) {
            if ((storeItems[i].text || '').toLowerCase() === label) {
                exact = storeItems[i];
                break;
            }
        }
        if (exact) return exact.code || exact.id || '';

        var matches = storeItems.filter(function(item){
            var hay = ((item.text || '') + ' ' + (item.code || '') + ' ' + (item.name || '')).toLowerCase();
            return hay.indexOf(label) !== -1;
        });
        return matches.length === 1 ? (matches[0].code || matches[0].id || '') : '';
    };

    window.initStockReportModal = function(modalId, storeItems, storeContext){
        if (typeof jQuery === 'undefined' || typeof attachAjaxAutocomplete !== 'function' || typeof attachLocalAutocomplete !== 'function') {
            return false;
        }

        var $modal = $('#' + modalId);
        var $storeInput = $('#' + modalId + '_code_store_search');
        var $storeHidden = $('#' + modalId + '_code_store');
        var $articleInput = $('#' + modalId + '_article_search');
        var $articleHidden = $('#' + modalId + '_article_id');

        if ($modal.data('stockReportReady')) {
            return true;
        }

        function clearArticleSelection(){
            $articleInput.val('');
            $articleHidden.val('');
        }

        if ($storeInput.length && storeItems && storeItems.length){
            attachLocalAutocomplete({
                $input: $storeInput,
                $hidden: $storeHidden,
                items: storeItems,
                minChars: 1,
                renderItem: function(item){
                    return '<div><strong>' + escHtml(item.text) + '</strong></div>';
                },
                onSelect: function(){
                    clearArticleSelection();
                }
            });

            $storeInput.on('input.stockReportStore', function(){
                if (!$storeHidden.val()) {
                    clearArticleSelection();
                }
            });
        }

        if ($articleInput.length && storeContext){
            var articleStoreWarning = $('#' + modalId + '_article_store_warning');
            var articleSearchAlert = @json(__('messages.stock_report_select_store_for_article'));

            attachAjaxAutocomplete({
                $input: $articleInput,
                $hidden: $articleHidden,
                url: @json(route('admin.autocomplete.stock-report-articles')),
                minChars: 1,
                validateBeforeSearch: function(){
                    var code = resolveStockReportStoreCode($storeHidden, $storeInput, storeItems);
                    if (code) {
                        articleStoreWarning.addClass('d-none');
                        return true;
                    }
                    articleStoreWarning.removeClass('d-none');
                    return false;
                },
                extraParams: function(){
                    return {
                        code_store: resolveStockReportStoreCode($storeHidden, $storeInput, storeItems),
                        store_context: storeContext
                    };
                },
                renderItem: function(item){
                    return '<div><strong>' + escHtml(item.text || item.name || '') + '</strong></div>';
                },
                onSelect: function(item){
                    $articleHidden.val(item.id || '');
                }
            });

            $articleInput.on('focus.stockReport', function(){
                var code = resolveStockReportStoreCode($storeHidden, $storeInput, storeItems);
                if (!code) {
                    articleStoreWarning.removeClass('d-none');
                    return;
                }
                if (($articleInput.val() || '').trim().length >= 1) {
                    $articleInput.trigger('input');
                }
            });

            $articleInput.on('input.stockReportWarn', function(){
                var code = resolveStockReportStoreCode($storeHidden, $storeInput, storeItems);
                if (!code && ($articleInput.val() || '').trim().length >= 1) {
                    articleStoreWarning.removeClass('d-none');
                } else if (code) {
                    articleStoreWarning.addClass('d-none');
                }
            });
        }

        $modal.data('stockReportReady', true);
        return true;
    };

    window.submitStockReportExport = function(modalId, type) {
        var filterForm = document.getElementById(modalId + '_filter_form');
        var targetForm = document.getElementById(modalId + '_' + type + '_form');
        if (!filterForm || !targetForm) return;

        var start = filterForm.querySelector('[name="date_debut"]');
        var end = filterForm.querySelector('[name="date_fin"]');
        if (!start || !end || !start.value || !end.value) {
            alert(@json(__('messages.export_select_date_range')));
            return;
        }

        targetForm.querySelectorAll('.sync-field').forEach(function(el){ el.remove(); });

        ['date_debut', 'date_fin', 'code_store', 'article_id', 'document_no'].forEach(function(name) {
            var input = filterForm.querySelector('[name="' + name + '"]');
            if (!input || !input.value) return;
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.value = input.value;
            hidden.className = 'sync-field';
            targetForm.appendChild(hidden);
            if (type === 'pdf' && name === 'date_debut') {
                var h2 = document.createElement('input');
                h2.type = 'hidden';
                h2.name = 'start_date';
                h2.value = input.value;
                h2.className = 'sync-field';
                targetForm.appendChild(h2);
            }
            if (type === 'pdf' && name === 'date_fin') {
                var h3 = document.createElement('input');
                h3.type = 'hidden';
                h3.name = 'end_date';
                h3.value = input.value;
                h3.className = 'sync-field';
                targetForm.appendChild(h3);
            }
        });

        targetForm.submit();
    };
})();
</script>
@endpush
@endonce

@push('scripts')
<script>
(function(){
    var modalId = @json($modalId);
    var storeItems = @json($storeOptions);
    var storeContext = @json($storeContext);

    function bootStockReportModal(){
        if (typeof jQuery === 'undefined') return;
        if (initStockReportModal(modalId, storeItems, storeContext)) return;
        setTimeout(bootStockReportModal, 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootStockReportModal);
    } else {
        bootStockReportModal();
    }
})();
</script>
@endpush
