<?php

return [
    'billing' => [
        'invoice_default_due_days' => max(0, (int) env('ERP_INVOICE_DEFAULT_DUE_DAYS', 30)),
        'overdue_grace_days' => max(0, (int) env('ERP_INVOICE_OVERDUE_GRACE_DAYS', 0)),
        'credit_note_auto_issue_limit' => max(0, (float) env('ERP_CREDIT_NOTE_AUTO_ISSUE_LIMIT', 100000)),
        'payment_reference_required_methods' => array_values(array_filter(array_map(
            static fn(string $method): string => trim(strtolower($method)),
            explode(',', (string) env('ERP_PAYMENT_REFERENCE_REQUIRED_METHODS', ''))
        ))),
        'invoice_numbering' => [
            'prefix' => trim((string) env('ERP_INVOICE_NUMBER_PREFIX', 'INV')),
            'padding' => max(3, (int) env('ERP_INVOICE_NUMBER_PADDING', 4)),
            'reset' => (string) env('ERP_INVOICE_NUMBER_RESET', 'yearly'),
            'separator' => (string) env('ERP_INVOICE_NUMBER_SEPARATOR', '-'),
        ],
    ],

    'quotes' => [
        'expired_acceptance_grace_days' => max(0, (int) env('ERP_QUOTE_ACCEPTANCE_GRACE_DAYS', 0)),
    ],

    'tax_profiles' => [
        'default_country' => trim((string) env('ERP_TAX_DEFAULT_COUNTRY', '')),
        'default' => [
            'code' => trim((string) env('ERP_TAX_DEFAULT_CODE', 'STANDARD')),
            'label' => trim((string) env('ERP_TAX_DEFAULT_LABEL', 'Standard tax profile')),
            'rate' => max(0, (float) env('ERP_TAX_DEFAULT_RATE', 0)),
            'mode' => (string) env('ERP_TAX_DEFAULT_MODE', 'exclusive'),
        ],
        'countries' => [
            'Mali' => [
                'code' => 'ML-VAT',
                'label' => 'TVA Mali',
                'rate' => 18,
                'mode' => 'exclusive',
            ],
            'Senegal' => [
                'code' => 'SN-VAT',
                'label' => 'TVA Sénégal',
                'rate' => 18,
                'mode' => 'exclusive',
                'regions' => [
                    'Dakar' => [
                        'code' => 'SN-DAKAR',
                        'label' => 'TVA Dakar',
                        'rate' => 20,
                        'mode' => 'exclusive',
                    ],
                ],
            ],
            'France' => [
                'code' => 'FR-VAT',
                'label' => 'TVA France',
                'rate' => 20,
                'mode' => 'exclusive',
            ],
            'Ghana' => [
                'code' => 'GH-VAT',
                'label' => 'VAT Ghana',
                'rate' => 15,
                'mode' => 'exclusive',
            ],
            'United Arab Emirates' => [
                'code' => 'AE-VAT',
                'label' => 'VAT UAE',
                'rate' => 5,
                'mode' => 'exclusive',
            ],
        ],
    ],

    'approvals' => [
        'expense_auto_approve_limit' => max(0, (float) env('ERP_EXPENSE_AUTO_APPROVE_LIMIT', 250000)),
        'bulk_approval_limit' => max(1, (int) env('ERP_APPROVAL_BULK_LIMIT', 10)),
        'project_auto_approve_statuses' => array_values(array_filter(array_map(
            static fn(string $status): string => trim($status),
            explode(',', (string) env('ERP_PROJECT_AUTO_APPROVE_STATUSES', 'planned,on_hold'))
        ))),
    ],

    'documents' => [
        'disk' => (string) env('ERP_DOCUMENTS_DISK', 'local'),
        'directory' => trim((string) env('ERP_DOCUMENTS_DIRECTORY', 'attachments'), '/'),
        'max_upload_kb' => max(512, (int) env('ERP_DOCUMENTS_MAX_UPLOAD_KB', 10240)),
        'quota_mb' => max(1, (int) env('ERP_DOCUMENTS_QUOTA_MB', 200)),
        'download_url_ttl_minutes' => max(1, (int) env('ERP_DOCUMENTS_DOWNLOAD_URL_TTL', 30)),
        'allowed_extensions' => array_values(array_filter(array_map(
            static fn(string $extension): string => trim(strtolower($extension)),
            explode(',', (string) env('ERP_DOCUMENTS_ALLOWED_EXTENSIONS', 'pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,zip,txt'))
        ))),
    ],

    'enterprise' => [
        'report_retention_days' => max(1, (int) env('ERP_REPORT_RETENTION_DAYS', 30)),
        'audit_retention_days' => max(7, (int) env('ERP_AUDIT_RETENTION_DAYS', 365)),
    ],
];
