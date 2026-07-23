@include('emails._partials.head', ['email_title' => 'Agreement terminated'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                An agreement has been terminated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                @if(!empty($document_title))
                  <strong>{{ $document_title }}</strong>
                @else
                  An agreement
                @endif
                has been terminated
                @if(!empty($terminated_by))
                  by <strong>{{ $terminated_by }}</strong>
                @endif
                effective <strong>{{ $term_date ?? 'today' }}</strong>.
              </p>

              @if(!empty($reason))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fff1f1;border-left:3px solid #dc2626;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reason:</strong> {{ $reason }}<br>
                      All permissions and delegated access under this agreement have been revoked.
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#8a8378;line-height:1.5;">
                The terminated agreement remains in your Aegis document archive for audit and compliance purposes.
              </p>

@include('emails._partials.foot', ['ungated' => false])
