@include('emails._partials.head', ['email_title' => 'Agreement archived'])
@php
  $isActor = $is_actor ?? false;
@endphp

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                @if($isActor)
                  Agreement archived
                @else
                  An agreement has been terminated
                @endif
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                @if($isActor)
                  You have archived
                @else
                  <strong>{{ $practitioner_name ?? 'Your practitioner' }}</strong> has archived
                @endif
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>.
                @else
                  an agreement.
                @endif
                @if(!$isActor)
                  Access and delegated authority associated with this agreement
                  have been revoked effective immediately.
                @endif
              </p>

              @if(!empty($reason))
              <!-- Reason box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fef3f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#7b241c;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Reason provided
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $reason }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $docs_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View Documents
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Archived agreements remain in your audit log. Contact your Aegis administrator
                if you believe this was done in error.
              </p>

@include('emails._partials.foot', ['ungated' => false])
