<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f5f3ff;color:#242039;font-family:Arial,sans-serif;line-height:1.6">
  <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e4ddf3;border-radius:16px" cellpadding="0" cellspacing="0">
    <tr><td style="padding:32px">
      <p style="margin:0 0 24px;color:#6d28d9;font-size:20px;font-weight:bold">{{ config('app.name') }}</p>
      <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3">{{ $headline }}</h1>
      <p>{{ $intro }}</p>
      @if ($details !== [])
        <table role="presentation" style="width:100%;margin:24px 0;border-collapse:collapse" cellpadding="8" cellspacing="0">
          @foreach ($details as $label => $value)
            <tr><th scope="row" style="text-align:left;border-bottom:1px solid #ede9f5">{{ $label }}</th><td style="border-bottom:1px solid #ede9f5">{{ $value }}</td></tr>
          @endforeach
        </table>
      @endif
      <p style="margin:28px 0"><a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 24px;border-radius:8px;background:#6d28d9;color:#ffffff;text-decoration:none;font-weight:bold">{{ $actionText }}</a></p>
      <p style="color:#625d73;font-size:14px">{{ $outro }}</p>
      <p style="font-size:12px;color:#625d73">Jika tombol tidak dapat dibuka, salin tautan berikut ke browser:</p>
      <p style="font-size:12px;word-break:break-all"><a href="{{ $actionUrl }}">{{ $actionUrl }}</a></p>
      @if ($preferencesUrl)
        <p style="font-size:12px"><a href="{{ $preferencesUrl }}">Atur preferensi email</a></p>
      @endif
    </td></tr>
  </table>
</body>
</html>
