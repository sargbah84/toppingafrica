<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topping Africa Promotion</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #000000; padding: 30px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">Topping Africa</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #111827; margin: 0 0 16px; font-size: 20px;">{{ $greeting }}</h2>

                            {{-- Paragraphs are built in the mailable; user input there is escaped with e(). --}}
                            @foreach($paragraphs as $paragraph)
                                <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 16px;">{!! $paragraph !!}</p>
                            @endforeach

                            @if($showDetails)
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin: 8px 0 24px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px;">
                                    @foreach(array_filter([
                                        'Order' => $promotion->reference,
                                        'Package' => $promotion->package_name,
                                        'Add-ons' => implode(', ', $promotion->addon_names),
                                        'Total' => $promotion->formatted_amount,
                                        'Promoting' => $promotion->subject_name . ' — ' . $promotion->title,
                                        'Link' => $promotion->primary_url,
                                        'Preferred date' => $promotion->preferred_date?->format('M j, Y'),
                                    ]) as $label => $value)
                                        <tr>
                                            <td style="padding: 8px 12px; color: #6b7280; border-bottom: 1px solid #f3f4f6; width: 130px; vertical-align: top;">{{ $label }}</td>
                                            <td style="padding: 8px 12px; color: #111827; border-bottom: 1px solid #f3f4f6; word-break: break-word;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if($ctaUrl)
                                <table cellpadding="0" cellspacing="0" style="margin: 0 auto 24px;">
                                    <tr>
                                        <td style="background-color: #000000; border-radius: 6px;">
                                            <a href="{{ $ctaUrl }}" style="display: inline-block; padding: 14px 32px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600;">
                                                {{ $ctaLabel }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="color: #9ca3af; font-size: 14px; line-height: 1.5; margin: 0;">
                                Questions? Just reply to this email and quote your order reference {{ $promotion->reference }}.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 13px; margin: 0;">
                                &copy; {{ date('Y') }} Topping Africa. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
