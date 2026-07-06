@extends('backend.layouts.master')

@section('title')
@lang('messages.goods_import_menu') — @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.goods_import_menu')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.goods_import_menu')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('goods_import.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.imports.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.imports.index', 'exportRoute' => null])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.N° import')</th>
                                    <th width="10%">@lang('messages.Signature')</th>
                                    <th width="5%">@lang('messages.status')</th>
                                    <th width="10%">@lang('messages.receptionist')</th>
                                    <th width="10%">@lang('messages.Remettant')</th>
                                    <th width="8%">@lang('messages.Type')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="12%">@lang('messages.Description')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($imports as $row)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.imports.show', $row->import_no) }}">{{ $row->import_no }}</a></td>
                                    <td>{{ $row->import_signature }}</td>
                                    <td>
                                        @if((string)$row->status === '1')<img src="{{ asset('img/warning3.gif')}}" width="35" alt="">
                                        @elseif((string)$row->status === '-1')<span class="badge badge-danger">@lang('messages.Rejeté')</span>
                                        @elseif((string)$row->status === '2')<span class="badge badge-info">@lang('messages.Validé')</span>
                                        @elseif((string)$row->status === '3')<span class="badge badge-info">@lang('messages.Confirmé')</span>
                                        @elseif((string)$row->status === '4')<span class="badge badge-success">@lang('messages.Approuvé')</span>
                                        @else<span class="badge badge-secondary">{{ $row->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->receptionist }}</td>
                                    <td>{{ $row->handingover }}</td>
                                    <td>@lang('messages.EN')</td>
                                    <td>{{ $row->created_by }}</td>
                                    <td>{{ Str::limit($row->description, 40) }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('goods_import.validate') && (string)$row->status === '1')
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.imports.validate', $row->import_no) }}"
                                               onclick="event.preventDefault(); document.getElementById('validate-form-{{ $row->import_no }}').submit();">Valider</a>
                                            <form id="validate-form-{{ $row->import_no }}" action="{{ route('admin.imports.validate', $row->import_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_import.confirm') && (string)$row->status === '2')
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.imports.confirm', $row->import_no) }}"
                                               onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $row->import_no }}').submit();">Confirmer</a>
                                            <form id="confirm-form-{{ $row->import_no }}" action="{{ route('admin.imports.confirm', $row->import_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_import.approuve') && (string)$row->status === '3')
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.imports.approuve', $row->import_no) }}"
                                               onclick="event.preventDefault(); document.getElementById('approuve-form-{{ $row->import_no }}').submit(); this.style.visibility='hidden';">Approuver</a>
                                            <form id="approuve-form-{{ $row->import_no }}" action="{{ route('admin.imports.approuve', $row->import_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_import.reject') && in_array((string)$row->status, ['1','2','3'], true))
                                            <a class="btn btn-warning text-white btn-sm" href="{{ route('admin.imports.reject', $row->import_no) }}"
                                               onclick="event.preventDefault(); var m = prompt('Motif de rejet:'); if(m) { document.getElementById('reject-motif-{{ $row->import_no }}').value = m; document.getElementById('reject-form-{{ $row->import_no }}').submit(); }">Rejeter</a>
                                            <form id="reject-form-{{ $row->import_no }}" action="{{ route('admin.imports.reject', $row->import_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                                <input type="hidden" name="rejected_motif" id="reject-motif-{{ $row->import_no }}">
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_import.reset') && in_array((string)$row->status, ['-1','2','3'], true))
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.imports.reset', $row->import_no) }}"
                                               onclick="event.preventDefault(); document.getElementById('reset-form-{{ $row->import_no }}').submit();">Réinitialiser</a>
                                            <form id="reset-form-{{ $row->import_no }}" action="{{ route('admin.imports.reset', $row->import_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_import.edit') && (string)$row->status !== '4')
                                            <a class="btn btn-success text-white btn-sm" href="{{ route('admin.imports.edit', $row->import_no) }}">@lang('messages.edit')</a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_import.delete') && in_array((string)$row->status, ['-1','1'], true))
                                            <a class="btn btn-danger text-white btn-sm" href="{{ route('admin.imports.destroy', $row->import_no) }}"
                                               onclick="event.preventDefault(); if(confirm(@json(__('messages.import_delete_confirm')))) { document.getElementById('delete-form-{{ $row->import_no }}').submit(); }">@lang('messages.delete')</a>
                                            <form id="delete-form-{{ $row->import_no }}" action="{{ route('admin.imports.destroy', $row->import_no) }}" method="POST" style="display: none;">
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
@endsection

@section('scripts')
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     <script>
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                responsive: true,
                order: [[1, 'desc']],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
            });
        }
     </script>
@endsection
