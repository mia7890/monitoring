<?php

namespace App\Http\Controllers;

use App\Models\FaeUser;
use App\Models\Task;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;

class FaeController extends Controller
{
    public function index(Request $request)
    {
        $faeList = FaeUser::with(['tasks'])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($fae) {
                $tasks = $fae->tasks;
                $fae->total_tasks = $tasks->count();
                $fae->completed_tasks = $tasks->where('status', 'Completed')->count();
                $fae->avg_progress = $fae->total_tasks > 0 ? (float)$tasks->avg('progress') : 0;
                return $fae;
            });

        return view('fae.index', compact('faeList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fae_code' => 'required|string|max:100|unique:fae_users,fae_code',
            'email' => 'nullable|email|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $name = trim($request->input('name'));
        $faeCode = strtoupper(trim($request->input('fae_code')));

        $profileImagePath = null;
        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $file = $request->file('profile_image');
            $ext = strtolower($file->getClientOriginalExtension());
            $uploadDir = public_path('uploads');
            if (!\Illuminate\Support\Facades\File::exists($uploadDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($uploadDir, 0755, true);
            }
            $filename = 'fae_' . bin2hex(random_bytes(12)) . '.' . $ext;
            $file->move($uploadDir, $filename);
            $profileImagePath = 'uploads/' . $filename;
        }

        FaeUser::create([
            'name' => $name,
            'fae_code' => $faeCode,
            'email' => $request->input('email') ?: null,
            'department' => $request->input('department') ?: null,
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
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $name = trim($request->input('name'));
        $faeCode = strtoupper(trim($request->input('fae_code')));

        $data = [
            'name' => $name,
            'fae_code' => $faeCode,
            'email' => $request->input('email') ?: null,
            'department' => $request->input('department') ?: null,
            'phone' => $request->input('phone') ?: null,
        ];

        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $file = $request->file('profile_image');
            $ext = strtolower($file->getClientOriginalExtension());
            $uploadDir = public_path('uploads');
            if (!\Illuminate\Support\Facades\File::exists($uploadDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($uploadDir, 0755, true);
            }
            $filename = 'fae_' . bin2hex(random_bytes(12)) . '.' . $ext;
            $file->move($uploadDir, $filename);
            $data['profile_image'] = 'uploads/' . $filename;
        }

        $fae->update($data);

        return redirect()->route('fae.index')->with('success', "FAE '{$name}' details updated successfully!");
    }

    public function destroy(FaeUser $fae)
    {
        $name = $fae->name;
        // Unassign any tasks
        Task::where('fae_id', $fae->id)->update(['fae_id' => null]);
        $fae->delete();

        return redirect()->route('fae.index')->with('success', "FAE member '{$name}' removed successfully. Assigned tasks were unassigned.");
    }
}
