<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Your Profile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #d60842; padding: 30px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">Topping Africa</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #111827; margin: 0 0 16px; font-size: 20px;">Hi {{ $creator->name }},</h2>

                            <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 16px;">
                                You've been featured on <strong>Topping Africa</strong> as one of the rising {{ $creator->category }} content creators from {{ $creator->country }}!
                            </p>

                            <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 24px;">
                                Claim your profile to upload your own photo, update your bio, and manage your social links. Your profile is already live and being discovered by audiences across Africa.
                            </p>

                            {{-- CTA Button --}}
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto 24px;">
                                <tr>
                                    <td style="background-color: #d60842; border-radius: 6px;">
                                        <a href="{{ $claimUrl }}" style="display: inline-block; padding: 14px 32px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600;">
                                            Claim Your Profile
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #9ca3af; font-size: 14px; line-height: 1.5; margin: 0 0 8px;">
                                This link expires in 48 hours. If you didn't expect this email, you can safely ignore it.
                            </p>

                            <p style="color: #9ca3af; font-size: 14px; line-height: 1.5; margin: 0;">
                                If the button doesn't work, copy and paste this URL into your browser:<br>
                                <a href="{{ $claimUrl }}" style="color: #d60842; word-break: break-all;">{{ $claimUrl }}</a>
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
