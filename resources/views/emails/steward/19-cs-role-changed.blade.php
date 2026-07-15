@include('emails._partials.head', ['email_title' => 'Your Continuity Steward role has been updated'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Continuity Steward role has been updated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $steward_name ?? 'there' }},
              </p>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                {{ $provider_name ?? 'Your provider' }} has updated your role on their Continuity Plan.
              </p>

              <!-- Role change detail box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($old_role))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;text-transform:uppercase;
                              letter-spacing:0.5px;">Previous Role</p>
                    <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;
                              font-size:14px;color:#4a4741;">{{ ucfirst($old_role) }} Continuity Steward</p>
                    @endif
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;text-transform:uppercase;
                              letter-spacing:0.5px;">New Role</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:14px;font-weight:700;color:#2d2a26;">{{ ucfirst($new_role ?? $requested_role ?? 'updated') }} Continuity Steward</p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                No action is required on your part. Your responsibilities and access remain
                unchanged unless separately communicated. If you have questions, please
                reach out to {{ $provider_name ?? 'your provider' }} through the Aegis platform.
              </p>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                You can view your current role and responsibilities by logging into your
                Aegis Continuity Steward portal.
              </p>

@include('emails._partials.footer')
