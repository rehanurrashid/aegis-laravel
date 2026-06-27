@include('emails._partials.head', ['email_title' => 'Your referral has received a response'])
@php
    $_status  = strtolower($status_label ?? 'responded to');
    $_accepted = ($_status === 'accepted');
@endphp

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your referral was {{ $_status }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $referrer_name ?? 'there' }},
                <strong>{{ $recipient_name ?? 'The recipient' }}</strong>
                has {{ $_status }} your referral
                @if(!empty($client_initials))
                  for client {{ $client_initials }}
                @endif
                @if(!empty($responded_at))
                  on {{ $responded_at }}
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
                      Note from {{ $recipient_name ?? 'them' }}
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $response_note }}
                    </p>
                    @else
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      @if($_accepted)
                        The referral has been accepted. The recipient will reach out to the
                        client directly.
                      @else
                        No reason was provided. You may send the referral to another
                        practitioner in your Integrative Network.
                      @endif
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
                    <a href="{{ $referral_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View referral record
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Referral records are retained for your network history.
              </p>

@include('emails._partials.foot', ['ungated' => false])
