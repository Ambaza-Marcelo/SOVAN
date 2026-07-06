@php
    $hasMaterials = $hasMaterials ?? false;
    $hasItems = $hasItems ?? false;
    $selectedTypeStore = old('type_store', $reception->type_store ?? '');
    $selectedCodeStore = old('code_store', $reception->code_store ?? '');
    $selectedDestExtraStoreId = old('dest_extra_store_id', $reception->dest_extra_store_id ?? '');
    $selectedDestinationStoreId = old('destination_store_id', $reception->destination_store_id ?? '');
@endphp

@if($hasMaterials)
<div class="col-md-12 mb-2">
    <h6 class="text-muted">@lang('messages.Stock matériels / fournitures')</h6>
</div>
<div class="col-md-4">
    <div class="form-group">
        <label for="type_store">@lang('messages.Store Type')<strong style="color: red;">*</strong></label>
        <select class="form-control material-type-store" name="type_store" id="type_store" @if($hasMaterials) required @endif>
            <option value="" disabled {{ !in_array((string) $selectedTypeStore, ['0', '1'], true) ? 'selected' : '' }}>@lang('messages.Merci de choisir')</option>
            @if (Auth::guard('admin')->user()->can('material_extra_big_inventory.view'))
            <option value="0" {{ (string) $selectedTypeStore === '0' ? 'selected' : '' }}>@lang('messages.Grand Stock')</option>
            @endif
            @if (Auth::guard('admin')->user()->can('material_big_inventory.view'))
            <option value="1" {{ (string) $selectedTypeStore === '1' ? 'selected' : '' }}>@lang('messages.Stock Intermediaire')</option>
            @endif
        </select>
    </div>
</div>
<div class="col-md-8" id="dynamic_material_store"
     data-extra-stores='@json(collect($material_big_stores ?? [])->map(fn($s) => ["id" => $s->id, "label" => trim($s->code.' '.$s->name)]))'
     data-medium-stores='@json(collect($material_medium_stores ?? [])->map(fn($s) => ["id" => $s->id, "label" => trim($s->code.' '.$s->name)]))'
     data-selected-extra="{{ $selectedDestExtraStoreId }}"
     data-selected-medium="{{ $selectedDestinationStoreId }}">
</div>
@elseif(!$hasItems)
    <input type="hidden" name="type_store" value="">
@endif

@if($hasItems)
<div class="col-md-12 mb-2 {{ $hasMaterials ? 'mt-3' : '' }}">
    <h6 class="text-muted">@lang('messages.goods_store_index_title')</h6>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label for="code_store">@lang('messages.Magasin marchandises')<strong style="color: red;">*</strong></label>
        <select class="form-control" name="code_store" id="code_store" required>
            <option value="" disabled {{ empty($selectedCodeStore) ? 'selected' : '' }}>@lang('messages.Merci de choisir')</option>
            @foreach(($goods_stores ?? []) as $goodsStore)
                <option value="{{ $goodsStore->code }}" {{ (string) $selectedCodeStore === (string) $goodsStore->code ? 'selected' : '' }}>
                    {{ $goodsStore->code }} - {{ $goodsStore->name }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">@lang('messages.reception_goods_store_help')</small>
    </div>
</div>
<input type="hidden" name="item_type_store" value="2">
@endif
