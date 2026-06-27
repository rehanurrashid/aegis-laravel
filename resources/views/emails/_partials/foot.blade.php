{{-- Shared footer row + outer shell close for all Aegis email templates. --}}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 40px 28px;border-top:1px solid #e4dfd7;text-align:center;">
              <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                &copy; {{ date('Y') }} Aegis &middot; A MA'AT product
              </p>
              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                <a href="{{ rtrim(config('app.url'), '/') }}/privacy"
                   style="color:#8a8378;text-decoration:underline;">Privacy Policy</a>
                @unless($ungated ?? false)
                &nbsp;&middot;&nbsp;
                <a href="{{ $unsubscribe_url ?? (rtrim(config('app.url'), '/') . '/unsubscribe?token=' . urlencode($unsubscribe_token ?? '')) }}"
                   style="color:#8a8378;text-decoration:underline;">Unsubscribe</a>
                @endunless
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
