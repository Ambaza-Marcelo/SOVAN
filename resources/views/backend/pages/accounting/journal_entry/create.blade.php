
@extends('backend.layouts.master')

@section('title')
@lang('messages.new journal entry') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    
    #total-debit, #total-credit, #balance {
        font-weight: bold;
        font-size: 16px;
    }
    
    .text-success {
        color: #28a745 !important;
    }
    
    .text-warning {
        color: #ffc107 !important;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    
    /* Account autocomplete suggestions styling */
    .account-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .account-suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.2s;
    }
    
    .account-suggestion-item:hover {
        background-color: #f8f9fa;
    }
    
    .account-suggestion-item:last-child {
        border-bottom: none;
    }
    
    .account-suggestion-title {
        font-weight: bold;
        color: #333;
        font-size: 14px;
    }
    
    .account-suggestion-desc {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }
    
    .account-suggestion-item.highlighted {
        background-color: #007bff;
        color: white;
    }
    
    .account-suggestion-item.highlighted .account-suggestion-title,
    .account-suggestion-item.highlighted .account-suggestion-desc {
        color: white;
    }
    
    /* Position relative for autocomplete container */
    td {
        position: relative;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.new journal entry')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.journal-entry.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.new journal entry')</span></li>
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
                    <h4 class="header-title">@lang('messages.new journal entry')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.journal-entry.store') }}" method="post" id="dynamic_form">
                        @csrf

                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date">
                            </div>
                        </div>
                        <div class="col-md-6" id="dynamicDiv">
                            <label for="type">@lang('messages.Type') <span class="text-danger">*</span></label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">@lang('messages.Select Type')</option>
                                <option value="NORMAL" {{ old('type') == 'NORMAL' ? 'selected' : '' }}>@lang('messages.NORMAL')</option>
                                <option value="OPENING" {{ old('type') == 'OPENING' ? 'selected' : '' }}>@lang('messages.Opening Balance')</option>
                                <option value="RECONCILIATION" {{ old('type') == 'RECONCILIATION' ? 'selected' : '' }}>@lang('messages.Reconciliation')</option>
                            </select>
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <thead>
                                <tr>
                                <th>@lang('messages.Account')</th>
                                <th>@lang('messages.Document No')</th>
                                <th>@lang('messages.Libelle')</th>
                                <th>@lang('messages.Debit')</th>
                                <th>@lang('messages.Credit')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr>    
                                <td>
                                    <input type="text" class="form-control account-input" name="account_display[]" placeholder="@lang('messages.Rechercher un compte (numéro ou nom)...')" autocomplete="off" required />
                                    <input type="hidden" class="account-id-input" name="account_id[]" />
                                </td> 
                                <td><input type="text" name="document_no[]" class="form-control" placeholder="@lang('messages.Enter document no')" required /></td>
                                <td><input type="text" name="libelle[]" class="form-control" placeholder="@lang('messages.Enter libelle')" required /></td>
                                <td><input type="number" name="debit[]" class="form-control debit-input" placeholder="@lang('messages.Enter debit')" min="0" step="any" required /></td> 
                                <td><input type="number" name="credit[]" class="form-control credit-input" placeholder="@lang('messages.Enter credit')" min="0" step="any" required /></td>
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>      
                            </tr>
                            </tbody>
                        </table>
                        
                        <!-- Totals Section -->
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <table class="table table-bordered">
                                    <tr>
                                        <td><strong>@lang('messages.Total Debit:')</strong></td>
                                        <td><span id="total-debit">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('messages.Total Credit:')</strong></td>
                                        <td><span id="total-credit">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('messages.Balance:')</strong></td>
                                        <td><span id="balance">0.00</span></td>
                                    </tr>
                                </table>
                            </div>
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
    var i = 0;
       
    $(document).on('click', '#add', function(){
        // Check if type is OPENING - don't allow adding rows manually
        var selectedType = $('#type').val();
        if (selectedType === 'OPENING') {
            alert(i18n.openingNoManualRows);
            return false;
        }
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<input type='text' class='form-control account-input' name='account_display[]' placeholder='" + i18n.searchAccount + "' autocomplete='off' required />"+
                         "<input type='hidden' class='account-id-input' name='account_id[]' />"+
                        "</td>"+
                        "<td>"+
                          "<input type='text' name='document_no[]' placeholder='" + i18n.enterDocumentNo + "' class='form-control' required/>"+
                        "</td>"+
                        "<td>"+
                          "<input type='text' name='libelle[]' placeholder='" + i18n.enterLibelle + "' class='form-control' required/>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='debit[]' placeholder='" + i18n.enterDebit + "' class='form-control debit-input' step='any' min='0' required/>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='credit[]' placeholder='" + i18n.enterCredit + "' class='form-control credit-input' step='any' min='0' required/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                        "</td>"+
                    "</tr>";
   
        $("#dynamicTable tbody").append(markup);
        
        // Pré-remplir document_no et libelle depuis la première ligne
        var firstRow = $("#dynamicTable tbody tr:first");
        var newRow = $("#dynamicTable tbody tr:last");
        newRow.find("input[name='document_no[]']").val(firstRow.find("input[name='document_no[]']").val());
        newRow.find("input[name='libelle[]']").val(firstRow.find("input[name='libelle[]']").val());
        
        // Initialize autocomplete for the new row
        initializeAccountAutocomplete();
        
        // Recalculate totals
        calculateTotals();
    });
   
    // Quand on modifie document_no ou libelle sur la première ligne, propager aux autres lignes
    $(document).on('input change', '#dynamicTable tbody tr:first input[name="document_no[]"], #dynamicTable tbody tr:first input[name="libelle[]"]', function() {
        var firstRow = $("#dynamicTable tbody tr:first");
        var docNo = firstRow.find("input[name='document_no[]']").val();
        var libelleVal = firstRow.find("input[name='libelle[]']").val();
        $("#dynamicTable tbody tr:not(:first)").each(function() {
            $(this).find("input[name='document_no[]']").val(docNo);
            $(this).find("input[name='libelle[]']").val(libelleVal);
        });
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
         calculateTotals(); // Recalculate totals when row is removed
    });

    // Auto-completion and validation logic
    $(document).on('input', '.debit-input', function() {
        var row = $(this).closest('tr');
        var debitValue = parseFloat($(this).val()) || 0;
        var creditInput = row.find('.credit-input');
        
        // If debit has value, set credit to 0
        if (debitValue > 0) {
            creditInput.val('0');
        }
        
        calculateTotals();
    });

    $(document).on('input', '.credit-input', function() {
        var row = $(this).closest('tr');
        var creditValue = parseFloat($(this).val()) || 0;
        var debitInput = row.find('.debit-input');
        
        // If credit has value, set debit to 0
        if (creditValue > 0) {
            debitInput.val('0');
        }
        
        calculateTotals();
    });

    // Function to calculate totals
    function calculateTotals() {
        var totalDebit = 0;
        var totalCredit = 0;
        
        $('.debit-input').each(function() {
            var value = parseFloat($(this).val()) || 0;
            totalDebit += value;
        });
        
        $('.credit-input').each(function() {
            var value = parseFloat($(this).val()) || 0;
            totalCredit += value;
        });
        
        var balance = totalCredit - totalDebit;
        
        // Update display
        $('#total-debit').text(totalDebit.toFixed(2));
        $('#total-credit').text(totalCredit.toFixed(2));
        $('#balance').text(balance.toFixed(2));
        
        // Color coding for balance
        var balanceElement = $('#balance');
        if (balance === 0) {
            balanceElement.removeClass('text-danger text-warning').addClass('text-success');
        } else if (balance > 0) {
            balanceElement.removeClass('text-success text-danger').addClass('text-warning');
        } else {
            balanceElement.removeClass('text-success text-warning').addClass('text-danger');
        }
        
        // Validate form submission
        validateForm(totalDebit, totalCredit);
    }

    // Form validation
    function validateForm(totalDebit, totalCredit) {
        var saveButton = $('#save');
        
        if (totalDebit === totalCredit && totalDebit > 0) {
            saveButton.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
        } else {
            saveButton.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
        }
    }

    // Initial calculation
    calculateTotals();

    // Account autocomplete (AJAX)
    var accountSearchUrl = '{{ route("admin.accounting-accounts.search") }}';

    function filterOpeningAccounts(results) {
        if ($('#type').val() !== 'OPENING') return results;
        return results.filter(function(r) {
            var num = ((r.text || '').split('-')[0] || '').trim();
            var first = num.charAt(0);
            return first !== '6' && first !== '7';
        });
    }

    function initAccountRowAutocomplete($input) {
        if (!$input || !$input.length || $input.data('ac-init')) return;
        $input.data('ac-init', true);
        attachAjaxAutocomplete({
            $input: $input,
            $hidden: $input.siblings('.account-id-input'),
            url: accountSearchUrl,
            minChars: 1,
            filterResults: filterOpeningAccounts,
            renderItem: function(item) {
                var t = (item.text || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                return '<div><strong>' + t + '</strong></div>';
            }
        });
    }

    function initializeAccountAutocomplete() {
        $('#dynamicTable .account-input').each(function() {
            initAccountRowAutocomplete($(this));
        });
    }

    // Initialize autocomplete on page load
    $(document).ready(function() {
        initializeAccountAutocomplete();
        
        // Handle type change for OPENING balance
        $('#type').on('change', function() {
            var selectedType = $(this).val();
            
            if (selectedType === 'OPENING') {
                if (confirm(@json(__('messages.journal_entry_opening_type_confirm')))) {
                loadOpeningBalance();
                    // Disable add button for OPENING type
                    toggleAddButton(false);
                } else {
                    $(this).val('');
                }
            } else {
                // Reset to single empty row if not OPENING (NORMAL or RECONCILIATION)
                resetToDefaultRow();
                // Enable add button for NORMAL and RECONCILIATION
                toggleAddButton(true);
            }
        });
        
        // Function to toggle add button based on type
        function toggleAddButton(enable) {
            $('#add').prop('disabled', !enable);
            if (enable) {
                $('#add').removeClass('btn-secondary').addClass('btn-success');
            } else {
                $('#add').removeClass('btn-success').addClass('btn-secondary');
            }
        }
        
        // Initialize button state based on current type
        var currentType = $('#type').val();
        if (currentType === 'OPENING') {
            toggleAddButton(false);
        } else {
            toggleAddButton(true);
        }
    });
    
    // Function to load opening balance data
    function loadOpeningBalance() {
        // Show loading indicator
        var loadingMsg = '<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> @lang('messages.Chargement des soldes d\\\'ouverture...')</td></tr>';
        $('#dynamicTable tbody').html(loadingMsg);
        
        $.ajax({
            url: '{{ route("admin.journal-entry.opening-balance") }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Clear existing rows
                    $('#dynamicTable tbody').empty();
                    
                    // Populate rows with opening balance data
                    response.data.forEach(function(account, index) {
                        var isLastRow = index === response.data.length - 1;
                        var accountDisplay = $('<div>').text(account.account_display).html();
                        var libelleText = 'Solde d\'ouverture ' + response.previous_year;
                        var libelleEscaped = $('<div>').text(libelleText).html();
                        
                        var rowMarkup = "<tr>"+
                            "<td>"+
                                "<input type='text' class='form-control account-input' name='account_display[]' value='" + accountDisplay + "' placeholder='" + i18n.searchAccount + "' autocomplete='off' required />"+
                                "<input type='hidden' class='account-id-input' name='account_id[]' value='" + account.account_id + "' />"+
                            "</td>"+
                            "<td>"+
                                "<input type='text' name='document_no[]' placeholder='" + i18n.enterDocumentNo + "' class='form-control' value='OUV-" + response.previous_year + "' required/>"+
                            "</td>"+
                            "<td>"+
                                "<input type='text' name='libelle[]' placeholder='" + i18n.enterLibelle + "' class='form-control' value='" + libelleEscaped + "' required/>"+
                            "</td>"+
                            "<td>"+
                                "<input type='number' name='debit[]' placeholder='" + i18n.enterDebit + "' class='form-control debit-input' step='any' min='0' value='" + account.debit + "' required/>"+
                            "</td>"+
                            "<td>"+
                                "<input type='number' name='credit[]' placeholder='" + i18n.enterCredit + "' class='form-control credit-input' step='any' min='0' value='" + account.credit + "' required/>"+
                            "</td>"+
                            "<td>"+
                                (isLastRow ? 
                                    "<button type='button' name='add' id='add' class='btn btn-success'>" + i18n.addmore + "</button>" :
                                    "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"
                                )+
                            "</td>"+
                        "</tr>";
                        
                        $('#dynamicTable tbody').append(rowMarkup);
                    });
                    
                    // Reinitialize autocomplete for new rows
                    initializeAccountAutocomplete();
                    
                    // Recalculate totals
                    calculateTotals();
                    
                    // Disable add button for OPENING type
                    $('#add').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
                } else {
                    // No opening balance data - show all accounts with empty fields
                    loadAllAccountsEmpty();
                    // Disable add button for OPENING type
                    $('#add').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading opening balance:', error);
                alert('Erreur lors du chargement des soldes d\'ouverture. Veuillez réessayer.');
                resetToDefaultRow();
            }
        });
    }
    
    // Function to load all accounts with empty fields (OPENING: exclude accounts starting with 6 or 7)
    function loadAllAccountsEmpty() {
        var accountsData = @json($accounts);
        accountsData = accountsData.filter(function(account) {
            var first = (account.account_number || '').charAt(0);
            return first !== '6' && first !== '7';
        });
        
        if (accountsData.length > 0) {
            $('#dynamicTable tbody').empty();
            
            var currentYear = new Date().getFullYear();
            var previousYear = currentYear - 1;
            
            accountsData.forEach(function(account, index) {
                var isLastRow = index === accountsData.length - 1;
                var accountDisplay = account.account_number + '-' + account.account_name;
                var accountDisplayEscaped = $('<div>').text(accountDisplay).html();
                var libelleText = 'Solde d\'ouverture ' + previousYear;
                var libelleEscaped = $('<div>').text(libelleText).html();
                
                var rowMarkup = "<tr>"+
                    "<td>"+
                        "<input type='text' class='form-control account-input' name='account_display[]' value='" + accountDisplayEscaped + "' placeholder='" + i18n.searchAccount + "' autocomplete='off' required />"+
                        "<input type='hidden' class='account-id-input' name='account_id[]' value='" + account.id + "' />"+
                    "</td>"+
                    "<td>"+
                        "<input type='text' name='document_no[]' placeholder='" + i18n.enterDocumentNo + "' class='form-control' value='OUV-" + previousYear + "' required/>"+
                    "</td>"+
                    "<td>"+
                        "<input type='text' name='libelle[]' placeholder='" + i18n.enterLibelle + "' class='form-control' value='" + libelleEscaped + "' required/>"+
                    "</td>"+
                    "<td>"+
                        "<input type='number' name='debit[]' placeholder='" + i18n.enterDebit + "' class='form-control debit-input' step='any' min='0' value='0' required/>"+
                    "</td>"+
                    "<td>"+
                        "<input type='number' name='credit[]' placeholder='" + i18n.enterCredit + "' class='form-control credit-input' step='any' min='0' value='0' required/>"+
                    "</td>"+
                    "<td>"+
                        (isLastRow ? 
                            "<button type='button' name='add' id='add' class='btn btn-success'>" + i18n.addmore + "</button>" :
                            "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"
                        )+
                    "</td>"+
                "</tr>";
                
                $('#dynamicTable tbody').append(rowMarkup);
            });
            
            // Reinitialize autocomplete for new rows
            initializeAccountAutocomplete();
            
            // Recalculate totals
            calculateTotals();
            
            // Disable add button for OPENING type
            $('#add').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
        }
    }
    
    // Function to reset to default single row
    function resetToDefaultRow() {
        var defaultRow = "<tr>"+
            "<td>"+
                "<input type='text' class='form-control account-input' name='account_display[]' placeholder='" + i18n.searchAccount + "' autocomplete='off' required />"+
                "<input type='hidden' class='account-id-input' name='account_id[]' />"+
            "</td>"+
            "<td><input type='text' name='document_no[]' class='form-control' placeholder='" + i18n.enterDocumentNo + "' required /></td>"+
            "<td><input type='text' name='libelle[]' class='form-control' placeholder='" + i18n.enterLibelle + "' required /></td>"+
            "<td><input type='number' name='debit[]' class='form-control debit-input' placeholder='" + i18n.enterDebit + "' min='0' step='any' required /></td>"+
            "<td><input type='number' name='credit[]' class='form-control credit-input' placeholder='" + i18n.enterCredit + "' min='0' step='any' required /></td>"+
            "<td><button type='button' name='add' id='add' class='btn btn-success'>" + i18n.addmore + "</button></td>"+
        "</tr>";
        
        $('#dynamicTable tbody').html(defaultRow);
        initializeAccountAutocomplete();
        calculateTotals();
        
        // Ensure add button is enabled for NORMAL and RECONCILIATION
        var selectedType = $('#type').val();
        if (selectedType !== 'OPENING') {
            $('#add').prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
        }
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
