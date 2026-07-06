
@extends('backend.layouts.master')

@section('title')
@lang('messages.setting') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.setting')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Setting')</h4>
                    <p class="float-right mb-2">
                            <a class="btn btn-primary text-white" href="{{ route('admin.settings.create') }}">@lang('messages.Create New')</a>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">@lang('messages.logo')</th>
                                    <th width="10%">@lang('messages.Nom')</th>
                                    <th width="10%">@lang('messages.NIF')</th>
                                    <th width="10%">@lang('messages.RC')</th>
                                    <th width="10%">@lang('messages.Commune')</th>
                                    <th width="10%">@lang('messages.Zone')</th>
                                    <th width="10%">@lang('messages.Quartier')</th>
                                    <th width="10%">@lang('messages.Rue')</th>
                                    <th width="10%">@lang('messages.Telephone 1')</th>
                                    <th width="10%">@lang('messages.Telephone 2')</th>
                                    <th width="10%">@lang('messages.Email')</th>
                                    <th width="10%">@lang('messages.Devloppeur')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($settings as $setting)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td> <img class="img-responsive" style="max-height: 50px;" src="{{ asset('storage/logo')}}/{{ $setting->logo }}" alt=""></td>
                                    <td>{{ $setting->name }}</td>
                                    <td>{{ $setting->nif }}</td>
                                    <td>{{ $setting->rc }}</td>
                                    <td>{{ $setting->commune }}</td>
                                    <td>{{ $setting->zone }}</td>
                                    <td>{{ $setting->quartier }}</td>
                                    <td>{{ $setting->rue }}</td>
                                    <td>{{ $setting->telephone1 }}</td>
                                    <td>{{ $setting->telephone2 }}</td>
                                    <td>{{ $setting->email }}</td>
                                    <td>{{ $setting->developpeur }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('setting.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.settings.edit', $setting->id) }}">Edit</a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('setting.edit'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.settings.destroy', $setting->id) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $setting->id }}').submit(); }">
                                                Delete
                                            </a>

                                            <form id="delete-form-{{ $setting->id }}" action="{{ route('admin.settings.destroy', $setting->id) }}" method="POST" style="display: none;">
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