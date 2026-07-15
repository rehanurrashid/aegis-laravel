@include('emails._partials.head', ['email_title' => 'Fee Amendment Request — Aegis'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Fee Amendment Request
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $cs_name ?? 'there' }},<br><br>
                <strong>{{ $provider_name ?? 'Your Practitioner' }}</strong> has proposed a fee
                amendment for your Continuity Steward agreement. Please review the details below
                and countersign the amendment document in your portal.
              </p>

              <!-- Fee summary table -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin:0 0 24px;border-collapse:collapse;">
                <tr>
                  <td style="padding:10px 14px;font-family:Arial,Helvetica,sans-serif;
                             font-size:13px;color:#6b6560;border-bottom:1px solid #e8e3dc;
                             width:50%;">Current Fee</td>
                  <td style="padding:10px 14px;font-family:Arial,Helvetica,sans-serif;
                             font-size:13px;color:#2d2a26;font-weight:600;
                             border-bottom:1px solid #e8e3dc;">${{ $old_fee ?? '0.00' }}</td>
                </tr>
                <tr>
                  <td style="padding:10px 14px;font-family:Arial,Helvetica,sans-serif;
                             font-size:13px;color:#6b6560;border-bottom:1px solid #e8e3dc;">
                    Proposed Fee</td>
                  <td style="padding:10px 14px;font-family:Arial,Helvetica,sans-serif;
                             font-size:13px;color:#a0813e;font-weight:700;
                             border-bottom:1px solid #e8e3dc;">${{ $new_fee ?? '0.00' }}</td>
                </tr>
                <tr>
                  <td style="padding:10px 14px;font-family:Arial,Helvetica,sans-serif;
                             font-size:13px;color:#6b6560;">Payment Terms</td>
                  <td style="padding:10px 14px;font-family:Arial,Helvetica,sans-serif;
                             font-size:13px;color:#2d2a26;">{{ $payment_terms ?? 'Upon Incident Close' }}</td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $portal_url ?? rtrim(config('app.url'),'/') . '/continuity-steward/documents' }}"
                       style="display:inline-block;padding:13px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:700;color:#ffffff;text-decoration:none;
                              border-radius:8px;">
                      Review &amp; Sign Amendment
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;line-height:1.5;color:#9a958f;">
                If you did not expect this request, please contact
                {{ $provider_name ?? 'your practitioner' }} directly before signing.
              </p>

@include('emails._partials.footer')
