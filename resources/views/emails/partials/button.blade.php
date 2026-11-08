@php
    $align = $align ?? 'center';
    $url = $url ?? '#';
    $label = $label ?? 'Continue';
@endphp
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="{{ $align }}" style="margin:22px auto;">
    <tr>
        <td align="center" bgcolor="#316D61" style="border-radius:8px;background-color:#316D61;">
            <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:700;line-height:1.2;color:#ffffff;text-decoration:none;border-radius:8px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
