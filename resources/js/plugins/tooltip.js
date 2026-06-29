/**
 * Aegis Global Tooltip — JS-portal implementation
 *
 * WHY NOT ::after PSEUDO-ELEMENTS:
 *   CSS pseudo-elements are rendered inside the trigger's stacking context.
 *   Any ancestor with overflow:hidden/auto, transform, filter, or a lower
 *   z-index stacking context will clip or obscure them — regardless of the
 *   z-index value on the ::after itself. This is a browser rendering spec
 *   constraint, not a CSS specificity issue.
 *
 * THIS APPROACH:
 *   A single <div id="aegis-tooltip"> is appended directly to <body> once.
 *   On mouseenter of any [data-tooltip] element, we measure the trigger's
 *   getBoundingClientRect() and position the tooltip using fixed coordinates,
 *   placing it completely outside every ancestor stacking context.
 *   Works inside modals, dropdowns, overflow:hidden cards, transformed
 *   containers — everywhere, always.
 *
 * USAGE:
 *   Any element with data-tooltip="Text" gets the tooltip automatically.
 *   Optional: data-tooltip-pos="bottom" | "right" | "left" (default: top)
 *   No per-component imports or directives needed.
 */

const GAP = 7 // px between trigger edge and tooltip bubble

let tip = null
let hideTimer = null

function getOrCreateTip () {
  if (tip) return tip
  tip = document.createElement('div')
  tip.id = 'aegis-tooltip'
  tip.setAttribute('role', 'tooltip')
  tip.setAttribute('aria-live', 'off')
  document.body.appendChild(tip)
  return tip
}

function showTooltip (trigger) {
  clearTimeout(hideTimer)
  const text = trigger.getAttribute('data-tooltip')
  if (!text) return

  const el = getOrCreateTip()
  el.textContent = text

  // Position off-screen first so we can measure its size
  el.style.left = '-9999px'
  el.style.top  = '-9999px'
  el.classList.add('is-visible')

  const pos  = trigger.getAttribute('data-tooltip-pos') || 'top'
  const tr   = trigger.getBoundingClientRect()
  const tw   = el.offsetWidth
  const th   = el.offsetHeight

  let x, y

  if (pos === 'bottom') {
    x = tr.left + tr.width / 2 - tw / 2
    y = tr.bottom + GAP
  } else if (pos === 'right') {
    x = tr.right + GAP
    y = tr.top + tr.height / 2 - th / 2
  } else if (pos === 'left') {
    x = tr.left - tw - GAP
    y = tr.top + tr.height / 2 - th / 2
  } else {
    // default: top
    x = tr.left + tr.width / 2 - tw / 2
    y = tr.top - th - GAP
  }

  // Viewport clamping — prevent bleed off screen edges
  const vw = window.innerWidth
  const vh = window.innerHeight
  x = Math.max(6, Math.min(x, vw - tw - 6))
  y = Math.max(6, Math.min(y, vh - th - 6))

  el.style.left = x + 'px'
  el.style.top  = y + 'px'
}

function hideTooltip () {
  clearTimeout(hideTimer)
  hideTimer = setTimeout(() => {
    if (tip) tip.classList.remove('is-visible')
  }, 80)
}

function onMouseEnter (e) {
  if (!(e.target instanceof Element)) return
  const trigger = e.target.closest('[data-tooltip]')
  if (trigger) showTooltip(trigger)
}

function onMouseLeave (e) {
  if (!(e.target instanceof Element)) return
  const trigger = e.target.closest('[data-tooltip]')
  if (trigger) hideTooltip()
}

function onScroll () {
  hideTooltip()
}

function onFocusIn (e) {
  if (!(e.target instanceof Element)) return
  const trigger = e.target.closest('[data-tooltip]')
  if (trigger) showTooltip(trigger)
}

function onFocusOut () {
  hideTooltip()
}

export const TooltipPlugin = {
  install (app) {
    // Use capture-phase listeners on document so every [data-tooltip] in the
    // entire app is covered — current and future, inside Vue or not.
    document.addEventListener('mouseenter', onMouseEnter, true)
    document.addEventListener('mouseleave', onMouseLeave, true)
    document.addEventListener('focusin',    onFocusIn,    true)
    document.addEventListener('focusout',   onFocusOut,   true)
    document.addEventListener('scroll',     onScroll,     true)

    // Ensure the singleton bubble exists on boot
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', getOrCreateTip)
    } else {
      getOrCreateTip()
    }
  },
}
