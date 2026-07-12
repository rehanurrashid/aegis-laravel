@include('emails._partials.head', ['email_title' => 'Revision requested on your milestone submission'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Revision requested
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                <strong>{{ $provider_name ?? 'the provider' }}</strong> has reviewed your
                submission for milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                and has requested changes before approving. Escrow funds remain held — they
                will release once your revised submission is approved.
              </p>

              @if(!empty($revision_notes))
              <!-- Feedback box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:700;text-transform:uppercase;
                              letter-spacing:0.8px;color:#7a6030;">Provider feedback</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.6;">
                      {{ \Illuminate\Support\Str::limit($revision_notes, 500) }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              @if(!empty($revision_count) && !empty($max_revisions))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#8a8378;line-height:1.5;">
                Revision {{ $revision_count }} of {{ $max_revisions }} allowed.
                @if((int) $revision_count >= (int) $max_revisions)
                  <strong style="color:#c0392b;">Maximum revisions reached.</strong>
                  If your next submission is rejected, a dispute will be automatically opened.
                @endif
              </p>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') . '/business-partner/milestones' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Resubmit milestone
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Escrow funds remain securely held by Aegis until the milestone is approved
                or the dispute process concludes.
              </p>

@include('emails._partials.foot', ['ungated' => false])
