<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Unsubscribed — Aegis</title>
</head>
<body style="margin:0;padding:0;background-color:#fffdf7;font-family:'Georgia','Times New Roman',serif;color:#2d2a26;">
  <div style="max-width:560px;margin:0 auto;padding:48px 16px;">
    <div style="background-color:#ffffff;border:1px solid #e4dfd7;border-radius:8px;overflow:hidden;">
      <div style="padding:28px 40px 20px;border-bottom:1px solid #e4dfd7;">
        <span style="font-size:22px;font-weight:700;color:#2d2a26;letter-spacing:-0.3px;">Aegis</span>
      </div>
      <div style="padding:32px 40px;">
        @if($resolved)
          <h1 style="font-size:20px;font-weight:700;margin:0 0 14px;color:#2d2a26;">You've been unsubscribed</h1>
          @if($was_master)
            <p style="font-size:15px;line-height:1.6;margin:0 0 16px;color:#5a5650;">
              You will no longer receive any email notifications from Aegis. Critical,
              account-security messages may still be sent where required to keep your
              account safe.
            </p>
          @else
            <p style="font-size:15px;line-height:1.6;margin:0 0 16px;color:#5a5650;">
              You will no longer receive {{ $category }} from Aegis. Your other
              notification preferences are unchanged.
            </p>
          @endif
          <p style="font-size:15px;line-height:1.6;margin:0 0 24px;color:#5a5650;">
            Changed your mind? You can update every notification preference from your
            account settings at any time.
          </p>
          <a href="{{ $settings_url }}"
             style="display:inline-block;background-color:#2d2a26;color:#fffdf7;text-decoration:none;
                    font-size:14px;font-weight:700;padding:12px 22px;border-radius:6px;">
            Manage notification settings
          </a>
        @else
          <h1 style="font-size:20px;font-weight:700;margin:0 0 14px;color:#2d2a26;">Link expired or invalid</h1>
          <p style="font-size:15px;line-height:1.6;margin:0 0 24px;color:#5a5650;">
            We couldn't process this unsubscribe request. The link may have expired.
            You can manage all of your notification preferences directly from your
            account settings.
          </p>
          <a href="{{ $settings_url }}"
             style="display:inline-block;background-color:#2d2a26;color:#fffdf7;text-decoration:none;
                    font-size:14px;font-weight:700;padding:12px 22px;border-radius:6px;">
            Go to settings
          </a>
        @endif
      </div>
      <div style="padding:20px 40px;border-top:1px solid #e4dfd7;">
        <p style="font-size:12px;line-height:1.5;margin:0;color:#8a8378;">
          &copy; {{ date('Y') }} Aegis. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</body>
</html>
