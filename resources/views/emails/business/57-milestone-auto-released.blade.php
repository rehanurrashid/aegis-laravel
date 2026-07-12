@include('emails._partials.head', ['email_title' => 'Milestone payment auto-released'])

              @if(($recipient_role ?? '') === 'provider')

              {{-- ── PROVIDER copy ── --}}
              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Milestone payment auto-released
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                because no review action was taken within the review window,
                the escrow funds for milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                have been automatically released to
                <strong>{{ $bp_name ?? 'the Business Partner' }}</strong>.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      @if(!empty($amount))
                        <strong>{{ $amount }}</strong> was transferred to the Business Partner's
                        Stripe Connect account.
                      @endif
                      To prevent future auto-releases, review submitted milestones within
                      the {{ config('aegis.milestone_auto_release_days', 7) }}-day review window.
                    </p>
                  </td>
                </tr>
              </table>

              @else

              {{-- ── BP copy ── --}}
              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your milestone payment has been released
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                your milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                was automatically approved and the payment has been released to your
                Stripe Connect account.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.5;">
                      @if(!empty($amount))
                        <strong>{{ $amount }}</strong> has been transferred.
                      @endif
                      It may take 1–2 business days to appear in your Stripe balance
                      depending on your payout schedule.
                    </p>
                  </td>
                </tr>
              </table>

              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View contract
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
