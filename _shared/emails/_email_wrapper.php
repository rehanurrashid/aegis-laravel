<?php
/**
 * _email_wrapper.php — Outer 600px table shell reference.
 *
 * This file is NOT included directly by templates — it is the documented
 * structural pattern that every template replicates inline so that each
 * .php file is a fully self-contained email (no double-wrapping risk).
 *
 * Templates include _email_head.php (top) and _email_foot.php (bottom row)
 * and replicate the structure documented here.
 *
 * Shell structure:
 *
 *   <?php include __DIR__ . '/../_email_head.php'; ?>
 *   <body style="margin:0;padding:0;background-color:#fffdf7;
 *                font-family:'Georgia','Times New Roman',serif;">
 *     <table role="presentation" width="100%" cellpadding="0"
 *            cellspacing="0" border="0"
 *            style="background-color:#fffdf7;">
 *       <tr>
 *         <td align="center" style="padding:32px 16px;">
 *
 *           <!-- 600px container -->
 *           <table role="presentation" width="600" cellpadding="0"
 *                  cellspacing="0" border="0"
 *                  style="max-width:600px;width:100%;
 *                         background-color:#ffffff;border-radius:12px;
 *                         border:1px solid #e4dfd7;">
 *
 *             <!-- Header strip -->
 *             <tr>
 *               <td style="padding:28px 40px 20px;
 *                          border-bottom:1px solid #e4dfd7;">
 *                 <span style="font-family:'Georgia','Times New Roman',serif;
 *                              font-size:22px;font-weight:700;color:#2d2a26;
 *                              letter-spacing:-0.3px;">Aegis</span>
 *               </td>
 *             </tr>
 *
 *             <!-- Body row -->
 *             <tr>
 *               <td style="padding:32px 40px;">
 *                 [template-specific content]
 *               </td>
 *             </tr>
 *
 *             <!-- Footer row -->
 *             <?php include __DIR__ . '/../_email_foot.php'; ?>
 *
 *           </table>
 *         </td>
 *       </tr>
 *     </table>
 *   </body>
 *   </html>
 *
 * Design tokens used (inline only — no <style> blocks):
 *   Background outer:  #fffdf7   (--color-surface)
 *   Card background:   #ffffff
 *   Card border:       #e4dfd7   (--color-border)
 *   Card radius:       12px
 *   Text primary:      #2d2a26   (--color-text)
 *   Text secondary:    #4a4741   (--color-text-2)
 *   Text muted:        #8a8378   (--color-text-3)
 *   Gold CTA:          #a0813e   (--color-gold-dark)
 *   Surface-2 (boxes): #f5f0e6   (--color-surface-2)
 *   Red alert:         #c0392b
 *   Green confirm:     #2f7d54
 *   Font serif:        'Georgia','Times New Roman',serif
 *   Font sans:         Arial,Helvetica,sans-serif
 */
declare(strict_types=1);
// This file is a structural reference only — do not include directly.
