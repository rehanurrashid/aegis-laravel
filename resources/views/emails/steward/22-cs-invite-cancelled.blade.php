@include('emails._partials.head', ['email_title' => 'Continuity Steward invitation withdrawn'])

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Your Continuity Steward invitation has been withdrawn
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Hi {{ $steward_name ?? 'there' }},
              </p>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Your invitation from <strong>{{ $provider_name ?? 'the practitioner' }}</strong> to serve as their Continuity Steward has been withdrawn as of {{ $cancelled_at ?? 'today' }}. The invitation link is no longer active.
              </p>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                If you believe this was done in error, please reach out to the practitioner directly. No further action is required on your part.
              </p>

@include('emails._partials.footer')
