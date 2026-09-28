import { formatDate, formatMoney } from './formatters';
import type { AuditLog, Category, User } from '../types';

export interface AuditChange {
    field: string;
    label: string;
    before: string | null;
    after: string | null;
}

export interface AuditPresentation {
    title: string;
    summary: string;
    changes: AuditChange[];
}

const FIELD_LABELS: Record<string, string> = {
    expense_date: 'Expense Date',
    description: 'Description',
    amount: 'Amount',
    category_id: 'Category',
    payment_status: 'Payment Status',
    payment_method: 'Payment Method',
    created_by: 'Added By',
    updated_by: 'Updated By',
    uploaded_by: 'Added By',
    name: 'Name',
    email: 'Email',
    role: 'Role',
    is_active: 'Status',
    original_name: 'File Name',
    imported_rows: 'Rows Imported',
    source_name: 'File',
};

const FIELD_ORDER = [
    'name', 'email', 'role', 'is_active',
    'expense_date', 'description', 'amount', 'category_id', 'payment_status', 'payment_method',
    'created_by', 'updated_by', 'uploaded_by', 'original_name',
    'source_name', 'imported_rows',
];

const HIDDEN_FIELDS = new Set(['expense_id', 'storage_path', 'password', 'remember_token', 'token']);

const PAYMENT_STATUS_LABELS: Record<string, string> = {
    paid: 'Paid',
    unpaid: 'Unpaid',
    pending: 'Pending',
};

export function presentAuditLog(log: AuditLog, users: User[], categories: Category[]): AuditPresentation {
    const userNames = new Map(users.map((user) => [user.id, user.name]));
    if (log.actor) {
        userNames.set(log.actor.id, log.actor.name);
    }
    const categoryNames = new Map(categories.map((category) => [category.id, category.name]));
    const actor = log.actor?.name ?? 'System';

    return {
        title: titleFor(log),
        summary: summaryFor(log, actor, categoryNames),
        changes: changesFor(log, userNames, categoryNames),
    };
}

function titleFor(log: AuditLog): string {
    const role = roleTitle(log);
    const titles: Record<string, string> = {
        'user.created': `${role} Added`,
        'user.updated': `${role} Updated`,
        'expense.created': 'Expense Added',
        'expense.updated': 'Expense Updated',
        'expense.deleted': 'Expense Deleted',
        'category.created': 'Category Added',
        'category.updated': 'Category Updated',
        'category.deleted': 'Category Deleted',
        'attachment.created': 'Receipt Added',
        'attachment.deleted': 'Receipt Removed',
        'import.completed': 'Workbook Imported',
    };

    return titles[log.action] ?? readableAction(log.action);
}

function summaryFor(log: AuditLog, actor: string, categoryNames: Map<number, string>): string {
    const current = log.new_values ?? log.old_values;

    switch (log.action) {
        case 'user.created':
            return `${actor} added a new ${rolePhrase(current)}.`;
        case 'user.updated':
            return userUpdatedSummary(log, actor);
        case 'expense.created':
            return expenseAddedSummary(actor, log.new_values, categoryNames);
        case 'expense.updated':
            return namedRecordSummary(actor, 'updated', 'expense', readText(log.new_values, 'description'));
        case 'expense.deleted':
            return namedRecordSummary(actor, 'deleted', 'expense', readText(log.old_values, 'description'));
        case 'category.created':
            return categorySummary(actor, 'created', readText(log.new_values, 'name'));
        case 'category.updated':
            return categorySummary(actor, 'updated', readText(log.new_values, 'name'));
        case 'category.deleted':
            return categorySummary(actor, 'deleted', readText(log.old_values, 'name'));
        case 'attachment.created':
            return namedRecordSummary(actor, 'added', 'file', readText(log.new_values, 'original_name'));
        case 'attachment.deleted':
            return namedRecordSummary(actor, 'removed', 'file', readText(log.old_values, 'original_name'));
        case 'import.completed':
            return importSummary(actor, log.new_values);
        default:
            return `${actor} — ${readableAction(log.action)}.`;
    }
}

function userUpdatedSummary(log: AuditLog, actor: string): string {
    const name = readText(log.new_values, 'name') || readText(log.old_values, 'name') || 'a user';
    if (onlyActiveStatusChanged(log.old_values, log.new_values)) {
        const active = isActiveValue(log.new_values?.is_active);
        return `${actor} ${active ? 'activated' : 'deactivated'} ${name}.`;
    }

    return `${actor} updated ${name}.`;
}

function expenseAddedSummary(actor: string, values: Record<string, unknown> | null, categoryNames: Map<number, string>): string {
    const amount = hasValue(values?.amount) ? formatAmount(values?.amount) : null;
    const category = categoryPhrase(values, categoryNames);
    if (amount && category) {
        return `${actor} added an expense of ${amount} for ${category}.`;
    }
    if (amount) {
        return `${actor} added an expense of ${amount}.`;
    }
    if (category) {
        return `${actor} added an expense for ${category}.`;
    }

    return `${actor} added an expense.`;
}

function importSummary(actor: string, values: Record<string, unknown> | null): string {
    const source = readText(values, 'source_name');
    const rows = values?.imported_rows;
    const count = typeof rows === 'number' || typeof rows === 'string' ? Number(rows) : null;
    const rowText = count !== null && !Number.isNaN(count) ? (count === 1 ? '1 row' : `${count} rows`) : null;
    if (rowText && source) {
        return `${actor} imported ${rowText} from "${source}".`;
    }
    if (rowText) {
        return `${actor} imported ${rowText}.`;
    }
    if (source) {
        return `${actor} imported "${source}".`;
    }

    return `${actor} imported a workbook.`;
}

function changesFor(log: AuditLog, userNames: Map<number, string>, categoryNames: Map<number, string>): AuditChange[] {
    const before = log.old_values;
    const after = log.new_values;
    const keys = orderedKeys([
        ...Object.keys(before ?? {}),
        ...Object.keys(after ?? {}),
    ].filter((key) => !HIDDEN_FIELDS.has(key)));
    const creating = before === null;
    const deleting = after === null;
    const rows: AuditChange[] = [];

    for (const key of keys) {
        const beforeRaw = before?.[key];
        const afterRaw = after?.[key];
        if (creating && !hasValue(afterRaw)) {
            continue;
        }
        if (deleting && !hasValue(beforeRaw)) {
            continue;
        }

        const beforeText = before === null ? null : formatField(key, beforeRaw, userNames, categoryNames);
        const afterText = after === null ? null : formatField(key, afterRaw, userNames, categoryNames);
        if (beforeText !== null && afterText !== null && beforeText === afterText) {
            continue;
        }

        rows.push({ field: key, label: labelFor(key), before: beforeText, after: afterText });
    }

    return rows;
}

function formatField(key: string, value: unknown, userNames: Map<number, string>, categoryNames: Map<number, string>): string {
    if (key === 'payment_status') {
        if (!hasValue(value)) {
            return 'Unspecified';
        }
        return PAYMENT_STATUS_LABELS[String(value)] ?? String(value);
    }
    if (!hasValue(value)) {
        return '—';
    }
    if (key === 'amount') {
        return formatAmount(value);
    }
    if (key === 'expense_date' || key.endsWith('_date')) {
        const date = String(value).slice(0, 10);
        return /^\d{4}-\d{2}-\d{2}$/.test(date) ? formatDate(date) : String(value);
    }
    if (key === 'category_id') {
        const id = Number(value);
        return categoryNames.get(id) ?? 'Unknown category';
    }
    if (key === 'created_by' || key === 'updated_by' || key === 'uploaded_by') {
        const id = Number(value);
        return userNames.get(id) ?? 'Unknown user';
    }
    if (key === 'role') {
        return roleLabel(String(value));
    }
    if (key === 'is_active') {
        return isActiveValue(value) ? 'Active' : 'Inactive';
    }
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return String(value);
}

function categoryPhrase(values: Record<string, unknown> | null, categoryNames: Map<number, string>): string | null {
    if (!values || !hasValue(values.category_id)) {
        return null;
    }
    const id = Number(values.category_id);
    if (!Number.isInteger(id)) {
        return 'an unknown category';
    }

    return categoryNames.get(id) ?? 'an unknown category';
}

function onlyActiveStatusChanged(before: Record<string, unknown> | null, after: Record<string, unknown> | null): boolean {
    if (!before || !after || !('is_active' in before) || !('is_active' in after)) {
        return false;
    }
    if (isActiveValue(before.is_active) === isActiveValue(after.is_active)) {
        return false;
    }

    const keys = new Set([...Object.keys(before), ...Object.keys(after)]);
    for (const key of keys) {
        if (key === 'is_active') {
            continue;
        }
        if (JSON.stringify(before[key]) !== JSON.stringify(after[key])) {
            return false;
        }
    }

    return true;
}

function roleTitle(log: AuditLog): string {
    const role = readText(log.new_values, 'role') || readText(log.old_values, 'role');
    return role === 'super_admin' ? 'Super Admin' : 'Staff';
}

function rolePhrase(values: Record<string, unknown> | null): string {
    return readText(values, 'role') === 'super_admin' ? 'super admin' : 'staff member';
}

function roleLabel(role: string): string {
    if (role === 'staff') {
        return 'Staff';
    }
    if (role === 'super_admin') {
        return 'Super Admin';
    }

    return role;
}

function namedRecordSummary(actor: string, verb: string, record: string, name: string): string {
    return name ? `${actor} ${verb} the ${record} "${name}".` : `${actor} ${verb} a ${record}.`;
}

function categorySummary(actor: string, verb: string, name: string): string {
    return name ? `${actor} ${verb} the "${name}" category.` : `${actor} ${verb} a category.`;
}

function readText(values: Record<string, unknown> | null, key: string): string {
    const value = values?.[key];
    if (typeof value !== 'string' && typeof value !== 'number') {
        return '';
    }

    return String(value).trim();
}

function formatAmount(value: unknown): string {
    if (typeof value === 'number' || (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value)))) {
        return formatMoney(value);
    }

    return String(value);
}

function isActiveValue(value: unknown): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function hasValue(value: unknown): boolean {
    return value !== null && value !== undefined && value !== '';
}

function labelFor(key: string): string {
    if (FIELD_LABELS[key]) {
        return FIELD_LABELS[key];
    }

    return key.split('_').filter(Boolean).map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
}

function readableAction(action: string): string {
    const words = action.split(/[._]/).filter(Boolean);
    if (!words.length) {
        return 'Activity';
    }

    return words.map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function orderedKeys(keys: string[]): string[] {
    const unique = [...new Set(keys)];
    const rank = (key: string): number => {
        const index = FIELD_ORDER.indexOf(key);
        return index === -1 ? FIELD_ORDER.length : index;
    };

    return unique.sort((left, right) => rank(left) - rank(right) || left.localeCompare(right));
}
