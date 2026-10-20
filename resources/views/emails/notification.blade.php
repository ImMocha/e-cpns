<!DOCTYPE html>
<html lang="id" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light">
  <title>{{ $headline }}</title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    a:focus-visible { outline: 3px solid #01609B; outline-offset: 4px; }
    @media only screen and (max-width: 600px) {
      .email-outer { padding: 24px 12px !important; }
      .email-header { padding: 0 4px 24px !important; }
      .email-brand { font-size: 19px !important; }
      .email-hero { padding: 28px 24px !important; }
      .email-body { padding: 28px 24px !important; }
      .email-title { font-size: 30px !important; line-height: 1.2 !important; }
      .email-footer { padding: 28px 12px 0 !important; }
      .email-button { display: block !important; text-align: center !important; padding: 16px 20px !important; }
      .email-detail-label, .email-detail-value { display: block !important; width: auto !important; text-align: left !important; }
      .email-detail-label { padding: 14px 18px 2px !important; border-bottom: 0 !important; }
      .email-detail-value { padding: 0 18px 14px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#FFFDF7;color:#2A4257;font-family:'Plus Jakarta Sans',Arial,Helvetica,sans-serif;">
  <div style="display:none;font-size:1px;line-height:1px;color:#FFFDF7;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">{{ $intro }}</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFDF7" style="width:100%;background-color:#FFFDF7;">
    <tr>
      <td class="email-outer" align="center" style="padding:44px 20px;">
        <!--[if mso]><table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;table-layout:fixed;">
          <tr>
            <td class="email-header" style="padding:0 4px 28px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="52" height="52" align="center" valign="middle" bgcolor="#FFFDF7" style="width:52px;height:52px;background-color:#FFFDF7;border-radius:50%;"><img src="{{ asset('images/ganesha-nusa-logo.png') }}" alt="Logo Ganesha Nusa" width="52" height="52" style="display:block;width:52px;height:52px;border:0;"></td>
                  <td style="padding-left:14px;">
                    <p class="email-brand" style="margin:0;color:#16324F;font-family:'Outfit',Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;line-height:1.2;">{{ config('app.name') }}</p>
                    <p style="margin:5px 0 0;color:#64809A;font-size:10px;font-weight:700;letter-spacing:1.8px;line-height:1.5;">BELAJAR &amp; TRYOUT CPNS</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color:#FFFFFF;border:2px solid #16324F;border-radius:24px;box-shadow:6px 6px 0 #16324F;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;table-layout:fixed;">
                <tr>
                  <td class="email-hero" bgcolor="#D9F0FE" style="padding:36px 36px 32px;background-color:#D9F0FE;border-bottom:2px solid #16324F;border-radius:22px 22px 0 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr><td bgcolor="#FFC53D" style="padding:7px 12px;background-color:#FFC53D;border:1px solid #16324F;border-radius:999px;color:#16324F;font-size:10px;font-weight:800;letter-spacing:1.4px;line-height:1.4;">KABAR UNTUKMU</td></tr>
                    </table>
                    <h1 class="email-title" style="margin:22px 0 0;color:#16324F;font-family:'Outfit','Plus Jakarta Sans',Arial,Helvetica,sans-serif;font-size:36px;font-weight:800;letter-spacing:-1px;line-height:1.18;overflow-wrap:anywhere;">{{ $headline }}</h1>
                  </td>
                </tr>
                <tr>
                  <td class="email-body" style="padding:32px 36px 36px;">
                    <p style="margin:0;color:#2A4257;font-size:15px;line-height:1.85;overflow-wrap:anywhere;">{{ $intro }}</p>
                    @if ($details !== [])
                      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:24px;background-color:#FFFDF7;border:1px solid #DCE8F2;border-radius:14px;table-layout:fixed;">
                        @foreach ($details as $label => $value)
                          <tr>
                            <th class="email-detail-label" scope="row" width="40%" valign="top" style="width:40%;padding:15px 18px;text-align:left;color:#64809A;font-size:12px;font-weight:500;line-height:1.7;overflow-wrap:anywhere;{{ $loop->last ? '' : 'border-bottom:1px solid #DCE8F2;' }}">{{ $label }}</th>
                            <td class="email-detail-value" valign="top" style="padding:15px 18px;text-align:right;color:#16324F;font-size:13px;font-weight:700;line-height:1.7;overflow-wrap:anywhere;{{ $loop->last ? '' : 'border-bottom:1px solid #DCE8F2;' }}">{{ $value }}</td>
                          </tr>
                        @endforeach
                      </table>
                    @endif
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:28px;">
                      <tr>
                        <td align="left" style="padding:0 5px 5px 0;">
                          <a class="email-button" href="{{ $actionUrl }}" style="display:inline-block;max-width:100%;box-sizing:border-box;padding:16px 26px;background-color:#01609B;border:2px solid #16324F;border-radius:999px;box-shadow:4px 4px 0 #16324F;color:#FFFFFF;font-family:'Outfit','Plus Jakarta Sans',Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;line-height:1.4;text-align:center;text-decoration:none;overflow-wrap:anywhere;mso-padding-alt:0;">
                            <!--[if mso]><i style="mso-font-width:130%;mso-text-raise:24pt;" hidden>&emsp;</i><span style="mso-text-raise:12pt;"><![endif]-->
                            {{ $actionText }} <span aria-hidden="true" style="padding-left:8px;">&rarr;</span>
                            <!--[if mso]></span><i style="mso-font-width:130%;" hidden>&emsp;&#8203;</i><![endif]-->
                          </a>
                        </td>
                      </tr>
                    </table>
                    <p style="margin:28px 0 0;padding-left:14px;border-left:3px solid #FFC53D;color:#64809A;font-size:12px;line-height:1.85;overflow-wrap:anywhere;">{{ $outro }}</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:28px;border-top:1px solid #DCE8F2;table-layout:fixed;">
                      <tr><td style="padding-top:20px;">
                        <p style="margin:0 0 8px;color:#64809A;font-size:11px;line-height:1.8;">Tombol tidak bisa dibuka? Salin tautan ini ke browser kamu:</p>
                        <a href="{{ $actionUrl }}" style="color:#01609B;font-size:11px;line-height:1.8;text-decoration:underline;word-break:break-all;overflow-wrap:anywhere;">{{ $actionUrl }}</a>
                      </td></tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class="email-footer" align="center" style="padding:32px 24px 0;">
              <p style="margin:0;color:#16324F;font-family:'Outfit',Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;line-height:1.6;">Langkah kecil, persiapan lebih baik.</p>
              <p style="margin:8px 0 0;color:#64809A;font-size:11px;line-height:1.9;">Dikirim otomatis oleh {{ config('app.name') }}.<br>Teman belajar dan persiapan CPNS kamu.</p>
              @if ($preferencesUrl)
                <p style="margin:16px 0 0;font-size:11px;line-height:1.8;"><a href="{{ $preferencesUrl }}" style="display:inline-block;padding:8px;color:#01609B;text-decoration:underline;">Atur preferensi email</a></p>
              @endif
            </td>
          </tr>
        </table>
        <!--[if mso]></td></tr></table><![endif]-->
      </td>
    </tr>
  </table>
</body>
</html>
