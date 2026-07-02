<template>
  <AppLayout portal="practitioner" activePage="network" pageTitle="Network">
    <div class="nw-page-root">

    <!-- ── 1. HERO ──────────────────────────────────────────────────────── -->
    <AegisHeroBanner
      eyebrow="Connections"
      title="Network"
      subtitle="Cultivate an interdisciplinary care network, secure essential business resources, create a shadow network as a backup, and send secure referrals to support care access."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity', { module: 'network' })" class="btn-hero-ghost is-on-light nw-icon-btn">
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light nw-icon-btn" @click="modals.inviteProvider = true">
          <AegisIcon name="user-plus" :size="14" />
          Invite Provider
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ── 2. PENDING CONNECTION REQUESTS ──────────────────────────────── -->
    <section v-if="pendingRequests.length" class="section-block">
      <header class="section-head">
        <div class="section-head-text">
          <div class="section-head-eyebrow">Pending · {{ pendingRequests.length }} awaiting</div>
          <h2 class="section-head-title">Connection Requests</h2>
          <p class="section-head-sub">
            <strong>{{ clinicalCount }} clinical provider{{ clinicalCount !== 1 ? 's' : '' }}</strong>
            <span v-if="businessCount"> and {{ businessCount }} business contact{{ businessCount !== 1 ? 's' : '' }}</span>
          </p>
        </div>
        <button type="button" class="btn btn-outline btn-sm nw-icon-btn" @click="modals.reviewRequests = true">
          Review all <AegisIcon name="arrow-right" :size="13" />
        </button>
      </header>

      <div class="list-grid">
        <article
          v-for="req in pendingRequests.slice(0, 3)"
          :key="req.id"
          class="card is-person"
          style="cursor:pointer"
          @click="viewProfile(req.requester_slug)"
        >
          <div class="card-top">
            <span
              class="badge is-quiet"
              :class="req.request_type === 'business' ? 'is-business' : 'is-clinical'"
              :data-tooltip="req.request_type === 'business' ? 'Business Partner request' : 'Clinical Provider request'"
            >
              <AegisIcon :name="req.request_type === 'business' ? 'briefcase' : 'users'" :size="10" />
              {{ req.request_type === 'business' ? 'Business' : 'Clinical' }}
            </span>
            <span class="card-time">{{ timeAgo(req.created_at) }}</span>
          </div>
          <div class="person-row">
            <div class="person-avatar">{{ req.requester_initials }}</div>
            <div class="person-text">
              <div class="person-name">{{ req.requester_name }}</div>
              <div class="person-meta">{{ req.requester_role }}<span v-if="req.requester_location"> · {{ req.requester_location }}</span></div>
            </div>
          </div>
          <div class="card-actions" @click.stop>
            <button type="button" class="btn btn-primary btn-sm nw-icon-btn" :disabled="pendingActionId === req.id" @click="acceptRequest(req)">
              <AegisIcon name="check" :size="12" />
              {{ pendingActionId === req.id ? 'Accepting…' : 'Accept' }}
            </button>
            <button type="button" class="btn btn-outline btn-sm" :disabled="pendingActionId === req.id" @click="declineRequest(req)">Decline</button>
          </div>
        </article>
      </div>
    </section>

    <!-- ── 3. MAIN TABS ──────────────────────────────────────────────────── -->
    <div class="tabs-twotier">
      <div class="tabs-primary" role="tablist">
        <button type="button" class="tab-primary" :class="{ active: scope === 'clinical' }" @click="scope = 'clinical'">
          <AegisIcon name="users" :size="15" /> Integrative Care Network
        </button>
        <button type="button" class="tab-primary" :class="{ active: scope === 'business' }" @click="scope = 'business'">
          <AegisIcon name="heart-2" :size="15" /> Business Partners
        </button>
        <button type="button" class="tab-primary" :class="{ active: scope === 'tools' }" @click="scope = 'tools'">
          <AegisIcon name="cpu" :size="15" /> Referrals &amp; Tools
        </button>
      </div>

      <!-- Sub-tabs: Integrative Care Network -->
      <div v-show="scope === 'clinical'" class="tabs-segmented net-sub-tabs" role="tablist">
        <button type="button" class="tab-pill" :class="{ active: clinicalTab === 'search' }" @click="clinicalTab = 'search'">
          <AegisIcon name="search-lg" :size="12" /> Search Providers
        </button>
        <button type="button" class="tab-pill" :class="{ active: clinicalTab === 'mynetwork' }" @click="clinicalTab = 'mynetwork'">
          <AegisIcon name="users" :size="12" /> My Network
        </button>
      </div>

      <!-- Sub-tabs: Business Partners -->
      <div v-show="scope === 'business'" class="tabs-segmented net-sub-tabs" role="tablist">
        <button type="button" class="tab-pill" :class="{ active: businessTab === 'search' }" @click="businessTab = 'search'">
          <AegisIcon name="search-lg" :size="12" /> Search Partners
        </button>
        <button type="button" class="tab-pill" :class="{ active: businessTab === 'mypartners' }" @click="businessTab = 'mypartners'">
          <AegisIcon name="heart-2" :size="12" /> My Partners
        </button>
      </div>

      <!-- Sub-tabs: Referrals & Tools -->
      <div v-show="scope === 'tools'" class="tabs-segmented net-sub-tabs" role="tablist">
        <button type="button" class="tab-pill" :class="{ active: toolsTab === 'list' }" @click="toolsTab = 'list'">
          <AegisIcon name="cpu" :size="12" /> Referral List
        </button>
        <button type="button" class="tab-pill" :class="{ active: toolsTab === 'shadows' }" @click="toolsTab = 'shadows'">
          <AegisIcon name="check" :size="12" /> My Shadows
          <span class="badge-pill">{{ shadowConnections.length }}</span>
        </button>
        <button type="button" class="tab-pill" :class="{ active: toolsTab === 'config' }" @click="toolsTab = 'config'">
          <AegisIcon name="settings-3" :size="12" /> Configuration
        </button>
      </div>
    </div>

    <!-- ══════════ INTEGRATIVE CARE NETWORK ══════════ -->

    <!-- SEARCH PROVIDERS -->
    <div v-show="scope === 'clinical' && clinicalTab === 'search'" style="width:100%;max-width:100%;overflow:hidden;">

      <!-- Recommended Network Partners -->
      <div class="rec-section">
        <div class="rec-header">
          <div>
            <div class="rec-title">
              <AegisIcon name="lightbulb" :size="16" />
              Recommended Network Partners
              <span class="badge-ai" data-tooltip="AI-suggested specialists based on your referral patterns">AI Suggested <AegisIcon name="info" :size="11" /></span>
            </div>
            <p class="rec-sub">Based on your referral history and the specialties your clients ask for most</p>
          </div>
        </div>
        <div class="nw-slider-wrap">
          <button type="button" class="nw-slider-arrow nw-slider-arrow-left" @click="slideRnp(-1)" :disabled="rnpAtStart" aria-label="Previous">
            <AegisIcon name="chevron-left" :size="16" />
          </button>
          <div class="rnp-scroll" ref="rnpTrack">
            <article
              v-for="cat in recommendedCategories"
              :key="cat.label"
              class="card rnp-card"
              @click="clinicalTab = 'search'"
            >
              <div class="rnp-icon"><AegisIcon :name="cat.icon" :size="16" /></div>
              <div class="rnp-name">{{ cat.label }}</div>
              <div class="rnp-meta">{{ cat.desc }}</div>
              <div class="rnp-foot">
                <span class="rnp-stat"><span class="rnp-num">{{ cat.count }}</span> nearby</span>
                <button type="button" class="rnp-cta" @click.stop="clinicalTab = 'search'"><AegisIcon name="arrow-right" :size="13" /></button>
              </div>
            </article>
          </div>
          <button type="button" class="nw-slider-arrow nw-slider-arrow-right" @click="slideRnp(1)" :disabled="rnpAtEnd" aria-label="Next">
            <AegisIcon name="chevron-right" :size="16" />
          </button>
        </div>
      </div>

      <!-- Recommended Shadow Providers (AI) -->
      <div class="rec-section rec-section-shadow">
        <div class="rec-header">
          <div>
            <div class="rec-title">
              <AegisIcon name="cpu" :size="16" />
              Recommended Shadow Providers
              <span class="badge-ai badge-ai-green" data-tooltip="AI matching based on your clinical profile">AI Matched <AegisIcon name="cpu" :size="10" /></span>
            </div>
            <p class="rec-sub">A curated referral resource that helps identify providers who closely mirror your background and practice profile — Including region, specialty, demographics, services, and payment type.</p>
          </div>
        </div>
        <div class="ai-shadow-label">AI SHADOW RECOMMENDATIONS</div>
        <div class="nw-slider-wrap">
          <button type="button" class="nw-slider-arrow nw-slider-arrow-left" @click="slideSpc(-1)" :disabled="spcAtStart" aria-label="Previous">
            <AegisIcon name="chevron-left" :size="16" />
          </button>
          <div class="spc-track" ref="spcTrack">
            <div v-for="p in aiShadowCandidates" :key="p.name" class="spc-card" @click="viewProfile(p.slug)">
              <div class="spc-pills">
                <span class="spc-match-badge" :data-tooltip="p.match + '% AI match based on your clinical focus and referral patterns'">{{ p.match }}%</span>
                <span v-if="p.telehealth" class="spc-pill-svc" data-tooltip="Telehealth available"><AegisIcon name="video" :size="12" /></span>
              </div>
              <div class="spc-rating" :data-tooltip="p.rating + ' from peer reviews'"><AegisIcon name="star" :size="12" /> {{ p.rating }}</div>
              <div class="spc-body">
                <div class="spc-avatar">{{ p.initials }}</div>
                <div class="spc-name">{{ p.name }}</div>
                <div class="spc-role">{{ p.role }}</div>
                <div class="spc-loc"><AegisIcon name="map-pin" :size="10" /> {{ p.location }}</div>
                <div class="spc-tags">
                  <span v-for="tag in p.tags.slice(0,3)" :key="tag" class="spc-tag">{{ tag }}</span>
                  <span v-if="p.tags.length > 3" class="spc-tag">+{{ p.tags.length - 3 }}</span>
                </div>
              </div>
              <div class="spc-actions" @click.stop>
                <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === p.id" @click="openConversation(p.id)"><AegisIcon name="message-square" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(p.slug)"><AegisIcon name="eye" :size="14" /></button>
                <button v-if="!p.connected" type="button" class="btn btn-primary btn-sm nw-icon-btn" @click="openConnect(p)"><AegisIcon name="user-plus" :size="11" /> Connect</button>
                <span v-else class="nw-added-pill">✓ Connected</span>
              </div>
            </div>
          </div>
          <button type="button" class="nw-slider-arrow nw-slider-arrow-right" @click="slideSpc(1)" :disabled="spcAtEnd" aria-label="Next">
            <AegisIcon name="chevron-right" :size="16" />
          </button>
        </div>
      </div>

      <!-- Search Results Layout -->
      <div class="nw-search-header-label">SEARCH RESULTS</div>
      <div class="nw-search-layout">
        <!-- Filters sidebar -->
        <div class="nw-sidebar">
          <div class="nw-sidebar-top">
            <span class="nw-sidebar-label">FILTERS</span>
            <button type="button" class="nw-clear-btn" @click="filterGroups.forEach(f => f.open = false)">CLEAR ALL</button>
          </div>
          <div v-for="f in filterGroups" :key="f.label" class="nw-filter-group">
            <button type="button" class="nw-filter-btn" @click="f.open = !f.open">
              <AegisIcon :name="f.icon" :size="12" />
              {{ f.label }}
              <AegisIcon name="chevron-down" :size="11" style="margin-left:auto" :style="{ transform: f.open ? 'rotate(180deg)' : '' }" />
            </button>
            <div v-if="f.open && f.label === 'Provider Type'" class="nw-filter-expand">
              <input class="form-input nw-filter-search" type="text" v-model="providerTypeSearch" placeholder="Search types..." />
              <label v-for="opt in filteredProviderTypes" :key="opt" class="nw-filter-check">
                <input type="checkbox" v-model="selectedProviderTypes" :value="opt" />
                {{ opt }}
              </label>
            </div>
          </div>
          <button type="button" class="nw-apply-btn" @click="toast.success('Filters applied')">Apply Filters</button>
        </div>
        <!-- Results grid -->
        <div class="nw-results">
          <div class="nw-results-bar">
            <span class="nw-results-count">{{ searchResults.length }} providers found in your region</span>
            <div style="display:flex;align-items:center;gap:6px">
              <span style="font-size:12px;color:var(--text-4)">Sort:</span>
              <select class="form-select" v-model="searchSort" style="font-size:12px;padding:4px 8px">
                <option>Best Match</option><option>Highest Rated</option><option>Closest</option>
              </select>
            </div>
          </div>
          <div class="spc-results-grid">
            <div
              v-for="p in searchResults"
              :key="p.name"
              class="spc-card search-provider-card"
              @click="viewProfile(p.slug)"
            >
              <div class="spc-pills">
                <span
                  class="spc-status"
                  :class="p.networkStatus === 'in-network' ? 'ok' : (p.networkStatus === 'pending' ? 'pend' : 'off')"
                  :data-tooltip="p.networkStatus === 'in-network' ? 'In Network' : (p.networkStatus === 'pending' ? 'Request Pending' : 'Not Connected')"
                >
                  <AegisIcon :name="p.networkStatus === 'in-network' ? 'user-check' : 'user-plus'" :size="12" />
                </span>
                <span v-if="p.telehealth" class="spc-pill-svc" data-tooltip="Telehealth available"><AegisIcon name="video" :size="12" /></span>
              </div>
              <div class="spc-rating" :data-tooltip="p.rating + ' from ' + p.reviews + ' peer reviews'"><AegisIcon name="star" :size="12" /> {{ p.rating }}</div>
              <div class="spc-body">
                <div class="spc-avatar spc-avatar-lg">{{ p.initials }}</div>
                <div class="spc-name">{{ p.name }}</div>
                <div class="spc-role">{{ p.role }}</div>
                <div class="spc-loc">{{ p.location }}</div>
                <div class="spc-tags">
                  <span v-for="tag in p.tags.slice(0,3)" :key="tag" class="spc-tag">{{ tag }}</span>
                  <span v-if="p.tags.length > 3" class="spc-tag">+{{ p.tags.length - 3 }}</span>
                </div>
              </div>
              <div class="spc-stats-row">{{ p.refs }} · {{ p.acc }} · {{ p.resp }}</div>
              <div class="spc-actions" @click.stop>
                <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === p.id" @click="openConversation(p.id)"><AegisIcon name="message-square" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForProvider(p)"><AegisIcon name="refresh" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="Request Service" @click="openSvcRequest('Services', p.name)"><AegisIcon name="briefcase-rx" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(p.slug)"><AegisIcon name="eye" :size="14" /></button>
              </div>
            </div>
          </div>
          <AegisEmptyState v-if="!searchResults.length" icon="search-lg" title="No providers found" subtitle="Try adjusting your filters or search terms." />
          <div v-if="searchResults.length" class="nw-load-more">
            <button type="button" class="btn btn-outline btn-sm" @click="toast.info('Loading more results…')">Load More Results</button>
          </div>
        </div>
      </div>
    </div><!-- /search providers -->

    <!-- MY NETWORK -->
    <div v-show="scope === 'clinical' && clinicalTab === 'mynetwork'">
      <div class="stat-chips-row">
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="users" :size="18" /></div><div><div class="stat-chip-value">{{ stats.clinical }}</div><div class="stat-chip-label">Active Partners</div></div></div>
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="refresh" :size="18" /></div><div><div class="stat-chip-value">{{ stats.total_refs }}</div><div class="stat-chip-label">Referrals Exchanged</div></div></div>
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="check-badge" :size="18" /></div><div><div class="stat-chip-value">{{ stats.avg_acc }}%</div><div class="stat-chip-label">Avg Acceptance</div></div></div>
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div><div><div class="stat-chip-value">{{ stats.avg_resp }}h</div><div class="stat-chip-label">Avg Response Time</div></div></div>
      </div>
      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="pn-search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search by name, specialty, location..." v-model="clinicalSearch" />
        </div>
      </div>
      <div class="pn-results-bar">Showing <strong>{{ filteredClinical.length }}</strong> providers</div>
      <div class="spc-grid">
        <div v-for="nc in filteredClinical" :key="nc.id" class="spc-card" @click="viewProfile(nc.partner_slug)">
          <div class="spc-pills"><span class="spc-status ok" data-tooltip="In Network"><AegisIcon name="user-check" :size="12" /></span></div>
          <div class="spc-body">
            <div class="spc-avatar">{{ nc.partner_initials }}</div>
            <div class="spc-name">{{ nc.partner_name }}</div>
            <div class="spc-role">{{ nc.partner_role }}</div>
            <div class="spc-loc">{{ nc.partner_location }}</div>
            <div class="spc-tags">
              <span v-for="tag in tagList(nc.partner_specialty).slice(0,3)" :key="tag" class="spc-tag">{{ tag }}</span>
            </div>
          </div>
          <div class="spc-actions" @click.stop>
            <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === nc.partner_id" @click="openConversation(nc.partner_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForConnection(nc)"><AegisIcon name="refresh" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(nc.partner_slug)"><AegisIcon name="eye" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Remove from network" @click="confirmDisconnect(nc)"><AegisIcon name="trash-2" :size="14" /></button>
          </div>
        </div>
      </div>
      <AegisEmptyState v-if="!filteredClinical.length" icon="users" title="No clinical connections yet" subtitle="Search providers and send connection requests to build your network.">
        <template #actions><button type="button" class="btn btn-primary" @click="clinicalTab = 'search'">Search Providers</button></template>
      </AegisEmptyState>
    </div><!-- /my network -->

    <!-- ══════════ BUSINESS PARTNERS ══════════ -->

    <!-- SEARCH BUSINESS PARTNERS -->
    <div v-show="scope === 'business' && businessTab === 'search'">
      <div class="bp-search-bar">
        <div class="pn-search-wrap" style="flex:1;min-width:220px">
          <span class="pn-search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" style="padding-left:34px" v-model="bpSearch" placeholder="Search by name, skill, company, keyword..." />
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
          <span style="font-size:12px;color:var(--text-4)">Sort:</span>
          <select class="form-select" v-model="bpSort" style="font-size:12px;padding:4px 8px"><option>Best Match</option><option>Highest Rated</option><option>Most Jobs</option><option>Lowest Rate</option></select>
        </div>
      </div>
      <div class="bp-count">Showing {{ filteredPartners.length }} of {{ businessPartners.length }} partners</div>
      <div class="bp-layout">
        <div class="nw-sidebar">
          <div class="nw-sidebar-top"><span class="nw-sidebar-label">FILTERS</span><button type="button" class="nw-clear-btn" @click="bpSearch='';bpCategory=''">CLEAR ALL</button></div>
          <div class="bp-toggle-row">
            <span style="font-size:12px;color:var(--text-2)">Clinical-service providers</span>
            <label class="bp-toggle"><input type="checkbox" v-model="bpClinicalOnly"><span class="bp-track"><span class="bp-thumb"></span></span></label>
          </div>
          <div class="nw-filter-group">
            <button type="button" class="nw-filter-btn" @click="bpCatOpen = !bpCatOpen">
              <AegisIcon name="globe" :size="12" /> Category <AegisIcon name="chevron-down" :size="11" style="margin-left:auto" :style="{ transform: bpCatOpen ? 'rotate(180deg)' : '' }" />
            </button>
            <div v-if="bpCatOpen" style="margin-top:8px">
              <select class="form-select" v-model="bpCategory" style="width:100%;font-size:12px">
                <option value="">All Categories</option>
                <option>Medical Billing</option><option>Digital Marketing</option><option>Credentialing</option><option>Practice Consulting</option><option>Accounting / CPA</option><option>Admin / VA</option><option>HR / Staffing</option><option>Legal / Attorney</option><option>IT / Software</option><option>Design / Branding</option>
              </select>
            </div>
          </div>
          <div v-for="f in bpFilterGroups" :key="f.label" class="nw-filter-group">
            <button type="button" class="nw-filter-btn"><AegisIcon :name="f.icon" :size="12" /> {{ f.label }} <AegisIcon name="chevron-down" :size="11" style="margin-left:auto" /></button>
          </div>
          <button type="button" class="nw-apply-btn" @click="toast.success('Filters applied')">Apply Filters</button>
        </div>
        <div>
          <div class="bp-grid">
            <div v-for="p in filteredPartners" :key="p.name" class="biz-grid-card spc-card">
              <div class="bp-card-top"><span class="bp-type-badge">{{ p.partnerType }}</span><span class="bp-card-rating"><AegisIcon name="star" :size="11" /> {{ p.rating }}</span></div>
              <div class="bp-avatar-row"><div class="bp-avatar" :style="{ background: p.avatarColor }">{{ p.initials }}</div></div>
              <div class="bp-card-name">{{ p.name }}</div>
              <div class="bp-card-role">{{ p.role }}</div>
              <div class="bp-card-loc">{{ p.location }}</div>
              <div class="spc-tags" style="margin-bottom:8px"><span v-for="tag in p.tags.slice(0,3)" :key="tag" class="spc-tag">{{ tag }}</span><span v-if="p.tags.length > 3" class="spc-tag">+{{ p.tags.length - 3 }}</span></div>
              <div class="bp-card-stats">{{ p.rate }} · {{ p.reviews }} reviews · {{ p.jobs }} jobs</div>
              <div class="spc-actions">
                <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === p.id" @click="openConversation(p.id)"><AegisIcon name="message-square" :size="13" /></button>
                <button type="button" class="btn-icon" data-tooltip="Hire" @click="openBpHire(p)"><AegisIcon name="briefcase" :size="13" /></button>
                <button type="button" class="btn-icon" data-tooltip="View Profile"><AegisIcon name="eye" :size="13" /></button>
              </div>
            </div>
          </div>
          <AegisEmptyState v-if="!filteredPartners.length" icon="briefcase" title="No partners found" subtitle="Try adjusting your search or filters." />
        </div>
      </div>
    </div><!-- /search business -->

    <!-- MY PARTNERS -->
    <div v-show="scope === 'business' && businessTab === 'mypartners'">
      <div class="stat-chips-row">
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="18" /></div><div><div class="stat-chip-value">{{ bpConnections.length }}</div><div class="stat-chip-label">Business Partners</div></div></div>
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="heart-2" :size="18" /></div><div><div class="stat-chip-value">{{ stats.bp_count }}</div><div class="stat-chip-label">Active Contracts</div></div></div>
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="18" /></div><div><div class="stat-chip-value">—</div><div class="stat-chip-label">Avg Partner Rating</div></div></div>
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div><div><div class="stat-chip-value">{{ stats.pending_requests }}</div><div class="stat-chip-label">Pending Requests</div></div></div>
      </div>
      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="pn-search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search business partners by name, type, service..." v-model="bizSearch" />
        </div>
      </div>
      <div class="pn-results-bar">Showing <strong>{{ filteredBpConnections.length }}</strong> business partners</div>
      <div class="spc-grid">
        <div v-for="nc in filteredBpConnections" :key="nc.id" class="biz-grid-card spc-card" @click="viewProfile(nc.partner_slug)">
          <div class="spc-pills"><span class="spc-status ok" data-tooltip="Business Partner"><AegisIcon name="user-check" :size="12" /></span></div>
          <div class="spc-body">
            <div class="spc-avatar">{{ nc.partner_initials }}</div>
            <div class="spc-name">{{ nc.partner_name }}</div>
            <div class="spc-role">{{ nc.partner_role }}</div>
            <div class="spc-loc">{{ nc.partner_location }}</div>
          </div>
          <div class="spc-actions" @click.stop>
            <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === nc.partner_id" @click="openConversation(nc.partner_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(nc.partner_slug)"><AegisIcon name="eye" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Remove" @click="confirmDisconnect(nc)"><AegisIcon name="trash-2" :size="14" /></button>
          </div>
        </div>
      </div>
      <AegisEmptyState v-if="!filteredBpConnections.length" icon="briefcase" title="No business partners yet" subtitle="Search the partner directory to find billing, legal, IT, and other practice services.">
        <template #actions><button type="button" class="btn btn-primary" @click="businessTab = 'search'">Search Partners</button></template>
      </AegisEmptyState>
    </div><!-- /my partners -->

    <!-- ══════════ REFERRALS & TOOLS ══════════ -->

    <!-- REFERRAL LIST (AI Shadow Suggestions) -->
    <div v-show="scope === 'tools' && toolsTab === 'list'">
      <div class="rt-section-title">Shadow Network</div>
      <div class="rt-ai-banner">
        <div class="rt-ai-icon"><AegisIcon name="help-circle" :size="16" /></div>
        <div>
          <div class="rt-ai-title">This week's AI insights</div>
          <div class="rt-ai-sub">Based on your recent clients, you may benefit from <strong>3 additional PTSD specialists</strong> and <strong>2 child &amp; adolescent therapists</strong>. Aegis found <strong>{{ filteredRtCandidates.length }} high-match candidates</strong> below.</div>
        </div>
      </div>
      <div class="pn-search-wrap" style="margin-bottom:10px">
        <span class="pn-search-icon"><AegisIcon name="search-lg" :size="14" /></span>
        <input class="form-input" style="padding-left:34px" v-model="rtSearch" placeholder="Search by name, specialty, location..." />
      </div>
      <div style="font-size:12px;color:var(--text-4);margin-bottom:14px">Showing {{ filteredRtCandidates.length }} AI suggestions</div>
      <div class="rt-grid">
        <div v-for="s in filteredRtCandidates" :key="s.name" class="rt-card">
          <div class="rt-shadow-icon"><AegisIcon name="cpu" :size="12" /></div>
          <div class="rt-avatar">{{ s.initials }}</div>
          <div class="rt-name">{{ s.name }}</div>
          <div class="rt-role">{{ s.role }}</div>
          <div class="rt-loc">{{ s.location }}</div>
          <div class="spc-tags" style="justify-content:center;margin-bottom:12px"><span v-for="tag in s.tags" :key="tag" class="spc-tag">{{ tag }}</span></div>
          <div class="rt-actions">
            <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === s.id" @click="openConversation(s.id)"><AegisIcon name="message-square" :size="13" /></button>
            <button type="button" class="btn-icon" data-tooltip="Add to Shadows" @click="openConnect(s)"><AegisIcon name="user-plus" :size="13" /></button>
            <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(s.slug)"><AegisIcon name="eye" :size="13" /></button>
            <button type="button" class="btn-icon rt-remove-btn" data-tooltip="Remove" @click="removeRtCandidate(s)"><AegisIcon name="x" :size="13" /></button>
          </div>
        </div>
      </div>
    </div><!-- /referral list -->

    <!-- MY SHADOWS -->
    <div v-show="scope === 'tools' && toolsTab === 'shadows'">
      <div class="stat-chips-row">
        <div class="stat-chip"><div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="cpu" :size="18" /></div><div><div class="stat-chip-value">{{ shadowConnections.length }}</div><div class="stat-chip-label">Active Shadows</div></div></div>
      </div>
      <div style="font-size:12px;color:var(--text-4);margin-bottom:14px">Showing <strong>{{ shadowConnections.length }}</strong> shadow connections</div>
      <div class="rt-grid">
        <div v-for="s in shadowConnections" :key="s.id" class="rt-card">
          <div class="rt-shadow-icon"><AegisIcon name="cpu" :size="12" /></div>
          <div class="rt-avatar">{{ s.shadow_initials }}</div>
          <div class="rt-name">{{ s.shadow_name }}</div>
          <div class="rt-role">{{ s.shadow_role }}</div>
          <div class="rt-loc">{{ s.shadow_location }}</div>
          <div class="spc-tags" style="justify-content:center;margin-bottom:12px">
            <span v-for="tag in tagList(s.shadow_specialty)" :key="tag" class="spc-tag">{{ tag }}</span>
          </div>
          <div class="rt-actions">
            <button v-if="s.shadow_user_id" type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === s.shadow_user_id" @click="openConversation(s.shadow_user_id)"><AegisIcon name="message-square" :size="13" /></button>
            <button type="button" class="btn-icon rt-remove-btn" data-tooltip="Remove Shadow" @click="confirmRemoveShadow(s)"><AegisIcon name="trash-2" :size="13" /></button>
          </div>
        </div>
      </div>
      <AegisEmptyState v-if="!shadowConnections.length" icon="cpu" title="No shadow connections yet" subtitle="Shadow providers are backup clinicians who mirror your profile. Add them from the Referral List.">
        <template #actions><button type="button" class="btn btn-primary" @click="toolsTab = 'list'">Browse Suggestions</button></template>
      </AegisEmptyState>
    </div><!-- /my shadows -->

    <!-- CONFIGURATION -->
    <div v-show="scope === 'tools' && toolsTab === 'config'">
      <div class="rt-section-title">Network Configuration</div>
      <div class="card card-body"><p style="font-size:13px;color:var(--text-3)">Network preferences and configuration options coming soon.</p></div>
    </div>

    <!-- ══════════ MODALS ══════════ -->

    <!-- Review Pending Requests -->
    <AegisModal v-model="modals.reviewRequests" title="Pending Connection Requests" :subtitle="pendingRequests.length + ' awaiting · ' + clinicalCount + ' clinical, ' + businessCount + ' business'" size="lg">
      <div class="alert alert-info" style="margin-bottom:14px">
        <AegisIcon name="info" :size="14" />
        <div><strong>Accepting</strong> adds the connection and notifies the requester. <strong>Declining</strong> removes the request — the other party is not notified.</div>
      </div>
      <div class="nw-modal-list">
        <div v-for="req in pendingRequests" :key="req.id" class="nw-modal-row">
          <div class="nw-modal-avatar">{{ req.requester_initials }}</div>
          <div class="nw-modal-info">
            <div class="nw-modal-name">{{ req.requester_name }}</div>
            <div class="nw-modal-meta">{{ req.requester_role }}<span v-if="req.requester_location"> · {{ req.requester_location }}</span></div>
            <div v-if="req.message" class="nw-modal-quote">"{{ req.message }}"</div>
          </div>
          <div class="nw-modal-right">
            <span class="badge is-quiet" :class="req.request_type === 'business' ? 'is-business' : 'is-clinical'">{{ req.request_type === 'business' ? 'Business' : 'Clinical' }}</span>
            <span style="font-size:11px;color:var(--text-4)">{{ timeAgo(req.created_at) }}</span>
          </div>
          <div class="nw-modal-btns">
            <button type="button" class="btn btn-primary btn-sm nw-icon-btn" :disabled="pendingActionId === req.id" @click="acceptRequest(req)"><AegisIcon name="check" :size="11" /> Accept</button>
            <button type="button" class="btn btn-outline btn-sm" :disabled="pendingActionId === req.id" @click="declineRequest(req)">Decline</button>
            <button type="button" class="btn-icon" data-tooltip="View profile" @click="viewProfile(req.requester_slug); modals.reviewRequests = false"><AegisIcon name="eye" :size="13" /></button>
          </div>
        </div>
        <div v-if="!pendingRequests.length" style="text-align:center;padding:24px;font-size:13px;color:var(--text-4)">All requests have been reviewed.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.reviewRequests = false">Close</button>
      </template>
    </AegisModal>

    <!-- Invite Provider Modal -->
    <AegisModal v-model="modals.inviteProvider" title="Invite Provider to Aegis">
      <div class="alert alert-info" style="margin-bottom:16px">
        <AegisIcon name="lightbulb" :size="16" />
        <div>Invited providers will receive a personalized email with your name, and a pre-filled Aegis onboarding link. You'll be notified when they join.</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Provider Full Name</label>
          <input class="form-input" type="text" v-model="inviteForm.display_name" placeholder="Dr. First Last" />
          <div v-if="inviteForm.errors.display_name" class="form-error">{{ inviteForm.errors.display_name }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input class="form-input" type="email" v-model="inviteForm.email" placeholder="doctor@clinic.com" />
          <div v-if="inviteForm.errors.email" class="form-error">{{ inviteForm.errors.email }}</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Specialty</label>
          <input class="form-input" type="text" v-model="inviteForm.specialty" placeholder="e.g., Psychologist, Therapist" />
        </div>
        <div class="form-group">
          <label class="form-label">Network Type</label>
          <select class="form-select" v-model="inviteForm.network_type"><option>Network</option><option>Business Partners</option><option>Both</option></select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Personal Message (optional)</label>
        <textarea class="form-textarea" v-model="inviteForm.note" rows="3" placeholder="Hi Dr. [Name], I'd love for you to join me on Aegis…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.inviteProvider = false">Cancel</button>
        <button type="button" class="btn btn-primary nw-icon-btn" :disabled="inviteForm.processing" @click="submitInvite">
          {{ inviteForm.processing ? 'Sending…' : 'Send Invitation' }} <AegisIcon name="mail" :size="16" />
        </button>
      </template>
    </AegisModal>

    <!-- Connect / Send Request Modal -->
    <AegisModal v-model="modals.connect" title="Send Connection Request">
      <div v-if="connectTarget" class="alert alert-info" style="margin-bottom:16px">
        <AegisIcon name="user-plus" :size="16" />
        <div><strong>{{ connectTarget.name }}</strong><span v-if="connectTarget.role"> · {{ connectTarget.role }}</span></div>
      </div>
      <div class="form-group">
        <label class="form-label">Message (optional)</label>
        <textarea class="form-textarea" v-model="connectForm.note" rows="3" placeholder="Introduce yourself or explain why you'd like to connect…"></textarea>
        <div v-if="connectForm.errors.note" class="form-error">{{ connectForm.errors.note }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.connect = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="connectForm.processing" @click="submitConnect">
          {{ connectForm.processing ? 'Sending…' : 'Send Request' }}
        </button>
      </template>
    </AegisModal>

    <!-- Service Request Modal -->
    <AegisModal v-model="modals.svcRequest" title="Request Service">
      <div v-if="svcTarget" style="padding:12px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:18px">
        <div style="font-size:13px;font-weight:700;color:var(--text)">{{ svcTarget.serviceName }}</div>
        <div style="font-size:12px;color:var(--text-4);margin-top:1px">with <strong>{{ svcTarget.providerName }}</strong></div>
      </div>
      <div class="alert alert-info" style="margin-bottom:16px">
        <AegisIcon name="alert-disc" :size="14" />
        <div>Once accepted, a <strong>Service Agreement</strong> will be generated. Payment is collected after both parties sign.</div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Preferred Date</label><input class="form-input" type="date" v-model="svcForm.preferred_date" /></div>
        <div class="form-group"><label class="form-label">Preferred Time</label><input class="form-input" type="time" v-model="svcForm.preferred_time" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Frequency</label><select class="form-select" v-model="svcForm.frequency"><option>One-time</option><option>Weekly</option><option>Bi-weekly</option><option>Monthly</option></select></div>
        <div class="form-group"><label class="form-label">Your License Status</label><select class="form-select" v-model="svcForm.license_status"><option>Pre-licensed (Associate)</option><option>Fully Licensed</option><option>Other</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">Message to Provider</label><textarea class="form-textarea" v-model="svcForm.message" rows="3" placeholder="Introduce yourself and describe your goals…"></textarea></div>
      <label class="form-check">
        <input type="checkbox" v-model="svcForm.terms_agreed" />
        <span class="form-check-label">I agree to Aegis's service terms and platform policies</span>
      </label>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.svcRequest = false">Cancel</button>
        <button type="button" class="btn btn-primary nw-icon-btn" :disabled="!svcForm.terms_agreed" @click="submitSvcRequest">
          <AegisIcon name="check" :size="14" /> Send Request
        </button>
      </template>
    </AegisModal>

    <!-- BP Hire Modal -->
    <AegisModal v-model="modals.bpHire" title="Hire Business Partner">
      <div v-if="bpHireTarget" style="padding:12px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:16px">
        <div style="font-size:13px;font-weight:700;color:var(--text)">{{ bpHireTarget.name }}</div>
        <div style="font-size:12px;color:var(--text-4)">{{ bpHireTarget.role }}</div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Project Title</label><input class="form-input" type="text" v-model="bpHireForm.title" placeholder="e.g. Monthly billing services" /></div>
        <div class="form-group"><label class="form-label">Engagement Type</label><select class="form-select" v-model="bpHireForm.engagement_type"><option>One-Time Project</option><option>Ongoing / Retainer</option><option>Part-Time Contract</option><option>Full-Time</option></select></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Start Date</label><input class="form-input" type="date" v-model="bpHireForm.start_date" /></div>
        <div class="form-group"><label class="form-label">Budget</label><input class="form-input" type="text" v-model="bpHireForm.budget" placeholder="e.g. $500/mo or $2,000 fixed" /></div>
      </div>
      <div class="form-group"><label class="form-label">Scope of Work</label><textarea class="form-textarea" v-model="bpHireForm.scope" rows="3" placeholder="Describe deliverables, requirements, and timeline…"></textarea></div>
      <div class="form-row" style="margin-bottom:14px">
        <label class="form-check"><input type="checkbox" v-model="bpHireForm.include_nda" /><span class="form-check-label">Include NDA Agreement</span></label>
        <label class="form-check"><input type="checkbox" v-model="bpHireForm.require_baa" /><span class="form-check-label">Require HIPAA BAA</span></label>
        <label class="form-check"><input type="checkbox" v-model="bpHireForm.auto_contract" /><span class="form-check-label">Auto-generate Service Contract</span></label>
      </div>
      <div class="alert alert-info">
        <AegisIcon name="clipboard" :size="16" />
        <div>Aegis will notify the partner and generate a contract draft. Both parties must e-sign before work begins.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.bpHire = false">Cancel</button>
        <button type="button" class="btn btn-primary nw-icon-btn" @click="submitBpHire"><AegisIcon name="user-cog" :size="16" /> Send Hire Request</button>
      </template>
    </AegisModal>

    <!-- Post Job / Request Support Modal -->
    <AegisModal v-model="modals.postJob" title="Request Support from Business Partners" size="lg">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Request Title</label><input class="form-input" type="text" v-model="postJobForm.title" placeholder="e.g. Medical Billing Specialist Needed" /></div>
        <div class="form-group"><label class="form-label">Service Category</label><select class="form-select" v-model="postJobForm.category"><option>Medical Billing</option><option>Accounting / CPA</option><option>Legal / Attorney</option><option>Marketing</option><option>Technology / EHR</option><option>HR / Staffing</option><option>Credentialing</option><option>Design / Branding</option><option>Admin / VA</option><option>Practice Consulting</option></select></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Partner Type Preferred</label><select class="form-select" v-model="postJobForm.partner_type"><option>Any</option><option>Freelancer</option><option>Agency</option><option>Consultant</option><option>Firm</option><option>Solopreneur</option></select></div>
        <div class="form-group"><label class="form-label">Engagement Type</label><select class="form-select" v-model="postJobForm.engagement_type"><option>One-Time Project</option><option>Ongoing / Retainer</option><option>Part-Time Contract</option><option>Full-Time</option></select></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Budget Range</label><input class="form-input" type="text" v-model="postJobForm.budget" placeholder="e.g. $50–$100/hr or $2,000/mo" /></div>
        <div class="form-group"><label class="form-label">Project Timeline</label><select class="form-select" v-model="postJobForm.timeline"><option>ASAP</option><option>Within 1 week</option><option>1–2 weeks</option><option>1 month</option><option>Flexible</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">Job Description</label><textarea class="form-textarea" v-model="postJobForm.description" rows="4" placeholder="Describe the role, required skills, deliverables, and any important context about your practice…"></textarea></div>
      <div class="form-group"><label class="form-label">Required Qualifications / Tags</label><input class="form-input" type="text" v-model="postJobForm.qualifications" placeholder="e.g. HIPAA Certified, 5+ yrs experience, Mental Health billing…" /></div>
      <div class="form-row" style="margin-bottom:14px">
        <label class="form-check"><input type="checkbox" v-model="postJobForm.hipaa_required" /><span class="form-check-label">Require HIPAA Compliance</span></label>
        <label class="form-check"><input type="checkbox" v-model="postJobForm.verified_only" /><span class="form-check-label">Verified Partners Only</span></label>
        <label class="form-check"><input type="checkbox" v-model="postJobForm.remote_allowed" /><span class="form-check-label">Remote Work Allowed</span></label>
        <label class="form-check"><input type="checkbox" v-model="postJobForm.require_nda" /><span class="form-check-label">Require NDA Signature</span></label>
      </div>
      <div class="alert alert-gold">
        <AegisIcon name="trending-up" :size="16" />
        <div>Your job will be visible to all matching verified partners in the Aegis Business Partners. You'll receive proposals within 24–48 hours.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.postJob = false">Cancel</button>
        <button type="button" class="btn btn-primary nw-icon-btn" @click="submitPostJob"><AegisIcon name="trending-up" :size="16" /> Post Job</button>
      </template>
    </AegisModal>

    <!-- Centralized ReferralModal — wired via openModal('referralModal') -->
    <ReferralModal :roster="roster" :network="referralNetwork" :preselect-slug="referralPreselectSlug" />
    </div><!-- /nw-page-root -->

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import { useModal }         from '@/composables/useModal'
import { useToast }         from '@/composables/useToast'
import { useConfirm }       from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  clinicalConnections: { type: Array,  default: () => [] },
  bpConnections:       { type: Array,  default: () => [] },
  pendingRequests:     { type: Array,  default: () => [] },
  shadowConnections:   { type: Array,  default: () => [] },
  referralNetwork:     { type: Array,  default: () => [] },
  roster:              { type: Array,  default: () => [] },
  stats:               { type: Object, default: () => ({}) },
})

// ── Composables ────────────────────────────────────────────────────────────
const { openModal } = useModal()
const toast         = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

// ── Slider state ──────────────────────────────────────────────────────────
const rnpTrack  = ref(null)
const spcTrack  = ref(null)
const rnpAtStart = ref(true)
const rnpAtEnd   = ref(false)
const spcAtStart = ref(true)
const spcAtEnd   = ref(false)

const SLIDE_PX = 320

function updateArrows(el, atStart, atEnd) {
  if (!el) return
  atStart.value = el.scrollLeft <= 4
  atEnd.value   = el.scrollLeft >= el.scrollWidth - el.clientWidth - 4
}

function slideRnp(dir) {
  const el = rnpTrack.value
  if (!el) return
  el.scrollBy({ left: dir * SLIDE_PX, behavior: 'smooth' })
  setTimeout(() => updateArrows(el, rnpAtStart, rnpAtEnd), 320)
}

function slideSpc(dir) {
  const el = spcTrack.value
  if (!el) return
  el.scrollBy({ left: dir * SLIDE_PX, behavior: 'smooth' })
  setTimeout(() => updateArrows(el, spcAtStart, spcAtEnd), 320)
}

onMounted(() => {
  if (rnpTrack.value) {
    rnpTrack.value.addEventListener('scroll', () => updateArrows(rnpTrack.value, rnpAtStart, rnpAtEnd))
    updateArrows(rnpTrack.value, rnpAtStart, rnpAtEnd)
  }
  if (spcTrack.value) {
    spcTrack.value.addEventListener('scroll', () => updateArrows(spcTrack.value, spcAtStart, spcAtEnd))
    updateArrows(spcTrack.value, spcAtStart, spcAtEnd)
  }
})

// ── Tab / scope state ──────────────────────────────────────────────────────
const scope       = ref('clinical')
const clinicalTab = ref('search')
const businessTab = ref('search')
const toolsTab    = ref('list')

// ── Local modal state ──────────────────────────────────────────────────────
const modals = reactive({
  reviewRequests: false,
  inviteProvider: false,
  connect:        false,
  svcRequest:     false,
  bpHire:         false,
  postJob:        false,
})

// ── Computed ───────────────────────────────────────────────────────────────
const clinicalCount = computed(() => props.pendingRequests.filter(r => r.request_type !== 'business').length)
const businessCount = computed(() => props.pendingRequests.filter(r => r.request_type === 'business').length)

// ── Accept / Decline ───────────────────────────────────────────────────────
const pendingActionId = ref(null)

function acceptRequest(req) {
  pendingActionId.value = req.id
  router.post(route('provider.network.accept', { networkRequest: req.id }), {}, {
    onSuccess: () => { toast.success(req.requester_name + ' accepted'); modals.reviewRequests = false },
    onError:   () => toast.error('Could not accept request'),
    onFinish:  () => { pendingActionId.value = null },
  })
}

function declineRequest(req) {
  pendingActionId.value = req.id
  router.post(route('provider.network.decline', { networkRequest: req.id }), {}, {
    onSuccess: () => toast.info(req.requester_name + ' declined'),
    onError:   () => toast.error('Could not decline request'),
    onFinish:  () => { pendingActionId.value = null },
  })
}

// ── Disconnect ─────────────────────────────────────────────────────────────
function confirmDisconnect(nc) {
  confirmAction(
    {
      title:        'Remove from network?',
      message:      nc.partner_name + ' will be removed from your network. This ends your referral relationship on Aegis.',
      confirmLabel: 'Remove',
      destructive:  true,
    },
    () => router.delete(route('provider.network.disconnect', { connection: nc.id }), {
      onSuccess: () => toast.info(nc.partner_name + ' removed'),
    })
  )
}

// ── Remove shadow connection ────────────────────────────────────────────────
function confirmRemoveShadow(s) {
  confirmAction(
    { title: 'Remove shadow?', message: (s.shadow_name || 'This shadow') + ' will be removed from your shadow network.', confirmLabel: 'Remove', destructive: true },
    () => router.delete(route('provider.network.disconnect', { connection: s.id }), {
      onSuccess: () => toast.info('Shadow removed'),
    })
  )
}

// ── Invite Provider ────────────────────────────────────────────────────────
const inviteForm = useForm({ display_name: '', email: '', note: '', specialty: '', network_type: 'Network' })

function submitInvite() {
  inviteForm.post(route('provider.network.invite'), {
    onSuccess: () => { modals.inviteProvider = false; inviteForm.reset(); toast.success('Invitation sent!') },
  })
}

// ── Connect (send request) ─────────────────────────────────────────────────
const connectTarget = ref(null)
const connectForm   = useForm({ to_user_id: '', note: '' })

function openConnect(p) {
  connectTarget.value = p
  connectForm.to_user_id = p.id ?? ''
  connectForm.note = ''
  modals.connect = true
}

function submitConnect() {
  connectForm.post(route('provider.network.connect'), {
    onSuccess: () => { modals.connect = false; toast.success('Connection request sent!') },
  })
}

// ── ReferralModal — centralized via useModal ───────────────────────────────
const referralPreselectSlug = ref('')

function openReferralForProvider(p) {
  referralPreselectSlug.value = p.slug ?? ''
  openModal('referralModal')
}

function openReferralForConnection(nc) {
  referralPreselectSlug.value = nc.partner_slug ?? ''
  openModal('referralModal')
}

// ── Service Request ────────────────────────────────────────────────────────
const svcTarget = ref(null)
const svcForm   = reactive({ preferred_date: '', preferred_time: '', frequency: 'One-time', license_status: 'Fully Licensed', message: '', terms_agreed: false })

function openSvcRequest(serviceName, providerName) {
  svcTarget.value = { serviceName, providerName }
  modals.svcRequest = true
}

function submitSvcRequest() {
  toast.success('Service request sent! Provider will be notified.')
  modals.svcRequest = false
}

// ── BP Hire ────────────────────────────────────────────────────────────────
const bpHireTarget = ref(null)
const bpHireForm   = reactive({ title: '', engagement_type: 'One-Time Project', start_date: '', budget: '', scope: '', include_nda: true, require_baa: true, auto_contract: true })

function openBpHire(p) {
  bpHireTarget.value = p
  modals.bpHire = true
}

function submitBpHire() {
  toast.success('Hire request sent! Partner will be notified.')
  modals.bpHire = false
}

// ── Post Job ───────────────────────────────────────────────────────────────
const postJobForm = reactive({ title: '', category: 'Medical Billing', partner_type: 'Any', engagement_type: 'One-Time Project', budget: '', timeline: 'ASAP', description: '', qualifications: '', hipaa_required: true, verified_only: false, remote_allowed: true, require_nda: false })

function submitPostJob() {
  toast.success('Job posted! Partners will be notified.')
  modals.postJob = false
}

// ── Profile navigation ─────────────────────────────────────────────────────
function viewProfile(slug) {
  if (slug) router.visit(route('provider.profile.public', { slug }))
}

// ── Search / filter helpers ────────────────────────────────────────────────
const clinicalSearch        = ref('')
const bizSearch             = ref('')
const providerTypeSearch    = ref('')
const selectedProviderTypes = ref([])
const allProviderTypes = ['Psychotherapist','Psychologist','Psychiatrist','Pain Management Specialist','Movement/Dance Specialist','Life Coach','Health Coach','Behavioral Therapist','Massage Therapist','Acupuncturist','Naturopathic Doctor (ND)','Functional Medicine Practitioner','Registered Dietitian (RD/RDN)']
const filteredProviderTypes = computed(() => {
  const q = providerTypeSearch.value.toLowerCase()
  if (!q) return allProviderTypes
  return allProviderTypes.filter(t => t.toLowerCase().includes(q))
})
const searchSort     = ref('Best Match')
const bpSearch       = ref('')
const bpSort         = ref('Best Match')
const bpClinicalOnly = ref(false)
const bpCategory     = ref('')
const bpCatOpen      = ref(true)
const rtSearch       = ref('')

const filteredClinical = computed(() => {
  const q = clinicalSearch.value.toLowerCase()
  if (!q) return props.clinicalConnections
  return props.clinicalConnections.filter(nc =>
    (nc.partner_name     ?? '').toLowerCase().includes(q) ||
    (nc.partner_role     ?? '').toLowerCase().includes(q) ||
    (nc.partner_location ?? '').toLowerCase().includes(q)
  )
})

const filteredBpConnections = computed(() => {
  const q = bizSearch.value.toLowerCase()
  if (!q) return props.bpConnections
  return props.bpConnections.filter(nc =>
    (nc.partner_name ?? '').toLowerCase().includes(q) ||
    (nc.partner_role ?? '').toLowerCase().includes(q)
  )
})

const filteredPartners = computed(() => {
  return businessPartners.value.filter(p => {
    const q = bpSearch.value.toLowerCase()
    if (q && !p.name.toLowerCase().includes(q) && !p.role.toLowerCase().includes(q) && !p.tags.some(t => t.toLowerCase().includes(q))) return false
    if (bpCategory.value && p.category !== bpCategory.value) return false
    return true
  })
})

const filteredRtCandidates = computed(() => {
  const q = rtSearch.value.toLowerCase()
  if (!q) return rtCandidates.value
  return rtCandidates.value.filter(s =>
    s.name.toLowerCase().includes(q) || s.role.toLowerCase().includes(q) || s.location.toLowerCase().includes(q)
  )
})

function removeRtCandidate(s) {
  rtCandidates.value = rtCandidates.value.filter(c => c.name !== s.name)
}

// ── Static display data ────────────────────────────────────────────────────
const recommendedCategories = [
  { label:'Psychiatrist',     desc:'Medication management',  count:3, icon:'pill-shape',  priority:'high'   },
  { label:'Therapist / LCSW', desc:'Ongoing psychotherapy', count:6, icon:'heart-2',     priority:'high'   },
  { label:'Neurologist',      desc:'Neuropsychiatric care',  count:2, icon:'globe',       priority:'medium' },
  { label:'Primary Care',     desc:'Care coordination',      count:4, icon:'home',        priority:'medium' },
  { label:'Dietician',        desc:'Eating & metabolism',    count:3, icon:'dollar-sign', priority:'medium' },
  { label:'Medical Billing',  desc:'Revenue cycle',          count:6, icon:'briefcase',   priority:'medium' },
  { label:'Credentialing',    desc:'Insurance & licensing',  count:2, icon:'shield',      priority:'medium' },
]

const aiShadowCandidates = ref([
  { name:'Dr. Rachel Moore, MD',  id:'', slug:'', initials:'RM', role:'Psychiatrist',             location:'New York, NY', tags:['Anxiety','Mood Disorders','PTSD'],       match:96, rating:4.3, telehealth:true,  connected:false },
  { name:'Sarah Nguyen, PsyD',    id:'', slug:'', initials:'SN', role:'Psychologist',             location:'Brooklyn, NY', tags:['CBT','Trauma','Depression'],             match:93, rating:4.7, telehealth:true,  connected:false },
  { name:'Maya Torres, LCSW',     id:'', slug:'', initials:'MT', role:'Licensed Clinical Social Worker', location:'Queens, NY', tags:['DBT','Anxiety','LGBTQ+'],      match:88, rating:4.8, telehealth:false, connected:false },
  { name:'James Okafor, LMFT',    id:'', slug:'', initials:'JO', role:'Marriage & Family Therapist',    location:'Bronx, NY',   tags:['Couples','Family Conflict','IFS'], match:90, rating:4.6, telehealth:false, connected:false },
  { name:'Alicia Reeves, LPC',    id:'', slug:'', initials:'AR', role:'Licensed Professional Counselor',location:'New York, NY', tags:['ACT','Stress','Life Transitions'], match:84, rating:4.8, telehealth:true,  connected:false },
  { name:'Nina Park, RDN',        id:'', slug:'', initials:'NP', role:'Registered Dietician',    location:'Manhattan, NY', tags:['Eating Disorders','Functional Nutrition'], match:92, rating:4.6, telehealth:true, connected:false },
])

const searchResults = ref([
  { name:'Dr. Daniel Malik, MD',  id:'', slug:'', initials:'DM', role:'Psychiatrist',          location:'NYC, NY',       tags:['Anxiety','PTSD','Mood Disorders'],             rating:4.9, reviews:62, refs:'14 refs', acc:'80% acc', resp:'3.1h resp', telehealth:true,  networkStatus:'in-network'    },
  { name:'Dr. Lisa Chen, PhD',    id:'', slug:'', initials:'LC', role:'Psychologist',           location:'Brooklyn, NY',  tags:['CBT','Depression','Trauma'],                   rating:4.7, reviews:38, refs:'8 refs',  acc:'88% acc', resp:'2.0h resp', telehealth:true,  networkStatus:'in-network'    },
  { name:'Dr. Marcus Webb, LCSW', id:'', slug:'', initials:'MW', role:'Therapist / Counselor',  location:'Queens, NY',    tags:['DBT','Family Therapy','Addiction'],             rating:4.8, reviews:51, refs:'0 refs',  acc:'—',       resp:'2.1h resp', telehealth:false, networkStatus:'not-connected' },
  { name:'Dr. Aisha Patel, PsyD', id:'', slug:'', initials:'AP', role:'Psychologist',           location:'Manhattan, NY', tags:['Child & Adolescent','ADHD','Autism'],          rating:4.8, reviews:29, refs:'0 refs',  acc:'—',       resp:'4.5h resp', telehealth:false, networkStatus:'pending'       },
  { name:'Dr. James Torres, MD',  id:'', slug:'', initials:'JT', role:'Psychiatrist',           location:'Newark, NJ',    tags:['Geriatric Psych','Dementia','Medication Mgmt'],rating:4.5, reviews:18, refs:'5 refs',  acc:'80% acc', resp:'5.0h resp', telehealth:true,  networkStatus:'in-network'    },
  { name:'Dr. Sofia Kim, MD',     id:'', slug:'', initials:'SK', role:'Neurologist',            location:'Bronx, NY',     tags:['Epilepsy','Headaches','Neurocognitive'],        rating:4.8, reviews:44, refs:'0 refs',  acc:'—',       resp:'2.8h resp', telehealth:false, networkStatus:'not-connected' },
])

const filterGroups = reactive([
  { label:'Provider Type',        open:false, icon:'user'        },
  { label:'Specialties',          open:false, icon:'star'        },
  { label:'Treatment Approaches', open:false, icon:'book-open'   },
  { label:'Insurance Accepted',   open:false, icon:'credit-card' },
  { label:'Format & Services',    open:false, icon:'monitor'     },
  { label:'Location',             open:false, icon:'map-pin'     },
  { label:'Credentials',          open:false, icon:'shield'      },
  { label:'Rate & Availability',  open:false, icon:'clock'       },
  { label:'Provider Demographics',open:false, icon:'users'       },
])

const businessPartners = ref([
  { name:'Marisol Vega',       id:'', initials:'MV', avatarColor:'var(--gold-dark)', partnerType:'FREELANCER',  role:'Medical Billing',     location:'Miami, FL · Remote',   tags:['Medical Billing','AR Management','Denial Mgmt'],         rate:'$85/hr',  reviews:147, jobs:92,  rating:4.9, category:'Medical Billing'    },
  { name:'Riya Patel',         id:'', initials:'RP', avatarColor:'var(--gold-dark)', partnerType:'SOLOPRENEUR', role:'Digital Marketing',   location:'Austin, TX · Remote',  tags:['SEO','Google Ads','Social Media'],                        rate:'$70/hr',  reviews:204, jobs:78,  rating:4.8, category:'Digital Marketing'  },
  { name:'Kevin Osei',         id:'', initials:'KO', avatarColor:'var(--gold-dark)', partnerType:'CONSULTANT',  role:'Credentialing',       location:'Atlanta, GA · Remote', tags:['CAQH','PECOS','Re-credentialing'],                        rate:'$55/hr',  reviews:310, jobs:130, rating:4.9, category:'Credentialing'      },
  { name:'Jae Won Park',       id:'', initials:'JP', avatarColor:'var(--gold-dark)', partnerType:'CONSULTANT',  role:'Practice Consulting', location:'Boston, MA · Remote',  tags:['Practice Management','Revenue Optimization','Operations'], rate:'$220/hr', reviews:72,  jobs:48,  rating:4.9, category:'Practice Consulting' },
  { name:'Apex Billing Co.',   id:'', initials:'AB', avatarColor:'var(--gold-dark)', partnerType:'AGENCY',      role:'Medical Billing',     location:'Chicago, IL · Remote', tags:['Revenue Cycle','Credentialing','Claims Processing'],       rate:'$150/hr', reviews:89,  jobs:204, rating:4.7, category:'Medical Billing'    },
  { name:'Daniel Torres, CPA', id:'', initials:'DT', avatarColor:'var(--gold-dark)', partnerType:'FREELANCER',  role:'Accounting / CPA',   location:'New York, NY · Remote',tags:['Tax Planning','GAAP','Practice Valuation'],                rate:'$175/hr', reviews:83,  jobs:41,  rating:4.8, category:'Accounting / CPA'   },
  { name:'Bright Minds Admin', id:'', initials:'BA', avatarColor:'var(--gold-dark)', partnerType:'AGENCY',      role:'Admin / VA',          location:'Phoenix, AZ · Remote', tags:['Virtual Assistants','Scheduling','Data Entry'],            rate:'$35/hr',  reviews:175, jobs:295, rating:4.6, category:'Admin / VA'         },
  { name:'StaffLink HR Group', id:'', initials:'SH', avatarColor:'var(--gold-dark)', partnerType:'FIRM',        role:'HR / Staffing',       location:'Dallas, TX',           tags:['Recruitment','Benefits Admin','Compliance'],               rate:'$120/hr', reviews:44,  jobs:82,  rating:4.8, category:'HR / Staffing'      },
])

const bpFilterGroups = [
  { label:'Partner Type',     icon:'users'       },
  { label:'Hourly Rate',      icon:'dollar-sign' },
  { label:'Experience Level', icon:'star'        },
  { label:'Availability',     icon:'clock'       },
  { label:'Engagement Type',  icon:'briefcase'   },
  { label:'Work Location',    icon:'map-pin'     },
]

const rtCandidates = ref([
  { name:'Alicia Reeves, LPC',     id:'', slug:'', initials:'AR', role:'Licensed Professional Counselor',  location:'New York, NY', tags:['ACT','Stress','Life Transitions'] },
  { name:'Carol Huang, CDE',       id:'', slug:'', initials:'CH', role:'Certified Diabetes Educator',      location:'Manhattan, NY', tags:['Diabetes','Blood Sugar','Lifestyle Medicine'] },
  { name:'Danielle Fox, PMHNP',    id:'', slug:'', initials:'DF', role:'Psychiatric Nurse Practitioner',   location:'New York, NY', tags:['Medication Mgmt','ADHD','Depression'] },
  { name:'Devon Hall, CADC',       id:'', slug:'', initials:'DH', role:'Certified Addiction Counselor',    location:'Harlem, NY',   tags:['Substance Use','Relapse Prevention','MI'] },
  { name:'Dr. Aisha Patel, PsyD',  id:'', slug:'', initials:'AP', role:'Psychologist',                    location:'Queens, NY',   tags:['Family Therapy','Cultural Competence'] },
  { name:'Dr. Amara Osei, LCSW',   id:'', slug:'', initials:'AO', role:'Clinical Social Worker',          location:'Brooklyn, NY', tags:['Trauma','BIPOC Care','CBT'] },
  { name:'Dr. Diana Vasquez, PhD', id:'', slug:'', initials:'DV', role:'Clinical Psychologist',           location:'Houston, TX',  tags:['Trauma','DBT','EMDR'] },
  { name:'Dr. Elena Rodriguez, RD',id:'', slug:'', initials:'ER', role:'Registered Dietitian',            location:'Manhattan, NY',tags:['Eating Disorders','Nutrition','Mental Health'] },
])

// ── Utility ────────────────────────────────────────────────────────────────
function tagList(str) {
  return (str || '').split(',').map(s => s.trim()).filter(Boolean)
}

function timeAgo(iso) {
  if (!iso) return ''
  const days = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000)
  if (days === 0) return 'Today'
  if (days === 1) return 'Yesterday'
  if (days < 7)  return days + 'd ago'
  return new Date(iso).toLocaleDateString('en-US', { month:'short', day:'numeric' })
}
</script>

<style scoped>
/* ══════════════════════════════════════════════════════
   OVERFLOW CONTRACT — nothing in this file may produce
   horizontal scroll. All grids use auto-fill or 100%.
   All flex rows either wrap or are inside a slider track.
   ══════════════════════════════════════════════════════ */

/* ── Page root ────────────────────────────────────────── */
.nw-page-root { width:100%; overflow-x:hidden; }
* { box-sizing:border-box; }

/* ── Hero actions ─────────────────────────────────────── */
.nw-icon-btn { display:inline-flex; align-items:center; gap:6px; }

/* ── Pending connection requests ─────────────────────── */
.section-block {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius-lg); padding:18px 20px; margin-bottom:14px;
  box-shadow:var(--shadow-xs); width:100%;
}
.section-head { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px; }
.section-head-eyebrow { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:3px; }
.section-head-title   { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); margin:0 0 2px; }
.section-head-sub     { font-size:12px; color:var(--text-3); margin:0; }
.list-grid { display:flex; gap:12px; flex-wrap:wrap; width:100%; }
.card.is-person { background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:14px 16px; flex:1 1 200px; max-width:280px; }
.card-top   { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.card-time  { font-size:11px; color:var(--text-4); }
.badge.is-quiet.is-clinical { font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; background:var(--green-light); color:var(--green-dark); border:1px solid var(--soft-green); display:inline-flex; align-items:center; gap:4px; }
.badge.is-quiet.is-business { font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; background:var(--blue-light);  color:var(--blue-dark);  border:1px solid var(--soft-blue);  display:inline-flex; align-items:center; gap:4px; }
.person-row    { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; }
.person-avatar { width:36px; height:36px; border-radius:8px; background:var(--gold-dark); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.person-name   { font-size:13px; font-weight:700; color:var(--text); line-height:1.3; }
.person-meta   { font-size:11px; color:var(--text-3); margin-top:1px; }
.card-actions  { display:flex; gap:6px; }

/* ── Main tabs (two-tier) ─────────────────────────────── */


/* ── Recommended section header ───────────────────────── */
.rec-section        { margin-bottom:22px; width:100%; overflow:hidden; }
.rec-section-shadow { margin-top:28px; }
.rec-header   { margin-bottom:6px; }
.rec-title    { display:inline-flex; align-items:center; gap:7px; font-size:14px; font-weight:700; color:var(--text); flex-wrap:wrap; }
.rec-sub      { font-size:12px; color:var(--text-3); margin:4px 0 12px; line-height:1.5; }
.badge-ai       { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:3px 8px; border-radius:99px; background:rgba(74,144,196,.1); color:var(--blue-dark); border:1px solid var(--soft-blue); }
.badge-ai-green { background:rgba(76,175,125,.1); color:var(--green-dark); border-color:var(--soft-green); }
.ai-shadow-label { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-4); text-align:center; margin:4px 0 14px; }

/* ── Slider wrapper ───────────────────────────────────── */
.nw-slider-wrap { display:flex; align-items:center; gap:8px; width:100%; min-width:0; }
.nw-slider-arrow {
  display:inline-flex; align-items:center; justify-content:center;
  width:32px; height:32px; border-radius:50%; flex-shrink:0;
  border:1.5px solid var(--border); background:var(--surface);
  color:var(--text-3); cursor:pointer; transition:all .15s; box-shadow:var(--shadow-sm);
}
.nw-slider-arrow:hover:not(:disabled) { border-color:var(--gold-dark); color:var(--gold-dark); background:var(--badge-bg-gold); }
.nw-slider-arrow:disabled  { opacity:.3; cursor:default; }
.nw-slider-arrow-left  { margin-right:8px; }
.nw-slider-arrow-right { margin-left:8px; }

/* ── RNP (Recommended Network Partners) scroll track ──── */
.rnp-scroll {
  display:flex; gap:10px; flex:1; width:0; min-width:0;
  overflow-x:auto; scroll-behavior:smooth; padding-bottom:4px;
  scrollbar-width:none; -ms-overflow-style:none;
}
.rnp-scroll::-webkit-scrollbar { display:none; }
.rnp-card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg);
  padding:18px 18px; min-width:190px; max-width:210px; flex-shrink:0;
  cursor:pointer; transition:box-shadow .15s,transform .15s; box-shadow:var(--shadow-sm);
}
.rnp-card:hover { box-shadow:var(--shadow); transform:translateY(-2px); }
.rnp-icon { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--badge-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
.rnp-name { font-size:14px; font-weight:700; color:var(--text); margin-bottom:4px; }
.rnp-meta { font-size:12px; color:var(--text-4); margin-bottom:10px; }
.rnp-foot { display:flex; align-items:center; justify-content:space-between; }
.rnp-stat { font-size:11px; color:var(--text-3); }
.rnp-num  { font-weight:700; color:var(--text); }
.rnp-cta  { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; border:1.5px solid var(--border); background:transparent; color:var(--text-3); cursor:pointer; transition:all .15s; }
.rnp-cta:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* ── SPC (Shadow Provider) card — used in slider AND grid ─ */
.spc-track {
  display:flex; gap:12px; flex:1; width:0; min-width:0;
  overflow-x:auto; scroll-behavior:smooth; padding-bottom:4px;
  scrollbar-width:none; -ms-overflow-style:none;
}
.spc-track::-webkit-scrollbar { display:none; }

/* Grid variant (My Network / My Partners) */
.spc-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(170px, 1fr));
  gap:12px; margin-bottom:22px; width:100%;
}

.spc-card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg);
  padding:40px 16px 14px; position:relative; cursor:pointer;
  transition:box-shadow .18s,transform .18s; box-shadow:var(--shadow-sm);
  display:flex; flex-direction:column;
  /* slider: fixed width; grid: fills cell */
  min-width:200px; max-width:220px; flex-shrink:0;
}
/* In grid context, override fixed width so it fills the column */
.spc-grid .spc-card { min-width:0; max-width:none; flex-shrink:1; }

.spc-card:hover  { box-shadow:var(--shadow); transform:translateY(-2px); }
.spc-pills  { position:absolute; top:10px; left:12px; display:flex; gap:5px; }
.spc-pill-ai  { width:22px; height:22px; border-radius:50%; background:rgba(160,129,62,.12); color:var(--gold-dark); display:inline-flex; align-items:center; justify-content:center; }
.spc-pill-svc { width:22px; height:22px; border-radius:50%; background:var(--surface-2); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.spc-match-badge { display:inline-flex; align-items:center; justify-content:center; padding:2px 7px; border-radius:99px; font-size:10px; font-weight:700; background:var(--green-light); color:var(--green-dark); border:1px solid var(--soft-green); }
.spc-status  { width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; }
.spc-status.ok   { background:var(--green-light); color:var(--green-dark); }
.spc-status.pend { background:var(--orange-light); color:var(--orange-dark); }
.spc-status.off  { background:var(--surface-2); color:var(--text-3); }
.spc-rating { position:absolute; top:12px; right:12px; font-size:11px; font-weight:700; color:var(--gold-dark); display:inline-flex; align-items:center; gap:3px; }
.spc-body   { display:flex; flex-direction:column; align-items:center; text-align:center; flex:1; }
.spc-avatar { width:60px; height:60px; border-radius:var(--radius); background:var(--gold-dark); color:#fff; font-size:18px; font-weight:700; display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
.spc-avatar-lg { width:66px; height:66px; border-radius:var(--radius-lg); font-size:20px; }
.spc-name   { font-size:12px; font-weight:700; color:var(--text); line-height:1.3; margin-bottom:2px; }
.spc-role   { font-size:11px; color:var(--text-4); margin-bottom:4px; }
.spc-loc    { display:inline-flex; align-items:center; gap:3px; font-size:11px; color:var(--text-4); margin-bottom:8px; }
.spc-tags   { display:flex; flex-wrap:wrap; justify-content:center; gap:4px; margin-bottom:8px; }
.spc-tag    { font-size:10px; color:var(--text-3); background:var(--surface-2); border:1px solid var(--border); border-radius:99px; padding:2px 7px; }
.spc-stats-row { font-size:11px; color:var(--text-4); margin-bottom:10px; text-align:center; padding:8px 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
.spc-actions { display:flex; align-items:center; justify-content:center; gap:5px; flex-wrap:wrap; padding-top:8px; border-top:1px solid var(--border); }

/* ── Shared icon button ────────────────────────────────── */
.btn-icon { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; flex-shrink:0; }
.btn-icon:hover:not(:disabled) { border-color:var(--gold-dark); color:var(--gold-dark); }
.btn-icon:disabled { opacity:.5; cursor:not-allowed; }
.nw-added-pill { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border:none; border-radius:99px; padding:2px 8px; }

/* ── Search layout (sidebar + results) ────────────────── */
.nw-search-header-label { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-4); text-align:center; margin:4px 0 16px; }
.nw-search-layout { display:grid; grid-template-columns:220px 1fr; gap:18px; width:100%; }
.nw-results       { min-width:0; width:100%; }
.nw-results-bar   { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px; font-size:12px; color:var(--text-3); }
.nw-results-count { font-size:12px; color:var(--text-3); }
.spc-results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; width:100%; }
/* In search results, cards fill column — override slider fixed width */
.spc-results-grid .spc-card { min-width:0; max-width:none; flex-shrink:1; }
.search-provider-card {}
.nw-load-more { display:flex; justify-content:center; margin-top:20px; padding-bottom:8px; }

/* ── Sidebar / filters ────────────────────────────────── */
.nw-sidebar      { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; height:fit-content; min-width:0; }
.nw-sidebar-top  { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.nw-sidebar-label { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-3); }
.nw-clear-btn    { font-size:10px; font-weight:700; color:var(--gold-dark); background:transparent; border:none; cursor:pointer; }
.nw-filter-group { margin-bottom:2px; }
.nw-filter-btn   { display:flex; align-items:center; gap:7px; width:100%; padding:8px 4px; font-size:12px; font-weight:500; color:var(--text-2); background:transparent; border:none; cursor:pointer; border-bottom:1px solid var(--border); }
.nw-filter-btn:hover { color:var(--text); }
.nw-apply-btn    { display:block; width:100%; margin-top:14px; padding:10px; font-size:12px; font-weight:700; background:var(--gold-dark); color:#fff; border:none; border-radius:8px; cursor:pointer; transition:background .15s; }
.nw-apply-btn:hover { background:var(--gold); }
.nw-filter-expand  { padding:8px 4px 4px; }
.nw-filter-search  { font-size:12px; padding:6px 10px; margin-bottom:8px; width:100%; }
.nw-filter-check   { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--text-2); padding:4px 2px; cursor:pointer; }
.nw-filter-check:hover { color:var(--text); }
.nw-filter-check input { accent-color:var(--gold-dark); }

/* ── Toolbar / results bar ────────────────────────────── */
.pn-toolbar     { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px; width:100%; }
.pn-search-wrap { position:relative; flex:1; min-width:0; }
.pn-search-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); display:flex; align-items:center; color:var(--text-3); pointer-events:none; }
.pn-results-bar { font-size:12px; color:var(--text-3); margin-bottom:14px; }

/* ── Stat chips row ───────────────────────────────────── */
.stat-chips-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:22px; width:100%; }
.stat-chip      { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; display:flex; align-items:center; gap:14px; box-shadow:var(--shadow-sm); min-width:0; }
.stat-chip-icon { width:36px; height:36px; border-radius:var(--radius); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stat-chip-value { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; margin-bottom:4px; }
.stat-chip-label { font-size:11px; font-weight:600; color:var(--text-3); text-transform:uppercase; letter-spacing:.05em; }

/* ── Business Partners ────────────────────────────────── */
.bp-search-bar { display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap; width:100%; }
.bp-count      { font-size:12px; color:var(--text-3); margin-bottom:14px; }
.bp-layout     { display:grid; grid-template-columns:220px 1fr; gap:18px; width:100%; }
.bp-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:10px 4px; border-bottom:1px solid var(--border); margin-bottom:4px; }
.bp-toggle     { position:relative; display:inline-block; width:36px; height:20px; flex-shrink:0; }
.bp-toggle input { opacity:0; width:0; height:0; }
.bp-track      { position:absolute; inset:0; background:var(--border-dark); border-radius:99px; cursor:pointer; transition:background .2s; }
.bp-toggle input:checked + .bp-track { background:var(--gold-dark); }
.bp-thumb      { position:absolute; width:16px; height:16px; left:2px; top:2px; background:#fff; border-radius:50%; transition:transform .2s; }
.bp-toggle input:checked + .bp-track .bp-thumb { transform:translateX(16px); }
.bp-grid       { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; width:100%; }
.biz-grid-card { display:flex; flex-direction:column; min-width:0; }
.bp-card-top   { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.bp-type-badge { font-size:10px; font-weight:700; letter-spacing:.05em; padding:2px 7px; border-radius:99px; background:var(--badge-bg-gold); color:var(--gold-dark); border:1px solid var(--fade-gold); }
.bp-card-rating { font-size:11px; font-weight:600; color:var(--gold-dark); display:inline-flex; align-items:center; gap:2px; }
.bp-avatar-row { display:flex; justify-content:flex-start; margin-bottom:8px; }
.bp-avatar     { width:44px; height:44px; border-radius:10px; color:#fff; font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; }
.bp-card-name  { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.bp-card-role  { font-size:11px; color:var(--text-4); margin-bottom:2px; }
.bp-card-loc   { font-size:11px; color:var(--text-4); margin-bottom:8px; }
.bp-card-stats { font-size:11px; color:var(--text-4); margin-bottom:10px; padding-top:8px; border-top:1px solid var(--border); }

/* ── Referrals & Tools ────────────────────────────────── */
.rt-section-title { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); margin-bottom:12px; }
.rt-ai-banner { display:flex; align-items:flex-start; gap:12px; background:rgba(160,129,62,.07); border:1px solid var(--fade-gold); border-radius:10px; padding:14px 16px; margin-bottom:16px; box-shadow:var(--shadow-sm); width:100%; }
.rt-ai-icon   { width:32px; height:32px; border-radius:8px; background:rgba(160,129,62,.12); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rt-ai-title  { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.rt-ai-sub    { font-size:12px; color:var(--text-3); line-height:1.55; }
.rt-ai-sub strong { color:var(--text); }
.rt-grid      { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; width:100%; }
.rt-card      { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 14px; position:relative; transition:box-shadow .15s,transform .15s; cursor:pointer; display:flex; flex-direction:column; align-items:center; text-align:center; box-shadow:var(--shadow-sm); min-width:0; }
.rt-card:hover { box-shadow:var(--shadow); transform:translateY(-2px); }
.rt-shadow-icon { position:absolute; top:12px; left:12px; color:var(--gold-dark); opacity:.6; }
.rt-avatar    { width:44px; height:44px; border-radius:10px; background:var(--gold-dark); color:#fff; font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; margin:8px auto 10px; }
.rt-name      { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.rt-role      { font-size:11px; color:var(--text-4); margin-bottom:2px; }
.rt-loc       { font-size:11px; color:var(--text-4); margin-bottom:10px; }
.rt-actions   { display:flex; justify-content:center; gap:6px; padding-top:10px; border-top:1px solid var(--border); width:100%; }
.rt-remove-btn:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }

/* ── Review Requests modal internals ──────────────────── */
.nw-modal-list   { max-height:380px; overflow-y:auto; }
.nw-modal-row    { display:flex; align-items:flex-start; gap:12px; padding:14px 0; border-bottom:1px solid var(--border); }
.nw-modal-row:last-child { border-bottom:none; }
.nw-modal-avatar { width:38px; height:38px; border-radius:50%; background:var(--gold-dark); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.nw-modal-info   { flex:1; min-width:0; }
.nw-modal-name   { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.nw-modal-meta   { font-size:12px; color:var(--text-4); margin-bottom:5px; }
.nw-modal-quote  { font-size:12px; color:var(--text-3); font-style:italic; line-height:1.5; }
.nw-modal-right  { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.nw-modal-btns   { display:flex; align-items:center; gap:6px; flex-shrink:0; }

/* ── Responsive ───────────────────────────────────────── */
@media (max-width:1024px) {
  .spc-results-grid { grid-template-columns:1fr; }
}
@media (max-width:900px) {
  .nw-search-layout { grid-template-columns:1fr; }
  .bp-layout        { grid-template-columns:1fr; }
}
@media (max-width:600px) {
  .stat-chips-row { grid-template-columns:1fr 1fr; }
  .spc-results-grid { grid-template-columns:1fr; }
}
</style>
