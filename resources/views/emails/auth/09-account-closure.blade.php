@include('emails._partials.head', ['email_title' => 'Your Aegis account has been closed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Account closure confirmed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                your Aegis account has been closed
                @if(!empty($closed_at))
                  on {{ $closed_at }}
                @endif.
                We are sorry to see you go.
              </p>

              <!-- Retention info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your data will be retained for
                      {{ ($data_retention_days ?? '30') }}
                      days in accordance with our data retention policy, after which it will be
                      permanently deleted. You may export your data before that window closes.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              @if(!empty($export_url))
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $export_url }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Export my data
                    </a>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you believe this closure was made in error, contact us at
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>.
                @if(!empty($reopen_url))
                  You may also
                  <a href="{{ $reopen_url }}"
                     style="color:#8a8378;">request account reactivation</a>.
                @endif
              </p>

@include('emails._partials.foot', ['ungated' => true])
