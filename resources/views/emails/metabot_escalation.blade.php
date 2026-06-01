<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Conversación escalada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 4px;">El bot escaló una conversación</h2>
    <p style="color: #6b7280; margin-top: 0;">Un cliente necesita atención humana.</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="font-weight: bold;">Teléfono</td>
            <td>{{ $phone }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Anuncio</td>
            <td>{{ $adName ?: '—' }}@if($sourceId) <span style="color:#6b7280;">(source_id: {{ $sourceId }})</span>@endif</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Motivo</td>
            <td>{{ $reason }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Responder</td>
            <td><a href="{{ $waLink }}" style="color:#2563eb;">{{ $waLink }}</a></td>
        </tr>
    </table>

    <h3 style="margin-bottom: 8px;">Conversación</h3>
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #f9fafb;">
        @forelse ($transcript as $line)
            <p style="margin: 4px 0;">
                <strong style="color: {{ $line['role'] === 'user' ? '#111827' : '#15803d' }};">
                    {{ $line['role'] === 'user' ? 'Cliente' : 'Bot' }}:
                </strong>
                {{ $line['content'] }}
            </p>
        @empty
            <p style="color:#6b7280; margin:0;">(sin mensajes)</p>
        @endforelse
    </div>
</body>
</html>
