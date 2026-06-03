# Task Operations — Arabic Browser QA Report

**Date:** 2026-05-31  
**Locale:** Arabic (`?lang=ar`)  
**Server:** http://127.0.0.1:8000  
**Login:** nodhumtech@gmail.com  

---

## How to follow along

Screenshots from each step are saved under:

```
dogfood-output/tasks-ar/screenshots/
```

They also appear inline in the Cursor chat as the agent runs browser automation.

To watch live in your own browser, open the same URLs while tests run (append `?lang=ar`).

---

## Summary

| Page | URL | Result |
|------|-----|--------|
| Task Operations Hub | `/admin/projects/task-hub?lang=ar` | ✅ Pass |
| Operations Calendar | `/admin/projects/task-hub/calendar?lang=ar` | ✅ Pass |
| Kanban Board | `/admin/projects/task-hub/kanban?lang=ar` | ✅ Pass |
| Task list | `/admin/project/tasks?lang=ar` | ✅ Pass |
| Create task | `/admin/project/tasks/create?lang=ar` | ✅ Pass |

---

## Findings

### ✅ RTL & Arabic translations

- Task Hub title: **مركز عمليات المهام**
- Navigation: **مركز المهام**, **كانبان**, **تقويم**
- Chart legend uses unified labels: **قيد الانتظار**, **معلقة**, **مكتملة**, etc.
- Create form state buttons show unified Arabic labels:
  - `approved` → **قيد الانتظار** (Pending)
  - `change_requested` → **معلقة** (On Hold)
  - `done` → **مكتملة** (Completed)

### ✅ Task Operations Hub

- Stats widgets render in Arabic
- View switcher links work (hub → kanban, hub → calendar)
- Screenshot: `01-task-hub-ar.png`

### ✅ Operations Calendar

- Title: **تقويم العمليات**
- FullCalendar month view in Arabic (مايو 2026)
- View toggles: شهر / أسبوع / يوم / قائمة
- Screenshot: `03-calendar-ar.png`

### ✅ Kanban board

- Loads at `/admin/projects/task-hub/kanban` (aligned with hub and calendar)
- Empty state message: **لم يتم إعداد مراحل المهام بعد.** (no task stages seeded)
- Header links to hub and calendar work
- Screenshot: `02-kanban-ar.png`

### ✅ Extended task list columns

Arabic column headers present: **الأولوية**, **الحالة**, **المسؤول**, **التصنيف**, **القسم**, **متأخرة**

Screenshot: `04-task-list-ar.png`

### ✅ Create task form

- Default/selected state shows **قيد الانتظار** (Pending)
- Extended fields visible in form sections
- Screenshot: `05-task-create-ar.png`

---

## Bugs / follow-ups

1. **No task stages** — Kanban empty state; seed or create task stages in Project → Configurations for a full drag-and-drop test.

2. **Page title "Tasks"** — Task list breadcrumb/title still English on Arabic locale (core Webkul string, not overridden).

3. **Performance** — Debug bar shows 150+ queries and ~10k models on hub/calendar pages (pre-existing pattern; worth optimizing later).

---

## Screenshots

| File | Page |
|------|------|
| `01-task-hub-ar.png` | Task Operations Hub |
| `02-kanban-ar.png` | Kanban board |
| `03-calendar-ar.png` | Operations calendar |
| `04-task-list-ar.png` | Task list with extended columns |
| `05-task-create-ar.png` | Create task — unified state labels |
