<?php

use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\DepartmentController;
use App\Http\Controllers\HR\DesignationController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\HolidayController;
use App\Http\Controllers\HR\LeaveController;
use App\Http\Controllers\HR\LeaveTypeController;
use App\Http\Controllers\HR\MyAttendanceController;
use App\Http\Controllers\HR\MyLeaveController;
use App\Http\Controllers\HR\MyPayslipController;
use App\Http\Controllers\HR\OvertimeController;
use App\Http\Controllers\HR\PayrollComponentController;
use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\HR\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('hr')->name('hr.')->group(function () {
    // Employee/Department/Designation/Shift/Holiday onboarding data.
    Route::middleware('permission:employees.view')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('designations', [DesignationController::class, 'index'])->name('designations.index');
        Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });
    Route::middleware('permission:employees.manage')->group(function () {
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });
    Route::middleware('permission:employees.manage')->group(function () {
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('employees/{employee}/link-user', [EmployeeController::class, 'linkUser'])->name('employees.link-user');
        Route::delete('employees/{employee}/link-user', [EmployeeController::class, 'unlinkUser'])->name('employees.unlink-user');
        Route::post('employees/{employee}/shifts', [EmployeeController::class, 'assignShift'])->name('employees.shifts.store');
        Route::delete('employees/{employee}/shifts/{assignment}', [EmployeeController::class, 'removeShiftAssignment'])->name('employees.shifts.destroy');

        Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('designations/create', [DesignationController::class, 'create'])->name('designations.create');
        Route::post('designations', [DesignationController::class, 'store'])->name('designations.store');
        Route::get('designations/{designation}/edit', [DesignationController::class, 'edit'])->name('designations.edit');
        Route::put('designations/{designation}', [DesignationController::class, 'update'])->name('designations.update');
        Route::delete('designations/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');

        Route::get('shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
        Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::get('shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
        Route::put('shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

        Route::get('holidays/create', [HolidayController::class, 'create'])->name('holidays.create');
        Route::post('holidays', [HolidayController::class, 'store'])->name('holidays.store');
        Route::get('holidays/{holiday}/edit', [HolidayController::class, 'edit'])->name('holidays.edit');
        Route::put('holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
        Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
    });

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

    // Attendance self-service (check in/out).
    Route::middleware('permission:attendance.own')->group(function () {
        Route::get('my-attendance', [MyAttendanceController::class, 'index'])->name('my-attendance.index');
        Route::post('my-attendance/check-in', [MyAttendanceController::class, 'checkIn'])->name('my-attendance.check-in');
        Route::post('my-attendance/check-out', [MyAttendanceController::class, 'checkOut'])->name('my-attendance.check-out');
    });
});
