@include('emails._partials.head', ['email_title' => 'You have been invited to serve as a Continuity Steward'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                You've been named as a Continuity Steward
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>{{ $practitioner_name ?? 'A healthcare practitioner' }}</strong> has invited you to
                serve as their <strong>{{ $role_label ?? 'Continuity Steward' }}</strong> on Aegis — a platform
                for healthcare practice continuity planning. As a Continuity Steward, you would be
                responsible for supporting their practice during unexpected absences or a critical incident.
              </p>

              <!-- Details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Role:</strong> {{ $role_label ?? 'Continuity Steward' }}<br>
                      @if(!empty($fee_display))
                      <strong>Compensation:</strong> {{ $fee_display }}<br>
                      @endif
                      <strong>Expires:</strong> {{ ($expires_days ?? '14') }} days from today
                    </p>
                    <p style="margin:6px 0 0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#6b6560;">
                      To accept, create a free Aegis account. You are under no obligation to accept.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $invite_url ?? rtrim(config('app.url'),'/').'/register?role=cs&path=invited' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Accept Invitation &amp; Create Account →
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Invitation code fallback box -->
              @if(!empty($invitation_code))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border:1px solid #d4c4a0;
                             border-radius:6px;padding:16px 20px;text-align:center;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:11px;color:#8a8378;text-transform:uppercase;
                              letter-spacing:0.6px;font-weight:700;">Your Invitation Code</p>
                    <p style="margin:0 0 8px;font-family:'Courier New',monospace;font-size:20px;
                              font-weight:700;color:#2d2a26;letter-spacing:2px;">
                      {{ $invitation_code }}
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:11px;color:#8a8378;line-height:1.5;">
                      If the button above doesn't work, visit
                      <strong>{{ rtrim(config('app.url'),'/') }}/register</strong>,
                      choose <strong>Continuity Steward → Invited</strong>,
                      and enter this code on the account creation step.
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you do not wish to accept this invitation, simply ignore this email.
                No account will be created without your action.
              </p>

@include('emails._partials.foot', ['ungated' => true])
