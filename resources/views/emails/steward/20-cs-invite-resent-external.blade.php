@include('emails._partials.head', ['email_title' => 'Your Continuity Steward invitation has been resent'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                You've been invited to serve as a Continuity Steward
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $steward_name ?? 'there' }},
              </p>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>{{ $provider_name ?? 'A healthcare practitioner' }}</strong> has resent their invitation for you to serve as their Continuity Steward on the Aegis platform. This invitation expires on <strong>{{ $expiry_date ?? 'soon' }}</strong>.
              </p>

              @if(!empty($follow_up_message))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:600;color:#4a4741;">Message from {{ $provider_name ?? 'Provider' }}</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#4a4741;">{{ $follow_up_message }}</p>
                  </td>
                </tr>
              </table>
              @endif

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:6px;padding:12px 28px;">
                    <a href="{{ $invite_url ?? '#' }}" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                      Accept Invitation &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#6b6460;">
                This invitation expires in {{ $expiry_days ?? 14 }} days. If you did not expect this invitation, you may safely ignore this email.
              </p>

@include('emails._partials.footer')
