@include('emails._partials.head', ['email_title' => 'Amendment requested'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                An amendment has been requested
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <strong>{{ $practitioner_name ?? 'Your practitioner' }}</strong>
                has requested an amendment to
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>.
                @else
                  an existing agreement.
                @endif
                Please review the proposed changes and accept or counter-propose through your portal.
              </p>

              @if(!empty($amendment_type) || !empty($proposed_change))
              <!-- Amendment details -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if(!empty($amendment_type))
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Amendment type
                    </p>
                    <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $amendment_type }}
                    </p>
                    @endif
                    @if(!empty($proposed_change))
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Proposed change
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ \Illuminate\Support\Str::limit($proposed_change, 300) }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#4a4741;line-height:1.6;">
                Amendments require mutual agreement before taking effect.
                The original agreement remains active until the amendment is executed.
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $docs_url ?? rtrim(config('app.url'),'/') . '/continuity-steward/important-documents' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review Amendment
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Log in to your Aegis portal to review the full amendment details and respond.
              </p>

@include('emails._partials.foot', ['ungated' => false])
