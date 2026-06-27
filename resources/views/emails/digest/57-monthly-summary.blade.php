@include('emails._partials.head', ['email_title' => 'Your Aegis monthly summary'])

              <h1 style="margin:0 0 8px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Monthly summary
              </h1>

              <p style="margin:0 0 28px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#8a8378;line-height:1.5;">
                @if(!empty($month_label))
                  {{ $month_label }}
                  &middot;
                @endif
                {{ $practitioner_name ?? 'Your account' }}
              </p>

              <!-- Plan health -->
              @if(!empty($plan_status_label))
              <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;font-weight:600;color:#4a4741;
                        text-transform:uppercase;letter-spacing:0.5px;">
                Plan health
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Continuity Plan status
                        </td>
                        <td align="right" style="font-family:Arial,Helvetica,sans-serif;
                                    font-size:13px;font-weight:600;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          {{ $plan_status_label }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              @endif

              <!-- Re-attestation -->
              @if(!empty($reattestation_due_date))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Re-attestation due
                        </td>
                        <td align="right" style="font-family:Arial,Helvetica,sans-serif;
                                    font-size:13px;font-weight:600;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          {{ $reattestation_due_date }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              @endif

              <!-- Network activity -->
              @if(isset($network_connections))
              <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;font-weight:600;color:#4a4741;
                        text-transform:uppercase;letter-spacing:0.5px;">
                Network
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Integrative Network connections
                        </td>
                        <td align="right" style="font-family:'Georgia','Times New Roman',serif;
                                    font-size:18px;font-weight:700;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          {{ (int)$network_connections }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              @endif

              <!-- Open proposals -->
              @if(isset($open_proposals))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Open Business Partner proposals
                        </td>
                        <td align="right" style="font-family:'Georgia','Times New Roman',serif;
                                    font-size:18px;font-weight:700;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          {{ (int)$open_proposals }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              @endif

              <!-- CEU note -->
              @if(!empty($ceus_note))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      CEU reminder
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      {{ $ceus_note }}
                    </p>
                  </td>
                </tr>
              </table>
              @endif

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="{{ $portal_url ?? rtrim(config('app.url'),'/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Go to my portal
                    </a>
                  </td>
                </tr>
              </table>

@include('emails._partials.foot', ['ungated' => false])
