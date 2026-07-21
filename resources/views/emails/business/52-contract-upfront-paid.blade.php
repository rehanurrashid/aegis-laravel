@include('emails._partials.head', ['email_title' => 'Contract upfront payment — direct to Business Partner'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Contract upfront payment processed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br><br>
                @if(($data['role'] ?? '') === 'provider')
                  The upfront payment for contract <strong>{{ $contract_title ?? 'your contract' }}</strong>
                  has been charged and sent directly to the Business Partner's Stripe account.
                @else
                  An upfront payment for contract <strong>{{ $contract_title ?? 'your contract' }}</strong>
                  has been deposited directly to your Stripe Connect account.
                @endif
              </p>

              @if(!empty($amount))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1e5c3b;line-height:1.8;">
                      <strong>Amount:</strong> {{ $amount }}<br>
                      <strong>Structure:</strong> {{ $terms_summary ?? 'Per committed terms' }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              @include('emails.partials.direct-to-bp-disclosure')

@include('emails._partials.foot')
