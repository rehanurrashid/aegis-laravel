@include('emails._partials.head', ['email_title' => 'A document in your plan has been updated'])
@php
    $_change   = strtolower($change_type ?? 'updated');
    $_archived = ($_change === 'archived');
@endphp

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A plan document has been {{ $_change }}
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $steward_name ?? 'there' }},
                <strong>{{ $practitioner_name ?? 'Your practitioner' }}</strong>
                has {{ $_change }} the document
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>
                @endif
                in their Continuity Plan.
                @if(!$_archived)
                  Please review if needed.
                @endif
              </p>

              <!-- Change details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    @if($_change === 'renewed' && !empty($new_expiry_date))
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>New expiry date:</strong>
                      {{ $new_expiry_date }}
                    </p>
                    @endif
                    @if($_archived)
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Archived documents are no longer active but remain accessible in the
                      document history for audit purposes.
                    </p>
                    @else
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      This change may affect your stewardship responsibilities. Review the
                      updated document to ensure your understanding remains current.
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $document_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View document
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                All document changes are logged in the plan audit trail.
              </p>

@include('emails._partials.foot', ['ungated' => false])
