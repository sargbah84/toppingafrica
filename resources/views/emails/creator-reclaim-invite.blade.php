<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage your profile</title>
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
                            <h2 style="color: #111827; margin: 0 0 16px; font-size: 20px;">Hi {{ $recipientName ?? $creator->name . ' team' }},</h2>

                            <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 16px;">
                                You previously claimed the <strong>{{ $creator->name }}</strong> profile on Topping Africa.
                            </p>

                            <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 24px;">
                                We've recently upgraded our system so you can now create an account and log in anytime to manage your profile — no more clicking email links each time you want to make an edit. Click the button below to log in and see your profile on the new dashboard.
                            </p>

                            {{-- CTA Button --}}
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto 24px;">
                                <tr>
                                    <td style="background-color: #000000; border-radius: 6px;">
                                        <a href="{{ $claimUrl }}" style="display: inline-block; padding: 14px 32px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600;">
                                            Log in to your profile
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #9ca3af; font-size: 14px; line-height: 1.5; margin: 0 0 8px;">
                                This link expires in 30 days. If you didn't claim this profile or you're not the right person, you can safely ignore this email.
                            </p>

                            <p style="color: #9ca3af; font-size: 14px; line-height: 1.5; margin: 0;">
                                If the button doesn't work, copy and paste this URL into your browser:<br>
                                <a href="{{ $claimUrl }}" style="color: #111827; word-break: break-all;">{{ $claimUrl }}</a>
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
