@include('emails._partials.head', ['email_title' => 'Agreement fully executed'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your agreement is now fully executed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Great news. <strong>{{ $cs_name ?? 'Your Continuity Steward' }}</strong>
                has countersigned
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>
                @else
                  your agreement
                @endif
                and it is now legally binding. Both parties hold equal responsibility under
                its terms.
              </p>

              <!-- Success box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#edf7ed;border-left:3px solid #3a7d44;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#2d5a32;line-height:1.5;">
                      This agreement is now active and audit-logged. You can download
                      a signed PDF copy from your Important Documents page at any time.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $docs_url ?? rtrim(config('app.url'),'/') . '/provider/important-documents' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View Executed Agreement
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                A signed PDF will be available in your portal within minutes.
                Keep this for your records.
              </p>

@include('emails._partials.foot', ['ungated' => false])
