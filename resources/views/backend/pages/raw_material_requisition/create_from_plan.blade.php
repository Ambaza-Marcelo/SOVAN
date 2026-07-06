@extends('backend.layouts.master')

@section('title')
@lang('messages.Requête depuis le plan') {{ $plan->prodution_plan_number }} - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label { text-transform: capitalize; }
    .autocomplete-wrapper { position: relative; }
    .autocomplete-list { position: absolute; border: 1px solid #d4d4d4; z-index: 99; max-height: 200px; overflow-y: auto; background: #fff; width: 100%; display: none; }
    .autocomplete-list div { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
    .autocomplete-list div:hover { background-color: #e9e9e9; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Requête depuis le plan') {{ $plan->prodution_plan_number }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.production-plan.index') }}">@lang('messages.Plans de production')</a></li>
                    <li><a href="{{ route('admin.production-plan.show', $plan->prodution_plan_number) }}">{{ $plan->prodution_plan_number }}</a></li>
                    <li><span>@lang('messages.Créer requête')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">@include('backend.layouts.partials.logout')</div>
    </div>
</div>
<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">{{ __('messages.requisition_from_plan_header', ['plan' => $plan->prodution_plan_number]) }}</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.raw-material-requisitions.store') }}" method="post" id="dynamic_form">
                        @csrf
                        <input type="hidden" name="type_store" value="md">
                        <input type="hidden" name="production_plan_number" value="{{ $plan->prodution_plan_number }}">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered" id="dynamicTable">
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($planDetails as $d)
                            <tr>
                                <td>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" class="form-control food_search" value="{{ $d->rawMaterial ? $d->rawMaterial->name . ' (' . ($d->rawMaterial->rawMaterialMeasurement ? $d->rawMaterial->rawMaterialMeasurement->purchase_unit : '') . ')' : __('messages.not_available_short') }}" placeholder="{{ __('messages.search_item_placeholder') }}" autocomplete="off" required>
                                        <input type="hidden" name="r_material_id[]" class="r_material_id" value="{{ $d->r_material_id }}">
                                        <div class="autocomplete-list food_search-list"></div>
                                    </div>
                                </td>
                                <td><input type="number" name="quantity_requisitioned[]" class="form-control" min="0" step="any" value="{{ $d->material_quantity }}" required></td>
                                <td><button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button></td>
                            </tr>
                            @endforeach
                        </table>
                        <p class="text-muted">@lang('messages.requisition_from_plan_help')</p>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" required>{{ __('messages.requisition_from_plan_default_description', ['plan' => $plan->prodution_plan_number]) }}</textarea>
                        </div>
                        <div style="margin-top: 15px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.save')</button>
                            <a href="{{ route('admin.production-plan.show', $plan->prodution_plan_number) }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
var foods = [
    @foreach($raw_materials as $rm)
    { id: {{ $rm->id }}, name: {!! json_encode($rm->name . '/' . ($rm->rawMaterialMeasurement ? $rm->rawMaterialMeasurement->purchase_unit : '')) !!} }@if(!$loop->last),@endif
    @endforeach
];
function initAutocomplete(wrapper) {
    var input = wrapper.find('.food_search');
    var hidden = wrapper.find('.r_material_id');
    var list = wrapper.find('.food_search-list');
    input.on('input', function() {
        var q = $(this).val().toLowerCase();
        list.empty();
        if (!q) { list.hide(); return; }
        foods.filter(function(f) { return f.name.toLowerCase().includes(q); }).forEach(function(f) {
            var div = $('<div></div>').text(f.name).attr('data-id', f.id).attr('data-name', f.name);
            div.on('click', function() { input.val($(this).attr('data-name')); hidden.val($(this).attr('data-id')); list.hide(); });
            list.append(div);
        });
        if (list.children().length) list.show();
    });
}
$(document).ready(function() {
    $('.autocomplete-wrapper').each(function() { initAutocomplete($(this)); });
});
$(document).on('click', '.remove-tr', function() { $(this).closest('tr').remove(); });
$(document).on('click', function(e) { if (!$(e.target).closest('.autocomplete-wrapper').length) $('.autocomplete-list').hide(); });
</script>
@endsection
