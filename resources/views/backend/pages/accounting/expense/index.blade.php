
@extends('backend.layouts.master')

@section('title')
@lang('messages.Expenses') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Expenses')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Expenses')</h4>
                    <p class="float-right mb-2">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#expenseModal" data-whatever="@mdo"><i class="fa fa-plus-square" title="Ajouter" aria-hidden="false"></i></button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.Account name')</th>
                                    <th width="10%">@lang('messages.Expense Code')</th>
                                    <th width="10%">@lang('messages.Cost')</th>
                                    <th width="10%">@lang('messages.Category')</th>
                                    <th width="10%">@lang('messages.Default Account')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($expenses as $expense)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $expense->name }}</td>
                                    <td>{{ $expense->code }}</td>
                                    <td>{{ number_format($expense->cost_price,2,',',' ') }}</td>
                                    <td>@if($expense->expense_category_id) {{ $expense->expenseCategory->name }}  @endif</td>
                                    <td>@if($expense->default_account_id) {{ $expense->defaultAccount->account_number }}-{{ $expense->defaultAccount->account_name }}  @endif</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('account_expense.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.accounting-expenses.edit', $expense->id) }}"><i class="fa fa-edit" title="Modifier" aria-hidden="false"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('account_expense.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.accounting-expenses.destroy', $expense->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.accounting_delete_expense_confirm')))) { document.getElementById('delete-form-{{ $expense->id }}').submit(); }">
                                                <i class="fa fa-trash" title="Supprimer" aria-hidden="false"></i>
                                            </a>

                                            <form id="delete-form-{{ $expense->id }}" action="{{ route('admin.accounting-expenses.destroy', $expense->id) }}" method="POST" style="display: none;">
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

@include('backend.pages.accounting.expense.create')
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
