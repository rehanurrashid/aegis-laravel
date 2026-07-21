@include('emails._partials.head', ['email_title' => 'Milestone payment failed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Milestone payment failed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br><br>
                A payment attempt for milestone <strong>{{ $milestone_title ?? 'your milestone' }}</strong>
                on contract <strong>{{ $contract_title ?? '' }}</strong> has failed.
              </p>

              @if(!empty($data['error']))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fef2f2;border-left:3px solid #dc2626;padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#991b1b;line-height:1.8;">
                      <strong>Reason:</strong> {{ $data['error'] ?? 'Unknown error' }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#4a4741;">
                Please log in to Aegis and retry the payment, or update your payment method in Settings → Billing.
              </p>

@include('emails._partials.foot')
