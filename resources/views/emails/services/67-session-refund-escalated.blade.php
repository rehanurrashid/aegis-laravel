@include('emails._partials.head', ['email_title' => 'Dispute opened'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                {{ $to_provider ? 'A dispute has been opened against you' : 'Your dispute has been submitted' }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br>
                @if(!empty($to_provider))
                  <strong>{{ $client_name ?? 'A client' }}</strong> has escalated their denied refund request for
                  <strong>{{ $service_title ?? 'a session' }}</strong> to a formal dispute.
                  You must respond within <strong>{{ $reply_days ?? 5 }} days</strong>.
                @else
                  Your refund request for <strong>{{ $service_title ?? 'a session' }}</strong>
                  has been escalated to a formal dispute. Our team will review the case and contact you.
                @endif
              </p>

              <!-- Dispute reference -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fff0f0;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#c0392b;
                              text-transform:uppercase;letter-spacing:0.5px;">Dispute reference</p>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      <strong>Dispute ID:</strong> {{ $dispute_id ?? '' }}
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      <strong>Amount in dispute:</strong> {{ $amount ?? '' }}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;line-height:1.6;color:#4a4741;">
                @if(!empty($to_provider))
                  Log in to Aegis and navigate to your Disputes section to submit your response.
                  Failure to respond may result in automatic resolution in the client's favour.
                @else
                  You can monitor the status of your dispute in your Aegis account under Finances → Disputes.
                  An Aegis administrator will review all evidence and reach a decision.
                @endif
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
                      View dispute
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Do not reply to this email. All communications must go through the Aegis messaging system.
              </p>

@include('emails._partials.foot', ['ungated' => false])
