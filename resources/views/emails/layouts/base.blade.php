@php
    $brand = $brand ?? config('yuktione.name', 'YuktiOne');
    $brandUrl = $brandUrl ?? 'https://yuktione.in';
    $supportEmail = $supportEmail ?? config('yuktione.email', 'info@yuktione.in');
    $logoUrl = $logoUrl ?? asset('frontend/assets/Yuktione_logo.png');
    $pageTitle = trim($__env->yieldContent('title', $brand));
    $preheader = trim($__env->yieldContent('preheader', ''));
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $pageTitle }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
        @media only screen and (max-width: 620px) {
            .yk-email-shell { width: 100% !important; }
            .yk-email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .yk-email-title { font-size: 22px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#142B4D;">
    @if ($preheader !== '')
        <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
            {{ $preheader }}
        </div>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f7f6;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" class="yk-email-shell" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background-color:#ffffff;border:1px solid #e2e8ee;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td>
                            @include('emails.partials.header', ['brand' => $brand, 'logoUrl' => $logoUrl, 'brandUrl' => $brandUrl])
                        </td>
                    </tr>
                    <tr>
                        <td class="yk-email-pad" style="padding:8px 32px 8px;">
                            @hasSection('heading')
                                <h1 class="yk-email-title" style="margin:0 0 8px;font-size:24px;line-height:1.3;font-weight:700;color:#142B4D;letter-spacing:-0.02em;">
                                    @yield('heading')
                                </h1>
                            @endif
                            @hasSection('subtitle')
                                <p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#5b6b7c;">
                                    @yield('subtitle')
                                </p>
                            @else
                                <div style="height:12px;line-height:12px;">&nbsp;</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="yk-email-pad" style="padding:0 32px 28px;font-size:15px;line-height:1.65;color:#334155;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td>
                            @include('emails.partials.footer', ['brand' => $brand, 'brandUrl' => $brandUrl, 'supportEmail' => $supportEmail])
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
