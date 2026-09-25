<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreAttendanceDeviceRequest;
use Easybdit\LaravelEasyAttendance\Models\AttendanceDevice;
use Easybdit\LaravelEasyAttendance\Services\AttendanceDeviceSyncService;
use Easybdit\LaravelEasyAttendance\Services\ZKService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Native Inertia wrapper around the package's AttendanceDevice model and
 * sync service — the package ships its own JSON API for this
 * (AttendanceDeviceController + attendance.devices.* routes), but that's
 * left disabled (attendance.features.device_sync only gates its
 * migrations here) so this app has one consistent UI instead of two.
 */
class AttendanceDeviceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HR/AttendanceDevices/Index', [
            'devices' => AttendanceDevice::latest()->get()->map(fn (AttendanceDevice $device) => [
                'id' => $device->id,
                'name' => $device->name,
                'ip' => $device->ip,
                'port' => $device->port,
                'serial_number' => $device->serial_number,
                'model' => $device->model,
                'status' => $device->status,
                'connection_mode' => $device->connection_mode,
                'is_online' => $device->is_online,
                'sync_fail_count' => $device->sync_fail_count,
                'last_synced_at' => $device->last_synced_at?->toDateTimeString(),
                'last_seen_at' => $device->last_seen_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/AttendanceDevices/Create');
    }

    public function store(StoreAttendanceDeviceRequest $request): RedirectResponse
    {
        AttendanceDevice::create($request->validated());

        return redirect()->route('hr.attendance-devices.index')->with('success', 'Device added.');
    }

    public function edit(AttendanceDevice $attendanceDevice): Response
    {
        return Inertia::render('HR/AttendanceDevices/Edit', ['device' => $attendanceDevice]);
    }

    public function update(StoreAttendanceDeviceRequest $request, AttendanceDevice $attendanceDevice): RedirectResponse
    {
        $attendanceDevice->update($request->validated());

        return redirect()->route('hr.attendance-devices.index')->with('success', 'Device updated.');
    }

    public function destroy(AttendanceDevice $attendanceDevice): RedirectResponse
    {
        $attendanceDevice->delete();

        return redirect()->route('hr.attendance-devices.index')->with('success', 'Device deleted.');
    }

    public function test(AttendanceDevice $attendanceDevice): RedirectResponse
    {
        if (! $attendanceDevice->ip) {
            return back()->with('error', 'No IP configured on this device — it looks like a push/ADMS device.');
        }

        try {
            $svc = new ZKService($attendanceDevice->ip, (int) ($attendanceDevice->port ?: 4370), $attendanceDevice->comm_key, 8);
            $connected = $svc->connect();
            $svc->disconnect();
        } catch (\Throwable $e) {
            return back()->with('error', 'Device error: '.$e->getMessage());
        }

        return $connected
            ? back()->with('success', 'Connected successfully.')
            : back()->with('error', "Cannot connect to {$attendanceDevice->ip}:{$attendanceDevice->port}");
    }

    public function pull(AttendanceDevice $attendanceDevice, AttendanceDeviceSyncService $sync): RedirectResponse
    {
        set_time_limit(300);

        $result = $sync->pull($attendanceDevice);

        return $result['success']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }
}
