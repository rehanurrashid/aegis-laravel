@include('emails._partials.head', ['email_title' => 'Your Aegis weekly digest'])
@php
    $_items = is_array($summary_items ?? null) ? $summary_items : [];
@endphp

              <h1 style="margin:0 0 8px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Weekly digest
              </h1>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#8a8378;line-height:1.5;">
                @if(!empty($week_label))
                  Week of {{ $week_label }}
                @endif
                &middot;
                {{ $recipient_name ?? 'Your account' }}
              </p>

              @if(!empty($_items))
                @foreach($_items as $_item)
                  @php
    $_label = $_item['label'] ?? '';
    $_count = (int)($_item['count'] ?? 0);
    $_url   = $_item['url'] ?? ($portal_url ?? rtrim(config('app.url'),'/'));
@endphp
                  <!-- Summary row -->
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                         style="margin:0 0 8px;">
                    <tr>
                      <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                       color:#4a4741;line-height:1.4;">
                              {{ $_label }}
                            </td>
                            <td align="right" style="font-family:'Georgia','Times New Roman',serif;
                                        font-size:18px;font-weight:700;color:#a0813e;
                                        white-space:nowrap;padding-left:16px;">
                              {{ $_count }}
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                @endforeach

                <p style="margin:24px 0 0;font-family:Arial,Helvetica,sans-serif;
                          font-size:13px;color:#8a8378;line-height:1.5;">
                  Review the full activity log in your portal for details.
                </p>

              @else
                <!-- Quiet week -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="margin:0 0 24px;">
                  <tr>
                    <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                               padding:14px 16px;border-radius:0 6px 6px 0;">
                      <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                                font-size:13px;color:#4a4741;line-height:1.5;">
                        It was a quiet week on your account — no notable activity to report.
                      </p>
                    </td>
                  </tr>
                </table>
              @endif

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:24px auto 0;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $portal_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Go to my portal
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
