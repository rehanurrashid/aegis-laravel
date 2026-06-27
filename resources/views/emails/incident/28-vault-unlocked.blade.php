@include('emails._partials.head', ['email_title' => 'The credential vault has been unlocked'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Credential vault unlocked
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $cs_name ?? 'there' }},
                the credential vault for
                <strong>{{ $practitioner_name ?? 'your practitioner' }}</strong>
                has been unlocked
                @if(!empty($unlocked_at))
                  on {{ $unlocked_at }}
                @endif
                following incident verification. You now have access to the vault contents
                needed to carry out the Continuity Plan.
              </p>

              <!-- Security notice -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      All vault access is logged and audited. Use the credentials only as
                      directed by the Continuity Plan. Do not share vault contents with
                      unauthorized parties.
                      @if(!empty($incident_id))
                        Reference: {{ $incident_id }}.
                      @endif
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $vault_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Access the vault
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you did not expect this notification, contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                immediately.
              </p>

@include('emails._partials.foot', ['ungated' => true])
