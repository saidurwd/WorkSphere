<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 - Access Restricted</title>
        <style>
            body {
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: #f8fafc;
                color: #0f172a;
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            .container {
                text-align: center;
                max-width: 28rem;
                width: 100%;
            }
            .icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 5rem;
                height: 5rem;
                border-radius: 9999px;
                background-color: #fee2e2;
                color: #dc2626;
                margin-bottom: 1.5rem;
            }
            .icon svg {
                width: 2.5rem;
                height: 2.5rem;
            }
            h1 {
                font-size: 3rem;
                font-weight: 800;
                letter-spacing: -0.025em;
                line-height: 1;
                margin: 0 0 0.5rem;
                color: #dc2626;
            }
            h2 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1e293b;
                margin: 0 0 0.75rem;
            }
            p {
                font-size: 1rem;
                color: #475569;
                line-height: 1.6;
                margin: 0 0 1.5rem;
            }
            a {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                background-color: #dc2626;
                color: #ffffff;
                font-weight: 500;
                border-radius: 0.5rem;
                text-decoration: none;
                transition: background-color 0.2s;
            }
            a:hover {
                background-color: #b91c1c;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1>403</h1>
            <h2>Access Restricted</h2>
            <p>You do not have permission to view this resource. If you believe this is an error, please contact your administrator.</p>
            <a href="{{ url('/') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.25rem;height:1.25rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Back to Dashboard
            </a>
        </div>
    </body>
</html>
