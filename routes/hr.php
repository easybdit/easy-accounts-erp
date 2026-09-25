<?php

use App\Http\Controllers\HR\LeaveController;
use App\Http\Controllers\HR\LeaveTypeController;
use App\Http\Controllers\HR\MyLeaveController;
use App\Http\Controllers\HR\MyPayslipController;
use App\Http\Controllers\HR\OvertimeController;
use App\Http\Controllers\HR\PayrollComponentController;
use App\Http\Controllers\HR\PayrollController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('hr')->name('hr.')->group(function () {
    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('payroll-components', [PayrollComponentController::class, 'index'])->name('payroll-components.index');
    });
    Route::middleware('permission:payroll.manage')->group(function () {
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/{salary_slip}/post', [PayrollController::class, 'post'])->name('payroll.post');

        Route::get('payroll-components/create', [PayrollComponentController::class, 'create'])->name('payroll-components.create');
        Route::post('payroll-components', [PayrollComponentController::class, 'store'])->name('payroll-components.store');
        Route::get('payroll-components/{payroll_component}/edit', [PayrollComponentController::class, 'edit'])->name('payroll-components.edit');
        Route::put('payroll-components/{payroll_component}', [PayrollComponentController::class, 'update'])->name('payroll-components.update');
        Route::delete('payroll-components/{payroll_component}', [PayrollComponentController::class, 'destroy'])->name('payroll-components.destroy');
    });

    // Leave — admin (sees/approves everyone's) vs self-service (own only).
    Route::middleware('permission:leaves.manage')->group(function () {
        Route::get('leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

        Route::get('leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
        Route::get('leave-types/create', [LeaveTypeController::class, 'create'])->name('leave-types.create');
        Route::post('leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::get('leave-types/{leave_type}/edit', [LeaveTypeController::class, 'edit'])->name('leave-types.edit');
        Route::put('leave-types/{leave_type}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
        Route::delete('leave-types/{leave_type}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
    });
    Route::middleware('permission:leaves.own')->group(function () {
        Route::get('my-leaves', [MyLeaveController::class, 'index'])->name('my-leaves.index');
        Route::get('my-leaves/create', [MyLeaveController::class, 'create'])->name('my-leaves.create');
        Route::post('my-leaves', [MyLeaveController::class, 'store'])->name('my-leaves.store');
    });

    // Overtime — admin approval only, no self-service view in this phase.
    Route::middleware('permission:overtime.manage')->group(function () {
        Route::get('overtime', [OvertimeController::class, 'index'])->name('overtime.index');
        Route::post('overtime/{overtime_record}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
        Route::post('overtime/{overtime_record}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');
    });

    // Payslip self-service.
    Route::middleware('permission:payroll.own')->group(function () {
        Route::get('my-payslips', [MyPayslipController::class, 'index'])->name('my-payslips.index');
        Route::get('my-payslips/{salary_slip}/pdf', [MyPayslipController::class, 'pdf'])->name('my-payslips.pdf');
    });
});
