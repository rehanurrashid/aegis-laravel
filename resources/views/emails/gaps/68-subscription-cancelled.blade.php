@include('emails._partials.head', ['email_title' => 'Your Aegis subscription has been cancelled'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your subscription has been cancelled
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $practitioner_name ?? 'there' }},
                your Aegis
                @if(!empty($tier_label))
                  <strong>{{ $tier_label }}</strong>
                @endif
                subscription has been cancelled. We are sorry to see you go.
              </p>

              <!-- Access and data info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($access_until_date))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Access continues until:</strong>
                      {{ $access_until_date }}
                    </p>
                    @endif
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your data, plan records, and documents remain safe and accessible
                      until your paid period ends. After that, your data is retained
                      per our retention policy before being permanently deleted.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $reactivate_url ?? rtrim(config('app.url'),'/') . '/billing' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Reactivate my subscription
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you cancelled by mistake or change your mind, you can reactivate
                at any time before your access period ends.
              </p>

@include('emails._partials.foot', ['ungated' => true])
