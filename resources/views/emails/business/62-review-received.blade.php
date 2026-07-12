@include('emails._partials.head', ['email_title' => 'You received a review on Aegis'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                You received a review
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},
                <strong>{{ $reviewer_name ?? 'a client' }}</strong> has left you a
                <strong>{{ $rating ?? '5' }}-star review</strong> on Aegis.
              </p>

              <!-- Star rating box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:16px 18px;border-radius:0 6px 6px 0;">
                    <!-- Stars -->
                    <p style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:20px;
                              color:#a0813e;letter-spacing:2px;">
                      @for($i = 0; $i < ($rating ?? 5); $i++)★@endfor@for($j = ($rating ?? 5); $j < 5; $j++)☆@endfor
                    </p>
                    @if(!empty($review_text))
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.6;
                              font-style:italic;">
                      "{{ \Illuminate\Support\Str::limit($review_text, 300) }}"
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Reviews build your reputation on the Aegis network and help practitioners
                make informed hiring decisions. Keep delivering great work!
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $contract_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View your profile
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
