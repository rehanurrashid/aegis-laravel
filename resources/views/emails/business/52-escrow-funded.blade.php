@include('emails._partials.head', ['email_title' => 'Escrow funded — work can begin'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Escrow funded — you're clear to start
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                <strong>{{ $provider_name ?? 'the provider' }}</strong> has funded escrow for
                @if(!empty($contract_title))
                  the contract <strong>{{ $contract_title }}</strong>.
                @else
                  your contract.
                @endif
                Funds are held securely by Aegis and will be released to you upon milestone approval.
              </p>

              <!-- Green confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.5;">
                      @if(!empty($amount))
                        <strong>{{ $amount }}</strong> is now held in escrow.
                      @endif
                      Submit your completed milestone deliverables through the portal — funds
                      release automatically once approved.
                    </p>
                  </td>
                </tr>
              </table>

              @if(!empty($milestone_title))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                First milestone: <strong>{{ $milestone_title }}</strong>
                @if(!empty($due_at))
                  · Due <strong>{{ $due_at }}</strong>
                @endif
              </p>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') . '/business-partner/contracts' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View contract &amp; milestones
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Aegis holds these funds in escrow on behalf of both parties until milestone
                approval. If a dispute arises, Aegis mediates the resolution.
              </p>

@include('emails._partials.foot', ['ungated' => false])
