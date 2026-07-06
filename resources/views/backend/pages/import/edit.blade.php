@extends('backend.layouts.master')

@section('title')
Modifier importation — @lang('messages.admin_panel')
@endsection

@section('styles')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Modifier {{ $import->import_no }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.imports.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Modifier')</span></li>
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
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.imports.update', $import->import_no) }}" method="POST" id="importForm">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($import->date)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="import_store">@lang('messages.Magasin') <strong class="text-danger">*</strong></label>
                                    <select class="form-control" name="import_store" id="import_store" required>
                                        <option value="" disabled>@lang('messages.Choisir le magasin')</option>
                                        <optgroup label="Marchandises">
                                            @foreach($goods_stores as $store)
                                                <option value="goods:{{ $store->id }}" @if(($selected_import_store ?? '') === 'goods:'.$store->id) selected @endif>{{ $store->code }} — {{ $store->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Produits finis">
                                            @foreach($finished_goods_stores as $store)
                                                <option value="finished_goods:{{ $store->id }}" @if(($selected_import_store ?? '') === 'finished_goods:'.$store->id) selected @endif>{{ $store->code }} — {{ $store->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Produits finis SM">
                                            @foreach($finished_goods_sm_stores as $store)
                                                <option value="finished_goods_sm:{{ $store->id }}" @if(($selected_import_store ?? '') === 'finished_goods_sm:'.$store->id) selected @endif>{{ $store->code }} — {{ $store->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="item_purchase_or_sale_currency">@lang('messages.Devise')</label>
                                    <input type="text" class="form-control" id="item_purchase_or_sale_currency" name="item_purchase_or_sale_currency" value="{{ $import->item_purchase_or_sale_currency ?? 'BIF' }}" maxlength="10">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="handingover">@lang('messages.Remettant')</label>
                                    <input type="text" class="form-control" id="handingover" name="handingover" value="{{ $import->handingover }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="origin">@lang('messages.Origine')</label>
                                    <input type="text" class="form-control" id="origin" name="origin" value="{{ $import->origin }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="receptionist">@lang('messages.receptionist')</label>
                                    <input type="text" class="form-control" id="receptionist" name="receptionist" value="{{ $import->receptionist }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reference_dmc">@lang('messages.Référence DMC')</label>
                                    <input type="text" class="form-control" id="reference_dmc" name="reference_dmc" value="{{ $import->reference_dmc }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rubrique_tarifaire">@lang('messages.Rubrique tarifaire')</label>
                                    <input type="text" class="form-control" id="rubrique_tarifaire" name="rubrique_tarifaire" value="{{ $import->rubrique_tarifaire }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transport_cost">@lang('messages.Frais transport')</label>
                                    <input type="number" step="any" class="form-control cost-global" id="transport_cost" name="transport_cost" value="{{ old('transport_cost', $import->transport_cost ?? 0) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="clearance_cost">@lang('messages.Frais dédouanement')</label>
                                    <input type="number" step="any" class="form-control cost-global" id="clearance_cost" name="clearance_cost" value="{{ old('clearance_cost', $import->clearance_cost ?? 0) }}" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                        <table class="table table-bordered" id="dynamicTable">
                            <thead>
                                <tr>
                                    <th>@lang('messages.Article')</th>
                                    <th>@lang('messages.Quantité')</th>
                                    <th>@lang('messages.Unité')</th>
                                    <th>@lang('messages.Nbr / paquet')</th>
                                    <th>@lang('messages.Desc. paquet')</th>
                                    <th>@lang('messages.Prix d’achat')</th>
                                    <th>@lang('messages.Total ligne')</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importDetails as $detail)
                                @php
                                    $label = $detail->item
                                        ? ($detail->item->name . '/' . number_format($detail->item->cump ?? 0, 0, ',', ' ') . '/' . ($detail->item->code ?? ''))
                                        : '';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="ajax-ac-wrap">
                                            <input type="text" class="form-control item_search" value="{{ $label }}" autocomplete="off" required>
                                            <input type="hidden" name="item_id[]" class="item_id" value="{{ $detail->item_id }}">
                                        </div>
                                    </td>
                                    <td><input type="number" name="item_quantity[]" class="form-control qty-input" step="any" min="0" value="{{ $detail->item_quantity }}" required></td>
                                    <td><input type="text" name="item_measurement_unit[]" class="form-control" value="{{ $detail->item_measurement_unit }}" required></td>
                                    <td><input type="number" name="nombre_par_paquet[]" class="form-control" step="any" min="0" value="{{ $detail->nombre_par_paquet }}" placeholder="ex. 300"></td>
                                    <td><input type="text" name="description_paquet[]" class="form-control" value="{{ $detail->description_paquet }}" placeholder="ex. Jerrycans"></td>
                                    <td><input type="number" name="purchase_price[]" class="form-control price-input" step="any" min="0" value="{{ $detail->purchase_price }}" required></td>
                                    <td><input type="text" class="form-control line-total-display" readonly tabindex="-1" value="0"></td>
                                    <td>
                                        @if($loop->first)
                                            <button type="button" class="btn btn-success btn-add-import-row">@lang('messages.addmore')</button>
                                        @else
                                            <button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4 offset-md-8">
                                <div class="border p-3 bg-light rounded">
                                    <div><strong>@lang('messages.Total achat lignes :')</strong> <span id="sum_lines_display">0</span></div>
                                    <div><strong>@lang('messages.Total document :')</strong> <span id="total_amount_display">0</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" rows="3" required>{{ $import->description }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
<script>
(function () {
    var itemsSearchUrl = {!! json_encode(route('admin.imports.items-search')) !!};

    function formatNum(n) {
        var x = parseFloat(n);
        if (isNaN(x)) x = 0;
        return x.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
    }

    function rowLineTotal($row) {
        var q = parseFloat($row.find('.qty-input').val()) || 0;
        var p = parseFloat($row.find('.price-input').val()) || 0;
        return q * p;
    }

    function recalcTotals() {
        var sum = 0;
        $('#dynamicTable tbody tr').each(function () {
            var lt = rowLineTotal($(this));
            $(this).find('.line-total-display').val(formatNum(lt));
            sum += lt;
        });
        var tr = parseFloat($('#transport_cost').val()) || 0;
        var cl = parseFloat($('#clearance_cost').val()) || 0;
        $('#sum_lines_display').text(formatNum(sum));
        $('#total_amount_display').text(formatNum(sum + tr + cl));
    }

    function getImportStoreParams() {
        var v = $('#import_store').val();
        if (!v || v.indexOf(':') < 0) return null;
        var parts = v.split(':');
        return { store_kind: parts[0], store_id: parts[1] };
    }

    function initItemAutocomplete($wrap) {
        attachAjaxAutocomplete({
            $input: $wrap.find('.item_search'),
            $hidden: $wrap.find('.item_id'),
            url: itemsSearchUrl,
            minChars: 1,
            validateBeforeSearch: function() {
                var params = getImportStoreParams();
                return params && params.store_id && params.store_id !== '0';
            },
            extraParams: function() {
                var params = getImportStoreParams();
                return params ? { store_kind: params.store_kind, store_id: params.store_id, limit: 20 } : {};
            },
            renderItem: function(item) {
                return '<div><strong>' + (item.text || item.label || '') + '</strong></div>';
            },
            onSelect: function(item) {
                $wrap.closest('tr').find('.price-input').val(item.cump ?? 0);
                recalcTotals();
            }
        });
    }

    $(document).ready(function () {
        $('#dynamicTable .ajax-ac-wrap').each(function () { initItemAutocomplete($(this)); });
        recalcTotals();
    });

    $(document).on('input', '.qty-input, .price-input', recalcTotals);
    $(document).on('input', '.cost-global', recalcTotals);

    $('#import_store').on('change', function () {
        $('#dynamicTable tbody .item_search').val('');
        $('#dynamicTable tbody .item_id').val('');
    });

    $(document).on('click', '.btn-add-import-row', function () {
        var markup = '<tr>' +
            '<td><div class="ajax-ac-wrap">' +
            '<input type="text" class="form-control item_search" placeholder="Tapez pour rechercher…" autocomplete="off" required>' +
            '<input type="hidden" name="item_id[]" class="item_id">' +
            '</div></td>' +
            '<td><input type="number" name="item_quantity[]" class="form-control qty-input" step="any" min="0" required></td>' +
            '<td><input type="text" name="item_measurement_unit[]" class="form-control" placeholder="ex. bouteille" required></td>' +
            '<td><input type="number" name="nombre_par_paquet[]" class="form-control" step="any" min="0" placeholder="ex. 300"></td>' +
            '<td><input type="text" name="description_paquet[]" class="form-control" placeholder="ex. Jerrycans"></td>' +
            '<td><input type="number" name="purchase_price[]" class="form-control price-input" step="any" min="0" required></td>' +
            '<td><input type="text" class="form-control line-total-display" readonly tabindex="-1" value="0"></td>' +
            '<td><button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button></td></tr>';
        var $row = $(markup);
        $('#dynamicTable tbody').append($row);
        initItemAutocomplete($row.find('.ajax-ac-wrap'));
        recalcTotals();
    });

    $(document).on('click', '.remove-tr', function () {
        if ($('#dynamicTable tbody tr').length <= 1) { alert('Au moins une ligne est requise.'); return; }
        $(this).closest('tr').remove();
        recalcTotals();
    });
})();
</script>
@endsection
