
@extends('backend.layouts.master')

@section('title')
@lang('messages.transfer') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.transfer')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-transfers.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.edit')</span></li>
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
                    <h4 class="header-title">@lang('messages.Edit Transfer'): {{ $transfer->transfer_no }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.raw-material-transfers.update', $transfer->transfer_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="type_store" value="{{ $transfer->type_store }}">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($transfer->date)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="requisition_no">@lang('messages.Requisition No')</label>
                                    <input type="text" class="form-control" id="requisition_no" name="requisition_no" value="{{ $transfer->requisition_no }}" readonly="readonly">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="{{ $transfer->type_store == 'md' ? 'origin_store_id' : 'origin_extra_store_id' }}">@lang('messages.Stock Origine')</label>
                                    <select class="form-control" name="{{ $transfer->type_store == 'md' ? 'origin_store_id' : 'origin_extra_store_id' }}" id="{{ $transfer->type_store == 'md' ? 'origin_store_id' : 'origin_extra_store_id' }}" required>
                                        <option disabled="disabled" value="">@lang('messages.Merci de choisir')</option>
                                        @foreach($origin_stores as $origin_store)
                                            <option value="{{ $origin_store->id }}" 
                                                @if($transfer->type_store == 'md' && $transfer->origin_store_id == $origin_store->id) selected
                                                @elseif($transfer->type_store == 'bg' && $transfer->origin_extra_store_id == $origin_store->id) selected
                                                @endif>
                                                {{ $origin_store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="{{ $transfer->type_store == 'md' ? 'destination_store_id' : 'dest_bg_store_id' }}">@lang('messages.Stock Destination')</label>
                                    <select class="form-control" name="{{ $transfer->type_store == 'md' ? 'destination_store_id' : 'dest_bg_store_id' }}" id="{{ $transfer->type_store == 'md' ? 'destination_store_id' : 'dest_bg_store_id' }}" required>
                                        <option disabled="disabled" value="">@lang('messages.Merci de choisir')</option>
                                        @foreach($destination_stores as $destination_store)
                                            <option value="{{ $destination_store->id }}"
                                                @if($transfer->type_store == 'md' && $transfer->destination_store_id == $destination_store->id) selected
                                                @elseif($transfer->type_store == 'bg' && $transfer->dest_bg_store_id == $destination_store->id) selected
                                                @endif>
                                                {{ $destination_store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.unit_price')</th>
                                <th>@lang('messages.Transfering Quantity')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @php
                                // Create a map of transfer details by r_material_id for quick lookup
                                $transferDetailsMap = $transferDetails->keyBy('r_material_id');
                            @endphp
                            @foreach($requisitionDetails as $requisitionDetail)
                            @php
                                $transferDetail = $transferDetailsMap->get($requisitionDetail->r_material_id);
                                // Only use transfer detail for quantity_transfered, all other data from requisition detail
                                $quantity_transfered = $transferDetail ? $transferDetail->quantity_transfered : $requisitionDetail->quantity_requisitioned;
                            @endphp
                            <tr>  
                                <td>
                                    <select class="form-control" name="r_material_id[]" required>
                                        <option value="{{ $requisitionDetail->r_material_id }}" selected>
                                            {{ $requisitionDetail->rawMaterial->name }}/{{ $requisitionDetail->rawMaterial->rawMaterialMeasurement->purchase_unit ?? 'N/A' }}
                                        </option>
                                    </select>
                                </td>  
                                <td>
                                    <input type="number" name="quantity_requisitioned[]" value="{{ $requisitionDetail->quantity_requisitioned }}" class="form-control" step="any" readonly required />
                                </td>  
                                <td>
                                    <input type="number" name="price[]" value="{{ $requisitionDetail->price }}" class="form-control" step="any" min="0" readonly required />
                                </td>
                                <td>
                                    <input type="number" name="quantity_transfered[]" value="{{ $requisitionDetail->quantity_requisitioned }}" class="form-control" min="0" max="{{ $requisitionDetail->quantity_requisitioned }}" step="any" required />
                                </td> 
                                <td>
                                    <button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>
                                </td>  
                            </tr> 
                            @endforeach 
                        </table>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')" required>{{ $transfer->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
                            <a href="{{ route('admin.raw-material-transfers.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
    $(document).on('click', '.remove-tr', function(){  
        $(this).parents('tr').remove();
    }); 

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };
</script>
@endsection

