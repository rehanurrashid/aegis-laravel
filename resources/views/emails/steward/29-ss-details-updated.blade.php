@include('emails._partials.head', ['email_title' => 'Your Support Steward details have been updated'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Support Steward record has been updated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $ss_name ?? 'there' }},<br><br>
                {{ $practitioner_name ?? 'The practitioner' }} has updated your Support Steward details on Aegis.
                Please review the information below to ensure it is accurate.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:700;text-transform:uppercase;
                              letter-spacing:0.4px;color:#6b5b3e;">What changed</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $changes ?? 'Contact information and/or role details were updated.' }}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                If these changes look incorrect, please reach out to {{ $practitioner_name ?? 'the practitioner' }}
                or contact Aegis support.
              </p>

@include('emails._partials.footer')
