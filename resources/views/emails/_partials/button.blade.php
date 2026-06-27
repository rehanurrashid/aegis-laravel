{{-- Primary CTA button. Usage: @include('emails._partials.button', ['url' => $x, 'label' => 'Open']) --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 24px;">
  <tr>
    <td style="background-color:#a0813e;border-radius:8px;">
      <a href="{{ $url ?? rtrim(config('app.url'), '/') }}"
         style="display:inline-block;padding:12px 28px;font-family:Arial,Helvetica,sans-serif;
                font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">{{ $label ?? 'Open Aegis' }}</a>
    </td>
  </tr>
</table>
