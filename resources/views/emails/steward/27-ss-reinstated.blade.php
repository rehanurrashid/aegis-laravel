@include('emails._partials.head', ['email_title' => 'Your Support Steward access has been reinstated'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Support Steward access has been reinstated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $ss_name ?? 'there' }},
                {{ $practitioner_name ?? 'The practitioner' }} has reinstated your Support Steward
                access on Aegis. Your previously authorized responsibilities have been restored.
              </p>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $portal_url ?? rtrim(config('app.url'),'/') . '/support-steward/dashboard' }}"
                       style="display:inline-block;padding:13px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:700;color:#ffffff;text-decoration:none;
                              border-radius:8px;">
                      Open Your Portal
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.footer')
