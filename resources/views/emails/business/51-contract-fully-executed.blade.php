@include('emails._partials.head', ['email_title' => 'Contract fully executed — ready for funding'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Contract fully signed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                the service agreement
                @if(!empty($contract_title))
                  <strong>{{ $contract_title }}</strong>
                @endif
                between you and <strong>{{ $counterparty_name ?? 'the other party' }}</strong>
                has been signed by both parties on {{ $signed_at ?? 'today' }}.
              </p>

              <!-- Green confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.5;">
                      @if($recipient_role === 'provider')
                        <strong>Next step:</strong> Log in to fund the escrow. Milestones unlock
                        for the Business Partner once each is funded.
                      @else
                        <strong>Next step:</strong> The provider must now fund escrow. You will
                        receive a notification when funds are secured and work can begin.
                      @endif
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

              <!-- CTA row -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;margin-right:10px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      {{ $recipient_role === 'provider' ? 'Fund escrow' : 'View agreement' }}
                    </a>
                  </td>
                </tr>
              </table>

              @if(!empty($pdf_url))
              <p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#4a4741;line-height:1.5;">
                Download a copy:
                <a href="{{ $pdf_url }}" style="color:#a0813e;font-weight:600;">
                  View &amp; print signed contract →
                </a>
              </p>
              @endif

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Both electronic signatures are recorded and constitute a legally binding
                agreement under applicable U.S. electronic signature laws.
              </p>

@include('emails._partials.foot', ['ungated' => false])
