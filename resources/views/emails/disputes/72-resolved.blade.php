@include('emails._partials.head', ['email_title' => 'Your dispute has been resolved'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Dispute resolved
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $user_name ?? 'there' }},
                Aegis has reviewed all evidence and issued a resolution for your dispute
                @if(!empty($dispute_id))
                  ({{ $dispute_id }})
                @endif.
              </p>

              <!-- Resolution box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.8;">
                      @if(!empty($resolution_label))
                        <strong>Outcome:</strong> {{ $resolution_label }}<br>
                      @endif
                      @if(!empty($resolution_summary))
                        <strong>Summary:</strong> {{ $resolution_summary }}
                      @endif
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Any escrow adjustments (fund releases or refunds) resulting from this
                decision have been processed automatically. Check your Finances portal
                for updated balances.
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ rtrim(config('app.url'),'/') . '/provider/disputes' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View resolution details
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Aegis dispute decisions are final per our platform terms. If you have
                questions, contact
                <a href="mailto:support@maatpracticefirm.com" style="color:#8a8378;">support@maatpracticefirm.com</a>.
              </p>

@include('emails._partials.foot', ['ungated' => false])
