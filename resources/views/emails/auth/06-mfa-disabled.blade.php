@include('emails._partials.head', ['email_title' => 'Two-factor authentication disabled'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Two-factor authentication disabled
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                two-factor authentication has been removed from your Aegis account
                @if(!empty($disabled_at))
                  on {{ $disabled_at }}
                @endif.
                Your account now signs in with password only.
              </p>

              <!-- Security alert box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      If you did not make this change, please secure your account immediately
                      by resetting your password and re-enabling two-factor authentication.
                      Contact
                      <a href="mailto:support@aegis.devlet.tech"
                         style="color:#c0392b;text-decoration:none;">support@aegis.devlet.tech</a>
                      if you need assistance.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $settings_url ?? rtrim(config('app.url'),'/') . '/settings' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review account settings
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you made this change yourself, no further action is required.
              </p>

@include('emails._partials.foot', ['ungated' => true])
