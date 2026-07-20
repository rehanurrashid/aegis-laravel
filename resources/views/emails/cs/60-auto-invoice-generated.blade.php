@include('emails._partials.head', ['email_title' => 'Invoice generated — ' . ($incident_id ?? 'incident') . ' closed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Retainer invoice generated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $user_name ?? 'there' }},<br><br>
                Your retainer with <strong>{{ $practitioner_name ?? 'your Continuity Steward' }}</strong>
                has generated an invoice following the close of
                @if(!empty($incident_id))
                  critical incident <strong>{{ $incident_id }}</strong>.
                @else
                  a critical incident.
                @endif
                Amount: <strong>${{ $total_dollars ?? '0.00' }}</strong>.
                You have 7 days to pay manually before auto-charge from your default payment method.
              </p>

              <!-- Invoice summary box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.8;">
                      @if(!empty($invoice_number))
                        <strong>Invoice #:</strong> {{ $invoice_number }}<br>
                      @endif
                      @if(!empty($incident_id))
                        <strong>Incident:</strong> {{ $incident_id }}<br>
                      @endif
                      <strong>Amount due:</strong> ${{ $total_dollars ?? '0.00' }}<br>
                      @if(!empty($auto_charged))
                        <strong>Status:</strong> Auto-charged to your payment method on file.
                      @else
                        <strong>Status:</strong> Awaiting payment — pay manually within 7 days or your default card will be auto-charged.
                      @endif
                    </p>
                  </td>
                </tr>
              </table>

              @if(!empty($auto_charged))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Payment has been processed automatically using your saved card on file.
                You can view the invoice and download a receipt in your Finances portal.
              </p>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ rtrim(config('app.url'),'/') . '/provider/finances' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View Invoice in Finances →
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Fees are charged directly via Stripe — Aegis never holds these funds.
                If you have questions about this invoice, message your Continuity Steward
                directly through the portal or contact
                <a href="mailto:support@maatpracticefirm.com" style="color:#8a8378;">support@maatpracticefirm.com</a>.
              </p>

@include('emails._partials.foot', ['ungated' => false])
