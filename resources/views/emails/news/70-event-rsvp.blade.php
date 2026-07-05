@include('emails._partials.head', ['email_title' => 'Event registration confirmed — Aegis'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                You're registered!
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $practitioner_name ?? 'there' }}, your registration for
                <strong>{{ $event_title ?? 'the event' }}</strong> has been confirmed.
              </p>

              <!-- Event details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">Event details</p>
                    @if(!empty($event_date))
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      📅 {{ $event_date }}
                    </p>
                    @endif
                    @if(!empty($event_location))
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      📍 {{ $event_location }}
                    </p>
                    @endif
                    @if(!empty($ceu_credits) && $ceu_credits > 0)
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      🎓 {{ $ceu_credits }} CEU credit{{ $ceu_credits == 1 ? '' : 's' }} offered
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $events_url ?? rtrim(config('app.url'),'/').'/provider/news/events' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View my events
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You can cancel your registration from the Events page at any time before the event.
              </p>

@include('emails._partials.foot', ['ungated' => false])
