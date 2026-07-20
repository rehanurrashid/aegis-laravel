@include('emails._partials.head', ['email_title' => 'Your Support Steward role has been terminated'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Support Steward role has ended
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $ss_name ?? 'there' }},<br><br>
                {{ $practitioner_name ?? 'The practitioner' }} has removed you as a Support Steward on Aegis.
                Your access to their Continuity Plan and associated resources has been revoked effective immediately.
              </p>

              @if(!empty($reason))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf0f0;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:700;text-transform:uppercase;
                              letter-spacing:0.4px;color:#8b1a1a;">Reason</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">{{ $reason }}</p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Your Aegis account remains active and your other roles are unaffected.
                If you believe this was made in error, please contact the practitioner directly.
              </p>

@include('emails._partials.footer')
