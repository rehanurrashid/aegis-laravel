/**
 * FormEnhancerPlugin.js
 *
 * Auto-upgrades:
 *   input[type="date"].form-input   →  flatpickr
 *   select.form-select              →  TomSelect
 *
 * Add  data-no-enhance  to any field you want left alone.
 *
 * Core fix for double-init: a pending Set debounces concurrent rAF scans.
 * If the same root is queued twice before the rAF fires, it only scans once.
 * The MARK attribute + el.tomselect check prevent re-enhancement on re-scans.
 */

import flatpickr from 'flatpickr'
import TomSelect from 'tom-select'

// ─── Configs ──────────────────────────────────────────────────────────────────
const FLATPICKR_CONFIG = {
  dateFormat:    'Y-m-d',
  altInput:      true,
  altFormat:     'M j, Y',
  allowInput:    true,
  disableMobile: false,
  appendTo:      document.body,
  onReady(_, __, fp) {
    fp.altInput?.classList.add('form-input', 'flatpickr-alt-field')
    fp.altInput?.removeAttribute('readonly')
  },
}

const TOMSELECT_CONFIG = {
  create:           false,
  allowEmptyOption: true,
  controlInput:     null,
  maxOptions:       250,
  render: {
    no_results: () => `<div class="no-results">No options found</div>`,
  },
}

// ─── Guard ────────────────────────────────────────────────────────────────────
const MARK = 'data-ae-enhanced'

function isEnhanced(el) {
  // Check attribute, widget instance, AND the ts-hidden-accessible class
  // TomSelect adds to the original <select> after wrapping it.
  return el.hasAttribute(MARK)
      || el._flatpickr != null
      || el.tomselect != null
      || el.classList.contains('ts-hidden-accessible')
}
function mark(el) { el.setAttribute(MARK, '1') }

function shouldSkip(el) {
  // Honour data-no-enhance regardless of value ("", "true", etc.)
  return el.hasAttribute('data-no-enhance')
}

// ─── Value-setter patches ─────────────────────────────────────────────────────
const _inputValueProp  = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,  'value')
const _selectValueProp = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value')

function patchDateInput(el) {
  let _syncing = false
  Object.defineProperty(el, 'value', {
    get() { return _inputValueProp.get.call(this) },
    set(v) {
      const normalised = v ? String(v).slice(0, 10) : ''
      _inputValueProp.set.call(this, normalised)
      if (!_syncing && this._flatpickr) {
        _syncing = true
        try { this._flatpickr.setDate(normalised || null, false) }
        finally { _syncing = false }
      }
    },
    configurable: true,
  })
}

function patchSelect(el) {
  let _syncing = false
  Object.defineProperty(el, 'value', {
    get() { return _selectValueProp.get.call(this) },
    set(v) {
      _selectValueProp.set.call(this, v)
      if (!_syncing && this.tomselect) {
        _syncing = true
        try { this.tomselect.setValue(v, true) }
        finally { _syncing = false }
      }
    },
    configurable: true,
  })
}

// ─── Enhancers ────────────────────────────────────────────────────────────────
let _observer = null

function enhanceDateInput(el) {
  if (isEnhanced(el) || shouldSkip(el)) return
  mark(el)
  flatpickr(el, { ...FLATPICKR_CONFIG, defaultDate: el.value || null })
  patchDateInput(el)
}

function enhanceSelect(el) {
  if (isEnhanced(el) || shouldSkip(el)) return

  mark(el)  // Mark IMMEDIATELY — before async TomSelect init — so racing rAFs skip it
  _observer?.disconnect()

  try {
    new TomSelect(el, {
      ...TOMSELECT_CONFIG,
      onInitialize() {
        this.control.style.setProperty('--ts-pr-caret', '0px')
        this.control.style.setProperty('--ts-pr-clear-button', '0px')
        if (el.classList.contains('form-select-sm')) {
          this.wrapper.classList.add('ts-sm')
        }
        patchSelect(el)
        const currentValue = _selectValueProp.get.call(el)
        if (currentValue) this.setValue(currentValue, true)
      },
    })
  } catch (_) { /* already initialised */ }

  _resumeObserver()
}

function _resumeObserver() {
  if (!_observer) return
  _observer.observe(document.body, { childList: true, subtree: true })
}

// ─── Scan ─────────────────────────────────────────────────────────────────────
function scanAndEnhance(root = document) {
  root.querySelectorAll(`input[type="date"].form-input:not([${MARK}]):not([data-no-enhance])`)
      .forEach(enhanceDateInput)
  root.querySelectorAll(`select.form-select:not([${MARK}]):not(.ts-hidden-accessible):not([data-no-enhance])`)
      .forEach(enhanceSelect)
}

// ─── Debounced deferred scan ──────────────────────────────────────────────────
// Multiple mutations (e.g. error div added + class changed) can fire in the
// same tick and each queue a deferredScanSubtree. Without debouncing, two rAFs
// both see the same un-marked select and both call enhanceSelect → double init.
// We use a WeakSet of pending roots: if a root is already queued, don't queue again.
const _pendingRoots = new Set()

function deferredScanSubtree(root) {
  if (_pendingRoots.has(root)) return   // already queued — skip
  _pendingRoots.add(root)
  requestAnimationFrame(() => {
    _pendingRoots.delete(root)
    scanAndEnhance(root)
  })
}

// ─── Global sync helper ───────────────────────────────────────────────────────
export function syncFormEnhancements() {
  requestAnimationFrame(() => {
    document.querySelectorAll('.modal-overlay.open .modal-body').forEach(root => {
      root.querySelectorAll(`select.form-select[${MARK}]`).forEach(el => {
        const v = _selectValueProp.get.call(el)
        if (el.tomselect) el.tomselect.setValue(v ?? '', true)
      })
      root.querySelectorAll(`input[type="date"].form-input[${MARK}]`).forEach(el => {
        const raw = _inputValueProp.get.call(el)
        const v = raw ? String(raw).slice(0, 10) : ''
        if (el._flatpickr) {
          el._flatpickr.setDate(v || null, false)
          if (el._flatpickr.altInput && v) {
            el._flatpickr.altInput.value = el._flatpickr.formatDate(
              el._flatpickr.parseDate(v, 'Y-m-d'),
              el._flatpickr.config.altFormat
            )
          } else if (el._flatpickr.altInput && !v) {
            el._flatpickr.altInput.value = ''
          }
        }
      })
    })
  })
}

// ─── Plugin ───────────────────────────────────────────────────────────────────
export const FormEnhancerPlugin = {
  install(app) {
    app.mixin({
      mounted() {
        if (this.$el === document.querySelector('#app > *')) {
          requestAnimationFrame(() => scanAndEnhance())
        }
      },
    })

    _observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        for (const node of m.addedNodes) {
          if (node.nodeType !== 1) continue
          if (
            node.classList?.contains('ts-wrapper')    ||
            node.classList?.contains('ts-dropdown')   ||
            node.classList?.contains('flatpickr-calendar') ||
            node.classList?.contains('flatpickr-wrapper')
          ) continue

          deferredScanSubtree(node)
        }
      }
    })

    const start = () => {
      _resumeObserver()
      requestAnimationFrame(() => scanAndEnhance())
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start)
    } else {
      start()
    }
  },
}
