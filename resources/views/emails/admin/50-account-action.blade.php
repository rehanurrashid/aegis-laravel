@include('emails._partials.head', ['email_title' => 'Administrative action on your Aegis account'])
@php
    $_action = strtolower($action_label ?? 'updated');
    $_is_lock = ($_action === 'locked');
@endphp

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your account has been {{ $_action }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                an administrative action has been taken on your Aegis account
                @if(!empty($actioned_at))
                  on {{ $actioned_at }}
                @endif.
              </p>

              <!-- Action details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:{{ $_is_lock ? '#fdf2f2' : '#f5f0e6' }};
                             border-left:3px solid {{ $_is_lock ? '#c0392b' : '#a0813e' }};
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Action:</strong>
                      Account {{ $_action }}
                    </p>
                    @if(!empty($action_reason))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reason:</strong>
                      {{ $action_reason }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $support_url ?? rtrim(config('app.url'),'/') . '/support' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Contact support
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you believe this action was taken in error, contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                with your account details.
              </p>

@include('emails._partials.foot', ['ungated' => true])
