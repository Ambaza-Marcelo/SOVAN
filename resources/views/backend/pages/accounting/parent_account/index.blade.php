
@extends('backend.layouts.master')

@section('title')
@lang('messages.Parent Accounts') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    
    <style>
        .accordion-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .accordion-header:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .child-account {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            margin: 5px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .account-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
        }
        .account-details {
            flex: 1;
        }
        .account-actions {
            display: flex;
            gap: 10px;
        }
        .child-accounts-container {
            padding: 0 20px 20px 20px;
            background-color: #ffffff;
            border-radius: 0 0 8px 8px;
        }
        .no-children {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        .btn-group .btn {
            margin-left: 5px;
        }
        .btn-group .btn:first-child {
            margin-left: 0;
        }
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Parent Accounts')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
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
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="header-title">@lang('messages.Parent Accounts')</h4>
                        <div class="btn-group">
                            @if (Auth::guard('admin')->user()->can('account_parent_account.view'))
                            <a href="{{ route('admin.accounting-parent-accounts.export-excel') }}" class="btn btn-excel btn-info text-white">
                                <i class="fa fa-file-excel-o" title="@lang('messages.Export Chart of Accounts to Excel')"></i> @lang('messages.Export to Excel')
                            </a>
                            @endif
                            @if (Auth::guard('admin')->user()->can('account_parent_account.create'))
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#parentAccountModal">
                                <i class="fa fa-plus-square" title="@lang('messages.Create Parent Account')"></i> @lang('messages.Create Parent Account')
                            </button>
                            @endif
                            @if (Auth::guard('admin')->user()->can('account_account.create'))
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#childAccountModal">
                                <i class="fa fa-plus-circle" title="@lang('messages.Create Child Account')"></i> @lang('messages.Create Child Account')
                            </button>
                            @endif
                        </div>
                    </div>
                    
                    <div class="accordion" id="parentAccountsAccordion">
                        @foreach($parent_accounts as $index => $parent_account)
                        <div class="accordion-item">
                            <div class="accordion-header" id="heading{{ $parent_account->id }}">
                                <div class="account-info" data-toggle="collapse" data-target="#collapse{{ $parent_account->id }}" 
                                     aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $parent_account->id }}">
                                    <div class="account-details">
                                        <h5 class="mb-1">
                                            <i class="fa fa-chevron-down mr-2"></i>
                                            {{ $parent_account->account_number }} - {{ $parent_account->account_name }}
                                        </h5>
                                        <small>
                                            @lang('messages.account_type'): 
                                            @if($parent_account->account_type == '1') @lang('messages.accumulated_depreciation')
                                            @elseif($parent_account->account_type == '2') @lang('messages.Produits')
                                            @elseif($parent_account->account_type == '3') @lang('messages.bank')
                                            @elseif($parent_account->account_type == '4') @lang('messages.Petite Caisse')
                                            @elseif($parent_account->account_type == '5') @lang('messages.Caisse Recette')
                                            @elseif($parent_account->account_type == '6') @lang('messages.Caisse Devise')
                                            @elseif($parent_account->account_type == '7') @lang('messages.Caisse Dépense')
                                            @elseif($parent_account->account_type == '8') @lang('messages.current_asset')
                                            @elseif($parent_account->account_type == '9') @lang('messages.current_liability')
                                            @elseif($parent_account->account_type == '10') @lang('messages.depreciation')
                                            @elseif($parent_account->account_type == '11') @lang('messages.Débiteurs divers')
                                            @elseif($parent_account->account_type == '12') @lang('messages.Créditeurs divers')
                                            @elseif($parent_account->account_type == '13') @lang('messages.equity')
                                            @elseif($parent_account->account_type == '14') @lang('messages.expense_account')
                                            @elseif($parent_account->account_type == '15') @lang('messages.Clients')
                                            @elseif($parent_account->account_type == '16') @lang('messages.Fournisseurs')
                                            @elseif($parent_account->account_type == '17') @lang('messages.fixed_asset')
                                            @elseif($parent_account->account_type == '18') @lang('messages.Coût des Marchandises Vendues')
                                            @elseif($parent_account->account_type == '19') @lang('messages.Matières Premières Consommées')
                                            @elseif($parent_account->account_type == '20') @lang('messages.liability')
                                            @elseif($parent_account->account_type == '21') @lang('messages.Autres Fournitures Consommées')
                                            @elseif($parent_account->account_type == '22') @lang('messages.Transport Consommé')
                                            @elseif($parent_account->account_type == '23') @lang('messages.Autres Services Consommés')
                                            @elseif($parent_account->account_type == '24') @lang('messages.Autres Charges de gestion courante')
                                            @elseif($parent_account->account_type == '25') @lang('messages.stock')
                                            @elseif($parent_account->account_type == '26') @lang('messages.stock_adjustment')
                                            @elseif($parent_account->account_type == '27') @lang('messages.Charges du personnel')
                                            @elseif($parent_account->account_type == '28') @lang('messages.Intérêt Bancaire')
                                            @elseif($parent_account->account_type == '29') @lang('messages.tax')
                                            @elseif($parent_account->account_type == '30') @lang('messages.temporary')
                                            @elseif($parent_account->account_type == '31') @lang('messages.Personnel')
                                            @elseif($parent_account->account_type == '32') @lang('messages.Provisions')
                                            @endif
                                        </small>
                                        <br>
                                        <small>
                                            @lang('messages.currency'): 
                                            @if($parent_account->currency_id) {{ $parent_account->currency->currency_name }} @endif
                                        </small>
                                    </div>
                                    <div class="account-actions">
                                        @if (Auth::guard('admin')->user()->can('account_parent_account.edit'))
                                        <a class="btn btn-sm btn-warning text-white" href="{{ route('admin.accounting-parent-accounts.edit', $parent_account->id) }}">
                                            <i class="fa fa-edit" title="@lang('messages.edit')"></i>
                                        </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('account_parent_account.delete'))
                                        <a class="btn btn-sm btn-danger text-white" href="{{ route('admin.accounting-parent-accounts.destroy', $parent_account->id) }}"
                                           onclick="event.preventDefault(); if(confirm(@json(__('messages.accounting_delete_parent_account_confirm')))) { document.getElementById('delete-form-{{ $parent_account->id }}').submit(); }">
                                            <i class="fa fa-trash" title="@lang('messages.delete')"></i>
                                        </a>
                                        <form id="delete-form-{{ $parent_account->id }}" action="{{ route('admin.accounting-parent-accounts.destroy', $parent_account->id) }}" method="POST" style="display: none;">
                                            @method('DELETE')
                                            @csrf
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div id="collapse{{ $parent_account->id }}" class="collapse {{ $index === 0 ? 'show' : '' }}" 
                                 aria-labelledby="heading{{ $parent_account->id }}" data-parent="#parentAccountsAccordion">
                                <div class="child-accounts-container">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">@lang('messages.Child Accounts') ({{ $parent_account->account->count() }})</h6>
                                        @if (Auth::guard('admin')->user()->can('account_account.create'))
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#childAccountModal" data-parent-id="{{ $parent_account->id }}" data-parent-name="{{ $parent_account->account_name }}">
                                            <i class="fa fa-plus" title="@lang('messages.Add Child Account')"></i> @lang('messages.Add Child Account')
                                        </button>
                                        @endif
                                    </div>
                                    
                                    @if($parent_account->account->count() > 0)
                                        @foreach($parent_account->account as $child_account)
                                        <div class="child-account">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $child_account->account_number }} - {{ $child_account->account_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        @lang('messages.account_type'): 
                                                        @if($child_account->account_type == '1') @lang('messages.accumulated_depreciation')
                                                        @elseif($child_account->account_type == '2') @lang('messages.Produits')
                                                        @elseif($child_account->account_type == '3') @lang('messages.bank')
                                                        @elseif($child_account->account_type == '4') @lang('messages.Petite Caisse')
                                                        @elseif($child_account->account_type == '5') @lang('messages.Caisse Recette')
                                                        @elseif($child_account->account_type == '6') @lang('messages.Caisse Devise')
                                                        @elseif($child_account->account_type == '7') @lang('messages.Caisse Dépense')
                                                        @elseif($child_account->account_type == '8') @lang('messages.current_asset')
                                                        @elseif($child_account->account_type == '9') @lang('messages.current_liability')
                                                        @elseif($child_account->account_type == '10') @lang('messages.depreciation')
                                                        @elseif($child_account->account_type == '11') @lang('messages.Débiteurs divers')
                                                        @elseif($child_account->account_type == '12') @lang('messages.Créditeurs divers')
                                                        @elseif($child_account->account_type == '13') @lang('messages.equity')
                                                        @elseif($child_account->account_type == '14') @lang('messages.expense_account')
                                                        @elseif($child_account->account_type == '15') @lang('messages.Clients')
                                                        @elseif($child_account->account_type == '16') @lang('messages.Fournisseurs')
                                                        @elseif($child_account->account_type == '17') @lang('messages.fixed_asset')
                                                        @elseif($child_account->account_type == '18') @lang('messages.Coût des Marchandises Vendues')
                                                        @elseif($child_account->account_type == '19') @lang('messages.Matières Premières Consommées')
                                                        @elseif($child_account->account_type == '20') @lang('messages.liability')
                                                        @elseif($child_account->account_type == '21') @lang('messages.Autres Fournitures Consommées')
                                                        @elseif($child_account->account_type == '22') @lang('messages.Transport Consommé')
                                                        @elseif($child_account->account_type == '23') @lang('messages.Autres Services Consommés')
                                                        @elseif($child_account->account_type == '24') @lang('messages.Autres Charges de gestion courante')
                                                        @elseif($child_account->account_type == '25') @lang('messages.stock')
                                                        @elseif($child_account->account_type == '26') @lang('messages.stock_adjustment')
                                                        @elseif($child_account->account_type == '27') @lang('messages.Charges du personnel')
                                                        @elseif($child_account->account_type == '28') @lang('messages.Intérêt Bancaire')
                                                        @elseif($child_account->account_type == '29') @lang('messages.tax')
                                                        @elseif($child_account->account_type == '30') @lang('messages.temporary')
                                                        @elseif($child_account->account_type == '31') @lang('messages.Personnel')
                                                        @elseif($child_account->account_type == '32') @lang('messages.Provisions')
                                                        @endif
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        @lang('messages.currency'): 
                                                        @if($child_account->currency_id) {{ $child_account->currency->currency_name }} @endif
                                                    </small>
                                                </div>
                                                <div class="account-actions">
                                                    @if (Auth::guard('admin')->user()->can('account_account.edit'))
                                                    <a class="btn btn-sm btn-warning text-white" href="{{ route('admin.accounting-accounts.edit', $child_account->id) }}">
                                                        <i class="fa fa-edit" title="@lang('messages.edit')"></i>
                                                    </a>
                                                    @endif
                                                    @if (Auth::guard('admin')->user()->can('account_account.delete'))
                                                    <a class="btn btn-sm btn-danger text-white" href="{{ route('admin.accounting-accounts.destroy', $child_account->id) }}"
                                                       onclick="event.preventDefault(); if(confirm(@json(__('messages.accounting_delete_child_account_confirm')))) { document.getElementById('delete-child-form-{{ $child_account->id }}').submit(); }">
                                                        <i class="fa fa-trash" title="@lang('messages.delete')"></i>
                                                    </a>
                                                    <form id="delete-child-form-{{ $child_account->id }}" action="{{ route('admin.accounting-accounts.destroy', $child_account->id) }}" method="POST" style="display: none;">
                                                        @method('DELETE')
                                                        @csrf
                                                    </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="no-children">
                                            <i class="fa fa-info-circle"></i> @lang('messages.No child accounts found')
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('backend.pages.accounting.parent_account.create')
@include('backend.pages.accounting.account.create')
@endsection


@section('scripts')
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     
     <script>
         $(document).ready(function() {
             // Accordion functionality
             $('.accordion-header').click(function() {
                 var target = $(this).data('target');
                 var chevron = $(this).find('.fa-chevron-down');
                 
                 // Toggle chevron rotation
                 if ($(target).hasClass('show')) {
                     chevron.removeClass('fa-rotate-180');
                 } else {
                     chevron.addClass('fa-rotate-180');
                 }
             });
             
             // Initialize first accordion as open
             $('.accordion-header:first .fa-chevron-down').addClass('fa-rotate-180');
             
             // Reset parent account modal form when closed
             $('#parentAccountModal').on('hidden.bs.modal', function () {
                 $(this).find('form')[0].reset();
                 $(this).find('.form-control').removeClass('is-invalid');
                 $(this).find('.invalid-feedback').remove();
             });
             
             // Handle child account modal with parent pre-selection
             $('#childAccountModal').on('show.bs.modal', function (event) {
                 var button = $(event.relatedTarget);
                 var parentId = button.data('parent-id');
                 var parentName = button.data('parent-name');
                 var modal = $(this);
                 
                 // Reset form first
                 modal.find('form')[0].reset();
                 modal.find('.form-control').removeClass('is-invalid');
                 modal.find('.invalid-feedback').remove();
                 
                 // If parent ID is provided, pre-select the parent account
                 if (parentId) {
                     modal.find('#acc_parent_id option[value="' + parentId + '"]').prop('selected', true);
                     modal.find('#acc_parent_id option[disabled]').prop('selected', false);
                 }
             });
             
             // Reset child account modal form when closed
             $('#childAccountModal').on('hidden.bs.modal', function () {
                 $(this).find('form')[0].reset();
                 $(this).find('.form-control').removeClass('is-invalid');
                 $(this).find('.invalid-feedback').remove();
                 // Reset parent selection to default
                 $(this).find('#acc_parent_id option[disabled]').prop('selected', true);
                 $(this).find('#acc_parent_id option:not([disabled])').prop('selected', false);
             });

        //one checked box in checkbox group of balance_must_be
        var group_=(el,callback)=>{
            el.forEach((checkbox)=>{
            callback(checkbox)
            })
        }

        group_(document.getElementsByName('balance_must_be'),(item)=>{
        item.onclick=(e)=>{
        group_(document.getElementsByName('balance_must_be'),(item)=>{
        item.checked=false;
        })
        e.target.checked=true;
        }
        })
         });
     </script>
@endsection
