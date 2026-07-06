
@extends('backend.layouts.master')

@section('title')
@lang('messages.Les requisitions') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Les requisitions')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.material-requisitions.index') }}">@lang('messages.list')</a></li>
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
                    <h4 class="header-title float-left">Details sur le Bon de requisition No : {{ $requisition->requisition_no }}</h4>
                    <div class="float-right mb-2">
                        <a href="{{ route('admin.material-requisitions.index') }}" class="btn btn-secondary">@lang('messages.back')</a>
                    </div>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="requisitionTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                <i class="fa fa-info-circle"></i> @lang('messages.general_information')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab">
                                <i class="fa fa-list"></i> @lang('messages.requisition_details_tab')
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
                                            <label class="info-label">@lang('messages.Requisition No:')</label>
                                            <div class="info-value">{{ $requisition->requisition_no }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Requisition Signature:')</label>
                                            <div class="info-value">{{ $requisition->requisition_signature }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Date:')</label>
                                            <div class="info-value">{{ \Carbon\Carbon::parse($requisition->date)->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Type Store:')</label>
                                            <div class="info-value">{{ $requisition->type_store ?? 'N/A' }}</div>
                                        </div>
                                        @if($requisition->code_store)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Code Store:')</label>
                                            <div class="info-value">{{ $requisition->code_store }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Status:')</label>
                                            <div class="info-value">
                                                @if($requisition->status == 2)
                                                    <span class="badge badge-success">@lang('messages.Validé')</span>
                                                @elseif($requisition->status == -1)
                                                    <span class="badge badge-danger">@lang('messages.Rejeté')</span>
                                                @elseif($requisition->status == 3)
                                                    <span class="badge badge-success">@lang('messages.Confirmé')</span>
                                                @elseif($requisition->status == 4)
                                                    <span class="badge badge-success">@lang('messages.Approuvé')</span>
                                                @elseif($requisition->status == 5)
                                                    <span class="badge badge-success">@lang('messages.Transferé')</span>
                                                @else
                                                    <span class="badge badge-primary">@lang('messages.En cours...')</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Created By:')</label>
                                            <div class="info-value">{{ $requisition->created_by }}</div>
                                        </div>
                                        @if($requisition->updated_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Updated By:')</label>
                                            <div class="info-value">{{ $requisition->updated_by }}</div>
                                        </div>
                                        @endif
                                        @if($requisition->validated_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Validated By:')</label>
                                            <div class="info-value">{{ $requisition->validated_by }}</div>
                                        </div>
                                        @endif
                                        @if($requisition->confirmed_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Confirmed By:')</label>
                                            <div class="info-value">{{ $requisition->confirmed_by }}</div>
                                        </div>
                                        @endif
                                        @if($requisition->approuved_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Approved By:')</label>
                                            <div class="info-value">{{ $requisition->approuved_by }}</div>
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
                                            <div class="info-value">{{ $requisition->description }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requisition Details Tab -->
                        <div class="tab-pane fade" id="details" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light text-capitalize">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="15%">@lang('messages.Item')</th>
                                            <th width="10%">@lang('messages.Code')</th>
                                            <th width="10%">@lang('messages.Quantity Requisitioned')</th>
                                            <th width="10%">@lang('messages.Unit')</th>
                                            <th width="10%">@lang('messages.Price')</th>
                                            <th width="15%">@lang('messages.Total Value')</th>
                                            <th width="25%">@lang('messages.Description')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($requisitionDetails as $detail)
                                        <tr>
                                            <td>{{ $loop->index+1 }}</td>
                                            <td>{{ $detail->material->name ?? 'N/A' }}</td>
                                            <td>{{ $detail->material->code ?? 'N/A' }}</td>
                                            <td>{{ $detail->quantity_requisitioned }}</td>
                                            <td>{{ $detail->material->materialMeasurement->purchase_unit ?? 'N/A' }}</td>
                                            <td>{{ number_format($detail->price, 0, ',', ' ') }}</td>
                                            <td>{{ number_format($detail->total_value_requisitioned, 0, ',', ' ') }}</td>
                                            <td>{{ $detail->description ?? 'N/A' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">@lang('messages.No details found')</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="6" class="text-right"><strong>@lang('messages.Total:')</strong></td>
                                            <td><strong>{{ number_format($totalValueRequisitioned, 0, ',', ' ') }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td colspan="6" class="text-right"><strong>@lang('messages.Total Quantity:')</strong></td>
                                            <td><strong>{{ $totalQuantityRequisitioned }}</strong></td>
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
<script>
    // Tab functionality is handled by Bootstrap
</script>
@endsection
