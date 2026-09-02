# FAE Page Normalization Plan

## Executive Summary
The FAE system currently maintains two sources of truth for department data:
- `department_id` (relational, modern, correct)
- `department` (legacy text field, should be removed)

This creates ambiguity, redundancy, and risk. This plan normalizes the FAE system to use only the relational model.

---

## 1. Current State Analysis

### What exists now
| Component | File | Status |
|-----------|------|--------|
| departments table | `database/migrations/2026_09_02_000001_create_departments_table.php` | ✅ Active |
| fae_users.department_id column | Migration | ✅ Active |
| fae_users.department column (text) | Legacy field | ⚠️ Still in schema |
| FaeUser model relations | `app/Models/FaeUser.php` | ✅ Correct |
| Department model | `app/Models/Department.php` | ✅ Correct |
| FaeController logic | `app/Http/Controllers/FaeController.php` | ⚠️ Hybrid (accepts both fields) |
| FAE view form | `resources/views/fae/index.blade.php` | ✅ Uses dropdown (department_id) |
| FAE display logic | View + Controller | ⚠️ Fallback chain (id → text → default) |

### The Problem
```php
// Current FaeController::store() and update()
$data = [
    'department_id' => $request->input('department_id') ?: null,  // Modern
    'department' => $request->input('department') ?: null,        // Legacy
];

// Current display in view
$fae->department_name = $fae->department?->department_name ?? $fae->department ?? 'Field Engineering';
```

This means:
- If department_id is NULL, the view falls back to the text field
- If both are NULL, the view shows a hardcoded default
- Old data may still have values in the legacy field
- New data is stored in both columns (redundant)
- The source of truth is unclear

---

## 2. Normalization Strategy

### Phase 1: Data Migration (No code changes yet)
**Goal:** Move all legacy `department` text values into the `departments` table and update all FAE records.

#### Step 1.1: Create migration to backfill existing departments
```php
// This migration will:
// 1. Find all unique department text values in fae_users
// 2. Create a department record for each (if not already present)
// 3. Update fae_users.department_id to match
// 4. Verify all fae_users have a valid department_id
```

#### Step 1.2: Validation check
Before proceeding, verify:
- All non-NULL fae_users.department values have corresponding departments table records
- All fae_users with department text now also have department_id set
- No fae_users have department_id = NULL (or set them to a default)

### Phase 2: Code Cleanup (Remove legacy support)

#### Step 2.1: Update FaeController
- Remove `department` field from validation
- Remove `department` from fillable array in store/update methods
- Keep only `department_id`

#### Step 2.2: Update FaeUser model
- Remove `department` from $fillable array
- Keep only `department_id`

#### Step 2.3: Update FAE view
- Remove fallback logic
- Display only: `{{ $fae->department?->department_name ?? 'Unassigned' }}`

#### Step 2.4: Update FaeController::index()
```php
// Current
$fae->department_name = $fae->department?->department_name ?? $fae->department ?? 'Field Engineering';

// After normalization
$fae->department_name = $fae->department?->department_name ?? 'Unassigned';
```

### Phase 3: Schema Cleanup (Remove old column)

#### Step 3.1: Create migration to drop department column
```php
// Migration to drop fae_users.department column
// Only after Phase 1 and Phase 2 are complete
```

---

## 3. Implementation Steps

### Step A: Backfill departments from existing FAE data

**File to create:**
`database/migrations/2026_09_02_000002_backfill_fae_departments.php`

**Logic:**
```php
public function up(): void
{
    // 1. Get all unique department text values
    $uniqueDepts = DB::table('fae_users')
        ->whereNotNull('department')
        ->distinct()
        ->pluck('department')
        ->filter()
        ->values();

    // 2. For each unique department, ensure a record exists
    foreach ($uniqueDepts as $deptName) {
        DB::table('departments')
            ->updateOrCreate(
                ['department_name' => $deptName],
                ['is_active' => true]
            );
    }

    // 3. Update fae_users to populate department_id from text value
    foreach ($uniqueDepts as $deptName) {
        $deptId = DB::table('departments')
            ->where('department_name', $deptName)
            ->value('id');

        DB::table('fae_users')
            ->where('department', $deptName)
            ->update(['department_id' => $deptId]);
    }

    // 4. For any FAE still without a department_id, assign a default
    $defaultDept = DB::table('departments')
        ->where('department_name', 'General')
        ->first();

    if (!$defaultDept) {
        $defaultDeptId = DB::table('departments')
            ->insertGetId([
                'department_name' => 'General',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    } else {
        $defaultDeptId = $defaultDept->id;
    }

    DB::table('fae_users')
        ->whereNull('department_id')
        ->update(['department_id' => $defaultDeptId]);
}
```

### Step B: Update FaeController to use only department_id

**File:** `app/Http/Controllers/FaeController.php`

**Changes:**
1. In store():
   - Remove `'department' => 'nullable|string|max:255',` from validation
   - Remove `'department' => $request->input('department') ?: null,` from data array

2. In update():
   - Same as store()

3. In index():
   - Change: `$fae->department_name = $fae->department?->department_name ?? $fae->department ?? 'Field Engineering';`
   - To: `$fae->department_name = $fae->department?->department_name ?? 'Unassigned';`

### Step C: Update FaeUser model

**File:** `app/Models/FaeUser.php`

**Changes:**
- Remove `'department',` from $fillable array
- Keep only relational fields

### Step D: Update FAE view

**File:** `resources/views/fae/index.blade.php`

**Changes:**
- The form already uses `department_id` dropdown ✅
- The display already uses `$fae->department_name` ✅
- No changes needed in the view (it's already correct)

### Step E: Drop the legacy column

**File to create:**
`database/migrations/2026_09_02_000003_drop_fae_department_column.php`

**Logic:**
```php
public function up(): void
{
    if (Schema::hasTable('fae_users') && Schema::hasColumn('fae_users', 'department')) {
        Schema::table('fae_users', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
}

public function down(): void
{
    if (Schema::hasTable('fae_users') && !Schema::hasColumn('fae_users', 'department')) {
        Schema::table('fae_users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('email');
        });
    }
}
```

---

## 4. Validation Checklist

After implementation, verify:
- [ ] All FAE records have a valid department_id
- [ ] No FAE records have NULL department_id (or all NULLs are intentional)
- [ ] Department dropdown on FAE page shows all active departments
- [ ] Adding a new FAE requires selecting a department
- [ ] Editing an FAE allows changing the department
- [ ] FAE card displays the correct department name
- [ ] Tests pass after changes
- [ ] No SQL errors when loading FAE page

---

## 5. Rollback Plan

If issues arise:
1. Keep the old `department` column during Phase 1-2 testing
2. Only drop it after verification that Phase 2 works correctly
3. If rollback needed, restore the old migration that drops the column

---

## 6. Risk Assessment

| Risk | Likelihood | Mitigation |
|------|-----------|-----------|
| Old data not migrated | Low | Backfill migration checks and maps all values |
| FAE with no department | Medium | Default to "General" department |
| View breaks after column drop | Low | Already uses department_id relation |
| Foreign key constraint violation | Low | Backfill ensures all IDs exist before drop |

---

## 7. Benefits After Normalization

- ✅ Single source of truth (department_id only)
- ✅ Cleaner code (no fallback chains)
- ✅ Stronger data integrity (foreign key enforced)
- ✅ Easier to extend (add department features without checking two fields)
- ✅ Reduced storage (one field instead of two)
- ✅ Better query performance (no need to check text field)

---

## 8. Next Steps

1. Review this plan with the team
2. Run Phase 1 migration (backfill)
3. Test the FAE page to ensure departments display correctly
4. Run Phase 2 code changes
5. Run tests to verify no regressions
6. Run Phase 3 migration (drop column)
7. Final verification and deployment

---

**Status:** Ready for approval and implementation
**Estimated time:** 1-2 hours
**Complexity:** Low (straightforward data cleanup)
