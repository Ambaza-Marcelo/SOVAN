
@extends('backend.layouts.master')

@section('title')
@lang('messages.Modifier Demande de sortie de fonds') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .autocomplete-wrapper {
        position: relative;
    }
    .autocomplete-list {
        position: absolute;
        border: 1px solid #d4d4d4;
        border-top: none;
        z-index: 99;
        max-height: 200px;
        overflow-y: auto;
        background-color: #fff;
        width: 100%;
        display: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: -1px;
    }
    .autocomplete-list div {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #d4d4d4;
    }
    .autocomplete-list div:hover {
        background-color: #e9e9e9;
    }
    .autocomplete-list div:last-child {
        border-bottom: none;
    }
    .autocomplete-active {
        background-color: #4CAF50 !important;
        color: #ffffff;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Modifier Demande de sortie de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.cash-out-flow-request.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Demande de sortie de fonds')</span></li>
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
                    <h4 class="header-title">@lang('messages.Demande de sortie de fonds')</h4>
                    @include('backend.layouts.partials.messages')
                    <div class="mb-3">
                        @include('backend.layouts.partials.update_supplier_modal', [
                            'updateSupplierRoute' => route('admin.cash-out-flow-request.update-supplier', $data->cash_outflow_request_no),
                            'currentSupplierId' => $data->supplier_id ?? '',
                            'suppliers' => $suppliers ?? collect(),
                            'entityLabel' => 'cette demande de sortie de fonds',
                            'modalId' => 'updateSupplierModalRequestEdit',
                        ])
                    </div>
                    <form action="{{ route('admin.cash-out-flow-request.update',$data->cash_outflow_request_no) }}" method="post" id="dynamic_form">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ $data->date }}">
                            </div>
                        </div>
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="asker">@lang('messages.asker')</label>
                                <input type="text" class="form-control" id="asker" name="asker" value="{{ $data->asker }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employe_search">@lang('messages.Employé')</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="employe_search" class="form-control" placeholder="@lang('messages.hr_type_to_search_employee')" autocomplete="off" required>
                                    <input type="hidden" name="employe_id" id="employe_id" value="{{ $data->employe_id }}">
                                    <div class="autocomplete-list" id="employe_search-list"></div>
                                </div>
                            </div>
                        </div>
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
                                 <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                                @foreach($currencies as $currency)
                                    <option value="{{$currency->id}}" {{ ($data->currency_id == $currency->id) || (!$data->currency_id && strtoupper($currency->currency_name) == 'BIF') ? 'selected' : '' }}>{{$currency->currency_name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vat_supplier_payer">@lang('messages.assujetti à la tva?')</label>
                                <select class="form-control" required name="vat_supplier_payer" id="vat_supplier_payer" required>
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                    <option value="0">@lang('messages.Non assujetti')</option>
                                    <option value="1">@lang('messages.Assujetti')</option>
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
                            @php
                                $expenseDisplay = '';
                                $expenseCostPrice = 0;
                                foreach($expenses as $expense) {
                                    if($expense->id == $data->expense_id) {
                                        $expenseDisplay = $expense->name . '/' . number_format($expense->cost_price,0,',',' ');
                                        $expenseCostPrice = $expense->cost_price;
                                        break;
                                    }
                                }
                            @endphp
                            <tr class="">    
                                <td>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" class="form-control expense_search" placeholder="@lang('messages.Tapez pour rechercher un article...')" autocomplete="off" value="{{ $expenseDisplay }}" required>
                                        <input type="hidden" name="expense_id[]" class="expense_id" value="{{ $data->expense_id }}">
                                        <input type="hidden" class="expense_cost_price" value="{{ $expenseCostPrice }}">
                                        <div class="autocomplete-list expense_search-list"></div>
                                    </div>
                                </td> 
                                <td><input type="number" name="quantity[]" class="form-control quantity-input" min="0" value="{{ $data->quantity }}" step="any" required /></td>  
                                <td><button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button></td>     
                            </tr>
                            @endforeach
                            <tr class="total-row" style="display: none;">
                                <td colspan="2" style="text-align: right; font-weight: bold;">@lang('messages.Total Amount'):</td>
                                <td><span id="total_amount" style="font-weight: bold;">0</span></td>
                            </tr>
                        </table>
                        <div>
                            <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>
                        </div> 
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')">
                                DEMANDE DE SORTIE DE FONDS
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
    function initEmployeeAutocomplete() {
        attachAjaxAutocomplete({
            $input: $('#employe_search'),
            $hidden: $('#employe_id'),
            url: '{{ route("admin.autocomplete.employees") }}',
            minChars: 1,
            onSelect: function(item){
                $('#asker').val(item.name || item.text || '');
            }
        });
    }

    function initExpenseAutocomplete($wrapper){
        var $input = $wrapper.find('.expense_search');
        var $hidden = $wrapper.find('.expense_id');
        attachAjaxAutocomplete({
            $input: $input,
            $hidden: $hidden,
            url: '{{ route("admin.autocomplete.expenses") }}',
            minChars: 1,
            onSelect: function(item){
                $wrapper.find('.expense_cost_price').val(item.cost_price ?? 0);
                calculateTotal();
            }
        });
    }

    // Function to calculate total amount
    function calculateTotal() {
        var total = 0;
        var vatRate = parseFloat($('select[name="vat_rate"]').val()) || 0;
        
        $('#dynamicTable tr').not('.total-row').not(':first').each(function() {
            var quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            var costPrice = parseFloat($(this).find('.expense_cost_price').val()) || 0;
            
            if (quantity > 0 && costPrice > 0) {
                // Calculate: (quantity * cost_price) * (1 + vat_rate/100)
                var rowTotal = (quantity * costPrice) * (1 + vatRate / 100);
                total += rowTotal;
            }
        });
        
        // Show total row if there are items
        if (total > 0) {
            $('.total-row').show();
        } else {
            $('.total-row').hide();
        }
        
        // Format and display total
        $('#total_amount').text(number_format(total, 0, ',', ' '));
    }

    // Initialize autocomplete for employees
    $(document).ready(function() {
        initEmployeeAutocomplete();
        
        // Set initial value if editing
        // Note: input value is already displayed by the form; hidden id remains.
        
        // Initialize autocomplete for existing expense rows
        $('.autocomplete-wrapper').each(function() {
            if ($(this).find('.expense_search').length) {
                initExpenseAutocomplete($(this));
            }
        });
        
        // Show total row initially if there are existing items
        calculateTotal();
    });
    
    // Listen for quantity changes
    $(document).on('input change', '.quantity-input', function() {
        calculateTotal();
    });
    
    // Listen for VAT rate changes
    $(document).on('change', 'select[name="vat_rate"]', function() {
        calculateTotal();
    });
    
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<div class='autocomplete-wrapper'>"+
                            "<input type='text' class='form-control expense_search' placeholder='" + i18n.searchExpense + "' autocomplete='off' required>"+
                            "<input type='hidden' name='expense_id[]' class='expense_id'>"+
                            "<input type='hidden' class='expense_cost_price'>"+
                            "<div class='autocomplete-list expense_search-list'></div>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' placeholder='" + i18n.enterQuantity + "' class='form-control quantity-input' step='any' min='0'/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                        "</td>"+
                    "</tr>";
   
        var newRow = $(markup);
        // Insert before total row
        $('.total-row').before(newRow);
        
        // Initialize autocomplete for the new row
        initExpenseAutocomplete(newRow.find('.autocomplete-wrapper'));
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
         calculateTotal();
    });
    
    // Helper function to format numbers
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }


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
                                "<option value='0'>0%</option>"+
                                "<option value='10'>10%</option>"+
                                "<option value='18'>18%</option>";
        
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
