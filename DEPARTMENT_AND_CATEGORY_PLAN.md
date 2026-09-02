# Department Page and Category Filtering Plan

This document describes the planned implementation for a department management page using the existing department table and a rendering-only category filter, without creating a new categories table.

This is a design plan only. No application code has been changed.

---

## 1. Objective

Create a dedicated department management page that supports:

- CRUD for departments
- Department-based filtering in the UI
- Category display as a rendered list based on department selection
- Compatibility with the existing FAE and task flows

The key decision is that category management will not require a separate database table. Instead, categories will be treated as display/filter labels derived from department data or existing task/FAE metadata.

---

## 2. Current State

The project already contains the needed foundation:

- `departments` table exists via migration
- `fae_users.department_id` already references departments
- `Department` model exists
- `FaeController` already loads departments into dropdowns

This means the system can support a department page without a second table.

---

## 3. Recommended Design

### 3.1 Department table remains the source of truth

Keep only this structure:

```sql
CREATE TABLE departments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Category handling is UI-only

Do not create a separate `categories` table.

Instead:

- department selection drives filtering
- category names are displayed from existing values already present in related records or from a simple server-side list defined in the controller/view
- category filtering is implemented in Blade or controller logic, not persisted as a separate table

This keeps the system lean and avoids unnecessary schema complexity.

---

## 4. Department Management Page

### 4.1 New route

Add admin-only routes for department CRUD:

```php
Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
```

### 4.2 Page responsibilities

The department page should:

- list all departments
- add a new department
- edit department name
- activate/deactivate department
- delete a department only when safe
- optionally show a count of FAEs under that department

### 4.3 UI layout

Recommended structure:

- Header: `Department Management`
- Form to add department
- Table with columns:
  - Department name
  - Status
  - FAE count
  - Edit
  - Delete

---

## 5. Category Filtering Without a Categories Table

### 5.1 Simple approach

Instead of storing categories in a new table, keep a category list in the view/controller logic using a department-driven filter.

Example idea:

```php
$departmentId = request('department_id');

$items = FaeUser::query();

if ($departmentId) {
    $items->where('department_id', $departmentId);
}

$faes = $items->get();
```

This is not a database category table; it is a filtered dataset by department.

### 5.2 Rendering-only filter logic

If the requirement is simply to show a category/section based on department, the logic can be implemented as:

- select department
- load records for that department
- render a filtered list or grouped output
- no separate category model or table

### 5.3 Example use case

For example, on the FAE page or reports page:

- user selects `Engineering`
- the page shows only `Engineering` FAE records
- the label is presented as a filtered section or group

This is a rendering concern, not a database concern.

---

## 6. Recommended Controller Design

### 6.1 `DepartmentController`

Responsibilities:

- list departments
- store new department
- update department
- delete department
- count related FAE records

### 6.2 No separate category controller

No `CategoryController` is required in this simpler design.

Category filtering will be handled in:

- the department page view
- query filters in the controller
- or a simple grouped Blade rendering block

---

## 7. Blade View Plan

### 7.1 `resources/views/departments/index.blade.php`

The page should have:

- add department form
- department list table
- each row may also show a department-specific filtered summary

Example structure:

```blade
<section>
    <form method="POST" action="{{ route('departments.store') }}"> ... </form>

    <table>
        <thead> ... </thead>
        <tbody>
            @foreach($departments as $department)
                <tr>
                    <td>{{ $department->department_name }}</td>
                    <td>{{ $department->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $department->faes_count }}</td>
                    <td><button>Edit</button></td>
                    <td><button>Delete</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
```

### 7.2 Grouped rendering approach

For category-like display, use a filtered selection in Blade:

```blade
@foreach($departments as $department)
    <div>
        <h3>{{ $department->department_name }}</h3>

        @php
            $filtered = $faes->where('department_id', $department->id);
        @endphp

        @foreach($filtered as $fae)
            <div>{{ $fae->name }}</div>
        @endforeach
    </div>
@endforeach
```

This gives the effect of department-specific groups without a category database table.

---

## 8. Validation Plan

### Department validation

```php
'name' => 'required|string|max:150|unique:departments,department_name',
```

For updates:

```php
'name' => 'required|string|max:150|unique:departments,department_name,' . $department->id,
```

No category validation is needed because no category table exists.

---

## 9. Filtering and Data Flow

The flow is simple:

1. Load all departments
2. User selects a department
3. Query related FAEs/tasks using `department_id`
4. Render only matching records
5. No extra categories table is required

This keeps the logic lighter and more maintainable.

---

## 10. Security and Data Safety

- Only admin users should access department management pages
- Use `admin.monitoring` middleware
- Prevent deleting departments with active linked records unless a safe cleanup is implemented
- Validate department names before saving

---

## 11. Recommended Order of Implementation

1. Create `DepartmentController`
2. Build `departments.index` view
3. Add department CRUD forms
4. Add filtered department-based lists in the FAE/task views
5. Use `department_id` to render grouped sections
6. Test admin flow and data consistency

---

## 12. Summary

Yes — this is possible without adding a new categories table.

The simpler and cleaner version is:

- departments remain the main lookup table
- filtering by department is handled in the controller and Blade rendering logic
- category-like display is just a filtered group of records using `department_id`

This is a good fit for the current system because it reduces database complexity while preserving a structured department-driven UI.

So the answer is: use the existing `departments` table as the source of truth, and implement category-like grouping as a rendering/filtering layer rather than a new schema table.
