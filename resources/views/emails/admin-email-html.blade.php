<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:24px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#6d28d9,#4f46e5);padding:24px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:0.5px;">Oceanovia</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @if($recipientName)
                                <p style="margin:0 0 16px;color:#374151;font-size:15px;">Dear {{ $recipientName }},</p>
                            @endif
                            <h1 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:600;">{{ $subject }}</h1>
                            <div style="color:#374151;font-size:15px;line-height:1.7;">{!! $body !!}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.6;">
                            <p style="margin:0 0 4px;font-weight:600;color:#374151;">Oceanovia</p>
                            <p style="margin:0;">{{ config('mail.from.address') }}</p>
                            <p style="margin:8px 0 0;">© {{ date('Y') }} Oceanovia. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>