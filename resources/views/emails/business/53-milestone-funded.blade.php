@include('emails._partials.head', ['email_title' => 'Milestone funded — ready to work'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Milestone funded — you can begin work
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                <strong>{{ $provider_name ?? 'the provider' }}</strong> has funded escrow for
                milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>.
                @else
                  on your contract.
                @endif
                The funds are locked in and ready to release upon your approved submission.
              </p>

              <!-- Details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.8;">
                      @if(!empty($amount))
                        <strong>Funded:</strong> {{ $amount }}<br>
                      @endif
                      @if(!empty($due_at))
                        <strong>Due:</strong> {{ $due_at }}<br>
                      @endif
                      <strong>Status:</strong> Funds held in Aegis escrow
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Complete the deliverables described in your contract, then submit through the
                Milestones section of your portal. The provider has
                <strong>{{ config('aegis.milestone_auto_release_days', 7) }} days</strong>
                to review before funds auto-release.
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') . '/business-partner/milestones' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View milestone
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
