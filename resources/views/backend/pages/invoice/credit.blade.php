
@extends('backend.layouts.master')

@section('title')
@lang('messages.Validation Credit de facture') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    #client_list {
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
        margin-top: 2px;
    }
    #client_list .list-group-item {
        border: none;
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 15px;
    }
    #client_list .list-group-item:last-child {
        border-bottom: none;
    }
    #client_list .list-group-item:hover {
        background-color: #f8f9fa !important;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Validation Credit de facture')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.invoice.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Validation Credit de facture')</span></li>
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
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Validation Credit Facture :') {{$facture->invoice_number}}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.invoice.validate-as-credit', $facture->invoice_number) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @if($facture->employe_id)
                        <div class="col-md-12">
                                <label for="employe_id">@lang('messages.Serveur')</label>
                                <select class="form-control" name="employe_id" id="employe_id">
                                <option disabled="disabled">@lang('messages.Merci de choisir un Serveur')</option>
                                <option value="{{$facture->employe_id}}" selected="selected">{{$facture->employe->name ?? ''}}</option>
                            </select>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="invoice_number">@lang('messages.Numero Facture')</label>
                                <input type="text" value="{{ $facture->invoice_number }}" name="invoice_number" class="form-control" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_name">@lang('messages.Nom et Prenom')</label>
                                <input type="text" value="{{ $setting->name ?? '' }}" name="tp_name" class="form-control" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_TIN">@lang('messages.NIF Contribuable')</label>
                                <input type="text" value="{{ $setting->nif ?? '' }}" name="tp_TIN" class="form-control" readonly>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_trade_number">@lang('messages.RC du Contribuable')</label>
                                <input type="text" value="{{ $setting->rc ?? '' }}" name="tp_trade_number" class="form-control" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_phone_number">@lang('messages.Tel. du Contribuable')</label>
                                <input type="text" value="{{ $setting->telephone1 ?? $setting->telephone2 ?? '' }}" name="tp_phone_number" class="form-control" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_address_province">@lang('messages.Province')</label>
                                <input type="text" value="{{ $setting->province ?? '' }}" name="tp_address_province" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_search">@lang('messages.Nom du Client')</label>
                                    <input type="text" id="client_search" class="form-control" placeholder="@lang('messages.Tapez pour rechercher un client...')" autocomplete="off" value="@foreach ($clients as $client)@if($facture->client_id == $client->id){{ $client->customer_name }}/{{ $client->telephone }}@endif @endforeach">
                                    <input type="hidden" name="client_id" id="client_id" value="{{ $facture->client_id }}">
                                    <ul id="client_list" class="list-group" style="position: absolute; z-index: 1000; max-height: 300px; overflow-y: auto; display: none;"></ul>
                                </div>
                            </div>
                        </div>
                        <br>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.Article')</th>
                                <th>@lang('messages.Quantite')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Prix HTVA') </th>
                                <th>@lang('messages.Montant TVA') </th>
                                <th>@lang('messages.P.V TVAC') </th>
                                <th>@lang('messages.P.V total') </th>
                            </tr>
                            @php
                                $items_data = [];
                                $grand_total = 0;
                            @endphp
                            @foreach($datas as $index => $detail)
                            @php
                                $item_name = $detail->item ? $detail->item->name : ($detail->item_designation ?? 'N/A');
                                $item_display = $item_name . '/' . number_format($detail->item_price, 0, ',', ' ');
                                $items_data[] = [
                                    'id' => $detail->item_id,
                                    'name' => $item_name,
                                    'price' => $detail->item_price,
                                    'display' => $item_display
                                ];
                                $grand_total += $detail->item_total_amount;
                            @endphp
                            <tr>  
                                <td>
                                    <div class="autocomplete">
                                        <input type="text" 
                                            id="item_name_{{ $index }}" 
                                            class="form-control readonly-field" 
                                            value="{{ $item_display }}" 
                                            readonly />
                                        <input type="hidden" name="item_id[]" value="{{ $detail->item_id }}" />
                                    </div>
                                </td>  
                                <td>
                                    <input type="number" 
                                        name="item_quantity[]" 
                                        id="quantity_{{ $index }}" 
                                        class="form-control readonly-field quantity-input" 
                                        value="{{ $detail->item_quantity }}" 
                                        readonly />
                                </td>
                                <td>
                                    <input type="text" 
                                        id="unit_price_{{ $index }}" 
                                        class="form-control readonly-field unit-price" 
                                        value="{{ number_format($detail->item_price, 0, ',', ' ') }} BIF" 
                                        readonly />
                                    <input type="hidden" name="item_price[]" value="{{ $detail->item_price }}" />
                                </td>
                                <td>
                                    <input type="text" step='any' min='0' name="item_price_nvat[]" value="{{ number_format($detail->item_price_nvat ?? 0, 2, '.', ' ') }}" class="form-control" readonly/>
                                </td>
                                <td>
                                    <input type="text" step='any' min='0' name="vat[]" value="{{ number_format($detail->vat ?? 0, 2, '.', ' ') }}" class="form-control" readonly/>
                                </td>
                                <td>
                                    <input type="text" step='any' min='0' name="item_price_wvat[]" value="{{ number_format($detail->item_price_wvat ?? 0, 2, '.', ' ') }}" class="form-control" readonly/>
                                </td>
                                <td>
                                    <input type="text" step='any' min='0' name="Item_total_amount[]" value="{{ number_format($detail->item_total_amount ?? 0, 2, '.', ' ') }}" class="form-control" readonly/>
                                </td>
                            </tr>
                            @endforeach
                        </table>
                        
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        @php
                                            $total_without_vat = 0;
                                            $total_vat = 0;
                                            $total_with_vat = 0;
                                            foreach($datas as $detail) {
                                                $total_without_vat += $detail->item_price_nvat ?? 0;
                                                $total_vat += $detail->vat ?? 0;
                                                $total_with_vat += $detail->item_total_amount ?? 0;
                                            }
                                        @endphp
                                        <table class="table table-bordered">
                                            <tr>
                                                <th>@lang('messages.Total HTVA')</th>
                                                <td class="text-right"><strong>{{ number_format($total_without_vat, 2, '.', ' ') }} BIF</strong></td>
                                            </tr>
                                            <tr>
                                                <th>@lang('messages.Total TVA')</th>
                                                <td class="text-right"><strong>{{ number_format($total_vat, 2, '.', ' ') }} BIF</strong></td>
                                            </tr>
                                            <tr>
                                                <th>@lang('messages.Total TTC')</th>
                                                <td class="text-right"><strong class="text-primary">{{ number_format($total_with_vat, 2, '.', ' ') }} BIF</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">{{ $facture->description ?? '' }}</textarea>
                        </div>
                        
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.VALIDER AVEC CREDIT(N\'OUBLIE PAS NOM DU CLIENT)')</button>
                            <a href="{{ route('admin.invoice.index') }}" class="btn btn-secondary mt-4 pr-4 pl-4">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
    // Client Autocomplete functionality
    var clients = [
        @foreach ($clients as $client)
        {
            id: {{ $client->id }},
            name: {{ json_encode($client->customer_name) }},
            telephone: {{ json_encode($client->telephone) }},
            display: {{ json_encode($client->customer_name . '/' . $client->telephone) }}
        },
        @endforeach
    ];

    $('#client_search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var $clientList = $('#client_list');
        
        if (searchTerm.length === 0) {
            $clientList.hide().empty();
            return;
        }
        
        var filteredClients = clients.filter(function(client) {
            return client.display.toLowerCase().includes(searchTerm) ||
                   client.name.toLowerCase().includes(searchTerm) ||
                   client.telephone.toString().includes(searchTerm);
        });
        
        $clientList.empty();
        
        if (filteredClients.length > 0) {
            filteredClients.forEach(function(client) {
                var $item = $('<li class="list-group-item" style="cursor: pointer;"></li>')
                    .text(client.display)
                    .data('client', client)
                    .on('click', function() {
                        var selectedClient = $(this).data('client');
                        $('#client_search').val(selectedClient.display);
                        $('#client_id').val(selectedClient.id);
                        $clientList.hide().empty();
                    })
                    .on('mouseenter', function() {
                        $(this).css('background-color', '#f8f9fa');
                    })
                    .on('mouseleave', function() {
                        $(this).css('background-color', 'white');
                    });
                $clientList.append($item);
            });
            $clientList.show();
        } else {
            $clientList.hide();
        }
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#client_search, #client_list').length) {
            $('#client_list').hide();
        }
    });
</script>
@endsection
