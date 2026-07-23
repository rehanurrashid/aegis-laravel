@include('emails._partials.head', ['email_title' => 'Agreement signed — your countersignature is needed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                An agreement is ready for your countersignature
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>{{ $practitioner_name ?? 'Your practitioner' }}</strong> has signed
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>
                @else
                  an agreement
                @endif
                on {{ $signed_date ?? 'today' }}. Your countersignature is now required to fully execute this agreement.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Once both parties have signed, the agreement becomes legally binding
                      and your continuity responsibilities take effect immediately.
                    </p>
                  </td>
                </tr>
              </table>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $docs_url ?? rtrim(config('app.url'),'/') . '/continuity-steward/important-documents' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review &amp; Countersign
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
