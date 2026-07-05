@include('emails._partials.head', ['email_title' => 'Event submission received — Aegis'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your event submission is under review
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $submitter_name ?? 'there' }}, thank you for submitting
                <strong>{{ $event_title ?? 'your event' }}</strong> to the Aegis community calendar.
                Our team will review it within 2 business days and notify you of the outcome.
              </p>

              <!-- Details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">Submission summary</p>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Event:</strong> {{ $event_title ?? '—' }}
                    </p>
                    @if(!empty($event_date))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Date:</strong> {{ $event_date }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Approved events are listed on the Aegis Events page and promoted to the community.
                If changes are needed, we will reach out before publishing.
              </p>

@include('emails._partials.foot', ['ungated' => false])
