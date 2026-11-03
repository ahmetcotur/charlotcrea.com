<?php

use App\Http\Controllers\Site\ContentController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\StoreInquiryController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\WorkController as AdminWorkController;
use App\Http\Controllers\Admin\CrmController as AdminCrmController;
use App\Http\Controllers\Admin\DestinationController as AdminDestinationController;
use App\Http\Controllers\Admin\FilmController as AdminFilmController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ReferenceController as AdminReferenceController;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Controllers\Admin\QuoteLinkController as AdminQuoteLinkController;
use App\Http\Controllers\Admin\QuoteSendController as AdminQuoteSendController;
use App\Http\Controllers\Admin\QuoteStatusController as AdminQuoteStatusController;
use App\Http\Controllers\Admin\RevisionRequestController as AdminRevisionRequestController;
use App\Http\Controllers\Admin\SiteSettingsController as AdminSiteSettingsController;
use App\Http\Controllers\Admin\WorkDeliverController as AdminWorkDeliverController;
use App\Http\Controllers\Admin\WorkLinkController as AdminWorkLinkController;
use App\Http\Controllers\Admin\WorkStatusController as AdminWorkStatusController;
use App\Http\Controllers\Public\QuoteApprovalController;
use App\Http\Controllers\Public\WorkDeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class);
Route::get('/robots.txt', fn () => response("User-agent: *\n".(config('charlot.indexable') ? "Allow: /\nDisallow: /admin/\nDisallow: /panel/\nDisallow: /legacy-cms/\n" : "Disallow: /\n"))->header('Content-Type', 'text/plain'));
Route::post('/quote-requests', StoreInquiryController::class)->middleware('throttle:5,1')->name('quote-requests.store');
Route::post('/contact-inquiries', StoreInquiryController::class)->middleware('throttle:5,1')->name('contact-inquiries.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware(AdminAuthenticate::class.':twill_users')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('demo-workflows', \App\Http\Controllers\Admin\DemoWorkflowsController::class)->name('demo-workflows');
        Route::get('crm', AdminCrmController::class)->name('crm.index');
        Route::resource('references', AdminReferenceController::class)->except('show');
        Route::resource('posts', AdminPostController::class)->except('show');
        Route::resource('projects', AdminProjectController::class)->except('show');
        Route::resource('films', AdminFilmController::class)->except('show');
        Route::resource('destinations', AdminDestinationController::class)->except('show');
        Route::resource('customers', AdminCustomerController::class)->except('show');
        Route::post('customers/{customer}/panel-invite', [\App\Http\Controllers\Admin\CustomerAccessController::class, 'invite'])->name('customers.access.invite');
        Route::post('customers/{customer}/panel-revoke', [\App\Http\Controllers\Admin\CustomerAccessController::class, 'revoke'])->name('customers.access.revoke');
        Route::post('works/{work}/delivery-links', [\App\Http\Controllers\Admin\WorkDeliveryLinkController::class, 'store'])->name('works.delivery-links.store');
        Route::put('works/{work}/delivery-links/{link}', [\App\Http\Controllers\Admin\WorkDeliveryLinkController::class, 'update'])->name('works.delivery-links.update');
        Route::delete('works/{work}/delivery-links/{link}', [\App\Http\Controllers\Admin\WorkDeliveryLinkController::class, 'destroy'])->name('works.delivery-links.destroy');
        Route::get('works/{work}/files/{file}', [\App\Http\Controllers\Admin\WorkFileController::class, 'download'])->name('works.files.download');
        Route::delete('works/{work}/files/{file}', [\App\Http\Controllers\Admin\WorkFileController::class, 'destroy'])->name('works.files.destroy');
        Route::resource('quotes', AdminQuoteController::class)->except('show');
        Route::post('quotes/{quote}/send', AdminQuoteSendController::class)->name('quotes.send');
        Route::get('quotes/{quote}/link', AdminQuoteLinkController::class)->name('quotes.link');
        Route::patch('quotes/{quote}/status', AdminQuoteStatusController::class)->name('quotes.update-status');
        Route::resource('works', AdminWorkController::class)->except('show');
        Route::post('works/{work}/deliver', AdminWorkDeliverController::class)->name('works.deliver');
        Route::get('works/{work}/link', AdminWorkLinkController::class)->name('works.link');
        Route::patch('works/{work}/status', AdminWorkStatusController::class)->name('works.update-status');
        Route::get('revision-requests', [AdminRevisionRequestController::class, 'index'])->name('revision-requests.index');
        Route::put('revision-requests/{revisionRequest}', [AdminRevisionRequestController::class, 'update'])->name('revision-requests.update');
        Route::get('inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [AdminInquiryController::class, 'edit'])->name('inquiries.edit');
        Route::put('inquiries/{inquiry}', [AdminInquiryController::class, 'update'])->name('inquiries.update');
        Route::delete('inquiries/{inquiry}', [AdminInquiryController::class, 'destroy'])->name('inquiries.destroy');
        Route::post('content-preview/{type}/{id}', \App\Http\Controllers\Admin\ContentPreviewController::class)->whereNumber('id')->name('content.preview');
        Route::get('page-manager', [AdminPageController::class, 'index'])->name('page-manager');
        Route::get('pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
        Route::put('pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
        Route::get('site-settings', [AdminSiteSettingsController::class, 'edit'])->name('site-settings.edit');
        Route::put('site-settings', [AdminSiteSettingsController::class, 'update'])->name('site-settings.update');
        Route::get('pages/create', [AdminPageController::class, 'create'])->name('pages.create');
        Route::post('pages', [AdminPageController::class, 'store'])->name('pages.store');
    });
});

Route::middleware('signed')->group(function () {
    Route::get('teslimat/{work}/dosyalar/{file}', \App\Http\Controllers\Public\WorkFileController::class)->name('public.works.files.download');
    Route::get('teklif/{quote}/pdf', [QuoteApprovalController::class, 'pdf'])->name('public.quotes.pdf');
    Route::get('teklif/{quote}', [QuoteApprovalController::class, 'show'])->name('public.quotes.show');
    Route::post('teklif/{quote}/kabul-et', [QuoteApprovalController::class, 'accept'])->name('public.quotes.accept');
    Route::post('teklif/{quote}/reddet', [QuoteApprovalController::class, 'decline'])->name('public.quotes.decline');

    Route::get('teslimat/{work}', [WorkDeliveryController::class, 'show'])->name('public.works.show');
    Route::post('teslimat/{work}/onayla', [WorkDeliveryController::class, 'approve'])->name('public.works.approve');
    Route::post('teslimat/{work}/revizyon-talep-et', [WorkDeliveryController::class, 'requestRevision'])->name('public.works.requestRevision');
});

Route::prefix('panel')->name('customer.')->middleware(\App\Http\Middleware\CustomerLocale::class)->group(function () {
    Route::get('login', [\App\Http\Controllers\Customer\AuthController::class, 'loginForm'])->name('login');
    Route::post('login', [\App\Http\Controllers\Customer\AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('forgot-password', [\App\Http\Controllers\Customer\AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Customer\AuthController::class, 'forgot'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Customer\AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Customer\AuthController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
    Route::middleware(\App\Http\Middleware\CustomerAuthenticate::class)->group(function () {
        Route::post('logout', [\App\Http\Controllers\Customer\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [\App\Http\Controllers\Customer\PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('quotes/{id}/pdf', [\App\Http\Controllers\Customer\PortalController::class, 'quotePdf'])->whereNumber('id')->name('quotes.pdf');
        Route::get('quotes/{id}', [\App\Http\Controllers\Customer\PortalController::class, 'showQuote'])->whereNumber('id')->name('quotes.show');
        Route::post('quotes/{id}/accept', [\App\Http\Controllers\Customer\PortalController::class, 'acceptQuote'])->whereNumber('id')->name('quotes.accept');
        Route::post('quotes/{id}/decline', [\App\Http\Controllers\Customer\PortalController::class, 'declineQuote'])->whereNumber('id')->name('quotes.decline');
        Route::get('works/{id}', [\App\Http\Controllers\Customer\PortalController::class, 'showWork'])->whereNumber('id')->name('works.show');
        Route::post('works/{id}/approve', [\App\Http\Controllers\Customer\PortalController::class, 'approveWork'])->whereNumber('id')->name('works.approve');
        Route::post('works/{id}/revision', [\App\Http\Controllers\Customer\PortalController::class, 'revision'])->whereNumber('id')->name('works.revision');
        Route::get('works/{id}/files/{file}', [\App\Http\Controllers\Customer\PortalController::class, 'download'])->whereNumber('id')->middleware('signed')->name('files.download');
    });
});

Route::get('/{path?}', ContentController::class)->middleware(\App\Http\Middleware\RecordPageVisit::class)->name('site.content')->where('path', '(?!admin(?:/|$)|legacy-cms(?:/|$)|img(?:/|$)|panel(?:/|$)|teklif(?:/|$)|teslimat(?:/|$)).*');
