@component('shop::emails.layout')
    <div style="margin-bottom: 34px;">
        <span style="font-size: 22px;font-weight: 600;color: #121A26;">
            @lang('shop::app.guest_email_verification.mail.title')
        </span> <br>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('shop::app.guest_email_verification.mail.intro', ['minutes' => $expiryMinutes])
        </p>
    </div>

    <div style="margin-bottom: 40px;">
        <span style="display: inline-block;padding: 16px 45px;border-radius: 2px;background: #060C3B;color: #FFFFFF;font-weight: 700;font-size: 28px;letter-spacing: 10px;">
            {{ $code }}
        </span>
    </div>

    <p style="font-size: 13px;color: #8A94A6;line-height: 20px;margin-bottom: 0;">
        @lang('shop::app.guest_email_verification.mail.ignore')
    </p>
@endcomponent
