@php
    $title = $title ?? null;
    $rows = $rows ?? [];
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:18px 0;background-color:#f8fbfa;border:1px solid #e2e8ee;border-radius:10px;">
    <tr>
        <td style="padding:16px 18px;">
            @if ($title)
                <p style="margin:0 0 12px;font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#316D61;">{{ $title }}</p>
            @endif
            @foreach ($rows as $row)
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 {{ $loop->last ? '0' : '10px' }};">
                    <tr>
                        <td style="font-size:13px;color:#64748b;padding:0 8px 0 0;vertical-align:top;width:38%;">{{ $row['label'] }}</td>
                        <td style="font-size:14px;color:#142B4D;font-weight:600;vertical-align:top;">{!! $row['value'] !!}</td>
                    </tr>
                </table>
            @endforeach
        </td>
    </tr>
</table>
