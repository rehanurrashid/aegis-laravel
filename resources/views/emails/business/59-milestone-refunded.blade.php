@include('emails._partials.head', ['email_title' => 'Milestone escrow refunded'])

              @if(($recipient_role ?? '') === 'provider')

              {{-- ── PROVIDER copy ── --}}
              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Escrow refunded to your card
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                the escrow funds for milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                have been refunded to your original payment method.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.8;">
                      @if(!empty($amount))
                        <strong>Refunded:</strong> {{ $amount }}<br>
                      @endif
                      @if(!empty($reason))
                        <strong>Reason:</strong> {{ $reason }}<br>
                      @endif
                      Refunds typically post within 5–10 business days depending on your card issuer.
                    </p>
                  </td>
                </tr>
              </table>

              @else

              {{-- ── BP copy ── --}}
              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Escrow funds returned to provider
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                the escrow funds for milestone
                @if(!empty($milestone_title))
                  <strong>{{ $milestone_title }}</strong>
                @endif
                have been refunded to
                <strong>{{ $provider_name ?? 'the provider' }}</strong>.
                @if(!empty($reason))
                  Reason: {{ $reason }}.
                @endif
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fef2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#7b1d1d;line-height:1.5;">
                      @if(!empty($amount))
                        <strong>{{ $amount }}</strong> was returned to the provider.
                      @endif
                      If you believe this was an error, please open a dispute through your
                      portal or contact Aegis support.
                    </p>
                  </td>
                </tr>
              </table>

              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View contract
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
