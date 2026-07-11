@include('emails._partials.head', ['email_title' => 'Deposit received'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Deposit received — session confirmed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br>
                {{ $other_party_name ?? 'Your client' }} has paid their 30% deposit for
                <strong>{{ $service_title ?? 'your session' }}</strong>. The session is now confirmed.
              </p>

              <!-- Amount block -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">Deposit received</p>
                    <p style="margin:0 0 8px;font-family:'Georgia','Times New Roman',serif;
                              font-size:20px;font-weight:700;color:#2d2a26;">
                      {{ $deposit_amount ?? '' }}
                    </p>
                    @if(!empty($session_date))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      Session scheduled: <strong>{{ $session_date }}</strong>
                    </p>
                    @endif
                    @if(!empty($balance_due))
                    <p style="margin:4px 0 0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;">
                      Balance due after session: <strong>{{ $balance_due }}</strong>
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;line-height:1.6;color:#4a4741;">
                The remaining 70% balance will be charged automatically when the session is confirmed complete.
                @if(!empty($payout_note))
                  {{ $payout_note }}
                @endif
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $services_url ?? rtrim(config('app.url'),'/') . '/provider/services' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View sessions
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Questions? Use the Aegis messaging system to contact the other party.
              </p>

@include('emails._partials.foot', ['ungated' => false])
