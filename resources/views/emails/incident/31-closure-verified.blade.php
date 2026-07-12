@include('emails._partials.head', ['email_title' => 'Incident closure verified — proceed to close'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Closure verified — you may now close the incident
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $cs_name ?? 'there' }},
                @if(!empty($verifier_name))
                  <strong>{{ $verifier_name }}</strong> has verified
                @else
                  the practitioner has confirmed
                @endif
                that all continuity tasks have been completed satisfactorily.
                You may now formally close the incident and issue your engagement invoice
                if applicable.
              </p>

              <!-- Green confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.5;">
                      <strong>Next step:</strong> Close the incident in your portal, then
                      your engagement invoice will be generated automatically. The vault
                      will be sealed upon closure.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $incident_url ?? rtrim(config('app.url'),'/') . '/continuity-steward/continuity-management' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Close incident &amp; issue invoice
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => true])
