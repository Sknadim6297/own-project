<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#0b2048;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e0e4ea;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 12px;text-align:center;background:#ffffff;">
                            <img src="{{ $logoUrl }}" alt="{{ $brand }}" width="180" style="display:inline-block;max-width:180px;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 0;text-align:center;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#316D61;font-weight:700;">{{ $brand }}</p>
                            <h1 style="margin:10px 0 0;font-size:22px;line-height:1.3;color:#0b2048;">Password reset</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 8px;font-size:15px;line-height:1.6;color:#334155;">
                            <p style="margin:0 0 16px;">{{ $intro }}</p>
                            <p style="margin:0 0 24px;text-align:center;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#316D61;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-size:14px;font-weight:700;">{{ $actionText }}</a>
                            </p>
                            <p style="margin:0 0 12px;">{{ $expireText }}</p>
                            <p style="margin:0 0 12px;">{{ $outro }}</p>
                            <p style="margin:20px 0 0;font-size:12px;line-height:1.5;color:#64748b;word-break:break-all;">
                                If the button does not work, copy and paste this link into your browser:<br>
                                <a href="{{ $actionUrl }}" style="color:#316D61;">{{ $actionUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 28px;font-size:12px;line-height:1.5;color:#64748b;text-align:center;border-top:1px solid #e0e4ea;">
                            © {{ date('Y') }} {{ $brand }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
