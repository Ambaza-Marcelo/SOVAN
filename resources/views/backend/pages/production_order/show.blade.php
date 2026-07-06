
@extends('backend.layouts.master')

@section('title')
@lang('messages.production order details') - @lang('messages.admin_panel')
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
    .badge-status {
        font-size: 0.9em;
        padding: 5px 10px;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.production order details')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.production-order.index') }}">@lang('messages.list')</a></li>
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
                    <h4 class="header-title">@lang('messages.Production Order Details:') {{ $productionOrder->prodution_number }}</h4>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')

                    <!-- Action Buttons -->
                    <div class="mb-3">
                        @if (Auth::guard('admin')->user()->can('production_order.edit'))
                            @if($productionOrder->status != '4' && $productionOrder->status != 4)
                                <a href="{{ route('admin.production-order.edit', $productionOrder->prodution_number) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> @lang('messages.Edit Order')
                                </a>
                            @endif
                        @endif
                        <a href="{{ route('admin.production-order.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> @lang('messages.Back to List')
                        </a>
                        @if (Auth::guard('admin')->user()->can('production_order.validate'))
                            @if($productionOrder->status == '0' || $productionOrder->status == 0 || $productionOrder->status == '-1' || $productionOrder->status == -1)
                                <form action="{{ route('admin.production-order.validate', $productionOrder->prodution_number) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.Valider cet ordre de production ?')))">
                                        <i class="fa fa-check"></i> @lang('messages.Valider')
                                    </button>
                                </form>
                            @endif
                        @endif
                        @if (Auth::guard('admin')->user()->can('production_order.confirm'))
                            @if($productionOrder->status == '1' || $productionOrder->status == 1)
                                <form action="{{ route('admin.production-order.confirm', $productionOrder->prodution_number) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-info" onclick="return confirm(@json(__('messages.Confirmer cet ordre de production ?')))">
                                        <i class="fa fa-check-circle"></i> @lang('messages.Confirmer')
                                    </button>
                                </form>
                            @endif
                        @endif
                        @if (Auth::guard('admin')->user()->can('production_order.approuve'))
                            @if($productionOrder->status == '2' || $productionOrder->status == 2)
                                <form action="{{ route('admin.production-order.approve', $productionOrder->prodution_number) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('messages.Approuver cet ordre de production ?')))">
                                        <i class="fa fa-check-double"></i> @lang('messages.Approuver')
                                    </button>
                                </form>
                            @endif
                        @endif
                        @if (Auth::guard('admin')->user()->can('production_order.delete'))
                            @if($productionOrder->status != '4' && $productionOrder->status != 4)
                                <form action="{{ route('admin.production-order.destroy', $productionOrder->prodution_number) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm(@json(__('messages.Supprimer cet ordre de production ?')))">
                                        <i class="fa fa-trash"></i> @lang('messages.Delete')
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="productionOrderTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="order-details-tab" data-toggle="tab" href="#order-details" role="tab">
                                <i class="fa fa-info-circle"></i> @lang('messages.Order Details')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="stock-tab" data-toggle="tab" href="#stock" role="tab">
                                <i class="fa fa-warehouse"></i> @lang('messages.Stock')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="products-tab" data-toggle="tab" href="#products" role="tab">
                                <i class="fa fa-box"></i> @lang('messages.Products & Quantity')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="materials-tab" data-toggle="tab" href="#materials" role="tab">
                                <i class="fa fa-flask"></i> @lang('messages.materials')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="routing-tab" data-toggle="tab" href="#routing" role="tab">
                                <i class="fa fa-cogs"></i> @lang('messages.Routing')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="scheduling-tab" data-toggle="tab" href="#scheduling" role="tab">
                                <i class="fa fa-calendar"></i> @lang('messages.Scheduling')
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="costing-tab" data-toggle="tab" href="#costing" role="tab">
                                <i class="fa fa-calculator"></i> @lang('messages.Costing')
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content mt-4">
                        <!-- Order Details Tab -->
                        <div class="tab-pane fade show active" id="order-details" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Production Number:')</label>
                                            <div class="info-value"><strong>{{ $productionOrder->prodution_number }}</strong></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Production Signature:')</label>
                                            <div class="info-value">{{ $productionOrder->production_signature }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Production Date:')</label>
                                            <div class="info-value">{{ \Carbon\Carbon::parse($productionOrder->production_date)->format('d/m/Y') }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Start Date:')</label>
                                            <div class="info-value">{{ \Carbon\Carbon::parse($productionOrder->start_date)->format('d/m/Y') }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.End Date (Due Date):')</label>
                                            <div class="info-value">{{ \Carbon\Carbon::parse($productionOrder->end_date)->format('d/m/Y') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Status:')</label>
                                            <div class="info-value">
                                                @if($productionOrder->status == '1' || $productionOrder->status == 1)
                                                    <span class="badge badge-success badge-status">@lang('messages.Validated')</span>
                                                @elseif($productionOrder->status == '-1' || $productionOrder->status == -1)
                                                    <span class="badge badge-danger badge-status">@lang('messages.Rejected')</span>
                                                @elseif($productionOrder->status == '2' || $productionOrder->status == 2)
                                                    <span class="badge badge-info badge-status">@lang('messages.Confirmed')</span>
                                                @elseif($productionOrder->status == '4' || $productionOrder->status == 4)
                                                    <span class="badge badge-success badge-status">@lang('messages.Approved')</span>
                                                @else
                                                    <span class="badge badge-primary badge-status">@lang('messages.In Progress')</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Created By:')</label>
                                            <div class="info-value">{{ $productionOrder->created_by }}</div>
                                        </div>
                                        @if($productionOrder->validated_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Validated By:')</label>
                                            <div class="info-value">{{ $productionOrder->validated_by }}</div>
                                        </div>
                                        @endif
                                        @if($productionOrder->confirmed_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Confirmed By:')</label>
                                            <div class="info-value">{{ $productionOrder->confirmed_by }}</div>
                                        </div>
                                        @endif
                                        @if($productionOrder->approuved_by)
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Approved By:')</label>
                                            <div class="info-value">{{ $productionOrder->approuved_by }}</div>
                                        </div>
                                        @endif
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Description:')</label>
                                            <div class="info-value">{{ $productionOrder->description ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Tab -->
                        <div class="tab-pane fade" id="stock" role="tabpanel">
                            @php
                                $fgStoreType = $productionOrder->finished_goods_store_type ?: 'MAIN';
                                $fgStore = null;
                                if ($fgStoreType === 'SM') {
                                    $fgStore = \App\Models\FinishedGoodsSmStore::find($productionOrder->finished_g_sm_store_id);
                                } else {
                                    $fgStore = \App\Models\FinishedGoodsStore::find($productionOrder->finished_g_store_id);
                                }
                            @endphp
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="detail-section">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="info-label">@lang('messages.Stock Type:')</label>
                                                    <div class="info-value">
                                                        @if($fgStoreType === 'SM')
                                                            <span class="badge badge-info badge-status">@lang('messages.Finished Goods SM')</span>
                                                        @else
                                                            <span class="badge badge-primary badge-status">@lang('messages.Finished Goods (Main)')</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label class="info-label">@lang('messages.Store (Destination):')</label>
                                                    <div class="info-value">
                                                        @if($fgStore)
                                                            <strong>{{ $fgStore->code }}</strong> — {{ $fgStore->name }}
                                                        @else
                                                            <span class="text-muted">@lang('messages.N/A')</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @if($fgStore && $fgStore->default_account_id)
                                            @php
                                                $acc = \App\Models\Accounting\AccountAccount::find($fgStore->default_account_id);
                                            @endphp
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="info-label">@lang('messages.Default Stock Account:')</label>
                                                        <div class="info-value">
                                                            {{ $acc ? ($acc->account_number . ' - ' . $acc->account_name) : $fgStore->default_account_id }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Products & Quantity Tab -->
                        <div class="tab-pane fade" id="products" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>@lang('messages.Item Code')</th>
                                            <th>@lang('messages.Item Name')</th>
                                            <th>@lang('messages.Quantity')</th>
                                            <th>@lang('messages.Cost Price')</th>
                                            <th>@lang('messages.Total Cost')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($finishedGoods as $index => $good)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $good->item->code ?? 'N/A' }}</td>
                                            <td>{{ $good->item->name ?? 'N/A' }}</td>
                                            <td>{{ number_format($good->item_quantity, 2) }}</td>
                                            <td>{{ number_format($good->cost_price, 0, ',', ' ') }} BIF</td>
                                            <td>{{ number_format($good->total_item_cost, 0, ',', ' ') }} BIF</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center">@lang('messages.No finished goods found')</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="3" class="text-right"><strong>@lang('messages.Total:')</strong></td>
                                            <td><strong>{{ number_format($totalItemQuantity, 2) }}</strong></td>
                                            <td></td>
                                            <td><strong>{{ number_format($totalItemCost, 0, ',', ' ') }} BIF</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Materials Tab -->
                        <div class="tab-pane fade" id="materials" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>@lang('messages.Raw Material')</th>
                                            <th>@lang('messages.Quantity')</th>
                                            <th>@lang('messages.Unit')</th>
                                            <th>@lang('messages.Cost')</th>
                                            <th>@lang('messages.Lot Number')</th>
                                            <th>@lang('messages.Batch Number')</th>
                                            <th>@lang('messages.Total Cost')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($rawMaterials as $index => $material)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $material->rawMaterial->name ?? 'N/A' }}</td>
                                            <td>{{ number_format($material->material_quantity, 2) }}</td>
                                            <td>{{ $material->unit ?? ($material->rawMaterial->rawMaterialMeasurement->purchase_unit ?? 'N/A') }}</td>
                                            <td>{{ number_format($material->material_cost, 0, ',', ' ') }} BIF</td>
                                            <td>{{ $material->lot_number ?? 'N/A' }}</td>
                                            <td>{{ $material->batch_number ?? 'N/A' }}</td>
                                            <td>{{ number_format($material->total_material_cost, 0, ',', ' ') }} BIF</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">@lang('messages.No raw materials found')</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="2" class="text-right"><strong>@lang('messages.Total:')</strong></td>
                                            <td><strong>{{ number_format($totalMaterialQuantity, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td><strong>{{ number_format($totalMaterialCost, 0, ',', ' ') }} BIF</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Routing Tab -->
                        <div class="tab-pane fade" id="routing" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Work Center:')</label>
                                            <div class="info-value">{{ $productionOrder->work_center ?? 'N/A' }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Operation Sequence:')</label>
                                            <div class="info-value">{{ $productionOrder->operation_sequence ?? 'N/A' }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Routing Notes:')</label>
                                            <div class="info-value">{{ $productionOrder->routing_notes ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scheduling Tab -->
                        <div class="tab-pane fade" id="scheduling" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Planned Start Time:')</label>
                                            <div class="info-value">
                                                {{ $productionOrder->planned_start_time ? \Carbon\Carbon::parse($productionOrder->planned_start_time)->format('d/m/Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Planned Finish Time:')</label>
                                            <div class="info-value">
                                                {{ $productionOrder->planned_finish_time ? \Carbon\Carbon::parse($productionOrder->planned_finish_time)->format('d/m/Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Actual Start Time:')</label>
                                            <div class="info-value">
                                                {{ $productionOrder->actual_start_time ? \Carbon\Carbon::parse($productionOrder->actual_start_time)->format('d/m/Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="info-label">@lang('messages.Actual Finish Time:')</label>
                                            <div class="info-value">
                                                {{ $productionOrder->actual_finish_time ? \Carbon\Carbon::parse($productionOrder->actual_finish_time)->format('d/m/Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Costing Tab -->
                        <div class="tab-pane fade" id="costing" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="detail-section">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="info-label">@lang('messages.Estimated Labor Cost:')</label>
                                                    <div class="info-value">
                                                        <strong>{{ number_format($productionOrder->estimated_labor_cost, 0, ',', ' ') }} BIF</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="info-label">@lang('messages.Estimated Material Cost:')</label>
                                                    <div class="info-value">
                                                        <strong>{{ number_format($productionOrder->estimated_material_cost, 0, ',', ' ') }} BIF</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="info-label">@lang('messages.Estimated Overhead Cost:')</label>
                                                    <div class="info-value">
                                                        <strong>{{ number_format($productionOrder->estimated_overhead_cost, 0, ',', ' ') }} BIF</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="info-label" style="font-size: 1.2em;">@lang('messages.Total Estimated Cost:')</label>
                                                    <div class="info-value" style="font-size: 1.5em; color: #007bff;">
                                                        <strong>{{ number_format($productionOrder->total_estimated_cost, 0, ',', ' ') }} BIF</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
    // Tab switching functionality
    $('#productionOrderTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
</script>
@endsection

