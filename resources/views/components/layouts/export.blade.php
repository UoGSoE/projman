<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Work Package Export' }} - {{ config('app.name') }}</title>
    <style>
        /* CSS Reset */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #F7F7F7;
        }

        /* UofG Brand Colours */
        :root {
            --uofg-blue: #011451;
            --uofg-light-bg: #F7F7F7;
            --uofg-burgundy: #7D2239;
            --uofg-lavender: #5B4D94;
            --uofg-leaf: #006630;
        }

        /* Layout */
        .container { max-width: 56rem; margin: 0 auto; }

        /* Header */
        .header {
            background-color: var(--uofg-blue);
            color: white;
            padding: 2rem;
        }
        .header-institution {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        .header-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .header-reference {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        /* Main content */
        .main { padding: 2rem; }

        /* Cards */
        .card {
            background: white;
            border-radius: 0.375rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--uofg-blue);
        }
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--uofg-blue);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Grid */
        .grid { display: grid; gap: 1rem; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }

        /* Definition items */
        .field { margin-bottom: 0.75rem; }
        .field-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .field-value {
            color: #1f2937;
        }
        .field-value.empty {
            color: #9ca3af;
            font-style: italic;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem 2rem;
            font-size: 0.875rem;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                font-size: 10pt;
                line-height: 1.4;
            }
            .header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card {
                page-break-inside: avoid;
                border-left-color: var(--uofg-blue) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card-title { page-break-after: avoid; }
            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { margin: 1.5cm; }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .header, .main { padding: 1rem; }
        }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
