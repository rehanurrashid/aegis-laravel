@include('emails._partials.head', ['email_title' => 'Incident auto-closed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your incident has been automatically closed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $user_name ?? 'there' }},
                the continuity incident in your account has been automatically closed
                after {{ $window_days ?? 7 }} days without a provider response to the
                closure request. The incident record is preserved for your audit trail.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      @if(!empty($incident_id))
                        <strong>Incident reference:</strong> {{ $incident_id }}<br>
                      @endif
                      Vault access has been sealed. Your Continuity Steward has been
                      notified and will issue their engagement invoice separately.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $incident_url ?? rtrim(config('app.url'),'/') . '/provider/continuity-plan' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View incident record
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you believe this closure was made in error, please contact
                <a href="mailto:support@maatpracticefirm.com"
                   style="color:#8a8378;">support@maatpracticefirm.com</a>.
              </p>

@include('emails._partials.foot', ['ungated' => true])
