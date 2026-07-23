@include('emails._partials.head', ['email_title' => 'Agreement fully executed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your agreement is fully executed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Both parties have signed
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>
                @else
                  the agreement
                @endif
                . The agreement is now legally binding and your continuity arrangement is in effect.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #16a34a;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Reference: <strong>{{ $reference ?? '—' }}</strong><br>
                      Effective: <strong>{{ $effective_date ?? '—' }}</strong><br>
                      @if(!empty($expiry_date))
                      Expires: <strong>{{ $expiry_date }}</strong>
                      @endif
                    </p>
                  </td>
                </tr>
              </table>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $docs_url ?? rtrim(config('app.url'),'/') . '/provider/important-documents' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View Agreement
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
