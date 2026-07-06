@extends('backend.layouts.master')

@section('title')
@lang('messages.stockout') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.stockout')</h4>
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
                    <h4 class="header-title float-left">@lang('messages.Sorties Marchandises')</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('goods_stockout.create'))
                            <a class="btn btn-primary text-white" href="{{ route('admin.goods-stockouts.create') }}">@lang('messages.new')</a>
                        @endif
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials._index_filter_export', ['filterRoute' => 'admin.goods-stockouts.index', 'exportRoute' => null])
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">@lang('messages.date')</th>
                                    <th width="10%">@lang('messages.Stockout No')</th>
                                    <th width="10%">@lang('messages.Stockout Signature')</th>
                                    <th width="5%">@lang('messages.status')</th>
                                    <th width="10%">@lang('messages.Demandeur')</th>
                                    <th width="10%">@lang('messages.Destination')</th>
                                    <th width="10%">@lang('messages.Type Sortie')</th>
                                    <th width="10%">@lang('messages.created_by')</th>
                                    <th width="20%">@lang('messages.description')</th>
                                    <th width="15%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach ($stockouts as $stockout)
                               <tr>
                                    <td>{{ $loop->index+1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($stockout->date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('admin.goods-stockouts.show',$stockout->stockout_no)}}">{{ $stockout->stockout_no }}</a></td>
                                    <td>{{ $stockout->stockout_signature }}</td>
                                    <td>@if($stockout->status == 1)<img src="{{ asset('img/warning3.gif')}}" width="35">@elseif($stockout->status == -1)<span class="badge badge-danger">@lang('messages.Rejeté')</span> @elseif($stockout->status == 2)<span class="badge badge-info">@lang('messages.Validé')</span> @elseif($stockout->status == 3)<span class="badge badge-info">@lang('messages.Confirmé')</span> @elseif($stockout->status == 4)<span class="badge badge-success">@lang('messages.Approuvé')</span>@endif</td>
                                    <td>{{ $stockout->asker }}</td>
                                    <td>{{ $stockout->destination }}</td>
                                    <td>
                                        @if($stockout->item_movement_type == 'SN') Sortie Normale
                                        @elseif($stockout->item_movement_type == 'SP') Sortie Perte
                                        @elseif($stockout->item_movement_type == 'SV') Sortie Vol
                                        @elseif($stockout->item_movement_type == 'SC') Sortie Casse
                                        @elseif($stockout->item_movement_type == 'SAJ') Sortie Ajustement
                                        @elseif($stockout->item_movement_type == 'SAU') Sortie Autre
                                        @else {{ $stockout->item_movement_type }}
                                        @endif
                                    </td>
                                    <td>{{ $stockout->created_by }}</td>
                                    <td>{{ $stockout->description }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.validate'))
                                        @if($stockout->status == 1)
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.goods-stockouts.validate', $stockout->stockout_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('validate-form-{{ $stockout->stockout_no }}').submit();">
                                                Valider
                                            </a>

                                            <form id="validate-form-{{ $stockout->stockout_no }}" action="{{ route('admin.goods-stockouts.validate', $stockout->stockout_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.confirm'))
                                        @if($stockout->status == 2)
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.goods-stockouts.confirm', $stockout->stockout_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('confirm-form-{{ $stockout->stockout_no }}').submit();">
                                                Confirmer
                                            </a>

                                            <form id="confirm-form-{{ $stockout->stockout_no }}" action="{{ route('admin.goods-stockouts.confirm', $stockout->stockout_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.approuve'))
                                        @if($stockout->status == 3)
                                            <a class="btn btn-primary text-white btn-sm" href="{{ route('admin.goods-stockouts.approuve', $stockout->stockout_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('approuve-form-{{ $stockout->stockout_no }}').submit();this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';">
                                                Approuver
                                            </a>

                                            <form id="approuve-form-{{ $stockout->stockout_no }}" action="{{ route('admin.goods-stockouts.approuve', $stockout->stockout_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.reject'))
                                            @if($stockout->status == 1 || $stockout->status == 2 || $stockout->status == 3)
                                            <a class="btn btn-warning text-white btn-sm" href="{{ route('admin.goods-stockouts.reject', $stockout->stockout_no) }}"
                                            onclick="event.preventDefault(); var motif = prompt('Motif de rejet:'); if(motif) { document.getElementById('reject-motif-{{ $stockout->stockout_no }}').value = motif; document.getElementById('reject-form-{{ $stockout->stockout_no }}').submit(); }">
                                                Rejeter
                                            </a>
                                            @endif
                                            <form id="reject-form-{{ $stockout->stockout_no }}" action="{{ route('admin.goods-stockouts.reject', $stockout->stockout_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                                <input type="hidden" name="rejected_motif" id="reject-motif-{{ $stockout->stockout_no }}">
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.reset'))
                                            @if($stockout->status == -1 || $stockout->status == 2 || $stockout->status == 3)
                                            <a class="btn btn-info text-white btn-sm" href="{{ route('admin.goods-stockouts.reset', $stockout->stockout_no) }}"
                                            onclick="event.preventDefault(); document.getElementById('reset-form-{{ $stockout->stockout_no }}').submit();">
                                                Annuler
                                            </a>
                                            @endif
                                            <form id="reset-form-{{ $stockout->stockout_no }}" action="{{ route('admin.goods-stockouts.reset', $stockout->stockout_no) }}" method="POST" style="display: none;">
                                                @method('PUT')
                                                @csrf
                                            </form>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.edit'))
                                            @if($stockout->status != 4)
                                            <a class="btn btn-success text-white btn-sm" href="{{ route('admin.goods-stockouts.edit', $stockout->stockout_no) }}">@lang('messages.edit')</a>
                                            @endif
                                        @endif

                                        @if (Auth::guard('admin')->user()->can('goods_stockout.delete'))
                                            @if($stockout->status == -1 || $stockout->status == 1)
                                            <a class="btn btn-danger text-white btn-sm" href="{{ route('admin.goods-stockouts.destroy', $stockout->stockout_no) }}"
                                            onclick="event.preventDefault(); if(confirm(@json(__('messages.delete_confirm_irreversible')))) { document.getElementById('delete-form-{{ $stockout->stockout_no }}').submit(); }">
                                                @lang('messages.delete')
                                            </a>

                                            <form id="delete-form-{{ $stockout->stockout_no }}" action="{{ route('admin.goods-stockouts.destroy', $stockout->stockout_no) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                            @endif
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('goods_stockout.view'))
                                            <a class="btn btn-secondary text-white btn-sm" href="{{ route('admin.goods-stockouts.print', $stockout->stockout_no) }}" target="_blank" title="@lang('messages.Imprimer le document')">
                                                <i class="fa fa-print"></i> @lang('messages.Imprimer')
                                            </a>
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
                responsive: true,
                order: [[0, 'desc']],
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
            });
        }

     </script>
@endsection

