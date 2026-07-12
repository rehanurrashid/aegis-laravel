@include('emails._partials.head', ['email_title' => 'Contract cancelled — escrow refunded'])

              @if(($recipient_role ?? '') === 'provider')

              {{-- ── PROVIDER copy ── --}}
              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Contract cancelled — funds refunded
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                the contract
                @if(!empty($contract_title))
                  <strong>{{ $contract_title }}</strong>
                @endif
                has been cancelled and any escrowed funds have been refunded to your
                payment method on file.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.8;">
                      @if(!empty($refund_amount))
                        <strong>Refunded:</strong> {{ $refund_amount }}<br>
                      @endif
                      @if(!empty($reason))
                        <strong>Reason:</strong> {{ $reason }}<br>
                      @endif
                      Refunds post within 5–10 business days.
                    </p>
                  </td>
                </tr>
              </table>

              @else

              {{-- ── BP copy ── --}}
              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Contract cancelled
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                the contract
                @if(!empty($contract_title))
                  <strong>{{ $contract_title }}</strong>
                @endif
                with <strong>{{ $provider_name ?? 'the provider' }}</strong> has been
                cancelled.
                @if(!empty($reason))
                  Reason: {{ $reason }}.
                @endif
              </p>

              @if(!empty($released_amount))
              <!-- Show BP any already-released payments -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#1e5c3b;line-height:1.5;">
                      <strong>{{ $released_amount }}</strong> in approved milestone payments
                      were already released to your Stripe account prior to cancellation.
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    @if(($recipient_role ?? '') === 'provider')
                    <a href="{{ rtrim(config('app.url'),'/') . '/provider/support-services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Find new Business Partners
                    </a>
                    @else
                    <a href="{{ rtrim(config('app.url'),'/') . '/business-partner/find-jobs' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Browse open positions
                    </a>
                    @endif
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
