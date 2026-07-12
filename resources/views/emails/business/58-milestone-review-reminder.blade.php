@include('emails._partials.head', ['email_title' => 'Action required: milestone auto-releases soon'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A milestone payment will auto-release in {{ $hours_remaining ?? 48 }} hours
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                @if(!empty($bp_name))
                  <strong>{{ $bp_name }}</strong> has submitted milestone
                @else
                  a milestone has been submitted
                @endif
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                and is awaiting your review. If no action is taken,
                the escrow funds will be automatically released to the Business Partner
                @if(!empty($auto_release_at))
                  on <strong>{{ \Illuminate\Support\Carbon::parse($auto_release_at)->format('F j, Y \a\t g:i A T') }}</strong>
                @endif.
              </p>

              <!-- Urgency box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fef9ec;border-left:3px solid #ca9e48;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#5c4810;line-height:1.5;">
                      <strong>Action required:</strong>
                      Log in to review this milestone and either approve it, request a revision,
                      or open a dispute. If you take no action, the funds will auto-release
                      to the Business Partner to protect them from non-payment.
                    </p>
                  </td>
                </tr>
              </table>

              @if(!empty($amount))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Amount in escrow: <strong>{{ $amount }}</strong>
              </p>
              @endif

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'), '/') . '/provider/support-services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review milestone now
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This reminder is sent {{ config('aegis.milestone_review_reminder_hours', 48) }} hours
                before the auto-release window closes. You will receive at most one reminder
                per milestone.
              </p>

@include('emails._partials.foot', ['ungated' => false])
