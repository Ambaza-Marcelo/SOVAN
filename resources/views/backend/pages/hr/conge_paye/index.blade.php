@extends('backend.layouts.master')

@section('title')
@lang('Congé Payé') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
@endsection

@section('admin-content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Congé Payé')</h4>
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

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">Congé Payé</h4>
                    <p class="float-right mb-2">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#congePayeModal">
                            <i class="fa fa-plus-square" title="Ajouter" aria-hidden="false"></i>
                        </button>
                    </p>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Session</th>
                                    <th>Nombre de jours</th>
                                    <th>Etat</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($conge_payes as $conge_paye)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ $conge_paye->session }}</td>
                                    <td>{{ $conge_paye->nbre_jours }}</td>
                                    <td>{{ $conge_paye->etat }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.edit'))
                                            <a class="btn btn-success text-white" href="{{ route('admin.hr-conge-payes.edit', $conge_paye->id) }}"><i class="fa fa-edit"></i></a>
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('hr_conge_paye.delete'))
                                            <a class="btn btn-danger text-white" href="{{ route('admin.hr-conge-payes.destroy', $conge_paye->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $conge_paye->id }}').submit(); }">
                                                <i class="fa fa-trash"></i>
                                            </a>

                                            <form id="delete-form-{{ $conge_paye->id }}" action="{{ route('admin.hr-conge-payes.destroy', $conge_paye->id) }}" method="POST" style="display: none;">
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
    </div>
</div>
@include('backend.pages.hr.conge_paye.create')
@endsection

@section('scripts')
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     <script>
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({ responsive: true });
        }
     </script>
@endsection
