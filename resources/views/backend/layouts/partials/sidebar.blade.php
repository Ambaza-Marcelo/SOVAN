 @php
     $usr = Auth::guard('admin')->user();
 @endphp
 <div class="sidebar-menu">
    <div class="sidebar-header">
        <div class="logo">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
                <span class="sidebar-brand-mark">SO</span>
                <span class="sidebar-brand-text">
                    <h2 class="sidebar-brand-name">@lang('messages.app_brand')</h2>
                    <span class="sidebar-brand-tag">@lang('messages.app_brand_tagline')</span>
                </span>
            </a>
        </div>
    </div>
    <div class="main-menu">
        <div class="menu-inner">
            <nav>
                <ul class="metismenu" id="menu">

                    @if ($usr->can('dashboard.view'))
                    <li class="active">
                        <a href="{{ route('admin.dashboard') }}" aria-expanded="true"><i class="ti-dashboard"></i><span>@lang('messages.dashboard')</span></a>
                    </li>
                    @endif
                    @if ($usr->can('material.view'))
                    <li class="mm-static">
                        <span class="sidebar-section-label"><i class="fa fa-industry"></i>@lang('messages.sidebar_company_section')</span>
                    </li>
                    @endif
                    @if ($usr->can('raw_material.create') || $usr->can('raw_material.view') ||  $usr->can('raw_material.edit') ||  $usr->can('raw_material.delete') || $usr->can('client.view') || $usr->can('material.create') || $usr->can('material.view') ||  $usr->can('material.edit') ||  $usr->can('material.delete') ||  $usr->can('employe.create') || $usr->can('employe.view') ||  $usr->can('employe.edit') ||  $usr->can('employe.delete') || $usr->can('supplier.create') || $usr->can('supplier.view') ||  $usr->can('supplier.edit') ||  $usr->can('item.view'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-shopping-basket"></i><span>
                            @lang('messages.basic_file')
                        </span></a>
                        <ul class="collapse">
                                @if($usr->can('supplier.view'))
                                <li class=""><a href="{{ route('admin.suppliers.index') }}"><i class="fa fa-truck"></i>&nbsp;@lang('messages.suppliers')</a></li>
                                @endif
                                @if($usr->can('client.view'))
                                <li class=""><a href="{{ route('admin.clients.index') }}"><i class="fa fa-user-circle"></i>&nbsp;@lang('messages.Clients')</a></li>
                                @endif
                                @if($usr->can('raw_material_category.view'))
                                <li class=""><a href="{{ route('admin.raw-material-measurement.index') }}"><i class="fa fa-balance-scale"></i>&nbsp;@lang('messages.unités de mesure matières premières')</a></li>
                                @endif
                                @if($usr->can('material_category.view'))
                                <li class=""><a href="{{ route('admin.material-measurement.index') }}"><i class="fa fa-balance-scale"></i>&nbsp;@lang('messages.unités de mesure materiel')</a></li>
                                @endif
                                @if($usr->can('raw_material_category.view'))
                                <li class=""><a href="{{ route('admin.raw-material-category.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Categorie matières premières')</a></li>
                                @endif
                                @if($usr->can('material_category.view'))
                                <li class=""><a href="{{ route('admin.material-category.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Categorie Materiel')</a></li>
                                @endif
                                @if($usr->can('raw_material.view'))
                                <li class=""><a href="{{ route('admin.raw-materials.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.matières premières')</a></li>
                                @endif
                                @if($usr->can('material.view'))
                                <li class=""><a href="{{ route('admin.materials.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Materiel')</a></li>
                                @endif
                                @if($usr->can('item.view'))
                                <li class=""><a href="{{ route('admin.exchange-rates.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Exchange Rate')</a></li>
                                @endif
                                @if($usr->can('item.view'))
                                <li class=""><a href="{{ route('admin.items.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Items')</a></li>
                                @endif
                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('raw_material_extra_big_store.create') || $usr->can('raw_material_extra_big_store.view') ||  $usr->can('raw_material_extra_big_store.edit') ||  $usr->can('raw_material_extra_big_store.delete') || $usr->can('raw_material_big_store.create') || $usr->can('raw_material_big_store.view') ||  $usr->can('raw_material_big_store.edit') ||  $usr->can('raw_material_big_store.delete') || $usr->can('raw_material_small_store.create') || $usr->can('raw_material_small_store.view') ||  $usr->can('raw_material_small_store.edit') ||  $usr->can('raw_material_small_store.delete') || $usr->can('material_extra_big_store.create') || $usr->can('material_extra_big_store.view') ||  $usr->can('material_extra_big_store.edit') ||  $usr->can('finished_product_stock.view') || $usr->can('material_big_store.create') || $usr->can('material_big_store.view') ||  $usr->can('material_big_store.edit') ||  $usr->can('goods_stock.view') || $usr->can('material_small_store.create') || $usr->can('material_small_store.view') ||  $usr->can('material_small_store.edit'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-cubes"></i><span>
                            @lang('messages.stock')
                        </span></a>
                        <ul class="collapse">
                                @if($usr->can('raw_material_extra_big_store.view'))
                                <li class=""><a href="{{ route('admin.raw-material-extra-big-store.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Grand Stock des matières premières')</a></li>
                                @endif
                                @if($usr->can('raw_material_big_store.view'))
                                <li class=""><a href="{{ route('admin.raw-material-big-store.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Stock Intermediaire des matières premières')</a></li>
                                @endif
                                @if($usr->can('raw_material_small_store.view'))
                                <li class=""><a href="{{ route('admin.raw-material-small-store.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Petit Stock des matières premières')</a></li>
                                @endif
                                @if($usr->can('finished_product_stock.view'))
                                <li class=""><a href="{{ route('admin.finished-goods-store.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.finished_goods_store_menu')</a></li>
                                @endif
                                @if($usr->can('finished_product_sm_stock.view'))
                                <li class=""><a href="{{ route('admin.finished-goods-sm-store.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.finished_goods_sm_store_menu')</a></li>
                                @endif
                                @if($usr->can('material_extra_big_store.view'))
                                <li class=""><a href="{{ route('admin.material-extra-big-store.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Grand Stock des Materiels')</a></li>
                                @endif
                                @if($usr->can('material_big_store.view'))
                                <li class=""><a href="{{ route('admin.material-big-store.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Stock Intermediaire des Materiels')</a></li>
                                @endif
                                @if($usr->can('material_small_store.view'))
                                <li class=""><a href="{{ route('admin.material-small-store.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Petit Stock des Materiels')</a></li>
                                @endif

                                @if($usr->can('goods_stock.view'))
                                <li class=""><a href="{{ route('admin.goods-store.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.stock des marchandises')</a></li>
                                @endif

                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('raw_material_extra_big_inventory.create') || $usr->can('raw_material_extra_big_inventory.view') ||  $usr->can('raw_material_extra_big_inventory.edit') ||  $usr->can('finished_product.delete') || $usr->can('raw_material_big_inventory.create') || $usr->can('raw_material_big_inventory.view') ||  $usr->can('raw_material_big_inventory.edit') ||  $usr->can('raw_material_big_inventory.delete') || $usr->can('raw_material_small_inventory.create') || $usr->can('raw_material_small_inventory.view') ||  $usr->can('raw_material_small_inventory.edit') ||  $usr->can('finished_product_stock.view') || $usr->can('material_extra_big_inventory.create') || $usr->can('material_extra_big_inventory.view') ||  $usr->can('material_extra_big_inventory.edit') ||  $usr->can('material_extra_big_inventory.delete') || $usr->can('material_big_inventory.create') || $usr->can('material_big_inventory.view') ||  $usr->can('material_big_inventory.edit') ||  $usr->can('goods_stock.view') || $usr->can('material_small_inventory.create') || $usr->can('material_small_inventory.view') ||  $usr->can('material_small_inventory.edit'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-clipboard"></i><span>
                            @lang('messages.inventory')
                        </span></a>
                        <ul class="collapse">
                                @if($usr->can('raw_material_extra_big_inventory.view'))
                                <li class=""><a href="{{ route('admin.raw-material-extra-big-store-inventory.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Grand Stock des matières premières')</a></li>
                                @endif
                                @if($usr->can('raw_material_big_inventory.view'))
                                <li class=""><a href="{{ route('admin.raw-material-big-store-inventory.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Stock Intermediaire des matières premières')</a></li>
                                @endif
                                @if($usr->can('raw_material_small_inventory.view'))
                                <li class=""><a href="{{ route('admin.raw-material-small-store-inventory.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Petit Stock des matières premières')</a></li>
                                @endif
                                @if($usr->can('material_extra_big_inventory.view'))
                                <li class=""><a href="{{ route('admin.material-extra-big-store-inventory.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Grand Stock des Materiels')</a></li>
                                @endif
                                @if($usr->can('finished_goods_inventory.view'))
                                <li class=""><a href="{{ route('admin.finished-goods-store-inventory.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.finished_goods_inventory_menu')</a></li>
                                @endif
                                @if($usr->can('finished_goods_sm_store_inventory.view'))
                                <li class=""><a href="{{ route('admin.finished-goods-sm-store-inventory.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.finished_goods_sm_inventory_menu')</a></li>
                                @endif
                                @if($usr->can('material_big_inventory.view'))
                                <li class=""><a href="{{ route('admin.material-big-store-inventory.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Stock Intermediaire des Materiels')</a></li>
                                @endif
                                @if($usr->can('goods_stock.view'))
                                <li class=""><a href="{{ route('admin.goods-store-inventory.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.stock des marchandises')</a></li>
                                @endif
                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('material_requisition.create') || $usr->can('material_requisition.view') ||  $usr->can('material_requisition.edit') ||  $usr->can('raw_material_requisition.view') || $usr->can('material_transfer.create') || $usr->can('material_transfer.view') ||  $usr->can('material_transfer.edit') ||  $usr->can('raw_material_transfer.view'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-file-text"></i><span>
                            @lang('messages.requisition')/@lang('messages.transfer')
                        </span></a>
                        <ul class="collapse">
                                @if($usr->can('material_requisition.view'))
                                <li class=""><a href="{{ route('admin.material-requisitions.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Materiels')</a></li>
                                @endif
                                @if($usr->can('material_transfer.view'))
                                <li class=""><a href="{{ route('admin.material-transfers.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Materiels')</a></li>
                                @endif

                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('raw_material_requisition.create') || $usr->can('raw_material_requisition.view') ||  $usr->can('raw_material_requisition.edit') ||  $usr->can('raw_material_requisition.delete') ||$usr->can('raw_material_transfer.create') || $usr->can('raw_material_transfer.view') ||  $usr->can('raw_material_transfer.edit') ||  $usr->can('raw_material_transfer.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-exchange"></i><span>
                            @lang('messages.Production')
                        </span></a>
                        <ul class="collapse">
                                @if($usr->can('production_plan.view'))
                                <li class=""><a href="{{ route('admin.production-plan.index') }}"><i class="fa fa-calendar-check-o"></i>&nbsp;@lang('messages.Plan de production')</a></li>
                                @endif
                                @if($usr->can('raw_material_requisition.view'))
                                <li class=""><a href="{{ route('admin.raw-material-requisitions.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Requisition')</a></li>
                                @endif
                                @if($usr->can('raw_material_transfer.view'))
                                <li class=""><a href="{{ route('admin.raw-material-transfers.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Transfer')</a></li>
                                @endif
                                @if($usr->can('raw_material_transfer.view'))
                                <li class=""><a href="{{ route('admin.production-order.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Production Order')</a></li>
                                @endif

                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('raw_material_supplier_order.view') ||  $usr->can('raw_material_supplier_order.create') ||  $usr->can('material_supplier_order.view') ||  $usr->can('material_supplier_order.create') ||  $usr->can('raw_material_reception.view') ||  $usr->can('material_reception.view'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-shopping-cart"></i><span>
                            @lang('messages.purchases')
                        </span></a>
                        <ul class="collapse">
                                @if ($usr->can('raw_material_supplier_order.view'))
                                <li class=""><a href="{{ route('admin.raw-material-purchases.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Demande Achat matières premières')</a></li>
                                @endif
                                @if ($usr->can('material_supplier_order.view'))
                                <li class=""><a href="{{ route('admin.material-purchases.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Demande Achat Materiel')</a></li>
                                @endif
                                @if ($usr->can('raw_material_supplier_order.view'))
                                <li class=""><a href="{{ route('admin.raw-material-supplier-orders.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Commande matières premières')</a></li>
                                @endif
                                @if ($usr->can('material_supplier_order.view'))
                                <li class=""><a href="{{ route('admin.material-supplier-orders.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Commande Materiel')</a></li>
                                @endif
                                @if ($usr->can('raw_material_reception.view'))
                                <li class=""><a href="{{ route('admin.raw-material-receptions.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Reception matières premières')</a></li>
                                @endif
                                @if ($usr->can('material_reception.view'))
                                <li class=""><a href="{{ route('admin.material-receptions.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Reception Materiel')</a></li>
                                @endif

                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('invoice.view') ||  $usr->can('invoice.create')|| $usr->can('note_credit.view') ||  $usr->can('note_credit.create'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-money"></i><span>
                            @lang('messages.sales')
                        </span></a>
                        <ul class="collapse">
                                @if ($usr->can('proforma_invoice.view'))
                                <li class=""><a href="{{ route('admin.proforma-invoice.index')}}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Proforma Invoice')</a></li>
                                @endif
                                @if ($usr->can('sales_order.view'))
                                <li class=""><a href="{{ route('admin.sales-order.index')}}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Bons de commande')</a></li>
                                @endif
                                @if ($usr->can('delivery_note.view'))
                                <li class=""><a href="{{ route('admin.delivery-note.index')}}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Delivery Note')</a></li>
                                @endif
                                @if ($usr->can('invoice.view'))
                                <li class=""><a href="{{ route('admin.invoice.index')}}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Invoices')</a></li>
                                @endif
                                @if ($usr->can('note_credit.view'))
                                <li class=""><a href="{{ route('admin.note-de-credit.index')}}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Note de Credit')</a></li>
                                @endif
                                @if ($usr->can('remboursement_caution.view'))
                                <li class=""><a href="{{ route('admin.remboursement-caution.index')}}"><i class="fa fa-money"></i>&nbsp;@lang('messages.Remboursement Caution')</a></li>
                                @endif
                                
                        </ul>
                    </li>
                    @endif
                    @if ( $usr->can('recouvrement.view'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-credit-card"></i><span>
                            @lang('messages.Credits')
                        </span></a>
                        <ul class="collapse">
                                @if ($usr->can('recouvrement.view'))
                                <li class=""><a href=""><i class="fa fa-money"></i>&nbsp;@lang('messages.Recouvrement')</a></li>
                                @endif
                        </ul>
                    </li>
                    @endif

                    @if ($usr->can('raw_material_stockin.create') || $usr->can('raw_material_stockin.view') ||  $usr->can('raw_material_stockin.edit') ||  $usr->can('raw_material_stockin.delete') || $usr->can('material_stockin.create') || $usr->can('material_stockin.view') ||  $usr->can('material_stockin.edit') ||  $usr->can('material_stockin.delete') || $usr->can('raw_material_stockout.create') || $usr->can('raw_material_stockout.view') ||  $usr->can('raw_material_stockout.edit') ||  $usr->can('private_drink_stockout.create') || $usr->can('material_stockout.create') || $usr->can('material_stockout.view') ||  $usr->can('material_stockout.edit') || $usr->can('goods_import.view'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-arrows-v"></i><span>
                            @lang('messages.stockin') / @lang('messages.stockout')
                        </span></a>
                        <ul class="collapse">
                                @if($usr->can('raw_material_stockin.view'))
                                <li class=""><a href="{{ route('admin.raw-material-stockins.index') }}"><i class="fa fa-arrow-down"></i>&nbsp;@lang('messages.Entree matières premières')</a></li>
                                @endif
                                @if($usr->can('finished_goods_stockin.view'))
                                <li class=""><a href="{{ route('admin.finished-goods-stockins.index') }}"><i class="fa fa-arrow-down"></i>&nbsp;@lang('messages.Entree des produits finis')</a></li>
                                @endif
                                @if($usr->can('material_stockin.view'))
                                <li class=""><a href="{{ route('admin.material-stockins.index') }}"><i class="fa fa-arrow-down"></i>&nbsp;@lang('messages.Entree Materiels')</a></li>
                                @endif
                                @if($usr->can('goods_stockin.view'))
                                <li class=""><a href="{{ route('admin.goods-stockins.index') }}"><i class="fa fa-arrow-down"></i>&nbsp;@lang('messages.Entree des marchandises')</a></li>
                                @endif
                                @if($usr->can('goods_import.view'))
                                <li class=""><a href="{{ route('admin.imports.index') }}"><i class="fa fa-ship"></i>&nbsp;@lang('messages.goods_import_menu')</a></li>
                                @endif
                                @if($usr->can('raw_material_stockout.view'))
                                <li class=""><a href="{{ route('admin.raw-material-stockouts.index') }}"><i class="fa fa-arrow-up"></i>&nbsp;@lang('messages.Sortie matières premières')</a></li>
                                @endif
                                @if($usr->can('finished_goods_stockout.view'))
                                <li class=""><a href="{{ route('admin.finished-goods-stockouts.index') }}"><i class="fa fa-arrow-down"></i>&nbsp;@lang('messages.Sortie des produits finis')</a></li>
                                @endif
                                @if($usr->can('goods_stockout.view'))
                                <li class=""><a href="{{ route('admin.goods-stockouts.index') }}"><i class="fa fa-arrow-down"></i>&nbsp;@lang('messages.Sortie des marchandises')</a></li>
                                @endif
                                @if($usr->can('material_stockout.view'))
                                <li class=""><a href="{{ route('admin.material-stockouts.index') }}"><i class="fa fa-arrow-up"></i>&nbsp;@lang('messages.Sortie Materiels')</a></li>
                                @endif

                        </ul>
                    </li>
                    @endif
                    @if ($usr->can('raw_material_extra_big_report.view') || $usr->can('raw_material_big_report.view') || $usr->can('raw_material_small_report.view') || $usr->can('material_extra_big_report.view') || $usr->can('material_big_report.view') || $usr->can('material_small_report.view'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-bar-chart"></i><span>
                            @lang('messages.stock_report')
                        </span></a>
                        <ul class="collapse">
                                @if ($usr->can('raw_material_extra_big_report.view'))
                                <li class=""><a href="{{ route('admin.raw-material-extra-big-store-report.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Grand stock matières premières ')</a></li>
                                @endif
                                @if ($usr->can('raw_material_big_report.view'))
                                <li class=""><a href="{{ route('admin.raw-material-big-store-report.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Intermidiaire stock matières premières ')</a></li>
                                @endif
                                @if ($usr->can('raw_material_small_report.view'))
                                <li class=""><a href="{{ route('admin.raw-material-small-store-report.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Petit stock matières premières ')</a></li>
                                @endif
                                @if ($usr->can('finished_product_stock_report.view'))
                                <li class=""><a href="{{ route('admin.finished-good-store-report.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Stock des produits finis')</a></li>
                                @endif
                                @if ($usr->can('finished_product_sm_stock_report.view'))
                                <li class=""><a href="{{ route('admin.finished-good-sm-store-report.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.Petit Stock des produits finis')</a></li>
                                @endif
                                @if ($usr->can('material_extra_big_report.view'))
                                <li class=""><a href="{{ route('admin.material-extra-big-store-report.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Grand stock materiel ')</a></li>
                                @endif
                                @if ($usr->can('material_big_report.view'))
                                <li class=""><a href="{{ route('admin.material-big-store-report.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Intermediaire stock materiel ')</a></li>
                                @endif
                                @if ($usr->can('goods_stock.view'))
                                <li class=""><a href="{{ route('admin.goods-store-report.index') }}"><i class="fa fa-cutlery"></i>&nbsp;@lang('messages.stock des marchandises')</a></li>
                                @endif
                                @if ($usr->can('material_small_report.view'))
                                <li class=""><a href="{{ route('admin.material-small-store-report.index') }}"><i class="fa fa-wrench"></i>&nbsp;@lang('messages.Petit stock materiel ')</a></li>
                                @endif
                        </ul>
                    </li>
                    @endif
                    <hr>
                  
                    <!-- human resource management -->
                    @if ($usr->can('hr_employe.create') || $usr->can('hr_employe.view') ||  $usr->can('hr_employe.edit') ||  $usr->can('hr_employe.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-users"></i><span>
                            @lang('messages.Employes')
                        </span></a>
                        <ul class="">
                            @if ($usr->can('hr_departement.view'))
                                <li class=""><a href="{{ route('admin.hr-companies.index') }}"><i class="fa fa-building"></i>&nbsp;@lang('messages.Entreprises')</a></li>
                            @endif
                            @if ($usr->can('hr_departement.view'))
                                <li class=""><a href="{{ route('admin.hr-departements.index') }}"><i class="fa fa-sitemap"></i>&nbsp;@lang('messages.Département')</a></li>
                            @endif
                            @if ($usr->can('hr_service.view'))
                                <li class=""><a href="{{ route('admin.hr-services.index') }}"><i class="fa fa-concierge-bell"></i>&nbsp;@lang('messages.Services')</a></li>
                            @endif
                            @if ($usr->can('hr_fonction.view'))
                                <li class=""><a href="{{ route('admin.hr-fonctions.index') }}"><i class="fa fa-briefcase"></i>&nbsp;@lang('messages.Fonctions')</a></li>
                            @endif
                            @if ($usr->can('hr_grade.view'))
                                <li class=""><a href="{{ route('admin.hr-grades.index') }}"><i class="fa fa-star"></i>&nbsp;@lang('messages.Grades')</a></li>
                            @endif
                            @if ($usr->can('hr_banque.view'))
                                <li class=""><a href="{{ route('admin.hr-banques.index') }}"><i class="fa fa-university"></i>&nbsp;@lang('messages.Banques')</a></li>
                            @endif
                            @if ($usr->can('hr_employe.view'))
                                <li class=""><a href="{{ route('admin.hr-company.select') }}"><i class="fa fa-users"></i>&nbsp;@lang('messages.Employés')</a></li>
                            @endif
                            
                        </ul>
                    </li>
                    @endif
                    <!-- human resource management -->
                    @if ($usr->can('hr_ecole.create') || $usr->can('hr_ecole.view') ||  $usr->can('hr_filiere.view') ||  $usr->can('hr_filiere.create') || $usr->can('hr_stagiaire.create') || $usr->can('hr_stagiaire.view') ||  $usr->can('hr_stagiaire.edit') ||  $usr->can('hr_stagiaire.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-graduation-cap"></i><span>
                            @lang('messages.Stagiaires')
                        </span></a>
                        <ul class="">
                            @if ($usr->can('hr_ecole.view'))
                                <li class=""><a href="{{ route('admin.hr-ecoles.index') }}"><i class="fa fa-university"></i>&nbsp;@lang('messages.Ecoles')</a></li>
                            @endif
                            @if ($usr->can('hr_filiere.view'))
                                <li class=""><a href="{{ route('admin.hr-filieres.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Filieres')</a></li>
                            @endif
                            @if ($usr->can('hr_stagiaire.view'))
                                <li class=""><a href="{{ route('admin.stagiare-select-by-company') }}"><i class="fa fa-graduation-cap"></i>&nbsp;@lang('messages.Stagiaires')</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    <!-- human resource management -->
                    @if ($usr->can('hr_conge.create') || $usr->can('hr_conge.view') ||  $usr->can('hr_conge.edit') ||  $usr->can('hr_conge.delete') || $usr->can('hr_conge_paye.create') || $usr->can('hr_conge_paye.view') ||  $usr->can('hr_conge_paye.edit') ||  $usr->can('hr_conge_paye.delete') || $usr->can('hr_absence.create') || $usr->can('hr_absence.view') ||  $usr->can('hr_absence.edit') ||  $usr->can('hr_absence.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-calendar-times-o"></i><span>
                            @lang('messages.Conges')
                        </span></a>
                        <ul class="">
                            @if ($usr->can('hr_conge.view'))
                                <li class=""><a href="{{ route('admin.hr-type-conges.index') }}"><i class="fa fa-calendar-times-o"></i>&nbsp;@lang('messages.Type Congé')</a></li>
                            @endif
                            @if ($usr->can('hr_conge.view'))
                                <li class=""><a href="{{ route('admin.hr-leave-taken.select-by-company') }}"><i class="fa fa-calendar-times-o"></i>&nbsp;@lang('messages.Congés')</a></li>
                            @endif
                            @if ($usr->can('hr_conge_paye.view'))
                                <li class=""><a href="{{ route('admin.hr-take-paid-leave.select-by-company') }}"><i class="fa fa-money"></i>&nbsp;@lang('messages.Congé Payé')</a></li>
                            @endif
                            @if ($usr->can('hr_absence.view'))
                                <li class=""><a href="{{ route('admin.hr-time-tables.index') }}"><i class="fa fa-clock-o"></i>&nbsp;@lang('messages.Time Table')</a></li>
                            @endif
                            @if ($usr->can('hr_absence.view'))
                                <li class=""><a href="{{ route('admin.hr-absences.index') }}"><i class="fa fa-clock-o"></i>&nbsp;@lang('messages.Attendance Transactions')</a></li>
                            @endif
                            @if ($usr->can('hr_absence.view'))
                                <li class=""><a href="{{ route('admin.hr-first-in-last-out.index') }}"><i class="fa fa-clock-o"></i>&nbsp;@lang('messages.first_in_last_out')</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    <!-- human resource management -->
                    @if ($usr->can('hr_paiement.create') || $usr->can('hr_paiement.view') ||  $usr->can('hr_paiement.edit') ||  $usr->can('hr_paiement.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-money"></i><span>
                            @lang('messages.Bulletin de paie')
                        </span></a>
                        <ul class="">
                            @if ($usr->can('hr_loan.view'))
                                <li class=""><a href="{{ route('admin.hr-loans.index') }}"><i class="fa fa-users"></i>&nbsp;@lang('messages.Avance sur salaire')</a></li>
                            @endif
                            @if ($usr->can('hr_paiement.view'))
                                <li class=""><a href="{{ route('admin.hr-journal-paies.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Journal de paie')</a></li>
                            @endif
                            @if ($usr->can('hr_paiement.view'))
                                <li class=""><a href="{{ route('admin.hr-paiement.selectByCompany') }}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Bulletin de paie')</a></li>
                            @endif
                            @if ($usr->can('hr_reglage.view'))
                                <li class=""><a href="{{ route('admin.hr-reglages.index') }}"><i class="fa fa-cogs"></i>&nbsp;@lang('messages.Réglages')</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    <!-- human resource management -->
                    @if ($usr->can('hr_journal_paie.create') || $usr->can('hr_journal_paie.view') ||  $usr->can('hr_journal_paie.edit') ||  $usr->can('hr_journal_paie.delete') || $usr->can('hr_journal_conge.create') || $usr->can('hr_journal_conge.view') ||  $usr->can('hr_journal_conge.edit') ||  $usr->can('hr_journal_conge.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-book"></i><span>
                            @lang('messages.Les Journaux')
                        </span></a>
                        <ul class="">
                            @if ($usr->can('hr_journal_paie.view'))
                                <li class=""><a href="{{ route('admin.hr-journal-paies.index') }}"><i class="fa fa-money"></i>&nbsp;@lang('messages.Journal Paie')</a></li>
                            @endif
                            @if ($usr->can('hr_journal_paie.view'))
                                <li class=""><a href="{{ route('admin.hr-journal-cotisations.select-by-company') }}"><i class="fa fa-credit-card"></i>&nbsp;@lang('messages.Journal Cotisations')</a></li>
                            @endif
                            @if ($usr->can('hr_journal_paie.view'))
                                <li class=""><a href="{{ route('admin.hr-journal-impots.select-by-company') }}"><i class="fa fa-calculator"></i>&nbsp;@lang('messages.Journal IRE')</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    <!-- end human ressources management  -->

                    <!-- accounting management -->
                    @if ($usr->can('account_account.create') || $usr->can('account_account.view') ||  $usr->can('account_parent_account.view') ||  $usr->can('account_parent_account.create') || $usr->can('account_setting.create') || $usr->can('account_setting.view') ||  $usr->can('account_setting.edit') ||  $usr->can('account_setting.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-calculator"></i><span>
                            @lang('messages.Accounting')
                        </span></a>
                        <ul class="">
                            @if ($usr->can('account_parent_account.view'))
                                <li class=""><a href="{{ route('admin.accounting-parent-accounts.index') }}"><i class="fa fa-sitemap"></i>&nbsp;@lang('messages.Chart Accounts')</a></li>
                            @endif
                            @if ($usr->can('account_journal_entry.view'))
                                <li class=""><a href="{{ route('admin.accounting-advance-payment.index') }}"><i class="fa fa-list-alt"></i>&nbsp;@lang('messages.Advance Payment')</a></li>
                            @endif
                            @if ($usr->can('fund_transfer.view'))
                                <li class=""><a href="{{ route('admin.accounting-fund-transfer.index') }}"><i class="fa fa-list-alt"></i>&nbsp;@lang('messages.Fund Transfer')</a></li>
                            @endif
                            @if ($usr->can('account_currency.view'))
                                <li class=""><a href="{{ route('admin.currencies.index') }}"><i class="fa fa-money"></i>&nbsp;@lang('messages.Currency')</a></li>
                            @endif
                            @if ($usr->can('account_expense_category.view'))
                                <li class=""><a href="{{ route('admin.expense-categories.index') }}"><i class="fa fa-tags"></i>&nbsp;@lang('messages.Expense Category')</a></li>
                            @endif
                            @if ($usr->can('account_expense.view'))
                                <li class=""><a href="{{ route('admin.accounting-expenses.index') }}"><i class="fa fa-credit-card"></i>&nbsp;@lang('messages.Expenses')</a></li>
                            @endif
                            @if ($usr->can('account_cash_ouflow_request.view'))
                                <li class=""><a href="{{ route('admin.cash-out-flow-request.index') }}"><i class="fa fa-file-text"></i>&nbsp;@lang('messages.Cash Out Flow Request')</a></li>
                            @endif
                            @if ($usr->can('account_cash_ouflow.view'))
                                <li class=""><a href="{{ route('admin.cash-out-flow.index') }}"><i class="fa fa-arrow-circle-down"></i>&nbsp;@lang('messages.Cash Out Flow')</a></li>
                            @endif
                            @if ($usr->can('fund_transfer.view'))
                                <li class=""><a href="{{ route('admin.accounting-return-fund.index') }}"><i class="fa fa-list-alt"></i>&nbsp;@lang('messages.Return Fund')</a></li>
                            @endif
                            @if ($usr->can('account_journal_entry.view'))
                                <li class=""><a href="{{ route('admin.journal-entry.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Journal Entry')</a></li>
                            @endif
                            @if ($usr->can('account_journal_entry.view'))
                                <li class=""><a href="{{ route('admin.accounts-balance.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Accounts Balance')</a></li>
                            @endif 
                            @if ($usr->can('account_journal_entry.view'))
                                <li class=""><a href="{{ route('admin.active-balance-sheet.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Active Balance Sheet')</a></li>
                            @endif
                            @if ($usr->can('account_journal_entry.view'))
                                <li class=""><a href="{{ route('admin.passive-balance-sheet.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Passive Balance Sheet')</a></li>
                            @endif
                            @if ($usr->can('account_journal_entry.view'))
                                <li class=""><a href="{{ route('admin.accounts-profit-and-loss-statement.index') }}"><i class="fa fa-book"></i>&nbsp;@lang('messages.Profit and Loss Statement')</a></li>
                            @endif 
                            @if ($usr->can('account_setting.view'))
                                <li class=""><a href="{{ route('admin.accounting-settings.index') }}"><i class="fa fa-cogs"></i>&nbsp;@lang('messages.Account Setting')</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    
                    @if ($usr->can('admin.create') || $usr->can('admin.view') ||  $usr->can('admin.edit') ||  $usr->can('admin.delete'))
                    <li>
                        <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-users"></i><span>
                            @lang('messages.users')
                        </span></a>
                        <ul class="collapse {{ Route::is('admin.admins.create') || Route::is('admin.admins.index') || Route::is('admin.admins.edit') || Route::is('admin.admins.show') ? 'in' : '' }}">
                            
                            @if ($usr->can('admin.view'))
                                <li class="{{ Route::is('admin.admins.index')  || Route::is('admin.admins.edit') ? 'active' : '' }}"><a href="{{ route('admin.admins.index') }}"><i class="fa fa-user"></i>&nbsp;@lang('messages.users')</a></li>
                            @endif
                            @if ($usr->can('role.view'))
                                <li class="{{ Route::is('admin.roles.index')  || Route::is('admin.roles.edit') ? 'active' : '' }}"><a href="{{ route('admin.roles.index') }}"><i class="fa fa-shield"></i> &nbsp;@lang('messages.roles') & @lang('messages.permissions')</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    @if($usr->can('setting.view'))
                    <li class=""><a href="{{ route('admin.settings.index') }}"><i class="fa fa-cogs"></i><span>@lang('messages.setting')</a></li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
</div>
