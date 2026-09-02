<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Task;
use App\Services\UploadService;
use Illuminate\Http\Request;

class FaeController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::where('is_active', true)
            ->orderBy('department_name', 'asc')
            ->get();

        $faeList = FaeUser::with(['tasks', 'department'])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($fae) {
                $tasks = $fae->tasks;
                $fae->total_tasks = $tasks->count();
                $fae->completed_tasks = $tasks->where('status', 'Completed')->count();
                $fae->avg_progress = $fae->total_tasks > 0 ? (float)$tasks->avg('progress') : 0;
                $fae->department_name = $fae->department?->department_name ?? 'Unassigned';
                return $fae;
            });

        return view('fae.index', compact('faeList', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fae_code' => 'required|string|max:100|unique:fae_users,fae_code',
            'email' => 'nullable|email|max:255',
            'department_id' => 'required|exists:departments,id',
            'phone' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $name = trim($request->input('name'));
        $faeCode = strtoupper(trim($request->input('fae_code')));

        $profileImagePath = null;
        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $profileImagePath = UploadService::storeFile($request->file('profile_image'), 'fae_');
        }

        FaeUser::create([
            'name' => $name,
            'fae_code' => $faeCode,
            'email' => $request->input('email') ?: null,
            'department_id' => $request->input('department_id'),
            'phone' => $request->input('phone') ?: null,
            'profile_image' => $profileImagePath,
        ]);

        return redirect()->route('fae.index')->with('success', "FAE '{$name}' ({$faeCode}) was added successfully!");
    }

    public function update(Request $request, FaeUser $fae)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fae_code' => 'required|string|max:100|unique:fae_users,fae_code,' . $fae->id,
            'email' => 'nullable|email|max:255',
            'department_id' => 'required|exists:departments,id',
            'phone' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $name = trim($request->input('name'));
        $faeCode = strtoupper(trim($request->input('fae_code')));

        $data = [
            'name' => $name,
            'fae_code' => $faeCode,
            'email' => $request->input('email') ?: null,
            'department_id' => $request->input('department_id'),
            'phone' => $request->input('phone') ?: null,
        ];

        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $data['profile_image'] = UploadService::storeFile(
                $request->file('profile_image'),
                'fae_',
                $fae->profile_image
            );
        }

        $fae->update($data);

        return redirect()->route('fae.index')->with('success', "FAE '{$name}' details updated successfully!");
    }

    public function destroy(FaeUser $fae)
    {
        $name = $fae->name;
        Task::where('fae_id', $fae->id)->update(['fae_id' => null]);

        if ($fae->profile_image) {
            UploadService::delete($fae->profile_image);
        }

        $fae->delete();

        return redirect()->route('fae.index')->with('success', "FAE member '{$name}' removed successfully. Assigned tasks were unassigned.");
    }
}
