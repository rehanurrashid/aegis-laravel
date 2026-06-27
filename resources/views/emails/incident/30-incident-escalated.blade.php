@include('emails._partials.head', ['email_title' => 'Incident escalation requested'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Incident escalation requires oversight
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                An active incident has been escalated to the Aegis team for oversight.
                Please review the incident record and take appropriate action.
              </p>

              <!-- Escalation details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($incident_id))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reference:</strong>
                      {{ $incident_id }}
                    </p>
                    @endif
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Practitioner:</strong>
                      {{ $practitioner_name ?? 'Unknown' }}
                    </p>
                    @if(!empty($cs_name))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Continuity Steward:</strong>
                      {{ $cs_name }}
                    </p>
                    @endif
                    @if(!empty($escalated_by))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Escalated by:</strong>
                      {{ $escalated_by }}
                    </p>
                    @endif
                    @if(!empty($escalated_at))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Escalated:</strong>
                      {{ $escalated_at }}
                    </p>
                    @endif
                    @if(!empty($escalation_reason))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reason:</strong>
                      {{ $escalation_reason }}
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
                    <a href="{{ $admin_url ?? rtrim(config('app.url'),'/') . '/admin' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review in admin portal
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This is an internal Aegis notification. Do not forward to external parties.
              </p>

@include('emails._partials.foot', ['ungated' => true])
