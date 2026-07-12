@include('emails._partials.head', ['email_title' => 'Your contract is ready to sign'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A contract is ready for your signature
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                a service agreement has been created between you and
                <strong>{{ $counterparty_name ?? 'the other party' }}</strong>.
                @if(!empty($contract_title))
                  The agreement is titled <strong>{{ $contract_title }}</strong>.
                @endif
                Please review the terms and sign at your earliest convenience.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>How it works:</strong> Both parties must sign before the provider
                      funds escrow. Once fully signed, the provider funds payment into Aegis
                      escrow — then work can begin.
                    </p>
                  </td>
                </tr>
              </table>

              @if(!empty($contract_value))
              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Contract value: <strong>{{ $contract_value }}</strong>
              </p>
              @endif

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review and sign agreement
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Work does not begin until both parties have signed and the provider has
                funded escrow. You will receive a confirmation once fully executed.
              </p>

@include('emails._partials.foot', ['ungated' => false])
