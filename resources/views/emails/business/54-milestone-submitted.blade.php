@include('emails._partials.head', ['email_title' => 'Milestone submitted — review required'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A milestone is ready for your review
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                <strong>{{ $bp_name ?? 'your Business Partner' }}</strong> has submitted
                @if(!empty($milestone_title))
                  milestone <strong>{{ $milestone_title }}</strong>
                @else
                  a milestone
                @endif
                for review. Please log in, review the deliverables, and take one of three
                actions: approve, request a revision, or open a dispute.
              </p>

              <!-- Urgency box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fef9ec;border-left:3px solid #ca9e48;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#5c4810;line-height:1.5;">
                      <strong>Action required within
                        {{ config('aegis.milestone_auto_release_days', 7) }} days.</strong>
                      If no action is taken, escrow funds will auto-release to the Business
                      Partner to protect against non-payment.
                      @if(!empty($auto_release_at))
                        Auto-release scheduled for
                        <strong>{{ \Illuminate\Support\Carbon::parse($auto_release_at)->format('F j, Y') }}</strong>.
                      @endif
                    </p>
                  </td>
                </tr>
              </table>

              @if(!empty($submission_notes))
              <p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>Submission notes:</strong><br>
                {{ \Illuminate\Support\Str::limit($submission_notes, 300) }}
              </p>
              @endif

              @if(!empty($amount))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Amount in escrow: <strong>{{ $amount }}</strong>
              </p>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') . '/provider/support-services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review milestone
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You will receive a reminder {{ config('aegis.milestone_review_reminder_hours', 48) }}
                hours before the auto-release window closes.
              </p>

@include('emails._partials.foot', ['ungated' => false])
