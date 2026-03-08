<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect ke halaman login
Route::get('/', function () {
    return redirect('/login');
});

// ===================================================
// ROUTES YANG MEMBUTUHKAN AUTHENTICATION
// ===================================================
Route::middleware('auth')->group(function () {

    // ===================================================
    // DASHBOARD PER ROLE
    // ===================================================
    Route::get('/dashboard', function () {
        // Redirect ke dashboard sesuai role jika akses /dashboard biasa
        $user = auth()->user();
        switch ($user->role) {
            case 'super_admin':
                return redirect()->route('super-admin.dashboard');
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'kasir':
                return redirect()->route('kasir.dashboard');
            default:
                return view('dashboard');
        }
    })->name('dashboard');

    // Super Admin Dashboard
    Route::get('/super-admin/dashboard', [App\Http\Controllers\Shared\DashboardController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('super-admin.dashboard');

    // Admin Dashboard
    Route::get('/admin/dashboard', [App\Http\Controllers\Shared\DashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // Kasir Dashboard
    Route::get('/kasir/dashboard', [App\Http\Controllers\Shared\DashboardController::class, 'index'])
        ->middleware('role:kasir')
        ->name('kasir.dashboard');

    // ===================================================
    // PROFILE (SEMUA ROLE)
    // ===================================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ===================================================
    // SUPER ADMIN ONLY ROUTES
    // ===================================================
    Route::prefix('super-admin')->name('super-admin.')->middleware('role:super_admin')->group(function () {

        // Approval Routes
        Route::get('/approvals', [App\Http\Controllers\SuperAdmin\ApprovalController::class, 'index'])
            ->name('approvals.index');
        Route::post('/approvals/jual/{id}/approve', [App\Http\Controllers\SuperAdmin\ApprovalController::class, 'approveJual'])
            ->name('approvals.jual.approve');
        Route::post('/approvals/jual/{id}/reject', [App\Http\Controllers\SuperAdmin\ApprovalController::class, 'rejectJual'])
            ->name('approvals.jual.reject');
        Route::post('/approvals/aju/{id}/approve', [App\Http\Controllers\SuperAdmin\ApprovalController::class, 'approveAju'])
            ->name('approvals.aju.approve');
        Route::post('/approvals/aju/{id}/reject', [App\Http\Controllers\SuperAdmin\ApprovalController::class, 'rejectAju'])
            ->name('approvals.aju.reject');

        // User Management Routes
        Route::resource('users', App\Http\Controllers\SuperAdmin\UserManagementController::class)
            ->except(['show']);
    });

    // ===================================================
    // ADMIN ONLY ROUTES
    // ===================================================
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {

        // Produksi Raw Routes
        Route::resource('produksi-raw', App\Http\Controllers\Admin\ProduksiRawController::class)
            ->except(['show']);

        // Timbangan Routes
        Route::resource('timbangan', App\Http\Controllers\Admin\TimbanganController::class)
            ->except(['show']);

        Route::prefix('timbangan/import')->name('timbangan.import.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\TimbanganImportController::class, 'index'])
                ->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\TimbanganImportController::class, 'create'])
                ->name('create');
            Route::post('/', [App\Http\Controllers\Admin\TimbanganImportController::class, 'store'])
                ->name('store');
            Route::get('/{id}', [App\Http\Controllers\Admin\TimbanganImportController::class, 'show'])
                ->name('show');
            Route::get('/template/download', [App\Http\Controllers\Admin\TimbanganImportController::class, 'downloadTemplate'])
                ->name('template');
        });

        // Terima Raw Routes
        Route::resource('terima-raw', App\Http\Controllers\Admin\TerimaRawController::class)
            ->except(['show']);

        // Keluar Material Routes
        Route::resource('keluar-material', App\Http\Controllers\Admin\KeluarMaterialController::class)
            ->except(['show']);

        // Keluar Material UTM Routes
        Route::resource('keluar-material-utm', App\Http\Controllers\Admin\KeluarMaterialUtmController::class)
            ->except(['show']);
    });

    // ===================================================
    // DATA MASTER ROUTES (Admin & Super Admin)
    // ===================================================
    Route::prefix('admin')->name('admin.')->middleware('role:admin,super_admin')->group(function () {
        // Transporter Master
        Route::get('master/transporters/list', [App\Http\Controllers\Admin\TransporterController::class, 'getList'])
            ->name('master.transporters.list');
        Route::resource('master/transporters', App\Http\Controllers\Admin\TransporterController::class)
            ->except(['show'])
            ->names('master.transporters');

        // Barang Master
        Route::get('master/barangs/list', [App\Http\Controllers\Admin\BarangController::class, 'getList'])
            ->name('master.barangs.list');
        Route::resource('master/barangs', App\Http\Controllers\Admin\BarangController::class)
            ->except(['show'])
            ->names('master.barangs');

        // Supplier Master
        Route::get('master/suppliers/list', [App\Http\Controllers\Admin\SupplierController::class, 'getList'])
            ->name('master.suppliers.list');
        Route::resource('master/suppliers', App\Http\Controllers\Admin\SupplierController::class)
            ->except(['show'])
            ->names('master.suppliers');

        // Customer Master
        Route::get('master/customers/list', [App\Http\Controllers\Admin\CustomerController::class, 'getList'])
            ->name('master.customers.list');
        Route::resource('master/customers', App\Http\Controllers\Admin\CustomerController::class)
            ->except(['show'])
            ->names('master.customers');
    });

    // ===================================================
    // UM & LEMBUR ROUTES (Admin & Super Admin)
    // ===================================================
    Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
        // Generate route untuk UM Lembur (khusus)
        Route::post('/um-lembur/generate', [App\Http\Controllers\Admin\UmLemburController::class, 'generate'])
            ->name('um-lembur.generate');

        // Resource route untuk UM Lembur
        Route::resource('um-lembur', App\Http\Controllers\Admin\UmLemburController::class)
            ->except(['show']);
    });

    // ===================================================
    // KASIR ONLY ROUTES
    // ===================================================
    Route::prefix('kasir')->name('kasir.')->middleware('role:kasir')->group(function () {

        // Jual Material Routes
        Route::resource('jual-material', App\Http\Controllers\Kasir\JualMaterialController::class);

        // Aju Kas Routes
        Route::resource('aju-kas', App\Http\Controllers\Kasir\AjuKasController::class);

        // Lap Kas Routes
        Route::get('lap-kas', [App\Http\Controllers\Kasir\LapKasController::class, 'index'])
            ->name('lap-kas.index');
        Route::get('lap-kas/create', [App\Http\Controllers\Kasir\LapKasController::class, 'create'])
            ->name('lap-kas.create');
        Route::post('lap-kas', [App\Http\Controllers\Kasir\LapKasController::class, 'store'])
            ->name('lap-kas.store');
        Route::get('lap-kas/export', [App\Http\Controllers\Kasir\LapKasController::class, 'export'])
            ->name('lap-kas.export');

        // Hutang Routes
        Route::resource('hutang', App\Http\Controllers\Kasir\HutangController::class);

        // Piutang Routes
        Route::resource('piutang', App\Http\Controllers\Kasir\PiutangController::class);
    });

    // ===================================================
    // READ ONLY ROUTES (SEMUA ROLE BISA AKSES)
    // ===================================================
    Route::prefix('readonly')->name('readonly.')->group(function () {

        // Produksi Raw Read Only
        Route::get('produksi-raw', [App\Http\Controllers\ReadOnly\ProduksiRawViewController::class, 'index'])
            ->name('produksi-raw.index');
        Route::get('produksi-raw/export', [App\Http\Controllers\ReadOnly\ProduksiRawViewController::class, 'export'])
            ->name('produksi-raw.export');
        Route::get('produksi-raw/{id}', [App\Http\Controllers\ReadOnly\ProduksiRawViewController::class, 'show'])
            ->name('produksi-raw.show');

        // Timbangan Read Only
        Route::get('timbangan', [App\Http\Controllers\ReadOnly\TimbanganViewController::class, 'index'])
            ->name('timbangan.index');
        Route::get('timbangan/export', [App\Http\Controllers\ReadOnly\TimbanganViewController::class, 'export'])
            ->name('timbangan.export');
        Route::get('timbangan/{id}', [App\Http\Controllers\ReadOnly\TimbanganViewController::class, 'show'])
            ->name('timbangan.show');

        // Terima Raw Read Only
        Route::get('terima-raw', [App\Http\Controllers\ReadOnly\TerimaRawViewController::class, 'index'])
            ->name('terima-raw.index');
        Route::get('terima-raw/export', [App\Http\Controllers\ReadOnly\TerimaRawViewController::class, 'export'])
            ->name('terima-raw.export');
        Route::get('terima-raw/{id}', [App\Http\Controllers\ReadOnly\TerimaRawViewController::class, 'show'])
            ->name('terima-raw.show');

        // Keluar Material Read Only
        Route::get('keluar-material', [App\Http\Controllers\ReadOnly\KeluarMaterialViewController::class, 'index'])
            ->name('keluar-material.index');
        Route::get('keluar-material/export', [App\Http\Controllers\ReadOnly\KeluarMaterialViewController::class, 'export'])
            ->name('keluar-material.export');
        Route::get('keluar-material/{id}', [App\Http\Controllers\ReadOnly\KeluarMaterialViewController::class, 'show'])
            ->name('keluar-material.show');

        // Keluar Material UTM Read Only
        Route::get('keluar-material-utm', [App\Http\Controllers\ReadOnly\KeluarMaterialUtmViewController::class, 'index'])
            ->name('keluar-material-utm.index');
        Route::get('keluar-material-utm/export', [App\Http\Controllers\ReadOnly\KeluarMaterialUtmViewController::class, 'export'])
            ->name('keluar-material-utm.export');
        Route::get('keluar-material-utm/{id}', [App\Http\Controllers\ReadOnly\KeluarMaterialUtmViewController::class, 'show'])
            ->name('keluar-material-utm.show');

        // Jual Material Read Only
        Route::get('jual-material', [App\Http\Controllers\ReadOnly\JualMaterialViewController::class, 'index'])
            ->name('jual-material.index');
        Route::get('jual-material/export', [App\Http\Controllers\ReadOnly\JualMaterialViewController::class, 'export'])
            ->name('jual-material.export');
        Route::get('jual-material/{id}', [App\Http\Controllers\ReadOnly\JualMaterialViewController::class, 'show'])
            ->name('jual-material.show');

        // Hutang Read Only
        Route::get('hutang', [App\Http\Controllers\ReadOnly\HutangViewController::class, 'index'])
            ->name('hutang.index');
        Route::get('hutang/export', [App\Http\Controllers\ReadOnly\HutangViewController::class, 'export'])
            ->name('hutang.export');
        Route::get('hutang/{id}', [App\Http\Controllers\ReadOnly\HutangViewController::class, 'show'])
            ->name('hutang.show');

        // Piutang Read Only
        Route::get('piutang', [App\Http\Controllers\ReadOnly\PiutangViewController::class, 'index'])
            ->name('piutang.index');
        Route::get('piutang/export', [App\Http\Controllers\ReadOnly\PiutangViewController::class, 'export'])
            ->name('piutang.export');
        Route::get('piutang/{id}', [App\Http\Controllers\ReadOnly\PiutangViewController::class, 'show'])
            ->name('piutang.show');

        // ===================================================
        // UM & LEMBUR READ ONLY ROUTES (Semua Role)
        // ===================================================
        Route::get('um-lembur', [App\Http\Controllers\ReadOnly\UmLemburViewController::class, 'index'])
            ->name('um-lembur.index');
        Route::get('um-lembur/export', [App\Http\Controllers\ReadOnly\UmLemburViewController::class, 'export'])
            ->name('um-lembur.export');
        Route::get('um-lembur/{id}', [App\Http\Controllers\ReadOnly\UmLemburViewController::class, 'show'])
            ->name('um-lembur.show');
    });
});

// ===================================================
// AUTH ROUTES (BAWAAN BREEZE)
// ===================================================
require __DIR__ . '/auth.php';