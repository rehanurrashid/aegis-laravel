<template>
  <div class="alert alert-info" style="border-left:4px solid var(--blue, #3b82f6);">
    <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
    <div class="alert-content">
      <div class="alert-title" style="font-weight:700;margin-bottom:4px;">MA'AT Professional Continuity Services</div>
      <div style="font-size:13px;color:var(--text-2);margin-bottom:10px;">
        Need hands-on continuity support? MA'AT offers professional services including
        Continuity Plan Setup ($1,500), Continuity Assurance ($75/mo), and
        Critical Moment Response Reserve ($3,500) — delivered by licensed, insured continuity professionals.
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="https://www.maatpracticefirm.com" target="_blank" rel="noopener"
           class="btn btn-outline" style="font-size:12px;">
          <AegisIcon name="external-link" :size="13" /> Learn More at maatpracticefirm.com
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
// No props — static banner. AegisIcon is globally registered.
</script>
