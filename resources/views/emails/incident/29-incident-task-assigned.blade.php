@include('emails._partials.head', ['email_title' => 'Incident task assigned to you'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A task has been assigned to you
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $cs_name ?? 'there' }},
                a task has been assigned to you as part of the active continuity response
                for
                <strong>{{ $practitioner_name ?? 'your practitioner' }}</strong>.
              </p>

              <!-- Task details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;font-weight:600;color:#2d2a26;line-height:1.4;">
                      {{ $task_title ?? 'Assigned task' }}
                    </p>
                    @if(!empty($task_due_date))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Due:</strong>
                      {{ $task_due_date }}
                    </p>
                    @endif
                    @if(!empty($task_note))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $task_note }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $task_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View task
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                @if(!empty($incident_id))
                  Incident reference: {{ $incident_id }}.
                @endif
                All task updates are logged against the incident record.
              </p>

@include('emails._partials.foot', ['ungated' => false])
