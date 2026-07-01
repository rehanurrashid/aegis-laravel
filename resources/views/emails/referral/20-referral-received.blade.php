@php
    $referral = \App\Models\Referral::with(['sender', 'meta'])->find($referral_id);
    if (!$referral) return;
    $sender   = $referral->sender;
    $urgency  = $referral->meta->firstWhere('meta_key', 'urgency')?->meta_value ?? 'routine';
    $subject  = $referral->meta->firstWhere('meta_key', 'reason')?->meta_value
             ?? $referral->meta->firstWhere('meta_key', 'presenting_issue')?->meta_value
             ?? $referral->subject ?? 'Client referral';
    $notes    = $referral->meta->firstWhere('meta_key', 'notes')?->meta_value;
    $urgencyLabel = ['urgent' => 'Urgent', 'soon' => 'Soon – 2 weeks', 'routine' => 'Routine'][$urgency] ?? 'Routine';
    $urgencyColor = ['urgent' => '#c0392b', 'soon' => '#d35400', 'routine' => '#27ae60'][$urgency] ?? '#27ae60';
    $url      = url('/provider/referrals');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>New Referral Received — Aegis</title>
</head>
<body style="margin:0;padding:0;background:#f5f0e8;font-family:'Inter',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

        {{-- Header --}}
        <tr><td style="background:#2c2218;padding:24px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="font-family:'Spectral',Georgia,serif;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">Aegis</td>
              <td align="right" style="font-size:11px;color:#c4a96a;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Referral Coordination</td>
            </tr>
          </table>
        </td></tr>

        {{-- Urgency banner --}}
        <tr><td style="background:{{ $urgencyColor }};padding:10px 32px;">
          <p style="margin:0;color:#ffffff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">
            {{ $urgencyLabel }} · New Client Referral
          </p>
        </td></tr>

        {{-- Body --}}
        <tr><td style="padding:32px;">
          <p style="margin:0 0 6px;font-size:14px;color:#8a7c6e;">You have a new referral to review.</p>
          <p style="margin:0 0 24px;font-family:'Spectral',Georgia,serif;font-size:24px;font-weight:700;color:#2c2218;line-height:1.3;">
            Referral from {{ $sender?->display_name ?? 'a colleague' }}
          </p>

          {{-- Info box --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8e0d4;border-radius:8px;overflow:hidden;margin-bottom:24px;">
            <tr style="background:#f9f5ee;">
              <td style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;border-bottom:1px solid #e8e0d4;">From</td>
              <td style="padding:10px 16px;font-size:13px;color:#2c2218;font-weight:600;border-bottom:1px solid #e8e0d4;">{{ $sender?->display_name }}{{ $sender?->credentials ? ', ' . $sender->credentials : '' }}</td>
            </tr>
            <tr>
              <td style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;border-bottom:1px solid #e8e0d4;">Reason</td>
              <td style="padding:10px 16px;font-size:13px;color:#2c2218;border-bottom:1px solid #e8e0d4;">{{ $subject }}</td>
            </tr>
            <tr>
              <td style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;">Urgency</td>
              <td style="padding:10px 16px;font-size:13px;font-weight:700;color:{{ $urgencyColor }};">{{ $urgencyLabel }}</td>
            </tr>
          </table>

          @if($notes)
          <div style="background:#f9f5ee;border-left:3px solid #c4a96a;border-radius:6px;padding:12px 16px;margin-bottom:24px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;">Note from {{ $sender?->display_name }}</p>
            <p style="margin:0;font-size:13px;color:#4a3f35;line-height:1.6;">{{ $notes }}</p>
          </div>
          @endif

          <p style="margin:0 0 20px;font-size:13px;color:#6b5f52;line-height:1.6;">
            Log in to Aegis to review the full referral and respond within the required SLA window.
          </p>

          <a href="{{ $url }}" style="display:inline-block;background:#2c2218;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;padding:12px 24px;border-radius:8px;letter-spacing:0.2px;">
            Review Referral →
          </a>
        </td></tr>

        {{-- Footer --}}
        <tr><td style="background:#f9f5ee;border-top:1px solid #e8e0d4;padding:20px 32px;">
          <p style="margin:0;font-size:11px;color:#8a7c6e;line-height:1.6;">
            This notification was sent because you have a new referral in Aegis. You can manage your notification preferences in your account settings.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
