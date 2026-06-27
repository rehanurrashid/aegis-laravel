@include('emails._partials.head', ['email_title' => 'Response to your service inquiry'])
@php
    $_status   = strtolower($status_label ?? 'responded to');
    $_accepted = ($_status === 'accepted');
@endphp

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your service inquiry was {{ $_status }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $inquirer_name ?? 'there' }},
                <strong>{{ $practitioner_name ?? 'The practitioner' }}</strong>
                has {{ $_status }} your inquiry
                @if(!empty($service_title))
                  for <strong>{{ $service_title }}</strong>
                @endif.
              </p>

              <!-- Response box — green if accepted, gold if declined -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:{{ $_accepted ? '#f0faf4' : '#f5f0e6' }};
                             border-left:3px solid {{ $_accepted ? '#2f7d54' : '#a0813e' }};
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($response_note))
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Note from {{ $practitioner_name ?? 'the practitioner' }}
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $response_note }}
                    </p>
                    @elseif($_accepted)
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The practitioner will be in touch with you directly to discuss next steps.
                    </p>
                    @else
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      No reason was provided. You may browse other services on Aegis.
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
                    <a href="{{ $service_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      {{ $_accepted ? 'View service details' : 'Browse services' }}
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Your inquiry record is available in your portal for reference.
              </p>

@include('emails._partials.foot', ['ungated' => false])
