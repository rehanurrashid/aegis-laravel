@include('emails._partials.head', ['email_title' => 'Your Aegis subscription renews soon'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your subscription renews in 7 days
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $practitioner_name ?? 'there' }},
                your Aegis
                @if(!empty($plan_label))
                  <strong>{{ $plan_label }}</strong>
                @endif
                subscription will automatically renew on
                <strong>{{ $renewal_date ?? 'your renewal date' }}</strong>
                @if(!empty($amount))
                  for <strong>{{ $amount }}</strong>
                @endif.
              </p>

              <!-- Renewal info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Renewal is automatic — no action is needed if your payment details
                      are current. If you wish to make changes to your plan or billing
                      information, please do so before the renewal date.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $billing_url ?? rtrim(config('app.url'),'/') . '/billing' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Manage billing
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You will receive a payment receipt after your renewal is processed.
              </p>

@include('emails._partials.foot', ['ungated' => false])
