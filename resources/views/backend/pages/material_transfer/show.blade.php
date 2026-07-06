
@extends('backend.layouts.master')

@section('title')
@lang('messages.details des transferts') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .info-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    .info-value {
        color: #212529;
        margin-bottom: 15px;
    }
    .nav-tabs .nav-link {
        color: #495057;
    }
    .nav-tabs .nav-link.active {
        color: #007bff;
        font-weight: 600;
    }
    .detail-section {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.details des transferts')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.material-transfers.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.details')</span></li>
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
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Details sur le Bon de transfert No') : {{ $transfer->transfer_no ?? ($transferDetails->first()->transfer_no ?? 'N/A') }}</h4>
                    <div class="float-right mb-2">
                        <a href="{{ route('admin.material-transfers.index') }}" class="btn btn-secondary">@lang('messages.back')</a>
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="transferTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                <i class="fa fa-info-circle"></i> General Information
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab">
                                <i class="fa fa-list"></i> Transfer Details
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content mt-4">
                        <!-- General Information Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Transfer No:')</label>
                                            <div class="info-value">{{ $transfer->transfer_no ?? 'N/A' }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Transfer Signature:')</label>
                                            <div class="info-value">{{ $transfer->transfer_signature ?? 'N/A' }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Requisition No:')</label>
                                            <div class="info-value">
                                                @if($transfer->requisition_no)
                                                    <a href="{{ route('admin.material-requisitions.show', $transfer->requisition_no) }}">{{ $transfer->requisition_no }}</a>
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Date:')</label>
                                            <div class="info-value">{{ $transfer->date ? \Carbon\Carbon::parse($transfer->date)->format('d/m/Y H:i') : 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Status:')</label>
                                            <div class="info-value">
                                                @if($transfer->status == 1)
                                                    <span class="badge badge-primary">@lang('messages.En cours')</span>
                                                @elseif($transfer->status == 2)
                                                    <span class="badge badge-info">@lang('messages.Validé')</span>
                                                @elseif($transfer->status == 3)
                                                    <span class="badge badge-warning">@lang('messages.Confirmé')</span>
                                                @elseif($transfer->status == 4)
                                                    <span class="badge badge-success">@lang('messages.Approuvé')</span>
                                                @elseif($transfer->status == -1)
                                                    <span class="badge badge-danger">@lang('messages.Rejeté')</span>
                                                @else
                                                    <span class="badge badge-secondary">@lang('messages.N/A')</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Origin Store:')</label>
                                            <div class="info-value">
                                                @if($transfer->originStore)
                                                    {{ $transfer->originStore->name ?? 'N/A' }}
                                                @elseif($transfer->originExtraStore)
                                                    {{ $transfer->originExtraStore->name ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Destination Store:')</label>
                                            <div class="info-value">
                                                @if($transfer->destination_store_id && $transfer->destinationStore)
                                                    {{ $transfer->destinationStore->name }}
                                                @elseif($transfer->dest_bg_store_id && $transfer->destinationBigStore)
                                                    {{ $transfer->destinationBigStore->name }}
                                                @else
                                                    <span class="text-muted">@lang('messages.N/A')</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Created By:')</label>
                                            <div class="info-value">{{ $transfer->created_by ?? 'N/A' }}</div>
                                        </div>
                                        @if($transfer->validated_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Validated By:')</label>
                                            <div class="info-value">{{ $transfer->validated_by }}</div>
                                        </div>
                                        @endif
                                        @if($transfer->confirmed_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Confirmed By:')</label>
                                            <div class="info-value">{{ $transfer->confirmed_by }}</div>
                                        </div>
                                        @endif
                                        @if($transfer->approuved_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Approved By:')</label>
                                            <div class="info-value">{{ $transfer->approuved_by }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Description:')</label>
                                            <div class="info-value">{{ $transfer->description ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Details Tab -->
                        <div class="tab-pane fade" id="details" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="dataTable">
                                    <thead class="bg-light text-capitalize">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="10%">@lang('messages.date')</th>
                                            <th width="10%">@lang('messages.Item')</th>
                                            <th width="10%">@lang('messages.quantity_requisitioned')</th>
                                            <th width="10%">@lang('messages.unit_price')</th>
                                            <th width="10%">@lang('messages.quantity_transfered')</th>
                                            <th width="10%">@lang('messages.unit')</th>
                                            <th width="15%">@lang('messages.total_value')</th>
                                            <th width="20%">@lang('messages.description')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transferDetails as $detail)
                                        <tr>
                                            <td>{{ $loop->index+1 }}</td>
                                            <td>{{ $detail->date ? \Carbon\Carbon::parse($detail->date)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $detail->material->name ?? 'N/A' }}</td>
                                            <td>{{ $detail->quantity_requisitioned }}</td>
                                            <td>{{ number_format($detail->price, 0, ',', ' ') }}</td>
                                            <td>{{ $detail->quantity_transfered }}</td>
                                            <td>{{ $detail->unit }}</td>
                                            <td>{{ number_format($detail->total_value_transfered, 0, ',', ' ') }}</td>
                                            <td>{{ $detail->description ?? 'N/A' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center">@lang('messages.No details found')</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="5" class="text-right"><strong>@lang('messages.Total Quantity Requisitioned:')</strong></td>
                                            <td><strong>{{ $totalQuantityRequisitioned ?? 0 }}</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td colspan="5" class="text-right"><strong>@lang('messages.Total Quantity Transfered:')</strong></td>
                                            <td><strong>{{ $totalQuantityTransfered ?? 0 }}</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td colspan="7" class="text-right"><strong>@lang('messages.Total Value Transfered:')</strong></td>
                                            <td><strong>{{ number_format($totalValueTransfered ?? 0, 0, ',', ' ') }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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