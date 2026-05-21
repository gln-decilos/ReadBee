<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ReadBee Evaluator Assignment</title>
</head>
<body style="margin: 0; padding: 0; background: #f4f7fb; font-family: Arial, sans-serif; color: #111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f4f7fb; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; background: #ffffff; border-radius: 18px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="padding: 28px 32px; background: #facc15; color: #111827;">
                            <h1 style="margin: 0; font-size: 22px; line-height: 1.3;">ReadBee Evaluator Assignment</h1>
                            <p style="margin: 8px 0 0; font-size: 14px;">Please confirm your assessment assignment.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 32px;">
                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">Hello {{ $fullName }},</p>
                            <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6;">
                                You have been assigned as an evaluator by {{ $assignedByName }}. Please review the details below and confirm this assignment.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 20px 0; font-size: 14px;">
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f9fafb; font-weight: bold; width: 38%;">School Year</td>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $schoolYearLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f9fafb; font-weight: bold;">Quarter</td>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $quarterLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f9fafb; font-weight: bold;">Assessment Date</td>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $assessmentDate }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f9fafb; font-weight: bold;">Grade</td>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $gradeLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f9fafb; font-weight: bold;">Section</td>
                                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $sectionName }}</td>
                                </tr>
                            </table>

                            <table cellpadding="0" cellspacing="0" style="margin-top: 24px;">
                                <tr>
                                    <td>
                                        <a href="{{ $confirmUrl }}" style="display: inline-block; background: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: bold; font-size: 14px;">Confirm Assignment</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0 0; font-size: 13px; line-height: 1.6; color: #6b7280;">
                                This confirmation link is generated for this assignment. If you received this message by mistake, please contact your school principal.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
