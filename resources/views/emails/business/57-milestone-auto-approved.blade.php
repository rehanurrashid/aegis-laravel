@include('emails._partials.head', ['email_title' => 'Milestone auto-approved — payment sent'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Milestone auto-approved
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear {{ $recipient_name ?? 'there' }},<br><br>
                The review window for milestone <strong>{{ $milestone_title ?? 'your milestone' }}</strong>
                has elapsed without a response from the provider. The milestone has been automatically approved
                and payment has been sent directly to the Business Partner's Stripe Connect account.
              </p>

              @include('emails.partials.direct-to-bp-disclosure')

@include('emails._partials.foot')
