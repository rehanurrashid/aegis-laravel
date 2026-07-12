@include('emails._partials.head', ['email_title' => 'New reply in your dispute'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                New reply in your dispute
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $user_name ?? 'there' }},
                a
                @if(!empty($author_role))
                  {{ ucfirst(str_replace('_', ' ', $author_role)) }}
                @else
                  party
                @endif
                has submitted a reply in your open dispute
                @if(!empty($dispute_id))
                  ({{ $dispute_id }})
                @endif.
                Log in to read the response and submit your own reply if needed.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Keep the discussion factual and evidence-based. Aegis reviewers
                      read all messages before issuing a resolution. Both parties must
                      respond within the dispute window to ensure their position is heard.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ rtrim(config('app.url'),'/') . '/provider/disputes' }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View dispute thread
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
