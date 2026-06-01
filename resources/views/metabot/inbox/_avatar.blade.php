@php
    // Generated initials avatar — WhatsApp Cloud API doesn't expose a customer's
    // real profile photo, so we render a stable colored circle from name/phone.
    $px      = $size ?? 40;
    $palette = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#0ea5e9'];
    $seed    = $phone ?: ($name ?: '?');
    $bg      = $palette[abs(crc32((string) $seed)) % count($palette)];

    if (!empty($name)) {
        $parts = preg_split('/\s+/', trim($name));
        $label = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (count($parts) > 1) {
            $label .= mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1));
        }
    } else {
        $digits = preg_replace('/\D/', '', (string) $phone);
        $label  = mb_substr($digits, -2);
    }
@endphp
<span style="display:inline-flex;align-items:center;justify-content:center;flex:none;width:{{ $px }}px;height:{{ $px }}px;border-radius:9999px;background:{{ $bg }};color:#fff;font-weight:600;font-size:{{ round($px * 0.4) }}px;">{{ $label }}</span>
