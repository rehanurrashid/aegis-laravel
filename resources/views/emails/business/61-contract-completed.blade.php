@include('emails._partials.head', ['email_title' => 'Contract completed — please leave a review'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Contract completed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                the contract
                @if(!empty($contract_title))
                  <strong>{{ $contract_title }}</strong>
                @endif
                @if(($recipient_role ?? '') === 'provider')
                  with <strong>{{ $bp_name ?? 'the Business Partner' }}</strong>
                @else
                  with <strong>{{ $provider_name ?? 'the provider' }}</strong>
                @endif
                has been successfully completed. All milestones have been approved and funds
                have been released.
              </p>

              <!-- Summary box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.8;">
                      @if(!empty($total_value))
                        <strong>Total contract value:</strong> {{ $total_value }}<br>
                      @endif
                      @if(!empty($milestone_count))
                        <strong>Milestones completed:</strong> {{ $milestone_count }}<br>
                      @endif
                      @if(!empty($completed_at))
                        <strong>Completed:</strong> {{ \Illuminate\Support\Carbon::parse($completed_at)->format('F j, Y') }}
                      @endif
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Review prompt -->
              <p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;font-weight:700;color:#2d2a26;">
                Leave a review
              </p>
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Reviews help build trust across the Aegis network. Your honest feedback
                helps other
                @if(($recipient_role ?? '') === 'provider')
                  practitioners make informed hiring decisions.
                @else
                  Business Partners understand the working relationship.
                @endif
                It takes less than two minutes.
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    @if(($recipient_role ?? '') === 'provider')
                    <a href="{{ $review_url ?? rtrim(config('app.url'),'/') . '/provider/support-services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Rate this Business Partner
                    </a>
                    @else
                    <a href="{{ $review_url ?? rtrim(config('app.url'),'/') . '/business-partner/contracts' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Rate this practitioner
                    </a>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You have 30 days to leave a review. Reviews are public by default and can
                be made private at any time from your portal.
              </p>

@include('emails._partials.foot', ['ungated' => false])
