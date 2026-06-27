@include('emails._partials.head', ['email_title' => 'Your Alternate Continuity Steward has been activated'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Alternate Continuity Steward activated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                {{ $alternate_cs_name ?? 'the designated alternate' }}
                has been activated as the Continuity Steward
                @if(!empty($practitioner_name))
                  for {{ $practitioner_name }}
                @endif
                @if(!empty($activated_at))
                  on {{ $activated_at }}
                @endif.
                @if(!empty($former_cs_name))
                  {{ $former_cs_name }}
                  is no longer the active Steward on this plan.
                @endif
              </p>

              <!-- Status box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The Continuity Plan remains active. The alternate Steward now holds all
                      responsibilities previously assigned to the primary Steward.
                      A countersignature renewal may be required — you will be notified if so.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $plan_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View the plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Contact <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                if you have questions about this transition.
              </p>

@include('emails._partials.foot', ['ungated' => false])
