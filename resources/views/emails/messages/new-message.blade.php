@include('emails._partials.head', ['email_title' => 'New message from ' . ($sender_name ?? 'a contact')])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                New message from {{ $sender_name ?? 'a contact' }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br>
                you have received a new message on Aegis.
              </p>

              <!-- Message preview -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($thread_title))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:11px;font-weight:700;color:#7a746c;
                              text-transform:uppercase;letter-spacing:0.6px;">Conversation</p>
                    <p style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;font-weight:700;color:#2d2a26;">{{ $thread_title }}</p>
                    @endif
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:11px;font-weight:700;color:#7a746c;
                              text-transform:uppercase;letter-spacing:0.6px;">Message</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.6;
                              white-space:pre-line;">{{ $message_preview ?? '' }}</p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
                <tr>
                  <td style="border-radius:6px;background-color:#a0813e;">
                    <a href="{{ $messages_url ?? ($portal_url ?? rtrim(config('app.url'),'/') . '/messages') }}"
                       style="display:inline-block;padding:12px 28px;font-family:Arial,Helvetica,sans-serif;
                              font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                      View Message
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;
                        line-height:1.6;color:#7a746c;">
                You are receiving this because you have message notifications enabled.
                Manage preferences in
                <a href="{{ $settings_url ?? rtrim(config('app.url'),'/') . '/settings' }}"
                   style="color:#a0813e;text-decoration:none;">Settings</a>.
              </p>

@include('emails._partials.foot')
