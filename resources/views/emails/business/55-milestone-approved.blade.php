@include('emails._partials.head', ['email_title' => 'Milestone approved — payment released'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Milestone approved — payment on its way
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                <strong>{{ $provider_name ?? 'the provider' }}</strong> has approved your
                submission for milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>.
                @else
                  on your contract.
                @endif
                Escrow funds have been released to your Stripe Connect account.
              </p>

              <!-- Payment confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.8;">
                      @if(!empty($amount))
                        <strong>Amount released:</strong> {{ $amount }}<br>
                      @endif
                      @if(!empty($approved_at))
                        <strong>Approved:</strong> {{ \Illuminate\Support\Carbon::parse($approved_at)->format('F j, Y') }}<br>
                      @endif
                      Funds will appear in your Stripe balance within 1–2 business days
                      depending on your payout schedule.
                    </p>
                  </td>
                </tr>
              </table>

              @if(!empty($next_milestone_title))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>Up next:</strong> {{ $next_milestone_title }}
                @if(!empty($next_milestone_due))
                  · Due {{ $next_milestone_due }}
                @endif
              </p>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') . '/business-partner/finances' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View in Finances
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
