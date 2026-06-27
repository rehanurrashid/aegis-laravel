@include('emails._partials.head', ['email_title' => 'Your Continuity Steward may be unresponsive'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Continuity Steward may be unresponsive
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $practitioner_name ?? 'there' }},
                <strong>{{ $ss_name ?? 'Your Support Steward' }}</strong>
                has flagged
                <strong>{{ $cs_name ?? 'your Continuity Steward' }}</strong>
                as potentially unresponsive
                @if(!empty($flagged_at))
                  on {{ $flagged_at }}
                @endif.
                No action has been taken yet — this is an early warning for your awareness.
              </p>

              <!-- Warning box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      If your Steward remains unreachable, Aegis may activate your designated
                      alternate Continuity Steward. We recommend attempting direct contact with
                      {{ $cs_name ?? 'your Steward' }}
                      and reviewing your steward designations in your portal.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $stewards_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review steward designations
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Contact <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                if you need guidance on next steps.
              </p>

@include('emails._partials.foot', ['ungated' => false])
