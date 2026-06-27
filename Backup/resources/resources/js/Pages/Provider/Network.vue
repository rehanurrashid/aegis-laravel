<template>
  <AppLayout :user="user" portal="practitioner" activePage="network" pageTitle="Integrative Network">

    <!-- HERO BANNER -->
    <div class="nw-hero">
      <div class="nw-hero-left">
        <div class="nw-eyebrow">CONNECTIONS</div>
        <h1 class="nw-title">Network</h1>
        <p class="nw-sub">Cultivate an interdisciplinary care network, secure essential business resources, create a shadow network as a backup, and send secure referrals to support care access.</p>
      </div>
      <div class="nw-hero-actions">
        <button class="btn btn-outline btn-sm nw-btn-icon" @click="showToast('Opening activity','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Activity
        </button>
        <button class="btn btn-primary btn-sm nw-btn-icon" @click="showToast('Inviting provider','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Invite Provider
        </button>
      </div>
    </div>

    <!-- CONNECTION REQUESTS BANNER -->
    <div class="nw-requests-banner">
      <div class="nw-requests-header">
        <div>
          <div class="nw-requests-label">PENDING · {{ pendingRequests.length }} AWAITING</div>
          <div class="nw-requests-title">Connection Requests</div>
          <div class="nw-requests-sub">{{ clinicalCount }} clinical providers and {{ businessCount }} business contacts</div>
        </div>
        <button class="btn btn-outline btn-sm nw-btn-icon" @click="reviewModalOpen = true">Review all <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
      </div>
      <div class="nw-request-cards">
        <div v-for="req in pendingRequests" :key="req.id" class="nw-request-card">
          <div class="nw-req-top">
            <span class="nw-req-type-badge" :class="req.type === 'Clinical' ? 'nw-badge-green' : 'nw-badge-blue'">{{ req.type }}</span>
            <span class="nw-req-date">{{ req.date }}</span>
          </div>
          <div class="nw-req-profile">
            <div class="nw-req-avatar" :style="{ background: req.avatarColor }">{{ req.initials }}</div>
            <div>
              <div class="nw-req-name">{{ req.name }}</div>
              <div class="nw-req-role">{{ req.role }}</div>
              <div class="nw-req-loc">{{ req.location }}</div>
            </div>
          </div>
          <div class="nw-req-actions">
            <button class="btn btn-primary btn-sm" @click="acceptRequest(req)">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
              Accept
            </button>
            <button class="btn btn-ghost btn-sm" @click="declineRequest(req)">Decline</button>
          </div>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="nw-tabs">
      <button v-for="tab in tabs" :key="tab.id" class="nw-tab" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="tab.icon"></svg>
        {{ tab.label }}
      </button>
    </div>

    <!-- INTEGRATIVE CARE NETWORK TAB -->
    <div v-show="activeTab === 'integrative'">
      <!-- Sub-tabs -->
      <div class="nw-subtabs">
        <button class="nw-subtab" :class="{ active: subTab === 'search' }" @click="subTab = 'search'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Search Providers
        </button>
        <button class="nw-subtab" :class="{ active: subTab === 'mynetwork' }" @click="subTab = 'mynetwork'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          My Network
        </button>
      </div>

      <!-- Recommended Partners -->
      <div class="nw-section-header">
        <div class="nw-section-title">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Recommended Network Partners
        </div>
        <span class="nw-ai-badge">AI SUGGESTED <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
      </div>
      <p class="nw-section-sub">Based on your referral history and the specialties your clients ask for most</p>

      <div class="nw-rec-scroll">
        <div v-for="cat in recommendedCategories" :key="cat.label" class="nw-rec-card">
          <div class="nw-rec-icon" :style="{ color: cat.color }">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="cat.icon"></svg>
          </div>
          <div class="nw-rec-label">{{ cat.label }}</div>
          <div class="nw-rec-desc">{{ cat.desc }}</div>
          <div class="nw-rec-count">{{ cat.count }} nearby</div>
          <button class="nw-rec-arrow" @click="showToast('Searching ' + cat.label,'info')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>

      <!-- Shadow Providers -->
      <div class="nw-section-header" style="margin-top:28px;">
        <div class="nw-section-title">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
          Recommended Shadow Providers
        </div>
        <span class="nw-ai-badge nw-ai-badge-match">AI MATCHING <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/></svg></span>
      </div>
      <p class="nw-section-sub">A curated referral resource that helps identify providers who closely mirror your background and practice profile — Including region, specialty, demographics, services, and payment type.</p>

      <div class="nw-shadow-grid">
        <div v-for="p in shadowProviders" :key="p.name" class="nw-provider-card">
          <div class="nw-pcard-top">
            <div class="nw-pcard-match">{{ p.match }}%</div>
            <div class="nw-pcard-rating">★ {{ p.rating }}</div>
            <button v-if="p.added" class="nw-added-btn" disabled>✓ Added</button>
          </div>
          <div class="nw-pcard-avatar" :style="{ background: p.avatarColor }">{{ p.initials }}</div>
          <div class="nw-pcard-name">{{ p.name }}</div>
          <div class="nw-pcard-role">{{ p.role }}</div>
          <div class="nw-pcard-loc">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            {{ p.location }}
          </div>
          <div class="nw-pcard-tags">
            <span v-for="tag in p.tags" :key="tag" class="nw-pcard-tag">{{ tag }}</span>
          </div>
          <div class="nw-pcard-actions">
            <button class="nw-pcard-icon-btn" title="Message" @click="showToast('Opening message','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
            <button class="nw-pcard-icon-btn" title="View profile" @click="showToast('Viewing profile','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button v-if="!p.added" class="btn btn-primary btn-sm nw-connect-btn" @click="connectProvider(p)">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Connect
            </button>
          </div>
        </div>
      </div>

      <!-- Search Results -->
      <div class="nw-search-results-header">SEARCH RESULTS</div>
      <div class="nw-search-layout">
        <!-- Filters sidebar -->
        <div class="nw-filters-sidebar">
          <div class="nw-filters-top">
            <span class="nw-filters-title">FILTERS</span>
            <button class="nw-clear-btn" @click="showToast('Filters cleared','info')">CLEAR ALL</button>
          </div>
          <div v-for="f in filterGroups" :key="f.label" class="nw-filter-group">
            <button class="nw-filter-group-btn" @click="f.open = !f.open">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="f.icon"></svg>
              {{ f.label }}
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-left:auto" :style="{ transform: f.open ? 'rotate(180deg)' : '' }"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
          </div>
          <button class="nw-apply-btn" @click="showToast('Filters applied','success')">Apply Filters</button>
        </div>

        <!-- Results grid -->
        <div class="nw-results">
          <div class="nw-results-header-row">
            <span class="nw-results-count">{{ searchResults.length }} providers found in your region</span>
            <div class="nw-results-sort">
              <span class="nw-sort-label">Sort:</span>
              <select class="nw-sort-select"><option>Best Match</option><option>Highest Rated</option><option>Closest</option></select>
            </div>
          </div>
          <div class="nw-results-grid">
            <div v-for="p in searchResults" :key="p.name" class="nw-provider-card nw-result-card">
              <div class="nw-pcard-top">
                <div class="nw-pcard-avatar" :style="{ background: p.avatarColor }">{{ p.initials }}</div>
                <div class="nw-pcard-rating">★ {{ p.rating }}</div>
              </div>
              <div class="nw-pcard-name">{{ p.name }}</div>
              <div class="nw-pcard-role">{{ p.role }}</div>
              <div class="nw-pcard-loc">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ p.location }}
              </div>
              <div class="nw-pcard-tags">
                <span v-for="tag in p.tags.slice(0,3)" :key="tag" class="nw-pcard-tag">{{ tag }}</span>
                <span v-if="p.tags.length > 3" class="nw-pcard-tag">+{{ p.tags.length - 3 }}</span>
              </div>
              <div class="nw-result-stats">{{ p.refs }} refs · {{ p.acc }} acc · {{ p.resp }} resp</div>
              <div class="nw-pcard-actions">
                <button class="nw-pcard-icon-btn" title="Message" @click="showToast('Opening message','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
                <button class="nw-pcard-icon-btn" title="Refer" @click="showToast('Opening referral','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg></button>
                <button class="nw-pcard-icon-btn" title="Save" @click="showToast('Saved','success')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button>
                <button class="nw-pcard-icon-btn" title="View" @click="showToast('Viewing profile','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              </div>
            </div>
          </div>
          <div style="display:flex;justify-content:center;margin-top:20px;">
            <button class="btn btn-outline btn-sm" @click="showToast('Loading more results','info')">Load More Results</button>
          </div>
        </div>
      </div>
    </div>

    <!-- BUSINESS PARTNERS TAB -->
    <div v-show="activeTab === 'business'">

      <!-- Sub-tabs -->
      <div class="nw-subtabs" style="margin-bottom:16px;">
        <button class="nw-subtab" :class="{ active: bpSubTab === 'search' }" @click="bpSubTab = 'search'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Search Partners
        </button>
        <button class="nw-subtab" :class="{ active: bpSubTab === 'mypartners' }" @click="bpSubTab = 'mypartners'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          My Partners
        </button>
      </div>

      <!-- Search + sort bar -->
      <div class="bp-search-bar">
        <div class="bp-search-wrap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-4);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input class="bp-search-input" v-model="bpSearch" placeholder="Search by name, skill, company, keyword..." />
        </div>
        <div class="bp-sort-wrap">
          <span class="bp-sort-label">Sort:</span>
          <select class="nw-sort-select" v-model="bpSort">
            <option>Best Match</option>
            <option>Highest Rated</option>
            <option>Most Jobs</option>
            <option>Lowest Rate</option>
          </select>
        </div>
      </div>
      <div class="bp-count">Showing {{ filteredPartners.length }} of {{ businessPartners.length }} partners</div>

      <div class="bp-layout">
        <!-- Filters sidebar -->
        <div class="nw-filters-sidebar bp-sidebar">
          <div class="nw-filters-top">
            <span class="nw-filters-title">FILTERS</span>
            <button class="nw-clear-btn" @click="showToast('Filters cleared','info')">CLEAR ALL</button>
          </div>
          <!-- Clinical-service toggle -->
          <div class="bp-filter-toggle-row">
            <span class="bp-filter-toggle-label">Clinical-service providers</span>
            <label class="bp-toggle">
              <input type="checkbox" v-model="bpClinicalOnly">
              <span class="bp-toggle-track"><span class="bp-toggle-thumb"></span></span>
            </label>
          </div>
          <!-- Category -->
          <div class="bp-filter-section">
            <div class="bp-filter-section-header" @click="bpFilters.category = !bpFilters.category">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10"/></svg>
              <span>Category</span>
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-left:auto" :style="{ transform: bpFilters.category ? 'rotate(180deg)' : '' }"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div v-if="bpFilters.category" style="margin-top:8px;">
              <select class="nw-sort-select" style="width:100%;" v-model="bpCategory">
                <option value="">All Categories</option>
                <option>Medical Billing</option>
                <option>Digital Marketing</option>
                <option>Credentialing</option>
                <option>Practice Consulting</option>
                <option>Accounting / CPA</option>
                <option>Admin / VA</option>
                <option>HR / Staffing</option>
                <option>Legal / Attorney</option>
                <option>IT / Software</option>
                <option>Design / Branding</option>
              </select>
            </div>
          </div>
          <div v-for="f in bpFilterGroups" :key="f.label" class="nw-filter-group">
            <button class="nw-filter-group-btn">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="f.icon"></svg>
              {{ f.label }}
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-left:auto"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
          </div>
          <button class="nw-apply-btn" @click="showToast('Filters applied','success')">Apply Filters</button>
        </div>

        <!-- Partner cards grid -->
        <div class="bp-results">
          <div class="bp-grid">
            <div v-for="p in filteredPartners" :key="p.name" class="bp-card">
              <div class="bp-card-top">
                <span class="bp-type-badge">{{ p.partnerType }}</span>
                <span class="bp-card-rating">★ {{ p.rating }}</span>
              </div>
              <div class="bp-card-avatar-row">
                <div class="bp-avatar" :style="{ background: p.avatarColor }">{{ p.initials }}</div>
              </div>
              <div class="bp-card-name">{{ p.name }}</div>
              <div class="bp-card-role">{{ p.role }}</div>
              <div class="bp-card-loc">{{ p.location }}</div>
              <div class="bp-card-tags">
                <span v-for="tag in p.tags.slice(0,3)" :key="tag" class="nw-pcard-tag">{{ tag }}</span>
                <span v-if="p.tags.length > 3" class="nw-pcard-tag">+{{ p.tags.length - 3 }}</span>
              </div>
              <div class="bp-card-stats">{{ p.rate }} · {{ p.reviews }} reviews · {{ p.jobs }} jobs</div>
              <div class="bp-card-actions">
                <button class="nw-pcard-icon-btn" title="Message" @click="showToast('Opening message to ' + p.name,'info')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </button>
                <button class="nw-pcard-icon-btn" title="Hire" @click="showToast('Opening hire flow for ' + p.name,'info')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </button>
                <button class="nw-pcard-icon-btn" title="Save" @click="showToast(p.name + ' saved','success')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                </button>
                <button class="nw-pcard-icon-btn" title="View profile" @click="showToast('Viewing profile of ' + p.name,'info')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>
          </div>
          <div style="display:flex;justify-content:center;margin-top:22px;">
            <button class="btn btn-outline btn-sm" @click="showToast('Loading more partners','info')">Load More Partners</button>
          </div>
        </div>
      </div>
    </div>

    <!-- REFERRALS & TOOLS TAB -->
    <div v-show="activeTab === 'referrals'">

      <!-- Sub-tabs -->
      <div class="nw-subtabs" style="margin-bottom:18px;">
        <button class="nw-subtab" :class="{ active: rtSubTab === 'list' }" @click="rtSubTab = 'list'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
          Referral List
        </button>
        <button class="nw-subtab" :class="{ active: rtSubTab === 'shadows' }" @click="rtSubTab = 'shadows'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          My Shadows
          <span class="rt-count-pill">0</span>
        </button>
        <button class="nw-subtab" :class="{ active: rtSubTab === 'config' }" @click="rtSubTab = 'config'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Configuration
        </button>
      </div>

      <!-- Shadow Network section title -->
      <div class="rt-section-title">Shadow Network</div>

      <!-- AI Insight banner -->
      <div class="rt-ai-banner">
        <div class="rt-ai-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
          <div class="rt-ai-title">This week's AI insights</div>
          <div class="rt-ai-sub">Based on your recent clients, you may benefit from <strong>3 additional PTSD specialists</strong> and <strong>2 child &amp; adolescent therapists</strong>. Aegis found <strong>8 high-match candidates</strong> below — sorted by compatibility score.</div>
        </div>
      </div>

      <!-- Search bar -->
      <div class="bp-search-wrap" style="margin-bottom:10px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-4);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input class="bp-search-input" v-model="rtSearch" placeholder="Search by name, specialty, location..." />
      </div>

      <div class="rt-showing">Showing {{ filteredShadows.length }} AI suggestions</div>

      <!-- Shadow cards grid -->
      <div class="rt-grid">
        <div v-for="s in filteredShadows" :key="s.name" class="rt-card">
          <div class="rt-card-shadow-icon">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <div class="rt-card-avatar" :style="{ background: s.avatarColor }">{{ s.initials }}</div>
          <div class="rt-card-name">{{ s.name }}</div>
          <div class="rt-card-role">{{ s.role }}</div>
          <div class="rt-card-loc">{{ s.location }}</div>
          <div class="rt-card-tags">
            <span v-for="tag in s.tags" :key="tag" class="nw-pcard-tag">{{ tag }}</span>
          </div>
          <div class="rt-card-actions">
            <button class="nw-pcard-icon-btn" title="Message" @click="showToast('Opening message to ' + s.name,'info')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button class="nw-pcard-icon-btn" title="Add to shadows" @click="addToShadows(s)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="21" y1="11" x2="15" y2="11"/><line x1="18" y1="8" x2="18" y2="14"/></svg>
            </button>
            <button class="nw-pcard-icon-btn" title="View profile" @click="showToast('Viewing profile of ' + s.name,'info')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="nw-pcard-icon-btn rt-card-remove" title="Remove" @click="removeShadow(s)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:center;margin-top:22px;">
        <button class="btn btn-outline btn-sm nw-btn-icon" @click="showToast('Loading more shadows','info')">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
          Load More Shadows
        </button>
      </div>
    </div>

    <!-- Teleport: Review modal + toasts -->
    <Teleport to="body">

      <!-- PENDING CONNECTION REQUESTS MODAL -->
      <Transition name="nw-modal-fade">
        <div v-if="reviewModalOpen" class="nw-modal-backdrop" @click.self="reviewModalOpen = false">
          <div class="nw-modal">
            <!-- Header -->
            <div class="nw-modal-header">
              <div>
                <div class="nw-modal-title">Pending Connection Requests</div>
                <div class="nw-modal-sub">{{ allPendingRequests.length }} awaiting your review · {{ allPendingRequests.filter(r=>r.type==='Clinical').length }} clinical, {{ allPendingRequests.filter(r=>r.type==='Business').length }} business</div>
              </div>
              <button class="nw-modal-close-btn" @click="reviewModalOpen = false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <!-- Info notice -->
            <div class="nw-modal-notice">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span><strong>Accepting</strong> adds the connection to your network and notifies the requester. <strong>Declining</strong> removes the request — the other party is not notified.</span>
            </div>
            <!-- Request list -->
            <div class="nw-modal-list">
              <div v-for="req in allPendingRequests" :key="req.id" class="nw-modal-row">
                <div class="nw-modal-row-avatar" :style="{ background: req.avatarColor }">{{ req.initials }}</div>
                <div class="nw-modal-row-info">
                  <div class="nw-modal-row-name">{{ req.name }}</div>
                  <div class="nw-modal-row-role">{{ req.role }} · {{ req.location }}</div>
                  <div class="nw-modal-row-quote">"{{ req.quote }}"</div>
                </div>
                <div class="nw-modal-row-right">
                  <span class="nw-req-type-badge" :class="req.type === 'Clinical' ? 'nw-badge-green' : 'nw-badge-blue'">{{ req.type }}</span>
                  <span class="nw-req-date">{{ req.date }}</span>
                </div>
                <div class="nw-modal-row-btns">
                  <button class="btn btn-primary btn-sm nw-btn-icon" @click="acceptModalRequest(req)">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Accept
                  </button>
                  <button class="btn btn-outline btn-sm" @click="declineModalRequest(req)">Decline</button>
                  <button class="nw-pcard-icon-btn" title="View profile" @click="showToast('Viewing profile','info')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>
              <div v-if="allPendingRequests.length === 0" class="nw-modal-empty">All requests have been reviewed.</div>
            </div>
            <!-- Footer -->
            <div class="nw-modal-footer">
              <button class="btn btn-outline btn-sm" @click="reviewModalOpen = false">Close</button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

// Toast
const toasts = ref([]);
let toastId = 0;
function showToast(msg, type = 'info') {
  const id = ++toastId;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

const reviewModalOpen = ref(false);

const allPendingRequests = ref([
  { id:1, type:'Clinical', date:'Feb 27', initials:'AP', name:'Dr. Aisha Patel, PsyD',        role:'Psychologist',            location:'Queens, NY',    avatarColor:'#a0813e', quote:"I frequently see patients who could benefit from trauma-focused therapy." },
  { id:2, type:'Clinical', date:'Feb 24', initials:'MW', name:'Dr. Marcus Webb, LCSW',         role:'Therapist / Counselor',   location:'New York, NY',  avatarColor:'#6a4c8c', quote:"Would love to collaborate on shared clients dealing with dual diagnoses." },
  { id:3, type:'Business', date:'Feb 17', initials:'TM', name:'TriState Medical Billing',       role:'Medical Billing Company', location:'Newark, NJ',    avatarColor:'#4a7a6a', quote:"We specialize in mental health billing and OON claims for NY/NJ practices." },
  { id:4, type:'Business', date:'Feb 5',  initials:'BP', name:'Bright Practice Marketing',      role:'Healthcare Marketing Agency', location:'Brooklyn, NY', avatarColor:'#7a5c4a', quote:"We've helped 50+ therapy practices grow their caseload. Happy to chat." },
]);

function acceptModalRequest(req) {
  allPendingRequests.value = allPendingRequests.value.filter(r => r.id !== req.id);
  pendingRequests.value    = pendingRequests.value.filter(r => r.id !== req.id);
  showToast(req.name + ' accepted', 'success');
}
function declineModalRequest(req) {
  allPendingRequests.value = allPendingRequests.value.filter(r => r.id !== req.id);
  pendingRequests.value    = pendingRequests.value.filter(r => r.id !== req.id);
  showToast(req.name + ' declined', 'info');
}

const activeTab = ref('integrative');
const subTab    = ref('search');

const tabs = [
  { id: 'integrative', label: 'Integrative Care Network', icon: '<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13l-6 4M12 13l6 4"/>' },
  { id: 'business',    label: 'Business Partners',        icon: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>' },
  { id: 'referrals',   label: 'Referrals & Tools',        icon: '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/>' },
];

// Connection requests
const pendingRequests = ref([
  { id:1, type:'Clinical', date:'Feb 27', initials:'AP', name:'Dr. Aisha Patel, PsyD', role:'Psychologist', location:'Queens, NY', avatarColor:'#a0813e' },
  { id:2, type:'Clinical', date:'Feb 24', initials:'MW', name:'Dr. Marcus Webb, LCSW', role:'Therapist / Counselor', location:'New York...', avatarColor:'#6a4c8c' },
  { id:3, type:'Business', date:'Feb 17', initials:'TM', name:'TriState Medical Billing', role:'Medical Billing Company', location:'Newark...', avatarColor:'#4a7a6a' },
]);
const clinicalCount = computed(() => pendingRequests.value.filter(r => r.type === 'Clinical').length);
const businessCount = computed(() => pendingRequests.value.filter(r => r.type === 'Business').length);

function acceptRequest(req) {
  pendingRequests.value = pendingRequests.value.filter(r => r.id !== req.id);
  showToast(req.name + ' accepted', 'success');
}
function declineRequest(req) {
  pendingRequests.value = pendingRequests.value.filter(r => r.id !== req.id);
  showToast(req.name + ' declined', 'info');
}

// Recommended categories
const recommendedCategories = [
  { label:'Psychiatrist',         desc:'Medication management',         count:3, color:'var(--green-dark)', icon:'<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>' },
  { label:'Therapist / LCSW',     desc:'Ongoing psychotherapy',         count:6, color:'var(--gold-dark)',  icon:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
  { label:'Neurologist',          desc:'Neuropsychiatric care',          count:2, color:'var(--blue-dark)',  icon:'<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/>' },
  { label:'Primary Care',         desc:'Care coordination',             count:4, color:'var(--teal-dark)',  icon:'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>' },
  { label:'Dietician',            desc:'Eating & metabolism',           count:3, color:'var(--orange-dark)',icon:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
  { label:'Medical Billing',      desc:'Revenue cycle',                 count:6, color:'var(--purple-dark)',icon:'<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>' },
  { label:'Credentialing',        desc:'Insurance & licensing',         count:2, color:'var(--gold-dark)',  icon:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' },
];

// Shadow providers
const shadowProviders = ref([
  { name:'Dr. Rachel Moore, MD',  initials:'RM', avatarColor:'#a0813e', role:'Psychiatrist',           location:'New York, NY', tags:['Anxiety','Mood Disorders','PTSD'],                match:96, rating:4.3, added:true  },
  { name:'Sarah Nguyen, PsyD',    initials:'SN', avatarColor:'#6a4c8c', role:'Psychologist',           location:'Brooklyn, NY',  tags:['CBT','Trauma','Depression'],                     match:93, rating:4.7, added:false },
  { name:'Maya Torres, LCSW',     initials:'MY', avatarColor:'#4a7a6a', role:'Licensed Clinical Social Worker', location:'Queens, NY', tags:['DBT','Anxiety','LGBTQ+'],                match:88, rating:4.8, added:false },
  { name:'James Okafor, LMFT',    initials:'JO', avatarColor:'#7a5c4a', role:'Marriage & Family Therapist',    location:'Bronx, NY',   tags:['Couples','Family Conflict','IFS'],        match:90, rating:4.6, added:false },
  { name:'Alicia Reeves, LPC',    initials:'AR', avatarColor:'#4a6c8c', role:'Licensed Professional Counselor',location:'New York, NY', tags:['ACT','Stress','Life Transitions'],        match:84, rating:4.8, added:false },
  { name:'Nina Park, RDN',        initials:'NP', avatarColor:'#8c5a4a', role:'Registered Dietician',   location:'Manhattan, NY', tags:['Eating Disorders','Functional Nutrition','HAES'],match:92, rating:4.6, added:false },
]);

function connectProvider(p) {
  p.added = true;
  showToast('Connection request sent to ' + p.name, 'success');
}

// Business Partners
const bpSubTab       = ref('search');
const bpSearch       = ref('');
const bpSort         = ref('Best Match');
const bpClinicalOnly = ref(false);
const bpCategory     = ref('');
const bpFilters      = reactive({ category: true });

const bpFilterGroups = [
  { label:'Partner Type',     icon:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
  { label:'Hourly Rate',      icon:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
  { label:'Experience Level', icon:'<polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/>' },
  { label:'Minimum Job Success', icon:'<polyline points="20 6 9 17 4 12"/>' },
  { label:'Availability',     icon:'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>' },
  { label:'Engagement Type',  icon:'<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>' },
  { label:'Work Location',    icon:'<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>' },
  { label:'Quality Badges',   icon:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' },
  { label:'Jobs Completed',   icon:'<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>' },
  { label:'Language',         icon:'<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/>' },
  { label:'Member Since',     icon:'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
];

const businessPartners = ref([
  { name:'Marisol Vega',           initials:'MV', avatarColor:'#a0813e', partnerType:'FREELANCER',   role:'Medical Billing',      location:'Miami, FL · Remote',        tags:['Medical Billing','AR Management','Denial Mgmt'],             rate:'$85/hr',  reviews:147, jobs:92,  rating:4.9, category:'Medical Billing'      },
  { name:'Riya Patel',             initials:'RP', avatarColor:'#6a4c8c', partnerType:'SOLOPRENEUR',  role:'Digital Marketing',    location:'Austin, TX · Remote',       tags:['SEO','Google Ads','Social Media'],                           rate:'$70/hr',  reviews:204, jobs:78,  rating:4.8, category:'Digital Marketing'    },
  { name:'Kevin Osei',             initials:'KO', avatarColor:'#4a7a6a', partnerType:'CONSULTANT',   role:'Credentialing',        location:'Atlanta, GA · Remote',      tags:['CAQH','PECOS','Re-credentialing'],                           rate:'$55/hr',  reviews:310, jobs:130, rating:4.9, category:'Credentialing'        },
  { name:'Jae Won Park',           initials:'JP', avatarColor:'#7a5c4a', partnerType:'CONSULTANT',   role:'Practice Consulting',  location:'Boston, MA · Remote',       tags:['Practice Management','Revenue Optimization','Operations'],    rate:'$220/hr', reviews:72,  jobs:48,  rating:4.9, category:'Practice Consulting'  },
  { name:'Apex Billing Co.',       initials:'AB', avatarColor:'#a0813e', partnerType:'AGENCY',       role:'Medical Billing',      location:'Chicago, IL · Remote',      tags:['Revenue Cycle','Credentialing','Claims Processing'],          rate:'$150/hr', reviews:89,  jobs:204, rating:4.7, category:'Medical Billing'      },
  { name:'Daniel Torres, CPA',     initials:'DT', avatarColor:'#4a6c8c', partnerType:'FREELANCER',   role:'Accounting / CPA',     location:'New York, NY · Remote',     tags:['Tax Planning','GAAP','Practice Valuation'],                  rate:'$175/hr', reviews:83,  jobs:41,  rating:4.8, category:'Accounting / CPA'     },
  { name:'Bright Minds Admin',     initials:'BA', avatarColor:'#6a4c8c', partnerType:'AGENCY',       role:'Admin / VA',           location:'Phoenix, AZ · Remote',      tags:['Virtual Assistants','Scheduling','Data Entry'],              rate:'$35/hr',  reviews:175, jobs:295, rating:4.6, category:'Admin / VA'           },
  { name:'StaffLink HR Group',     initials:'SH', avatarColor:'#4a7a6a', partnerType:'FIRM',         role:'HR / Staffing',        location:'Dallas, TX',                tags:['Recruitment','Benefits Admin','Compliance'],                 rate:'$120/hr', reviews:44,  jobs:82,  rating:4.8, category:'HR / Staffing'        },
  { name:'Sandra Kim',             initials:'SK', avatarColor:'#8c5a4a', partnerType:'FREELANCER',   role:'Accounting / CPA',     location:'Los Angeles, CA · Remote',  tags:['Bookkeeping','QuickBooks','Tax Prep'],                       rate:'$145/hr', reviews:58,  jobs:39,  rating:4.7, category:'Accounting / CPA'     },
  { name:'ClearPath Legal Group',  initials:'CL', avatarColor:'#4a6c8c', partnerType:'FIRM',         role:'Legal / Attorney',     location:'Houston, TX',               tags:['Healthcare Law','HIPAA Counsel','Employment Law'],            rate:'$275/hr', reviews:38,  jobs:110, rating:4.6, category:'Legal / Attorney'     },
  { name:'CloudMed IT Solutions',  initials:'CM', avatarColor:'#7a5c4a', partnerType:'AGENCY',       role:'IT / Software',        location:'San Francisco, CA · Remote',tags:['HIPAA IT','Network Security','Cloud Migration'],             rate:'$200/hr', reviews:52,  jobs:65,  rating:4.5, category:'IT / Software'        },
  { name:'Lena Hoffmann',          initials:'LH', avatarColor:'#a0813e', partnerType:'FREELANCER',   role:'Design / Branding',    location:'Seattle, WA · Remote',      tags:['Brand Identity','Web Design','Logo Design'],                 rate:'$90/hr',  reviews:88,  jobs:56,  rating:4.7, category:'Design / Branding'    },
]);

const filteredPartners = computed(() => {
  return businessPartners.value.filter(p => {
    const q = bpSearch.value.toLowerCase();
    if (q && !p.name.toLowerCase().includes(q) && !p.role.toLowerCase().includes(q) && !p.tags.some(t => t.toLowerCase().includes(q))) return false;
    if (bpCategory.value && p.category !== bpCategory.value) return false;
    return true;
  });
});

// Referrals & Tools — Shadow Network
const rtSubTab = ref('list');
const rtSearch = ref('');

const shadowCandidates = ref([
  { name:'Alicia Reeves, LPC',     initials:'AR', avatarColor:'#a0813e', role:'Licensed Professional Counselor', location:'New York, NY', tags:['ACT','Stress','Life Transitions'] },
  { name:'Carol Huang, CDE',       initials:'CH', avatarColor:'#4a7a6a', role:'Certified Diabetes Educator',     location:'Manhattan, NY', tags:['Diabetes','Blood Sugar','Lifestyle Medicine'] },
  { name:'Danielle Fox, PMHNP',    initials:'DF', avatarColor:'#6a4c8c', role:'Psychiatric Nurse Practitioner',  location:'New York, NY', tags:['Medication Mgmt','ADHD','Depression'] },
  { name:'Devon Hall, CADC',       initials:'DH', avatarColor:'#7a5c4a', role:'Certified Addiction Counselor',   location:'Harlem, NY',   tags:['Substance Use','Relapse Prevention','MI'] },
  { name:'Dr. Aisha Patel, PsyD',  initials:'AP', avatarColor:'#a0813e', role:'Psychologist',                   location:'Queens, NY',   tags:['Family Therapy','Cultural Competence'] },
  { name:'Dr. Amara Osei, LCSW',   initials:'AO', avatarColor:'#4a6c8c', role:'Clinical Social Worker',         location:'Brooklyn, NY', tags:['Trauma','BIPOC Care','CBT'] },
  { name:'Dr. Diana Vasquez, PhD', initials:'DV', avatarColor:'#4a7a6a', role:'Clinical Psychologist',          location:'Houston, TX',  tags:['Trauma','DBT','EMDR'] },
  { name:'Dr. Elena Rodriguez, RD',initials:'ER', avatarColor:'#8c5a4a', role:'Registered Dietitian',           location:'Manhattan, NY',tags:['Eating Disorders','Nutrition','Mental Health'] },
  { name:'Dr. Hana Yoon, MD',      initials:'HY', avatarColor:'#6a4c8c', role:'Psychiatrist',                   location:'New York, NY', tags:['Mood disorders','adolescent psychiatry'] },
  { name:'Dr. Hannah Brooks, LCSW',initials:'HB', avatarColor:'#a0813e', role:'Clinical Social Worker',         location:'Brooklyn, NY', tags:['Family therapy','trauma-informed care'] },
  { name:'Dr. James Torres, MD',   initials:'JT', avatarColor:'#4a7a6a', role:'Geriatric Psychiatrist',         location:'New York, NY', tags:['Geriatric Psychiatry','Memory Disorders'] },
  { name:'Dr. Keisha Brooks, ND',  initials:'KB', avatarColor:'#7a5c4a', role:'Naturopathic Doctor',            location:'Jersey City, NJ',tags:['Hormone Health','Gut Health','Root-Cause Medicine'] },
]);

const filteredShadows = computed(() => {
  const q = rtSearch.value.toLowerCase();
  if (!q) return shadowCandidates.value;
  return shadowCandidates.value.filter(s =>
    s.name.toLowerCase().includes(q) ||
    s.role.toLowerCase().includes(q) ||
    s.location.toLowerCase().includes(q) ||
    s.tags.some(t => t.toLowerCase().includes(q))
  );
});

function addToShadows(s) {
  showToast(s.name + ' added to My Shadows', 'success');
}
function removeShadow(s) {
  shadowCandidates.value = shadowCandidates.value.filter(c => c.name !== s.name);
  showToast(s.name + ' removed', 'info');
}

// Filter groups
const filterGroups = reactive([
  { label:'Provider Type',          open:false, icon:'<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>' },
  { label:'Specialties',            open:false, icon:'<polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/>' },
  { label:'Treatment Approaches',   open:false, icon:'<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>' },
  { label:'Insurance Accepted',     open:false, icon:'<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>' },
  { label:'Format & Services',      open:false, icon:'<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>' },
  { label:'Location',               open:false, icon:'<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>' },
  { label:'Credentials',            open:false, icon:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' },
  { label:'Rate & Availability',    open:false, icon:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
  { label:'Provider Demographics',  open:false, icon:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
]);

// Search results
const searchResults = ref([
  { name:'Dr. Daniel Malik, MD',  initials:'DM', avatarColor:'#a0813e', role:'Psychiatrist',  location:'NYC, NY',       tags:['Anxiety','PTSD','Mood Disorders'], rating:4.9, refs:'14 refs', acc:'80% acc', resp:'3.1h resp' },
  { name:'Dr. Lisa Chen, PhD',    initials:'LC', avatarColor:'#6a4c8c', role:'Psychologist',  location:'Brooklyn, NY',  tags:['CBT','Depression'],               rating:4.7, refs:'8 refs',  acc:'88% acc', resp:'2.0h resp' },
  { name:'Dr. Marcus Webb, LCSW', initials:'MW', avatarColor:'#4a7a6a', role:'Therapist / Counselor', location:'Queens, NY', tags:['DBT','Family Therapy','Addiction'], rating:4.8, refs:'0 refs', acc:'—',       resp:'2.1h resp' },
  { name:'Dr. Aisha Patel, PsyD', initials:'AP', avatarColor:'#7a5c4a', role:'Psychologist',  location:'Manhattan, NY', tags:['Child & Adolescent','ADHD','Autism'], rating:4.8, refs:'0 refs', acc:'—',       resp:'6.5h resp' },
  { name:'Dr. James Torres, MD',  initials:'JT', avatarColor:'#4a6c8c', role:'Psychiatrist',  location:'Newark, NJ',    tags:['Geriatric Psych','Dementia','Medication Mgmt'], rating:4.5, refs:'5 refs', acc:'80% acc', resp:'5.1h resp' },
  { name:'Dr. Sofia Kim, MD',     initials:'SK', avatarColor:'#8c5a4a', role:'Neurologist',   location:'Bronx, NY',     tags:['Epilepsy','Headaches','Neurocognitive'], rating:4.8, refs:'0 refs', acc:'—',       resp:'2.8h resp' },
]);
</script>

<style scoped>
/* Hero */
.nw-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:22px 26px; margin-bottom:14px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.nw-eyebrow { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:5px; }
.nw-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin:0 0 6px; }
.nw-sub { font-family:var(--font-sans); font-size:12.5px; color:var(--text-3); margin:0; line-height:1.5; max-width:600px; }
.nw-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; }
.nw-btn-icon { display:inline-flex; align-items:center; gap:6px; }

/* Requests banner */
.nw-requests-banner { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:18px 20px; margin-bottom:14px; box-shadow:var(--shadow-xs); }
.nw-requests-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px; }
.nw-requests-label { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:3px; }
.nw-requests-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); margin-bottom:2px; }
.nw-requests-sub { font-size:12px; color:var(--text-3); }
.nw-request-cards { display:flex; gap:12px; flex-wrap:wrap; }
.nw-request-card { background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:14px 16px; min-width:200px; flex:1; max-width:260px; }
.nw-req-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.nw-req-type-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; }
.nw-badge-green { background:var(--green-light); color:var(--green-dark); border:1px solid var(--soft-green); }
.nw-badge-blue  { background:var(--blue-light);  color:var(--blue-dark);  border:1px solid var(--soft-blue); }
.nw-req-date { font-size:11px; color:var(--text-4); }
.nw-req-profile { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; }
.nw-req-avatar { width:36px; height:36px; border-radius:8px; color:#fff; font-family:var(--font-sans); font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.nw-req-name { font-size:13px; font-weight:700; color:var(--text); line-height:1.3; }
.nw-req-role { font-size:11.5px; color:var(--text-3); margin-top:1px; }
.nw-req-loc  { font-size:11px; color:var(--text-4); margin-top:1px; }
.nw-req-actions { display:flex; gap:6px; }

/* Tabs */
.nw-tabs { display:flex; align-items:center; gap:2px; border-bottom:1px solid var(--border); margin-bottom:18px; }
.nw-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text-3); background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s,border-color .15s; margin-bottom:-1px; }
.nw-tab:hover { color:var(--text); }
.nw-tab.active { color:var(--text); font-weight:600; border-bottom-color:var(--gold-dark); }

/* Sub-tabs */
.nw-subtabs { display:flex; gap:8px; margin-bottom:18px; }
.nw-subtab { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; color:var(--text-3); background:var(--surface-2); border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:all .15s; }
.nw-subtab:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.nw-subtab.active { background:var(--gold-dark); color:#fff; border-color:var(--gold-dark); }

/* Section headers */
.nw-section-header { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
.nw-section-title { display:inline-flex; align-items:center; gap:7px; font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); }
.nw-section-sub { font-size:12.5px; color:var(--text-3); margin:0 0 14px; line-height:1.5; }
.nw-ai-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; letter-spacing:.05em; padding:3px 8px; border-radius:99px; background:rgba(74,144,196,.1); color:var(--blue-dark); border:1px solid var(--soft-blue); }
.nw-ai-badge-match { background:rgba(76,175,125,.1); color:var(--green-dark); border-color:var(--soft-green); }

/* Recommended scroll row */
.nw-rec-scroll { display:flex; gap:10px; overflow-x:auto; padding-bottom:6px; margin-bottom:4px; }
.nw-rec-card { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:14px 16px; min-width:150px; flex-shrink:0; cursor:pointer; transition:box-shadow .15s; }
.nw-rec-card:hover { box-shadow:var(--shadow-sm); }
.nw-rec-icon { margin-bottom:8px; }
.nw-rec-label { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.nw-rec-desc { font-size:11.5px; color:var(--text-4); margin-bottom:8px; }
.nw-rec-count { font-size:11.5px; color:var(--text-3); margin-bottom:8px; }
.nw-rec-arrow { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; border:1.5px solid var(--border); background:transparent; color:var(--text-3); cursor:pointer; transition:all .15s; }
.nw-rec-arrow:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* Shadow / provider cards */
.nw-shadow-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:28px; }
.nw-provider-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px 13px; position:relative; cursor:pointer; transition:box-shadow .15s,transform .15s; }
.nw-provider-card:hover { box-shadow:var(--shadow-sm); transform:translateY(-2px); }
.nw-pcard-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.nw-pcard-match { font-size:11px; font-weight:700; color:var(--green-dark); background:var(--green-light); border-radius:99px; padding:2px 7px; }
.nw-pcard-rating { font-size:11px; font-weight:600; color:var(--gold-dark); }
.nw-added-btn { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border:none; border-radius:99px; padding:2px 8px; cursor:default; }
.nw-pcard-avatar { width:40px; height:40px; border-radius:10px; color:#fff; font-family:var(--font-sans); font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; margin-bottom:8px; }
.nw-pcard-name { font-family:var(--font-sans); font-size:12.5px; font-weight:700; color:var(--text); line-height:1.3; margin-bottom:2px; }
.nw-pcard-role { font-size:11px; color:var(--text-4); margin-bottom:5px; }
.nw-pcard-loc  { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:var(--text-4); margin-bottom:8px; }
.nw-pcard-tags { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:10px; }
.nw-pcard-tag  { font-size:10.5px; color:var(--text-3); background:var(--surface-2); border:1px solid var(--border); border-radius:99px; padding:2px 7px; }
.nw-pcard-actions { display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.nw-pcard-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.nw-pcard-icon-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.nw-connect-btn { display:inline-flex; align-items:center; gap:4px; font-size:11.5px; }

/* Search layout */
.nw-search-results-header { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-4); text-align:center; margin:4px 0 16px; }
.nw-search-layout { display:grid; grid-template-columns:180px 1fr; gap:18px; }
.nw-filters-sidebar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px; height:fit-content; }
.nw-filters-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.nw-filters-title { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-3); }
.nw-clear-btn { font-size:10px; font-weight:700; color:var(--gold-dark); background:transparent; border:none; cursor:pointer; letter-spacing:.04em; }
.nw-filter-group { margin-bottom:2px; }
.nw-filter-group-btn { display:flex; align-items:center; gap:7px; width:100%; padding:8px 4px; font-family:var(--font-sans); font-size:12px; font-weight:500; color:var(--text-2); background:transparent; border:none; cursor:pointer; border-bottom:1px solid var(--border); }
.nw-filter-group-btn:hover { color:var(--text); }
.nw-apply-btn { display:block; width:100%; margin-top:14px; padding:10px; font-family:var(--font-sans); font-size:12.5px; font-weight:700; background:var(--gold-dark); color:#fff; border:none; border-radius:8px; cursor:pointer; transition:background .15s; }
.nw-apply-btn:hover { background:var(--gold); }
.nw-results-header-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px; }
.nw-results-count { font-size:12.5px; color:var(--text-3); }
.nw-results-sort { display:flex; align-items:center; gap:6px; }
.nw-sort-label { font-size:12px; color:var(--text-4); }
.nw-sort-select { font-family:var(--font-sans); font-size:12px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; padding:4px 8px; cursor:pointer; outline:none; }
.nw-results-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.nw-result-card .nw-pcard-top { flex-direction:row; align-items:center; gap:10px; }
.nw-result-card .nw-pcard-avatar { margin-bottom:0; width:38px; height:38px; font-size:12px; }
.nw-result-stats { font-size:11px; color:var(--text-4); margin-bottom:8px; }

/* Empty states */
.nw-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:60px 20px; color:var(--text-4); }
.nw-empty p { font-family:var(--font-sans); font-size:14px; margin:0; }

@media(max-width:1200px) { .nw-shadow-grid { grid-template-columns:repeat(3,1fr); } .nw-results-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:860px)  { .nw-shadow-grid { grid-template-columns:repeat(2,1fr); } .nw-search-layout { grid-template-columns:1fr; } }

/* ── Business Partners ──────────────────────────────────────── */
.bp-search-bar { display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap; }
.bp-search-wrap { position:relative; flex:1; min-width:220px; }
.bp-search-input { width:100%; padding:9px 12px 9px 34px; font-family:var(--font-sans); font-size:13px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-sm,8px); outline:none; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.bp-search-input:focus { border-color:var(--gold-dark); box-shadow:0 0 0 3px rgba(160,129,62,.14); }
.bp-search-input::placeholder { color:var(--text-4); }
.bp-sort-wrap { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.bp-sort-label { font-size:12px; color:var(--text-4); }
.bp-count { font-size:12.5px; color:var(--text-3); margin-bottom:14px; }
.bp-layout { display:grid; grid-template-columns:200px 1fr; gap:18px; }
.bp-sidebar { height:fit-content; }
.bp-filter-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:10px 4px; border-bottom:1px solid var(--border); margin-bottom:4px; }
.bp-filter-toggle-label { font-family:var(--font-sans); font-size:12px; color:var(--text-2); }
.bp-toggle { position:relative; display:inline-block; width:36px; height:20px; flex-shrink:0; }
.bp-toggle input { opacity:0; width:0; height:0; }
.bp-toggle-track { position:absolute; inset:0; background:var(--border-dark); border-radius:99px; cursor:pointer; transition:background .2s; }
.bp-toggle input:checked + .bp-toggle-track { background:var(--gold-dark); }
.bp-toggle-thumb { position:absolute; width:16px; height:16px; left:2px; top:2px; background:#fff; border-radius:50%; transition:transform .2s; }
.bp-toggle input:checked + .bp-toggle-track .bp-toggle-thumb { transform:translateX(16px); }
.bp-filter-section { border-bottom:1px solid var(--border); padding:8px 0; }
.bp-filter-section-header { display:flex; align-items:center; gap:7px; padding:4px; font-family:var(--font-sans); font-size:12px; font-weight:500; color:var(--text-2); cursor:pointer; }
.bp-filter-section-header:hover { color:var(--text); }
.bp-results {}
.bp-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.bp-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px 13px; transition:box-shadow .15s,transform .15s; cursor:pointer; }
.bp-card:hover { box-shadow:var(--shadow-sm); transform:translateY(-2px); }
.bp-card-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.bp-type-badge { font-size:9.5px; font-weight:700; letter-spacing:.05em; padding:2px 7px; border-radius:99px; background:rgba(160,129,62,.1); color:var(--gold-dark); border:1px solid var(--fade-gold); }
.bp-card-rating { font-size:11px; font-weight:600; color:var(--gold-dark); }
.bp-card-avatar-row { display:flex; justify-content:flex-start; margin-bottom:8px; }
.bp-avatar { width:44px; height:44px; border-radius:10px; color:#fff; font-family:var(--font-sans); font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; }
.bp-card-name { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.bp-card-role { font-size:11.5px; color:var(--text-4); margin-bottom:2px; }
.bp-card-loc  { font-size:11px; color:var(--text-4); margin-bottom:8px; }
.bp-card-tags { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:8px; }
.bp-card-stats { font-size:11px; color:var(--text-4); margin-bottom:10px; padding-top:8px; border-top:1px solid var(--border); }
.bp-card-actions { display:flex; gap:5px; }
@media(max-width:1200px) { .bp-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:900px)  { .bp-grid { grid-template-columns:repeat(2,1fr); } .bp-layout { grid-template-columns:1fr; } }

/* ── Referrals & Tools — Shadow Network ───────────────────── */
.rt-count-pill { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; font-size:10px; font-weight:700; border-radius:99px; background:#d4cdc3; color:var(--text-2); margin-left:2px; }
.rt-section-title { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); margin-bottom:12px; }
.rt-ai-banner { display:flex; align-items:flex-start; gap:12px; background:rgba(160,129,62,0.07); border:1px solid var(--fade-gold); border-radius:10px; padding:14px 16px; margin-bottom:16px; }
.rt-ai-icon { width:32px; height:32px; border-radius:8px; background:rgba(160,129,62,0.12); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rt-ai-title { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.rt-ai-sub   { font-family:var(--font-sans); font-size:12.5px; color:var(--text-3); line-height:1.55; }
.rt-ai-sub strong { color:var(--text); }
.rt-showing { font-size:12px; color:var(--text-4); margin-bottom:14px; }
.rt-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.rt-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 14px; position:relative; transition:box-shadow .15s,transform .15s; cursor:pointer; }
.rt-card:hover { box-shadow:var(--shadow-sm); transform:translateY(-2px); }
.rt-card-shadow-icon { position:absolute; top:12px; left:12px; color:var(--gold-dark); opacity:.6; }
.rt-card-avatar { width:44px; height:44px; border-radius:10px; color:#fff; font-family:var(--font-sans); font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; margin:8px auto 10px; }
.rt-card-name { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--text); text-align:center; margin-bottom:2px; }
.rt-card-role { font-size:11.5px; color:var(--text-4); text-align:center; margin-bottom:2px; }
.rt-card-loc  { font-size:11px; color:var(--text-4); text-align:center; margin-bottom:10px; }
.rt-card-tags { display:flex; flex-wrap:wrap; justify-content:center; gap:4px; margin-bottom:12px; }
.rt-card-actions { display:flex; justify-content:center; gap:6px; padding-top:10px; border-top:1px solid var(--border); }
.rt-card-remove:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }
@media(max-width:1200px) { .rt-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:860px)  { .rt-grid { grid-template-columns:repeat(2,1fr); } }

/* ── Review Requests Modal ─────────────────────────────────── */
.nw-modal-backdrop {
  position:fixed; inset:0; z-index:1000;
  background:rgba(30,28,26,0.45);
  backdrop-filter:blur(4px);
  display:flex; align-items:center; justify-content:center; padding:20px;
}
.nw-modal {
  background:var(--surface); border:1px solid var(--border);
  border-radius:14px; box-shadow:0 24px 64px rgba(30,28,26,0.2);
  width:100%; max-width:660px; overflow:hidden;
}
.nw-modal-header {
  display:flex; align-items:flex-start; justify-content:space-between;
  padding:20px 22px 14px; border-bottom:1px solid var(--border);
}
.nw-modal-title { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); margin-bottom:3px; }
.nw-modal-sub   { font-family:var(--font-sans);  font-size:12px; color:var(--text-4); }
.nw-modal-close-btn {
  width:28px; height:28px; padding:0; display:inline-flex; align-items:center; justify-content:center;
  border:1px solid var(--border); border-radius:6px; background:var(--surface);
  color:var(--text-3); cursor:pointer; flex-shrink:0; transition:all .15s;
}
.nw-modal-close-btn:hover { border-color:var(--text); color:var(--text); }
.nw-modal-notice {
  display:flex; align-items:flex-start; gap:9px; margin:14px 22px;
  padding:10px 13px; background:var(--blue-light); border:1px solid var(--soft-blue);
  border-radius:8px; font-family:var(--font-sans); font-size:12.5px; color:var(--blue-dark); line-height:1.5;
}
.nw-modal-list { padding:0 22px; max-height:380px; overflow-y:auto; }
.nw-modal-row {
  display:flex; align-items:flex-start; gap:12px; padding:14px 0;
  border-bottom:1px solid var(--border);
}
.nw-modal-row:last-child { border-bottom:none; }
.nw-modal-row-avatar {
  width:38px; height:38px; border-radius:50%; color:#fff;
  font-family:var(--font-sans); font-size:12px; font-weight:700;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.nw-modal-row-info { flex:1; min-width:0; }
.nw-modal-row-name  { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:2px; }
.nw-modal-row-role  { font-size:12px; color:var(--text-4); margin-bottom:5px; }
.nw-modal-row-quote { font-size:12px; color:var(--text-3); font-style:italic; line-height:1.5; }
.nw-modal-row-right { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.nw-modal-row-btns  { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.nw-modal-empty { text-align:center; padding:24px; font-size:13px; color:var(--text-4); }
.nw-modal-footer {
  display:flex; justify-content:flex-end; padding:14px 22px;
  border-top:1px solid var(--border); background:var(--surface-2);
}
.nw-modal-fade-enter-active, .nw-modal-fade-leave-active { transition:opacity .2s ease; }
.nw-modal-fade-enter-active .nw-modal, .nw-modal-fade-leave-active .nw-modal { transition:transform .2s ease; }
.nw-modal-fade-enter-from, .nw-modal-fade-leave-to { opacity:0; }
.nw-modal-fade-enter-from .nw-modal { transform:translateY(-10px) scale(0.98); }
</style>
