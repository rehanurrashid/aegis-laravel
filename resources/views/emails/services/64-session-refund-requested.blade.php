@include('emails._partials.head', ['email_title' => 'Refund requested'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Refund request — action required
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br>
                <strong>{{ $client_name ?? 'Your client' }}</strong> has submitted a refund request for the session
                <strong>{{ $service_title ?? '' }}</strong>.
                You have <strong>{{ $reply_days ?? 5 }} days</strong> to respond before this is auto-escalated.
              </p>

              <!-- Refund detail -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fffbf0;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">Refund details</p>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      <strong>Amount requested:</strong> {{ $amount_requested ?? '' }}
                    </p>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      <strong>Type:</strong> {{ $refund_type_label ?? '' }}
                    </p>
                    @if(!empty($reason))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      <strong>Reason:</strong> {{ $reason }}
                    </p>
                    @endif
                    @if(!empty($reason_detail))
                    <p style="margin:6px 0 0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#6a6460;font-style:italic;">
                      {{ $reason_detail }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#4a4741;line-height:1.6;">
                Log in to your Services dashboard to approve or deny this request.
                If you do not respond within {{ $reply_days ?? 5 }} days, the request may be auto-escalated to a dispute.
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
                      Review refund request
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This request was submitted through Aegis. Do not reply to this email.
              </p>

@include('emails._partials.foot', ['ungated' => false])
