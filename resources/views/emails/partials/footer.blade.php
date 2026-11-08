<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f8fbfa;border-top:1px solid #eaf0ee;">
    <tr>
        <td align="center" style="padding:22px 28px 26px;font-size:12px;line-height:1.6;color:#64748b;">
            <p style="margin:0 0 6px;font-weight:700;color:#142B4D;">{{ $brand }}</p>
            <p style="margin:0 0 6px;">
                <a href="{{ $brandUrl }}" style="color:#316D61;text-decoration:none;">{{ $brandUrl }}</a>
                &nbsp;·&nbsp;
                <a href="mailto:{{ $supportEmail }}" style="color:#316D61;text-decoration:none;">{{ $supportEmail }}</a>
            </p>
            <p style="margin:0 0 8px;">This is an automated message. Please do not reply directly to this email.</p>
            <p style="margin:0;">&copy; {{ date('Y') }} {{ $brand }}. All rights reserved.</p>
        </td>
    </tr>
</table>
