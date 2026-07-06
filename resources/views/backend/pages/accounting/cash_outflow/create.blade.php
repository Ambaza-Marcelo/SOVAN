
@extends('backend.layouts.master')

@section('title')
@lang('messages.sortie de fonds') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label { text-transform: capitalize; }
    .autocomplete-wrapper { position: relative; }
    .autocomplete-list { position: absolute; border: 1px solid #d4d4d4; border-top: none; z-index: 99; max-height: 200px; overflow-y: auto; background: #fff; width: 100%; display: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: -1px; }
    .autocomplete-list div { padding: 10px; cursor: pointer; border-bottom: 1px solid #d4d4d4; }
    .autocomplete-list div:hover { background-color: #e9e9e9; }
    .autocomplete-list div:last-child { border-bottom: none; }
    .autocomplete-active { background-color: #4CAF50 !important; color: #fff; }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.sortie de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.cash-out-flow.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.sortie de fonds')</span></li>
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
                    <h4 class="header-title">@lang('messages.sortie de fonds')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.cash-out-flow.store') }}" method="post" id="dynamic_form">
                        @csrf
                    <input type="hidden" name="cash_outflow_request_no" value="{{ $cash_outflow_request_no }}">
                    <div class="row">
                        <div class="col-md-4" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ $data->date }}">
                            </div>
                        </div>
                        <div class="col-md-4" id="dynamicDiv">
                            <div class="form-group">
                                <label for="asker">@lang('messages.asker')</label>
                                <input type="text" class="form-control" id="asker" name="asker" value="{{ $data->asker }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employe_id">@lang('messages.Employé')</label>
                                <select class="form-control" name="employe_id" id="employe_id" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($employes as $employe)
                                    <option value="{{$employe->id}}" {{ $data->employe_id == $employe->id ? 'selected' : '' }}>{{$employe->firstname}} {{$employe->lastname}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="cost_center_id">@lang('messages.Cost Center')</label>
                                <select class="form-control" name="cost_center_id" id="cost_center_id" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($cost_centers as $cost_center)
                                    <option value="{{$cost_center->id}}" {{ $data->cost_center_id == $cost_center->id ? 'selected' : '' }}>{{$cost_center->name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="currency_id">@lang('messages.Currency')</label>
                                <select class="form-control" name="currency_id" id="currency_id" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($currencies as $currency)
                                    <option value="{{$currency->id}}" {{ $data->currency_id == $currency->id ? 'selected' : '' }}>{{$currency->currency_name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="account_id">@lang('messages.Account')</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="account_search" class="form-control" placeholder="@lang('messages.Tapez pour rechercher un compte...')" autocomplete="off">
                                    <input type="hidden" name="account_id" id="account_id" value="" required>
                                    <div class="autocomplete-list" id="account_search-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vat_supplier_payer">@lang('messages.assujetti à la tva?')</label>
                                <select class="form-control" required name="vat_supplier_payer" id="vat_supplier_payer" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                    <option value="0" {{ $data->vat_supplier_payer == 0 ? 'selected' : '' }}>Non assujetti</option>
                                    <option value="1" {{ $data->vat_supplier_payer == 1 ? 'selected' : '' }}>Assujetti</option>
                             </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="vat_rate">
                            
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="">
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($datas as $data)
                            <tr class="">    
                                <td><select class="form-control" name="expense_id[]" id="expense_id">
                                <option disabled="disabled" selected="selected">@lang('messages.merci de choisir')</option>
                                @foreach($expenses as $expense)
                                <option value="{{ $expense->id }}" {{ $data->expense_id == $expense->id ? 'selected' : '' }} class="form-control">{{$expense->name}}/{{ number_format($expense->cost_price,0,',',' ') }}</option>
                                @endforeach
                                </select></td> 
                                <td><input type="number" name="quantity[]" class="form-control" min="0" value="{{ $data->quantity }}" step="any" required readonly /></td>  
                                <td></td>     
                            </tr>
                            @endforeach
                        </table>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">
                                SORTIE DE FONDS
                            </textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    var i18n = {
        searchAccount: @json(__('messages.Rechercher un compte (numéro ou nom)...')),
        searchExpense: @json(__('messages.Tapez pour rechercher un article...')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        enterDescription: @json(__('messages.Enter Description')),
        enterDocumentNo: @json(__('messages.Enter document no')),
        enterLibelle: @json(__('messages.Enter libelle')),
        enterDebit: @json(__('messages.Enter debit')),
        enterCredit: @json(__('messages.Enter credit')),
        delete: @json(__('messages.delete')),
        addmore: @json(__('messages.addmore')),
        pleaseChoose: @json(__('messages.merci de choisir')),
        loadingOpeningBalances: @json(__('messages.Chargement des soldes d\'ouverture...')),
        openingNoManualRows: @json(__('messages.journal_entry_opening_no_manual_rows'))
    };
    $(document).ready(function(){
        attachAjaxAutocomplete({
            $input: $('#account_search'),
            $hidden: $('#account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}',
            minChars: 1,
            renderItem: function(item){
                var t = (item.text || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                return '<div><strong>' + t + '</strong></div>';
            }
        });
    });

    //one checked box in checkbox group of invoice_currency

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    $('#vat_supplier_payer').change(function () { 
    if ($(this).val() === '1'){

                var vat_rate = "<label for='vat_rate'>" + i18n.pleaseChoose + "<strong style='color: red;'>*</strong></label>"+
                            "<select name='vat_rate' required class='form-control'>"+
                                "<option selected disabled>" + i18n.pleaseChoose + "</option>"+
                                "<option value='0' {{ $data->vat_rate == '0' ? 'selected' : '' }}>0%</option>"+
                                "<option value='10' {{ $data->vat_rate == '10' ? 'selected' : '' }}>10%</option>"+
                                "<option value='18' {{ $data->vat_rate == '18' ? 'selected' : '' }}>18%</option>";
        
        $("#vat_rate").append([vat_rate]);
    }

    })
    .trigger( "change" ); 

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

</script>
@endsection
