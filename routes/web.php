<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Auth::routes();

/**
 * Admin routes
 */
Route::group(['prefix' => 'admin'], function () {
    Route::get('/', 'Backend\DashboardController@index')->name('admin.dashboard');
    Route::resource('roles', 'Backend\RolesController', ['names' => 'admin.roles']);
    Route::resource('users', 'Backend\UsersController', ['names' => 'admin.users']);
    Route::resource('admins', 'Backend\AdminsController', ['names' => 'admin.admins']);

    // AJAX autocomplete (type-to-search)
    Route::get('autocomplete/clients', 'Backend\AutocompleteController@clients')->name('admin.autocomplete.clients');
    Route::get('autocomplete/items', 'Backend\AutocompleteController@items')->name('admin.autocomplete.items');
    Route::get('autocomplete/invoice-items', 'Backend\AutocompleteController@invoiceItems')->name('admin.autocomplete.invoice-items');
    Route::get('autocomplete/suppliers', 'Backend\AutocompleteController@suppliers')->name('admin.autocomplete.suppliers');
    Route::get('autocomplete/raw-materials', 'Backend\AutocompleteController@rawMaterials')->name('admin.autocomplete.raw-materials');
    Route::get('autocomplete/materials', 'Backend\AutocompleteController@materials')->name('admin.autocomplete.materials');
    Route::get('autocomplete/stock-report-articles', 'Backend\AutocompleteController@stockReportArticles')->name('admin.autocomplete.stock-report-articles');
    Route::get('autocomplete/articles', 'Backend\AutocompleteController@articles')->name('admin.autocomplete.articles');
    Route::get('autocomplete/employees', 'Backend\AutocompleteController@employees')->name('admin.autocomplete.employees');
    Route::get('autocomplete/expenses', 'Backend\AutocompleteController@expenses')->name('admin.autocomplete.expenses');

    //proforma invoice routes
    Route::get('admin/proforma-invoice/index', 'Backend\ProformaInvoiceController@index')->name('admin.proforma-invoice.index');
    Route::get('admin/proforma-invoice/create', 'Backend\ProformaInvoiceController@create')->name('admin.proforma-invoice.create');
    Route::post('admin/proforma-invoice/store', 'Backend\ProformaInvoiceController@store')->name('admin.proforma-invoice.store');
    Route::get('admin/proforma-invoice/export-to-excel', 'Backend\ProformaInvoiceController@exportToExcel')->name('admin.proforma-invoice.export-to-excel');
    Route::get('admin/proforma-invoice/{proforma_number}', 'Backend\ProformaInvoiceController@show')->name('admin.proforma-invoice.show');
    Route::get('admin/proforma-invoice/{proforma_number}/edit', 'Backend\ProformaInvoiceController@edit')->name('admin.proforma-invoice.edit');
    Route::put('admin/proforma-invoice/{proforma_number}', 'Backend\ProformaInvoiceController@update')->name('admin.proforma-invoice.update');
    Route::delete('admin/proforma-invoice/{proforma_number}', 'Backend\ProformaInvoiceController@destroy')->name('admin.proforma-invoice.destroy');
    Route::put('admin/proforma-invoice/{proforma_number}/validate', 'Backend\ProformaInvoiceController@validateInvoice')->name('admin.proforma-invoice.validate');
    Route::put('admin/proforma-invoice/{proforma_number}/confirm', 'Backend\ProformaInvoiceController@confirm')->name('admin.proforma-invoice.confirm');
    Route::put('admin/proforma-invoice/{proforma_number}/approve', 'Backend\ProformaInvoiceController@approve')->name('admin.proforma-invoice.approve');
    Route::get('admin/proforma-invoice/{proforma_number}/print', 'Backend\ProformaInvoiceController@print')->name('admin.proforma-invoice.print');
    Route::get('admin/proforma-invoice/{proforma_number}/print-usd', 'Backend\ProformaInvoiceController@printUsd')->name('admin.proforma-invoice.print-usd');

    // sales order (bon de commande) routes
    Route::get('admin/sales-order/index', 'Backend\SalesOrderController@index')->name('admin.sales-order.index');
    Route::get('admin/sales-order/create', 'Backend\SalesOrderController@create')->name('admin.sales-order.create');
    Route::get('admin/sales-order/create-from-proforma/{proforma_number}', 'Backend\SalesOrderController@create')->name('admin.sales-order.create-from-proforma');
    Route::post('admin/sales-order/store', 'Backend\SalesOrderController@store')->name('admin.sales-order.store');
    Route::get('admin/sales-order/export-to-excel', 'Backend\SalesOrderController@exportToExcel')->name('admin.sales-order.export-to-excel');
    Route::get('admin/sales-order/{sales_order_number}', 'Backend\SalesOrderController@show')->name('admin.sales-order.show');
    Route::get('admin/sales-order/{sales_order_number}/edit', 'Backend\SalesOrderController@edit')->name('admin.sales-order.edit');
    Route::put('admin/sales-order/{sales_order_number}', 'Backend\SalesOrderController@update')->name('admin.sales-order.update');
    Route::delete('admin/sales-order/{sales_order_number}', 'Backend\SalesOrderController@destroy')->name('admin.sales-order.destroy');
    Route::put('admin/sales-order/{sales_order_number}/validate', 'Backend\SalesOrderController@validateInvoice')->name('admin.sales-order.validate');
    Route::put('admin/sales-order/{sales_order_number}/confirm', 'Backend\SalesOrderController@confirm')->name('admin.sales-order.confirm');
    Route::put('admin/sales-order/{sales_order_number}/approve', 'Backend\SalesOrderController@approve')->name('admin.sales-order.approve');
    Route::get('admin/sales-order/{sales_order_number}/print', 'Backend\SalesOrderController@print')->name('admin.sales-order.print');

    //delivery note routes
    Route::get('admin/delivery-note/index', 'Backend\DeliveryNoteController@index')->name('admin.delivery-note.index');
    Route::get('admin/delivery-note/create/{proforma_number}', 'Backend\DeliveryNoteController@create')->name('admin.delivery-note.create');
    Route::get('admin/delivery-note/create-from-invoice/{invoice_number}', 'Backend\DeliveryNoteController@createFromInvoice')->name('admin.delivery-note.create-from-invoice');
    Route::post('admin/delivery-note/store', 'Backend\DeliveryNoteController@store')->name('admin.delivery-note.store');
    Route::get('admin/delivery-note/export-to-excel', 'Backend\DeliveryNoteController@exportToExcel')->name('admin.delivery-note.export-to-excel');
    Route::get('admin/delivery-note/{delivery_number}', 'Backend\DeliveryNoteController@show')->name('admin.delivery-note.show');
    Route::get('admin/delivery-note/{delivery_number}/edit', 'Backend\DeliveryNoteController@edit')->name('admin.delivery-note.edit');
    Route::put('admin/delivery-note/{delivery_number}', 'Backend\DeliveryNoteController@update')->name('admin.delivery-note.update');
    Route::delete('admin/delivery-note/{delivery_number}', 'Backend\DeliveryNoteController@destroy')->name('admin.delivery-note.destroy');
    Route::put('admin/delivery-note/{delivery_number}/validate', 'Backend\DeliveryNoteController@validateInvoice')->name('admin.delivery-note.validate');
    Route::put('admin/delivery-note/{delivery_number}/confirm', 'Backend\DeliveryNoteController@confirm')->name('admin.delivery-note.confirm');
    Route::put('admin/delivery-note/{delivery_number}/approve', 'Backend\DeliveryNoteController@approve')->name('admin.delivery-note.approve');
    Route::get('admin/delivery-note/{delivery_number}/print', 'Backend\DeliveryNoteController@print')->name('admin.delivery-note.print');

    //invoice routes
    Route::get('admin/invoice/index', 'Backend\InvoiceController@index')->name('admin.invoice.index');
    Route::get('admin/invoice/create/{delivery_number}', 'Backend\InvoiceController@create')->name('admin.invoice.create');
    Route::get('admin/invoice/create-from-sales-order/{sales_order_number}', 'Backend\InvoiceController@createFromSalesOrder')->name('admin.invoice.create-from-sales-order');
    Route::post('admin/invoice/store', 'Backend\InvoiceController@store')->name('admin.invoice.store');
    Route::get('admin/invoice/export-to-excel', 'Backend\InvoiceController@exportToExcel')->name('admin.invoice.export-to-excel');
    Route::get('admin/invoice/{invoice_number}', 'Backend\InvoiceController@show')->name('admin.invoice.show');
    Route::get('admin/invoice/{invoice_number}/edit', 'Backend\InvoiceController@edit')->name('admin.invoice.edit');
    Route::put('admin/invoice/{invoice_number}', 'Backend\InvoiceController@update')->name('admin.invoice.update');
    Route::delete('admin/invoice/{invoice_number}', 'Backend\InvoiceController@destroy')->name('admin.invoice.destroy');
    Route::get('admin/invoice/{invoice_number}/cash', 'Backend\InvoiceController@cash')->name('admin.invoice.cash');
    Route::get('admin/invoice/{invoice_number}/credit', 'Backend\InvoiceController@credit')->name('admin.invoice.credit');
    Route::put('admin/invoice/{invoice_number}/validate-as-cash', 'Backend\InvoiceController@validateAsCash')->name('admin.invoice.validate-as-cash');
    Route::put('admin/invoice/{invoice_number}/validate-as-credit', 'Backend\InvoiceController@validateAsCredit')->name('admin.invoice.validate-as-credit');
    Route::get('admin/invoice/{invoice_number}/print', 'Backend\InvoiceController@print')->name('admin.invoice.print');
    Route::get('admin/invoice/{invoice_number}/print-usd', 'Backend\InvoiceController@printUsd')->name('admin.invoice.print-usd');
    // Réimpression (sans transmission OBR ni écriture DB)
    Route::get('admin/invoice/{invoice_number}/reprint', 'Backend\InvoiceController@reprint')->name('admin.invoice.reprint');
    // Annulation d'une facture en brouillon (OBR + motif)
    Route::put('admin/invoice/{invoice_number}/cancel', 'Backend\InvoiceController@cancelInvoice')->name('admin.invoice.cancel');


    //credit note routes
    Route::get('Egotek-note-de-credit/index', 'Backend\NoteCreditController@index')->name('admin.note-de-credit.index');

    Route::get('Egotek-note-de-credit/show/{invoice_number}', 'Backend\NoteCreditController@show')->name('admin.note-de-credit.show');

    Route::get('Egotek/faire-note-de-credit/{invoice_number}','Backend\NoteCreditController@noteCredit')->name('admin.note-de-credit.create');


    Route::get('Egotek/note-de-credit/{invoice_number}','Backend\NoteCreditController@facture')->name('admin.note-de-credit.facture');


    Route::post('Egotek-note-de-credit/store', 'Backend\NoteCreditController@store')->name('admin.note-de-credit.store');



    Route::put('Egotek-note-de-credit/valider/{invoice_number}', 'Backend\NoteCreditController@validerFacture')->name('admin-note-de-credit.valider');


    //remboursement caution

    Route::get('Egotek-remboursement-caution/index', 'Backend\DepositRefundController@index')->name('admin.remboursement-caution.index');

    Route::get('Egotek-remboursement-caution/show/{invoice_number}', 'Backend\DepositRefundController@show')->name('admin.remboursement-caution.show');

    Route::get('Egotek/faire-remboursement-caution/{invoice_number}','Backend\DepositRefundController@depositRefundBoisson')->name('admin.remboursement-caution.create');

    Route::get('Egotek/remboursement-caution/{invoice_number}','Backend\DepositRefundController@facture')->name('admin.remboursement-caution.facture');


    Route::post('Egotek-remboursement-caution/store', 'Backend\DepositRefundController@store')->name('admin.remboursement-caution.store');
    
    Route::put('Egotek-remboursement-caution/valider/{invoice_number}', 'Backend\DepositRefundController@validerFacture')->name('admin.remboursement-caution.valider');

    // Login Routes
    Route::get('/login', 'Backend\Auth\LoginController@showLoginForm')->name('admin.login');
    Route::post('/login/submit', 'Backend\Auth\LoginController@login')->name('admin.login.submit');

    // Logout Routes
    Route::post('/logout/submit', 'Backend\Auth\LoginController@logout')->name('admin.logout.submit');

    // Password reset routes
    Route::get('/password/reset', 'Backend\Auth\ForgotPasswordController@showLinkRequestForm')->name('admin.password.request');
    Route::post('/password/reset/submit', 'Backend\Auth\ForgotPasswordController@sendResetLinkEmail')->name('admin.password.update');
    Route::get('/password/reset/{token}', 'Backend\Auth\ResetPasswordController@showResetForm')->name('admin.password.reset');
    Route::post('/password/reset', 'Backend\Auth\ResetPasswordController@reset')->name('admin.password.reset.submit');
    
    //change language
    Route::get('/changeLang','Backend\DashboardController@changeLang')->name('changeLang');


    Route::get('positions/index', 'Backend\PositionController@index')->name('admin.positions.index');
    Route::get('positions/create', 'Backend\PositionController@create')->name('admin.positions.create');
    Route::post('positions/store', 'Backend\PositionController@store')->name('admin.positions.store');
    Route::get('positions/edit/{id}', 'Backend\PositionController@edit')->name('admin.positions.edit');
    Route::put('positions/update/{id}', 'Backend\PositionController@update')->name('admin.positions.update');
    Route::delete('positions/destroy/{id}', 'Backend\PositionController@destroy')->name('admin.positions.destroy');

    //raw_materials routes (ex foods)
    Route::get('Egotek/raw-materials/index', 'Backend\RawMaterialController@index')->name('admin.raw-materials.index');
    Route::get('Egotek/raw-materials/create', 'Backend\RawMaterialController@create')->name('admin.raw-materials.create');
    Route::post('Egotek/raw-materials/store', 'Backend\RawMaterialController@store')->name('admin.raw-materials.store');
    Route::get('Egotek/raw-materials/edit/{id}', 'Backend\RawMaterialController@edit')->name('admin.raw-materials.edit');
    Route::put('Egotek/raw-materials/update/{id}', 'Backend\RawMaterialController@update')->name('admin.raw-materials.update');
    Route::delete('Egotek/raw-materials/destroy/{id}', 'Backend\RawMaterialController@destroy')->name('admin.raw-materials.destroy');
    Route::post('Egotek/raw-materials/toggle-status/{id}', 'Backend\RawMaterialController@toggleStatus')->name('admin.raw-materials.toggle-status');

    //clients routes
    Route::get('Egotek/clients/index', 'Backend\ClientController@index')->name('admin.clients.index');
    Route::get('Egotek/clients/create', 'Backend\ClientController@create')->name('admin.clients.create');
    Route::post('Egotek/clients/store', 'Backend\ClientController@store')->name('admin.clients.store');
    Route::get('Egotek/clients/edit/{id}', 'Backend\ClientController@edit')->name('admin.clients.edit');
    Route::put('Egotek/clients/update/{id}', 'Backend\ClientController@update')->name('admin.clients.update');
    Route::delete('Egotek/clients/destroy/{id}', 'Backend\ClientController@destroy')->name('admin.clients.destroy');

    Route::post('Egotek/clients/checkTIN', 'Backend\ClientController@checkTIN')->name('admin.clients.checkTIN');

    //exchange-rate routes
    Route::get('exchange-rates/index', 'Backend\ExchangeRateController@index')->name('admin.exchange-rates.index');
    Route::get('exchange-rates/create', 'Backend\ExchangeRateController@create')->name('admin.exchange-rates.create');
    Route::post('exchange-rates/store', 'Backend\ExchangeRateController@store')->name('admin.exchange-rates.store');
    Route::get('exchange-rates/edit/{id}', 'Backend\ExchangeRateController@edit')->name('admin.exchange-rates.edit');
    Route::put('exchange-rates/update/{id}', 'Backend\ExchangeRateController@update')->name('admin.exchange-rates.update');
    Route::delete('exchange-rates/destroy/{id}', 'Backend\ExchangeRateController@destroy')->name('admin.exchange-rates.destroy');
    Route::post('exchange-rates/get-rate', 'Backend\ExchangeRateController@getRate')->name('admin.exchange-rates.get-rate');

    //items routes
    Route::get('Egotek/items/index', 'Backend\ItemController@index')->name('admin.items.index');
    Route::get('Egotek/items/create', 'Backend\ItemController@create')->name('admin.items.create');
    Route::post('Egotek/items/store', 'Backend\ItemController@store')->name('admin.items.store');
    Route::get('Egotek/items/show/{code}', 'Backend\ItemController@show')->name('admin.items.show');
    Route::get('Egotek/items/fiche/{code}', 'Backend\ItemController@fiche')->name('admin.items.fiche');
    Route::get('Egotek/items/edit/{code}', 'Backend\ItemController@edit')->name('admin.items.edit');
    Route::put('Egotek/items/update/{code}', 'Backend\ItemController@update')->name('admin.items.update');
    Route::delete('Egotek/items/destroy/{code}', 'Backend\ItemController@destroy')->name('admin.items.destroy');
    Route::post('Egotek/items/toggle-status/{code}', 'Backend\ItemController@toggleStatus')->name('admin.items.toggle-status');

    //materials routes
    Route::get('Egotek/materials/index', 'Backend\MaterialController@index')->name('admin.materials.index');
    Route::get('Egotek/materials/create', 'Backend\MaterialController@create')->name('admin.materials.create');
    Route::post('Egotek/materials/store', 'Backend\MaterialController@store')->name('admin.materials.store');
    Route::get('Egotek/materials/edit/{id}', 'Backend\MaterialController@edit')->name('admin.materials.edit');
    Route::put('Egotek/materials/update/{id}', 'Backend\MaterialController@update')->name('admin.materials.update');
    Route::get('Egotek/materials/{id}/depreciation', 'Backend\MaterialController@depreciation')->name('admin.materials.depreciation');
    Route::get('Egotek/materials/{id}/depreciation/excel', 'Backend\MaterialController@exportDepreciationExcel')->name('admin.materials.depreciation.export-excel');
    Route::get('Egotek/materials/{id}/depreciation/pdf', 'Backend\MaterialController@exportDepreciationPdf')->name('admin.materials.depreciation.export-pdf');
    Route::get('Egotek/immobilisations/export-excel', 'Backend\ImmobilisationExportController@exportExcel')->name('admin.immobilisations.export-excel');
    Route::get('Egotek/immobilisations/export-pdf', 'Backend\ImmobilisationExportController@exportPdf')->name('admin.immobilisations.export-pdf');
    Route::delete('Egotek/materials/destroy/{id}', 'Backend\MaterialController@destroy')->name('admin.materials.destroy');
    Route::post('Egotek/materials/toggle-status/{id}', 'Backend\MaterialController@toggleStatus')->name('admin.materials.toggle-status');

    //material-category routes
    Route::get('Egotek/material-category/index', 'Backend\MaterialCategoryController@index')->name('admin.material-category.index');
    Route::get('Egotek/material-category/create', 'Backend\MaterialCategoryController@create')->name('admin.material-category.create');
    Route::post('Egotek/material-category/store', 'Backend\MaterialCategoryController@store')->name('admin.material-category.store');
    Route::get('Egotek/material-category/edit/{id}', 'Backend\MaterialCategoryController@edit')->name('admin.material-category.edit');
    Route::put('Egotek/material-category/update/{id}', 'Backend\MaterialCategoryController@update')->name('admin.material-category.update');
    Route::delete('Egotek/material-category/destroy/{id}', 'Backend\MaterialCategoryController@destroy')->name('admin.material-category.destroy');

    //raw-material-category routes (ex food-category)
    Route::get('Egotek/raw-material-category/index', 'Backend\RawMaterialCategoryController@index')->name('admin.raw-material-category.index');
    Route::get('Egotek/raw-material-category/create', 'Backend\RawMaterialCategoryController@create')->name('admin.raw-material-category.create');
    Route::post('Egotek/raw-material-category/store', 'Backend\RawMaterialCategoryController@store')->name('admin.raw-material-category.store');
    Route::get('Egotek/raw-material-category/edit/{id}', 'Backend\RawMaterialCategoryController@edit')->name('admin.raw-material-category.edit');
    Route::put('Egotek/raw-material-category/update/{id}', 'Backend\RawMaterialCategoryController@update')->name('admin.raw-material-category.update');
    Route::delete('Egotek/raw-material-category/destroy/{id}', 'Backend\RawMaterialCategoryController@destroy')->name('admin.raw-material-category.destroy');

    //raw-material-measurement routes (ex food-measurement)
    Route::get('Egotek/raw-material-measurement/index', 'Backend\RawMaterialMeasurementController@index')->name('admin.raw-material-measurement.index');
    Route::get('Egotek/raw-material-measurement/create', 'Backend\RawMaterialMeasurementController@create')->name('admin.raw-material-measurement.create');
    Route::post('Egotek/raw-material-measurement/store', 'Backend\RawMaterialMeasurementController@store')->name('admin.raw-material-measurement.store');
    Route::get('Egotek/raw-material-measurement/edit/{id}', 'Backend\RawMaterialMeasurementController@edit')->name('admin.raw-material-measurement.edit');
    Route::put('Egotek/raw-material-measurement/update/{id}', 'Backend\RawMaterialMeasurementController@update')->name('admin.raw-material-measurement.update');
    Route::delete('Egotek/raw-material-measurement/destroy/{id}', 'Backend\RawMaterialMeasurementController@destroy')->name('admin.raw-material-measurement.destroy');

    //material-measurement routes
    Route::get('Egotek/material-measurement/index', 'Backend\MaterialMeasurementController@index')->name('admin.material-measurement.index');
    Route::get('Egotek/material-measurement/create', 'Backend\MaterialMeasurementController@create')->name('admin.material-measurement.create');
    Route::post('Egotek/material-measurement/store', 'Backend\MaterialMeasurementController@store')->name('admin.material-measurement.store');
    Route::get('Egotek/material-measurement/edit/{id}', 'Backend\MaterialMeasurementController@edit')->name('admin.material-measurement.edit');
    Route::put('Egotek/material-measurement/update/{id}', 'Backend\MaterialMeasurementController@update')->name('admin.material-measurement.update');
    Route::delete('Egotek/material-measurement/destroy/{id}', 'Backend\MaterialMeasurementController@destroy')->name('admin.material-measurement.destroy');

    //Extra Grand Stock des nourritures
    Route::get('Egotek/raw-material-extra-big-store/index', 'Backend\RawMaterialExtraBigStoreController@index')->name('admin.raw-material-extra-big-store.index');
    Route::get('Egotek/raw-material-extra-big-store/create', 'Backend\RawMaterialExtraBigStoreController@create')->name('admin.raw-material-extra-big-store.create');
    Route::post('Egotek/raw-material-extra-big-store/store', 'Backend\RawMaterialExtraBigStoreController@store')->name('admin.raw-material-extra-big-store.store');
    Route::get('Egotek/raw-material-extra-big-store/show/{code}', 'Backend\RawMaterialExtraBigStoreController@show')->name('admin.raw-material-extra-big-store.show');
    Route::get('Egotek/raw-material-extra-big-store/edit/{code}', 'Backend\RawMaterialExtraBigStoreController@edit')->name('admin.raw-material-extra-big-store.edit');
    Route::put('Egotek/raw-material-extra-big-store/update/{code}', 'Backend\RawMaterialExtraBigStoreController@update')->name('admin.raw-material-extra-big-store.update');
    Route::delete('Egotek/raw-material-extra-big-store/destroy/{id}', 'Backend\RawMaterialExtraBigStoreController@destroy')->name('admin.raw-material-extra-big-store.destroy');

    Route::get('Egotek/raw-material-extra-big-store/store-status/{code}', 'Backend\RawMaterialExtraBigStoreController@storeStatus')->name('admin.raw-material-extra-big-store.storeStatus');

    //production plan (planification de la production)
    Route::get('Egotek/production-plan/index', 'Backend\ProductionPlanController@index')->name('admin.production-plan.index');
    Route::get('Egotek/production-plan/create', 'Backend\ProductionPlanController@create')->name('admin.production-plan.create');
    Route::get('Egotek/production-plan/items-search', 'Backend\ProductionPlanController@searchItems')->name('admin.production-plan.items-search');
    Route::post('Egotek/production-plan/store', 'Backend\ProductionPlanController@store')->name('admin.production-plan.store');
    Route::get('Egotek/production-plan/show/{plan_number}', 'Backend\ProductionPlanController@show')->name('admin.production-plan.show');
    Route::get('Egotek/production-plan/edit/{plan_number}', 'Backend\ProductionPlanController@edit')->name('admin.production-plan.edit');
    Route::put('Egotek/production-plan/update/{plan_number}', 'Backend\ProductionPlanController@update')->name('admin.production-plan.update');
    Route::delete('Egotek/production-plan/destroy/{plan_number}', 'Backend\ProductionPlanController@destroy')->name('admin.production-plan.destroy');
    Route::put('Egotek/production-plan/validate/{plan_number}', 'Backend\ProductionPlanController@validatePlan')->name('admin.production-plan.validate');
    Route::put('Egotek/production-plan/confirm/{plan_number}', 'Backend\ProductionPlanController@confirm')->name('admin.production-plan.confirm');
    Route::put('Egotek/production-plan/approve/{plan_number}', 'Backend\ProductionPlanController@approve')->name('admin.production-plan.approve');
    Route::put('Egotek/production-plan/reject/{plan_number}', 'Backend\ProductionPlanController@reject')->name('admin.production-plan.reject');
    Route::get('Egotek/production-plan/print/{plan_number}', 'Backend\ProductionPlanController@printPlan')->name('admin.production-plan.print');
    Route::get('Egotek/production-plan/export-to-excel', 'Backend\ProductionPlanController@exportToExcel')->name('admin.production-plan.export-to-excel');

    //production order
    Route::get('Egotek/production-order/index', 'Backend\ProductionOrderController@index')->name('admin.production-order.index');
    Route::get('Egotek/production-order/export-to-excel', 'Backend\ProductionOrderController@exportToExcel')->name('admin.production-order.export-to-excel');
    Route::get('Egotek/production-order/create', 'Backend\ProductionOrderController@create')->name('admin.production-order.create');
    Route::get('Egotek/production-order/items-search', 'Backend\ProductionOrderController@searchItems')->name('admin.production-order.items-search');
    Route::post('Egotek/production-order/store', 'Backend\ProductionOrderController@store')->name('admin.production-order.store');
    Route::get('Egotek/production-order/show/{production_no}', 'Backend\ProductionOrderController@show')->name('admin.production-order.show');
    Route::get('Egotek/production-order/edit/{production_no}', 'Backend\ProductionOrderController@edit')->name('admin.production-order.edit');
    Route::put('Egotek/production-order/update/{production_no}', 'Backend\ProductionOrderController@update')->name('admin.production-order.update');
    Route::delete('Egotek/production-order/destroy/{production_no}', 'Backend\ProductionOrderController@destroy')->name('admin.production-order.destroy');
    Route::put('Egotek/production-order/validate/{production_no}', 'Backend\ProductionOrderController@validateOrder')->name('admin.production-order.validate');
    Route::put('Egotek/production-order/confirm/{production_no}', 'Backend\ProductionOrderController@confirm')->name('admin.production-order.confirm');
    Route::put('Egotek/production-order/approve/{production_no}', 'Backend\ProductionOrderController@approve')->name('admin.production-order.approve');
    Route::put('Egotek/production-order/reject/{production_no}', 'Backend\ProductionOrderController@reject')->name('admin.production-order.reject');

    //finished goods store
    Route::get('Egotek/finished-goods-store/index', 'Backend\FinishedGoodsStoreController@index')->name('admin.finished-goods-store.index');
    Route::get('Egotek/finished-goods-store/create', 'Backend\FinishedGoodsStoreController@create')->name('admin.finished-goods-store.create');
    Route::post('Egotek/finished-goods-store/store', 'Backend\FinishedGoodsStoreController@store')->name('admin.finished-goods-store.store');
    Route::get('Egotek/finished-goods-store/show/{code}', 'Backend\FinishedGoodsStoreController@show')->name('admin.finished-goods-store.show');
    Route::get('Egotek/finished-goods-store/status/{code}', 'Backend\FinishedGoodsStoreController@storeStatus')->name('admin.finished-goods-store.storeStatus');
    Route::get('Egotek/finished-goods-store/export-to-excel/{code}', 'Backend\FinishedGoodsStoreController@exportToExcel')->name('admin.finished-goods-store.exportToExcel');
    Route::get('Egotek/finished-goods-store/edit/{id}', 'Backend\FinishedGoodsStoreController@edit')->name('admin.finished-goods-store.edit');
    Route::put('Egotek/finished-goods-store/update/{id}', 'Backend\FinishedGoodsStoreController@update')->name('admin.finished-goods-store.update');
    Route::delete('Egotek/finished-goods-store/destroy/{id}', 'Backend\FinishedGoodsStoreController@destroy')->name('admin.finished-goods-store.destroy');
    Route::delete('Egotek/finished-goods-store-detail/destroy/{id}', 'Backend\FinishedGoodsStoreController@destroyDetail')->name('admin.finished-goods-store-detail.destroy');

   //finished goods-sm store
   Route::get('Egotek/finished-goods-sm-store/index', 'Backend\FinishedGoodsSmStoreController@index')->name('admin.finished-goods-sm-store.index');
   Route::get('Egotek/finished-goods-sm-store/create', 'Backend\FinishedGoodsSmStoreController@create')->name('admin.finished-goods-sm-store.create');
   Route::post('Egotek/finished-goods-sm-store/store', 'Backend\FinishedGoodsSmStoreController@store')->name('admin.finished-goods-sm-store.store');
   Route::get('Egotek/finished-goods-sm-store/show/{code}', 'Backend\FinishedGoodsSmStoreController@show')->name('admin.finished-goods-sm-store.show');
   Route::get('Egotek/finished-goods-sm-store/status/{code}', 'Backend\FinishedGoodsSmStoreController@storeStatus')->name('admin.finished-goods-sm-store.storeStatus');
   Route::get('Egotek/finished-goods-sm-store/edit/{id}', 'Backend\FinishedGoodsSmStoreController@edit')->name('admin.finished-goods-sm-store.edit');
   Route::put('Egotek/finished-goods-sm-store/update/{id}', 'Backend\FinishedGoodsSmStoreController@update')->name('admin.finished-goods-sm-store.update');
   Route::delete('Egotek/finished-goods-sm-store/destroy/{id}', 'Backend\FinishedGoodsSmStoreController@destroy')->name('admin.finished-goods-sm-store.destroy');
   Route::delete('Egotek/finished-goods-sm-store-detail/destroy/{id}', 'Backend\FinishedGoodsSmStoreController@destroyDetail')->name('admin.finished-goods-sm-store-detail.destroy');

   //finished goods-sm store inventory
   Route::get('Egotek/finished-goods-sm-store-inventory/index', 'Backend\FinishedGoodsSmStoreInventoryController@index')->name('admin.finished-goods-sm-store-inventory.index');
   Route::get('Egotek/finished-goods-sm-store-inventory/create/{code}', 'Backend\FinishedGoodsSmStoreInventoryController@create')->name('admin.finished-goods-sm-store-inventory.create');
   Route::post('Egotek/finished-goods-sm-store-inventory/store', 'Backend\FinishedGoodsSmStoreInventoryController@store')->name('admin.finished-goods-sm-store-inventory.store');
   Route::get('Egotek/finished-goods-sm-store-inventory/edit/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@edit')->name('admin.finished-goods-sm-store-inventory.edit');
   Route::get('Egotek/finished-goods-sm-store-inventory/show/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@show')->name('admin.finished-goods-sm-store-inventory.show');
   Route::put('Egotek/finished-goods-sm-store-inventory/update/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@update')->name('admin.finished-goods-sm-store-inventory.update');
   Route::delete('Egotek/finished-goods-sm-store-inventory/destroy/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@destroy')->name('admin.finished-goods-sm-store-inventory.destroy');
   Route::get('Egotek/finished-goods-sm-store-inventory/generatePdf/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@generatePdf')->name('admin.finished-goods-sm-store-inventory.generatePdf');
   Route::put('Egotek/finished-goods-sm-store-inventory/validate/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@validateInventory')->name('admin.finished-goods-sm-store-inventory.validate');
   Route::put('Egotek/finished-goods-sm-store-inventory/reject/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@rejectInventory')->name('admin.finished-goods-sm-store-inventory.reject');
   Route::put('Egotek/finished-goods-sm-store-inventory/reset/{inventory_no}', 'Backend\FinishedGoodsSmStoreInventoryController@resetInventory')->name('admin.finished-goods-sm-store-inventory.reset');

    // Stock des marchandises (goods store - distinct des produits finis)
    Route::get('Egotek/goods-store/index', 'Backend\GoodStoreController@index')->name('admin.goods-store.index');
    Route::get('Egotek/goods-store/create', 'Backend\GoodStoreController@create')->name('admin.goods-store.create');
    Route::post('Egotek/goods-store/store', 'Backend\GoodStoreController@store')->name('admin.goods-store.store');
    Route::get('Egotek/goods-store/show/{code}', 'Backend\GoodStoreController@show')->name('admin.goods-store.show');
    Route::get('Egotek/goods-store/edit/{id}', 'Backend\GoodStoreController@edit')->name('admin.goods-store.edit');
    Route::put('Egotek/goods-store/update/{id}', 'Backend\GoodStoreController@update')->name('admin.goods-store.update');
    Route::delete('Egotek/goods-store/destroy/{id}', 'Backend\GoodStoreController@destroy')->name('admin.goods-store.destroy');
    Route::delete('Egotek/goods-store-detail/destroy/{id}', 'Backend\GoodStoreController@destroyDetail')->name('admin.goods-store-detail.destroy');
    Route::get('Egotek/goods-store/print/{code}', 'Backend\GoodStoreController@print')->name('admin.goods-store.print');

    // Entrées marchandises (goods stockin)
    Route::get('Egotek/goods-stockins/index', 'Backend\GoodStockinController@index')->name('admin.goods-stockins.index');
    Route::get('Egotek/goods-stockins/create', 'Backend\GoodStockinController@create')->name('admin.goods-stockins.create');
    Route::post('Egotek/goods-stockins/store', 'Backend\GoodStockinController@store')->name('admin.goods-stockins.store');
    Route::get('Egotek/goods-stockins/show/{stockin_no}', 'Backend\GoodStockinController@show')->name('admin.goods-stockins.show');
    Route::get('Egotek/goods-stockins/edit/{stockin_no}', 'Backend\GoodStockinController@edit')->name('admin.goods-stockins.edit');
    Route::put('Egotek/goods-stockins/update/{stockin_no}', 'Backend\GoodStockinController@update')->name('admin.goods-stockins.update');
    Route::delete('Egotek/goods-stockins/destroy/{stockin_no}', 'Backend\GoodStockinController@destroy')->name('admin.goods-stockins.destroy');
    Route::put('Egotek/goods-stockins/validate/{stockin_no}', 'Backend\GoodStockinController@validateStockin')->name('admin.goods-stockins.validate');
    Route::put('Egotek/goods-stockins/confirm/{stockin_no}', 'Backend\GoodStockinController@confirm')->name('admin.goods-stockins.confirm');
    Route::put('Egotek/goods-stockins/approuve/{stockin_no}', 'Backend\GoodStockinController@approuve')->name('admin.goods-stockins.approuve');
    Route::put('Egotek/goods-stockins/reject/{stockin_no}', 'Backend\GoodStockinController@reject')->name('admin.goods-stockins.reject');
    Route::put('Egotek/goods-stockins/reset/{stockin_no}', 'Backend\GoodStockinController@reset')->name('admin.goods-stockins.reset');
    Route::get('Egotek/goods-stockins/print/{stockin_no}', 'Backend\GoodStockinController@print')->name('admin.goods-stockins.print');

    // Importations marchandises (OBR AddStockMovementImporters à l'approbation)
    Route::get('Egotek/imports/index', 'Backend\ImportController@index')->name('admin.imports.index');
    Route::get('Egotek/imports/create', 'Backend\ImportController@create')->name('admin.imports.create');
    Route::get('Egotek/imports/items-search', 'Backend\ImportController@searchItems')->name('admin.imports.items-search');
    Route::post('Egotek/imports/store', 'Backend\ImportController@store')->name('admin.imports.store');
    Route::get('Egotek/imports/show/{import_no}', 'Backend\ImportController@show')->name('admin.imports.show');
    Route::get('Egotek/imports/edit/{import_no}', 'Backend\ImportController@edit')->name('admin.imports.edit');
    Route::put('Egotek/imports/update/{import_no}', 'Backend\ImportController@update')->name('admin.imports.update');
    Route::delete('Egotek/imports/destroy/{import_no}', 'Backend\ImportController@destroy')->name('admin.imports.destroy');
    Route::put('Egotek/imports/validate/{import_no}', 'Backend\ImportController@validateImport')->name('admin.imports.validate');
    Route::put('Egotek/imports/confirm/{import_no}', 'Backend\ImportController@confirm')->name('admin.imports.confirm');
    Route::put('Egotek/imports/approuve/{import_no}', 'Backend\ImportController@approuve')->name('admin.imports.approuve');
    Route::put('Egotek/imports/reject/{import_no}', 'Backend\ImportController@reject')->name('admin.imports.reject');
    Route::put('Egotek/imports/reset/{import_no}', 'Backend\ImportController@reset')->name('admin.imports.reset');
    Route::get('Egotek/imports/print/{import_no}', 'Backend\ImportController@print')->name('admin.imports.print');

    // Sorties marchandises (goods stockout)
    Route::get('Egotek/goods-stockouts/index', 'Backend\GoodStockoutController@index')->name('admin.goods-stockouts.index');
    Route::get('Egotek/goods-stockouts/create', 'Backend\GoodStockoutController@create')->name('admin.goods-stockouts.create');
    Route::post('Egotek/goods-stockouts/store', 'Backend\GoodStockoutController@store')->name('admin.goods-stockouts.store');
    Route::get('Egotek/goods-stockouts/show/{stockout_no}', 'Backend\GoodStockoutController@show')->name('admin.goods-stockouts.show');
    Route::get('Egotek/goods-stockouts/edit/{stockout_no}', 'Backend\GoodStockoutController@edit')->name('admin.goods-stockouts.edit');
    Route::put('Egotek/goods-stockouts/update/{stockout_no}', 'Backend\GoodStockoutController@update')->name('admin.goods-stockouts.update');
    Route::delete('Egotek/goods-stockouts/destroy/{stockout_no}', 'Backend\GoodStockoutController@destroy')->name('admin.goods-stockouts.destroy');
    Route::put('Egotek/goods-stockouts/validate/{stockout_no}', 'Backend\GoodStockoutController@validateStockout')->name('admin.goods-stockouts.validate');
    Route::put('Egotek/goods-stockouts/confirm/{stockout_no}', 'Backend\GoodStockoutController@confirm')->name('admin.goods-stockouts.confirm');
    Route::put('Egotek/goods-stockouts/approuve/{stockout_no}', 'Backend\GoodStockoutController@approuve')->name('admin.goods-stockouts.approuve');
    Route::put('Egotek/goods-stockouts/reject/{stockout_no}', 'Backend\GoodStockoutController@reject')->name('admin.goods-stockouts.reject');
    Route::put('Egotek/goods-stockouts/reset/{stockout_no}', 'Backend\GoodStockoutController@reset')->name('admin.goods-stockouts.reset');
    Route::get('Egotek/goods-stockouts/print/{stockout_no}', 'Backend\GoodStockoutController@print')->name('admin.goods-stockouts.print');

    // Rapport stock marchandises
    Route::get('Egotek/goods-store-report/index', 'Backend\GoodStoreReportController@index')->name('admin.goods-store-report.index');
    Route::get('Egotek/goods-store-report/export-to-excel', 'Backend\GoodStoreReportController@exportToExcel')->name('admin.goods-store-report.export-to-excel');
    Route::get('Egotek/goods-store-report/export-to-pdf', 'Backend\GoodStoreReportController@exportToPdf')->name('admin.goods-store-report.export-to-pdf');

    // Inventaire stock marchandises
    Route::get('Egotek/goods-store-inventory/index', 'Backend\GoodStoreInventoryController@index')->name('admin.goods-store-inventory.index');
    Route::get('Egotek/goods-store-inventory/create/{code}', 'Backend\GoodStoreInventoryController@create')->name('admin.goods-store-inventory.create');
    Route::post('Egotek/goods-store-inventory/store', 'Backend\GoodStoreInventoryController@store')->name('admin.goods-store-inventory.store');
    Route::get('Egotek/goods-store-inventory/edit/{inventory_no}', 'Backend\GoodStoreInventoryController@edit')->name('admin.goods-store-inventory.edit');
    Route::get('Egotek/goods-store-inventory/show/{inventory_no}', 'Backend\GoodStoreInventoryController@show')->name('admin.goods-store-inventory.show');
    Route::put('Egotek/goods-store-inventory/update/{inventory_no}', 'Backend\GoodStoreInventoryController@update')->name('admin.goods-store-inventory.update');
    Route::delete('Egotek/goods-store-inventory/destroy/{inventory_no}', 'Backend\GoodStoreInventoryController@destroy')->name('admin.goods-store-inventory.destroy');
    Route::get('Egotek/goods-store-inventory/generatePdf/{inventory_no}', 'Backend\GoodStoreInventoryController@bon_inventaire')->name('admin.goods-store-inventory.generatePdf');
    Route::put('Egotek/goods-store-inventory/validate/{inventory_no}', 'Backend\GoodStoreInventoryController@validateInventory')->name('admin.goods-store-inventory.validate');
    Route::put('Egotek/goods-store-inventory/reject/{inventory_no}', 'Backend\GoodStoreInventoryController@rejectInventory')->name('admin.goods-store-inventory.reject');
    Route::put('Egotek/goods-store-inventory/reset/{inventory_no}', 'Backend\GoodStoreInventoryController@resetInventory')->name('admin.goods-store-inventory.reset');
    Route::get('Egotek/goods-store-inventory/print/{inventory_no}', 'Backend\GoodStoreInventoryController@print')->name('admin.goods-store-inventory.print');

    // Inventaire stock des produits finis
    Route::get('Egotek/finished-goods-store-inventory/index', 'Backend\FinishedGoodsStoreInventoryController@index')->name('admin.finished-goods-store-inventory.index');
    Route::get('Egotek/finished-goods-store-inventory/create/{code}', 'Backend\FinishedGoodsStoreInventoryController@create')->name('admin.finished-goods-store-inventory.create');
    Route::post('Egotek/finished-goods-store-inventory/store', 'Backend\FinishedGoodsStoreInventoryController@store')->name('admin.finished-goods-store-inventory.store');
    Route::get('Egotek/finished-goods-store-inventory/edit/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@edit')->name('admin.finished-goods-store-inventory.edit');
    Route::get('Egotek/finished-goods-store-inventory/show/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@show')->name('admin.finished-goods-store-inventory.show');
    Route::put('Egotek/finished-goods-store-inventory/update/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@update')->name('admin.finished-goods-store-inventory.update');
    Route::delete('Egotek/finished-goods-store-inventory/destroy/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@destroy')->name('admin.finished-goods-store-inventory.destroy');
    Route::get('Egotek/finished-goods-store-inventory/generatePdf/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@bon_inventaire')->name('admin.finished-goods-store-inventory.generatePdf');
    Route::put('Egotek/finished-goods-store-inventory/validate/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@validateInventory')->name('admin.finished-goods-store-inventory.validate');
    Route::put('Egotek/finished-goods-store-inventory/reject/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@rejectInventory')->name('admin.finished-goods-store-inventory.reject');
    Route::put('Egotek/finished-goods-store-inventory/reset/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@resetInventory')->name('admin.finished-goods-store-inventory.reset');

    //Grand Stock des nourritures
    Route::get('Egotek/raw-material-big-store/index', 'Backend\RawMaterialBigStoreController@index')->name('admin.raw-material-big-store.index');
    Route::get('Egotek/raw-material-big-store/create', 'Backend\RawMaterialBigStoreController@create')->name('admin.raw-material-big-store.create');
    Route::post('Egotek/raw-material-big-store/store', 'Backend\RawMaterialBigStoreController@store')->name('admin.raw-material-big-store.store');
    Route::get('Egotek/raw-material-big-store/show/{code}', 'Backend\RawMaterialBigStoreController@show')->name('admin.raw-material-big-store.show');
    Route::get('Egotek/raw-material-big-store/edit/{code}', 'Backend\RawMaterialBigStoreController@edit')->name('admin.raw-material-big-store.edit');
    Route::put('Egotek/raw-material-big-store/update/{code}', 'Backend\RawMaterialBigStoreController@update')->name('admin.raw-material-big-store.update');
    Route::delete('Egotek/raw-material-big-store/destroy/{id}', 'Backend\RawMaterialBigStoreController@destroy')->name('admin.raw-material-big-store.destroy');

    Route::get('Egotek/raw-material-big-store/store-status/{code}', 'Backend\RawMaterialBigStoreController@storeStatus')->name('admin.raw-material-big-store.storeStatus');
    Route::get('Egotek/raw-material-big-store-status/export-to-exce/{code}', 'Backend\RawMaterialBigStoreController@ExportToExcel')->name('admin.raw-material-big-store.exportToExcel');

    //Petit Stock des matières premières
    Route::get('Egotek/raw-material-small-store/index', 'Backend\RawMaterialSmallStoreController@index')->name('admin.raw-material-small-store.index');
    Route::get('Egotek/raw-material-small-store/create', 'Backend\RawMaterialSmallStoreController@create')->name('admin.raw-material-small-store.create');
    Route::post('Egotek/raw-material-small-store/store', 'Backend\RawMaterialSmallStoreController@store')->name('admin.raw-material-small-store.store');
    Route::get('Egotek/raw-material-small-store/show/{code}', 'Backend\RawMaterialSmallStoreController@show')->name('admin.raw-material-small-store.show');
    Route::get('Egotek/raw-material-small-store/edit/{code}', 'Backend\RawMaterialSmallStoreController@edit')->name('admin.raw-material-small-store.edit');
    Route::put('Egotek/raw-material-small-store/update/{code}', 'Backend\RawMaterialSmallStoreController@update')->name('admin.raw-material-small-store.update');
    Route::delete('Egotek/raw-material-small-store/destroy/{id}', 'Backend\RawMaterialSmallStoreController@destroy')->name('admin.raw-material-small-store.destroy');

    Route::get('Egotek/raw-material-small-store/store-status/{code}', 'Backend\RawMaterialSmallStoreController@storeStatus')->name('admin.raw-material-small-store.storeStatus');
    Route::get('Egotek/raw-material-small-store-status/export-to-exce/{code}', 'Backend\RawMaterialSmallStoreController@ExportToExcel')->name('admin.raw-material-small-store.exportToExcel');

    //Extra Grand Stock des materiels
    Route::get('Egotek/material-extra-big-store/index', 'Backend\MaterialExtraBigStoreController@index')->name('admin.material-extra-big-store.index');
    Route::get('Egotek/material-extra-big-store/create', 'Backend\MaterialExtraBigStoreController@create')->name('admin.material-extra-big-store.create');
    Route::post('Egotek/material-extra-big-store/store', 'Backend\MaterialExtraBigStoreController@store')->name('admin.material-extra-big-store.store');
    Route::get('Egotek/material-extra-big-store/show/{code}', 'Backend\MaterialExtraBigStoreController@show')->name('admin.material-extra-big-store.show');
    Route::get('Egotek/material-extra-big-store/edit/{code}', 'Backend\MaterialExtraBigStoreController@edit')->name('admin.material-extra-big-store.edit');
    Route::put('Egotek/material-extra-big-store/update/{code}', 'Backend\MaterialExtraBigStoreController@update')->name('admin.material-extra-big-store.update');
    Route::delete('Egotek/material-extra-big-store/destroy/{id}', 'Backend\MaterialExtraBigStoreController@destroy')->name('admin.material-extra-big-store.destroy');

    Route::get('Egotek/material-extra-big-store/store-status/{code}', 'Backend\MaterialExtraBigStoreController@storeStatus')->name('admin.material-extra-big-store.storeStatus');

    //Grand Stock des materiels
    Route::get('Egotek/material-big-store/index', 'Backend\MaterialBigStoreController@index')->name('admin.material-big-store.index');
    Route::get('Egotek/material-big-store/create', 'Backend\MaterialBigStoreController@create')->name('admin.material-big-store.create');
    Route::post('Egotek/material-big-store/store', 'Backend\MaterialBigStoreController@store')->name('admin.material-big-store.store');
    Route::get('Egotek/material-big-store/show/{code}', 'Backend\MaterialBigStoreController@show')->name('admin.material-big-store.show');
    Route::get('Egotek/material-big-store/edit/{code}', 'Backend\MaterialBigStoreController@edit')->name('admin.material-big-store.edit');
    Route::put('Egotek/material-big-store/update/{code}', 'Backend\MaterialBigStoreController@update')->name('admin.material-big-store.update');
    Route::delete('Egotek/material-big-store/destroy/{id}', 'Backend\MaterialBigStoreController@destroy')->name('admin.material-big-store.destroy');

    Route::get('Egotek/material-big-store/store-status/{code}', 'Backend\MaterialBigStoreController@storeStatus')->name('admin.material-big-store.storeStatus');
    Route::get('Egotek/material-big-store/export-to-exce/{code}', 'Backend\MaterialBigStoreController@ExportToExcel')->name('admin.material-big-store.exportToExcel');

    //Petit Stock des materiels
    Route::get('Egotek/material-small-store/index', 'Backend\MaterialSmallStoreController@index')->name('admin.material-small-store.index');
    Route::get('Egotek/material-small-store/create', 'Backend\MaterialSmallStoreController@create')->name('admin.material-small-store.create');
    Route::post('Egotek/material-small-store/store', 'Backend\MaterialSmallStoreController@store')->name('admin.material-small-store.store');
    Route::get('Egotek/material-small-store/show/{code}', 'Backend\MaterialSmallStoreController@show')->name('admin.material-small-store.show');
    Route::get('Egotek/material-small-store/edit/{code}', 'Backend\MaterialSmallStoreController@edit')->name('admin.material-small-store.edit');
    Route::put('Egotek/material-small-store/update/{code}', 'Backend\MaterialSmallStoreController@update')->name('admin.material-small-store.update');
    Route::delete('Egotek/material-small-store/destroy/{id}', 'Backend\MaterialSmallStoreController@destroy')->name('admin.material-small-store.destroy');

    Route::get('Egotek/material-small-store/store-status/{code}', 'Backend\MaterialSmallStoreController@storeStatus')->name('admin.material-small-store.storeStatus');

    
    //suppliers routes
    Route::get('Egotek/suppliers/index', 'Backend\SupplierController@index')->name('admin.suppliers.index');
    Route::get('Egotek/suppliers/create', 'Backend\SupplierController@create')->name('admin.suppliers.create');
    Route::post('Egotek/suppliers/store', 'Backend\SupplierController@store')->name('admin.suppliers.store');
    Route::get('Egotek/suppliers/edit/{id}', 'Backend\SupplierController@edit')->name('admin.suppliers.edit');
    Route::put('Egotek/suppliers/update/{id}', 'Backend\SupplierController@update')->name('admin.suppliers.update');
    Route::delete('Egotek/suppliers/destroy/{id}', 'Backend\SupplierController@destroy')->name('admin.suppliers.destroy');

    //Extra Grand Stock des nourritures
    Route::get('Egotek/raw-material-extra-big-store-inventory/index', 'Backend\RawMaterialExtraBigStoreInventoryController@index')->name('admin.raw-material-extra-big-store-inventory.index');
    Route::get('Egotek/raw-material-extra-big-store-inventory/create/{code}', 'Backend\RawMaterialExtraBigStoreInventoryController@create')->name('admin.raw-material-extra-big-store-inventory.create');
    Route::post('Egotek/raw-material-extra-big-store-inventory/store', 'Backend\RawMaterialExtraBigStoreInventoryController@store')->name('admin.raw-material-extra-big-store-inventory.store');
    Route::get('Egotek/raw-material-extra-big-store-inventory/inventory/{id}', 'Backend\RawMaterialExtraBigStoreInventoryController@inventory')->name('admin.raw-material-extra-big-store-inventory.inventory');
    Route::get('Egotek/raw-material-extra-big-store-inventory/edit/{inventory_no}', 'Backend\RawMaterialExtraBigStoreInventoryController@edit')->name('admin.raw-material-extra-big-store-inventory.edit');
    Route::get('Egotek/raw-material-extra-big-store-inventory/show/{inventory_no}', 'Backend\RawMaterialExtraBigStoreInventoryController@show')->name('admin.raw-material-extra-big-store-inventory.show');
    Route::put('Egotek/raw-material-extra-big-store-inventory/update/{inventory_no}', 'Backend\RawMaterialExtraBigStoreInventoryController@update')->name('admin.raw-material-extra-big-store-inventory.update');
    Route::delete('Egotek/raw-material-extra-big-store-inventory/destroy/{id}', 'Backend\RawMaterialExtraBigStoreInventoryController@destroy')->name('admin.raw-material-extra-big-store-inventory.destroy');

    Route::get('Egotek/raw-material-extra-big-store-inventory/generatePdf/{inventory_no}','Backend\RawMaterialExtraBigStoreInventoryController@bon_inventaire')->name('admin.raw-material-extra-big-store-inventory.generatePdf');
    Route::put('Egotek/raw-material-extra-big-store-inventory/validate/{inventory_no}','Backend\RawMaterialExtraBigStoreInventoryController@validateInventory')->name('admin.raw-material-extra-big-store-inventory.validate');
    Route::put('Egotek/raw-material-extra-big-store-inventory/reject/{inventory_no}','Backend\RawMaterialExtraBigStoreInventoryController@rejectInventory')->name('admin.raw-material-extra-big-store-inventory.reject');
    Route::put('Egotek/raw-material-extra-big-store-inventory/reset/{inventory_no}','Backend\RawMaterialExtraBigStoreInventoryController@resetInventory')->name('admin.raw-material-extra-big-store-inventory.reset');

    //Grand Stock des nourritures
    Route::get('Egotek/raw-material-big-store-inventory/index', 'Backend\RawMaterialBigStoreInventoryController@index')->name('admin.raw-material-big-store-inventory.index');
    Route::get('Egotek/raw-material-big-store-inventory/create/{code}', 'Backend\RawMaterialBigStoreInventoryController@create')->name('admin.raw-material-big-store-inventory.create');
    Route::post('Egotek/raw-material-big-store-inventory/store', 'Backend\RawMaterialBigStoreInventoryController@store')->name('admin.raw-material-big-store-inventory.store');
    Route::get('Egotek/raw-material-big-store-inventory/inventory/{id}', 'Backend\RawMaterialBigStoreInventoryController@inventory')->name('admin.raw-material-big-store-inventory.inventory');
    Route::get('Egotek/raw-material-big-store-inventory/edit/{inventory_no}', 'Backend\RawMaterialBigStoreInventoryController@edit')->name('admin.raw-material-big-store-inventory.edit');
    Route::get('Egotek/raw-material-big-store-inventory/show/{inventory_no}', 'Backend\RawMaterialBigStoreInventoryController@show')->name('admin.raw-material-big-store-inventory.show');
    Route::put('Egotek/raw-material-big-store-inventory/update/{inventory_no}', 'Backend\RawMaterialBigStoreInventoryController@update')->name('admin.raw-material-big-store-inventory.update');
    Route::delete('Egotek/raw-material-big-store-inventory/destroy/{id}', 'Backend\RawMaterialBigStoreInventoryController@destroy')->name('admin.raw-material-big-store-inventory.destroy');

    Route::get('Egotek/raw-material-big-store-inventory/generatePdf/{inventory_no}','Backend\RawMaterialBigStoreInventoryController@bon_inventaire')->name('admin.raw-material-big-store-inventory.generatePdf');
    Route::put('Egotek/raw-material-big-store-inventory/validate/{inventory_no}','Backend\RawMaterialBigStoreInventoryController@validateInventory')->name('admin.raw-material-big-store-inventory.validate');
    Route::put('Egotek/raw-material-big-store-inventory/reject/{inventory_no}','Backend\RawMaterialBigStoreInventoryController@rejectInventory')->name('admin.raw-material-big-store-inventory.reject');
    Route::put('Egotek/raw-material-big-store-inventory/reset/{inventory_no}','Backend\RawMaterialBigStoreInventoryController@resetInventory')->name('admin.raw-material-big-store-inventory.reset');
    Route::get('Egotek/raw-material-big-store-inventory/export-to-excel/{inventory_no}','Backend\RawMaterialBigStoreInventoryController@exportToExcel')->name('admin.raw-material-big-store-inventory.export-to-excel');

    //Petit Stock des Nourritures
    Route::get('Egotek/raw-material-small-store-inventory/index', 'Backend\RawMaterialSmallStoreInventoryController@index')->name('admin.raw-material-small-store-inventory.index');
    Route::get('Egotek/raw-material-small-store-inventory/create/{code}', 'Backend\RawMaterialSmallStoreInventoryController@create')->name('admin.raw-material-small-store-inventory.create');
    Route::post('Egotek/raw-material-small-store-inventory/store', 'Backend\RawMaterialSmallStoreInventoryController@store')->name('admin.raw-material-small-store-inventory.store');
    Route::get('Egotek/raw-material-small-store-inventory/inventory/{id}', 'Backend\RawMaterialSmallStoreInventoryController@inventory')->name('admin.raw-material-small-store-inventory.inventory');
    Route::get('Egotek/raw-material-small-store-inventory/edit/{inventory_no}', 'Backend\RawMaterialSmallStoreInventoryController@edit')->name('admin.raw-material-small-store-inventory.edit');
    Route::get('Egotek/raw-material-small-store-inventory/show/{inventory_no}', 'Backend\RawMaterialSmallStoreInventoryController@show')->name('admin.raw-material-small-store-inventory.show');
    Route::put('Egotek/raw-material-small-store-inventory/update/{inventory_no}', 'Backend\RawMaterialSmallStoreInventoryController@update')->name('admin.raw-material-small-store-inventory.update');
    Route::delete('Egotek/raw-material-small-store-inventory/destroy/{id}', 'Backend\RawMaterialSmallStoreInventoryController@destroy')->name('admin.raw-material-small-store-inventory.destroy');

    Route::get('Egotek/raw-material-small-store-inventory/generatePdf/{inventory_no}','Backend\RawMaterialSmallStoreInventoryController@bon_inventaire')->name('admin.raw-material-small-store-inventory.generatePdf');
    Route::put('Egotek/raw-material-small-store-inventory/validate/{inventory_no}','Backend\RawMaterialSmallStoreInventoryController@validateInventory')->name('admin.raw-material-small-store-inventory.validate');
    Route::put('Egotek/raw-material-small-store-inventory/reject/{inventory_no}','Backend\RawMaterialSmallStoreInventoryController@rejectInventory')->name('admin.raw-material-small-store-inventory.reject');
    Route::put('Egotek/raw-material-small-store-inventory/reset/{inventory_no}','Backend\RawMaterialSmallStoreInventoryController@resetInventory')->name('admin.raw-material-small-store-inventory.reset');
    Route::get('Egotek/raw-material-small-store-inventory/export-to-excel/{inventory_no}','Backend\RawMaterialSmallStoreInventoryController@exportToExcel')->name('admin.raw-material-small-store-inventory.export-to-excel');

    //Extra Grand Stock Des Materiels
    Route::get('Egotek/material-extra-big-store-inventory/index', 'Backend\MaterialExtraBigStoreInventoryController@index')->name('admin.material-extra-big-store-inventory.index');
    Route::get('Egotek/material-extra-big-store-inventory/create/{code}', 'Backend\MaterialExtraBigStoreInventoryController@create')->name('admin.material-extra-big-store-inventory.create');
    Route::post('Egotek/material-extra-big-store-inventory/store', 'Backend\MaterialExtraBigStoreInventoryController@store')->name('admin.material-extra-big-store-inventory.store');
    Route::get('Egotek/material-extra-big-store-inventory/inventory/{id}', 'Backend\MaterialExtraBigStoreInventoryController@inventory')->name('admin.material-extra-big-store-inventory.inventory');
    Route::get('Egotek/material-extra-big-store-inventory/edit/{inventory_no}', 'Backend\MaterialExtraBigStoreInventoryController@edit')->name('admin.material-extra-big-store-inventory.edit');
    Route::get('Egotek/material-extra-big-store-inventory/show/{inventory_no}', 'Backend\MaterialExtraBigStoreInventoryController@show')->name('admin.material-extra-big-store-inventory.show');
    Route::put('Egotek/material-extra-big-store-inventory/update/{inventory_no}', 'Backend\MaterialExtraBigStoreInventoryController@update')->name('admin.material-extra-big-store-inventory.update');
    Route::delete('Egotek/material-extra-big-store-inventory/destroy/{id}', 'Backend\MaterialExtraBigStoreInventoryController@destroy')->name('admin.material-extra-big-store-inventory.destroy');

    Route::get('Egotek/material-extra-big-store-inventory/generatePdf/{inventory_no}','Backend\MaterialExtraBigStoreInventoryController@bon_inventaire')->name('admin.material-extra-big-store-inventory.generatePdf');
    Route::put('Egotek/material-extra-big-store-inventory/validate/{inventory_no}','Backend\MaterialExtraBigStoreInventoryController@validateInventory')->name('admin.material-extra-big-store-inventory.validate');
    Route::put('Egotek/material-extra-big-store-inventory/reject/{inventory_no}','Backend\MaterialExtraBigStoreInventoryController@rejectInventory')->name('admin.material-extra-big-store-inventory.reject');
    Route::put('Egotek/material-extra-big-store-inventory/reset/{inventory_no}','Backend\MaterialExtraBigStoreInventoryController@resetInventory')->name('admin.material-extra-big-store-inventory.reset');

    //Grand Stock Des Materiels
    Route::get('Egotek/material-big-store-inventory/index', 'Backend\MaterialBigStoreInventoryController@index')->name('admin.material-big-store-inventory.index');
    Route::get('Egotek/material-big-store-inventory/create/{code}', 'Backend\MaterialBigStoreInventoryController@create')->name('admin.material-big-store-inventory.create');
    Route::post('Egotek/material-big-store-inventory/store', 'Backend\MaterialBigStoreInventoryController@store')->name('admin.material-big-store-inventory.store');
    Route::get('Egotek/material-big-store-inventory/inventory/{id}', 'Backend\MaterialBigStoreInventoryController@inventory')->name('admin.material-big-store-inventory.inventory');
    Route::get('Egotek/material-big-store-inventory/edit/{inventory_no}', 'Backend\MaterialBigStoreInventoryController@edit')->name('admin.material-big-store-inventory.edit');
    Route::get('Egotek/material-big-store-inventory/show/{inventory_no}', 'Backend\MaterialBigStoreInventoryController@show')->name('admin.material-big-store-inventory.show');
    Route::put('Egotek/material-big-store-inventory/update/{inventory_no}', 'Backend\MaterialBigStoreInventoryController@update')->name('admin.material-big-store-inventory.update');
    Route::delete('Egotek/material-big-store-inventory/destroy/{id}', 'Backend\MaterialBigStoreInventoryController@destroy')->name('admin.material-big-store-inventory.destroy');

    Route::get('Egotek/material-big-store-inventory/generatePdf/{inventory_no}','Backend\MaterialBigStoreInventoryController@bon_inventaire')->name('admin.material-big-store-inventory.generatePdf');
    Route::get('Egotek/material-md-store-inventory/export-to-excel/{inventory_no}','Backend\MaterialBigStoreInventoryController@exportToExcel')->name('admin.material-md-store-inventory.export-to-excel');
    Route::put('Egotek/material-big-store-inventory/validate/{inventory_no}','Backend\MaterialBigStoreInventoryController@validateInventory')->name('admin.material-big-store-inventory.validate');
    Route::put('Egotek/material-big-store-inventory/reject/{inventory_no}','Backend\MaterialBigStoreInventoryController@rejectInventory')->name('admin.material-big-store-inventory.reject');
    Route::put('Egotek/material-big-store-inventory/reset/{inventory_no}','Backend\MaterialBigStoreInventoryController@resetInventory')->name('admin.material-big-store-inventory.reset');

    //Petit Stock des materiels
    Route::get('Egotek/material-small-store-inventory/index', 'Backend\MaterialSmallStoreInventoryController@index')->name('admin.material-small-store-inventory.index');
    Route::get('Egotek/material-small-store-inventory/create/{code}', 'Backend\MaterialSmallStoreInventoryController@create')->name('admin.material-small-store-inventory.create');
    Route::post('Egotek/material-small-store-inventory/store', 'Backend\MaterialSmallStoreInventoryController@store')->name('admin.material-small-store-inventory.store');
    Route::get('Egotek/material-small-store-inventory/inventory/{id}', 'Backend\MaterialSmallStoreInventoryController@inventory')->name('admin.material-small-store-inventory.inventory');
    Route::get('Egotek/material-small-store-inventory/edit/{inventory_no}', 'Backend\MaterialSmallStoreInventoryController@edit')->name('admin.material-small-store-inventory.edit');
    Route::get('Egotek/material-small-store-inventory/show/{inventory_no}', 'Backend\MaterialSmallStoreInventoryController@show')->name('admin.material-small-store-inventory.show');
    Route::put('Egotek/material-small-store-inventory/update/{inventory_no}', 'Backend\MaterialSmallStoreInventoryController@update')->name('admin.material-small-store-inventory.update');
    Route::delete('Egotek/material-small-store-inventory/destroy/{id}', 'Backend\MaterialSmallStoreInventoryController@destroy')->name('admin.material-small-store-inventory.destroy');

    Route::get('Egotek/material-small-store-inventory/generatePdf/{inventory_no}','Backend\MaterialSmallStoreInventoryController@bon_inventaire')->name('admin.material-small-store-inventory.generatePdf');
    Route::put('Egotek/material-small-store-inventory/validate/{inventory_no}','Backend\MaterialSmallStoreInventoryController@validateInventory')->name('admin.material-small-store-inventory.validate');
    Route::put('Egotek/material-small-store-inventory/reject/{inventory_no}','Backend\MaterialSmallStoreInventoryController@rejectInventory')->name('admin.material-small-store-inventory.reject');
    Route::put('Egotek/material-small-store-inventory/reset/{inventory_no}','Backend\MaterialSmallStoreInventoryController@resetInventory')->name('admin.material-small-store-inventory.reset');

    //raw-material-requisitions routes
    Route::get('Egotek/raw-material-requisitions/index', 'Backend\RawMaterialRequisitionController@index')->name('admin.raw-material-requisitions.index');
    Route::get('Egotek/raw-material-requisitions/choose', 'Backend\RawMaterialRequisitionController@choose')->name('admin.raw-material-requisitions.choose');
    Route::get('Egotek/raw-material-requisitions/create', 'Backend\RawMaterialRequisitionController@create')->name('admin.raw-material-requisitions.create');
    Route::get('Egotek/raw-material-requisitions/createFromBig', 'Backend\RawMaterialRequisitionController@createFromBig')->name('admin.raw-material-requisitions.createFromBig');
    Route::get('Egotek/raw-material-requisitions/create-from-plan/{plan_number}', 'Backend\RawMaterialRequisitionController@createFromPlan')->name('admin.raw-material-requisitions.create-from-plan');
    Route::post('Egotek/raw-material-requisitions/store', 'Backend\RawMaterialRequisitionController@store')->name('admin.raw-material-requisitions.store');
    Route::get('Egotek/raw-material-requisitions/edit/{requisition_no}', 'Backend\RawMaterialRequisitionController@edit')->name('admin.raw-material-requisitions.edit');
    Route::put('Egotek/raw-material-requisitions/update/{requisition_no}', 'Backend\RawMaterialRequisitionController@update')->name('admin.raw-material-requisitions.update');
    Route::delete('Egotek/raw-material-requisitions/destroy/{requisition_no}', 'Backend\RawMaterialRequisitionController@destroy')->name('admin.raw-material-requisitions.destroy');

    Route::get('Egotek/raw-material-requisitions/show/{requisition_no}', 'Backend\RawMaterialRequisitionController@show')->name('admin.raw-material-requisitions.show');

    Route::get('Egotek/raw-material-requisitions/generatepdf/{requisition_no}','Backend\RawMaterialRequisitionController@demande_requisition')->name('admin.raw-material-requisitions.generatepdf');
    Route::put('Egotek/raw-material-requisitions/validate/{requisition_no}', 'Backend\RawMaterialRequisitionController@validateRequisition')->name('admin.raw-material-requisitions.validate');
    Route::put('Egotek/raw-material-requisitions/reject/{requisition_no}','Backend\RawMaterialRequisitionController@reject')->name('admin.raw-material-requisitions.reject');
    Route::put('Egotek/raw-material-requisitions/reset/{requisition_no}','Backend\RawMaterialRequisitionController@reset')->name('admin.raw-material-requisitions.reset');
    Route::put('Egotek/raw-material-requisitions/confirm/{requisition_no}','Backend\RawMaterialRequisitionController@confirm')->name('admin.raw-material-requisitions.confirm');
    Route::put('Egotek/raw-material-requisitions/approuve/{requisition_no}','Backend\RawMaterialRequisitionController@approuve')->name('admin.raw-material-requisitions.approuve');
    Route::get('Egotek/raw-material-requisitions/export-to-excel', 'Backend\RawMaterialRequisitionController@exportToExcel')->name('admin.raw-material-requisitions.export-to-excel');

    //material-requisitions routes
    Route::get('Egotek/material-requisitions/index', 'Backend\MaterialRequisitionController@index')->name('admin.material-requisitions.index');
    Route::get('Egotek/material-requisitions/choose', 'Backend\MaterialRequisitionController@choose')->name('admin.material-requisitions.choose');
    Route::get('Egotek/material-requisitions/create', 'Backend\MaterialRequisitionController@create')->name('admin.material-requisitions.create');
    Route::get('Egotek/material-requisitions/createFromBig', 'Backend\MaterialRequisitionController@createFromBig')->name('admin.material-requisitions.createFromBig');
    Route::post('Egotek/material-requisitions/store', 'Backend\MaterialRequisitionController@store')->name('admin.material-requisitions.store');
    Route::get('Egotek/material-requisitions/edit/{requisition_no}', 'Backend\MaterialRequisitionController@edit')->name('admin.material-requisitions.edit');
    Route::put('Egotek/material-requisitions/update/{requisition_no}', 'Backend\MaterialRequisitionController@update')->name('admin.material-requisitions.update');
    Route::delete('Egotek/material-requisitions/destroy/{requisition_no}', 'Backend\MaterialRequisitionController@destroy')->name('admin.material-requisitions.destroy');

    Route::get('Egotek/material-requisitions/show/{requisition_no}', 'Backend\MaterialRequisitionController@show')->name('admin.material-requisitions.show');

    Route::get('Egotek/material-requisitions/generatepdf/{requisition_no}','Backend\MaterialRequisitionController@demande_requisition')->name('admin.material-requisitions.generatepdf');
    Route::put('Egotek/material-requisitions/validate/{requisition_no}', 'Backend\MaterialRequisitionController@validateRequisition')->name('admin.material-requisitions.validate');
    Route::put('Egotek/material-requisitions/reject/{requisition_no}','Backend\MaterialRequisitionController@reject')->name('admin.material-requisitions.reject');
    Route::put('Egotek/material-requisitions/reset/{requisition_no}','Backend\MaterialRequisitionController@reset')->name('admin.material-requisitions.reset');
    Route::put('Egotek/material-requisitions/confirm/{requisition_no}','Backend\MaterialRequisitionController@confirm')->name('admin.material-requisitions.confirm');
    Route::put('Egotek/material-requisitions/approuve/{requisition_no}','Backend\MaterialRequisitionController@approuve')->name('admin.material-requisitions.approuve');

    //food transfer routes
    Route::get('Egotek/raw-material-transfers/index', 'Backend\RawMaterialTransferController@index')->name('admin.raw-material-transfers.index');
    Route::get('Egotek/raw-material-transfers/export-to-excel', 'Backend\RawMaterialTransferController@exportToExcel')->name('admin.raw-material-transfers.export-to-excel');
    Route::get('Egotek/raw-material-transfers/create/{requisition_no}', 'Backend\RawMaterialTransferController@create')->name('admin.raw-material-transfers.create');
    Route::get('Egotek/raw-material-transfers/createFromBig/{requisition_no}', 'Backend\RawMaterialTransferController@createFromBig')->name('admin.raw-material-transfers.createFromBig');
    Route::post('Egotek/raw-material-transfers/store', 'Backend\RawMaterialTransferController@store')->name('admin.raw-material-transfers.store');
    Route::post('Egotek/raw-material-transfers/storeFromBig', 'Backend\RawMaterialTransferController@storeFromBig')->name('admin.raw-material-transfers.storeFromBig');
    Route::get('Egotek/raw-material-transfers/edit/{transfer_no}', 'Backend\RawMaterialTransferController@edit')->name('admin.raw-material-transfers.edit');
    Route::put('Egotek/raw-material-transfers/update/{transfer_no}', 'Backend\RawMaterialTransferController@update')->name('admin.raw-material-transfers.update');
    Route::delete('Egotek/raw-material-transfers/destroy/{transfer_no}', 'Backend\RawMaterialTransferController@destroy')->name('admin.raw-material-transfers.destroy');
    Route::get('Egotek/raw-material-transfers/show/{transfer_no}','Backend\RawMaterialTransferController@show')->name('admin.raw-material-transfers.show');

    Route::get('Egotek/raw-material-transfers/bonTransfert/{transfer_no}','Backend\RawMaterialTransferController@bonTransfert')->name('admin.raw-material-transfers.bonTransfert');
    Route::put('Egotek/raw-material-transfers/validate/{transfer_no}', 'Backend\RawMaterialTransferController@validateTransfer')->name('admin.raw-material-transfers.validate');
    Route::put('Egotek/raw-material-transfers/reject/{transfer_no}','Backend\RawMaterialTransferController@reject')->name('admin.raw-material-transfers.reject');
    Route::put('Egotek/raw-material-transfers/reset/{transfer_no}','Backend\RawMaterialTransferController@reset')->name('admin.raw-material-transfers.reset');
    Route::put('Egotek/raw-material-transfers/confirm/{transfer_no}','Backend\RawMaterialTransferController@confirm')->name('admin.raw-material-transfers.confirm');
    Route::put('Egotek/raw-material-transfers/approuve/{transfer_no}','Backend\RawMaterialTransferController@approuve')->name('admin.raw-material-transfers.approuve');
    Route::get('Egotek/raw-material-transfers/portion/{transfer_no}', 'Backend\RawMaterialTransferController@portion')->name('admin.raw-material-transfers.portion');
    Route::put('Egotek/raw-material-transfers/storePortion/{transfer_no}', 'Backend\RawMaterialTransferController@storePortion')->name('admin.raw-material-transfers.storePortion');
    Route::put('Egotek/raw-material-transfers/validatePortion/{transfer_no}', 'Backend\RawMaterialTransferController@validatePortion')->name('admin.raw-material-transfers.validatePortion');

    //material transfer routes
    Route::get('Egotek/material-transfers/index', 'Backend\MaterialTransferController@index')->name('admin.material-transfers.index');
    Route::get('Egotek/material-transfers/create/{requisition_no}', 'Backend\MaterialTransferController@create')->name('admin.material-transfers.create');
    Route::get('Egotek/material-transfers/createFromBig/{requisition_no}', 'Backend\MaterialTransferController@createFromBig')->name('admin.material-transfers.createFromBig');
    Route::post('Egotek/material-transfers/store', 'Backend\MaterialTransferController@store')->name('admin.material-transfers.store');
    Route::post('Egotek/material-transfers/storeFromBig', 'Backend\MaterialTransferController@storeFromBig')->name('admin.material-transfers.storeFromBig');
    Route::get('Egotek/material-transfers/edit/{transfer_no}', 'Backend\MaterialTransferController@edit')->name('admin.material-transfers.edit');
    Route::put('Egotek/material-transfers/update/{transfer_no}', 'Backend\MaterialTransferController@update')->name('admin.material-transfers.update');
    Route::delete('Egotek/material-transfers/destroy/{transfer_no}', 'Backend\MaterialTransferController@destroy')->name('admin.material-transfers.destroy');
    Route::get('Egotek/material-transfers/show/{transfer_no}','Backend\MaterialTransferController@show')->name('admin.material-transfers.show');

    Route::get('Egotek/material-transfers/bonTransfert/{transfer_no}','Backend\MaterialTransferController@bonTransfert')->name('admin.material-transfers.bonTransfert');
    Route::put('Egotek/material-transfers/validate/{transfer_no}', 'Backend\MaterialTransferController@validateTransfer')->name('admin.material-transfers.validate');
    Route::put('Egotek/material-transfers/reject/{transfer_no}','Backend\MaterialTransferController@reject')->name('admin.material-transfers.reject');
    Route::put('Egotek/material-transfers/reset/{transfer_no}','Backend\MaterialTransferController@reset')->name('admin.material-transfers.reset');
    Route::put('Egotek/material-transfers/confirm/{transfer_no}','Backend\MaterialTransferController@confirm')->name('admin.material-transfers.confirm');
    Route::put('Egotek/material-transfers/approuve/{transfer_no}','Backend\MaterialTransferController@approuve')->name('admin.material-transfers.approuve');

    //material purchases routes
    Route::get('Egotek/material-purchases/index', 'Backend\MaterialPurchaseController@index')->name('admin.material-purchases.index');
    Route::get('Egotek/material-purchases/create', 'Backend\MaterialPurchaseController@create')->name('admin.material-purchases.create');
    Route::post('Egotek/material-purchases/store', 'Backend\MaterialPurchaseController@store')->name('admin.material-purchases.store');
    Route::get('Egotek/material-purchases/edit/{purchase_no}', 'Backend\MaterialPurchaseController@edit')->name('admin.material-purchases.edit');
    Route::put('Egotek/material-purchases/update/{purchase_no}', 'Backend\MaterialPurchaseController@update')->name('admin.material-purchases.update');
    Route::delete('Egotek/material-purchases/destroy/{purchase_no}', 'Backend\MaterialPurchaseController@destroy')->name('admin.material-purchases.destroy');
    Route::get('Egotek/material-purchases/show/{purchase_no}','Backend\MaterialPurchaseController@show')->name('admin.material-purchases.show');
    Route::put('Egotek/material-purchases/update-supplier/{purchase_no}', 'Backend\MaterialPurchaseController@updateSupplier')->name('admin.material-purchases.update-supplier');

    Route::get('Egotek/material-purchases/materialPurchase/{purchase_no}','Backend\MaterialPurchaseController@materialPurchase')->name('admin.material-purchases.materialPurchase');
    Route::put('Egotek/material-purchases/validate/{purchase_no}', 'Backend\MaterialPurchaseController@validatePurchase')->name('admin.material-purchases.validate');
    Route::put('Egotek/material-purchases/reject/{purchase_no}','Backend\MaterialPurchaseController@reject')->name('admin.material-purchases.reject');
    Route::put('Egotek/material-purchases/reset/{purchase_no}','Backend\MaterialPurchaseController@reset')->name('admin.material-purchases.reset');
    Route::put('Egotek/material-purchases/confirm/{purchase_no}','Backend\MaterialPurchaseController@confirm')->name('admin.material-purchases.confirm');
    Route::put('Egotek/material-purchases/approuve/{purchase_no}','Backend\MaterialPurchaseController@approuve')->name('admin.material-purchases.approuve');

    Route::get('Egotek/material-purchases/export-to-excel','Backend\MaterialPurchaseController@exportToExcel')->name('admin.material-purchases.export-to-excel');

    //food purchases routes
    Route::get('Egotek/raw-material-purchases/index', 'Backend\RawMaterialPurchaseController@index')->name('admin.raw-material-purchases.index');
    Route::get('Egotek/raw-material-purchases/create', 'Backend\RawMaterialPurchaseController@create')->name('admin.raw-material-purchases.create');
    Route::post('Egotek/raw-material-purchases/store', 'Backend\RawMaterialPurchaseController@store')->name('admin.raw-material-purchases.store');
    Route::get('Egotek/raw-material-purchases/edit/{purchase_no}', 'Backend\RawMaterialPurchaseController@edit')->name('admin.raw-material-purchases.edit');
    Route::put('Egotek/raw-material-purchases/update/{purchase_no}', 'Backend\RawMaterialPurchaseController@update')->name('admin.raw-material-purchases.update');
    Route::delete('Egotek/raw-material-purchases/destroy/{purchase_no}', 'Backend\RawMaterialPurchaseController@destroy')->name('admin.raw-material-purchases.destroy');
    Route::get('Egotek/raw-material-purchases/show/{purchase_no}','Backend\RawMaterialPurchaseController@show')->name('admin.raw-material-purchases.show');
    Route::put('Egotek/raw-material-purchases/update-supplier/{purchase_no}', 'Backend\RawMaterialPurchaseController@updateSupplier')->name('admin.raw-material-purchases.update-supplier');

    Route::get('Egotek/raw-material-purchases/rawMaterialPurchase/{purchase_no}','Backend\RawMaterialPurchaseController@rawMaterialPurchase')->name('admin.raw-material-purchases.rawMaterialPurchase');
    Route::put('Egotek/raw-material-purchases/validate/{purchase_no}', 'Backend\RawMaterialPurchaseController@validatePurchase')->name('admin.raw-material-purchases.validate');
    Route::put('Egotek/raw-material-purchases/reject/{purchase_no}','Backend\RawMaterialPurchaseController@reject')->name('admin.raw-material-purchases.reject');
    Route::put('Egotek/raw-material-purchases/reset/{purchase_no}','Backend\RawMaterialPurchaseController@reset')->name('admin.raw-material-purchases.reset');
    Route::put('Egotek/raw-material-purchases/confirm/{purchase_no}','Backend\RawMaterialPurchaseController@confirm')->name('admin.raw-material-purchases.confirm');
    Route::put('Egotek/raw-material-purchases/approuve/{purchase_no}','Backend\RawMaterialPurchaseController@approuve')->name('admin.raw-material-purchases.approuve');
    Route::get('Egotek/raw-material-purchases/export-to-excel', 'Backend\RawMaterialPurchaseController@exportToExcel')->name('admin.raw-material-purchases.export-to-excel');

    //material supplier-orders routes
    Route::get('Egotek/material-supplier-orders/index', 'Backend\MaterialSupplierOrderController@index')->name('admin.material-supplier-orders.index');
    Route::get('Egotek/material-supplier-orders/create/{purchase_no}', 'Backend\MaterialSupplierOrderController@create')->name('admin.material-supplier-orders.create');
    Route::post('Egotek/material-supplier-orders/store', 'Backend\MaterialSupplierOrderController@store')->name('admin.material-supplier-orders.store');
    Route::get('Egotek/material-supplier-orders/edit/{order_no}', 'Backend\MaterialSupplierOrderController@edit')->name('admin.material-supplier-orders.edit');
    Route::put('Egotek/material-supplier-orders/update/{order_no}', 'Backend\MaterialSupplierOrderController@update')->name('admin.material-supplier-orders.update');
    Route::delete('Egotek/material-supplier-orders/destroy/{order_no}', 'Backend\MaterialSupplierOrderController@destroy')->name('admin.material-supplier-orders.destroy');
    Route::get('Egotek/material-supplier-orders/show/{order_no}','Backend\MaterialSupplierOrderController@show')->name('admin.material-supplier-orders.show');
    Route::put('Egotek/material-supplier-orders/update-supplier/{order_no}', 'Backend\MaterialSupplierOrderController@updateSupplier')->name('admin.material-supplier-orders.update-supplier');

    Route::get('Egotek/material-supplier-orders/materialSupplierOrder/{order_no}','Backend\MaterialSupplierOrderController@materialSupplierOrder')->name('admin.material-supplier-orders.materialSupplierOrder');
    Route::put('Egotek/material-supplier-orders/validate/{order_no}', 'Backend\MaterialSupplierOrderController@validateOrder')->name('admin.material-supplier-orders.validate');
    Route::put('Egotek/material-supplier-orders/reject/{order_no}','Backend\MaterialSupplierOrderController@reject')->name('admin.material-supplier-orders.reject');
    Route::put('Egotek/material-supplier-orders/reset/{order_no}','Backend\MaterialSupplierOrderController@reset')->name('admin.material-supplier-orders.reset');
    Route::put('Egotek/material-supplier-orders/confirm/{order_no}','Backend\MaterialSupplierOrderController@confirm')->name('admin.material-supplier-orders.confirm');
    Route::put('Egotek/material-supplier-orders/approuve/{order_no}','Backend\MaterialSupplierOrderController@approuve')->name('admin.material-supplier-orders.approuve');

    Route::get('Egotek/material-supplier-orders/export-to-excel','Backend\MaterialSupplierOrderController@exportToExcel')->name('admin.material-supplier-orders.export-to-excel');

    //food supplier-orders routes
    Route::get('Egotek/raw-material-supplier-orders/index', 'Backend\RawMaterialSupplierOrderController@index')->name('admin.raw-material-supplier-orders.index');
    Route::get('Egotek/raw-material-supplier-orders/create/{purchase_no}', 'Backend\RawMaterialSupplierOrderController@create')->name('admin.raw-material-supplier-orders.create');
    Route::post('Egotek/raw-material-supplier-orders/store', 'Backend\RawMaterialSupplierOrderController@store')->name('admin.raw-material-supplier-orders.store');
    Route::get('Egotek/raw-material-supplier-orders/edit/{order_no}', 'Backend\RawMaterialSupplierOrderController@edit')->name('admin.raw-material-supplier-orders.edit');
    Route::put('Egotek/raw-material-supplier-orders/update/{order_no}', 'Backend\RawMaterialSupplierOrderController@update')->name('admin.raw-material-supplier-orders.update');
    Route::delete('Egotek/raw-material-supplier-orders/destroy/{order_no}', 'Backend\RawMaterialSupplierOrderController@destroy')->name('admin.raw-material-supplier-orders.destroy');
    Route::get('Egotek/raw-material-supplier-orders/show/{order_no}','Backend\RawMaterialSupplierOrderController@show')->name('admin.raw-material-supplier-orders.show');
    Route::put('Egotek/raw-material-supplier-orders/update-supplier/{order_no}', 'Backend\RawMaterialSupplierOrderController@updateSupplier')->name('admin.raw-material-supplier-orders.update-supplier');

    Route::get('Egotek/raw-material-supplier-orders/rawMaterialSupplierOrder/{order_no}','Backend\RawMaterialSupplierOrderController@rawMaterialSupplierOrder')->name('admin.raw-material-supplier-orders.rawMaterialSupplierOrder');
    Route::put('Egotek/raw-material-supplier-orders/validate/{order_no}', 'Backend\RawMaterialSupplierOrderController@validateOrder')->name('admin.raw-material-supplier-orders.validate');
    Route::put('Egotek/raw-material-supplier-orders/reject/{order_no}','Backend\RawMaterialSupplierOrderController@reject')->name('admin.raw-material-supplier-orders.reject');
    Route::put('Egotek/raw-material-supplier-orders/reset/{order_no}','Backend\RawMaterialSupplierOrderController@reset')->name('admin.raw-material-supplier-orders.reset');
    Route::put('Egotek/raw-material-supplier-orders/confirm/{order_no}','Backend\RawMaterialSupplierOrderController@confirm')->name('admin.raw-material-supplier-orders.confirm');
    Route::put('Egotek/raw-material-supplier-orders/approuve/{order_no}','Backend\RawMaterialSupplierOrderController@approuve')->name('admin.raw-material-supplier-orders.approuve');
    Route::get('Egotek/raw-material-supplier-orders/export-to-excel', 'Backend\RawMaterialSupplierOrderController@exportToExcel')->name('admin.raw-material-supplier-orders.export-to-excel');

    //material receptions routes
    Route::get('Egotek/material-receptions/index', 'Backend\MaterialReceptionController@index')->name('admin.material-receptions.index');
    Route::get('Egotek/material-receptions/create/{order_no}', 'Backend\MaterialReceptionController@create')->name('admin.material-receptions.create');
    Route::get('Egotek/material-reception-without-remaining/create/{purchase_no}', 'Backend\MaterialReceptionController@createWithoutRemaining')->name('admin.material-reception-without-remaining.create');
    Route::post('Egotek/material-receptions/store', 'Backend\MaterialReceptionController@store')->name('admin.material-receptions.store');
    Route::post('Egotek/material-reception-without-remaining/store', 'Backend\MaterialReceptionController@storeWithoutRemaining')->name('admin.material-reception-without-remaining.store');
    Route::get('Egotek/material-reception/edit/{reception_no}', 'Backend\MaterialReceptionController@edit')->name('admin.material-reception.edit');
    Route::put('Egotek/material-reception/update/{reception_no}', 'Backend\MaterialReceptionController@update')->name('admin.material-reception.update');
    Route::delete('Egotek/material-receptions/destroy/{reception_no}', 'Backend\MaterialReceptionController@destroy')->name('admin.material-receptions.destroy');
    Route::get('Egotek/material-receptions/show/{reception_no}','Backend\MaterialReceptionController@show')->name('admin.material-receptions.show');
    Route::put('Egotek/material-receptions/update-supplier/{reception_no}', 'Backend\MaterialReceptionController@updateSupplier')->name('admin.material-receptions.update-supplier');

    Route::get('Egotek/material-receptions/fiche_reception/{reception_no}','Backend\MaterialReceptionController@fiche_reception')->name('admin.material-receptions.fiche_reception');
    Route::put('Egotek/material-receptions/validate/{reception_no}', 'Backend\MaterialReceptionController@validateReception')->name('admin.material-receptions.validate');
    Route::put('Egotek/material-receptions/reject/{reception_no}','Backend\MaterialReceptionController@reject')->name('admin.material-receptions.reject');
    Route::put('Egotek/material-receptions/reset/{reception_no}','Backend\MaterialReceptionController@reset')->name('admin.material-receptions.reset');
    Route::put('Egotek/material-receptions/confirm/{reception_no}','Backend\MaterialReceptionController@confirm')->name('admin.material-receptions.confirm');
    Route::put('Egotek/material-receptions/approuve/{reception_no}','Backend\MaterialReceptionController@approuve')->name('admin.material-receptions.approuve');

    Route::get('Egotek/material-receptions/export-to-excel','Backend\MaterialReceptionController@exportToExcel')->name('admin.material-receptions.export-to-excel');

    //food receptions routes
    Route::get('Egotek/raw-material-receptions/index', 'Backend\RawMaterialReceptionController@index')->name('admin.raw-material-receptions.index');
    Route::get('Egotek/raw-material-receptions/create/{order_no}', 'Backend\RawMaterialReceptionController@create')->name('admin.raw-material-receptions.create');
    Route::get('Egotek/raw-material-reception-without-remaining/create/{purchase_no}', 'Backend\RawMaterialReceptionController@createWithoutRemaining')->name('admin.raw-material-reception-without-remaining.create');
    Route::post('Egotek/raw-material-receptions/store', 'Backend\RawMaterialReceptionController@store')->name('admin.raw-material-receptions.store');
    Route::post('Egotek/raw-material-reception-without-remaining/store', 'Backend\RawMaterialReceptionController@storeWithoutRemaining')->name('admin.raw-material-reception-without-remaining.store');
    Route::get('Egotek/raw-material-reception/edit/{reception_no}', 'Backend\RawMaterialReceptionController@edit')->name('admin.raw-material-reception.edit');
    Route::put('Egotek/raw-material-reception/update/{reception_no}', 'Backend\RawMaterialReceptionController@update')->name('admin.raw-material-reception.update');
    Route::delete('Egotek/raw-material-receptions/destroy/{reception_no}', 'Backend\RawMaterialReceptionController@destroy')->name('admin.raw-material-receptions.destroy');
    Route::get('Egotek/raw-material-receptions/show/{reception_no}','Backend\RawMaterialReceptionController@show')->name('admin.raw-material-receptions.show');
    Route::put('Egotek/raw-material-receptions/update-supplier/{reception_no}', 'Backend\RawMaterialReceptionController@updateSupplier')->name('admin.raw-material-receptions.update-supplier');

    Route::get('Egotek/raw-material-receptions/fiche_reception/{reception_no}','Backend\RawMaterialReceptionController@fiche_reception')->name('admin.raw-material-receptions.fiche_reception');
    Route::put('Egotek/raw-material-receptions/validate/{reception_no}', 'Backend\RawMaterialReceptionController@validateReception')->name('admin.raw-material-receptions.validate');
    Route::put('Egotek/raw-material-receptions/reject/{reception_no}','Backend\RawMaterialReceptionController@reject')->name('admin.raw-material-receptions.reject');
    Route::put('Egotek/raw-material-receptions/reset/{reception_no}','Backend\RawMaterialReceptionController@reset')->name('admin.raw-material-receptions.reset');
    Route::put('Egotek/raw-material-receptions/confirm/{reception_no}','Backend\RawMaterialReceptionController@confirm')->name('admin.raw-material-receptions.confirm');
    Route::put('Egotek/raw-material-receptions/approuve/{reception_no}','Backend\RawMaterialReceptionController@approuve')->name('admin.raw-material-receptions.approuve');

    Route::get('Egotek/raw-material-receptions/export-to-excel','Backend\RawMaterialReceptionController@exportToExcel')->name('admin.raw-material-receptions.export-to-excel');

    //material stockins routes
    Route::get('Egotek/material-stockins/index', 'Backend\MaterialStockinController@index')->name('admin.material-stockins.index');
    Route::get('Egotek/material-stockins/choose', 'Backend\MaterialStockinController@choose')->name('admin.material-stockins.choose');
    Route::get('Egotek/material-stockins/create', 'Backend\MaterialStockinController@create')->name('admin.material-stockins.create');
    Route::get('Egotek/material-stockins/createFromBig', 'Backend\MaterialStockinController@createFromBig')->name('admin.material-stockins.createFromBig');
    Route::get('Egotek/material-stockins/createFromSmall', 'Backend\MaterialStockinController@createFromSmall')->name('admin.material-stockins.createFromSmall');
    Route::post('Egotek/material-stockins/store', 'Backend\MaterialStockinController@store')->name('admin.material-stockins.store');
    Route::post('Egotek/material-stockins/storeFromBig', 'Backend\MaterialStockinController@storeFromBig')->name('admin.material-stockins.storeFromBig');
    Route::post('Egotek/material-stockins/storeFromSmall', 'Backend\MaterialStockinController@storeFromSmall')->name('admin.material-stockins.storeFromSmall');
    Route::get('Egotek/material-stockins/edit/{stockin_no}', 'Backend\MaterialStockinController@edit')->name('admin.material-stockins.edit');
    Route::put('Egotek/material-stockins/update/{stockin_no}', 'Backend\MaterialStockinController@update')->name('admin.material-stockins.update');
    Route::delete('Egotek/material-stockins/destroy/{stockin_no}', 'Backend\MaterialStockinController@destroy')->name('admin.material-stockins.destroy');
    Route::get('Egotek/material-stockins/show/{stockin_no}','Backend\MaterialStockinController@show')->name('admin.material-stockins.show');

    Route::get('Egotek/material-stockins/bonEntree/{stockin_no}','Backend\MaterialStockinController@bonEntree')->name('admin.material-stockins.bonEntree');
    Route::put('Egotek/material-stockins/validate/{stockin_no}', 'Backend\MaterialStockinController@validateStockin')->name('admin.material-stockins.validate');
    Route::put('Egotek/material-stockins/reject/{stockin_no}','Backend\MaterialStockinController@reject')->name('admin.material-stockins.reject');
    Route::put('Egotek/material-stockins/reset/{stockin_no}','Backend\MaterialStockinController@reset')->name('admin.material-stockins.reset');
    Route::put('Egotek/material-stockins/confirm/{stockin_no}','Backend\MaterialStockinController@confirm')->name('admin.material-stockins.confirm');
    Route::put('Egotek/material-stockins/approuve/{stockin_no}','Backend\MaterialStockinController@approuve')->name('admin.material-stockins.approuve');

    //food stockins routes
    Route::get('Egotek/raw-material-stockins/index', 'Backend\RawMaterialStockinController@index')->name('admin.raw-material-stockins.index');
    Route::get('Egotek/raw-material-stockins/export-to-excel', 'Backend\RawMaterialStockinController@exportToExcel')->name('admin.raw-material-stockins.export-to-excel');
    Route::get('Egotek/raw-material-stockins/create', 'Backend\RawMaterialStockinController@create')->name('admin.raw-material-stockins.create');
    Route::post('Egotek/raw-material-stockins/store', 'Backend\RawMaterialStockinController@store')->name('admin.raw-material-stockins.store');
    Route::get('Egotek/raw-material-stockins/edit/{stockin_no}', 'Backend\RawMaterialStockinController@edit')->name('admin.raw-material-stockins.edit');
    Route::put('Egotek/raw-material-stockins/update/{stockin_no}', 'Backend\RawMaterialStockinController@update')->name('admin.raw-material-stockins.update');
    Route::delete('Egotek/raw-material-stockins/destroy/{stockin_no}', 'Backend\RawMaterialStockinController@destroy')->name('admin.raw-material-stockins.destroy');
    Route::get('Egotek/raw-material-stockins/show/{stockin_no}','Backend\RawMaterialStockinController@show')->name('admin.raw-material-stockins.show');

    Route::get('Egotek/raw-material-stockins/bonEntree/{stockin_no}','Backend\RawMaterialStockinController@bonEntree')->name('admin.raw-material-stockins.bonEntree');
    Route::put('Egotek/raw-material-stockins/validate/{stockin_no}', 'Backend\RawMaterialStockinController@validateStockin')->name('admin.raw-material-stockins.validate');
    Route::put('Egotek/raw-material-stockins/reject/{stockin_no}','Backend\RawMaterialStockinController@reject')->name('admin.raw-material-stockins.reject');
    Route::put('Egotek/raw-material-stockins/reset/{stockin_no}','Backend\RawMaterialStockinController@reset')->name('admin.raw-material-stockins.reset');
    Route::put('Egotek/raw-material-stockins/confirm/{stockin_no}','Backend\RawMaterialStockinController@confirm')->name('admin.raw-material-stockins.confirm');
    Route::put('Egotek/raw-material-stockins/approuve/{stockin_no}','Backend\RawMaterialStockinController@approuve')->name('admin.raw-material-stockins.approuve');

    //finished goods stockins routes
    Route::get('Egotek/finished-goods-stockins/index', 'Backend\FinishedGoodsStockinController@index')->name('admin.finished-goods-stockins.index');
    Route::get('Egotek/finished-goods-stockins/create', 'Backend\FinishedGoodsStockinController@create')->name('admin.finished-goods-stockins.create');
    Route::get('Egotek/finished-goods-stockins/items-search', 'Backend\FinishedGoodsStockinController@searchItems')->name('admin.finished-goods-stockins.items-search');
    Route::post('Egotek/finished-goods-stockins/store', 'Backend\FinishedGoodsStockinController@store')->name('admin.finished-goods-stockins.store');
    Route::get('Egotek/finished-goods-stockins/edit/{stockin_no}', 'Backend\FinishedGoodsStockinController@edit')->name('admin.finished-goods-stockins.edit');
    Route::put('Egotek/finished-goods-stockins/update/{stockin_no}', 'Backend\FinishedGoodsStockinController@update')->name('admin.finished-goods-stockins.update');
    Route::delete('Egotek/finished-goods-stockins/destroy/{stockin_no}', 'Backend\FinishedGoodsStockinController@destroy')->name('admin.finished-goods-stockins.destroy');
    Route::get('Egotek/finished-goods-stockins/show/{stockin_no}','Backend\FinishedGoodsStockinController@show')->name('admin.finished-goods-stockins.show');

    Route::put('Egotek/finished-goods-stockins/validate/{stockin_no}', 'Backend\FinishedGoodsStockinController@validateStockin')->name('admin.finished-goods-stockins.validate');
    Route::put('Egotek/finished-goods-stockins/reject/{stockin_no}','Backend\FinishedGoodsStockinController@reject')->name('admin.finished-goods-stockins.reject');
    Route::put('Egotek/finished-goods-stockins/reset/{stockin_no}','Backend\FinishedGoodsStockinController@reset')->name('admin.finished-goods-stockins.reset');
    Route::put('Egotek/finished-goods-stockins/confirm/{stockin_no}','Backend\FinishedGoodsStockinController@confirm')->name('admin.finished-goods-stockins.confirm');
    Route::put('Egotek/finished-goods-stockins/approuve/{stockin_no}','Backend\FinishedGoodsStockinController@approuve')->name('admin.finished-goods-stockins.approuve');

    //material stockouts routes
    Route::get('Egotek/material-stockouts/index', 'Backend\MaterialStockoutController@index')->name('admin.material-stockouts.index');
    Route::get('Egotek/material-stockouts/choose', 'Backend\MaterialStockoutController@choose')->name('admin.material-stockouts.choose');
    Route::get('Egotek/material-stockouts/create', 'Backend\MaterialStockoutController@create')->name('admin.material-stockouts.create');
    Route::get('Egotek/material-stockouts/createFromBig', 'Backend\MaterialStockoutController@createFromBig')->name('admin.material-stockouts.createFromBig');
    Route::get('Egotek/material-stockouts/createFromSmall', 'Backend\MaterialStockoutController@createFromSmall')->name('admin.material-stockouts.createFromSmall');
    Route::post('Egotek/material-stockouts/store', 'Backend\MaterialStockoutController@store')->name('admin.material-stockouts.store');
    Route::post('Egotek/material-stockouts/storeFromBig', 'Backend\MaterialStockoutController@storeFromBig')->name('admin.material-stockouts.storeFromBig');
    Route::post('Egotek/material-stockouts/storeFromSmall', 'Backend\MaterialStockoutController@storeFromSmall')->name('admin.material-stockouts.storeFromSmall');
    Route::get('Egotek/material-stockouts/edit/{stockout_no}', 'Backend\MaterialStockoutController@edit')->name('admin.material-stockouts.edit');
    Route::put('Egotek/material-stockouts/update/{stockout_no}', 'Backend\MaterialStockoutController@update')->name('admin.material-stockouts.update');
    Route::delete('Egotek/material-stockouts/destroy/{stockout_no}', 'Backend\MaterialStockoutController@destroy')->name('admin.material-stockouts.destroy');
    Route::get('Egotek/material-stockouts/show/{stockout_no}','Backend\MaterialStockoutController@show')->name('admin.material-stockouts.show');

    Route::get('Egotek/material-stockouts/bonSortie/{stockout_no}','Backend\MaterialStockoutController@bonSortie')->name('admin.material-stockouts.bonSortie');
    Route::put('Egotek/material-stockouts/validate/{stockout_no}', 'Backend\MaterialStockoutController@validateStockout')->name('admin.material-stockouts.validate');
    Route::put('Egotek/material-stockouts/reject/{stockout_no}','Backend\MaterialStockoutController@reject')->name('admin.material-stockouts.reject');
    Route::put('Egotek/material-stockouts/reset/{stockout_no}','Backend\MaterialStockoutController@reset')->name('admin.material-stockouts.reset');
    Route::put('Egotek/material-stockouts/confirm/{stockout_no}','Backend\MaterialStockoutController@confirm')->name('admin.material-stockouts.confirm');
    Route::put('Egotek/material-stockouts/approuve/{stockout_no}','Backend\MaterialStockoutController@approuve')->name('admin.material-stockouts.approuve');

    //food stockouts routes
    Route::get('Egotek/raw-material-stockouts/index', 'Backend\RawMaterialStockoutController@index')->name('admin.raw-material-stockouts.index');
    Route::get('Egotek/raw-material-stockouts/export-to-excel', 'Backend\RawMaterialStockoutController@exportToExcel')->name('admin.raw-material-stockouts.export-to-excel');
    Route::get('Egotek/raw-material-stockouts/create', 'Backend\RawMaterialStockoutController@create')->name('admin.raw-material-stockouts.create');
    Route::post('Egotek/raw-material-stockouts/store', 'Backend\RawMaterialStockoutController@store')->name('admin.raw-material-stockouts.store');
    Route::get('Egotek/raw-material-stockouts/edit/{stockout_no}', 'Backend\RawMaterialStockoutController@edit')->name('admin.raw-material-stockouts.edit');
    Route::put('Egotek/raw-material-stockouts/update/{stockout_no}', 'Backend\RawMaterialStockoutController@update')->name('admin.raw-material-stockouts.update');
    Route::delete('Egotek/raw-material-stockouts/destroy/{stockout_no}', 'Backend\RawMaterialStockoutController@destroy')->name('admin.raw-material-stockouts.destroy');
    Route::get('Egotek/raw-material-stockouts/show/{stockout_no}','Backend\RawMaterialStockoutController@show')->name('admin.raw-material-stockouts.show');

    Route::get('Egotek/raw-material-stockouts/bonSortie/{stockout_no}','Backend\RawMaterialStockoutController@bonSortie')->name('admin.raw-material-stockouts.bonSortie');
    Route::put('Egotek/raw-material-stockouts/validate/{stockout_no}', 'Backend\RawMaterialStockoutController@validateStockout')->name('admin.raw-material-stockouts.validate');
    Route::put('Egotek/raw-material-stockouts/reject/{stockout_no}','Backend\RawMaterialStockoutController@reject')->name('admin.raw-material-stockouts.reject');
    Route::put('Egotek/raw-material-stockouts/reset/{stockout_no}','Backend\RawMaterialStockoutController@reset')->name('admin.raw-material-stockouts.reset');
    Route::put('Egotek/raw-material-stockouts/confirm/{stockout_no}','Backend\RawMaterialStockoutController@confirm')->name('admin.raw-material-stockouts.confirm');
    Route::put('Egotek/raw-material-stockouts/approuve/{stockout_no}','Backend\RawMaterialStockoutController@approuve')->name('admin.raw-material-stockouts.approuve');

    Route::get('Egotek/raw-material-stockouts/export-to-pdf', 'Backend\RawMaterialStockoutController@exportTopdf')->name('admin.raw-material-stockouts.export-to-pdf');


    //finished-goods-stockins routes
    Route::get('Egotek/finished-goods-stockins/index', 'Backend\FinishedGoodsStockinController@index')->name('admin.finished-goods-stockins.index');

    //finished goods stockouts routes
    Route::get('Egotek/finished-goods-stockouts/index', 'Backend\FinishedGoodsStockoutController@index')->name('admin.finished-goods-stockouts.index');
    Route::get('Egotek/finished-goods-stockouts/create', 'Backend\FinishedGoodsStockoutController@create')->name('admin.finished-goods-stockouts.create');
    Route::get('Egotek/finished-goods-stockouts/items-search', 'Backend\FinishedGoodsStockoutController@searchItems')->name('admin.finished-goods-stockouts.items-search');
    Route::post('Egotek/finished-goods-stockouts/store', 'Backend\FinishedGoodsStockoutController@store')->name('admin.finished-goods-stockouts.store');
    Route::get('Egotek/finished-goods-stockouts/edit/{stockout_no}', 'Backend\FinishedGoodsStockoutController@edit')->name('admin.finished-goods-stockouts.edit');
    Route::put('Egotek/finished-goods-stockouts/update/{stockout_no}', 'Backend\FinishedGoodsStockoutController@update')->name('admin.finished-goods-stockouts.update');
    Route::delete('Egotek/finished-goods-stockouts/destroy/{stockout_no}', 'Backend\FinishedGoodsStockoutController@destroy')->name('admin.finished-goods-stockouts.destroy');
    Route::get('Egotek/finished-goods-stockouts/show/{stockout_no}','Backend\FinishedGoodsStockoutController@show')->name('admin.finished-goods-stockouts.show');

    Route::put('Egotek/finished-goods-stockouts/validate/{stockout_no}', 'Backend\FinishedGoodsStockoutController@validateStockout')->name('admin.finished-goods-stockouts.validate');
    Route::put('Egotek/finished-goods-stockouts/reject/{stockout_no}','Backend\FinishedGoodsStockoutController@reject')->name('admin.finished-goods-stockouts.reject');
    Route::put('Egotek/finished-goods-stockouts/reset/{stockout_no}','Backend\FinishedGoodsStockoutController@reset')->name('admin.finished-goods-stockouts.reset');
    Route::put('Egotek/finished-goods-stockouts/confirm/{stockout_no}','Backend\FinishedGoodsStockoutController@confirm')->name('admin.finished-goods-stockouts.confirm');
    Route::put('Egotek/finished-goods-stockouts/approuve/{stockout_no}','Backend\FinishedGoodsStockoutController@approuve')->name('admin.finished-goods-stockouts.approuve');

    // Impression par numéro de document (PDF)
    Route::get('Egotek/finished-goods-store-inventory/print/{inventory_no}', 'Backend\FinishedGoodsStoreInventoryController@print')->name('admin.finished-goods-store-inventory.print');
    Route::get('Egotek/finished-goods-stockins/print/{stockin_no}', 'Backend\FinishedGoodsStockinController@print')->name('admin.finished-goods-stockins.print');
    Route::get('Egotek/finished-goods-stockouts/print/{stockout_no}', 'Backend\FinishedGoodsStockoutController@print')->name('admin.finished-goods-stockouts.print');
    Route::get('Egotek/finished-goods-store/print/{code}', 'Backend\FinishedGoodsStoreController@print')->name('admin.finished-goods-store.print');
    Route::get('Egotek/raw-material-extra-big-store-inventory/print/{inventory_no}', 'Backend\RawMaterialExtraBigStoreInventoryController@print')->name('admin.raw-material-extra-big-store-inventory.print');
    Route::get('Egotek/raw-material-big-store-inventory/print/{inventory_no}', 'Backend\RawMaterialBigStoreInventoryController@print')->name('admin.raw-material-big-store-inventory.print');
    Route::get('Egotek/raw-material-small-store-inventory/print/{inventory_no}', 'Backend\RawMaterialSmallStoreInventoryController@print')->name('admin.raw-material-small-store-inventory.print');
    Route::get('Egotek/material-extra-big-store-inventory/print/{inventory_no}', 'Backend\MaterialExtraBigStoreInventoryController@print')->name('admin.material-extra-big-store-inventory.print');
    Route::get('Egotek/material-big-store-inventory/print/{inventory_no}', 'Backend\MaterialBigStoreInventoryController@print')->name('admin.material-big-store-inventory.print');
    Route::get('Egotek/material-small-store-inventory/print/{inventory_no}', 'Backend\MaterialSmallStoreInventoryController@print')->name('admin.material-small-store-inventory.print');
    Route::get('Egotek/raw-material-requisitions/print/{requisition_no}', 'Backend\RawMaterialRequisitionController@print')->name('admin.raw-material-requisitions.print');
    Route::get('Egotek/material-requisitions/print/{requisition_no}', 'Backend\MaterialRequisitionController@print')->name('admin.material-requisitions.print');
    Route::get('Egotek/raw-material-transfers/print/{transfer_no}', 'Backend\RawMaterialTransferController@print')->name('admin.raw-material-transfers.print');
    Route::get('Egotek/material-transfers/print/{transfer_no}', 'Backend\MaterialTransferController@print')->name('admin.material-transfers.print');
    Route::get('Egotek/raw-material-purchases/print/{purchase_no}', 'Backend\RawMaterialPurchaseController@print')->name('admin.raw-material-purchases.print');
    Route::get('Egotek/material-purchases/print/{purchase_no}', 'Backend\MaterialPurchaseController@print')->name('admin.material-purchases.print');
    Route::get('Egotek/raw-material-supplier-orders/print/{order_no}', 'Backend\RawMaterialSupplierOrderController@print')->name('admin.raw-material-supplier-orders.print');
    Route::get('Egotek/material-supplier-orders/print/{order_no}', 'Backend\MaterialSupplierOrderController@print')->name('admin.material-supplier-orders.print');
    Route::get('Egotek/raw-material-receptions/print/{reception_no}', 'Backend\RawMaterialReceptionController@print')->name('admin.raw-material-receptions.print');
    Route::get('Egotek/material-receptions/print/{reception_no}', 'Backend\MaterialReceptionController@print')->name('admin.material-receptions.print');
    Route::get('Egotek/raw-material-stockins/print/{stockin_no}', 'Backend\RawMaterialStockinController@print')->name('admin.raw-material-stockins.print');
    Route::get('Egotek/material-stockins/print/{stockin_no}', 'Backend\MaterialStockinController@print')->name('admin.material-stockins.print');
    Route::get('Egotek/raw-material-stockouts/print/{stockout_no}', 'Backend\RawMaterialStockoutController@print')->name('admin.raw-material-stockouts.print');
    Route::get('Egotek/material-stockouts/print/{stockout_no}', 'Backend\MaterialStockoutController@print')->name('admin.material-stockouts.print');
    Route::get('Egotek/production-order/print/{production_no}', 'Backend\ProductionOrderController@print')->name('admin.production-order.print');
    Route::get('Egotek/raw-material-extra-big-store-report/print', 'Backend\RawMaterialExtraBigReportController@print')->name('admin.raw-material-extra-big-store-report.print');
    Route::get('Egotek/raw-material-big-store-report/print', 'Backend\RawMaterialBigReportController@print')->name('admin.raw-material-big-store-report.print');
    Route::get('Egotek/raw-material-small-store-report/print', 'Backend\RawMaterialSmallReportController@print')->name('admin.raw-material-small-store-report.print');
    Route::get('Egotek/material-extra-big-store-report/print', 'Backend\MaterialExtraBigReportController@print')->name('admin.material-extra-big-store-report.print');
    Route::get('Egotek/material-big-store-report/print', 'Backend\MaterialBigReportController@print')->name('admin.material-big-store-report.print');
    Route::get('Egotek/material-small-store-report/print', 'Backend\MaterialSmallReportController@print')->name('admin.material-small-store-report.print');
    Route::get('Egotek/raw-material-extra-big-store/print/{code}', 'Backend\RawMaterialExtraBigStoreController@print')->name('admin.raw-material-extra-big-store.print');
    Route::get('Egotek/raw-material-big-store/print/{code}', 'Backend\RawMaterialBigStoreController@print')->name('admin.raw-material-big-store.print');
    Route::get('Egotek/raw-material-small-store/print/{code}', 'Backend\RawMaterialSmallStoreController@print')->name('admin.raw-material-small-store.print');
    Route::get('Egotek/material-extra-big-store/print/{code}', 'Backend\MaterialExtraBigStoreController@print')->name('admin.material-extra-big-store.print');
    Route::get('Egotek/material-big-store/print/{code}', 'Backend\MaterialBigStoreController@print')->name('admin.material-big-store.print');
    Route::get('Egotek/material-small-store/print/{code}', 'Backend\MaterialSmallStoreController@print')->name('admin.material-small-store.print');

    //food extra big store report routes
    Route::get('Egotek/raw-material-extra-big-store-report/index','Backend\RawMaterialExtraBigReportController@index')->name('admin.raw-material-extra-big-store-report.index');
    Route::get('Egotek/raw-material-extra-big-store-report/export-to-pdf','Backend\RawMaterialExtraBigReportController@exportToPdf')->name('admin.raw-material-extra-big-store-report.export-to-pdf');
    Route::get('Egotek/raw-material-extra-big-store-report/export-to-excel','Backend\RawMaterialExtraBigReportController@exportToExcel')->name('admin.raw-material-extra-big-store-report.export-to-excel');
    //food big store report routes
    Route::get('Egotek/raw-material-big-store-report/index','Backend\RawMaterialBigReportController@index')->name('admin.raw-material-big-store-report.index');
    Route::get('Egotek/raw-material-big-store-report/export-to-pdf','Backend\RawMaterialBigReportController@exportToPdf')->name('admin.raw-material-big-store-report.export-to-pdf');
    Route::get('Egotek/raw-material-big-store-report/export-to-excel','Backend\RawMaterialBigReportController@exportToExcel')->name('admin.raw-material-big-store-report.export-to-excel');

   //finished goods report
   Route::get('Egotek/finished-good-store-report/index', 'Backend\FinishedGoodReportController@index')->name('admin.finished-good-store-report.index');
   Route::get('Egotek/finished-good-store-report/export-to-excel', 'Backend\FinishedGoodReportController@exportToExcel')->name('admin.finished-good-store-report.export-to-excel');
   Route::get('Egotek/finished-good-store-report/export-to-pdf', 'Backend\FinishedGoodReportController@exportToPdf')->name('admin.finished-good-store-report.export-to-pdf');

   //finished goods SM report
   Route::get('Egotek/finished-good-sm-store-report/index', 'Backend\FinishedGoodsSmStoreReportController@index')->name('admin.finished-good-sm-store-report.index');
   Route::get('Egotek/finished-good-sm-store-report/export-to-excel', 'Backend\FinishedGoodsSmStoreReportController@exportToExcel')->name('admin.finished-good-sm-store-report.export-to-excel');
   Route::get('Egotek/finished-good-sm-store-report/export-to-pdf', 'Backend\FinishedGoodsSmStoreReportController@exportToPdf')->name('admin.finished-good-sm-store-report.export-to-pdf');

    //raw material small store report routes
    Route::get('Egotek/raw-material-small-store-report/index','Backend\RawMaterialSmallReportController@index')->name('admin.raw-material-small-store-report.index');
    Route::get('Egotek/raw-material-small-store-report/export-to-pdf','Backend\RawMaterialSmallReportController@exportToPdf')->name('admin.raw-material-small-store-report.export-to-pdf');
    Route::get('Egotek/raw-material-small-store-report/export-to-excel','Backend\RawMaterialSmallReportController@exportToExcel')->name('admin.raw-material-small-store-report.export-to-excel');


    //material extra big store report routes
    Route::get('Egotek/material-extra-big-store-report/index','Backend\MaterialExtraBigReportController@index')->name('admin.material-extra-big-store-report.index');
    Route::get('Egotek/material-extra-big-store-report/export-to-pdf','Backend\MaterialExtraBigReportController@exportToPdf')->name('admin.material-extra-big-store-report.export-to-pdf');
    Route::get('Egotek/material-extra-big-store-report/export-to-excel','Backend\MaterialExtraBigReportController@exportToExcel')->name('admin.material-extra-big-store-report.export-to-excel');
    //material big store report routes
    Route::get('Egotek/material-big-store-report/index','Backend\MaterialBigReportController@index')->name('admin.material-big-store-report.index');
    Route::get('Egotek/material-big-store-report/export-to-pdf','Backend\MaterialBigReportController@exportToPdf')->name('admin.material-big-store-report.export-to-pdf');
    Route::get('Egotek/material-big-store-report/export-to-excel','Backend\MaterialBigReportController@exportToExcel')->name('admin.material-big-store-report.export-to-excel');

    //material small store report routes
    Route::get('Egotek/material-small-store-report/index','Backend\MaterialSmallReportController@index')->name('admin.material-small-store-report.index');
    Route::get('Egotek/material-small-store-report/export-to-pdf','Backend\MaterialSmallReportController@exportToPdf')->name('admin.material-small-store-report.export-to-pdf');
    Route::get('Egotek/material-small-store-report/export-to-excel','Backend\MaterialSmallReportController@exportToExcel')->name('admin.material-small-store-report.export-to-excel');

    //settings routes
    Route::get('Egotek/settings/index', 'Backend\SettingController@index')->name('admin.settings.index');
    Route::get('Egotek/settings/create', 'Backend\SettingController@create')->name('admin.settings.create');
    Route::post('Egotek/settings/store', 'Backend\SettingController@store')->name('admin.settings.store');
    Route::get('Egotek/settings/edit/{id}', 'Backend\SettingController@edit')->name('admin.settings.edit');
    Route::put('Egotek/settings/update/{id}', 'Backend\SettingController@update')->name('admin.settings.update');
    Route::delete('Egotek/settings/destroy/{id}', 'Backend\SettingController@destroy')->name('admin.settings.destroy');
    Route::post('Egotek/settings/backup-database', 'Backend\SettingController@backupDatabase')->name('admin.settings.backup-database');

    Route::get('Egotek/supplier/export','Backend\SupplierController@get_supplier_data')->name('admin.supplier.export');

     //hr routes
    //time table routes
    Route::get('hr-time-tables/index', 'Backend\Hr\TimeTableController@index')->name('admin.hr-time-tables.index');
    Route::get('hr-time-tables/create', 'Backend\Hr\TimeTableController@create')->name('admin.hr-time-tables.create');
    Route::post('hr-time-tables/store', 'Backend\Hr\TimeTableController@store')->name('admin.hr-time-tables.store');
    Route::get('hr-time-tables/edit/{id}', 'Backend\Hr\TimeTableController@edit')->name('admin.hr-time-tables.edit');
    Route::put('hr-time-tables/update/{id}', 'Backend\Hr\TimeTableController@update')->name('admin.hr-time-tables.update');
    Route::delete('hr-time-tables/destroy/{id}', 'Backend\Hr\TimeTableController@destroy')->name('admin.hr-time-tables.destroy');
    Route::get('hr-time-tables/print-departement', 'Backend\Hr\TimeTableController@printByDepartement')->name('admin.hr-time-tables.print-departement');
    Route::get('hr-time-tables/print-employe/{id}', 'Backend\Hr\TimeTableController@printByEmploye')->name('admin.hr-time-tables.print-employe');

    //hr-departements routes
    Route::get('hr-departements/index', 'Backend\Hr\DepartementController@index')->name('admin.hr-departements.index');
    Route::get('hr-departements/create', 'Backend\Hr\DepartementController@create')->name('admin.hr-departements.create');
    Route::post('hr-departements/store', 'Backend\Hr\DepartementController@store')->name('admin.hr-departements.store');
    Route::get('hr-departements/edit/{id}', 'Backend\Hr\DepartementController@edit')->name('admin.hr-departements.edit');
    Route::put('hr-departements/update/{id}', 'Backend\Hr\DepartementController@update')->name('admin.hr-departements.update');
    Route::delete('hr-departements/destroy/{id}', 'Backend\Hr\DepartementController@destroy')->name('admin.hr-departements.destroy');

    //hr-services routes
    Route::get('hr-services/index', 'Backend\Hr\ServiceController@index')->name('admin.hr-services.index');
    Route::get('hr-services/create', 'Backend\Hr\ServiceController@create')->name('admin.hr-services.create');
    Route::post('hr-services/store', 'Backend\Hr\ServiceController@store')->name('admin.hr-services.store');
    Route::get('hr-services/edit/{id}', 'Backend\Hr\ServiceController@edit')->name('admin.hr-services.edit');
    Route::put('hr-services/update/{id}', 'Backend\Hr\ServiceController@update')->name('admin.hr-services.update');
    Route::delete('hr-services/destroy/{id}', 'Backend\Hr\ServiceController@destroy')->name('admin.hr-services.destroy');

    //hr-fonctions routes
    Route::get('hr-fonctions/index', 'Backend\Hr\FonctionController@index')->name('admin.hr-fonctions.index');
    Route::get('hr-fonctions/create', 'Backend\Hr\FonctionController@create')->name('admin.hr-fonctions.create');
    Route::post('hr-fonctions/store', 'Backend\Hr\FonctionController@store')->name('admin.hr-fonctions.store');
    Route::get('hr-fonctions/edit/{id}', 'Backend\Hr\FonctionController@edit')->name('admin.hr-fonctions.edit');
    Route::put('hr-fonctions/update/{id}', 'Backend\Hr\FonctionController@update')->name('admin.hr-fonctions.update');
    Route::delete('hr-fonctions/destroy/{id}', 'Backend\Hr\FonctionController@destroy')->name('admin.hr-fonctions.destroy');

    //hr-banques routes
    Route::get('hr-banques/index', 'Backend\Hr\BanqueController@index')->name('admin.hr-banques.index');
    Route::get('hr-banques/create', 'Backend\Hr\BanqueController@create')->name('admin.hr-banques.create');
    Route::post('hr-banques/store', 'Backend\Hr\BanqueController@store')->name('admin.hr-banques.store');
    Route::get('hr-banques/edit/{id}', 'Backend\Hr\BanqueController@edit')->name('admin.hr-banques.edit');
    Route::put('hr-banques/update/{id}', 'Backend\Hr\BanqueController@update')->name('admin.hr-banques.update');
    Route::delete('hr-banques/destroy/{id}', 'Backend\Hr\BanqueController@destroy')->name('admin.hr-banques.destroy');

    //hr-grades routes
    Route::get('hr-grades/index', 'Backend\Hr\GradeController@index')->name('admin.hr-grades.index');
    Route::get('hr-grades/create', 'Backend\Hr\GradeController@create')->name('admin.hr-grades.create');
    Route::post('hr-grades/store', 'Backend\Hr\GradeController@store')->name('admin.hr-grades.store');
    Route::get('hr-grades/edit/{id}', 'Backend\Hr\GradeController@edit')->name('admin.hr-grades.edit');
    Route::put('hr-grades/update/{id}', 'Backend\Hr\GradeController@update')->name('admin.hr-grades.update');
    Route::delete('hr-grades/destroy/{id}', 'Backend\Hr\GradeController@destroy')->name('admin.hr-grades.destroy');

    //hr-employes routes
    Route::get('hr-employes/index/{company_id}', 'Backend\Hr\EmployeController@index')->name('admin.hr-employes.index');
    Route::get('hr-employes/create', 'Backend\Hr\EmployeController@create')->name('admin.hr-employes.create');
    Route::get('hr-employes/show/{id}', 'Backend\Hr\EmployeController@show')->name('admin.hr-employes.show');
    Route::post('hr-employes/store', 'Backend\Hr\EmployeController@store')->name('admin.hr-employes.store');
    Route::get('hr-employes/edit/{id}', 'Backend\Hr\EmployeController@edit')->name('admin.hr-employes.edit');
    Route::put('hr-employes/update/{id}', 'Backend\Hr\EmployeController@update')->name('admin.hr-employes.update');
    Route::delete('hr-employes/destroy/{id}', 'Backend\Hr\EmployeController@destroy')->name('admin.hr-employes.destroy');

    Route::post('hr-employes/replace-matricule-prefix/{company_id}', 'Backend\Hr\EmployeController@replaceMatriculePrefix')->name('admin.hr-employes.replace-matricule-prefix');
    Route::get('hr-employes/exportToExcel/{company_id}', 'Backend\Hr\EmployeController@exportToExcel')->name('admin.hr-employes.exportToExcel');
    Route::get('hr-employes/search-accounts', 'Backend\Hr\EmployeController@searchAccounts')->name('admin.hr-employes.search-accounts');
    Route::get('hr-employes/{id}/image/{filename}', 'Backend\Hr\EmployeController@serveImage')->name('admin.hr-employes.image');
    Route::get('hr-employes/{id}/document/{filename}', 'Backend\Hr\EmployeController@serveDocument')->name('admin.hr-employes.document');
    Route::get('hr-employes/{id}/badge', 'Backend\Hr\EmployeController@badge')->name('admin.hr-employes.badge');

    //hr-stagiaires routes
    Route::get('hr-stagiaires/index/{company_id}', 'Backend\Hr\StagiaireController@index')->name('admin.hr-stagiaires.index');
    Route::get('hr-stagiaire/select-by-company', 'Backend\Hr\StagiaireController@selectByCompany')->name('admin.stagiare-select-by-company');
    Route::get('hr-stagiaires/create', 'Backend\Hr\StagiaireController@create')->name('admin.hr-stagiaires.create');
    Route::get('hr-stagiaires/show/{id}', 'Backend\Hr\StagiaireController@show')->name('admin.hr-stagiaires.show');
    Route::post('hr-stagiaires/store', 'Backend\Hr\StagiaireController@store')->name('admin.hr-stagiaires.store');
    Route::get('hr-stagiaires/edit/{id}', 'Backend\Hr\StagiaireController@edit')->name('admin.hr-stagiaires.edit');
    Route::put('hr-stagiaires/update/{id}', 'Backend\Hr\StagiaireController@update')->name('admin.hr-stagiaires.update');
    Route::delete('hr-stagiaires/destroy/{id}', 'Backend\Hr\StagiaireController@destroy')->name('admin.hr-stagiaires.destroy');

    //hr-ecoles routes
    Route::get('hr-ecoles/index', 'Backend\Hr\EcoleController@index')->name('admin.hr-ecoles.index');
    Route::get('hr-ecoles/create', 'Backend\Hr\EcoleController@create')->name('admin.hr-ecoles.create');
    Route::post('hr-ecoles/store', 'Backend\Hr\EcoleController@store')->name('admin.hr-ecoles.store');
    Route::get('hr-ecoles/edit/{id}', 'Backend\Hr\EcoleController@edit')->name('admin.hr-ecoles.edit');
    Route::put('hr-ecoles/update/{id}', 'Backend\Hr\EcoleController@update')->name('admin.hr-ecoles.update');
    Route::delete('hr-ecoles/destroy/{id}', 'Backend\Hr\EcoleController@destroy')->name('admin.hr-ecoles.destroy');

    //hr-filieres routes
    Route::get('hr-filieres/index', 'Backend\Hr\FiliereController@index')->name('admin.hr-filieres.index');
    Route::get('hr-filieres/create', 'Backend\Hr\FiliereController@create')->name('admin.hr-filieres.create');
    Route::post('hr-filieres/store', 'Backend\Hr\FiliereController@store')->name('admin.hr-filieres.store');
    Route::get('hr-filieres/edit/{id}', 'Backend\Hr\FiliereController@edit')->name('admin.hr-filieres.edit');
    Route::put('hr-filieres/update/{id}', 'Backend\Hr\FiliereController@update')->name('admin.hr-filieres.update');
    Route::delete('hr-filieres/destroy/{id}', 'Backend\Hr\FiliereController@destroy')->name('admin.hr-filieres.destroy');

    //hr-type-absences routes
    Route::get('hr-absences/index', 'Backend\Hr\AbsenceController@index')->name('admin.hr-absences.index');
    Route::get('hr-first-in-last-out/index', 'Backend\Hr\AbsenceController@firstInLastOut')->name('admin.hr-first-in-last-out.index');

    // API helper endpoints
    Route::get('hr-absences/get-attendance', 'Backend\Hr\AbsenceController@getAttendance')->name('admin.hr-absences.get-attendance');
    Route::get('hr-absences/get-departments', 'Backend\Hr\AbsenceController@getDepartmentsApi')->name('admin.hr-absences.get-departments');
    Route::get('hr-absences/get-employees', 'Backend\Hr\AbsenceController@getEmployeesApi')->name('admin.hr-absences.get-employees');

    // Exports
    Route::get('hr-absences/export-excel', 'Backend\Hr\AbsenceController@exportIndexExcel')->name('admin.hr-absences.export-excel');
    Route::get('hr-first-in-last-out/export-excel', 'Backend\Hr\AbsenceController@exportFirstInLastOutExcel')->name('admin.hr-first-in-last-out.export-excel');


    //hr-conge-payes routes
    Route::get('hr-conge-payes/index', 'Backend\Hr\CongePayeController@index')->name('admin.hr-conge-payes.index');
    Route::get('hr-conge-payes/create', 'Backend\Hr\CongePayeController@create')->name('admin.hr-conge-payes.create');
    Route::post('hr-conge-payes/store', 'Backend\Hr\CongePayeController@store')->name('admin.hr-conge-payes.store');
    Route::get('hr-conge-payes/edit/{id}', 'Backend\Hr\CongePayeController@edit')->name('admin.hr-conge-payes.edit');
    Route::put('hr-conge-payes/update/{id}', 'Backend\Hr\CongePayeController@update')->name('admin.hr-conge-payes.update');
    Route::delete('hr-conge-payes/destroy/{id}', 'Backend\Hr\CongePayeController@destroy')->name('admin.hr-conge-payes.destroy');

    //hr-type-conges routes
    Route::get('hr-type-conges/index', 'Backend\Hr\TypeCongeController@index')->name('admin.hr-type-conges.index');
    Route::get('hr-type-conges/create', 'Backend\Hr\TypeCongeController@create')->name('admin.hr-type-conges.create');
    Route::post('hr-type-conges/store', 'Backend\Hr\TypeCongeController@store')->name('admin.hr-type-conges.store');
    Route::get('hr-type-conges/edit/{id}', 'Backend\Hr\TypeCongeController@edit')->name('admin.hr-type-conges.edit');
    Route::put('hr-type-conges/update/{id}', 'Backend\Hr\TypeCongeController@update')->name('admin.hr-type-conges.update');
    Route::delete('hr-type-conges/destroy/{id}', 'Backend\Hr\TypeCongeController@destroy')->name('admin.hr-type-conges.destroy');

    //hr-take-conges routes
    Route::get('hr-take-conges/index/{company_id}', 'Backend\Hr\TakeCongeController@index')->name('admin.hr-take-conges.index');
    Route::get('hr-leave-taken/select-by-company', 'Backend\Hr\TakeCongeController@selectByCompany')->name('admin.hr-leave-taken.select-by-company');
    Route::get('hr-take-conges/create/{company_id}', 'Backend\Hr\TakeCongeController@create')->name('admin.hr-take-conges.create');
    Route::post('hr-take-conges/store', 'Backend\Hr\TakeCongeController@store')->name('admin.hr-take-conges.store');
    Route::get('hr-take-conges/edit/{id}', 'Backend\Hr\TakeCongeController@edit')->name('admin.hr-take-conges.edit');
    Route::put('hr-take-conges/update/{id}', 'Backend\Hr\TakeCongeController@update')->name('admin.hr-take-conges.update');
    Route::delete('hr-take-conges/destroy/{id}', 'Backend\Hr\TakeCongeController@destroy')->name('admin.hr-take-conges.destroy');

    Route::get('hr-take-conges/billet-sortie/{id}', 'Backend\Hr\TakeCongeController@billetSortie')->name('admin.hr-take-conges.billetSortie');

    //hr-take-conge-payes routes
    Route::get('hr-take-conge-payes/index/{company_id}', 'Backend\Hr\TakeCongePayeController@index')->name('admin.hr-take-conge-payes.index');
    Route::get('hr-take-paid-leave/select-by-company', 'Backend\Hr\TakeCongePayeController@selectByCompany')->name('admin.hr-take-paid-leave.select-by-company');
    Route::get('hr-take-conge-payes/create/{company_id}', 'Backend\Hr\TakeCongePayeController@create')->name('admin.hr-take-conge-payes.create');
    Route::post('hr-take-conge-payes/store', 'Backend\Hr\TakeCongePayeController@store')->name('admin.hr-take-conge-payes.store');
    Route::get('hr-take-conge-payes/edit/{id}', 'Backend\Hr\TakeCongePayeController@edit')->name('admin.hr-take-conge-payes.edit');
    Route::put('hr-take-conge-payes/update/{id}', 'Backend\Hr\TakeCongePayeController@update')->name('admin.hr-take-conge-payes.update');
    Route::delete('hr-take-conge-payes/destroy/{id}', 'Backend\Hr\TakeCongePayeController@destroy')->name('admin.hr-take-conge-payes.destroy');

    Route::post('hr-take-conge-payes/fetch', 'Backend\Hr\TakeCongePayeController@fetch')->name('admin.hr-take-conge-payes.fetch');
    Route::post('hr-take-conge-payes/fetch-min-date-debut', 'Backend\Hr\TakeCongePayeController@fetchMinDateDebut')->name('admin.hr-take-conge-payes.fetch-min-date-debut');

    Route::get('hr-take-conge-payes/lettre-Demande-Conge/{id}', 'Backend\Hr\TakeCongePayeController@lettreDemandeConge')->name('admin.hr-take-conge-payes.lettreDemandeConge');
    Route::get('hr-take-conge-payes/export-excel/{company_id}', 'Backend\Hr\TakeCongePayeController@exportToExcel')->name('admin.hr-take-conge-payes.export-excel');
    Route::get('hr-take-conge-payes/export-pdf/{company_id}', 'Backend\Hr\TakeCongePayeController@exportToPdf')->name('admin.hr-take-conge-payes.export-pdf');

    Route::put('hr-take-conge-paye/valider/{id}', 'Backend\Hr\TakeCongePayeController@validerConge')->name('admin.hr-take-conge-paye.valider');
    Route::put('hr-take-conge-paye/confirmer/{id}', 'Backend\Hr\TakeCongePayeController@confirmerConge')->name('admin.hr-take-conge-paye.confirmer');
    Route::put('hr-take-conge-paye/approuver/{id}', 'Backend\Hr\TakeCongePayeController@approuverConge')->name('admin.hr-take-conge-paye.approuver');
    Route::put('hr-take-conge-paye/rejeter/{id}', 'Backend\Hr\TakeCongePayeController@rejeterConge')->name('admin.hr-take-conge-paye.rejeter');
    Route::put('hr-take-conge-paye/annuler/{id}', 'Backend\Hr\TakeCongePayeController@annulerConge')->name('admin.hr-take-conge-paye.annuler');

    //hr-loans routes
    Route::get('hr-loans/index', 'Backend\Hr\LoanController@index')->name('admin.hr-loans.index');
    Route::get('hr-loans/create', 'Backend\Hr\LoanController@create')->name('admin.hr-loans.create');
    Route::post('hr-loans/store', 'Backend\Hr\LoanController@store')->name('admin.hr-loans.store');
    Route::get('hr-loans/show/{id}', 'Backend\Hr\LoanController@show')->name('admin.hr-loans.show');
    Route::get('hr-loans/edit/{id}', 'Backend\Hr\LoanController@edit')->name('admin.hr-loans.edit');
    Route::get('hr-loans/printDocument/{id}', 'Backend\Hr\LoanController@loanDocument')->name('admin.hr-loans.printDocument');
    Route::put('hr-loans/update/{id}', 'Backend\Hr\LoanController@update')->name('admin.hr-loans.update');
    Route::delete('hr-loans/destroy/{id}', 'Backend\Hr\LoanController@destroy')->name('admin.hr-loans.destroy');
    Route::post('hr-loans/validate/{id}', 'Backend\Hr\LoanController@validateLoan')->name('admin.hr-loans.validate');
    Route::post('hr-loans/confirm/{id}', 'Backend\Hr\LoanController@confirm')->name('admin.hr-loans.confirm');
    Route::post('hr-loans/approve/{id}', 'Backend\Hr\LoanController@approve')->name('admin.hr-loans.approve');

    //hr-cotations routes
    Route::get('hr-cotations/index', 'Backend\Hr\CotationController@index')->name('admin.hr-cotations.index');
    Route::get('hr-cotations/create', 'Backend\Hr\CotationController@create')->name('admin.hr-cotations.create');
    Route::post('hr-cotations/store', 'Backend\Hr\CotationController@store')->name('admin.hr-cotations.store');
    Route::get('hr-cotations/edit/{id}', 'Backend\Hr\CotationController@edit')->name('admin.hr-cotations.edit');
    Route::put('hr-cotations/update/{id}', 'Backend\Hr\CotationController@update')->name('admin.hr-cotations.update');
    Route::delete('hr-cotations/destroy/{id}', 'Backend\Hr\CotationController@destroy')->name('admin.hr-cotations.destroy');

    //hr-paiements routes
    Route::get('hr-paiement/create-by-company', 'Backend\Hr\PaiementController@createByCompany')->name('admin.hr-paiement.createByCompany');
    Route::get('hr-paiement/select-by-company', 'Backend\Hr\PaiementController@selectByCompany')->name('admin.hr-paiement.selectByCompany');
    Route::get('hr-paiements/index/{company_id}', 'Backend\Hr\PaiementController@index')->name('admin.hr-paiements.index');
    Route::get('hr-paiements/create/{company_id}', 'Backend\Hr\PaiementController@create')->name('admin.hr-paiements.create');
    Route::post('hr-paiements/store', 'Backend\Hr\PaiementController@store')->name('admin.hr-paiements.store');
    Route::get('hr-paiements/edit/{id}/by-company/{company_id}', 'Backend\Hr\PaiementController@edit')->name('admin.hr-paiements.edit');
    Route::get('hr-paiements/show/{code}', 'Backend\Hr\PaiementController@show')->name('admin.hr-paiements.show');
    Route::put('hr-paiements/update/{id}', 'Backend\Hr\PaiementController@update')->name('admin.hr-paiements.update');
    Route::delete('hr-paiements/destroy/{id}', 'Backend\Hr\PaiementController@destroy')->name('admin.hr-paiements.destroy');

    Route::get('hr-fiche-paie/print/{code}', 'Backend\Hr\PaiementController@ficheSalaire')->name('admin.hr-fiche-paie.print');

    Route::post('hr-paiements/fetch', 'Backend\Hr\PaiementController@fetch')->name('admin.hr-paiements.fetch');
    Route::post('hr-paiements/fetch-loan', 'Backend\Hr\PaiementController@fetchLoan')->name('admin.hr-paiements.fetch-loan');

    //hr-note-interne routes
    Route::get('hr-note-interne/index', 'Backend\Hr\NoteInterneController@index')->name('admin.hr-note-interne.index');
    Route::get('hr-note-interne/create', 'Backend\Hr\NoteInterneController@create')->name('admin.hr-note-interne.create');
    Route::post('hr-note-interne/store', 'Backend\Hr\NoteInterneController@store')->name('admin.hr-note-interne.store');
    Route::get('hr-note-interne/edit/{id}', 'Backend\Hr\NoteInterneController@edit')->name('admin.hr-note-interne.edit');
    Route::put('hr-note-interne/update/{id}', 'Backend\Hr\NoteInterneController@update')->name('admin.hr-note-interne.update');
    Route::delete('hr-note-interne/destroy/{id}', 'Backend\Hr\NoteInterneController@destroy')->name('admin.hr-note-interne.destroy');

    //hr-reglages routes
    Route::get('hr-reglages/index', 'Backend\Hr\ReglageController@index')->name('admin.hr-reglages.index');
    Route::get('hr-reglages/create', 'Backend\Hr\ReglageController@create')->name('admin.hr-reglages.create');
    Route::post('hr-reglages/store', 'Backend\Hr\ReglageController@store')->name('admin.hr-reglages.store');
    Route::get('hr-reglages/edit/{id}', 'Backend\Hr\ReglageController@edit')->name('admin.hr-reglages.edit');
    Route::put('hr-reglages/update/{id}', 'Backend\Hr\ReglageController@update')->name('admin.hr-reglages.update');
    Route::delete('hr-reglages/destroy/{id}', 'Backend\Hr\ReglageController@destroy')->name('admin.hr-reglages.destroy');

    //hr-companies routes
    Route::get('hr-company/select', 'Backend\Hr\CompanyController@selectCompany')->name('admin.hr-company.select');
    Route::get('hr-companies/index', 'Backend\Hr\CompanyController@index')->name('admin.hr-companies.index');
    Route::get('hr-companies/create', 'Backend\Hr\CompanyController@create')->name('admin.hr-companies.create');
    Route::post('hr-companies/store', 'Backend\Hr\CompanyController@store')->name('admin.hr-companies.store');
    Route::get('hr-companies/edit/{id}', 'Backend\Hr\CompanyController@edit')->name('admin.hr-companies.edit');
    Route::put('hr-companies/update/{id}', 'Backend\Hr\CompanyController@update')->name('admin.hr-companies.update');
    Route::delete('hr-companies/destroy/{id}', 'Backend\Hr\CompanyController@destroy')->name('admin.hr-companies.destroy');

    //hr-journal-paies routes
    Route::get('hr-journal-paie/select-by-company/{code}', 'Backend\Hr\JournalPaieController@selectByCompany')->name('admin.hr-journal-paie.select-by-company');
    Route::get('hr-journal-paies/index', 'Backend\Hr\JournalPaieController@index')->name('admin.hr-journal-paies.index');
    Route::get('hr-journal-paies/export-to-excel', 'Backend\Hr\JournalPaieController@exportToExcel')->name('admin.hr-journal-paies.export-to-excel');
    Route::get('hr-journal-paies/export-to-pdf/{code?}/{company_id?}', 'Backend\Hr\JournalPaieController@exportToPdf')->name('admin.hr-journal-paies.export-to-pdf');
    Route::get('hr-journal-paies/create', 'Backend\Hr\JournalPaieController@create')->name('admin.hr-journal-paies.create');
    Route::post('hr-journal-paies/store', 'Backend\Hr\JournalPaieController@store')->name('admin.hr-journal-paies.store');
    Route::get('hr-journal-paies/edit/{id}', 'Backend\Hr\JournalPaieController@edit')->name('admin.hr-journal-paies.edit');
    Route::get('hr-journal-paies/show/{code}/{company_id}', 'Backend\Hr\JournalPaieController@show')->name('admin.hr-journal-paies.show');
    Route::put('hr-journal-paies/cloturer/{id}', 'Backend\Hr\JournalPaieController@cloturer')->name('admin.hr-journal-paies.cloturer');
    Route::put('hr-journal-paies/update/{id}', 'Backend\Hr\JournalPaieController@update')->name('admin.hr-journal-paies.update');
    Route::delete('hr-journal-paies/destroy/{id}', 'Backend\Hr\JournalPaieController@destroy')->name('admin.hr-journal-paies.destroy');

    //hr-journal-cotisations routes
    Route::get('hr-journal-cotisations/index/{company_id}', 'Backend\Hr\JournalCotisationController@index')->name('admin.hr-journal-cotisations.index');
    Route::get('hr-journal-cotisations/select-by-company', 'Backend\Hr\JournalCotisationController@selectByCompany')->name('admin.hr-journal-cotisations.select-by-company');
    Route::get('hr-journal-cotisations/export-to-excel', 'Backend\Hr\JournalCotisationController@exportToExcel')->name('admin.hr-journal-cotisations.export-to-excel');

    //hr-journal-impots routes
    Route::get('hr-journal-impots/index/{company_id}', 'Backend\Hr\JournalImpotController@index')->name('admin.hr-journal-impots.index');
    Route::get('hr-journal-impots/select-by-company', 'Backend\Hr\JournalImpotController@selectByCompany')->name('admin.hr-journal-impots.select-by-company');
    Route::get('hr-journal-impots/export-to-excel', 'Backend\Hr\JournalImpotController@exportToExcel')->name('admin.hr-journal-impots.export-to-excel');

    //hr-journal-conges routes
    Route::get('hr-journal-conges/index', 'Backend\Hr\JournalCongeController@index')->name('admin.hr-journal-conges.index');
    Route::get('hr-journal-conges/create', 'Backend\Hr\JournalCongeController@create')->name('admin.hr-journal-conges.create');
    Route::post('hr-journal-conges/store', 'Backend\Hr\JournalCongeController@store')->name('admin.hr-journal-conges.store');
    Route::get('hr-journal-conges/edit/{id}', 'Backend\Hr\JournalCongeController@edit')->name('admin.hr-journal-conges.edit');
    Route::put('hr-journal-conges/update/{id}', 'Backend\Hr\JournalCongeController@update')->name('admin.hr-journal-conges.update');
    Route::delete('hr-journal-conges/destroy/{id}', 'Backend\Hr\JournalCongeController@destroy')->name('admin.hr-journal-conges.destroy');

    Route::get('hr-choose-report/index', 'Backend\Hr\ReportController@choose')->name('admin.hr-choose-report.choose');


    //currencies routes
    Route::get('currencies/index', 'Backend\Accounting\AccountCurrencyController@index')->name('admin.currencies.index');
    Route::get('currencies/create', 'Backend\Accounting\AccountCurrencyController@create')->name('admin.currencies.create');
    Route::post('currencies/store', 'Backend\Accounting\AccountCurrencyController@store')->name('admin.currencies.store');
    Route::get('currencies/edit/{id}', 'Backend\Accounting\AccountCurrencyController@edit')->name('admin.currencies.edit');
    Route::put('currencies/update/{id}', 'Backend\Accounting\AccountCurrencyController@update')->name('admin.currencies.update');
    Route::delete('currencies/destroy/{id}', 'Backend\Accounting\AccountCurrencyController@destroy')->name('admin.currencies.destroy');

    //expense-categories routes
    Route::get('expense-categories/index', 'Backend\Accounting\AccountExpenseCategoryController@index')->name('admin.expense-categories.index');
    Route::get('expense-categories/create', 'Backend\Accounting\AccountExpenseCategoryController@create')->name('admin.expense-categories.create');
    Route::post('expense-categories/store', 'Backend\Accounting\AccountExpenseCategoryController@store')->name('admin.expense-categories.store');
    Route::get('expense-categories/edit/{id}', 'Backend\Accounting\AccountExpenseCategoryController@edit')->name('admin.expense-categories.edit');
    Route::put('expense-categories/update/{id}', 'Backend\Accounting\AccountExpenseCategoryController@update')->name('admin.expense-categories.update');
    Route::delete('expense-categories/destroy/{id}', 'Backend\Accounting\AccountExpenseCategoryController@destroy')->name('admin.expense-categories.destroy');

    //accounting-expenses routes
    Route::get('accounting-expenses/index', 'Backend\Accounting\AccountExpenseController@index')->name('admin.accounting-expenses.index');
    Route::get('accounting-expenses/create', 'Backend\Accounting\AccountExpenseController@create')->name('admin.accounting-expenses.create');
    Route::post('accounting-expenses/store', 'Backend\Accounting\AccountExpenseController@store')->name('admin.accounting-expenses.store');
    Route::get('accounting-expenses/edit/{id}', 'Backend\Accounting\AccountExpenseController@edit')->name('admin.accounting-expenses.edit');
    Route::put('accounting-expenses/update/{id}', 'Backend\Accounting\AccountExpenseController@update')->name('admin.accounting-expenses.update');
    Route::delete('accounting-expenses/destroy/{id}', 'Backend\Accounting\AccountExpenseController@destroy')->name('admin.accounting-expenses.destroy');

    //accounting fund-transfer routes
    Route::get('accounting-fund-transfer/index', 'Backend\Accounting\FundTransferController@index')->name('admin.accounting-fund-transfer.index');
    Route::get('accounting-fund-transfer/create', 'Backend\Accounting\FundTransferController@create')->name('admin.accounting-fund-transfer.create');
    Route::post('accounting-fund-transfer/store', 'Backend\Accounting\FundTransferController@store')->name('admin.accounting-fund-transfer.store');
    Route::get('accounting-fund-transfer/show/{id}', 'Backend\Accounting\FundTransferController@show')->name('admin.accounting-fund-transfer.show');
    Route::get('accounting-fund-transfer/edit/{id}', 'Backend\Accounting\FundTransferController@edit')->name('admin.accounting-fund-transfer.edit');
    Route::put('accounting-fund-transfer/update/{id}', 'Backend\Accounting\FundTransferController@update')->name('admin.accounting-fund-transfer.update');
    Route::post('accounting-fund-transfer/validate/{id}', 'Backend\Accounting\FundTransferController@validateFundTransfer')->name('admin.accounting-fund-transfer.validate');
    Route::post('accounting-fund-transfer/confirm/{id}', 'Backend\Accounting\FundTransferController@confirm')->name('admin.accounting-fund-transfer.confirm');
    Route::post('accounting-fund-transfer/approuve/{id}', 'Backend\Accounting\FundTransferController@approuve')->name('admin.accounting-fund-transfer.approuve');
    Route::get('accounting-fund-transfer/print/{id}', 'Backend\Accounting\FundTransferController@print')->name('admin.accounting-fund-transfer.print');

    //accounting return-fund routes
    Route::get('accounting-return-fund/index', 'Backend\Accounting\ReturnFundController@index')->name('admin.accounting-return-fund.index');
    Route::get('accounting-return-fund/create/{cash_outflow_no}', 'Backend\Accounting\ReturnFundController@create')->name('admin.accounting-return-fund.create');
    Route::post('accounting-return-fund/store', 'Backend\Accounting\ReturnFundController@store')->name('admin.accounting-return-fund.store');
    Route::get('accounting-return-fund/show/{id}', 'Backend\Accounting\ReturnFundController@show')->name('admin.accounting-return-fund.show');
    Route::get('accounting-return-fund/edit/{id}', 'Backend\Accounting\ReturnFundController@edit')->name('admin.accounting-return-fund.edit');
    Route::put('accounting-return-fund/update/{id}', 'Backend\Accounting\ReturnFundController@update')->name('admin.accounting-return-fund.update');
    Route::delete('accounting-return-fund/destroy/{id}', 'Backend\Accounting\ReturnFundController@destroy')->name('admin.accounting-return-fund.destroy');
    Route::post('accounting-return-fund/validate/{id}', 'Backend\Accounting\ReturnFundController@validateReturnFund')->name('admin.accounting-return-fund.validate');
    Route::post('accounting-return-fund/confirm/{id}', 'Backend\Accounting\ReturnFundController@confirm')->name('admin.accounting-return-fund.confirm');
    Route::post('accounting-return-fund/approuve/{id}', 'Backend\Accounting\ReturnFundController@approuve')->name('admin.accounting-return-fund.approuve');
    Route::get('accounting-return-fund/print/{id}', 'Backend\Accounting\ReturnFundController@print')->name('admin.accounting-return-fund.print');

    //accounting advance-payment routes
    Route::get('accounting-advance-payment/index', 'Backend\Accounting\AccountPaymentAdvanceController@index')->name('admin.accounting-advance-payment.index');
    Route::get('accounting-advance-payment/create', 'Backend\Accounting\AccountPaymentAdvanceController@create')->name('admin.accounting-advance-payment.create');
    Route::post('accounting-advance-payment/store', 'Backend\Accounting\AccountPaymentAdvanceController@store')->name('admin.accounting-advance-payment.store');
    Route::get('accounting-advance-payment/show/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@show')->name('admin.accounting-advance-payment.show');
    Route::get('accounting-advance-payment/edit/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@edit')->name('admin.accounting-advance-payment.edit');
    Route::put('accounting-advance-payment/update/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@update')->name('admin.accounting-advance-payment.update');
    Route::post('accounting-advance-payment/validate/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@validateAccountPaymentAdvance')->name('admin.accounting-advance-payment.validate');
    Route::post('accounting-advance-payment/confirm/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@confirm')->name('admin.accounting-advance-payment.confirm');
    Route::post('accounting-advance-payment/approuve/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@approuve')->name('admin.accounting-advance-payment.approuve');
    Route::get('accounting-advance-payment/print/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@print')->name('admin.accounting-advance-payment.print');
    Route::get('accounting-advance-payment/document/view/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@viewDocument')->name('admin.accounting-advance-payment.document.view');
    Route::get('accounting-advance-payment/document/download/{id}', 'Backend\Accounting\AccountPaymentAdvanceController@downloadDocument')->name('admin.accounting-advance-payment.document.download');

    //accounting accounts routes
    Route::get('accounting-accounts/index', 'Backend\Accounting\AccountAccountController@index')->name('admin.accounting-accounts.index');
    Route::get('accounting-accounts/create', 'Backend\Accounting\AccountAccountController@create')->name('admin.accounting-accounts.create');
    Route::post('accounting-accounts/store', 'Backend\Accounting\AccountAccountController@store')->name('admin.accounting-accounts.store');
    Route::get('accounting-accounts/edit/{id}', 'Backend\Accounting\AccountAccountController@edit')->name('admin.accounting-accounts.edit');
    Route::put('accounting-accounts/update/{id}', 'Backend\Accounting\AccountAccountController@update')->name('admin.accounting-accounts.update');
    Route::delete('accounting-accounts/destroy/{id}', 'Backend\Accounting\AccountAccountController@destroy')->name('admin.accounting-accounts.destroy');
    Route::get('accounting-accounts/search', 'Backend\Accounting\AccountAccountController@search')->name('admin.accounting-accounts.search');

    //accounting parent-accounts routes
    Route::get('accounting-parent-accounts/index', 'Backend\Accounting\AccountParentAccountController@index')->name('admin.accounting-parent-accounts.index');
    Route::get('accounting-parent-accounts/create', 'Backend\Accounting\AccountParentAccountController@create')->name('admin.accounting-parent-accounts.create');
    Route::post('accounting-parent-accounts/store', 'Backend\Accounting\AccountParentAccountController@store')->name('admin.accounting-parent-accounts.store');
    Route::get('accounting-parent-accounts/show/{id}', 'Backend\Accounting\AccountParentAccountController@show')->name('admin.accounting-parent-accounts.show');
    Route::get('accounting-parent-accounts/edit/{id}', 'Backend\Accounting\AccountParentAccountController@edit')->name('admin.accounting-parent-accounts.edit');
    Route::put('accounting-parent-accounts/update/{id}', 'Backend\Accounting\AccountParentAccountController@update')->name('admin.accounting-parent-accounts.update');
    Route::delete('accounting-parent-accounts/destroy/{id}', 'Backend\Accounting\AccountParentAccountController@destroy')->name('admin.accounting-parent-accounts.destroy');
    Route::get('accounting-parent-accounts/export-excel', 'Backend\Accounting\AccountParentAccountController@exportExcel')->name('admin.accounting-parent-accounts.export-excel');

    //accounting settings routes
    Route::get('accounting-settings/index', 'Backend\Accounting\AccountSettingController@index')->name('admin.accounting-settings.index');
    Route::get('accounting-settings/create', 'Backend\Accounting\AccountSettingController@create')->name('admin.accounting-settings.create');
    Route::post('accounting-settings/store', 'Backend\Accounting\AccountSettingController@store')->name('admin.accounting-settings.store');
    Route::get('accounting-settings/show/{id}', 'Backend\Accounting\AccountSettingController@show')->name('admin.accounting-settings.show');
    Route::get('accounting-settings/edit/{id}', 'Backend\Accounting\AccountSettingController@edit')->name('admin.accounting-settings.edit');
    Route::put('accounting-settings/update/{id}', 'Backend\Accounting\AccountSettingController@update')->name('admin.accounting-settings.update');
    Route::delete('accounting-settings/destroy/{id}', 'Backend\Accounting\AccountSettingController@destroy')->name('admin.accounting-settings.destroy');

    //-----exercices / clôture comptable-----
    Route::get('accounting/exercises/index', 'Backend\Accounting\AccountExerciseController@index')->name('admin.accounting-exercises.index');
    Route::get('accounting/exercises/create', 'Backend\Accounting\AccountExerciseController@create')->name('admin.accounting-exercises.create');
    Route::post('accounting/exercises/store', 'Backend\Accounting\AccountExerciseController@store')->name('admin.accounting-exercises.store');
    Route::get('accounting/exercises/preview', 'Backend\Accounting\AccountExerciseController@preview')->name('admin.accounting-exercises.preview');
    Route::post('accounting/exercises/close', 'Backend\Accounting\AccountExerciseController@close')->name('admin.accounting-exercises.close');
    Route::get('accounting/exercises/preview-result', 'Backend\Accounting\AccountExerciseController@previewResult')->name('admin.accounting-exercises.preview-result');
    Route::post('accounting/exercises/close-result', 'Backend\Accounting\AccountExerciseController@closeResult')->name('admin.accounting-exercises.close-result');
    Route::get('accounting/exercises/preview-ran', 'Backend\Accounting\AccountExerciseController@previewRan')->name('admin.accounting-exercises.preview-ran');
    Route::post('accounting/exercises/close-ran', 'Backend\Accounting\AccountExerciseController@closeRan')->name('admin.accounting-exercises.close-ran');
    Route::post('accounting/exercises/lock', 'Backend\Accounting\AccountExerciseController@lock')->name('admin.accounting-exercises.lock');
    Route::get('accounting/exercises/show/{id}', 'Backend\Accounting\AccountExerciseController@show')->name('admin.accounting-exercises.show');
    Route::put('accounting/exercises/lock-period/{periodId}', 'Backend\Accounting\AccountExerciseController@lockPeriod')->name('admin.accounting-exercises.lock-period');
    //-----exercices / clôture comptable-----
    Route::get('accounting/sau-rectification', 'Backend\Accounting\SauRectificationController@index')->name('admin.accounting-sau-rectification.index');
    Route::post('accounting/sau-rectification/apply', 'Backend\Accounting\SauRectificationController@apply')->name('admin.accounting-sau-rectification.apply');

    //cash outflow request routes
    Route::get('accounting/cash-out-flow-request/index', 'Backend\Accounting\CashOutFlowRequestController@index')->name('admin.cash-out-flow-request.index');
    Route::get('accounting/cash-out-flow-request/create', 'Backend\Accounting\CashOutFlowRequestController@create')->name('admin.cash-out-flow-request.create');
    Route::post('accounting/cash-out-flow-request/store', 'Backend\Accounting\CashOutFlowRequestController@store')->name('admin.cash-out-flow-request.store');
    Route::get('accounting/cash-out-flow-request/loan-create/{loan_id}', 'Backend\Accounting\CashOutFlowRequestController@loanPaymentRequest')->name('admin.cash-out-flow-request.loan-create');
    Route::post('accounting/cash-out-flow-request/loan-store', 'Backend\Accounting\CashOutFlowRequestController@storeLoanPaymentRequest')->name('admin.cash-out-flow-request.loan-store');
    Route::get('accounting/cash-out-flow-request/edit/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowRequestController@edit')->name('admin.cash-out-flow-request.edit');
    Route::put('accounting/cash-out-flow-request/update/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowRequestController@update')->name('admin.cash-out-flow-request.update');
    Route::delete('accounting/cash-out-flow-request/destroy/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowRequestController@destroy')->name('admin.cash-out-flow-request.destroy');
    Route::get('accounting/cash-out-flow-request/show/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowRequestController@show')->name('admin.cash-out-flow-request.show');
    Route::put('accounting/cash-out-flow-request/update-supplier/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowRequestController@updateSupplier')->name('admin.cash-out-flow-request.update-supplier');

    Route::get('accounting/cash-out-flow-request/document/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowRequestController@document')->name('admin.cash-out-flow-request.document');
    Route::put('accounting/cash-out-flow-request/validate/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowRequestController@validateCashOutFlowRequest')->name('admin.cash-out-flow-request.validate');
    Route::put('accounting/cash-out-flow-request/reject/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowRequestController@reject')->name('admin.cash-out-flow-request.reject');
    Route::put('accounting/cash-out-flow-request/reset/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowRequestController@reset')->name('admin.cash-out-flow-request.reset');
    Route::put('accounting/cash-out-flow-request/confirm/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowRequestController@confirm')->name('admin.cash-out-flow-request.confirm');
    Route::put('accounting/cash-out-flow-request/approuve/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowRequestController@approuve')->name('admin.cash-out-flow-request.approuve');

    Route::get('accounting/drink-purchase-payment-request/create/{reception_no}', 'Backend\Accounting\CashOutFlowRequestController@drinkPaymentRequest')->name('admin.drink-purchase-payment-request.create');
    Route::get('accounting/drink-purchase-payment-request-before/create/{order_no}', 'Backend\Accounting\CashOutFlowRequestController@drinkPaymentRequestBefore')->name('admin.drink-purchase-payment-request-before.create');
    Route::get('accounting/raw-material-purchase-payment-request/create/{reception_no}', 'Backend\Accounting\CashOutFlowRequestController@rawMaterialPaymentRequest')->name('admin.raw-material-purchase-payment-request.create');
    Route::get('accounting/raw-material-purchase-payment-request-before/create/{order_no}', 'Backend\Accounting\CashOutFlowRequestController@rawMaterialPaymentRequestBefore')->name('admin.raw-material-purchase-payment-request-before.create');

    Route::get('accounting/material-purchase-payment-request/create/{reception_no}', 'Backend\Accounting\CashOutFlowRequestController@materialPaymentRequest')->name('admin.material-purchase-payment-request.create');
    Route::get('accounting/material-purchase-payment-request-before/create/{order_no}', 'Backend\Accounting\CashOutFlowRequestController@materialPaymentRequestBefore')->name('admin.material-purchase-payment-request-before.create');

    Route::post('accounting/drink-purchase-payment-request/store', 'Backend\Accounting\CashOutFlowRequestController@storeDrinkPaymentRequest')->name('admin.drink-purchase-payment-request.store');
    Route::post('accounting/drink-purchase-payment-request-before/store', 'Backend\Accounting\CashOutFlowRequestController@storeDrinkPaymentRequestBefore')->name('admin.drink-purchase-payment-request-before.store');
    Route::post('accounting/raw-material-purchase-payment-request/store', 'Backend\Accounting\CashOutFlowRequestController@storeRawMaterialPaymentRequest')->name('admin.raw-material-purchase-payment-request.store');
    Route::post('accounting/raw-material-purchase-payment-request-before/store', 'Backend\Accounting\CashOutFlowRequestController@storeRawMaterialPaymentRequestBefore')->name('admin.raw-material-purchase-payment-request-before.store');
    Route::post('accounting/material-purchase-payment-request/store', 'Backend\Accounting\CashOutFlowRequestController@storeMaterialPaymentRequest')->name('admin.material-purchase-payment-request.store');
    Route::post('accounting/material-purchase-payment-request-before/store', 'Backend\Accounting\CashOutFlowRequestController@storeMaterialPaymentRequestBefore')->name('admin.material-purchase-payment-request-before.store');

    Route::get('accounting/cash-out-flow-request/export-to-excel','Backend\Accounting\CashOutFlowRequestController@exportToExcel')->name('admin.cash-out-flow-request.export-to-excel');

    //cash outflow routes
    Route::get('accounting/cash-out-flow/index', 'Backend\Accounting\CashOutFlowController@index')->name('admin.cash-out-flow.index');
    Route::get('accounting/cash-out-flow/create/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@create')->name('admin.cash-out-flow.create');
    Route::post('accounting/cash-out-flow/store', 'Backend\Accounting\CashOutFlowController@store')->name('admin.cash-out-flow.store');
    Route::get('accounting/cash-out-flow/loan-create/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@loanPayment')->name('admin.cash-out-flow.loan-create');
    Route::post('accounting/cash-out-flow/loan-store', 'Backend\Accounting\CashOutFlowController@storeLoanPayment')->name('admin.cash-out-flow.loan-store');
    Route::get('accounting/cash-out-flow/edit/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@edit')->name('admin.cash-out-flow.edit');
    Route::put('accounting/cash-out-flow/update/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@update')->name('admin.cash-out-flow.update');
    Route::delete('accounting/cash-out-flow/destroy/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@destroy')->name('admin.cash-out-flow.destroy');
    Route::get('accounting/cash-out-flow/show/{cash_outflow_no}','Backend\Accounting\CashOutFlowController@show')->name('admin.cash-out-flow.show');
    Route::put('accounting/cash-out-flow/update-supplier/{cash_outflow_no}', 'Backend\Accounting\CashOutFlowController@updateSupplier')->name('admin.cash-out-flow.update-supplier');

    Route::get('accounting/cash-out-flow/document/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowController@document')->name('admin.cash-out-flow.document');
    Route::put('accounting/cash-out-flow/validate/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@validateCashOutFlow')->name('admin.cash-out-flow.validate');
    Route::put('accounting/cash-out-flow/reject/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowController@reject')->name('admin.cash-out-flow.reject');
    Route::put('accounting/cash-out-flow/reset/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowController@reset')->name('admin.cash-out-flow.reset');
    Route::put('accounting/cash-out-flow/confirm/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowController@confirm')->name('admin.cash-out-flow.confirm');
    Route::put('accounting/cash-out-flow/approuve/{cash_outflow_request_no}','Backend\Accounting\CashOutFlowController@approuve')->name('admin.cash-out-flow.approuve');
    Route::put('accounting/cash-out-flow/mark-as-no-return/{cash_outflow_no}','Backend\Accounting\CashOutFlowController@markAsNoReturn')->name('admin.cash-out-flow.mark-as-no-return');

    

    Route::get('accounting/purchase-payment/create/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@payment')->name('admin.purchase-payment.create');
    Route::get('accounting/purchase-payment-before/create/{cash_outflow_request_no}', 'Backend\Accounting\CashOutFlowController@paymentBefore')->name('admin.purchase-payment-before.create');
    

    Route::post('accounting/drink-purchase-payment/store', 'Backend\Accounting\CashOutFlowController@storeDrinkPayment')->name('admin.drink-purchase-payment.store');
    Route::post('accounting/drink-purchase-payment-before/store', 'Backend\Accounting\CashOutFlowController@storeDrinkPaymentBefore')->name('admin.drink-purchase-payment-before.store');
    Route::post('accounting/raw-material-purchase-payment/store', 'Backend\Accounting\CashOutFlowController@storeFoodPayment')->name('admin.raw-material-purchase-payment.store');
    Route::post('accounting/raw-material-purchase-payment-before/store', 'Backend\Accounting\CashOutFlowController@storeFoodPaymentBefore')->name('admin.raw-material-purchase-payment-before.store');
    Route::post('accounting/material-purchase-payment/store', 'Backend\Accounting\CashOutFlowController@storeMaterialPayment')->name('admin.material-purchase-payment.store');
    Route::post('accounting/material-purchase-payment-before/store', 'Backend\Accounting\CashOutFlowController@storeMaterialPaymentBefore')->name('admin.material-purchase-payment-before.store');

    Route::get('accounting/cash-out-flow/export-to-excel','Backend\Accounting\CashOutFlowController@exportToExcel')->name('admin.cash-out-flow.export-to-excel');

    Route::get('accounting/journal-entry/index','Backend\Accounting\AccountJournalEntryController@index')->name('admin.journal-entry.index');
    Route::get('accounting/journal-entry/search-accounts','Backend\Accounting\AccountJournalEntryController@searchLedgerAccounts')->name('admin.journal-entry.search-accounts');
    Route::get('accounting/journal-entry/print','Backend\Accounting\AccountJournalEntryController@print')->name('admin.journal-entry.print');
    Route::get('accounting/journal-entry/export-excel','Backend\Accounting\AccountJournalEntryController@exportToExcel')->name('admin.journal-entry.export-excel');
    //-----balance-----
    Route::get('accounting/accounts-balance/index','Backend\Accounting\AccountLedgerController@balance')->name('admin.accounts-balance.index');
    Route::get('accounting/accounts-balance/export-pdf','Backend\Accounting\AccountLedgerController@exportPDF')->name('admin.accounts-balance.export-pdf');
    Route::get('accounting/accounts-balance/export-excel','Backend\Accounting\AccountLedgerController@exportExcel')->name('admin.accounts-balance.export-excel');
    //-----balance----
    //-----active balance-----
    Route::get('accounting/active-balance-sheet/index','Backend\Accounting\ActiveBalanceController@index')->name('admin.active-balance-sheet.index');
    Route::get('accounting/active-balance-sheet/export-pdf','Backend\Accounting\ActiveBalanceController@exportPDF')->name('admin.active-balance-sheet.export-pdf');
    Route::get('accounting/active-balance-sheet/export-excel','Backend\Accounting\ActiveBalanceController@exportExcel')->name('admin.active-balance-sheet.export-excel');
    //-----active balance----
    //-----passive balance-----
    Route::get('accounting/passive-balance-sheet/index','Backend\Accounting\PassiveBalanceController@index')->name('admin.passive-balance-sheet.index');
    Route::get('accounting/passive-balance-sheet/export-pdf','Backend\Accounting\PassiveBalanceController@exportPDF')->name('admin.passive-balance-sheet.export-pdf');
    Route::get('accounting/passive-balance-sheet/export-excel','Backend\Accounting\PassiveBalanceController@exportExcel')->name('admin.passive-balance-sheet.export-excel');
    //-----passive balance----
    //--------profit and loss statement ---------
    Route::get('accounting/accounts-profit-and-loss-statement/index','Backend\Accounting\ProfitLossStatementController@index')->name('admin.accounts-profit-and-loss-statement.index');
    Route::get('accounting/accounts-profit-and-loss-statement/export-pdf','Backend\Accounting\ProfitLossStatementController@exportPDF')->name('admin.profit-loss-statement.export-pdf');
    Route::get('accounting/accounts-profit-and-loss-statement/export-excel','Backend\Accounting\ProfitLossStatementController@exportExcel')->name('admin.profit-loss-statement.export-excel');
    //--------profit and loss statement ---------
    Route::get('accounting/journal-entry/create','Backend\Accounting\AccountJournalEntryController@create')->name('admin.journal-entry.create');
    Route::post('accounting/journal-entry/store','Backend\Accounting\AccountJournalEntryController@store')->name('admin.journal-entry.store');
    Route::get('accounting/journal-entry/{id}/edit','Backend\Accounting\AccountJournalEntryController@edit')->name('admin.journal-entry.edit');
    Route::post('accounting/journal-entry/{id}/update','Backend\Accounting\AccountJournalEntryController@update')->name('admin.journal-entry.update');
    Route::get('accounting/journal-entry/opening-balance','Backend\Accounting\AccountJournalEntryController@getOpeningBalance')->name('admin.journal-entry.opening-balance');

    Route::get('/404/muradutunge/ivyomwasavye-ntibishoboye-kuboneka',function(){
        return view('errors.404');


    });

    // Route to serve employee files if symlink doesn't exist
    Route::get('storage/employes/{type}/{filename}', function($type, $filename) {
        $path = storage_path('app/public/employes/'.$type.'/'.$filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        $file = \Illuminate\Support\Facades\File::get($path);
        $type = \Illuminate\Support\Facades\File::mimeType($path);
        
        $response = response($file, 200);
        $response->header('Content-Type', $type);
        $response->header('Content-Disposition', 'inline; filename="'.$filename.'"');
        
        return $response;
    })->where(['type' => 'images|documents', 'filename' => '[A-Za-z0-9_\-\.]+']);
});