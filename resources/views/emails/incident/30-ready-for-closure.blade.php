@include('emails._partials.head', ['email_title' => 'Incident ready for closure review'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Incident ready for closure
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                your Continuity Steward has completed all required tasks and has marked
                the incident for
                <strong>{{ $practitioner_name ?? 'the practitioner' }}</strong>
                as ready to close. Please review and confirm closure to formally end
                the incident record.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      @if(!empty($cs_name))
                        <strong>Continuity Steward:</strong> {{ $cs_name }}<br>
                      @endif
                      @if(!empty($completed_tasks))
                        <strong>Tasks completed:</strong> {{ $completed_tasks }}<br>
                      @endif
                      Your confirmation is required before the incident is formally closed
                      and vault access is revoked.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $incident_url ?? rtrim(config('app.url'),'/') . '/provider/continuity-plan' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review &amp; confirm closure
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you need more time, you can keep the incident open and request
                additional tasks from your CS portal.
              </p>

@include('emails._partials.foot', ['ungated' => true])
