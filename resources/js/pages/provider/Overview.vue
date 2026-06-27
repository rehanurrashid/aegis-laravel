<template>
  <AppLayout :user="user" portal="practitioner" activePage="overview" pageTitle="Overview — Start Here">
    <!-- HERO + SETUP CHECKLIST -->
    <section class="ov-hero">
      <div class="ov-hero-eyebrow">Welcome to Aegis</div>
      <h1 class="ov-hero-title">Hello, <em>{{ firstName }}</em>.</h1>
      <p class="ov-hero-sub">You are a Practitioner — a licensed professional building a Continuity Plan so your clients and practice remain supported if you become unavailable. This guide explains key terms, why Aegis matters, and how to use this portal step by step.</p>

      <div class="ov-setup" style="--ov-tiles: 5;">
        <div class="ov-setup-head">
          <span class="ov-setup-label">Your Setup Path</span>
          <div class="ov-setup-progress" aria-hidden="true">
            <div class="ov-setup-progress-fill" style="width: 100%"></div>
          </div>
          <span class="ov-setup-counter">5/5 <span>complete</span></span>
        </div>
        <div class="ov-setup-grid">
          <a class="ov-setup-tile done" href="/provider/profile">
            <div class="ov-setup-tile-row">
              <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
              <span class="ov-setup-tile-label">Profile</span>
            </div>
            <span class="ov-setup-tile-desc">Credentials & contact</span>
          </a>
          <a class="ov-setup-tile done" href="/provider/continuity-stewards">
            <div class="ov-setup-tile-row">
              <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
              <span class="ov-setup-tile-label">Continuity Steward</span>
            </div>
            <span class="ov-setup-tile-desc">Licensed successor</span>
          </a>
          <a class="ov-setup-tile done" href="/provider/support-stewards">
            <div class="ov-setup-tile-row">
              <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
              <span class="ov-setup-tile-label">Support Steward</span>
            </div>
            <span class="ov-setup-tile-desc">Personal contact</span>
          </a>
          <a class="ov-setup-tile done" href="/provider/continuity-plan">
            <div class="ov-setup-tile-row">
              <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
              <span class="ov-setup-tile-label">Continuity Plan</span>
            </div>
            <span class="ov-setup-tile-desc">Your master document</span>
          </a>
          <a class="ov-setup-tile done" href="/provider/vault">
            <div class="ov-setup-tile-row">
              <span class="ov-setup-num"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg></span>
              <span class="ov-setup-tile-label">Document Vault</span>
            </div>
            <span class="ov-setup-tile-desc">Sensitive records</span>
          </a>
        </div>
      </div>
    </section>

    <!-- NOTICE BAND -->
    <div class="ov-notice">
      <span class="ov-notice-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.007H12v-.007z"/></svg>
      </span>
      <div class="ov-notice-text">If you have not yet signed your Continuity Plan, start with <strong>Continuity Plan</strong> to define what happens to your practice during a critical moment, then designate your Stewards.</div>
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
          <h3 class="kt-term">Continuity Plan</h3>
          <p class="kt-def">The core document you create to define what should happen to your practice during a critical moment. Contains task lists, permissions, and instructions for your designated Stewards.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Continuity Steward</h3>
          <p class="kt-def">The licensed professional you designate to manage your practice during a critical incident. Responsible for carrying out the tasks outlined in your Continuity Plan.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Support Steward</h3>
          <p class="kt-def">A trusted contact (often family or office staff) you designate to coordinate communications and logistics alongside the Continuity Steward during a critical incident.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Critical Incident</h3>
          <p class="kt-def">A defined event (death, incapacitation, missing person, natural disaster, etc.) that activates your Continuity Plan. Each type can be enabled or disabled in your plan.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Document Vault</h3>
          <p class="kt-def">A secure, four-zone repository for credentials, client roster, emergency documents, and standard records. Sealed during standby and unlocked only on a verified critical incident.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Standby Tasks</h3>
          <p class="kt-def">Preparation tasks to complete before a critical moment occurs — so your Stewards are fully ready when needed. Think of this as the preparation checklist.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Aegis Network</h3>
          <p class="kt-def">The professional network connecting Practitioners, Continuity Stewards, Support Stewards, and Business Partners on the Aegis platform.</p>
        </article>
        <article class="kt-card">
          <h3 class="kt-term">Business Partners</h3>
          <p class="kt-def">Professionals offering services to practitioners through Aegis — including billing, legal, accounting, and other practice support services.</p>
        </article>
      </div>
    </div>

    <!-- WHY AEGIS -->
    <div class="ov-section" :class="{ active: activeTab === 'why' }">
      <div class="wa-grid">
        <article class="wa-card">
          <div class="wa-num">01</div>
          <h3 class="wa-title">Client continuity</h3>
          <p class="wa-desc">Your clients deserve uninterrupted care. Aegis ensures their records, medications, and treatment plans transfer cleanly to the right hands.</p>
        </article>
        <article class="wa-card">
          <div class="wa-num">02</div>
          <h3 class="wa-title">Family protection</h3>
          <p class="wa-desc">Without a Continuity Plan, your loved ones are left to figure things out alone. Aegis gives them a clear, signed protocol your designated Stewards carry out.</p>
        </article>
        <article class="wa-card">
          <div class="wa-num">03</div>
          <h3 class="wa-title">Care responsibility</h3>
          <p class="wa-desc">State licensing boards require continuity arrangements. Aegis produces an audit-ready, signed Continuity Plan that meets HIPAA and most state requirements.</p>
        </article>
        <article class="wa-card">
          <div class="wa-num">04</div>
          <h3 class="wa-title">Peace of mind</h3>
          <p class="wa-desc">Once your plan is signed and your Stewards are certified, you can focus on client care knowing the unexpected has been planned for.</p>
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
              <p class="hu-step-desc">Fill in your specialty, license, and contact information under My Profile. This populates your Continuity Plan.</p>
            </div>
          </div>
          <div class="hu-step">
            <div class="hu-num">2</div>
            <div class="hu-content">
              <h3 class="hu-step-title">Build your Continuity Plan</h3>
              <p class="hu-step-desc">Open Continuity Plan and walk through the five sections: practice details, incident types, task assignments, document vault setup, and signing.</p>
            </div>
          </div>
          <div class="hu-step">
            <div class="hu-num">3</div>
            <div class="hu-content">
              <h3 class="hu-step-title">Designate your Stewards</h3>
              <p class="hu-step-desc">Invite a Continuity Steward (primary + alternate) and a Support Steward. Each one needs to accept and certify their task list.</p>
            </div>
          </div>
          <div class="hu-step">
            <div class="hu-num">4</div>
            <div class="hu-content">
              <h3 class="hu-step-title">Stock your Document Vault</h3>
              <p class="hu-step-desc">Upload credentials, client roster, emergency contacts, and standard records into the four vault zones. The vault is sealed until a verified critical incident.</p>
            </div>
          </div>
          <div class="hu-step">
            <div class="hu-num">5</div>
            <div class="hu-content">
              <h3 class="hu-step-title">Review annually</h3>
              <p class="hu-step-desc">Aegis reminds you to review and re-sign your plan every 12 months. Update Stewards, contact info, and tasks as your practice evolves.</p>
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
            <span>Who can see my Continuity Plan?</span>
            <span class="accordion-caret">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
          <div class="accordion-content">Only you, your designated Stewards (after they accept), and Aegis support staff with explicit authorization. Client-identifying data lives in the sealed vault and is never visible until a verified incident.</div>
        </div>
        <div class="accordion-item" @click="toggleFaq(1)" :class="{ open: openFaq === 1 }">
          <div class="accordion-trigger">
            <span>What happens if my Continuity Steward is unavailable?</span>
            <span class="accordion-caret">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
          <div class="accordion-content">You can designate an Alternate CS who steps in if the Primary cannot serve. The Support Steward can also escalate to your Alternate CS directly.</div>
        </div>
        <div class="accordion-item" @click="toggleFaq(2)" :class="{ open: openFaq === 2 }">
          <div class="accordion-trigger">
            <span>How is a critical incident verified?</span>
            <span class="accordion-caret">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
          <div class="accordion-content">A Support Steward triggers an incident with documentation (death certificate, doctor note, police report, etc.). Your Continuity Steward verifies the documentation before the vault unlocks and tasks generate.</div>
        </div>
        <div class="accordion-item" @click="toggleFaq(3)" :class="{ open: openFaq === 3 }">
          <div class="accordion-trigger">
            <span>Can I cancel my plan?</span>
            <span class="accordion-caret">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
          <div class="accordion-content">Yes — anytime from Settings. Your designated Stewards are notified and the plan moves to "expired" status. Vault contents remain locked until you delete them or reactivate.</div>
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
        <a href="/provider/messages" class="faq-support-cta">Contact Support &rarr;</a>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
const props = defineProps({ user: Object });
const firstName = computed(() => (props.user?.display_name || props.user?.name || 'Sarah').replace(/^Dr\.?\s+/i, '').split(' ')[0]);
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
  background: #f8f5ee;
  border: 1px solid #e8e4db;
  border-radius: 24px;
  padding: 36px 40px 32px;
  margin: 8px 0 24px;
  position: relative;
  overflow: hidden;
}
.ov-hero::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: #a0813e;
}
.ov-hero-eyebrow {
  display: inline-flex; align-items: center; gap: 10px;
  font-family: var(--font-sans, 'Inter', sans-serif);
  font-size: 10px; font-weight: 700;
  letter-spacing: 1.8px; text-transform: uppercase;
  color: #a0813e;
  margin-bottom: 16px;
}
.ov-hero-eyebrow::before {
  content: ""; width: 24px; height: 1px;
  background: #a0813e; display: inline-block;
}
.ov-hero-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 42px; font-weight: 600;
  letter-spacing: -0.6px; line-height: 1.1;
  color: #2d2a26; margin: 0 0 16px;
  max-width: 800px;
}
.ov-hero-title em {
  font-style: italic; color: #a0813e; font-weight: 500;
}
.ov-hero-sub {
  font-size: 14px; color: #4a4741;
  line-height: 1.7; max-width: 760px; margin: 0;
}

/* ── SETUP CHECKLIST STRIP ── */
.ov-setup {
  margin-top: 28px;
  padding: 22px 24px 20px;
  background: #ffffff;
  border: 1px solid #e8e4db;
  border-radius: 16px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}
.ov-setup-head {
  display: flex; align-items: center; gap: 16px;
  margin-bottom: 18px; flex-wrap: wrap;
}
.ov-setup-label {
  font-size: 10px; font-weight: 700;
  letter-spacing: 1.6px; text-transform: uppercase;
  color: #a0813e;
}
.ov-setup-progress {
  flex: 1; height: 3px;
  background: #e8e4db;
  border-radius: 99px; overflow: hidden;
  min-width: 180px; max-width: 280px;
}
.ov-setup-progress-fill {
  height: 100%;
  background: #a0813e;
  border-radius: 99px; transition: width 0.5s ease;
}
.ov-setup-counter {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 14px; font-weight: 600; color: #2d2a26;
}
.ov-setup-counter span { color: #7a756c; font-weight: 500; }

.ov-setup-grid {
  display: grid;
  grid-template-columns: repeat(var(--ov-tiles, 5), 1fr);
  gap: 12px;
}
.ov-setup-tile {
  display: flex; flex-direction: column; gap: 6px;
  padding: 14px 16px;
  background: #f8f5ee;
  border: 1px solid #e8e4db;
  border-radius: 12px;
  text-decoration: none; color: inherit;
  position: relative;
  transition: border-color var(--transition, 0.18s ease),
              background  var(--transition, 0.18s ease),
              transform   var(--transition, 0.18s ease);
}
.ov-setup-tile:hover {
  border-color: #c4a96a;
  background: #ffffff;
  transform: translateY(-1px);
}
.ov-setup-tile-row { display: flex; align-items: center; gap: 8px; }
.ov-setup-num {
  width: 24px; height: 24px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 11px; font-weight: 700;
  background: #e8e4db;
  color: #7a756c;
  flex-shrink: 0;
}
.ov-setup-tile.done .ov-setup-num {
  background: #2f7d54; color: #fff;
}
.ov-setup-tile-label {
  font-family: var(--font-sans, 'Inter', sans-serif);
  font-size: 13px; font-weight: 600; color: #2d2a26;
}
.ov-setup-tile-desc { font-size: 11px; color: #7a756c; }

/* ── NOTICE BAND ── */
.ov-notice {
  display: flex; align-items: center; gap: 16px;
  padding: 18px 22px;
  background: #f0e9d8;
  border: 1px solid #e0d9c9;
  border-left: 3px solid #a0813e;
  border-radius: 16px;
  margin-bottom: 28px;
}
.ov-notice-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 36px; height: 36px;
  border-radius: 50%; background: rgba(160,129,62,0.2);
  color: #a0813e; flex-shrink: 0;
}
.ov-notice-text {
  font-size: 14px; color: #4a4741;
  line-height: 1.6; flex: 1; min-width: 0;
}
.ov-notice-text strong { color: #2d2a26; }

/* ── TABS ── */
.ov-section { display: none; }
.ov-section.active { display: block; }

.tabs-segmented {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.tab-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 999px;
  border: 1px solid #e8e4db;
  background: #ffffff;
  font-size: 13px;
  font-weight: 600;
  color: #4a4741;
  cursor: pointer;
  transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.tab-pill:hover {
  border-color: #c4a96a;
  background: #faf6ec;
  color: #2d2a26;
}

.tab-pill.active {
  background: #a0813e;
  border-color: #a0813e;
  color: #ffffff;
}

@media (max-width: 640px) {
  .tabs-segmented {
    gap: 6px;
  }
  .tab-pill {
    padding: 8px 14px;
    font-size: 12px;
  }
}

/* ── KEY TERMS GRID ── */
.kt-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}
.kt-card {
  padding: 22px 26px;
  background: #ffffff;
  border: 1px solid #e8e4db;
  border-radius: 18px;
  position: relative;
  transition: border-color var(--transition, 0.18s ease),
              box-shadow var(--transition, 0.18s ease);
}
.kt-card:hover {
  border-color: #c4a96a;
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
.kt-term {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 17px; font-weight: 700;
  color: #a0813e;
  margin: 0 0 10px; letter-spacing: 0.1px;
}
.kt-def {
  font-size: 13px; color: #4a4741;
  line-height: 1.7; margin: 0;
}

/* ── WHY AEGIS GRID ── */
.wa-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
.wa-card {
  padding: 24px 24px 22px;
  background: #ffffff;
  border: 1px solid #e8e4db;
  border-radius: 14px;
  position: relative;
  transition: border-color var(--transition, 0.18s ease),
              transform var(--transition, 0.18s ease);
}
.wa-card:hover {
  border-color: #c4a96a;
  transform: translateY(-2px);
}
.wa-num {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 11px; font-weight: 700;
  letter-spacing: 1.4px; color: #a0813e;
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 14px;
}
.wa-num::after {
  content: ""; flex: 1; height: 1px;
  background: #e8e4db;
}
.wa-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 17px; font-weight: 700;
  color: #2d2a26;
  margin: 0 0 8px; line-height: 1.25; letter-spacing: -0.1px;
}
.wa-desc {
  font-size: 13px; color: #6b655d;
  line-height: 1.7; margin: 0;
}

/* ── HOW TO USE TIMELINE ── */
.hu-wrap {
  background: #ffffff;
  border: 1px solid #e8e4db;
  border-radius: 18px;
  padding: 8px 30px;
  position: relative;
}
.hu-steps { position: relative; padding: 8px 0 8px 14px; }
.hu-steps::before {
  content: ""; position: absolute;
  left: 33px; top: 32px; bottom: 32px;
  width: 2px; background: #e8e4db;
  border-radius: 1px;
}
.hu-step {
  display: flex; gap: 22px; align-items: flex-start;
  padding: 22px 0; position: relative;
  border-bottom: 1px solid #e8e4db;
}
.hu-step:last-child { border-bottom: none; }
.hu-num {
  width: 38px; height: 38px; border-radius: 50%;
  background: #ffffff;
  border: 2px solid #e8e4db;
  color: #6b655d;
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
  color: #2d2a26;
  margin: 2px 0 6px; letter-spacing: -0.1px;
}
.hu-step-desc {
  font-size: 13px; color: #4a4741;
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
  background: linear-gradient(135deg, #faf6ec, #ffffff);
  border: 1px solid #e8e4db;
  border-radius: 18px;
}
.faq-support-icon {
  width: 48px; height: 48px; border-radius: 50%;
  background: #a0813e; color: #fff;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(160,129,62,0.25);
}
.faq-support-text { flex: 1; }
.faq-support-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 18px; font-weight: 700;
  color: #2d2a26;
  margin-bottom: 2px; letter-spacing: -0.1px;
}
.faq-support-sub {
  font-size: 13px; color: #6b655d; line-height: 1.5;
}
.faq-support-cta {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 99px;
  background: #a0813e; color: #fff;
  font-size: 12.5px; font-weight: 700;
  text-decoration: none; flex-shrink: 0;
  transition: background var(--transition, 0.18s ease);
}
.faq-support-cta:hover { background: #7d6429; }

/* ── ACCORDION ── */
.accordion-item {
  background: #ffffff;
  border: 1px solid #e8e4db;
  border-radius: 14px;
  overflow: hidden;
}

.accordion-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  cursor: pointer;
  user-select: none;
  transition: background 0.18s ease;
  font-size: 14px;
  font-weight: 600;
  color: #2d2a26;
}

.accordion-trigger:hover {
  background: #faf6ec;
}

.accordion-caret {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #7a756c;
  transition: transform 0.25s ease;
}

.accordion-item.open .accordion-caret {
  transform: rotate(180deg);
}

.accordion-content {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease, padding 0.3s ease;
  padding: 0 20px;
  font-size: 13px;
  color: #4a4741;
  line-height: 1.6;
}

.accordion-item.open .accordion-content {
  max-height: 500px;
  padding: 0 20px 18px;
}

@media (max-width: 640px) {
  .accordion-trigger {
    padding: 16px 16px;
    font-size: 13px;
  }
  .accordion-item.open .accordion-content {
    padding: 0 16px 16px;
  }
}

/* ── RESPONSIVE ── */
@media (max-width: 1200px) {
  .wa-grid       { grid-template-columns: repeat(2, 1fr); }
  .ov-setup-grid { grid-template-columns: repeat(4, 1fr) !important; }
}

@media (max-width: 1024px) {
  .wa-grid       { grid-template-columns: repeat(2, 1fr); }
  .ov-setup-grid { grid-template-columns: repeat(3, 1fr) !important; }
  .ov-hero       { padding: 32px 32px 28px; }
}

@media (max-width: 820px) {
  .ov-hero            { padding: 28px 24px 24px; }
  .ov-hero-title      { font-size: 36px; }
  .kt-grid            { grid-template-columns: 1fr; }
  .wa-grid            { grid-template-columns: 1fr; }
  .faq-grid           { grid-template-columns: 1fr; }
  .ov-setup-grid      { grid-template-columns: repeat(2, 1fr) !important; }
  .ov-notice          { flex-direction: column; align-items: flex-start; gap: 12px; }
  .hu-wrap            { padding: 0 18px; }
  .hu-steps::before   { left: 25px; }
  .faq-support        { flex-direction: column; align-items: flex-start; }
}

@media (max-width: 640px) {
  .ov-hero            { padding: 24px 20px 20px; }
  .ov-hero-title      { font-size: 32px; }
  .ov-setup-grid      { grid-template-columns: repeat(2, 1fr) !important; }
  .ov-setup           { padding: 18px 16px 16px; }
  .ov-notice          { padding: 14px 16px; }
}

@media (max-width: 480px) {
  .ov-setup-grid { grid-template-columns: 1fr !important; }
  .ov-hero-title { font-size: 28px; }
  .ov-hero-eyebrow { font-size: 9px; }
  .ov-hero-sub { font-size: 13px; }
  .kt-card, .wa-card { padding: 18px 18px 16px; }
  .hu-wrap { padding: 0 14px; }
  .hu-step { padding: 18px 0; }
  .faq-support { padding: 18px 18px; }
}
</style>