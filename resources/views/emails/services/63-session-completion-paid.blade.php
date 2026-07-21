@include('emails._partials.head', ['email_title' => 'Session payment complete'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Session complete — payment received
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br>
                @if(!empty($other_party_name))
                  {{ $other_party_name }} has confirmed the session for
                  <strong>{{ $service_title ?? 'your service' }}</strong>. Full payment has been received.
                @else
                  Your session for <strong>{{ $service_title ?? 'your session' }}</strong> is complete
                  and payment has been processed.
                @endif
              </p>

              <!-- Payment summary -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #3d7a5a;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($payment_terms))
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#3d7a5a;
                              text-transform:uppercase;letter-spacing:0.5px;">Payment terms</p>
                    <p style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">{{ $payment_terms }}</p>
                    @endif
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#3d7a5a;
                              text-transform:uppercase;letter-spacing:0.5px;">Total received</p>
                    <p style="margin:0 0 8px;font-family:'Georgia','Times New Roman',serif;
                              font-size:20px;font-weight:700;color:#2d2a26;">
                      {{ $total_amount ?? '' }}
                    </p>
                    @if(!empty($upfront_amount) && !empty($completion_amount))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#4a4741;">
                      Upfront: {{ $upfront_amount }} &nbsp;·&nbsp; Completion: {{ $completion_amount }}
                    </p>
                    @elseif(!empty($completion_amount))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#4a4741;">
                      Full payment: {{ $completion_amount }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              @if(!empty($payout_note))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;line-height:1.6;color:#4a4741;">
                {{ $payout_note }}
              </p>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $services_url ?? rtrim(config('app.url'),'/') . '/provider/services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View sessions
                    </a>
                  </td>
                </tr>
              </table>

              @include('emails._partials.direct-to-provider-disclosure')

              <p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Thank you for using Aegis for your clinical services.
              </p>

@include('emails._partials.foot', ['ungated' => false])
