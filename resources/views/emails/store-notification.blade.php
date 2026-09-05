@php
    $locale = app()->getLocale();

    $storeName = $storeSettings->store_name
        ?: config('app.name');

    $logoUrl = $storeSettings->logo
        ? asset('storage/' . $storeSettings->logo)
        : null;
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $subjectLine }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0;padding:0;background:#f6f7f9;font-family:Arial,Tahoma,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #eeeeee;">
                    <tr>
                        <td style="background:#111827;padding:28px;text-align:center;">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height:58px;max-width:170px;margin-bottom:14px;">
                            @endif

                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;line-height:1.5;">
                                {{ $storeName }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 28px 20px;text-align:{{ $locale === 'ar' ? 'right' : 'left' }};">
                            <h2 style="margin:0 0 18px;color:#111827;font-size:22px;font-weight:800;line-height:1.5;">
                                {{ $subjectLine }}
                            </h2>

                            <div style="font-size:15px;line-height:2;color:#374151;font-weight:500;white-space:pre-line;">
                                {!! nl2br(e($bodyText)) !!}
                            </div>

                            @if ($actionUrl)
                                <div style="text-align:center;margin:30px 0 10px;">
                                    <a href="{{ $actionUrl }}"
                                       style="display:inline-block;background:#d97706;color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:14px;font-size:15px;font-weight:800;">
                                        {{ $actionLabel ?: ($locale === 'ar' ? 'عرض التفاصيل' : 'View Details') }}
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 28px 30px;">
                            <div style="height:1px;background:#eeeeee;margin-bottom:18px;"></div>

                            <p style="margin:0;text-align:center;color:#6b7280;font-size:13px;line-height:1.8;">
                                {{ $locale === 'ar'
                                    ? 'هذه رسالة تلقائية من المتجر، برجاء عدم الرد عليها.'
                                    : 'This is an automated message from the store. Please do not reply.' }}
                            </p>

                            @if ($storeSettings->email || $storeSettings->phone || $storeSettings->whatsapp)
                                <p style="margin:12px 0 0;text-align:center;color:#9ca3af;font-size:12px;line-height:1.8;">
                                    @if ($storeSettings->email)
                                        {{ $storeSettings->email }}
                                    @endif

                                    @if ($storeSettings->phone)
                                        {{ $storeSettings->email ? ' | ' : '' }}{{ $storeSettings->phone }}
                                    @endif

                                    @if ($storeSettings->whatsapp)
                                        {{ ($storeSettings->email || $storeSettings->phone) ? ' | ' : '' }}WhatsApp: {{ $storeSettings->whatsapp }}
                                    @endif
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0;color:#9ca3af;font-size:12px;text-align:center;">
                    © {{ date('Y') }} {{ $storeName }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>