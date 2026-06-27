@include('emails._partials.head', ['email_title' => 'MAAT Continuity Steward Service update'])
@php
    $_state    = strtolower($addon_state ?? 'updated');
    $_active   = ($_state === 'active');
@endphp

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                MAAT Continuity Steward Service is now {{ $_state }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $practitioner_name ?? 'there' }},
                the MAAT Continuity Steward Service add-on on your account is now
                <strong>{{ $_state }}</strong>.
                @if($_active && !empty($addon_price))
                  A charge of <strong>{{ $addon_price }}/mo</strong>
                  has been added to your billing cycle.
                @elseif(!$_active)
                  The add-on charge has been removed from your billing cycle effective next renewal.
                @endif
              </p>

              <!-- Status box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:{{ $_active ? '#f0faf4' : '#f5f0e6' }};
                             border-left:3px solid {{ $_active ? '#2f7d54' : '#a0813e' }};
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if($_active)
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The MAAT Continuity Steward Service provides a MA'AT-designated steward
                      to fulfill your Continuity Steward role. Your Continuity Plan is now
                      backed by the MA'AT network.
                    </p>
                    @else
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The MAAT Continuity Steward Service has been deactivated. You will
                      need to designate a new Continuity Steward to keep your plan fully active.
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
                    <a href="{{ $billing_url ?? rtrim(config('app.url'),'/') . '/billing' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View billing details
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This add-on requires an active Practice tier subscription. Contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                with any billing questions.
              </p>

@include('emails._partials.foot', ['ungated' => false])
