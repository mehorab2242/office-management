<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><style>
body{font-family:'DejaVu Sans',sans-serif;color:#0f172a;font-size:10px}h1{font-size:20px;color:#0f766e;margin:0 0 4px}h2{font-size:13px;margin:16px 0 6px}.summary{margin:14px 0}.metric{display:inline-block;width:15%;padding:8px;border:1px solid #cbd5e1;margin-right:4px}.metric strong{display:block;font-size:14px;margin-top:4px}table{width:100%;border-collapse:collapse}th{background:#0f766e;color:white;text-align:left}th,td{padding:6px;border:1px solid #cbd5e1}.right{text-align:right}.muted{color:#64748b}.footer{position:fixed;bottom:-22px;left:0;right:0;text-align:center;color:#64748b}.page:after{content:counter(page)}
</style></head>
<body>
<h1>{{ $title }}</h1>
<div class="muted">Generated {{ now(config('app.display_timezone'))->format('Y-m-d H:i') }}</div>
<div class="summary">
    <div class="metric">Total<strong>BDT {{ number_format((float) $summary['total_amount'], 2) }}</strong></div>
    <div class="metric">Paid<strong>BDT {{ number_format((float) $summary['paid_amount'], 2) }}</strong></div>
    <div class="metric">Unpaid<strong>BDT {{ number_format((float) $summary['unpaid_amount'], 2) }}</strong></div>
    <div class="metric">Pending<strong>BDT {{ number_format((float) $summary['pending_amount'], 2) }}</strong></div>
    <div class="metric">Transactions<strong>{{ $summary['transaction_count'] }}</strong></div>
</div>
<h2>Category summary</h2><table><thead><tr><th>Category</th><th class="right">Total (BDT)</th></tr></thead><tbody>@forelse($categories as $category)<tr><td>{{ $category['name'] }}</td><td class="right">{{ number_format((float) $category['total'], 2) }}</td></tr>@empty<tr><td colspan="2" class="muted">No categories.</td></tr>@endforelse</tbody></table>
<h2>Expense details</h2>
<table><thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Status</th><th>Paid by</th><th class="right">Amount (BDT)</th></tr></thead><tbody>
@forelse($expenses as $expense)
<tr><td>{{ $expense->expense_date?->format('Y-m-d') }}</td><td>{{ $expense->description }}</td><td>{{ $expense->category?->name ?? 'Uncategorized' }}</td><td>{{ ucfirst($expense->payment_status ?? 'unspecified') }}</td><td>{{ $expense->payerAllocations->pluck('payer_name')->implode(', ') }}</td><td class="right">{{ number_format((float) $expense->amount, 2) }}</td></tr>
@empty<tr><td colspan="6" class="muted">No expenses match this report.</td></tr>@endforelse
</tbody></table>
<div class="footer">Page <span class="page"></span></div>
</body></html>
