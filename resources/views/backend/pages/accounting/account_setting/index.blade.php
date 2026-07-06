
@extends('backend.layouts.master')

@section('title')
@lang('messages.account settings') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.account settings')</h4>
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
                    <h4 class="header-title">@lang('messages.account settings')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <div class="text-right mb-3">
                        @if (Auth::guard('admin')->user()->can('account_setting.create'))
                        <a href="{{ route('admin.accounting-settings.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> @lang('messages.create')
                        </a>
                        @endif
                    </div>

                    <div class="data-tables">
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">@lang('messages.ID')</th>
                                    <th width="10%">@lang('messages.Code')</th>
                                    <th width="10%">@lang('messages.Date')</th>
                                    <th width="15%">@lang('messages.Fiscal Year')</th>
                                    <th width="10%">@lang('messages.Currency')</th>
                                    <th width="10%">@lang('messages.Active')</th>
                                    <th width="15%">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($settings as $setting)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td><a href="{{ route('admin.accounting-settings.show',$setting->id) }}">{{ $setting->code }}</a></td>
                                    <td>{{ $setting->date }}</td>
                                    <td>{{ $setting->fiscal_year }}</td>
                                    <td>
                                        @if($setting->currency_id)
                                            {{ App\Models\Accounting\AccountCurrency::find($setting->currency_id)->name ?? 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($setting->active == 1)
                                            <span class="badge badge-success">@lang('messages.Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('messages.Inactive')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('account_setting.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.accounting-settings.edit', $setting->id) }}">@lang('messages.edit')</a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('account_setting.delete'))
                                        <a class="btn btn-danger text-white" href="{{ route('admin.accounting-settings.destroy', $setting->id) }}"
                                        onclick="event.preventDefault(); 
                                        if(confirm(@json(__('messages.accounting_delete_setting_confirm')))) {
                                            document.getElementById('delete-form-{{ $setting->id }}').submit();
                                        }">
                                            @lang('messages.delete')
                                        </a>

                                        <form id="delete-form-{{ $setting->id }}" action="{{ route('admin.accounting-settings.destroy', $setting->id) }}" method="POST" style="display: none;">
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

     </script>
@endsection