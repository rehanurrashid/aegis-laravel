@include('emails._partials.head', ['email_title' => 'Your signature is requested'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your countersignature is requested
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>{{ $practitioner_name ?? 'Your practitioner' }}</strong> has signed
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>
                @else
                  an agreement
                @endif
                and it now requires your countersignature to become fully executed.
              </p>

              @if(!empty($deadline))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fff9ec;border-left:3px solid #d97706;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Requested by:</strong> {{ $deadline }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

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
