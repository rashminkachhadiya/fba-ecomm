<?php

use App\Http\Controllers\AmazonOrderReportController;
use App\Http\Controllers\POReceivedController;
use App\Http\Controllers\Supplier\ContactsController;
use App\Http\Controllers\Supplier\SupplierController;
use App\Http\Controllers\Supplier\ProductsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PurchaseOrderItemController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\FBAProductsController;
use App\Http\Controllers\FBA\FBAShipmentController;
use App\Http\Controllers\ShipmentPlanController;
use App\Http\Controllers\Prep\FbaPrepController;
use App\Http\Controllers\Prep\FbaPrepProductivityController;
use App\Services\CreateShipmentService;
use App\Http\Controllers\Shopify\ShopifyOrdersController;
use Illuminate\Support\Facades\View;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */

Route::get('/', function () {
    return redirect()->route('users.index');
});
// Route::get('generate-pdf', [PDFController::class, 'generatePDF']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.forgot');
Route::post('send-password-link', [ResetPasswordController::class, 'store'])->name('password-reset-link');
Route::post('password-update', [ResetPasswordController::class, 'updatePassword'])->name('password-update');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('change-status', [UserController::class, 'updateStatus'])->name('change-status');
    Route::get('profile', [UserController::class, 'profile'])->name('profile');
    Route::post('update-profile', [UserController::class, 'updateProfile'])->name('update-profile');
    
    Route::post('users-columns-visibility', [UserController::class, 'columnVisibility'])->name('users-columns-visibility');

    Route::resource('stores', StoreController::class);
    Route::post('store-change-status', [StoreController::class, 'updateStatus'])->name('store-change-status');
    Route::post('stores-columns-visibility', [StoreController::class, 'columnVisibility'])->name('stores-columns-visibility');

    Route::post('products-columns-visibility', [ProductController::class, 'columnVisibility'])->name('products-columns-visibility');
    Route::get('supplier-list', [ProductController::class, 'supplierList'])->name('supplier.list');
    Route::post('update-product', [ProductController::class, 'updateProduct'])->name('update-product');
    Route::resource('products', ProductController::class)->only(['index','show','update']);
    
    Route::post('update-product-supplier', [ProductController::class, 'updateProductDefaultSupplier'])->name('update-product-supplier');
    Route::get('orders', [AmazonOrderReportController::class,'list'])->name('orders.list');
    Route::post('orders-columns-visibility', [AmazonOrderReportController::class, 'columnVisibility'])->name('orders-columns-visibility');
    
    Route::resource('suppliers', SupplierController::class);
    Route::post('supplier-change-status', [SupplierController::class, 'updateStatus'])->name('supplier-change-status');
    Route::post('supplier-columns-visibility', [SupplierController::class, 'columnVisibility'])->name('supplier-columns-visibility');

    Route::resource('supplier_contact_info', ContactsController::class);
    Route::post('contact-columns-visibility', [ContactsController::class, 'columnVisibility'])->name('contact-columns-visibility');
    
    Route::resource('supplier_products', ProductsController::class);
    Route::post('product-columns-visibility', [ProductsController::class, 'columnVisibility'])->name('product-columns-visibility');
    Route::post('product-list', [ProductsController::class, 'getProductList'])->name('product-list');
    
    Route::post('po-columns-visibility', [PurchaseOrderController::class, 'columnVisibility'])->name('po-columns-visibility');
    Route::post('get_supplier_contact_info', [PurchaseOrderController::class, 'getSupplierContactInfo'])->name('get_supplier_contact_info');
    Route::post('update-po-status', [PurchaseOrderController::class, 'updatePOStatus'])->name('update-po-status');
    Route::get('generate-pdf/{poId}', [PDFController::class, 'generatePDF'])->name('generate-pdf');
    Route::get('export-po-excel/{po_id}', [PurchaseOrderController::class, 'exportPOExcel'])->name('export-po-excel');
    Route::post('send-po-email', [PurchaseOrderController::class, 'sendPOEmail'])->name('send-po-email');
    Route::post('submit-email-po', [PurchaseOrderController::class, 'submitEmailPO'])->name('submit-email-po');
    Route::get('get-shipping-info/{poId}', [PurchaseOrderController::class, 'getShippingInfo'])->name('get-shipping-info');
    Route::resource('purchase_orders', PurchaseOrderController::class);

    Route::resource('restocks', RestockController::class);
    Route::post('restock-columns-visibility', [RestockController::class, 'columnVisibility'])->name('restock-columns-visibility');
    Route::get('restock-supplier-products/{supplier_id}', [RestockController::class, 'supplierProductsList'])->name('restock-supplier-products');
    Route::post('create-po', [RestockController::class, 'createPO'])->name('create-po');
    Route::put('update-order-qty/{supplier_id}', [RestockController::class, 'updateOrderQty'])->name('update-order-qty');

    Route::resource('settings', SettingController::class)->only(['index', 'store']);
    Route::post('update-warehouse', [SettingController::class,'updateWarehouseData'])->name('update.warehouse');
    Route::get("/page-1", function(){  return View::make("pack-order-blank-page.page-1");});
    Route::get("/page-2", function(){  return View::make("pack-order-blank-page.page-2");});

    // Route::get('po-received', [POReceivedController::class,'index'])->name('po-received');
    Route::get('po-receiving/{poId}', [POReceivedController::class,'index'])->name('po_receiving.list');
    Route::put('update-po-receiving/{id}', [POReceivedController::class,'update'])->name('po_receiving.update');
    Route::post('add-discrepancy', [POReceivedController::class,'addDiscrepancy'])->name('add-discrepancy');
    Route::post('edit-discrepancy', [POReceivedController::class,'editDiscrepancy'])->name('edit-discrepancy');
    Route::post('delete-discrepancy', [POReceivedController::class,'deleteDiscrepancy'])->name('delete-discrepancy');
    Route::post('update-discrepancy', [POReceivedController::class,'updateDiscrepancy'])->name('update-discrepancy');

    Route::resource('purchase_order_items', PurchaseOrderItemController::class);
    Route::post('po-item-list', [PurchaseOrderItemController::class, 'getPOItemList'])->name('po-item-list');
    Route::put('update-po-item-order-qty', [PurchaseOrderItemController::class, 'updatePOItemOrderQty'])->name('update-po-item-order-qty');
    Route::post('po-item-bulk-delete', [PurchaseOrderItemController::class, 'poItemBulkDelete'])->name('po-item-bulk-delete');

    Route::resource('fba-products', FBAProductsController::class);
    Route::post('fba-products-columns-visibility', [FBAProductsController::class, 'columnVisibility'])->name('fba-products-columns-visibility');
    Route::get('fba_products.selected_products/{selectedProducts}/{storeFilter}', [FBAProductsController::class, 'selectedProducts'])->name('fba_products.selected_products');
    Route::get('selectAllChecked', [FBAProductsController::class,'selectAllChecked'])->name('selectAllChecked');
    Route::post('update-product-note', [FBAProductsController::class, 'updateProductproductNote'])->name('update-product-note');

    Route::resource('shipment-plans',ShipmentPlanController::class);
    Route::delete('delete-plan-product/{product_id}/{po_id?}', [ShipmentPlanController::class, 'deletePlanProduct'])->name('delete-plan-product');
    Route::post('fba-plan-columns-visibility', [ShipmentPlanController::class, 'columnVisibility'])->name('fba-plan-columns-visibility');
    Route::get('shipment-plans/{po_id}/create',[ShipmentPlanController::class,'create'])->name('shipment-plans.create');
    Route::post('shipment-plans/{shipment_plan}/submit',[ShipmentPlanController::class,'submit'])->name('shipment-plans.submit');
    Route::get('shipment-plans/{shipment_plan}/error-log',[ShipmentPlanController::class,'getShipmentPlanError'])->name('shipment-plans.error_log');
    Route::post('add-fba-products/{plan_id}', [ShipmentPlanController::class, 'addFbaProducts'])->name('add-fba-products');
    Route::post('insert-selected-fba-product/{po_id?}', [ShipmentPlanController::class, 'insertSelectedFbaProducts'])->name('insert-selected-fba-product');
    Route::post('storewise-products', [ShipmentPlanController::class,'storewiseProducts'])->name('storewise-products');
    Route::get('get-empty-sellable-units',[ShipmentPlanController::class,'getEmptySellableUnits'])->name('get-empty-sellable-units');
    Route::post('update-auto-shipment-detail', [ShipmentPlanController::class,'updateAutoShipmentDetail'])->name('update-auto-shipment-detail');
    Route::post('add-products/{po_id}', [ShipmentPlanController::class, 'addProducts'])->name('add-products');
    
    // FBA shipments
    Route::get('fba-shipments', [FBAShipmentController::class, 'index'])->name('fba-shipments.index');
    Route::get('fba-shipments/{shipmentId}', [FBAShipmentController::class, 'show'])->name('fba-shipments.show');
    Route::delete('fba-shipments/{fba_shipment}', [FBAShipmentController::class, 'destroy'])->name('fba-shipments.destroy');
    Route::get('fba-shipments-status/working', [FBAShipmentController::class, 'fbaWorkingShipmentsList'])->name('fba-shipments.fba_working_shipment_list');
    Route::get('fba-shipments-status', [FBAShipmentController::class, 'fbaCommonShipmentsList'])->name('fba-shipments.fba_common_shipment_list');
    Route::get('fba-shipments-transport_info/{shipmentId}', [FBAShipmentController::class, 'transportInfo'])->name('fba-shipments.transport_info');
    Route::post('create_transport_pallet', [FBAShipmentController::class, 'createTransportPallet'])->name('create_transport_pallet');
    Route::post('delete_transport_pallet', [FBAShipmentController::class, 'deleteTransportPallet'])->name('delete_transport_pallet');
    Route::post('save_transport_info', [FBAShipmentController::class, 'saveTransportInfo'])->name('save_transport_info');
    Route::post('sent_transport_detail', [FBAShipmentController::class, 'putShipmentTransportDetail'])->name('sent_transport_detail');
    Route::post('estimate_transport_detail', [FBAShipmentController::class, 'estimateTransportDetail'])->name('estimate_transport_detail');
    Route::post('confirm_transport_detail', [FBAShipmentController::class, 'confirmTransportDetail'])->name('confirm_transport_detail');
    Route::post('void_transport_detail', [FBAShipmentController::class, 'voidTransportDetail'])->name('void_transport_detail');

    // shipment reverse sync
    Route::post('fetch-shipment-sync', [SettingController::class, 'shipmentReverseSync'])->name('fetch-shipment-sync');

    //FBA Prep
    Route::get('prep-list', [FbaPrepController::class, 'index'])->name('prep_list');
    Route::get('prep-productivity-list', [FbaPrepProductivityController::class, 'index'])->name('prep-productivity-list');
    Route::post('get-prep-graph', [FbaPrepProductivityController::class, 'getPrepData'])->name('get-prep-graph');
    Route::post('fba-prep-columns-visibility', [FbaPrepController::class, 'columnVisibility'])->name('fba-prep-columns-visibility');
    Route::post('print-pallet-label', [FbaPrepController::class, 'printShipmentPalletLabel'])->name('fba_shipment.print-pallet-label');

    Route::group(['prefix' => 'fba-prep'], function () {
        Route::get('get-multi-skus/{shipmentId}', [FbaPrepController::class, 'getMultiSkus']);
        Route::delete('remove-sku/{skuId}', [FbaPrepController::class, 'deleteSku']);
        Route::post('add-mulit-skus', [FbaPrepController::class, 'addMultiSkus'])->name('add-mulit-skus');
        Route::put('update-sku-unit/{skuId}', [FbaPrepController::class, 'updateSkuUnit'])->name('update-sku-unit');
        Route::post('create-multi-skus-box', [FbaPrepController::class, 'createMultiSkusBox'])->name('create-multi-skus-box');
        Route::get('generate-multi-skus-box', [FbaPrepController::class, 'generateMultiSkusBoxLabelHtml'])->name('generate-multi-skus-box');
        Route::get('export-prep-xls/{id}', [FbaPrepController::class, 'exportPrepAsXls'])->name('export_prep_xls');
        Route::get('export-prep-csv/{id}', [FbaPrepController::class, 'exportPrepAsCSV'])->name('export_prep_csv');
        Route::get('check-shipment-discrepancy', [FbaPrepController::class, 'checkShipmentDiscrepancy'])->name('fba_prep.check_shipment_discrepancy');
        Route::post('update-shipment-complete-prep-status', [FbaPrepController::class, 'updateShipmentCompletePrepStatus'])->name('fba_prep.update_shipment_complete_prep_status');
        Route::post('generate-multisku-box-label', [FbaPrepController::class, 'generateMultiSkuBoxLabels'])->name('fba_prep.generate_multisku_box_label');
        Route::get('generate-box-label', [FbaPrepController::class, 'generateBoxLabelsHtml'])->name('fba_prep.generate_box_label');
    });
    
    Route::group(['prefix' => 'fba-shipment'], function () {
        Route::get('/edit-prep/{shipmentId}', [FbaPrepController::class, 'editPrep'])->name('edit-prep');
        Route::post('/get-single-item-label-data', [FbaPrepController::class,'getSinglePrepDetailsInfo'])->name('get-single-item-label-data');
        Route::post('/generate-product-labels', [FbaPrepController::class,'generateProductLabels'])->name('generate-product-labels');
        Route::post('/get-sku-validate', [FbaPrepController::class, 'updateSkuValidate'])->name('get_sku_validate');
        Route::get('/generate_prep_label_html', [FbaPrepController::class, 'generateProductLabelHtml'])->name('generate_prep_label_html');
        Route::get('generate_pallet_label_html', [FbaPrepController::class, 'generatePalletLabelHtml'])->name('fba_shipment.generate_pallet_label_html');
        Route::post('/generate-box-labels', [FbaPrepController::class,'generateBoxLabels'])->name('generate-box-labels');
        Route::post('/get-per-box-item-count', [FbaPrepController::class,'getPerBoxItemCount'])->name('get-per-box-item-count');
        Route::get('/generate_box_label_html', [FbaPrepController::class, 'generateBoxLabelHtml'])->name('generate_box_label_html');
        Route::post('/get-view-all-item-label-data', [FbaPrepController::class,'getViewAllPrepDetailsInfo'])->name('get-view-all-item-label-data');
        Route::post('/generate-single-box-labels', [FbaPrepController::class,'generateSingleBoxLabels'])->name('generate-single-box-labels');
        Route::post('/generate-all-box-labels', [FbaPrepController::class,'generateAllBoxLabels'])->name('generate-all-box-labels');
        Route::post('/delete-single-box', [FbaPrepController::class,'deleteSingleBoxData'])->name('delete-single-box');
        Route::post('/delete-all-boxes', [FbaPrepController::class,'deleteAllBoxesData'])->name('delete-all-boxes');
        Route::post('/update-prep-listing-log', [FbaPrepController::class,'updatePrepListingLogStatus'])->name('update-prep-listing-log');
        Route::post('/search-box-number', [FbaPrepController::class,'getSearchBoxNumber'])->name('search-box-number');
        Route::post('/update-prep-notes', [FbaPrepController::class, 'updatePrepNotes'])->name('update_prep_notes');
        Route::post('/print-all-label', [FbaPrepController::class, 'printAllLabel'])->name('print_all_label');
        // Route::get('prep-log/{shipmentId}', [FbaPrepController::class, 'prepLog'])->name('prep_log');

    Route::post('fba-shipments/move-delete-shipment', [FbaShipmentController::class, 'moveAndDeleteShipment'])->name('fba_shipments.move-delete-shipment');
        Route::post('/search-skus', [FbaPrepController::class, 'searchMultiSkus'])->name('search-skus');
    });

    Route::post('confirm-shipment/{shipmentId}', [FBAShipmentController::class, 'confirmShipment'])->name('confirm-shipment');

    Route::resource('shopify-orders',ShopifyOrdersController::class);
    Route::post('shopify-orders-columns-visibility', [ShopifyOrdersController::class, 'columnVisibility'])->name('shopify-orders-columns-visibility');
    Route::post('shopify-order-note-save', [ShopifyOrdersController::class, 'saveOrderNote'])->name('shopify-order-note-save');
    Route::put('supplier-products/update/{supplierProductId}', [SupplierController::class, 'updateSupplierProducts'])->name('supplier-products.update');
});
