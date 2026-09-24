# Phase 3 Frontend Foundation and Expense Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the responsive Vue 3 application for authentication, dashboard summaries, expense CRUD, and admin category management against the existing Laravel API.

**Architecture:** Laravel serves one Blade application shell and Vue Router handles client routes. A centralized Axios client calls the existing bearer token API; Pinia owns authentication while page-local state owns filters and forms. Shared UI components follow shadcn-vue composition patterns with Tailwind CSS and accessible semantics.

**Tech Stack:** Vue 3, TypeScript, Vite 8, Tailwind CSS 4, Vue Router, Axios, Pinia, Reka UI, Lucide Vue, Vitest, Vue Test Utils.

**Spec:** `C:/Users/mehra/.codex/attachments/c87a84fb-f923-4f4f-8aac-fd87a5469c9f/pasted-text.txt`

## Global Constraints

- Preserve the existing API and backend authorization.
- Keep Phase 4 reporting, import, export, audit, users, and settings UI out of scope.
- Use Composition API with `<script setup lang="ts">` and avoid `any`.
- Use server-side filtering and pagination.
- Support desktop through 375px mobile layouts.
- Store date-only values without timezone conversion.
- Store bearer tokens in session storage and clear them on HTTP 401.

## Review Focus

- Revoked tokens redirect to login without rendering protected content.
- Date-only values display on the original calendar date.
- Staff users cannot see category management or delete controls.
- Filters survive pagination and search is debounced.
- Laravel 422 errors map to controls and submissions cannot repeat.

## Tasks

- [ ] **Task 1:** Configure Vue, TypeScript, Vite, Tailwind, Vitest, and the Laravel SPA shell; prove the shell route with a feature test.
- [ ] **Task 2:** Implement typed API services, Pinia authentication, session token lifecycle, login UI, and protected routes; test successful and failed login plus route protection.
- [ ] **Task 3:** Build accessible shadcn-style UI primitives, toast handling, formatters, and responsive role-aware application navigation; test formatting and role visibility.
- [ ] **Task 4:** Build the database-driven dashboard and monthly selector using `/api/dashboard` and `/api/reports/monthly`; test loading and selector refresh.
- [ ] **Task 5:** Build the expense list with server filtering, debounced search, sorting, pagination, detail view, and admin delete confirmation; test rendering, filtering, role visibility, and deletion.
- [ ] **Task 6:** Build reusable expense create/edit forms with active categories, local input checks, Laravel 422 mapping, loading states, and navigation; test validation, create, fetch, and update.
- [ ] **Task 7:** Build admin category create, edit, and deactivate dialogs with backend validation; test admin behavior and staff route rejection.
- [ ] **Task 8:** Update README and run Vitest, vue-tsc, Vite build, PHPUnit, Pint, and a manual browser workflow.
