
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
                    <li><a href="{{ route('admin.material-transfers.index') }}">@lang('messages.list')</a></li>
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
                    
                    <form action="{{ route('admin.material-transfers.update', $transfer->transfer_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="type_store" value="{{ $transfer->type_store }}">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" readonly required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="requisition_no">@lang('messages.Requisition No')</label>
                                <input type="text" class="form-control" id="requisition_no" name="requisition_no" value="{{$requisition_no}}" readonly="readonly">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        @if($transfer->type_store == 'md')
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="origin_store_id">@lang('messages.Stock Origine')</label>
                                <select class="form-control" name="origin_store_id" id="origin_store_id" required readonly disabled>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($origin_stores as $origin_store)
                                    <option value="{{$origin_store->id}}" {{ $transfer->origin_store_id == $origin_store->id ? 'selected' : '' }}>{{$origin_store->name}}</option>
                                @endforeach
                             </select>
                             <input type="hidden" name="origin_store_id" value="{{ $transfer->origin_store_id }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="destination_store_id">@lang('messages.Stock Destination')</label>
                                <select class="form-control" name="destination_store_id" id="destination_store_id" required readonly disabled>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($destination_stores as $destination_store)
                                    <option value="{{$destination_store->id}}" {{ $transfer->destination_store_id == $destination_store->id ? 'selected' : '' }}>{{$destination_store->name}}</option>
                                @endforeach
                             </select>
                             <input type="hidden" name="destination_store_id" value="{{ $transfer->destination_store_id }}">
                            </div>
                        </div>
                        @else
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="origin_extra_store_id">@lang('messages.Stock Origine')</label>
                                <select class="form-control" name="origin_extra_store_id" id="origin_extra_store_id" required readonly disabled>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($origin_extra_stores as $origin_store)
                                    <option value="{{$origin_store->id}}" {{ $transfer->origin_extra_store_id == $origin_store->id ? 'selected' : '' }}>{{$origin_store->name}}</option>
                                @endforeach
                             </select>
                             <input type="hidden" name="origin_extra_store_id" value="{{ $transfer->origin_extra_store_id }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="dest_bg_store_id">@lang('messages.Stock Destination')</label>
                                <select class="form-control" name="dest_bg_store_id" id="dest_bg_store_id" required readonly disabled>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($destination_bg_stores as $destination_store)
                                    <option value="{{$destination_store->id}}" {{ $transfer->dest_bg_store_id == $destination_store->id ? 'selected' : '' }}>{{$destination_store->name}}</option>
                                @endforeach
                             </select>
                             <input type="hidden" name="dest_bg_store_id" value="{{ $transfer->dest_bg_store_id }}">
                            </div>
                        </div>
                        @endif
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.unit_price')</th>
                                <th>@lang('messages.Transfering Quantity')</th>
                            </tr>
                            @foreach($datas as $data)
                            <tr>  
                                <td> <select class="form-control" name="material_id[]" id="material_id" readonly disabled>
                                <option value="{{ $data->material_id }}" class="form-control" selected>{{ $data->material->name }}/{{ $data->material->code }}</option>
                                </select>
                                <input type="hidden" name="material_id[]" value="{{ $data->material_id }}">
                                </td>  
                                <td><input type="number" name="quantity_requisitioned[]" value="{{ $data->quantity_requisitioned }}" class="form-control"  readonly /></td>  
                                <td><input type="number" name="price[]" value="{{$data->price}}" class="form-control" step="any" min="0" readonly /></td>
                                <td><input type="number" name="quantity_transfered[]" value="{{ $data->quantity_requisitioned }}" class="form-control" min="0" max="{{ $data->quantity_requisitioned }}" readonly /></td>  
                            </tr> 
                            @endforeach 
                        </table>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">{{ $transfer->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
   

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

</script>
@endsection

