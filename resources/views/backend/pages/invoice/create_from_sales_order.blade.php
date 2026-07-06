@extends('backend.layouts.master')

@section('title')
@lang('messages.Créer Facture (Bon de commande)') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Créer Facture')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.sales-order.index') }}">@lang('messages.Bons de commande')</a></li>
                    <li><span>@lang('messages.Créer Facture - BC') {{ $sales_order_number }}</span></li>
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
                    <h4 class="header-title">@lang('messages.Créer Facture - Bon de commande:') {{ $sales_order_number }}</h4>
                    @include('backend.layouts.partials.messages')
                    <form action="{{ route('admin.invoice.store') }}" method="post" id="dynamic_form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="document_no" value="{{ $sales_order_number }}">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="invoice_type">@lang('messages.Type Facture')</label>
                                <div class="form-group">
                                    <label class="text">F. Normale
                                    <input type="checkbox" name="invoice_type" value="FN" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">R. Caution
                                    <input type="checkbox" disabled name="invoice_type" value="RC" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Reduction HF
                                    <input type="checkbox" name="invoice_type" disabled value="RHF" class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="payment_type">@lang('messages.Type de Paiement')</label>
                                <div class="form-group">
                                    <label class="text">Espece
                                    <input type="checkbox" name="payment_type" value="1" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Banque
                                    <input type="checkbox" name="payment_type" value="2" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Credit
                                    <input type="checkbox" name="payment_type" value="3" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Autres
                                    <input type="checkbox" name="payment_type" value="4" class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="invoice_currency">@lang('messages.Type de Monaie')</label>
                                <div class="form-group">
                                    <label class="text">BIF
                                    <input type="checkbox" name="invoice_currency" value="BIF" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">USD
                                    <input type="checkbox" name="invoice_currency" value="USD" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">EUR
                                    <input type="checkbox" name="invoice_currency" value="EUR" class="form-control">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="client_id">@lang('messages.Client')</label>
                                    <input type="text" class="form-control readonly-field"
                                        value="@if($sales_order->client){{ $sales_order->client->customer_name }}/{{ $sales_order->client->telephone }}@endif"
                                        readonly />
                                    <input type="hidden" name="client_id" value="{{ $sales_order->client_id }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="invoice_date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                                           value="{{ old('invoice_date', now()->format('Y-m-d')) }}">
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
                                $item_display = $item_name . '/' . number_format($detail->item_price, 0, ',', ' ');
                                $grand_total += $detail->item_total_amount;
                            @endphp
                            <tr>
                                <td>
                                    <input type="text" class="form-control readonly-field" value="{{ $item_display }}" readonly />
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
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.description')">{{ $sales_order->description ?? '' }}</textarea>
                        </div>
                        <div style="margin-top: 15px; margin-left: 15px;">
                            <button type="submit" class="btn btn-primary" id="save">@lang('messages.save')</button>
                            <a href="{{ route('admin.sales-order.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function() {
        function makeSingleChoice(name) {
            var boxes = document.querySelectorAll('input[name="' + name + '"]');
            boxes.forEach(function(box) {
                box.addEventListener('change', function() {
                    if (!this.checked) {
                        return;
                    }
                    boxes.forEach(function(other) {
                        if (other !== box) {
                            other.checked = false;
                        }
                    });
                });
            });
        }

        makeSingleChoice('invoice_type');
        makeSingleChoice('payment_type');
        makeSingleChoice('invoice_currency');
    })();
</script>
@endsection
