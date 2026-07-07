@include('emails._partials.head', ['email_title' => 'Session completed — payment released'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Session completed &amp; payment released
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $practitioner_name ?? 'there' }},<br>
                <strong>{{ $client_name ?? 'Your client' }}</strong> has confirmed the session
                for <strong>{{ $service_title ?? 'your service' }}</strong> as complete.
              </p>

              <!-- Payment summary -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Payment initiated
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:20px;font-weight:700;color:#2f7d54;line-height:1.3;">
                      {{ $amount ?? '' }}
                    </p>
                    <p style="margin:4px 0 0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#4a4741;">
                      {{ $payout_note ?? 'Transfer to your connected Stripe account is underway.' }}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $services_url ?? rtrim(config('app.url'),'/') . '/provider/services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View my sessions
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Stripe transfers typically arrive within 2 business days depending on your payout schedule.
              </p>

@include('emails._partials.foot', ['ungated' => false])
