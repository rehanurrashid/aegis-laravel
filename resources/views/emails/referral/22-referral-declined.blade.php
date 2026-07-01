@php
    $referral  = \App\Models\Referral::with(['recipient', 'meta'])->find($referral_id);
    if (!$referral) return;
    $recipient    = $referral->recipient;
    $subject      = $referral->meta->firstWhere('meta_key', 'reason')?->meta_value
                 ?? $referral->meta->firstWhere('meta_key', 'presenting_issue')?->meta_value
                 ?? $referral->subject ?? 'Client referral';
    $declineReason = $referral->meta->firstWhere('meta_key', 'decline_reason')?->meta_value;
    $url          = url('/provider/referrals');
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>Referral Declined — Aegis</title></head>
<body style="margin:0;padding:0;background:#f5f0e8;font-family:'Inter',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <tr><td style="background:#2c2218;padding:24px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="font-family:'Spectral',Georgia,serif;font-size:22px;font-weight:700;color:#ffffff;">Aegis</td>
              <td align="right" style="font-size:11px;color:#c4a96a;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Referral Update</td>
            </tr>
          </table>
        </td></tr>
        <tr><td style="padding:32px;">
          <p style="margin:0 0 6px;font-size:14px;color:#8a7c6e;">Your referral received a response.</p>
          <p style="margin:0 0 24px;font-family:'Spectral',Georgia,serif;font-size:24px;font-weight:700;color:#2c2218;line-height:1.3;">
            {{ $recipient?->display_name ?? 'Your colleague' }} is unable to accept this referral
          </p>
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8e0d4;border-radius:8px;overflow:hidden;margin-bottom:24px;">
            <tr style="background:#f9f5ee;">
              <td style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;border-bottom:1px solid #e8e0d4;">Provider</td>
              <td style="padding:10px 16px;font-size:13px;color:#2c2218;font-weight:600;border-bottom:1px solid #e8e0d4;">{{ $recipient?->display_name }}{{ $recipient?->credentials ? ', ' . $recipient->credentials : '' }}</td>
            </tr>
            <tr>
              <td style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;border-bottom:1px solid #e8e0d4;">Referral</td>
              <td style="padding:10px 16px;font-size:13px;color:#2c2218;border-bottom:1px solid #e8e0d4;">{{ $subject }}</td>
            </tr>
            @if($declineReason)
            <tr>
              <td style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#8a7c6e;">Reason</td>
              <td style="padding:10px 16px;font-size:13px;color:#2c2218;">{{ $declineReason }}</td>
            </tr>
            @endif
          </table>
          <p style="margin:0 0 20px;font-size:13px;color:#6b5f52;line-height:1.6;">
            You can re-refer this client to another provider in your Aegis network. Your referral history is saved for your records.
          </p>
          <a href="{{ $url }}" style="display:inline-block;background:#2c2218;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;padding:12px 24px;border-radius:8px;">
            Find Another Provider →
          </a>
        </td></tr>
        <tr><td style="background:#f9f5ee;border-top:1px solid #e8e0d4;padding:20px 32px;">
          <p style="margin:0;font-size:11px;color:#8a7c6e;">You're receiving this because one of your Aegis referrals received a response.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
