<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FaeUser;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::query()
            ->with(['faes' => function ($query) {
                $query->approved()->orderBy('name', 'asc');
            }])
            ->withCount(['faes' => function ($query) {
                $query->approved();
            }])
            ->orderBy('department_name', 'asc')
            ->get();

        return view('departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:150|unique:departments,department_name',
            'is_active' => 'nullable|boolean',
        ]);

        Department::create([
            'department_name' => trim($request->input('department_name')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'department_name' => 'required|string|max:150|unique:departments,department_name,' . $department->id,
            'is_active' => 'nullable|boolean',
        ]);

        $department->update([
            'department_name' => trim($request->input('department_name')),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $faeCount = FaeUser::where('department_id', $department->id)->count();

        if ($faeCount > 0) {
            return redirect()->route('departments.index')->with('error', 'This department still has FAE records assigned to it.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
