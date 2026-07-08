@include('emails._partials.head', ['email_title' => 'Your sign-in code'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your sign-in verification code
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Hi {{ $recipient_name ?? 'there' }},
              </p>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Use the code below to complete your sign-in to Aegis.
                This code expires in <strong>10 minutes</strong>.
              </p>

              <!-- OTP code box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="background-color:#f5f0e8;border:2px solid #a0813e;
                                   border-radius:10px;padding:20px 40px;text-align:center;">
                          <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                                    font-size:11px;font-weight:700;text-transform:uppercase;
                                    letter-spacing:1px;color:#a0813e;">
                            Verification Code
                          </p>
                          <p style="margin:0;font-family:'Courier New',Courier,monospace;
                                    font-size:36px;font-weight:700;color:#2d2a26;
                                    letter-spacing:10px;">
                            {{ $otp_code }}
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Warning box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fef9ec;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Never share this code</strong> with anyone.
                      Aegis staff will never ask for your verification code.
                      If you did not attempt to sign in, please
                      <a href="{{ $settings_url ?? rtrim(config('app.url'),'/') . '/settings' }}"
                         style="color:#a0813e;">secure your account immediately</a>.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This code expires at {{ $expires_at ?? 'in 10 minutes' }}.
                If you need a new code, return to the sign-in page and try again.
              </p>

@include('emails._partials.foot', ['ungated' => true])
