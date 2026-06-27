@include('emails._partials.head', ['email_title' => 'You have been invited to join a team on Aegis'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                You have been invited to join a team
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                @if(!empty($invitee_name))
                  Dear {{ $invitee_name }},
                @else
                  Dear colleague,
                @endif
                {{ $inviter_name ?? 'A team member' }}
                has invited you to join
                <strong>{{ $agency_name ?? 'their organization' }}</strong>
                on Aegis
                @if(!empty($role_label))
                  as a <strong>{{ $role_label }}</strong>
                @endif.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      This invitation expires in
                      {{ ($expires_days ?? '14') }} days.
                      Accepting will give you access to the team's shared portal and assignments.
                      You are under no obligation to accept.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $invite_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Accept invitation
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you do not wish to join, disregard this email.
                No account changes will be made without your consent.
              </p>

@include('emails._partials.foot', ['ungated' => true])
