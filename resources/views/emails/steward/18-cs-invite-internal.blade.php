@include('emails._partials.head', ['email_title' => 'You have been invited to be a Continuity Steward'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                You have been invited as a Continuity Steward
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $cs_name ?? 'there' }},
                {{ $practitioner_name ?? 'A practitioner' }}
                has designated you as their Continuity Steward on Aegis. Please review the
                invitation and accept or decline through your portal.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      This invitation expires in
                      {{ ($expires_days ?? '14') }} days.
                      Accepting means you will countersign their Continuity Plan and take on
                      the stewardship responsibilities described within it.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $invite_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review Invitation in Portal
                    </a>
                  </td>
                </tr>
              </table>

              @if(!empty($invitation_code))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border:1px solid #d4c4a0;
                             border-radius:6px;padding:16px 20px;text-align:center;">
                    <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#8a8378;text-transform:uppercase;
                              letter-spacing:0.5px;">Your Invitation Code</p>
                    <p style="margin:0;font-family:'Courier New',monospace;font-size:18px;
                              font-weight:700;color:#2d2a26;letter-spacing:1px;">
                      {{ $invitation_code }}
                    </p>
                    <p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;
                              font-size:11px;color:#8a8378;">
                      You can also manage this invitation directly in your Aegis portal.
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You can also manage this invitation directly in your Aegis portal.
              </p>

@include('emails._partials.foot', ['ungated' => false])
