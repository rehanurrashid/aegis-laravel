@include('emails._partials.head', ['email_title' => 'Subscription Invoice — Aegis'])

              <h1 style="margin:0 0 6px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Aegis Invoice
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Hi {{ $recipient_name ?? 'there' }}, thank you for subscribing to Aegis.
                Below is a summary of your first billing charge. Please keep this for your records.
              </p>

              <!-- Invoice meta row -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="font-family:Arial,Helvetica,sans-serif;font-size:12px;
                             color:#8a8378;line-height:1.8;">
                    <strong style="color:#4a4741;">Invoice #:</strong> {{ $invoice_number ?? ('INV-' . strtoupper(substr(md5($recipient_name ?? 'aegis'), 0, 8))) }}<br>
                    <strong style="color:#4a4741;">Date:</strong> {{ $invoice_date ?? now()->format('F j, Y') }}<br>
                    <strong style="color:#4a4741;">Billing:</strong> {{ $billing_label ?? 'Monthly' }}
                  </td>
                </tr>
              </table>

              <!-- Line items table -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;border:1px solid #e4dfd7;border-radius:8px;overflow:hidden;">

                <!-- Table header -->
                <tr>
                  <td style="background-color:#f5f0e6;padding:10px 16px;
                             font-family:Arial,Helvetica,sans-serif;font-size:11px;
                             font-weight:700;color:#8a8378;text-transform:uppercase;
                             letter-spacing:0.8px;border-bottom:1px solid #e4dfd7;">
                    Description
                  </td>
                  <td style="background-color:#f5f0e6;padding:10px 16px;text-align:right;
                             font-family:Arial,Helvetica,sans-serif;font-size:11px;
                             font-weight:700;color:#8a8378;text-transform:uppercase;
                             letter-spacing:0.8px;border-bottom:1px solid #e4dfd7;
                             white-space:nowrap;">
                    Amount
                  </td>
                </tr>

                <!-- Base plan line -->
                <tr>
                  <td style="padding:14px 16px;border-bottom:1px solid #f0ebe3;
                             font-family:Arial,Helvetica,sans-serif;">
                    <div style="font-size:14px;font-weight:600;color:#2d2a26;margin-bottom:2px;">
                      {{ $plan_name ?? 'Aegis Subscription' }}
                    </div>
                    <div style="font-size:12px;color:#8a8378;">
                      {{ $plan_description ?? 'Monthly subscription' }}
                    </div>
                  </td>
                  <td style="padding:14px 16px;text-align:right;border-bottom:1px solid #f0ebe3;
                             font-family:'Georgia','Times New Roman',serif;font-size:15px;
                             font-weight:700;color:#2d2a26;white-space:nowrap;vertical-align:top;">
                    {{ $plan_price ?? '—' }}
                  </td>
                </tr>

                @if(!empty($addons) && count($addons) > 0)
                  @foreach($addons as $addon)
                  <!-- Add-on line -->
                  <tr>
                    <td style="padding:14px 16px;border-bottom:1px solid #f0ebe3;
                               background-color:#fdf9f2;font-family:Arial,Helvetica,sans-serif;">
                      <div style="font-size:14px;font-weight:600;color:#2d2a26;margin-bottom:2px;">
                        {{ $addon['name'] }}
                      </div>
                      <div style="font-size:12px;color:#8a8378;">
                        {{ $addon['description'] ?? '' }}
                      </div>
                    </td>
                    <td style="padding:14px 16px;text-align:right;border-bottom:1px solid #f0ebe3;
                               background-color:#fdf9f2;font-family:'Georgia','Times New Roman',serif;
                               font-size:15px;font-weight:700;color:#2d2a26;white-space:nowrap;
                               vertical-align:top;">
                      {{ $addon['price'] }}
                    </td>
                  </tr>
                  @endforeach
                @endif

                <!-- Total row -->
                <tr>
                  <td style="padding:14px 16px;background-color:#f5f0e6;
                             font-family:Arial,Helvetica,sans-serif;font-size:13px;
                             font-weight:700;color:#2d2a26;border-top:2px solid #e4dfd7;">
                    Total charged today
                  </td>
                  <td style="padding:14px 16px;text-align:right;background-color:#f5f0e6;
                             font-family:'Georgia','Times New Roman',serif;font-size:18px;
                             font-weight:700;color:#a0813e;border-top:2px solid #e4dfd7;
                             white-space:nowrap;">
                    {{ $total_price ?? '—' }}
                  </td>
                </tr>

              </table>

              <!-- Renewal note -->
              @if(!empty($renewal_label))
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:12px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Next renewal:</strong> {{ $renewal_label }}.
                      You can manage or cancel your subscription anytime from your billing settings.
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
                    <a href="{{ $portal_url ?? rtrim(config('app.url'), '/') }}"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Go to my portal
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Questions about your invoice? Contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>.
                All invoices are also available in your portal under
                <strong style="color:#4a4741;">Billing</strong>.
              </p>

@include('emails._partials.foot', ['ungated' => true])
