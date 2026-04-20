<?php

return [
    'billing' => [
        'invoice_default_due_days' => max(0, (int) env('ERP_INVOICE_DEFAULT_DUE_DAYS', 30)),
        'overdue_grace_days' => max(0, (int) env('ERP_INVOICE_OVERDUE_GRACE_DAYS', 0)),
    ],

    'quotes' => [
        'expired_acceptance_grace_days' => max(0, (int) env('ERP_QUOTE_ACCEPTANCE_GRACE_DAYS', 0)),
    ],

    'approvals' => [
        'expense_auto_approve_limit' => max(0, (float) env('ERP_EXPENSE_AUTO_APPROVE_LIMIT', 250000)),
        'bulk_approval_limit' => max(1, (int) env('ERP_APPROVAL_BULK_LIMIT', 10)),
        'project_auto_approve_statuses' => array_values(array_filter(array_map(
            static fn(string $status): string => trim($status),
            explode(',', (string) env('ERP_PROJECT_AUTO_APPROVE_STATUSES', 'planned,on_hold'))
        ))),
    ],
];
