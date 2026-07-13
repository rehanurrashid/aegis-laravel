@include('emails._partials.head', ['email_title' => 'Your Support Steward access has been suspended'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Support Steward access has been suspended
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $ss_name ?? 'there' }},
                {{ $practitioner_name ?? 'The practitioner' }} has temporarily suspended your
                Support Steward access on Aegis.
              </p>

              @if(!empty($reason))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:700;text-transform:uppercase;
                              letter-spacing:0.4px;color:#6b5b3e;">Reason</p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">{{ $reason }}</p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Your Aegis account remains active and all other roles are unaffected.
                Your access will be restored if the practitioner reinstates your designation.
                Please reach out to the practitioner if you have any questions.
              </p>

@include('emails._partials.footer')
