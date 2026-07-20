@include('emails._partials.head', ['email_title' => 'Retainer Agreement — Continuity Steward for ' . ($practitioner_name ?? 'Your Practitioner')])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Standing Retainer Agreement — Continuity Steward
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $cs_name ?? 'there' }},<br><br>
                <strong>{{ $practitioner_name ?? 'A healthcare practitioner' }}</strong> has invited you to
                serve as their <strong>{{ $role_label ?? 'Continuity Steward' }}</strong> on Aegis.
                This is a standing retainer agreement — active from your signature until either party cancels.
                No fees are charged until a critical incident closes and your CS tasks are complete.
              </p>

              <!-- Details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.6;">
                      <strong>Practitioner:</strong> {{ $practitioner_name ?? '—' }}<br>
                      <strong>Your Role:</strong> {{ $role_label ?? 'Continuity Steward' }}<br>
                      @if(!empty($fee_display))
                      <strong>Fee per incident:</strong> {{ $fee_display }}<br>
                      @endif
                      <strong>Retainer active:</strong> From signing until cancelled by either party
                    </p>
                    <p style="margin:6px 0 0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#6b6560;">
                      Accepting means you will countersign the retainer agreement and take on the
                      stewardship responsibilities described within it. Annual re-attestation required.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA — goes directly to CS portal dashboard -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $invite_url ?? rtrim(config('app.url'),'/').'/continuity-steward/dashboard' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review &amp; Sign Retainer Agreement →
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#4a4741;line-height:1.5;">
                Log in to your Aegis Continuity Steward account to review the full plan details,
                your responsibilities, and the compensation terms before signing.
              </p>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you do not wish to accept this designation, you may decline it directly
                in your portal. The practitioner will be notified of your decision.
              </p>

@include('emails._partials.foot', ['ungated' => false])
