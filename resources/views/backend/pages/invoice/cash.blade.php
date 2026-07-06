
@extends('backend.layouts.master')

@section('title')
@lang('messages.Validation de facture à cash') - @lang('messages.admin_panel')
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
        z-index: 1000;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 250px;
        overflow-y: auto;
        width: 100%;
        list-style: none;
        padding: 0;
        margin: 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-radius: 0 0 4px 4px;
    }
    .autocomplete-list li {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }
    .autocomplete-list li:hover {
        background-color: #f8f9fa;
    }
    .autocomplete-list li.active {
        background-color: #007bff;
        color: white;
    }
    .autocomplete-list li:last-child {
        border-bottom: none;
    }
    .autocomplete-list li.no-results,
    .no-results {
        padding: 10px 15px;
        color: #6c757d;
        font-style: italic;
        cursor: default;
    }
    .autocomplete-list li.no-results:hover {
        background-color: transparent;
        color: #6c757d;
    }
    .field-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Validation de facture à cash')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.invoice.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Validation de facture à cash')</span></li>
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
                    <h4 class="header-title">@lang('messages.Validation de facture à cash :') {{$facture->invoice_number}}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    @php
                        $selectedClientId = old('client_id', $facture->client_id);
                        $clientLabel = '';
                        if ($selectedClientId) {
                            $selectedClient = $clients->firstWhere('id', (int) $selectedClientId)
                                ?? $facture->client;
                            if ($selectedClient) {
                                $clientLabel = $selectedClient->customer_name . '/' . $selectedClient->telephone;
                            }
                        }

                        $selectedAccountId = old('account_id', $facture->account_id);
                        $accountLabel = '';
                        if ($selectedAccountId) {
                            $selectedAccount = $accounts->firstWhere('id', (int) $selectedAccountId);
                            if ($selectedAccount) {
                                $accountLabel = $selectedAccount->account_number . '-' . $selectedAccount->account_name;
                            }
                        }
                    @endphp

                    <form action="{{ route('admin.invoice.validate-as-cash', $facture->invoice_number) }}" method="POST" id="cashInvoiceForm">
                        @csrf
                        @method('PUT')
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
                                    <label for="client_id">@lang('messages.Nom du Client')</label>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" 
                                               class="form-control" 
                                               id="client_search" 
                                               value="{{ $clientLabel }}"
                                               placeholder="@lang('messages.Tapez pour rechercher un client...')"
                                               autocomplete="off"
                                               required>
                                        <input type="hidden" name="client_id" id="client_id" value="{{ $selectedClientId }}">
                                        <ul class="autocomplete-list" id="client_list" style="display:none;"></ul>
                                    </div>
                                    @error('client_id')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                    <small class="field-hint">@lang('messages.Tapez pour rechercher un client...')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_id">@lang('messages.Account')</label>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" 
                                               class="form-control" 
                                               id="account_search" 
                                               value="{{ $accountLabel }}"
                                               placeholder="@lang('messages.Tapez pour rechercher un compte...')"
                                               autocomplete="off" 
                                               required>
                                        <input type="hidden" name="account_id" id="account_id" value="{{ $selectedAccountId }}">
                                        <ul class="autocomplete-list" id="account_list" style="display:none;"></ul>
                                    </div>
                                    @error('account_id')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                    <small class="field-hint">Banque ou petite caisse — sélectionnez une ligne dans la liste.</small>
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
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.VALIDER A CASH')</button>
                            <a href="{{ route('admin.invoice.index') }}" class="btn btn-secondary mt-4 pr-4 pl-4">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
    var clients = [
        @foreach ($clients as $client)
        {
            id: {{ $client->id }},
            name: {!! json_encode($client->customer_name . '/' . $client->telephone) !!},
            customer_name: {!! json_encode($client->customer_name) !!},
            telephone: {!! json_encode($client->telephone) !!}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    var accounts = [
        @foreach($accounts as $account)
        {
            id: {{ $account->id }},
            display: {!! json_encode($account->account_number . '-' . $account->account_name) !!},
            account_number: {!! json_encode($account->account_number) !!},
            account_name: {!! json_encode($account->account_name) !!}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    var noResultsLabel = {!! json_encode(__('messages.Aucun résultat trouvé')) !!};
    var selectClientLabel = {!! json_encode('Veuillez sélectionner un client dans la liste.') !!};
    var selectAccountLabel = {!! json_encode('Veuillez sélectionner un compte caisse/banque dans la liste.') !!};

    function normalizeSearchText(value) {
        return (value || '').toString().toLowerCase().trim();
    }

    function filterAutocompleteItems(dataArray, searchFields, query) {
        var searchValue = normalizeSearchText(query);
        if (!searchValue) {
            return dataArray.slice(0, 50);
        }

        return dataArray.filter(function(item) {
            return searchFields.some(function(field) {
                var fieldValue = item[field] ? item[field].toString().toLowerCase() : '';
                return fieldValue.indexOf(searchValue) !== -1;
            });
        });
    }

    function setupAutocomplete(inputId, listId, hiddenId, dataArray, searchFields, displayField) {
        var input = document.getElementById(inputId);
        var list = document.getElementById(listId);
        var hidden = document.getElementById(hiddenId);
        var currentFocus = -1;

        if (!input || !list || !hidden) {
            return;
        }

        function syncSelectedState() {
            if (hidden.value) {
                var selected = dataArray.find(function(item) {
                    return String(item.id) === String(hidden.value);
                });
                if (selected) {
                    input.setAttribute('data-selected-id', selected.id);
                    input.setAttribute('data-selected-display', selected[displayField]);
                    if (!input.value.trim()) {
                        input.value = selected[displayField];
                    }
                }
            }
        }

        function clearSelection() {
            hidden.value = '';
            input.removeAttribute('data-selected-id');
            input.removeAttribute('data-selected-display');
        }

        function closeAllLists() {
            list.style.display = 'none';
            list.innerHTML = '';
            currentFocus = -1;
        }

        function selectItem(item) {
            input.value = item[displayField];
            hidden.value = item.id;
            input.setAttribute('data-selected-id', item.id);
            input.setAttribute('data-selected-display', item[displayField]);
            closeAllLists();
        }

        function renderList(matches) {
            list.innerHTML = '';

            if (!matches.length) {
                list.style.display = 'block';
                list.innerHTML = '<li class="no-results">' + noResultsLabel + '</li>';
                return;
            }

            list.style.display = 'block';

            matches.forEach(function(item) {
                var li = document.createElement('li');
                li.textContent = item[displayField];
                li.setAttribute('data-id', item.id);
                li.setAttribute('data-display', item[displayField]);

                li.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    selectItem(item);
                });

                list.appendChild(li);
            });
        }

        function addActive(items) {
            if (!items || !items.length) {
                return false;
            }

            for (var i = 0; i < items.length; i++) {
                items[i].classList.remove('active');
            }

            if (currentFocus >= items.length) {
                currentFocus = 0;
            }
            if (currentFocus < 0) {
                currentFocus = items.length - 1;
            }

            items[currentFocus].classList.add('active');
            items[currentFocus].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('input', function() {
            var val = this.value.trim();
            currentFocus = -1;

            var selectedDisplay = this.getAttribute('data-selected-display') || '';
            if (val !== selectedDisplay) {
                clearSelection();
            }

            renderList(filterAutocompleteItems(dataArray, searchFields, val));
        });

        input.addEventListener('focus', function() {
            renderList(filterAutocompleteItems(dataArray, searchFields, this.value.trim()));
        });

        input.addEventListener('keydown', function(e) {
            var items = list.querySelectorAll('li:not(.no-results)');

            if (e.keyCode === 40) {
                e.preventDefault();
                currentFocus++;
                addActive(items);
            } else if (e.keyCode === 38) {
                e.preventDefault();
                currentFocus--;
                addActive(items);
            } else if (e.keyCode === 13) {
                if (currentFocus > -1 && items[currentFocus]) {
                    e.preventDefault();
                    var selectedId = items[currentFocus].getAttribute('data-id');
                    var selected = dataArray.find(function(item) {
                        return String(item.id) === String(selectedId);
                    });
                    if (selected) {
                        selectItem(selected);
                    }
                }
            } else if (e.keyCode === 27) {
                closeAllLists();
            }
        });

        input.addEventListener('blur', function() {
            window.setTimeout(function() {
                closeAllLists();
                var val = input.value.trim();
                var selectedDisplay = input.getAttribute('data-selected-display') || '';

                if (!val) {
                    clearSelection();
                    return;
                }

                if (val !== selectedDisplay) {
                    var exactMatch = dataArray.find(function(item) {
                        return normalizeSearchText(item[displayField]) === normalizeSearchText(val);
                    });
                    if (exactMatch) {
                        selectItem(exactMatch);
                    } else {
                        clearSelection();
                    }
                }
            }, 150);
        });

        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !list.contains(e.target)) {
                closeAllLists();
            }
        });

        syncSelectedState();
    }

    $(document).ready(function() {
        setupAutocomplete(
            'client_search',
            'client_list',
            'client_id',
            clients,
            ['name', 'customer_name', 'telephone'],
            'name'
        );

        setupAutocomplete(
            'account_search',
            'account_list',
            'account_id',
            accounts,
            ['display', 'account_number', 'account_name'],
            'display'
        );

        if (accounts.length === 1 && !$('#account_id').val()) {
            var onlyAccount = accounts[0];
            $('#account_search').val(onlyAccount.display);
            $('#account_id').val(onlyAccount.id);
            $('#account_search').attr('data-selected-id', onlyAccount.id);
            $('#account_search').attr('data-selected-display', onlyAccount.display);
        }

        $('#cashInvoiceForm').on('submit', function(e) {
            var clientId = $('#client_id').val();
            var accountId = $('#account_id').val();
            var clientText = $('#client_search').val().trim();
            var accountText = $('#account_search').val().trim();

            if (!clientId || !clientText) {
                e.preventDefault();
                alert(selectClientLabel);
                $('#client_search').focus();
                return false;
            }

            if (!accountId || !accountText) {
                e.preventDefault();
                alert(selectAccountLabel);
                $('#account_search').focus();
                return false;
            }

            return true;
        });
    });
</script>
@endsection
