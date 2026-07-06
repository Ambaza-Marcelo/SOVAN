
@extends('backend.layouts.master')

@section('title')
@lang('messages.dashboard') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .procurement-flow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 0;
    }
    .procurement-step {
        flex: 1;
        min-width: 120px;
        text-align: center;
        text-decoration: none !important;
        color: #027789;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: transform 0.2s ease, color 0.2s ease;
    }
    .procurement-step:hover {
        color: #e9b820;
        transform: translateY(-3px);
        text-decoration: none !important;
    }
    .procurement-step:hover .procurement-step-label {
        color: #e9b820;
    }
    .procurement-step:focus {
        outline: none;
        color: #015a66;
    }
    .procurement-icon-wrap {
        position: relative;
        display: inline-block;
        width: 72px;
        height: 72px;
        line-height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f8f9fa 0%, #eef6f7 100%);
        border: 2px solid #027789;
        margin-bottom: 8px;
        font-size: 28px;
    }
    .procurement-step:hover .procurement-icon-wrap {
        border-color: #e9b820;
        box-shadow: 0 6px 16px rgba(233, 184, 32, 0.25);
    }
    .procurement-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 22px;
        height: 22px;
        line-height: 16px;
        padding: 2px 6px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .procurement-arrow {
        color: #027789;
        font-size: 22px;
        flex-shrink: 0;
    }
    .procurement-step-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #333;
        line-height: 1.3;
    }
    .payment-link-note {
        background: #f8f9fa;
        border-left: 4px solid #027789;
        padding: 10px 14px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #555;
    }
    .payment-source-links {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px dashed #dee2e6;
    }
    .payment-source-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 20px;
        background: #eef6f7;
        color: #027789;
        text-decoration: none !important;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.2s ease;
    }
    .payment-source-link:hover {
        background: #dceef0;
        color: #015a66;
    }
    .payment-source-badge {
        min-width: 22px;
        height: 22px;
        line-height: 18px;
        padding: 2px 7px;
        border-radius: 12px;
        font-size: 11px;
    }
</style>
@endsection


@section('admin-content')
<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.dashboard')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{url('/404/muradutunge/ivyomwasavye-ntibishoboye-kuboneka')}}">@lang('messages.home')</a></li>
                    <li><span>@lang('messages.dashboard')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<!-- page title area end -->

@if (Auth::guard('admin')->user()->can('raw_material_order_client.view') || Auth::guard('admin')->user()->can('material_big_store.view') || 
Auth::guard('admin')->user()->can('raw_material_big_store.view') || Auth::guard('admin')->user()->can('invoice.view') ||
Auth::guard('admin')->user()->can('material_purchase.view') || Auth::guard('admin')->user()->can('material_supplier_order.view') || Auth::guard('admin')->user()->can('material_reception.view') ||
Auth::guard('admin')->user()->can('raw_material_purchase.view') || Auth::guard('admin')->user()->can('raw_material_supplier_order.view') || Auth::guard('admin')->user()->can('raw_material_reception.view') ||
Auth::guard('admin')->user()->can('account_cash_ouflow_request.view') || Auth::guard('admin')->user()->can('account_cash_ouflow.view') || Auth::guard('admin')->user()->can('fund_transfer.view') ||
Auth::guard('admin')->user()->can('production_plan.view') || Auth::guard('admin')->user()->can('raw_material_requisition.view') || Auth::guard('admin')->user()->can('raw_material_transfer.view') || Auth::guard('admin')->user()->can('production_order.view') ||
Auth::guard('admin')->user()->can('proforma_invoice.view') || Auth::guard('admin')->user()->can('sales_order.view') || Auth::guard('admin')->user()->can('delivery_note.view'))
<div class="main-content-inner">
  <div class="row">
    <div class="col-md-2" id="side-navbar">
    </div>
    <div class="col-lg-12">
    @if (Auth::guard('admin')->user()->can('raw_material_purchase.view') || Auth::guard('admin')->user()->can('material_purchase.view'))
        <div class="row">
            <div class="col-md-12">
                <div class="card" style="border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(90deg,#027789,#e9b820); color: #ffffff;">
                        <h4 class="mb-0">@lang('messages.profitability_dashboard')</h4>
                        <form method="GET" action="{{ url()->current() }}" class="form-inline">
                            <label for="exercise" class="mr-2 mb-0"><strong>@lang('messages.fiscal_year')</strong></label>
                            <select name="exercise" id="exercise" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach($exercises as $exercise)
                                    <option value="{{ $exercise }}" {{ $exercise == $selectedExercise ? 'selected' : '' }}>{{ $exercise }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="card-body" style="background-color:#ffffff;">
                        <div class="row">
                            <!-- Rentabilité globale -->
                            <div class="col-md-4 mb-3">
                                <div class="card h-100" style="border-radius:8px; border:1px solid #e5e5e5;">
                                    <div class="card-body">
                                        <h5 style="color:#027789;">@lang('messages.company_profitability')</h5>
                                        <p class="mb-1 text-muted">@lang('messages.products_accounts_7')</p>
                                        <h4 style="color:#027789;">{{ number_format($products,0,',',' ') }}</h4>
                                        <p class="mb-1 text-muted mt-3">@lang('messages.charges_accounts_6')</p>
                                        <h4 style="color:#e74c3c;">{{ number_format($charges,0,',',' ') }}</h4>
                                        <hr>
                                        <p class="mb-1 font-weight-bold">@lang('messages.profit')</p>
                                        <h3 style="color:#e9b820;">{{ number_format($profit,0,',',' ') }}</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Ventes CASH / CREDIT -->
                            <div class="col-md-4 mb-3">
                                <div class="card h-100" style="border-radius:8px; border:1px solid #e5e5e5;">
                                    <div class="card-body">
                                        <h5 style="color:#027789;">@lang('messages.sales')</h5>
                                        <div class="mb-3">
                                            <span class="badge badge-success mb-1">@lang('messages.cash')</span>
                                            <p class="mb-0 text-muted">@lang('messages.invoice_count')</p>
                                            <h4>{{ number_format($cashSales['count'],0,',',' ') }}</h4>
                                            <p class="mb-0 text-muted">@lang('messages.total_amount')</p>
                                            <h4 style="color:#027789;">{{ number_format($cashSales['sum'],0,',',' ') }}</h4>
                                        </div>
                                        <hr>
                                        <div>
                                            <span class="badge badge-warning mb-1">@lang('messages.credit')</span>
                                            <p class="mb-0 text-muted">@lang('messages.invoice_count')</p>
                                            <h4>{{ number_format($creditSales['count'],0,',',' ') }}</h4>
                                            <p class="mb-0 text-muted">@lang('messages.total_amount')</p>
                                            <h4 style="color:#e9b820;">{{ number_format($creditSales['sum'],0,',',' ') }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Achats par catégories -->
                            <div class="col-md-4 mb-3">
                                <div class="card h-100" style="border-radius:8px; border:1px solid #e5e5e5;">
                                    <div class="card-body">
                                        <h5 style="color:#027789;">@lang('messages.purchases_by_category')</h5>
                                        <div class="mb-2">
                                            <p class="mb-0 font-weight-bold" style="color:#027789;">@lang('messages.raw_materials')</p>
                                            <small class="text-muted">@lang('messages.paid'): {{ number_format($rawMaterialPurchases['paid_count'],0,',',' ') }} ({{ number_format($rawMaterialPurchases['paid_sum'],0,',',' ') }})</small><br>
                                            <small class="text-muted">@lang('messages.unpaid'): {{ number_format($rawMaterialPurchases['unpaid_count'],0,',',' ') }} ({{ number_format($rawMaterialPurchases['unpaid_sum'],0,',',' ') }})</small>
                                        </div>
                                        <hr>
                                        <div>
                                            <p class="mb-0 font-weight-bold" style="color:#027789;">@lang('messages.materials')</p>
                                            <small class="text-muted">@lang('messages.paid'): {{ number_format($materialPurchases['paid_count'],0,',',' ') }} ({{ number_format($materialPurchases['paid_sum'],0,',',' ') }})</small><br>
                                            <small class="text-muted">@lang('messages.unpaid'): {{ number_format($materialPurchases['unpaid_count'],0,',',' ') }} ({{ number_format($materialPurchases['unpaid_sum'],0,',',' ') }})</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

        {{-- Procurement workflows --}}
        @if (Auth::guard('admin')->user()->can('material_purchase.view') || Auth::guard('admin')->user()->can('material_supplier_order.view') || Auth::guard('admin')->user()->can('material_reception.view') || Auth::guard('admin')->user()->can('raw_material_purchase.view') || Auth::guard('admin')->user()->can('raw_material_supplier_order.view') || Auth::guard('admin')->user()->can('raw_material_reception.view'))
        <div class="row mt-4">
            @if (Auth::guard('admin')->user()->can('material_purchase.view') || Auth::guard('admin')->user()->can('material_supplier_order.view') || Auth::guard('admin')->user()->can('material_reception.view'))
            <div class="col-lg-6 mb-3">
                <div class="card h-100" style="border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="card-header" style="background: linear-gradient(90deg,#027789,#5ba3ad); color: #ffffff;">
                        <h5 class="mb-0"><i class="fa fa-wrench mr-2"></i>@lang('messages.dashboard_procurement_supplies')</h5>
                    </div>
                    <div class="card-body">
                        <div class="procurement-flow">
                            <a href="{{ route('admin.material-purchases.index') }}" class="procurement-step" title="@lang('messages.dashboard_step_purchase_request')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-file-text-o"></i>
                                    @if($materialProcurement['purchase_requests'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $materialProcurement['purchase_requests'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_step_purchase_request')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.material-supplier-orders.index') }}" class="procurement-step" title="@lang('messages.dashboard_step_purchase_order')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-shopping-cart"></i>
                                    @if($materialProcurement['purchase_orders'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $materialProcurement['purchase_orders'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_step_purchase_order')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.material-receptions.index') }}" class="procurement-step" title="@lang('messages.dashboard_step_purchase_receipt')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-truck"></i>
                                    @if($materialProcurement['purchase_receipts'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $materialProcurement['purchase_receipts'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_step_purchase_receipt')</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if (Auth::guard('admin')->user()->can('raw_material_purchase.view') || Auth::guard('admin')->user()->can('raw_material_supplier_order.view') || Auth::guard('admin')->user()->can('raw_material_reception.view'))
            <div class="col-lg-6 mb-3">
                <div class="card h-100" style="border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="card-header" style="background: linear-gradient(90deg,#027789,#e9b820); color: #ffffff;">
                        <h5 class="mb-0"><i class="fa fa-cutlery mr-2"></i>@lang('messages.dashboard_procurement_raw_materials')</h5>
                    </div>
                    <div class="card-body">
                        <div class="procurement-flow">
                            <a href="{{ route('admin.raw-material-purchases.index') }}" class="procurement-step" title="@lang('messages.dashboard_step_purchase_request')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-clipboard"></i>
                                    @if($rawMaterialProcurement['purchase_requests'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $rawMaterialProcurement['purchase_requests'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_step_purchase_request')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.raw-material-supplier-orders.index') }}" class="procurement-step" title="@lang('messages.dashboard_step_purchase_order')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-shopping-basket"></i>
                                    @if($rawMaterialProcurement['purchase_orders'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $rawMaterialProcurement['purchase_orders'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_step_purchase_order')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.raw-material-receptions.index') }}" class="procurement-step" title="@lang('messages.dashboard_step_purchase_receipt')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-download"></i>
                                    @if($rawMaterialProcurement['purchase_receipts'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $rawMaterialProcurement['purchase_receipts'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_step_purchase_receipt')</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Payment process --}}
        @if (Auth::guard('admin')->user()->can('account_cash_ouflow_request.view') || Auth::guard('admin')->user()->can('account_cash_ouflow.view') || Auth::guard('admin')->user()->can('fund_transfer.view'))
        <div class="row mt-2">
            <div class="col-lg-12 mb-3">
                <div class="card" style="border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="card-header" style="background: linear-gradient(90deg,#027789,#34495e); color: #ffffff;">
                        <h5 class="mb-0"><i class="fa fa-money mr-2"></i>@lang('messages.dashboard_payment_process_title')</h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-link-note">
                            <i class="fa fa-info-circle mr-1"></i>
                            @lang('messages.dashboard_payment_link_help')
                        </div>

                        <div class="procurement-flow">
                            <a href="{{ route('admin.cash-out-flow-request.index') }}" class="procurement-step" title="@lang('messages.dashboard_payment_step_cash_out_request')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-file-text"></i>
                                    @if($paymentProcess['cash_outflow_requests'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $paymentProcess['cash_outflow_requests'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_payment_step_cash_out_request')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.cash-out-flow.index') }}" class="procurement-step" title="@lang('messages.dashboard_payment_step_cash_out')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-credit-card"></i>
                                    @if($paymentProcess['cash_outflows'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $paymentProcess['cash_outflows'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_payment_step_cash_out')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.accounting-return-fund.index') }}" class="procurement-step" title="@lang('messages.dashboard_payment_step_return_fund')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-reply"></i>
                                    @if($paymentProcess['return_funds'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $paymentProcess['return_funds'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.dashboard_payment_step_return_fund')</span>
                            </a>
                        </div>

                        <div class="payment-source-links">
                            @can('material_supplier_order.view')
                            <a href="{{ route('admin.material-supplier-orders.index') }}" class="payment-source-link" title="@lang('messages.dashboard_payment_cash_via_order_supplies')">
                                <i class="fa fa-shopping-cart"></i>
                                @lang('messages.dashboard_payment_cash_via_order_supplies')
                                @if($awaitingPayment['material_orders_cash'] > 0)
                                <span class="badge badge-warning payment-source-badge">{{ $awaitingPayment['material_orders_cash'] }}</span>
                                @endif
                            </a>
                            @endcan
                            @can('raw_material_supplier_order.view')
                            <a href="{{ route('admin.raw-material-supplier-orders.index') }}" class="payment-source-link" title="@lang('messages.dashboard_payment_cash_via_order_raw_materials')">
                                <i class="fa fa-shopping-basket"></i>
                                @lang('messages.dashboard_payment_cash_via_order_raw_materials')
                                @if($awaitingPayment['raw_material_orders_cash'] > 0)
                                <span class="badge badge-warning payment-source-badge">{{ $awaitingPayment['raw_material_orders_cash'] }}</span>
                                @endif
                            </a>
                            @endcan
                            @can('material_reception.view')
                            <a href="{{ route('admin.material-receptions.index') }}" class="payment-source-link" title="@lang('messages.dashboard_payment_credit_via_reception_supplies')">
                                <i class="fa fa-truck"></i>
                                @lang('messages.dashboard_payment_credit_via_reception_supplies')
                                @if($awaitingPayment['material_receptions_credit'] > 0)
                                <span class="badge badge-warning payment-source-badge">{{ $awaitingPayment['material_receptions_credit'] }}</span>
                                @endif
                            </a>
                            @endcan
                            @can('raw_material_reception.view')
                            <a href="{{ route('admin.raw-material-receptions.index') }}" class="payment-source-link" title="@lang('messages.dashboard_payment_credit_via_reception_raw_materials')">
                                <i class="fa fa-download"></i>
                                @lang('messages.dashboard_payment_credit_via_reception_raw_materials')
                                @if($awaitingPayment['raw_material_receptions_credit'] > 0)
                                <span class="badge badge-warning payment-source-badge">{{ $awaitingPayment['raw_material_receptions_credit'] }}</span>
                                @endif
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Production process --}}
        @if (Auth::guard('admin')->user()->can('production_plan.view') || Auth::guard('admin')->user()->can('raw_material_requisition.view') || Auth::guard('admin')->user()->can('raw_material_transfer.view') || Auth::guard('admin')->user()->can('production_order.view'))
        <div class="row mt-2">
            <div class="col-lg-12 mb-3">
                <div class="card" style="border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="card-header" style="background: linear-gradient(90deg,#027789,#e9b820); color: #ffffff;">
                        <h5 class="mb-0"><i class="fa fa-cogs mr-2"></i>@lang('messages.dashboard_production_process_title')</h5>
                    </div>
                    <div class="card-body">
                        <div class="procurement-flow">
                            <a href="{{ route('admin.production-plan.index') }}" class="procurement-step" title="@lang('messages.Plan de production')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-calendar-check-o"></i>
                                    @if($productionProcess['production_plans'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $productionProcess['production_plans'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Plan de production')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.raw-material-requisitions.index') }}" class="procurement-step" title="@lang('messages.Requisition')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-cutlery"></i>
                                    @if($productionProcess['requisitions'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $productionProcess['requisitions'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Requisition')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.raw-material-transfers.index') }}" class="procurement-step" title="@lang('messages.Transfer')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-exchange"></i>
                                    @if($productionProcess['transfers'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $productionProcess['transfers'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Transfer')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.production-order.index') }}" class="procurement-step" title="@lang('messages.Production Order')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-wrench"></i>
                                    @if($productionProcess['production_orders'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $productionProcess['production_orders'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Production Order')</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Sales process --}}
        @if (Auth::guard('admin')->user()->can('proforma_invoice.view') || Auth::guard('admin')->user()->can('sales_order.view') || Auth::guard('admin')->user()->can('invoice.view') || Auth::guard('admin')->user()->can('delivery_note.view'))
        <div class="row mt-2">
            <div class="col-lg-12 mb-3">
                <div class="card" style="border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="card-header" style="background: linear-gradient(90deg,#027789,#34495e); color: #ffffff;">
                        <h5 class="mb-0"><i class="fa fa-shopping-bag mr-2"></i>@lang('messages.dashboard_sales_process_title')</h5>
                    </div>
                    <div class="card-body">
                        <div class="procurement-flow">
                            <a href="{{ route('admin.proforma-invoice.index') }}" class="procurement-step" title="@lang('messages.Proforma Invoice')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-file-text-o"></i>
                                    @if($salesProcess['proforma_invoices'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $salesProcess['proforma_invoices'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Proforma Invoice')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.sales-order.index') }}" class="procurement-step" title="@lang('messages.Bons de commande')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-shopping-cart"></i>
                                    @if($salesProcess['sales_orders'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $salesProcess['sales_orders'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Bons de commande')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.invoice.index') }}" class="procurement-step" title="@lang('messages.Invoices')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-file-text"></i>
                                    @if($salesProcess['invoices_in_progress'] > 0)
                                    <span class="badge badge-warning procurement-badge" title="@lang('messages.dashboard_invoice_in_progress')">{{ $salesProcess['invoices_in_progress'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Invoices')</span>
                            </a>

                            <i class="fa fa-long-arrow-right procurement-arrow"></i>

                            <a href="{{ route('admin.delivery-note.index') }}" class="procurement-step" title="@lang('messages.Delivery Note')">
                                <span class="procurement-icon-wrap">
                                    <i class="fa fa-truck"></i>
                                    @if($salesProcess['delivery_notes'] > 0)
                                    <span class="badge badge-danger procurement-badge" title="@lang('messages.dashboard_unapproved_pending')">{{ $salesProcess['delivery_notes'] }}</span>
                                    @endif
                                </span>
                                <span class="procurement-step-label">@lang('messages.Delivery Note')</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    <br><br>
    </div>
  </div>
</div>
@endif

<!-- end f -->
@endsection