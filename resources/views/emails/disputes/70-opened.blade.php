@include('emails._partials.head', ['email_title' => 'A dispute has been opened'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A dispute has been opened against you
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $respondent_name ?? 'there' }},
                a formal dispute has been opened on your Aegis account.
                Aegis will mediate this dispute. Please log in to review the claim and
                submit your response within
                <strong>{{ $reply_by_days ?? 5 }} days</strong>.
              </p>

              <!-- Dispute details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf0f0;border-left:3px solid #a02d22;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.8;">
                      @if(!empty($dispute_id))
                        <strong>Reference:</strong> {{ $dispute_id }}<br>
                      @endif
                      @if(!empty($reason))
                        <strong>Reason:</strong> {{ $reason }}<br>
                      @endif
                      @if(!empty($amount_dollars))
                        <strong>Amount in dispute:</strong> ${{ $amount_dollars }}<br>
                      @endif
                      <strong>Response deadline:</strong>
                      {{ now()->addDays($reply_by_days ?? 5)->format('F j, Y') }}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Aegis holds any disputed funds in escrow until the dispute is resolved.
                Failing to respond within the deadline may result in the dispute being
                decided in the other party's favour.
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
                      View dispute &amp; respond
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Disputes are managed by Aegis in accordance with our platform terms.
                Contact <a href="mailto:support@maatpracticefirm.com" style="color:#8a8378;">support@maatpracticefirm.com</a>
                if you need assistance.
              </p>

@include('emails._partials.foot', ['ungated' => false])
