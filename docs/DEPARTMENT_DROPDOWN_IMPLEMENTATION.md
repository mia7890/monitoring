# Department Dropdown Implementation Plan

This is the planned implementation for adding a dedicated department table and using it in the FAE page as a dropdown populated from the department name values.

> This document is a design/implementation plan only. No application code has been changed yet.

## Objective

- Create a separate `departments` table in SQL.
- Store department selections using a reference to that table instead of free-text input.
- Update the FAE add/edit page to read `department_name` from the `departments` table and render it as a dropdown list.
- Keep the flow compatible with the current FAE management forms.

## Current State

The current schema already includes a `department` field in `fae_users` in:

- `legacy/monitoring.sql`
- `legacy/db.php`
- `database/migrations/2026_09_01_000001_create_fae_users_table.php`

The existing FAE form currently uses a plain text input field for department in:

- `legacy/tasks.php`
- `resources/views/fae/index.blade.php`

## Recommended Database Design

Create a dedicated table for departments and link FAE records to it by department id.

```sql
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `department_name` VARCHAR(150) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_departments_name` (`department_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Then update the FAE table to reference the department table:

```sql
ALTER TABLE `fae_users`
    ADD COLUMN `department_id` INT UNSIGNED NULL AFTER `email`;

ALTER TABLE `fae_users`
    ADD CONSTRAINT `fk_fae_users_department`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;
```

### Optional Migration Safety

If there is already data in `fae_users.department`, keep it temporarily while migrating:

```sql
INSERT INTO `departments` (`department_name`)
SELECT DISTINCT TRIM(department)
FROM `fae_users`
WHERE department IS NOT NULL AND TRIM(department) <> ''
ON DUPLICATE KEY UPDATE `department_name` = `department_name`;

UPDATE `fae_users` f
JOIN `departments` d ON d.department_name = f.department
SET f.department_id = d.id
WHERE f.department IS NOT NULL AND TRIM(f.department) <> '';
```

## Seed Data Example

```sql
INSERT INTO `departments` (`department_name`) VALUES
('Engineering'),
('IT Support'),
('Field Service'),
('Automation'),
('Customer Support'),
('Operations');
```

## FAE Page Change

The FAE add/edit form should not use a free-text field like:

```html
<input type="text" name="department" ...>
```

Instead, populate a dropdown from the `departments` table:

```html
<select name="department_id" class="form-control" required>
    <option value="">Select Department</option>
    <option value="1">Engineering</option>
    <option value="2">IT Support</option>
    <option value="3">Field Service</option>
</select>
```

## Backend Logic

### Add FAE

The form should submit `department_id`, not raw text.

Example logic:

```php
$departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
```

Then insert it into the FAE record:

```php
INSERT INTO fae_users (name, fae_code, email, department_id, phone)
VALUES (?, ?, ?, ?, ?)
```

### Edit FAE

On edit, load the selected department id and preselect it in the dropdown.

```php
SELECT f.id, f.name, f.fae_code, f.email, f.phone, f.department_id
FROM fae_users f
WHERE f.id = ?
```

Then render:

```php
<option value="<?= (int)$row['id'] ?>" selected>
```

## Fetching Department List for Dropdown

Use a query similar to this:

```sql
SELECT id, department_name
FROM departments
WHERE is_active = 1
ORDER BY department_name ASC;
```

Then build the output in PHP or Blade/Laravel:

```php
$stmt = $pdo->query("SELECT id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name ASC");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

Laravel example:

```php
$departments = Department::where('is_active', true)
    ->orderBy('department_name')
    ->get();
```

In the view:

```blade
<select name="department_id" class="form-control" required>
    <option value="">Select Department</option>
    @foreach($departments as $department)
        <option value="{{ $department->id }}">
            {{ $department->department_name }}
        </option>
    @endforeach
</select>
```

## Recommended FAE Form Behavior

For the FAE page, the form should:

1. Query the `departments` table.
2. Read `department_name` values.
3. Render them in a dropdown.
4. Save the selected department as `department_id`.
5. Reuse the same dropdown on add and edit forms.

## Final Recommended Structure

### `departments` table

- `id`
- `department_name`
- `is_active`
- `created_at`

### `fae_users` table

- `id`
- `name`
- `fae_code`
- `email`
- `department_id`
- `phone`
- `profile_image`

This keeps the FAE record normalized and ensures the dropdown is always driven by a controlled list of departments.

## Files Likely to Be Updated Later

- `legacy/monitoring.sql`
- `legacy/tasks.php`
- `resources/views/fae/index.blade.php`
- `app/Http/Controllers/FaeController.php` (if Laravel controller is used)
- `database/migrations/2026_09_01_000001_create_fae_users_table.php`

## Implementation Notes

- The current `department` string column can remain temporarily for backward compatibility.
- Best long-term approach is to migrate to `department_id` + `departments` table.
- The dropdown should always show readable names from `department_name` and submit the matching numeric id.

This plan is intentionally staged so the SQL schema and FAE form can be changed safely without breaking existing data.
