<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportProductController;
use App\Http\Controllers\ReportAdsController;
use App\Http\Controllers\ReportGeoController;
use App\Http\Controllers\ReportInventoryController;
use App\Http\Controllers\ReportProfitController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OrderProblemController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\BankAccountsController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\MetabotAdController;
use App\Http\Controllers\MetabotFaqController;
use App\Http\Controllers\MetabotInboxController;
use App\Http\Controllers\MetabotTemplateController;

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

Route::get('/dashboard', function () {
       return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/', function () {
    return view('welcome');
});



Route::post('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/pay', [OrderController::class, 'pay'])->name('orders.pay');
Route::post('/orders/refund', [OrderController::class, 'refund'])->name('orders.refund');
Route::post('/orders/delete', [OrderController::class, 'delete'])->name('orders.delete');
Route::post('/products/create', [ProductController::class, 'create'])->name('products.create');
Route::post('/products/update', [ProductController::class, 'update'])->name('products.update');
Route::post('/products/delete', [ProductController::class, 'delete'])->name('products.delete');
Route::post('/orders/fulfill', [OrderController::class, 'fulfill'])->name('orders.fulfill');
Route::post('/items/update', [ProductController::class, 'item_update'])->name('items.update');
Route::post('/inventory/update', [ProductController::class, 'inventory_update'])->name('inventory.update');

Route::middleware(['role:ceo,administrador,vendedor'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/add', [OrderController::class, 'add'])->name('orders.add');
    Route::put('/orders/save', [OrderController::class, 'save'])->name('orders.save');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{id}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}/partial-edit', [OrderController::class, 'partialEdit'])->name('orders.partialEdit');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/pdf', [OrderController::class, 'pdf'])->name('orders.pdf');
    Route::get('/orders/{id}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('/shipments', [OrderController::class, 'shipments'])->name('shipments');
    Route::post('/shipments/{id}/done', [OrderController::class, 'shipments_done'])->name('shipments.done');
    Route::get('/shipments/export', [OrderController::class, 'shipments_export'])->name('shipments.export');
    Route::get('/shipments/load', [OrderController::class, 'shipments_load'])->name('shipments.load');
    Route::post('/shipments/upload', [OrderController::class, 'shipments_upload'])->name('shipments.upload');

});

Route::middleware(['role:ceo'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/report-product', [ReportProductController::class, 'index'])->name('report-product.index');
    Route::get('/report-product/{id}/details', [ReportProductController::class, 'details'])->name('report-product.details');
    Route::get('/report-product/{id}/details2', [ReportProductController::class, 'details2'])->name('report-product.details2');

    Route::get('/export-inventory-history', [ReportInventoryController::class, 'history_export'])->name('report-inventory-history.export');
    Route::get('/report-ads', [ReportAdsController::class, 'index'])->name('report-ads.index');

    Route::get('/report-geo', [ReportGeoController::class, 'index'])->name('report-geo.index');
    Route::get('/report-geo/{id}/details', [ReportGeoController::class, 'details'])->name('report-geo.details');
    Route::get('/report-profit', [ReportProfitController::class, 'index'])->name('report-profit.index');
    Route::get('/bank-accounts/new', [BankAccountsController::class, 'new'])->name('bank-accounts.new');
    Route::get('/bank-accounts', [BankAccountsController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountsController::class, 'store'])->name('bank-accounts.store');
    Route::get('/bank-accounts/{id}/edit', [BankAccountsController::class, 'edit'])->name('bank-accounts.edit');
    Route::post('/bank-accounts/{id}/update', [BankAccountsController::class, 'update'])->name('bank-accounts.update');
    Route::get('/bank-accounts/create', [BankAccountsController::class, 'create'])->name('bank-accounts.create');
    Route::post('/bank-accounts/upload', [BankAccountsController::class, 'upload'])->name('bank-accounts.upload');
    
    Route::get('/taxes/create', [TaxController::class, 'create'])->name('taxes.create');
    Route::post('/taxes/upload', [TaxController::class, 'upload'])->name('taxes.upload');
});

Route::middleware(['role:ceo,administrador'])->group(function () {
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/new', [TransferController::class, 'new'])->name('transfers.new');
    Route::get('/transfers/new-step-2', [TransferController::class, 'newStep2'])->name('transfers.newStep2');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/import', [OrderController::class, 'import'])->name('orders.import');
    Route::get('/import-ads', [OrderController::class, 'import_ads'])->name('orders.import_ads');
    Route::get('/import-ad-costs', [OrderController::class, 'import_ad_costs'])->name('orders.import_ad_costs');
    Route::get('/ads', [AdController::class, 'index'])->name('ads.index');
    Route::post('/ads/upload', [AdController::class, 'upload'])->name('ads.upload');
    Route::post('/ads/store', [AdController::class, 'store'])->name('ads.store');
    Route::get('/ads/costs', [AdController::class, 'costs'])->name('ads.costs');
    Route::post('/ads/costs/upload', [AdController::class, 'costs_upload'])->name('ads.costs.upload');
    Route::get('/expenses/new', [ExpenseController::class, 'new'])->name('expenses.new');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{id}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::post('/expenses/{id}/update', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::get('/order-problems/new', [OrderProblemController::class, 'new'])->name('order-problems.new');
    Route::get('/order-problems', [OrderProblemController::class, 'index'])->name('order-problems.index');
    Route::post('/order-problems', [OrderProblemController::class, 'store'])->name('order-problems.store');
    Route::get('/order-problems/{id}/edit', [OrderProblemController::class, 'edit'])->name('order-problems.edit');
    Route::post('/order-problems/{id}/update', [OrderProblemController::class, 'update'])->name('order-problems.update');
});

Route::middleware(['role:ceo,administrador,vendedor,contador'])->group(function () {
    Route::get('/invoices/{state}', [OrderController::class, 'invoices'])->name('invoices');
    Route::post('/invoices/{id}/done', [OrderController::class, 'invoices_done'])->name('invoices.done');
    Route::get('/invoices/export', [OrderController::class, 'invoices_export'])->name('invoices.export');
    Route::post('/invoices/{id}/generate', [OrderController::class, 'invoices_generate'])->name('invoices.generate');
    Route::get('/report-inventory', [ReportInventoryController::class, 'index'])->name('report-inventory.index');
    Route::get('/export-inventory', [ReportInventoryController::class, 'export'])->name('report-inventory.export');
    Route::post('/invoices/generate_batch', [OrderController::class, 'invoices_generate_batch'])->name('invoices.generate_batch');
    Route::get('/calculator', [OrderController::class, 'calculator'])->name('calculator.index');
});

Route::middleware(['role:ceo,administrador,contador'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/report-inventory-history', [ReportInventoryController::class, 'history'])->name('report-inventory.history');
    Route::get('/order-problems', [OrderProblemController::class, 'index'])->name('order-problems.index');
    Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.index');
    Route::get('/invoices-date', [AccountingController::class, 'invoices_date'])->name('accounting.invoices_date');
    Route::post('/invoices-date-save', [AccountingController::class, 'invoices_date_save'])->name('accounting.invoices_date_save');

});

// Metabot inbox — staff read & reply to WhatsApp chats (sales reps included).
Route::middleware(['role:ceo,administrador,vendedor'])->group(function () {
    Route::get('/metabot/inbox',                  [MetabotInboxController::class, 'index'])->name('metabot.inbox.index');
    Route::get('/metabot/inbox/{phone}',          [MetabotInboxController::class, 'show'])->name('metabot.inbox.show');
    Route::get('/metabot/inbox/{phone}/messages', [MetabotInboxController::class, 'messages'])->name('metabot.inbox.messages');
    Route::post('/metabot/inbox/{phone}/reply',    [MetabotInboxController::class, 'reply'])->name('metabot.inbox.reply');
    Route::post('/metabot/inbox/{phone}/template', [MetabotInboxController::class, 'sendTemplate'])->name('metabot.inbox.template');
});

// Metabot admin — configure which ads the bot engages and its FAQ answers.
Route::middleware(['role:ceo,administrador'])->group(function () {
    Route::get('/metabot/ads',           [MetabotAdController::class, 'index'])->name('metabot.ads.index');
    Route::get('/metabot/ads/new',       [MetabotAdController::class, 'new'])->name('metabot.ads.new');
    Route::post('/metabot/ads',          [MetabotAdController::class, 'store'])->name('metabot.ads.store');
    Route::get('/metabot/ads/{id}/edit', [MetabotAdController::class, 'edit'])->name('metabot.ads.edit');
    Route::post('/metabot/ads/{id}',     [MetabotAdController::class, 'update'])->name('metabot.ads.update');

    Route::get('/metabot/faqs',           [MetabotFaqController::class, 'index'])->name('metabot.faqs.index');
    Route::get('/metabot/faqs/new',       [MetabotFaqController::class, 'new'])->name('metabot.faqs.new');
    Route::post('/metabot/faqs',          [MetabotFaqController::class, 'store'])->name('metabot.faqs.store');
    Route::get('/metabot/faqs/{id}/edit', [MetabotFaqController::class, 'edit'])->name('metabot.faqs.edit');
    Route::post('/metabot/faqs/{id}',     [MetabotFaqController::class, 'update'])->name('metabot.faqs.update');

    Route::get('/metabot/templates',           [MetabotTemplateController::class, 'index'])->name('metabot.templates.index');
    Route::get('/metabot/templates/new',       [MetabotTemplateController::class, 'new'])->name('metabot.templates.new');
    Route::post('/metabot/templates',          [MetabotTemplateController::class, 'store'])->name('metabot.templates.store');
    Route::get('/metabot/templates/{id}/edit', [MetabotTemplateController::class, 'edit'])->name('metabot.templates.edit');
    Route::post('/metabot/templates/{id}',     [MetabotTemplateController::class, 'update'])->name('metabot.templates.update');
});

// WhatsApp Cloud API webhook — intentionally outside every role middleware group.
// Meta authenticates via X-Hub-Signature-256, verified inside the controller.
Route::get('/webhooks/whatsapp',  [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle']);

// Public privacy policy, required by Meta for switching the app to Live mode.
Route::view('/privacidad', 'privacidad');

require __DIR__.'/auth.php';
