<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escalation Notice</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" style="width: 100%; max-width: 800px; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 32px; border-bottom: 3px solid #dc2626; background-color: #fef2f2;">
                            <h1 style="margin: 0; font-size: 24px; color: #991b1b;">⚠️ Escalation Notice: Immediate Action Required</h1>
                            <p style="margin: 8px 0 0 0; color: #7f1d1d; font-size: 14px;">{{ config('app.name') }} - Compliance & Obligation Management</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Dear <strong>{{ $recipient->name }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                The following obligation has been <strong style="color: #dc2626;">escalated</strong> and requires your immediate attention. This escalation indicates that the obligation has not been addressed within the expected timeframe.
                            </p>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #fef2f2; border-left: 4px solid #dc2626; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">{{ $obligation->title }}</p>
                                        <p style="margin: 4px 0 0 0; font-size: 14px; color: #6b7280;">{{ $obligation->obligation_no }}</p>
                                    </td>
                                </tr>
                            </table>

                            @php
                                $daysRemaining = now()->startOfDay()->diffInDays($obligation->expiry_date, false);
                                $urgencyLabel = $daysRemaining < 0 ? 'EXPIRED' : ($daysRemaining <= 7 ? 'CRITICAL' : ($daysRemaining <= 30 ? 'HIGH' : 'MEDIUM'));
                                $urgencyColor = $daysRemaining < 0 ? '#7f1d1d' : ($daysRemaining <= 7 ? '#dc2626' : ($daysRemaining <= 30 ? '#f59e0b' : '#3b82f6'));
                            @endphp

                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 24px 0;">
                                <thead>
                                    <tr style="background-color: #fef2f2; border-bottom: 2px solid #fecaca;">
                                        <th style="text-align: left; padding: 12px 16px; font-weight: 600; color: #991b1b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Escalation Alert</th>
                                        <th style="text-align: left; padding: 12px 16px; font-weight: 600; color: #991b1b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px; width: 35%;">Obligation Number</td>
                                        <td style="padding: 12px 16px; font-weight: 600; color: #1e293b; font-size: 14px;">{{ $obligation->obligation_no }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca; background-color: #fff5f5;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Obligation Title</td>
                                        <td style="padding: 12px 16px; font-weight: 600; color: #1e293b; font-size: 14px;">{{ $obligation->title }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Obligation Type</td>
                                        <td style="padding: 12px 16px; color: #1e293b; font-size: 14px;">{{ $obligation->type->type_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca; background-color: #fff5f5;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Category</td>
                                        <td style="padding: 12px 16px; color: #1e293b; font-size: 14px;">{{ $obligation->category->category_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Company / Department</td>
                                        <td style="padding: 12px 16px; color: #1e293b; font-size: 14px;">{{ $obligation->company->company_name ?? 'N/A' }} / {{ $obligation->department->department_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca; background-color: #fff5f5;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Location</td>
                                        <td style="padding: 12px 16px; color: #1e293b; font-size: 14px;">{{ $obligation->location->location_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Vendor</td>
                                        <td style="padding: 12px 16px; color: #1e293b; font-size: 14px;">{{ $obligation->vendor->vendor_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca; background-color: #fff5f5;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Owner</td>
                                        <td style="padding: 12px 16px; font-weight: 600; color: #1e293b; font-size: 14px;">{{ $obligation->owner->name ?? 'Unassigned' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Expiry Date</td>
                                        <td style="padding: 12px 16px; color: #dc2626; font-weight: 700; font-size: 14px;">{{ $obligation->expiry_date->format('Y-m-d') }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca; background-color: #fff5f5;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Days Remaining</td>
                                        <td style="padding: 12px 16px;">
                                            <span style="display: inline-block; padding: 6px 16px; border-radius: 4px; background-color: {{ $urgencyColor }}20; color: {{ $urgencyColor }}; font-weight: 700; font-size: 13px; text-transform: uppercase;">
                                                {{ $urgencyLabel }} - {{ abs($daysRemaining) }} day(s)
                                            </span>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #fecaca;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Priority</td>
                                        <td style="padding: 12px 16px;">
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 4px; background-color: {{ $obligation->priority === 'critical' ? '#fee2e2' : ($obligation->priority === 'high' ? '#fef3c7' : '#dbeafe') }}; color: {{ $obligation->priority === 'critical' ? '#991b1b' : ($obligation->priority === 'high' ? '#92400e' : '#1e40af') }}; font-weight: 600; text-transform: uppercase; font-size: 12px;">
                                                {{ $obligation->priority }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr style="background-color: #fff5f5;">
                                        <td style="padding: 12px 16px; color: #6b7280; font-weight: 500; font-size: 14px;">Risk Level</td>
                                        <td style="padding: 12px 16px;">
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 4px; background-color: {{ $obligation->risk_level === 'critical' ? '#fee2e2' : ($obligation->risk_level === 'high' ? '#fef3c7' : '#d1fae5') }}; color: {{ $obligation->risk_level === 'critical' ? '#991b1b' : ($obligation->risk_level === 'high' ? '#92400e' : '#065f46') }}; font-weight: 600; text-transform: uppercase; font-size: 12px;">
                                                {{ $obligation->risk_level }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #fef2f2; border-left: 4px solid #dc2626; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0 0 8px 0; font-weight: 600; color: #991b1b; font-size: 14px;">⚠️ Immediate Action Required</p>
                                        <p style="margin: 0; color: #7f1d1d; font-size: 14px; line-height: 1.6;">This obligation has been escalated due to approaching expiry or lack of action. Please review the obligation details immediately and take necessary steps to resolve this matter. If this is not addressed promptly, further escalation will follow.</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ route('obligations.show', $obligation) }}" style="display: inline-block; padding: 14px 32px; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">Review Obligation Immediately</a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f0f9ff; border-left: 4px solid #3b82f6; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;">
                                            If you believe this escalation was made in error or if you have already taken action, please update the obligation status immediately. For any questions, contact the compliance team.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 32px; background-color: #fef2f2; border-top: 1px solid #fecaca; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 14px; color: #374151; line-height: 1.6;">
                                Thanks,<br>
                                <strong>{{ config('app.name') }}</strong><br>
                                <span style="color: #6b7280; font-size: 13px;">Compliance & Obligation Management System</span>
                            </p>
                            <p style="margin: 16px 0 0 0; font-size: 12px; color: #9ca3af;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                                This is an automated notification. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
