
@extends('backend.layouts.master')

@section('title')
@lang('messages.inventory') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label { text-transform: capitalize; }
    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffc107;
        color: #856404;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('admin-content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.edit') @lang('messages.inventory') - Stock des marchandises</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.goods-store-inventory.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.edit')</span></li>
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
                    <h4 class="header-title">@lang('messages.edit') @lang('messages.inventory')</h4>
                    @include('backend.layouts.partials.messages')

                    @if($inventory->status == 2)
                        <div class="alert alert-warning">
                            <strong>@lang('messages.Attention!')</strong> Cet inventaire est validé et ne peut pas être modifié.
                        </div>
                    @else
                    <form action="{{ route('admin.goods-store-inventory.update', $inventory->inventory_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="code_store" value="{{ $code }}">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($inventory->date)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="title">@lang('messages.title')</label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ $inventory->title }}" required>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered" id="dynamicTable">
                            <thead>
                                <tr class="bg-secondary">
                                    <th width="5%">#</th>
                                    <th>@lang('messages.item')</th>
                                    <th>@lang('messages.Quantité théorique')</th>
                                    <th>@lang('messages.CUMP')</th>
                                    <th>@lang('messages.Quantité physique')</th>
                                    <th>@lang('messages.Nouveau CUMP')</th>
                                    <th>@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inventoryDetails as $detail)
                                <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>
                                        <select class="form-control" name="item_id[]" required>
                                            <option value="{{ $detail->item_id }}" selected>{{ $detail->item->name ?? '' }} / {{ $detail->item->code ?? '' }}</option>
                                            @foreach($allItems as $item)
                                                @if($item->id != $detail->item_id)
                                                    <option value="{{ $item->id }}">{{ $item->name }} / {{ $item->code }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="quantity[]" value="{{ $detail->quantity ?? 0 }}" class="form-control" step="any" min="0" readonly /></td>
                                    <td><input type="number" name="purchase_price[]" value="{{ $detail->purchase_price ?? $detail->cump ?? 0 }}" class="form-control" step="any" readonly /></td>
                                    <td><input type="number" name="new_quantity[]" value="{{ $detail->new_quantity }}" class="form-control" step="any" min="0" required /></td>
                                    <td><input type="number" name="new_purchase_price[]" value="{{ $detail->new_cump ?? $detail->new_purchase_price }}" class="form-control" step="any" min="0" required /></td>
                                    <td><button type="button" class="btn btn-danger remove-tr"><i class="fa fa-trash-o"></i> @lang('messages.Supprimer')</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" name="add" id="add" class="btn btn-primary">@lang('messages.addmore')</button>
                        <div class="col-lg-12 mt-3">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" required>{{ $inventory->description }}</textarea>
                        </div>
                        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
    var i18n = {
        searchItem: @json(__('messages.Tapez pour rechercher un article...')),
        quantity: @json(__('messages.Enter quantity')),
        costPrice: @json(__('messages.Enter cost price')),
        purchasePrice: @json(__('messages.Enter purchase price')),
        unitPrice: @json(__('messages.Enter Unit price')),
        delete: @json(__('messages.delete'))
    };
</script>
<script type="text/javascript">
    $("#add").click(function(){
        var markup = "<tr>"+
            "<td></td>"+
            "<td><select class='form-control' name='item_id[]' required><option value=''>@lang('messages.Choisir')</option>@foreach($allItems as $item)<option value='{{ $item->id }}'>{{ $item->name }} / {{ $item->code }}</option>@endforeach</select></td>"+
            "<td><input type='number' name='quantity[]' class='form-control' step='any' min='0' readonly /></td>"+
            "<td><input type='number' name='purchase_price[]' class='form-control' step='any' readonly /></td>"+
            "<td><input type='number' name='new_quantity[]' class='form-control' step='any' min='0' required /></td>"+
            "<td><input type='number' name='new_purchase_price[]' class='form-control' step='any' min='0' required /></td>"+
            "<td><button type='button' class='btn btn-danger remove-tr'><i class='fa fa-trash-o'></i> @lang('messages.Supprimer')</button></td>"+
        "</tr>";
        $("#dynamicTable tbody").append(markup);
    });
    $(document).on('click', '.remove-tr', function(){
        $(this).closest('tr').remove();
    });
</script>
@endsection
