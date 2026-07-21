@include('emails._partials.head', ['email_title' => 'Milestone approved — payment sent direct'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                @if(($data['type'] ?? 'milestone') === 'completion')
                  Contract completion payment processed
                @else
                  Milestone approved — payment sent
                @endif
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br><br>
                @if(!empty($milestone_title))
                  Milestone <strong>{{ $milestone_title }}</strong> has been approved.
                @else
                  Your milestone has been approved.
                @endif
                Payment has been sent directly to the Business Partner's Stripe Connect account.
              </p>

              @if(!empty($amount))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1e5c3b;line-height:1.8;">
                      <strong>Amount paid:</strong> {{ $amount }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              @include('emails.partials.direct-to-bp-disclosure')

@include('emails._partials.foot')
