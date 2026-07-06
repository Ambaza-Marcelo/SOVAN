
@extends('backend.layouts.master')

@section('title')
@lang('messages.Validation Credit de facture') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .account-suggestions {
        position: absolute;
        z-index: 1000;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        width: 100%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .account-suggestion-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }
    .account-suggestion-item:hover {
        background-color: #f5f5f5;
    }
    .account-suggestion-item:last-child {
        border-bottom: none;
    }
    .account-input-wrapper {
        position: relative;
    }
    .montant-formatted {
        color: #6c757d;
        font-size: 0.875rem;
        font-weight: 500;
        margin-top: 0.25rem;
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
                    <li><a href="{{ route('admin.credit-invoices.list') }}">@lang('messages.list')</a></li>
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
                    <h4 class="header-title">@lang('messages.Validation paiement Facture')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.facture-credit.payer',$facture->invoice_number) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @if($facture->employe_id)
                        <div class="col-md-12">
                                <label for="employe_id">@lang('messages.Serveur')</label>
                                <select class="form-control" name="employe_id" id="employe_id">
                                <option disabled="disabled">@lang('messages.Merci de choisir un Serveur')</option>
                                <option value="{{$facture->employe_id}}" selected="selected">{{$facture->employe->name}}</option>
                            </select>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-sm-5">
                                <label for="tp_name">@lang('messages.Nom et Prenom')</label>
                                <input type="text" value="{{ $facture->tp_name }}" name="tp_name" class="form-control" readonly>
                            </div>
                            <div class="col-sm-5">
                                <label for="tp_TIN">@lang('messages.NIF Contribuable')</label>
                                <input type="text" value="{{ $facture->tp_TIN }}" name="tp_TIN" class="form-control" readonly>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_trade_number">@lang('messages.RC du Contribuable')</label>
                                <input type="text" value="{{ $facture->tp_trade_number }}" name="tp_trade_number" class="form-control" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_phone_number">@lang('messages.Tel. du Contribuable')</label>
                                <input type="text" value="{{ $facture->tp_phone_number }}" name="tp_phone_number" class="form-control" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label for="">@lang('messages.Province')</label>
                                <input type="text" value="{{ $facture->tp_address_province }}" name="" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                <label for="client_id">@lang('messages.Nom du Client')</label>
                                @php
                                    $selectedClient = $clients->firstWhere('id', $facture->client_id);
                                    $clientDisplay = $selectedClient ? $selectedClient->customer_name . '/' . $selectedClient->telephone : '';
                                @endphp
                                <input type="text" value="{{ $clientDisplay }}" class="form-control" readonly>
                                <input type="hidden" name="client_id" value="{{ $facture->client_id }}">
                            </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                <label for="etat_recouvrement">@lang('messages.Type de Paiement')</label>
                                <select class="form-control" name="etat_recouvrement" id="etat_recouvrement" required>
                                <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir client')</option>
                                <option value="1" class="form-control">@lang('messages.Paiement partiel')</option>
                                <option value="2" class="form-control">@lang('messages.Paiement Total')</option>
                                </select>
                            </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                <label for="statut_paied">@lang('messages.Mode de Paiement')</label>
                                <select class="form-control" name="statut_paied" id="statut_paied" required>
                                <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir client')</option>
                                <option value="1" class="form-control">@lang('messages.CASH')</option>
                                <option value="2" class="form-control">@lang('messages.Banque')</option>
                                </select>
                            </div>
                            </div>
                        </div>
                        <div class="row" id="mode_paiement">
                            
                        </div>
                        <div class="row" id="type_paiement">
                            <div class="col-md-4">
                                <label for="reste_credit">@lang('messages.Reste Credit')</label>
                                <input type="text" name="reste_credit_display" value="{{ number_format($reste_credit, 0, ',', ' ') }} BIF" class="form-control" readonly>
                                <input type="hidden" name="reste_credit" value="{{ $reste_credit }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="date_recouvrement">@lang('messages.Date de Recouvrement')</label>
                                <input type="date" name="date_recouvrement" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nom_recouvrement">@lang('messages.Nom Charge de recouvrement')</label>
                                <input type="text" name="nom_recouvrement" value="{{ Auth::guard('admin')->user()->name }}" class="form-control" required>
                            </div>
                        </div>
                        <br>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.Date facture')</th>
                                <th>@lang('messages.No Facture')</th>
                                <th>@lang('messages.Article')</th>
                                <th>@lang('messages.Quantite')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.Prix HTVA') </th>
                                <th>@lang('messages.Montant TVA') </th>
                                <th>@lang('messages.P.V TVAC') </th>
                                <th>@lang('messages.P.V total') </th>
                               <!-- <th>@lang('messages.Action')</th> -->
                            </tr>
                            @foreach($datas as $data)
                            <tr>
                                <td><input type="text" value="{{ \Carbon\Carbon::parse($data->invoice_date)->format('d/m/Y') }}" class="form-control" readonly/></td>   
                                <td><input type="text" value="{{ $data->invoice_number }}" class="form-control" readonly/></td>  
                                <td><input type="text" name="item_designation[]" value="@if($data->drink_id) {{ $data->drink->name }} @elseif($data->food_item_id) {{ $data->foodItem->name }} @elseif($data->barrist_item_id) {{ $data->barristItem->name }} @elseif($data->bartender_id) {{ $data->bartenderItem->name }} @elseif($data->salle_id) {{ $data->salle->name }} @elseif($data->service_id) {{ $data->service->name }} @elseif($data->table_id) {{ $data->table->name }} @endif" class="form-control" readonly /></td>  
                                <td><input type="text" step='any' min='0' name="item_quantity[]" value="{{ $data->item_quantity }}" class="form-control" readonly /></td>  
                                <td><input type="text" step='any' min='0' name="item_price[]" value="{{ number_format($data->item_price, 0, ',', ' ') }} BIF" class="form-control" readonly /></td>
                                <td><input type="text" step='any' min='0' name="item_price_nvat[]" value="{{ number_format($data->item_price_nvat, 2, ',', ' ') }} BIF" class="form-control" readonly/></td>
                                <td><input type="text" step='any' min='0' name="vat[]" value="{{ number_format($data->vat, 2, ',', ' ') }} BIF" class="form-control" readonly/></td>
                                <td><input type="text" step='any' min='0' name="item_price_wvat[]" value="{{ number_format($data->item_price_wvat, 0, ',', ' ') }} BIF" class="form-control" readonly/></td>
                                <td><input type="text" step='any' min='0' name="Item_total_amount[]" value="{{ number_format($data->item_total_amount, 0, ',', ' ') }} BIF" class="form-control" readonly/></td>
                                <!--<td><button type="button" name="add" id="add" class="btn btn-success"><i class="fa fa-plus-square" title="Ajouter Plus" aria-hidden="false"></i></button></td> -->
                            </tr> 
                            @endforeach
                        </table> 
                        <div>
                            <label for="note_recouvrement"></label>
                            <textarea name="note_recouvrement" id="note_recouvrement" class="form-control" required>
                                PAIEMENT
                            </textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.VALIDER CREDIT')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">

    var currentAccountType = null;

    // Handle payment mode change (Cash or Bank)
    $('#statut_paied').change(function () { 
        // Clear previous content
        $("#mode_paiement").empty();
        
        if ($(this).val() === '1'){ // CASH - account_type = 4
            currentAccountType = 4;
            
            var cash_account = "<div class='col-md-6 account-input-wrapper'>"+
                                "<label for='account_name'>@lang('messages.Compte Cash')<strong style='color: red;'>*</strong></label>"+
                                "<input type='text' class='form-control account-input' data-account-type='4' placeholder='Rechercher par numéro ou nom de compte...' autocomplete='off' required />"+
                                "<input type='hidden' class='account-id-input' name='account_id' />"+
                                "<div class='account-suggestions' style='display: none;'></div>"+
                            "</div>";
            var cheque_no = "<div class='col-md-6'>"+
                                "<label for='cheque_no'>@lang('messages.Référence')<strong style='color: red;'>*</strong></label>"+
                                    "<input type='text' name='cheque_no' placeholder='Saisir référence' class='form-control'/>"+
                            "</div>";
            
            $("#mode_paiement").append([cash_account, cheque_no]);
        }
        
        if ($(this).val() === '2'){ // Banque - account_type = 3
            currentAccountType = 3;
            
            var banque = "<div class='col-md-6 account-input-wrapper'>"+
                                "<label for='bank_name'>@lang('messages.Compte Bancaire')<strong style='color: red;'>*</strong></label>"+
                                "<input type='text' class='form-control account-input' data-account-type='3' placeholder='Rechercher par numéro ou nom de compte...' autocomplete='off' required />"+
                                "<input type='hidden' class='account-id-input' name='account_id' />"+
                                "<div class='account-suggestions' style='display: none;'></div>"+
                            "</div>";
            var cheque_no = "<div class='col-md-6'>"+
                                "<label for='cheque_no'>@lang('messages.No Cheque')<strong style='color: red;'>*</strong></label>"+
                                    "<input type='text' name='cheque_no' placeholder='Saisir no cheque ou no bordereau' class='form-control'/>"+
                            "</div>";
            
            $("#mode_paiement").append([banque, cheque_no]);
        }
        
        // Reinitialize autocomplete after adding new inputs
        initializeAutocomplete();
    })
    .trigger( "change" );

    // Handle payment type change (Total or Partial)
    $('#etat_recouvrement').change(function () { 
        // Clear previous content
        $("#type_paiement").find('.col-md-4:not(:first)').remove();
        
        if ($(this).val() === '2'){ // Total Payment
            var totalAmount = {{ $total_amount }};
            var formattedTotal = formatCurrency(totalAmount);
            
            var montant_total_credit = "<div class='col-md-4'>"+
                                "<label for='montant_total_credit'>@lang('messages.Montant Total a payer')<strong style='color: red;'>*</strong></label>"+
                                    "<input type='text' name='montant_total_credit_display' value='" + formattedTotal + "' class='form-control' readonly/>"+
                                    "<input type='hidden' name='montant_total_credit' value='" + totalAmount + "'/>"+
                            "</div>";
            var montant_recouvre = "<div class='col-md-4'>"+
                                "<label for='montant_recouvre'>@lang('messages.Montant paye')<strong style='color: red;'>*</strong></label>"+
                                    "<input type='number' name='montant_recouvre' value='" + totalAmount + "' class='form-control montant-input' min='0' max='" + totalAmount + "' required/>"+
                                    "<small class='form-text text-muted montant-formatted'>@lang('messages." + formattedTotal + "')</small>"+
                            "</div>";
            
            $("#type_paiement").append([montant_total_credit, montant_recouvre]);
        }

        if ($(this).val() === '1'){ // Partial Payment
            var totalAmount = {{ $total_amount }};
            var resteCredit = {{ $reste_credit }};
            var formattedTotal = formatCurrency(totalAmount);
            var formattedReste = formatCurrency(resteCredit);
            
            var montant_total_credit = "<div class='col-md-4'>"+
                                "<label for='montant_total_credit'>@lang('messages.Montant Total a payer')<strong style='color: red;'>*</strong></label>"+
                                    "<input type='text' name='montant_total_credit_display' value='" + formattedTotal + "' class='form-control' readonly/>"+
                                    "<input type='hidden' name='montant_total_credit' value='" + totalAmount + "'/>"+
                            "</div>";
            var montant_recouvre = "<div class='col-md-4'>"+
                                "<label for='montant_recouvre'>@lang('messages.Montant paye')<strong style='color: red;'>*</strong></label>"+
                                    "<input type='number' name='montant_recouvre' value='" + resteCredit + "' min='0' max='" + resteCredit + "' class='form-control montant-input' required/>"+
                                    "<small class='form-text text-muted montant-formatted'>@lang('messages." + formattedReste + "')</small>"+
                            "</div>";
            
            $("#type_paiement").append([montant_total_credit, montant_recouvre]);
        }
        
        // Initialize montant input formatter
        initializeMontantFormatter();
    })
    .trigger( "change" );

    // Initialize autocomplete functionality
    function initializeAutocomplete() {
        var typingTimer;
        var doneTypingInterval = 300; // milliseconds

        // Handle input on account fields
        $(document).on('input', '.account-input', function() {
            clearTimeout(typingTimer);
            var $input = $(this);
            var accountType = $input.data('account-type');
            var searchTerm = $input.val();

            if (searchTerm.length >= 2) {
                typingTimer = setTimeout(function() {
                    searchAccounts(searchTerm, accountType, $input);
                }, doneTypingInterval);
            } else {
                $input.siblings('.account-suggestions').hide().empty();
            }
        });

        // Handle focus to show suggestions if there's already text
        $(document).on('focus', '.account-input', function() {
            var $input = $(this);
            var searchTerm = $input.val();
            var accountType = $input.data('account-type');
            
            if (searchTerm.length >= 2) {
                searchAccounts(searchTerm, accountType, $input);
            }
        });

        // Handle click outside to hide suggestions
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.account-input-wrapper').length) {
                $('.account-suggestions').hide();
            }
        });

        // Handle suggestion item click
        $(document).on('click', '.account-suggestion-item', function() {
            var $item = $(this);
            var accountId = $item.data('id');
            var accountText = $item.data('text');
            var $wrapper = $item.closest('.account-input-wrapper');

            $wrapper.find('.account-input').val(accountText);
            $wrapper.find('.account-id-input').val(accountId);
            $wrapper.find('.account-suggestions').hide().empty();
        });
    }

    // Search accounts via AJAX
    function searchAccounts(term, accountType, $input) {
        $.ajax({
            url: '{{ route("admin.accounting-accounts.search") }}',
            method: 'GET',
            data: {
                q: term,
                account_type: accountType
            },
            success: function(response) {
                var $suggestions = $input.siblings('.account-suggestions');
                $suggestions.empty();

                if (response.results && response.results.length > 0) {
                    $.each(response.results, function(index, account) {
                        var $item = $('<div>')
                            .addClass('account-suggestion-item')
                            .attr('data-id', account.id)
                            .attr('data-text', account.text)
                            .text(account.text);
                        $suggestions.append($item);
                    });
                    $suggestions.show();
                } else {
                    $suggestions.html('<div class="account-suggestion-item">@lang('messages.Aucun compte trouvé')</div>').show();
                }
            },
            error: function() {
                console.error('Error searching accounts');
            }
        });
    }

    // Currency formatting function
    function formatCurrency(amount) {
        // Format number with spaces as thousand separators
        var formatted = Number(amount).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return formatted + ' BIF';
    }

    // Initialize montant formatter for editable amount fields
    function initializeMontantFormatter() {
        $(document).on('input', '.montant-input', function() {
            var value = $(this).val();
            if (value) {
                var formatted = formatCurrency(value);
                $(this).siblings('.montant-formatted').text(formatted);
            }
        });
    }

    // Initialize on page load
    $(document).ready(function() {
        initializeAutocomplete();
        initializeMontantFormatter();
    });

</script>
@endsection