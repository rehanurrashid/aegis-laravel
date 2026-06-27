@include('emails._partials.head', ['email_title' => 'Your milestone has been approved'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Milestone approved — payment processing
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $bp_name ?? 'there' }},
                {{ $practitioner_name ?? 'The practitioner' }}
                has approved your milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                @if(!empty($approved_at))
                  on {{ $approved_at }}
                @endif.
                Payment is being processed.
              </p>

              <!-- Payment info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      @if(!empty($payout_eta))
                        Estimated payout date:
                        <strong>{{ $payout_eta }}</strong>.
                      @else
                        You will receive a separate notification once the payout has been released.
                      @endif
                      Funds are transferred to your connected payout account via Stripe.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $portal_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View payment status
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Payment timelines depend on your Stripe Connect account and banking provider.
              </p>

@include('emails._partials.foot', ['ungated' => false])
