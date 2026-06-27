<template>
  <AppLayout :user="user" portal="support_steward" activePage="overview" pageTitle="Overview — Start Here" :has-emergency="true">
    <!-- HERO + SETUP CHECKLIST -->
    <section class="ov-hero">
      <div class="ov-hero-eyebrow">Welcome to Aegis</div>
      <h1 class="ov-hero-title">Hello, <em>Linda</em>.</h1>
      <p class="ov-hero-sub">You are a Support Steward — a trusted contact designated by a healthcare practitioner to coordinate logistics and report critical incidents. This guide explains key terms, why Aegis matters, and exactly how to use this portal step by step.</p>

      <div class="ov-setup" style="--ov-tiles: 4;">
        <div class="ov-setup-head">
          <span class="ov-setup-label">Your Setup Path</span>
          <div class="ov-setup-progress" aria-hidden="true">
            <div class="ov-setup-progress-fill" style="width: 50%"></div>
          </div>
          <span class="ov-setup-counter">2/4 <span>complete</span></span>
        </div>
        <div class="ov-setup-grid">
      <a class="ov-setup-tile next" href="/support-steward/profile">
        <div class="ov-setup-tile-row">
          <span class="ov-setup-num">01</span>
          <span class="ov-setup-tile-label">Profile</span>
        </div>
        <span class="ov-setup-tile-desc">Contact & relationship</span>
      </a>      <a class="ov-setup-tile done" href="/support-steward/profile">
        <div class="ov-setup-tile-row">
          <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
          <span class="ov-setup-tile-label">Note to your CS</span>
        </div>
        <span class="ov-setup-tile-desc">A short introduction</span>
      </a>      <a class="ov-setup-tile done" href="/support-steward/providers">
        <div class="ov-setup-tile-row">
          <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
          <span class="ov-setup-tile-label">My Provider</span>
        </div>
        <span class="ov-setup-tile-desc">Active designation</span>
      </a>      <a class="ov-setup-tile" href="/support-steward/my-tasks">
        <div class="ov-setup-tile-row">
          <span class="ov-setup-num">04</span>
          <span class="ov-setup-tile-label">Preparation Tasks</span>
        </div>
        <span class="ov-setup-tile-desc">Standby checklist</span>
      </a>
        </div>
      </div>
    </section>

    <!-- NOTICE BAND -->
    <div class="ov-notice">
      <span class="ov-notice-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div class="ov-notice-text"><strong>Remember: You may be accessing this during a difficult time.</strong> If the practitioner you support has passed away, is incapacitated, or is unreachable, go directly to <strong>Critical Incident Log</strong> to report the situation. The Continuity Steward will take it from there.</div>
    </div>

    <!-- TABS -->
    <div class="tabs-segmented" role="tablist" style="margin-bottom:28px;">
      <button class="tab-pill" :class="{ active: activeTab === 'terms' }" @click="activeTab = 'terms'" role="tab" :aria-selected="activeTab === 'terms'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Key Terms
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'why' }" @click="activeTab = 'why'" role="tab" :aria-selected="activeTab === 'why'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Why Aegis
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'how' }" @click="activeTab = 'how'" role="tab" :aria-selected="activeTab === 'how'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        How to Use Aegis
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'faq' }" @click="activeTab = 'faq'" role="tab" :aria-selected="activeTab === 'faq'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        FAQ
      </button>
    </div>

    <!-- KEY TERMS -->
    <div class="ov-section" :class="{ active: activeTab === 'terms' }">
      <div class="kt-grid">
    <article class="kt-card">
      <h3 class="kt-term">Support Steward</h3>
      <p class="kt-def">The trusted contact (often family member or office staff) designated by a practitioner to coordinate communications, logistics, and incident reporting alongside the Continuity Steward.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">Continuity Steward</h3>
      <p class="kt-def">The licensed professional who manages the practitioner's practice during a critical incident. The CS receives your incident report and verifies it before activating the Continuity Plan.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">Practitioner / Provider</h3>
      <p class="kt-def">The healthcare professional you support. They have designated you in their Continuity Plan as a first-line contact during a critical incident.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">Critical Incident</h3>
      <p class="kt-def">A defined event (death, incapacitation, missing person, natural disaster, etc.) that activates the practitioner's Continuity Plan. You report the incident; the CS verifies and acts.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">Critical Incident Log</h3>
      <p class="kt-def">The page where you report a critical incident. Includes a sensitive false-reporting warning — only submit if the situation is real.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">Standby Tasks</h3>
      <p class="kt-def">Preparation tasks to complete before a critical moment occurs — so you and the provider are fully prepared. Available under My Tasks.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">View Important Documents</h3>
      <p class="kt-def">Read-only access to the practitioner's Continuity Plan and supplemental documents so you understand what will happen during an incident.</p>
    </article>    <article class="kt-card">
      <h3 class="kt-term">Aegis Network</h3>
      <p class="kt-def">The professional network connecting Practitioners, Continuity Stewards, Support Stewards, and Business Partners on the Aegis platform.</p>
    </article>
      </div>
    </div>

    <!-- WHY AEGIS -->
    <div class="ov-section" :class="{ active: activeTab === 'why' }">
      <div class="wa-grid">
    <article class="wa-card">
      <div class="wa-num">01</div>
      <h3 class="wa-title">You are the first responder</h3>
      <p class="wa-desc">Critical incidents often surface through family or close staff first. Aegis gives you a structured way to escalate without confusion or paperwork.</p>
    </article>    <article class="wa-card">
      <div class="wa-num">02</div>
      <h3 class="wa-title">Clear authorization</h3>
      <p class="wa-desc">Each incident type (death, short-term incapacitation, missing person, etc.) has a per-Steward authorization flag. You will only see and trigger the types the practitioner authorized you for.</p>
    </article>    <article class="wa-card">
      <div class="wa-num">03</div>
      <h3 class="wa-title">Protected from misuse</h3>
      <p class="wa-desc">Triggering a critical incident is consequential — vaults unlock, tasks generate, contacts are notified. Aegis includes a false-reporting warning and verification step to protect everyone.</p>
    </article>    <article class="wa-card">
      <div class="wa-num">04</div>
      <h3 class="wa-title">Step-by-step guidance</h3>
      <p class="wa-desc">You never have to wonder what to do. The Tasks page tells you exactly what to do before and during a critical moment.</p>
    </article>
      </div>
    </div>

    <!-- HOW TO USE -->
    <div class="ov-section" :class="{ active: activeTab === 'how' }">
      <div class="hu-wrap">
        <div class="hu-steps">
    <div class="hu-step">
      <div class="hu-num">1</div>
      <div class="hu-content">
        <h3 class="hu-step-title">Complete your profile</h3>
        <p class="hu-step-desc">Fill in your contact information and your relationship to the practitioner under Edit Profile. This appears on the Continuity Plan.</p>
      </div>
    </div>    <div class="hu-step">
      <div class="hu-num">2</div>
      <div class="hu-content">
        <h3 class="hu-step-title">Review your authorizations</h3>
        <p class="hu-step-desc">Open My Profile and check which incident types the practitioner has authorized you to report. Discuss any gaps with them.</p>
      </div>
    </div>    <div class="hu-step">
      <div class="hu-num">3</div>
      <div class="hu-content">
        <h3 class="hu-step-title">Work through the preparation checklist</h3>
        <p class="hu-step-desc">Before anything happens, complete the standby tasks with the practitioner. This ensures contact info, instructions, and documents are in place.</p>
      </div>
    </div>    <div class="hu-step">
      <div class="hu-num">4</div>
      <div class="hu-content">
        <h3 class="hu-step-title">Trigger an incident if needed</h3>
        <p class="hu-step-desc">When a critical event occurs, go to Critical Incident Log → Report new. Include narrative details and contact attempts. The Continuity Steward verifies and acts.</p>
      </div>
    </div>    <div class="hu-step">
      <div class="hu-num">5</div>
      <div class="hu-content">
        <h3 class="hu-step-title">Coordinate during the incident</h3>
        <p class="hu-step-desc">Stay available to the Continuity Steward by phone and through Messages. The CS may ask for additional information or coordination.</p>
      </div>
    </div>
        </div>
      </div>
    </div>

    <!-- FAQ -->
    <div class="ov-section" :class="{ active: activeTab === 'faq' }">
      <div class="faq-grid">
    <div class="accordion-item" @click="toggleFaq(0)" :class="{ open: openFaq === 0 }">
      <div class="accordion-trigger">
        <span>What if I am wrong about the incident?</span>
        <span class="accordion-caret">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
      </div>
      <div class="accordion-content">Aegis includes a verification step: the Continuity Steward reviews your report before any Continuity Plan is activated. If you're unsure, share what you know in the narrative and the CS will confirm before acting.</div>
    </div>    <div class="accordion-item" @click="toggleFaq(1)" :class="{ open: openFaq === 1 }">
      <div class="accordion-trigger">
        <span>Can I withdraw a report after submitting?</span>
        <span class="accordion-caret">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
      </div>
      <div class="accordion-content">Yes — contact the Continuity Steward or Aegis support right away. Until the CS verifies the incident, no Continuity Plan actions are taken and a report can be retracted.</div>
    </div>    <div class="accordion-item" @click="toggleFaq(2)" :class="{ open: openFaq === 2 }">
      <div class="accordion-trigger">
        <span>Am I paid for this work?</span>
        <span class="accordion-caret">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
      </div>
      <div class="accordion-content">No. The Support Steward role is a designated point of contact, not a paid position. There are no fees or payouts associated with it.</div>
    </div>    <div class="accordion-item" @click="toggleFaq(3)" :class="{ open: openFaq === 3 }">
      <div class="accordion-trigger">
        <span>What if I cannot reach the Continuity Steward?</span>
        <span class="accordion-caret">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
      </div>
      <div class="accordion-content">Submit the Critical Incident Log report anyway — it notifies the CS and any alternates automatically. You can also message them in the portal or contact Aegis support during a critical moment.</div>
    </div>
      </div>

      <div class="faq-support">
        <span class="faq-support-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </span>
        <div class="faq-support-text">
          <div class="faq-support-title">Still have questions?</div>
          <div class="faq-support-sub">Our team responds within one business day, sooner during a critical moment.</div>
        </div>
        <a href="/messages" class="faq-support-cta">Contact Support &rarr;</a>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });
const activeTab = ref('terms');
const openFaq = ref(-1);
function toggleFaq(i) { openFaq.value = openFaq.value === i ? -1 : i; }
</script>
<style scoped>
/* ════════════════════════════════════════════════════════════════════
   OVERVIEW — Shared cross-portal template
   Namespace: .ov-* / .kt-* / .wa-* / .hu-* / .faq-*
   ═══════════════════════════════════════════════════════════════════ */

/* ── HERO BAND ── */
.ov-hero {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px);
  padding: 30px 32px 28px;
  margin: 8px 0 22px;
  position: relative;
  overflow: hidden;
}
.ov-hero::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--gold-dark), var(--gold) 50%, var(--gold-dark));
  opacity: 0.85;
}
.ov-hero-eyebrow {
  display: inline-flex; align-items: center; gap: 9px;
  font-family: var(--font-sans, 'Inter', sans-serif);
  font-size: 10px; font-weight: 700;
  letter-spacing: 1.6px; text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 14px;
}
.ov-hero-eyebrow::before {
  content: ""; width: 18px; height: 1px;
  background: var(--gold-dark); display: inline-block;
}
.ov-hero-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 38px; font-weight: 600;
  letter-spacing: -0.5px; line-height: 1.1;
  color: var(--text); margin: 0 0 12px;
  max-width: 800px;
}
.ov-hero-title em {
  font-style: italic; color: var(--gold-dark); font-weight: 500;
}
.ov-hero-sub {
  font-size: 14px; color: var(--text-3);
  line-height: 1.65; max-width: 720px; margin: 0;
}

/* ── SETUP CHECKLIST STRIP ── */
.ov-setup {
  margin-top: 24px;
  padding: 20px 22px 18px;
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius-lg, 14px);
  box-shadow: 0 1px 2px rgba(30,28,26,0.04);
}
.ov-setup-head {
  display: flex; align-items: center; gap: 14px;
  margin-bottom: 14px; flex-wrap: wrap;
}
.ov-setup-label {
  font-size: 10px; font-weight: 700;
  letter-spacing: 1.4px; text-transform: uppercase;
  color: var(--gold-dark, #a0813e);
}
.ov-setup-progress {
  flex: 1; height: 4px;
  background: var(--surface-3, #f0ece2);
  border-radius: 99px; overflow: hidden;
  min-width: 160px; max-width: 260px;
}
.ov-setup-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--gold, #c4a96a), var(--gold-dark, #a0813e));
  border-radius: 99px; transition: width 0.5s ease;
}
.ov-setup-counter {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 14px; font-weight: 600; color: var(--text, #1e1c1a);
}
.ov-setup-counter span { color: var(--text-4, #a89f94); font-weight: 500; }

.ov-setup-grid {
  display: grid;
  grid-template-columns: repeat(var(--ov-tiles, 5), 1fr);
  gap: 10px;
}
.ov-setup-tile {
  display: flex; flex-direction: column; gap: 6px;
  padding: 12px 14px;
  background: var(--surface-2, #faf7f1);
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius, 10px);
  text-decoration: none; color: inherit;
  position: relative;
  transition: border-color var(--transition, 0.18s ease),
              background  var(--transition, 0.18s ease),
              transform   var(--transition, 0.18s ease);
}
.ov-setup-tile:hover {
  border-color: var(--gold, #c4a96a);
  background: var(--surface, #ffffff);
  transform: translateY(-1px);
}
.ov-setup-tile-row { display: flex; align-items: center; gap: 8px; }
.ov-setup-num {
  width: 22px; height: 22px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 11px; font-weight: 700;
  background: var(--surface-3, #f0ece2);
  color: var(--text-3, #6b655d);
  flex-shrink: 0;
}
.ov-setup-tile.done .ov-setup-num {
  background: var(--green-dark, #2f7d54); color: #fff;
}
.ov-setup-tile.next .ov-setup-num {
  background: var(--gold-dark, #a0813e); color: #fff;
  box-shadow: 0 0 0 3px rgba(160,129,62,0.18);
}
.ov-setup-tile-label {
  font-family: var(--font-sans, 'Inter', sans-serif);
  font-size: 12.5px; font-weight: 600; color: var(--text, #1e1c1a);
}
.ov-setup-tile-desc { font-size: 11px; color: var(--text-4, #a89f94); }
.ov-setup-tile.next::after {
  content: "Next"; position: absolute;
  top: 8px; right: 10px;
  font-size: 9px; font-weight: 700;
  letter-spacing: 0.6px; text-transform: uppercase;
  color: var(--gold-dark, #a0813e);
}

/* ── NOTICE BAND ── */
.ov-notice {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 18px;
  background: rgba(196,169,106,0.08);
  border: 1px solid rgba(196,169,106,0.3);
  border-left: 4px solid var(--gold, #c4a96a);
  border-radius: var(--radius-lg, 14px);
  margin-bottom: 24px;
}
.ov-notice-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px;
  border-radius: 50%; background: rgba(160,129,62,0.14);
  color: var(--gold-dark, #a0813e); flex-shrink: 0;
}
.ov-notice-text {
  font-size: 13px; color: var(--text-2, #3d3a36);
  line-height: 1.6; flex: 1; min-width: 0;
}
.ov-notice-text strong { color: var(--text, #1e1c1a); }
.ov-notice-text a { color: var(--gold-dark, #a0813e); font-weight: 600; }

/* ── TABS ── */
.ov-section { display: none; }
.ov-section.active { display: block; }

/* ── KEY TERMS GRID ── */
.kt-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}
.kt-card {
  padding: 20px 22px;
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius-lg, 14px);
  position: relative;
  transition: border-color var(--transition, 0.18s ease),
              box-shadow var(--transition, 0.18s ease);
}
.kt-card:hover {
  border-color: var(--gold, #c4a96a);
  box-shadow: 0 6px 18px rgba(30,28,26,0.06);
}
.kt-term {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 17px; font-weight: 700;
  color: var(--gold-dark, #a0813e);
  margin: 0 0 8px; letter-spacing: 0.1px;
}
.kt-def {
  font-size: 13px; color: var(--text-2, #3d3a36);
  line-height: 1.65; margin: 0;
}

/* ── WHY AEGIS GRID ── */
.wa-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
.wa-card {
  padding: 24px 24px 22px;
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius-lg, 14px);
  position: relative;
  transition: border-color var(--transition, 0.18s ease),
              transform var(--transition, 0.18s ease);
}
.wa-card:hover {
  border-color: var(--gold, #c4a96a);
  transform: translateY(-2px);
}
.wa-num {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 11px; font-weight: 700;
  letter-spacing: 1.4px; color: var(--gold-dark, #a0813e);
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 14px;
}
.wa-num::after {
  content: ""; flex: 1; height: 1px;
  background: var(--border, #e4dfd7);
}
.wa-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 17px; font-weight: 700;
  color: var(--text, #1e1c1a);
  margin: 0 0 8px; line-height: 1.25; letter-spacing: -0.1px;
}
.wa-desc {
  font-size: 13px; color: var(--text-3, #6b655d);
  line-height: 1.7; margin: 0;
}

/* ── HOW TO USE TIMELINE ── */
.hu-wrap {
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius-xl, 18px);
  padding: 8px 30px;
  position: relative;
}
.hu-steps { position: relative; padding: 8px 0 8px 14px; }
.hu-steps::before {
  content: ""; position: absolute;
  left: 33px; top: 32px; bottom: 32px;
  width: 2px; background: var(--border, #e4dfd7);
  border-radius: 1px;
}
.hu-step {
  display: flex; gap: 22px; align-items: flex-start;
  padding: 22px 0; position: relative;
  border-bottom: 1px solid var(--border, #e4dfd7);
}
.hu-step:last-child { border-bottom: none; }
.hu-num {
  width: 38px; height: 38px; border-radius: 50%;
  background: var(--surface, #ffffff);
  border: 2px solid var(--border, #e4dfd7);
  color: var(--text-3, #6b655d);
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 15px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; position: relative; z-index: 2;
  transition: border-color var(--transition, 0.18s ease),
              color        var(--transition, 0.18s ease),
              background   var(--transition, 0.18s ease);
}
.hu-content { flex: 1; min-width: 0; }
.hu-step-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 17px; font-weight: 700;
  color: var(--text, #1e1c1a);
  margin: 2px 0 6px; letter-spacing: -0.1px;
}
.hu-step-desc {
  font-size: 13px; color: var(--text-2, #3d3a36);
  line-height: 1.65; margin: 0;
}

/* ── FAQ ── */
.faq-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px; align-items: start;
}
.faq-support {
  margin-top: 26px;
  display: flex; align-items: center; gap: 18px;
  padding: 22px 26px;
  background: linear-gradient(135deg, var(--gold-tint, #faf6ec), var(--surface, #ffffff));
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius-xl, 18px);
}
.faq-support-icon {
  width: 48px; height: 48px; border-radius: 50%;
  background: var(--gold-dark, #a0813e); color: #fff;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(160,129,62,0.25);
}
.faq-support-text { flex: 1; }
.faq-support-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 18px; font-weight: 700;
  color: var(--text, #1e1c1a);
  margin-bottom: 2px; letter-spacing: -0.1px;
}
.faq-support-sub {
  font-size: 13px; color: var(--text-3, #6b655d); line-height: 1.5;
}
.faq-support-cta {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 99px;
  background: var(--gold-dark, #a0813e); color: #fff;
  font-size: 12.5px; font-weight: 700;
  text-decoration: none; flex-shrink: 0;
  transition: background var(--transition, 0.18s ease);
}
.faq-support-cta:hover { background: var(--gold-deep, #7d6429); }

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
  .wa-grid       { grid-template-columns: repeat(2, 1fr); }
  .ov-setup-grid { grid-template-columns: repeat(3, 1fr) !important; }
}
@media (max-width: 820px) {
  .ov-hero            { padding: 24px 20px 22px; }
  .ov-hero-title      { font-size: 28px; }
  .kt-grid, .wa-grid  { grid-template-columns: 1fr; }
  .faq-grid           { grid-template-columns: 1fr; }
  .ov-setup-grid      { grid-template-columns: repeat(2, 1fr) !important; }
  .ov-notice          { flex-direction: column; align-items: flex-start; gap: 12px; }
  .hu-wrap            { padding: 0 18px; }
  .hu-steps::before   { left: 25px; }
  .faq-support        { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 480px) {
  .ov-setup-grid { grid-template-columns: 1fr !important; }
}
</style>