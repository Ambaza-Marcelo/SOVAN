@extends('backend.layouts.master')

@section('title')
@lang('messages.Créer Bon de livraison (Facture)') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .readonly-field { background-color: #f5f5f5; cursor: not-allowed; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Créer Bon de livraison')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.invoice.index') }}">@lang('messages.Factures')</a></li>
                    <li><span>@lang('messages.Bon de livraison - Facture') {{ $invoice_number }}</span></li>
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
                    <h4 class="header-title">@lang('messages.Créer Bon de livraison - Facture:') {{ $invoice_number }}</h4>
                    @include('backend.layouts.partials.messages')
                    <form action="{{ route('admin.delivery-note.store') }}" method="post" id="dynamic_form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="invoice_number" value="{{ $invoice_number }}">
                        <input type="hidden" name="document_no" value="{{ $invoice_number }}">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="client_id">@lang('messages.Client')</label>
                                    <input type="text" class="form-control readonly-field"
                                        value="@if($invoice->client){{ $invoice->client->customer_name }}/{{ $invoice->client->telephone }}@endif"
                                        readonly />
                                    <input type="hidden" name="client_id" value="{{ $invoice->client_id }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="delivery_date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                                           value="{{ old('delivery_date', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered" id="dynamicTable">
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Total')</th>
                            </tr>
                            @php $grand_total = 0; @endphp
                            @foreach($datas as $index => $detail)
                            @php
                                $item_name = $detail->item ? $detail->item->name : ($detail->item_designation ?? 'N/A');
                                $grand_total += $detail->item_total_amount;
                            @endphp
                            <tr>
                                <td>
                                    <input type="text" class="form-control readonly-field" value="{{ $item_name }}/{{ number_format($detail->item_price, 0, ',', ' ') }}" readonly />
                                    <input type="hidden" name="item_id[]" value="{{ $detail->item_id }}" />
                                </td>
                                <td>
                                    <input type="number" name="item_quantity[]" class="form-control readonly-field" value="{{ $detail->item_quantity }}" readonly />
                                </td>
                                <td>
                                    <input type="text" class="form-control readonly-field" value="{{ number_format($detail->item_price, 0, ',', ' ') }} BIF" readonly />
                                    <input type="hidden" name="item_price[]" value="{{ $detail->item_price }}" />
                                </td>
                                <td>
                                    <input type="text" class="form-control readonly-field" value="{{ number_format($detail->item_total_amount, 0, ',', ' ') }} BIF" readonly />
                                </td>
                            </tr>
                            @endforeach
                        </table>
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">@lang('messages.Total Général')</h5>
                                        <h3 id="grand_total" class="text-primary">{{ number_format($grand_total, 0, ',', ' ') }} BIF</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group">
                            <label for="document">@lang('messages.Document (PDF / Image)')</label>
                            <input type="file" class="form-control document-upload-with-preview" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">@lang('messages.PDF, JPG, JPEG, PNG')</small>
                            @include('backend.layouts.partials.document_upload_preview')
                        </div>
                        <div class="col-lg-12 mt-2">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">{{ $invoice->description ?? '' }}</textarea>
                        </div>
                        <div style="margin-top: 15px; margin-left: 15px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.save')</button>
                            <a href="{{ route('admin.invoice.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
