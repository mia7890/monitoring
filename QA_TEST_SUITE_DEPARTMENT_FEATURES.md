# Department Management - Comprehensive QA Test Suite

**Feature:** Department Page with Inline Expandable Member List  
**Test Date:** 2026-09-02  
**Environment:** MySQL, Laravel 11, Chrome/Firefox/Safari

---

## Test Case Matrix

| Test ID | Test Title | Preconditions | Test Steps | Expected Result | Priority |
|---------|-----------|---------------|-----------|-----------------|----------|
| TC-DEP-001 | View department page with departments | Admin user logged in, 3+ departments exist with members | 1. Navigate to /departments | Department list displays in table format with columns: Department, Status, FAEs, Actions | P0 |
| TC-DEP-002 | View department page with no departments | Admin user logged in, no departments exist | 1. Navigate to /departments | Empty state message "No departments created yet." displays | P1 |
| TC-DEP-003 | Expand department with members | Department page loaded, Department "Engineering" has 3 members | 1. Click on "Engineering" row | Member expansion row appears below, shows 3 member badges with profile images/initials | P0 |
| TC-DEP-004 | Expand department without members | Department page loaded, Department "Archive" has 0 members | 1. Click on "Archive" row (no members) | Department row does not expand; no "Click to view members" hint shown | P1 |
| TC-DEP-005 | Collapse expanded department | Department page loaded, "Engineering" department expanded showing members | 1. Click on expanded "Engineering" row | Member panel disappears; row background returns to normal; FAE count still visible | P0 |
| TC-DEP-006 | Single-expand behavior (open second dept) | Department page loaded, "Engineering" department expanded | 1. Click on "Operations" department row | "Engineering" auto-collapses; "Operations" expands showing its members | P0 |
| TC-DEP-007 | Single-expand behavior (close first dept) | Department page loaded, "Operations" department expanded | 1. Click on "Operations" row again | "Operations" collapses; no other department opens | P0 |
| TC-DEP-008 | View member badge with all info | Department expanded with members | 1. Observe member badge for "John Doe" (FAE-001, john@example.com) | Badge shows: profile image/initial, name "John Doe", code "FAE-001", email "john@ex..." | P0 |
| TC-DEP-009 | View member badge without email | Department expanded, member has no email stored | 1. Observe member badge for user with NULL email | Badge shows: profile image/initial, name, code; email field is blank/omitted | P1 |
| TC-DEP-010 | View member badge with profile image | Department expanded, member has profile image uploaded | 1. Observe member badge for "Jane Smith" with profile pic | Profile image displays correctly; image is circular; no broken image icon | P0 |
| TC-DEP-011 | View member badge without profile image | Department expanded, member has no profile image | 1. Observe member badge for user without image | Avatar initial displays (first letter of name in circle); no broken image icon | P1 |
| TC-DEP-012 | Email truncation on badge | Department expanded, member with long email | 1. Observe email field on member badge | Email truncated to ~20 chars; "..." shown at end; full email visible on hover tooltip | P1 |
| TC-DEP-013 | Mobile view - single column layout | Department page on mobile device (< 768px) | 1. Access department page on mobile | Members displayed in single column; full-width badges; no horizontal scroll | P1 |
| TC-DEP-014 | Tablet view - responsive layout | Department page on tablet (768px - 1199px) | 1. Access department page on tablet | Members displayed in 2-3 column grid; badges fit screen width | P1 |
| TC-DEP-015 | Desktop view - responsive layout | Department page on desktop (> 1200px) | 1. Access department page on desktop | Members displayed in 3-4 column grid; clean spacing | P1 |
| TC-DEP-016 | Add department without members | Admin logged in, department page loaded | 1. Fill "Department name" with "Marketing"; check "Active" 2. Click "＋ Add department" | Department created; new row added to table; FAE count = 0; "Click to view members" not shown | P0 |
| TC-DEP-017 | Add department (blank name) | Admin logged in, department page loaded | 1. Leave department name empty 2. Click "＋ Add department" | Form validation error displays; department not created | P0 |
| TC-DEP-018 | Add department (duplicate name) | Admin logged in, "Engineering" department already exists | 1. Fill department name with "Engineering" 2. Click "＋ Add department" | Validation error "Engineering already exists"; department not created | P0 |
| TC-DEP-019 | Add department (max length name) | Admin logged in, department page loaded | 1. Fill name with 150-char string 2. Click "＋ Add department" | Department created successfully; full name stored and displayed | P1 |
| TC-DEP-020 | Add department (name > max length) | Admin logged in, department page loaded | 1. Fill name with 151+ char string 2. Click "＋ Add department" | Validation error; department not created | P1 |
| TC-DEP-021 | Add department (inactive) | Admin logged in, department page loaded | 1. Fill name with "Archived" 2. Uncheck "Active" 3. Click "＋ Add department" | Department created with is_active = 0; "Inactive" status badge shown | P1 |
| TC-DEP-022 | Edit department name (valid) | Department "Engineering" exists, admin logged in | 1. Click row to expand 2. Edit inline form: change "Engineering" to "Software Engineering" 3. Click "Save" | Department name updated in database; table refreshes; members still visible | P0 |
| TC-DEP-023 | Edit department name (empty) | Department "Engineering" exists with inline edit form | 1. Clear department name field 2. Click "Save" | Validation error; name not changed | P0 |
| TC-DEP-024 | Edit department name (duplicate) | Departments "Engineering" and "Operations" exist | 1. Edit "Engineering" name to "Operations" 2. Click "Save" | Validation error "Operations already exists"; no change made | P0 |
| TC-DEP-025 | Edit department status to inactive | Department "Engineering" is active | 1. Uncheck "Active" checkbox in edit form 2. Click "Save" | Department status changes to "Inactive"; badge color changes; members still displayable | P1 |
| TC-DEP-026 | Edit department status to active | Department "Engineering" is inactive | 1. Check "Active" checkbox in edit form 2. Click "Save" | Department status changes to "Active"; badge color changes | P1 |
| TC-DEP-027 | Delete department (no members) | Department "Archive" exists with 0 members | 1. Click "Delete" button 2. Confirm dialog | Department deleted; row removed from table; success message shown | P0 |
| TC-DEP-028 | Delete department (with members) | Department "Engineering" exists with 3 members | 1. Click "Delete" button 2. Confirm dialog | Error message "This department still has FAE records assigned to it."; department not deleted | P0 |
| TC-DEP-029 | Delete department (confirm dialog) | Department "Archive" exists, delete button clicked | 1. Click "Delete" 2. Cancel dialog | Deletion cancelled; department remains in table | P1 |
| TC-DEP-030 | Member list order (alphabetical) | Department with members: Bob, Alice, Charlie | 1. Expand department | Members displayed in alphabetical order: Alice, Bob, Charlie (by name) | P1 |
| TC-DEP-031 | Click dept row on non-clickable area | Department with members expanded | 1. Click on "Status" cell value "Active" | No expand/collapse triggered; row stays expanded | P1 |
| TC-DEP-032 | Click edit form (don't expand) | Department row with edit form inline | 1. Click on form input field (department name) | Form field focused; no row expansion triggered | P0 |
| TC-DEP-033 | Click edit button (don't expand) | Department row with edit form | 1. Click on "Save" button | Form submitted; no unwanted expand/collapse | P0 |
| TC-DEP-034 | Click delete button (don't expand) | Department row with delete form | 1. Click on "Delete" button | Confirmation dialog shows; no unwanted expand/collapse | P0 |
| TC-DEP-035 | Click member badge (interactable) | Department expanded with member badges | 1. Click on a member badge | Badge has hover state; cursor shows pointer; click doesn't cause action (prepared for future features) | P2 |
| TC-DEP-036 | Prevent form submission on row click | Department with edit form visible | 1. Click anywhere on the department row EXCEPT form 2. Edit form should still be usable 3. Click "Save" | Form submits normally; edit succeeds; no accidental cancellation | P0 |
| TC-DEP-037 | Many departments (10+) | Page loaded with 15 departments | 1. Expand and collapse several departments | All expand/collapse works; no performance issues; no lag | P1 |
| TC-DEP-038 | Many members in one dept | Department with 20+ members | 1. Expand department with 20+ members | All members display in grid; responsive wrapping; no cut-off members | P1 |
| TC-DEP-039 | Member with special characters | Member name: "José García", code "FAE-QA1" | 1. Expand department | Name and code display correctly; no encoding issues; special chars visible | P1 |
| TC-DEP-040 | Department name with special chars | Department name: "R&D / Research" | 1. Navigate to departments page | Name displays correctly; no HTML injection; no broken layout | P0 |
| TC-DEP-041 | Browser back/forward button | Department expanded; navigate away and back | 1. Expand department 2. Navigate to /dashboard 3. Click browser back button | Department page loads; expansion state NOT persisted (refresh clears); members need to be re-expanded | P2 |
| TC-DEP-042 | Page refresh (F5) | Department expanded | 1. Expand department 2. Press F5 | Page refreshes; all departments collapse; expansion state lost (expected) | P1 |
| TC-DEP-043 | Keyboard accessibility - Tab navigation | Department page | 1. Use Tab key to navigate through rows 2. Press Enter on department row | Focus moves through elements; Enter on clickable areas triggers action; accessibility maintained | P1 |
| TC-DEP-044 | Department count accuracy | 5 departments exist in database | 1. View department page header | Count shows "5 department entries" accurately | P0 |
| TC-DEP-045 | FAE count accuracy | Engineering department has 3 members in DB | 1. Expand Engineering department | Table shows FAE count = 3; expanded list shows exactly 3 members | P0 |
| TC-DEP-046 | Member list matches database | Database has: John (FAE-001), Jane (FAE-002) in Engineering | 1. Expand Engineering department | Exactly John and Jane appear; no extra/missing members | P0 |
| TC-DEP-047 | Inactive department display | Department "Archive" with is_active = 0 | 1. View departments page | Status badge shows "Inactive" with muted color; still expandable if has members | P1 |
| TC-DEP-048 | Admin access only (non-admin blocked) | Non-admin user (FAE user) logged in | 1. Navigate to /departments | Access denied; 403 Forbidden or redirect to dashboard | P0 |
| TC-DEP-049 | Session persistence | Admin user logged in, performing operations | 1. Expand department, edit, add new 2. All operations complete | User stays logged in; session maintained; no unexpected logouts | P1 |
| TC-DEP-050 | Concurrent expansion attempts | Same browser, rapid clicks on multiple rows | 1. Rapidly click: Engineering, Operations, Marketing | Only last clicked department expands; no race conditions; data consistent | P1 |
| TC-DEP-051 | Member with very long name | Member: "Alexander Jonathan Christopher Williams-Smith" | 1. Expand department | Name displays completely or with ellipsis; layout not broken; no overflow | P1 |
| TC-DEP-052 | Member email validation display | Member with invalid email format stored (old data) | 1. Expand department | Email displays as-is; no validation errors on display; text shown safely | P2 |
| TC-DEP-053 | Empty department then add members | Department "NewDept" created with 0 members | 1. Expand NewDept (no members case) 2. Separately add FAE to NewDept 3. Refresh and expand | Initial: no badge, no "Click to view" hint. After FAE added: badges appear on refresh | P2 |
| TC-DEP-054 | Remove all members from department | Engineering has 3 members, remove all via FAE page | 1. From FAE page, unassign all members from Engineering 2. Navigate to departments, refresh 3. Click Engineering | Department row shows "0" FAEs; expansion shows "No members assigned" message | P1 |
| TC-DEP-055 | Member reassignment between departments | John assigned to Engineering, reassign to Operations | 1. From FAE page, change John's department to Operations 2. Navigate to departments 3. Expand Engineering then Operations | John disappears from Engineering; appears in Operations | P1 |
| TC-DEP-056 | Add department then immediately expand | Add new department "QA" with member already assigned | 1. Add department 2. Assign John to QA from FAE page 3. Refresh departments 4. Expand QA | Members appear immediately after refresh | P1 |
| TC-DEP-057 | Edit form persists on page load | Department edit form opened | 1. Make changes to edit form (don't save yet) 2. Another admin adds a department on different tab 3. Return to original tab | Edit form changes still visible (form state preserved client-side) | P2 |
| TC-DEP-058 | Navigation from dept to FAE page | Admin on departments page | 1. Click "Back to FAE" link in page header | Navigate to /fae (FAE management page); no errors | P0 |
| TC-DEP-059 | Navigation from FAE to dept page | Admin on FAE page | 1. Click "Departments" link in sidebar | Navigate to /departments; no errors; all departments load | P0 |
| TC-DEP-060 | Success message display | Department added successfully | 1. Add new department 2. Page refreshes | Success flash message "Department created successfully." displays briefly | P1 |
| TC-DEP-061 | Error message display | Attempt to delete department with members | 1. Click delete on department with members 2. Confirm | Error message "This department still has FAE records assigned to it." displays | P0 |
| TC-DEP-062 | Member profile image placeholder | Multiple members without images | 1. Expand department with 3 members (no images) | Each shows correct initial: John → "J", Alice → "A", Bob → "B" | P1 |
| TC-DEP-063 | Mixed member images | Some members with images, some without | 1. Expand department with 5 members (2 with images, 3 without) | Images display for 2; initials for 3; consistent styling | P1 |
| TC-DEP-064 | Broken profile image fallback | Member image URL broken/404 | 1. Expand department with broken image member | Broken image icon does NOT appear; falls back to initial or default | P1 |
| TC-DEP-065 | Scroll within expanded member list | Department with 20+ members | 1. Expand department 2. Scroll within member grid | All members accessible via scroll; no content cut off; responsive layout maintained | P1 |
| TC-DEP-066 | No console errors on expand/collapse | Developer console open | 1. Expand department 2. Collapse department 3. Refresh | No JavaScript errors in console; no warnings about missing DOM elements | P1 |
| TC-DEP-067 | Accessibility - Screen reader | Using screen reader (NVDA/JAWS) | 1. Read through department table 2. Navigate to member badges | Department name, status, count announced; member info readable (name, code) | P2 |
| TC-DEP-068 | Member count in different scenarios | Multiple department scenarios | 1. Check dept with 1 member, 5 members, 0 members | Count always accurate; "FAE" vs "FAEs" plural handling correct | P1 |
| TC-DEP-069 | Edit form validation in real-time | Department edit form open | 1. Type in name field; exceed max length 2. Clear name field | Validation feedback shown; Save button behavior consistent | P2 |
| TC-DEP-070 | Cascade delete prevention | Department with members exists | 1. Attempt delete via form 2. Check database | Database prevents cascade; department remains; error shown to user; referential integrity maintained | P0 |

---

## Test Execution Summary

**Total Test Cases:** 70  
**Priority Breakdown:**
- **P0 (Critical):** 22 tests (must pass before release)
- **P1 (High):** 37 tests (should pass before release)
- **P2 (Medium):** 11 tests (nice to have; can defer)

---

## Test Scenarios by Category

### Core Functionality (TC-DEP-001 to TC-DEP-015)
Covers: Page load, expand/collapse, member display, responsive design

### Department CRUD (TC-DEP-016 to TC-DEP-031)
Covers: Create, read, update, delete departments with validation

### Click Behavior & Form Handling (TC-DEP-032 to TC-DEP-037)
Covers: Proper event handling, form submission, no unwanted triggers

### Performance & Scale (TC-DEP-038 to TC-DEP-042)
Covers: Many departments, many members, browser actions

### Data Integrity (TC-DEP-043 to TC-DEP-070)
Covers: Accuracy, reassignment, special characters, accessibility, error handling

---

## Regression Test Suite (P0 + Critical P1)

Run these on every build:
- TC-DEP-001 (page loads)
- TC-DEP-003 (expand with members)
- TC-DEP-005 (collapse)
- TC-DEP-006 (single expand)
- TC-DEP-008 (member badge data)
- TC-DEP-016 (add department)
- TC-DEP-022 (edit department)
- TC-DEP-027 (delete no members)
- TC-DEP-028 (delete with members)
- TC-DEP-032 (edit form click)
- TC-DEP-044 (count accuracy)
- TC-DEP-048 (auth check)
- TC-DEP-070 (cascade prevent)

---

## Notes for QA

1. **Test data:** Ensure test database has departments with varying member counts (0, 1, 5, 20+)
2. **Browsers:** Test on Chrome, Firefox, Safari, Edge (latest versions)
3. **Screen sizes:** Desktop (1920x1080), Tablet (768x1024), Mobile (375x667)
4. **Automation:** UI tests (expand/collapse, member display) can be automated with Selenium/Playwright
5. **API tests:** Create/update/delete can be tested via API directly (PHPUnit)
6. **Performance:** Monitor page load time with 100+ departments

---

**Test Suite Version:** 1.0  
**Last Updated:** 2026-09-02
