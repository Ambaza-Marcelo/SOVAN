
@extends('backend.layouts.master')

@section('title')
@lang('messages.Accounts') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Accounts')</h4>
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
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Accounts')</h4>
                    <p class="float-right mb-2">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#accountModal" data-whatever="@mdo"><i class="fa fa-plus-square" title="{{ __('messages.create') }}" aria-hidden="false"></i></button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.account_name')</th>
                                    <th width="10%">@lang('messages.account_number')</th>
                                    <th width="10%">@lang('messages.account_type')</th>
                                    <th width="10%">@lang('messages.balance_must_be')</th>
                                    <th width="10%">@lang('messages.Currency')</th>
                                    <th width="10%">@lang('messages.Status')</th>
                                    <th width="10%">@lang('messages.parent_account')</th>
                                    <th width="10%">@lang('messages.description')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($accounts as $account)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $account->account_name }}</td>
                                    <td>{{ $account->account_number }}</td>
                                    <td>@if($account->account_type == '1') @lang('messages.accumulated_depreciation')
                                    @elseif($account->account_type == '2') @lang('messages.Produits')
                                    @elseif($account->account_type == '3') @lang('messages.bank')
                                    @elseif($account->account_type == '4') @lang('messages.Petite Caisse')
                                    @elseif($account->account_type == '5') @lang('messages.Caisse Recette')
                                    @elseif($account->account_type == '6') @lang('messages.Caisse Devise')
                                    @elseif($account->account_type == '7') @lang('messages.Caisse Dépense')
                                    @elseif($account->account_type == '8') @lang('messages.current_asset')
                                    @elseif($account->account_type == '9') @lang('messages.current_liability')
                                    @elseif($account->account_type == '10') @lang('messages.depreciation')
                                    @elseif($account->account_type == '11') @lang('messages.Débiteurs divers')
                                    @elseif($account->account_type == '12') @lang('messages.Créditeurs divers')
                                    @elseif($account->account_type == '13') @lang('messages.equity')
                                    @elseif($account->account_type == '14') @lang('messages.expense_account')
                                    @elseif($account->account_type == '15') @lang('messages.Clients')
                                    @elseif($account->account_type == '16') @lang('messages.Fournisseurs')
                                    @elseif($account->account_type == '17') @lang('messages.fixed_asset')
                                    @elseif($account->account_type == '18') @lang('messages.Coût des Marchandises Vendues')
                                    @elseif($account->account_type == '19') @lang('messages.Matières Premières Consommées')
                                    @elseif($account->account_type == '20') @lang('messages.liability')
                                    @elseif($account->account_type == '21') @lang('messages.Autres Fournitures Consommées')
                                    @elseif($account->account_type == '22') @lang('messages.Transport Consommé')
                                    @elseif($account->account_type == '23') @lang('messages.Autres Services Consommés')
                                    @elseif($account->account_type == '24') @lang('messages.Autres Charges de gestion courante')
                                    @elseif($account->account_type == '25') @lang('messages.stock')
                                    @elseif($account->account_type == '26') @lang('messages.stock_adjustment')
                                    @elseif($account->account_type == '27') @lang('messages.Charges du personnel')
                                    @elseif($account->account_type == '28') @lang('messages.Intérêt Bancaire')
                                    @elseif($account->account_type == '29') @lang('messages.tax')
                                    @elseif($account->account_type == '30') @lang('messages.temporary')
                                    @elseif($account->account_type == '31') @lang('messages.Personnel')
                                    @elseif($account->account_type == '32') @lang('messages.Provisions')
                                    @endif</td>
                                    <td>@if($account->balance_must_be == '1') Debit @else Credit @endif</td>
                                    <td>@if($account->currency_id) {{ $account->currency->currency_name }}  @endif</td>
                                    <td>@if($account->active == 1) Active @else deactivated @endif</td>
                                    <td>@if($account->acc_parent_id) {{ $account->accParent->account_name }} @endif</td>
                                    <td>{{ $account->description }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('account_account.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.accounting-accounts.edit', $account->id) }}"><i class="fa fa-edit" title="Modifier" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('account_account.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.accounting-accounts.destroy', $account->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $account->id }}').submit(); }">
                                                <i class="fa fa-trash" title="Supprimer" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $account->id }}" action="{{ route('admin.currencies.destroy', $account->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                               @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>

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
         /*================================
        datatable active
        ==================================*/
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                responsive: true
            });
        }

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

     </script>
@endsection
