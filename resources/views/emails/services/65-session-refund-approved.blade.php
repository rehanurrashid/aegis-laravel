@include('emails._partials.head', ['email_title' => 'Refund approved'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your refund has been approved
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br>
                The provider has approved your refund request for
                <strong>{{ $service_title ?? 'your session' }}</strong>.
              </p>

              <!-- Refund amount -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #3d7a5a;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#3d7a5a;
                              text-transform:uppercase;letter-spacing:0.5px;">Refund approved</p>
                    <p style="margin:0;font-family:'Georgia','Times New Roman',serif;
                              font-size:20px;font-weight:700;color:#2d2a26;">
                      {{ $refund_amount ?? '' }}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;line-height:1.6;color:#4a4741;">
                The refund will be returned to your original payment method.
                Please allow <strong>5–10 business days</strong> for the funds to appear depending on your bank or card issuer.
              </p>

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
                If you have questions, contact support through the Aegis messaging system.
              </p>

@include('emails._partials.foot', ['ungated' => false])
