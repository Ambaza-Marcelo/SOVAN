@extends('backend.layouts.master')

@section('title')
@lang('messages.note_credit_index_title') — @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.note_credit_index_title')</h4>
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
    <div class="col-12 mt-5">
        <div class="card">
            <div class="card-body">
                <p class="text-muted small">@lang('messages.note_credit_index_intro')</p>
                @include('backend.layouts.partials.messages')
                <table id="dataTable" class="table text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>@lang('messages.note_credit_col_avoir')</th>
                            <th>@lang('messages.note_credit_col_invoice_ref')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('messages.Clients')</th>
                            <th>@lang('messages.note_credit_col_motif')</th>
                            <th>@lang('messages.status')</th>
                            <th>@lang('messages.created_by')</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($credits as $c)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><a href="{{ route('admin.note-de-credit.show', $c->invoice_number) }}">{{ $c->invoice_number }}</a></td>
                            <td><a href="{{ route('admin.invoice.show', $c->invoice_ref) }}">{{ $c->invoice_ref }}</a></td>
                            <td>{{ $c->invoice_date ? \Carbon\Carbon::parse($c->invoice_date)->format('d/m/Y') : '' }}</td>
                            <td>{{ $c->client->customer_name ?? '' }}</td>
                            <td>
                                @if($c->cn_motif == '2') @lang('messages.note_credit_motif_return_goods')
                                @elseif($c->cn_motif == '3') @lang('messages.note_credit_motif_rebate')
                                @elseif($c->cn_motif == '4') @lang('messages.note_credit_motif_reduction_hf')
                                @else @lang('messages.note_credit_motif_invoice_error')
                                @endif
                            </td>
                            <td>
                                @if($c->statut == '2')
                                    <span class="badge badge-success">@lang('messages.status_validated_short')</span>
                                @else
                                    <span class="badge badge-warning">@lang('messages.status_pending_short')</span>
                                @endif
                            </td>
                            <td>{{ $c->auteur }}</td>
                            <td>
                                <a class="btn btn-sm btn-info text-white" href="{{ route('admin.note-de-credit.show', $c->invoice_number) }}"><i class="fa fa-eye"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
