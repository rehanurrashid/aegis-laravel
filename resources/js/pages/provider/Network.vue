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
        <a :href="route('provider.activity', { module: 'network' })" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" @click="modals.inviteProvider = true">
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
        <button type="button" class="btn btn-outline btn-sm" @click="modals.reviewRequests = true">
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
            <button type="button" class="btn btn-primary btn-sm" :disabled="pendingActionId === req.id" @click="acceptRequest(req)">
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
        <div class="rec-section-header">
          <div class="rec-section-title">
            <h3><AegisIcon name="lightbulb" :size="16" /> Recommended Network Partners</h3>
            <span class="rec-ai-info" data-tooltip="AI-suggested specialists based on your referral patterns">AI Suggested <AegisIcon name="info" :size="11" /></span>
          </div>
        </div>
        <div class="rec-section-subtitle">Based on your referral history and the specialties your clients ask for most</div>
        <div class="nw-slider-wrap">
          <button type="button" class="nw-slider-arrow nw-slider-arrow-left" @click="slideRnp(-1)" :disabled="rnpAtStart" aria-label="Previous">
            <AegisIcon name="chevron-left" :size="16" />
          </button>
          <div class="rec-partner-grid" ref="rnpTrack">
            <article
              v-for="cat in recommendedCategories"
              :key="cat.label"
              class="card rnp-card"
              :class="cat.tier"
            >
              <div class="rnp-head">
                <div class="rnp-icon"><AegisIcon :name="cat.icon" :size="14" /></div>
                <div class="rnp-title-block">
                  <div class="rnp-name">{{ cat.label }}</div>
                  <div class="rnp-meta">{{ cat.desc }}</div>
                </div>
                <span class="badge" :class="cat.tier === 'is-high' ? 'priority-high' : 'priority-medium'"></span>
              </div>
              <div class="rnp-foot">
                <span class="rnp-stat">
                  <span class="rnp-stat-num">{{ cat.count }}</span> nearby
                </span>
                <button type="button" class="btn rnp-cta" @click.stop="clinicalTab = 'search'">
                  <AegisIcon name="arrow-right" :size="12" />
                </button>
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
        <div class="rec-section-header">
          <div class="rec-section-title">
            <h3><AegisIcon name="cpu" :size="16" /> Recommended Shadow Providers</h3>
            <span class="rec-ai-info" data-tooltip="AI matching based on your clinical profile">AI Matched <AegisIcon name="cpu" :size="10" /></span>
          </div>
        </div>
        <div class="rec-section-subtitle">A curated referral resource that helps identify providers who closely mirror your background and practice profile — Including region, specialty, demographics, services, and payment type.</div>
        <div class="nw-slider-wrap">
          <button type="button" class="nw-slider-arrow nw-slider-arrow-left" @click="slideSpc(-1)" :disabled="spcAtStart" aria-label="Previous">
            <AegisIcon name="chevron-left" :size="16" />
          </button>
          <div class="rec-shadow-grid" ref="spcTrack">
            <div v-for="p in aiShadowCandidates" :key="p.name" class="card rsp-card" @click="viewProfile(p.slug)">
              <header class="rsp-head">
                <span class="rsp-match" :data-tooltip="p.match + '% AI match based on your clinical focus and referral patterns'">
                  <AegisIcon name="sparkle-cluster" :size="10" /> {{ p.match }}%
                </span>
                <span class="rsp-rating" :data-tooltip="p.rating + ' from peer reviews'">
                  <AegisIcon name="star" :size="10" :filled="true" /> {{ p.rating }}
                </span>
              </header>
              <div class="rsp-body">
                <div class="rsp-avatar">{{ p.initials }}</div>
                <div class="rsp-info">
                  <div class="rsp-name">{{ p.name }}</div>
                  <div class="rsp-role">{{ p.role }}</div>
                  <div class="rsp-loc"><AegisIcon name="map-pin" :size="9" /> {{ p.location }}</div>
                </div>
              </div>
              <ul class="rsp-tags">
                <li v-for="tag in p.tags.slice(0,3)" :key="tag" class="rsp-tag">{{ tag }}</li>
                <li v-if="p.tags.length > 3" class="rsp-tag">+{{ p.tags.length - 3 }}</li>
              </ul>
              <footer class="rsp-foot" @click.stop>
                <button type="button" class="rsp-act" data-tooltip="Message" :disabled="msgLoading === p.id" @click="openConversation(p.id)"><AegisIcon name="message-square" :size="13" /></button>
                <button type="button" class="rsp-act" data-tooltip="View Profile" @click="viewProfile(p.slug)"><AegisIcon name="eye" :size="13" /></button>
                <button v-if="!p.connected" type="button" class="rsp-connect" @click="openConnect(p)"><AegisIcon name="user-plus" :size="12" /> Connect</button>
                <span v-else class="rsp-connect is-connected"><AegisIcon name="check" :size="12" /> Added</span>
              </footer>
            </div>
          </div>
          <button type="button" class="nw-slider-arrow nw-slider-arrow-right" @click="slideSpc(1)" :disabled="spcAtEnd" aria-label="Next">
            <AegisIcon name="chevron-right" :size="16" />
          </button>
        </div>
      </div>

      <!-- Search Results Layout -->
      <div class="rec-divider-label nw-search-divider">SEARCH RESULTS</div>
      <div class="search-layout">
        <!-- Filters sidebar — 100% PHP class names -->
        <aside class="filter-sidebar" id="filterSidebar">
          <div class="filter-sidebar-header">
            <div class="filter-sidebar-title"><AegisIcon name="filter" :size="14" /> Filters</div>
            <button type="button" class="filter-clear-btn" @click="clearFilters">Clear All</button>
          </div>

          <!-- Active filter pills -->
          <div v-if="activeFilters.length" class="active-filters-row">
            <span
              v-for="f in activeFilters"
              :key="f.value + f.group"
              class="active-filter-pill"
              @click="removeFilter(f)"
            >{{ f.value }}<span class="pill-x">&times;</span></span>
          </div>

          <!-- 1. Provider Type -->
          <div class="filter-group" :class="{ open: openGroups.type }">
            <div class="filter-group-header" @click="toggleGroup('type')">
              <span class="filter-group-label">
                <AegisIcon name="users" :size="16" /> Provider Type
                <span v-if="countGroup('type')" class="filter-group-count visible">{{ countGroup('type') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <input class="filter-inner-search" type="text" placeholder="Search types..." v-model="search.type" />
              <div>
                <span
                  v-for="opt in filteredOpts(providerTypes, search.type)"
                  :key="opt"
                  class="ftag"
                  :class="{ selected: isSelected('type', opt) }"
                  @click="toggleFilter('type', opt)"
                >{{ opt }}</span>
              </div>
            </div>
          </div>

          <!-- 2. Specialties -->
          <div class="filter-group" :class="{ open: openGroups.specialty }">
            <div class="filter-group-header" @click="toggleGroup('specialty')">
              <span class="filter-group-label">
                <AegisIcon name="star" :size="16" /> Specialties
                <span v-if="countGroup('specialty')" class="filter-group-count visible">{{ countGroup('specialty') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <input class="filter-inner-search" type="text" placeholder="Search specialties..." v-model="search.specialty" />
              <template v-for="(group, label) in specialtyGroups" :key="label">
                <div class="filter-subcat">{{ label }}</div>
                <span
                  v-for="opt in filteredOpts(group, search.specialty)"
                  :key="opt"
                  class="ftag"
                  :class="{ selected: isSelected('specialty', opt) }"
                  @click="toggleFilter('specialty', opt)"
                >{{ opt }}</span>
              </template>
            </div>
          </div>

          <!-- 3. Treatment Approaches -->
          <div class="filter-group" :class="{ open: openGroups.approach }">
            <div class="filter-group-header" @click="toggleGroup('approach')">
              <span class="filter-group-label">
                <AegisIcon name="leaves" :size="16" /> Treatment Approaches
                <span v-if="countGroup('approach')" class="filter-group-count visible">{{ countGroup('approach') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <input class="filter-inner-search" type="text" placeholder="Search approaches..." v-model="search.approach" />
              <template v-for="(group, label) in approachGroups" :key="label">
                <div class="filter-subcat">{{ label }}</div>
                <span
                  v-for="opt in filteredOpts(group, search.approach)"
                  :key="opt"
                  class="ftag"
                  :class="{ selected: isSelected('approach', opt) }"
                  @click="toggleFilter('approach', opt)"
                >{{ opt }}</span>
              </template>
            </div>
          </div>

          <!-- 4. Insurance Accepted -->
          <div class="filter-group" :class="{ open: openGroups.insurance }">
            <div class="filter-group-header" @click="toggleGroup('insurance')">
              <span class="filter-group-label">
                <AegisIcon name="credit-card" :size="16" /> Insurance Accepted
                <span v-if="countGroup('insurance')" class="filter-group-count visible">{{ countGroup('insurance') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <input class="filter-inner-search" type="text" placeholder="Search insurance..." v-model="search.insurance" />
              <template v-for="(group, label) in insuranceGroups" :key="label">
                <div class="filter-subcat">{{ label }}</div>
                <span
                  v-for="opt in filteredOpts(group, search.insurance)"
                  :key="opt"
                  class="ftag"
                  :class="{ selected: isSelected('insurance', opt) }"
                  @click="toggleFilter('insurance', opt)"
                >{{ opt }}</span>
              </template>
            </div>
          </div>

          <!-- 5. Format & Services -->
          <div class="filter-group" :class="{ open: openGroups.format }">
            <div class="filter-group-header" @click="toggleGroup('format')">
              <span class="filter-group-label">
                <AegisIcon name="monitor" :size="16" /> Format &amp; Services
                <span v-if="countGroup('format')" class="filter-group-count visible">{{ countGroup('format') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <template v-for="(group, label) in formatGroups" :key="label">
                <div class="filter-subcat">{{ label }}</div>
                <span
                  v-for="opt in filteredOpts(group, '')"
                  :key="opt"
                  class="ftag"
                  :class="{ selected: isSelected('format', opt) }"
                  @click="toggleFilter('format', opt)"
                >{{ opt }}</span>
              </template>
            </div>
          </div>

          <!-- 6. Location -->
          <div class="filter-group" :class="{ open: openGroups.location }">
            <div class="filter-group-header" @click="toggleGroup('location')">
              <span class="filter-group-label">
                <AegisIcon name="map-pin" :size="16" /> Location
                <span v-if="countGroup('location')" class="filter-group-count visible">{{ countGroup('location') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <template v-for="(group, label) in locationGroups" :key="label">
                <div class="filter-subcat">{{ label }}</div>
                <span
                  v-for="opt in filteredOpts(group, '')"
                  :key="opt"
                  class="ftag"
                  :class="{ selected: isSelected('location', opt) }"
                  @click="toggleFilter('location', opt)"
                >{{ opt }}</span>
              </template>
            </div>
          </div>

          <!-- 7. Credentials -->
          <div class="filter-group" :class="{ open: openGroups.credentials }">
            <div class="filter-group-header" @click="toggleGroup('credentials')">
              <span class="filter-group-label">
                <AegisIcon name="shield" :size="16" /> Credentials
                <span v-if="countGroup('credentials')" class="filter-group-count visible">{{ countGroup('credentials') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span v-for="opt in credentialTypes" :key="opt" class="ftag" :class="{ selected: isSelected('credentials', opt) }" @click="toggleFilter('credentials', opt)">{{ opt }}</span>
            </div>
          </div>

          <!-- 8. Rate & Availability -->
          <div class="filter-group" :class="{ open: openGroups.rate }">
            <div class="filter-group-header" @click="toggleGroup('rate')">
              <span class="filter-group-label">
                <AegisIcon name="clock" :size="16" /> Rate &amp; Availability
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span v-for="opt in rateTypes" :key="opt" class="ftag" :class="{ selected: isSelected('rate', opt) }" @click="toggleFilter('rate', opt)">{{ opt }}</span>
            </div>
          </div>

          <!-- 9. Provider Demographics -->
          <div class="filter-group" :class="{ open: openGroups.demographics }">
            <div class="filter-group-header" @click="toggleGroup('demographics')">
              <span class="filter-group-label">
                <AegisIcon name="users" :size="16" /> Provider Demographics
                <span v-if="countGroup('demographics')" class="filter-group-count visible">{{ countGroup('demographics') }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span v-for="opt in demographicTypes" :key="opt" class="ftag" :class="{ selected: isSelected('demographics', opt) }" @click="toggleFilter('demographics', opt)">{{ opt }}</span>
            </div>
          </div>
          <div class="filter-sidebar-apply">
            <button type="button" class="btn btn-primary" @click="applyFilters">Apply Filters</button>
          </div>
        </aside>
        <!-- Results grid -->
        <div class="results-panel">
          <div class="results-topbar">
            <span class="results-count">{{ searchResults.length }} providers found in your region</span>
            <div style="display:flex;align-items:center;gap:6px">
              <span style="font-size:12px;color:var(--text-4)">Sort:</span>
              <select class="form-select" v-model="searchSort" style="font-size:12px;padding:4px 8px;min-width:160px">
                <option>Best Match</option><option>Highest Rated</option><option>Closest</option>
              </select>
            </div>
          </div>
          <div class="search-results-grid">
            <div
              v-for="p in searchResults"
              :key="p.name"
              class="spc-card"
              @click="viewProfile(p.slug)"
            >
              <div class="spc-top-pills">
                <span
                  class="spc-status-icon"
                  :class="p.networkStatus === 'in-network' ? 'ok' : (p.networkStatus === 'pending' ? 'pend' : 'off')"
                  :data-tooltip="p.networkStatus === 'in-network' ? 'In Network' : (p.networkStatus === 'pending' ? 'Request Pending' : 'Not Connected')"
                >
                  <AegisIcon :name="p.networkStatus === 'in-network' ? 'user-check' : 'user-plus'" :size="12" />
                </span>
                <span v-if="p.telehealth" class="spc-svc-icon" data-tooltip="Telehealth available"><AegisIcon name="video" :size="12" /></span>
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
              <div class="spc-stats">{{ p.refs }} · {{ p.acc }} · {{ p.resp }}</div>
              <div class="spc-actions" @click.stop>
                <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === p.id" @click="openConversation(p.id)"><AegisIcon name="message-square" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForProvider(p)"><AegisIcon name="share-tree" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="Request Service" @click="openSvcRequest('Services', p.name)"><AegisIcon name="briefcase-rx" :size="14" /></button>
                <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(p.slug)"><AegisIcon name="eye" :size="14" /></button>
              </div>
            </div>
          </div>
          <AegisEmptyState v-if="!searchResults.length" icon="search-lg" title="No providers found" subtitle="Try adjusting your filters or search terms." />
          <div v-if="searchResults.length" class="results-topbar" style="justify-content:center;margin-top:20px">
            <button type="button" class="btn btn-outline btn-sm" @click="toast.info('Loading more results…')">Load More Results</button>
          </div>
        </div>
      </div>
    </div><!-- /search providers -->

    <!-- MY NETWORK -->
    <div v-show="scope === 'clinical' && clinicalTab === 'mynetwork'">
      <div class="ph-stats">
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="users" :size="18" /></div><div><div class="ph-stat-val">{{ stats.clinical }}</div><div class="ph-stat-lbl">Active Partners</div></div></div>
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="refresh" :size="18" /></div><div><div class="ph-stat-val">{{ stats.total_refs }}</div><div class="ph-stat-lbl">Referrals Exchanged</div></div></div>
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="check-badge" :size="18" /></div><div><div class="ph-stat-val">{{ stats.avg_acc }}%</div><div class="ph-stat-lbl">Avg Acceptance</div></div></div>
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div><div><div class="ph-stat-val">{{ stats.avg_resp }}h</div><div class="ph-stat-lbl">Avg Response Time</div></div></div>
      </div>
      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search by name, specialty, location..." v-model="clinicalSearch" />
        </div>
      </div>
      <div class="pn-results-bar">Showing <strong>{{ filteredClinical.length }}</strong> providers</div>
      <div class="provider-grid">
        <div v-for="nc in filteredClinical" :key="nc.id" class="spc-card" @click="viewProfile(nc.partner_slug)">
          <div class="spc-top-pills"><span class="spc-status-icon ok" data-tooltip="In Network"><AegisIcon name="user-check" :size="12" /></span></div>
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
            <button type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForConnection(nc)"><AegisIcon name="share-tree" :size="14" /></button>
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
      <div class="pn-toolbar">
        <div class="pn-search-wrap" style="flex:1;min-width:220px">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" style="padding-left:34px" v-model="bpSearch" placeholder="Search by name, skill, company, keyword..." />
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
          <span style="font-size:12px;color:var(--text-4)">Sort:</span>
          <select class="form-select" v-model="bpSort" style="font-size:12px;padding:4px 8px;min-width:160px"><option>Best Match</option><option>Highest Rated</option><option>Most Jobs</option><option>Lowest Rate</option></select>
        </div>
      </div>
      <div class="pn-results-bar">Showing {{ filteredPartners.length }} of {{ businessPartners.length }} partners</div>
      <div class="search-layout">
        <div class="filter-sidebar">
          <div class="filter-sidebar-header"><span class="filter-sidebar-title">FILTERS</span><button type="button" class="filter-clear-btn" @click="bpSearch='';bpCategory=''">CLEAR ALL</button></div>
          <div class="bp-toggle-row">
            <span style="font-size:12px;color:var(--text-2)">Clinical-service providers</span>
            <label class="bp-toggle-legacy"><input type="checkbox" v-model="bpClinicalOnly"><span class="bp-track"><span class="bp-thumb"></span></span></label>
          </div>
          <div class="filter-group">
            <button type="button" class="filter-group-btn" @click="bpCatOpen = !bpCatOpen">
              <AegisIcon name="globe" :size="12" /> Category <AegisIcon name="chevron-down" :size="11" style="margin-left:auto" :style="{ transform: bpCatOpen ? 'rotate(180deg)' : '' }" />
            </button>
            <div v-if="bpCatOpen" style="margin-top:8px">
              <select class="form-select" v-model="bpCategory" style="width:100%;font-size:12px">
                <option value="">All Categories</option>
                <option>Medical Billing</option><option>Digital Marketing</option><option>Credentialing</option><option>Practice Consulting</option><option>Accounting / CPA</option><option>Admin / VA</option><option>HR / Staffing</option><option>Legal / Attorney</option><option>IT / Software</option><option>Design / Branding</option>
              </select>
            </div>
          </div>
          <div v-for="f in bpFilterGroups" :key="f.label" class="filter-group">
            <button type="button" class="filter-group-btn"><AegisIcon :name="f.icon" :size="12" /> {{ f.label }} <AegisIcon name="chevron-down" :size="11" style="margin-left:auto" /></button>
          </div>
          <button type="button" class="btn btn-primary" style="width:100%;margin-top:14px;justify-content:center" @click="toast.success('Filters applied')">Apply Filters</button>
        </div>
        <div>
          <div class="provider-grid">
            <div v-for="p in filteredPartners" :key="p.name" class="biz-grid-card spc-card">
              <div class="biz-card-top"><span class="biz-type-badge billing">{{ p.partnerType }}</span><span class="spc-rating" style="position:static"><AegisIcon name="star" :size="11" /> {{ p.rating }}</span></div>
              <div class="spc-body" style="align-items:flex-start"><div class="biz-avatar" :style="{ background: p.avatarColor }">{{ p.initials }}</div></div>
              <div class="biz-card-name">{{ p.name }}</div>
              <div class="biz-card-role">{{ p.role }}</div>
              <div class="biz-card-meta">{{ p.location }}</div>
              <div class="spc-tags" style="margin-bottom:8px"><span v-for="tag in p.tags.slice(0,3)" :key="tag" class="spc-tag">{{ tag }}</span><span v-if="p.tags.length > 3" class="spc-tag">+{{ p.tags.length - 3 }}</span></div>
              <div class="biz-card-services">{{ p.rate }} · {{ p.reviews }} reviews · {{ p.jobs }} jobs</div>
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
      <div class="ph-stats">
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="18" /></div><div><div class="ph-stat-val">{{ bpConnections.length }}</div><div class="ph-stat-lbl">Business Partners</div></div></div>
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="heart-2" :size="18" /></div><div><div class="ph-stat-val">{{ stats.bp_count }}</div><div class="ph-stat-lbl">Active Contracts</div></div></div>
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="18" /></div><div><div class="ph-stat-val">—</div><div class="ph-stat-lbl">Avg Partner Rating</div></div></div>
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div><div><div class="ph-stat-val">{{ stats.pending_requests }}</div><div class="ph-stat-lbl">Pending Requests</div></div></div>
      </div>
      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search business partners by name, type, service..." v-model="bizSearch" />
        </div>
      </div>
      <div class="pn-results-bar">Showing <strong>{{ filteredBpConnections.length }}</strong> business partners</div>
      <div class="provider-grid">
        <div v-for="nc in filteredBpConnections" :key="nc.id" class="biz-grid-card spc-card" @click="viewProfile(nc.partner_slug)">
          <div class="spc-top-pills"><span class="spc-status-icon ok" data-tooltip="Business Partner"><AegisIcon name="user-check" :size="12" /></span></div>
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
      <div class="section-head-title">Shadow Network</div>
      <div class="insight-box">
        <div class="ph-stat-icon"><AegisIcon name="help-circle" :size="16" /></div>
        <div>
          <div class="insight-box-title">This week's AI insights</div>
          <div class="rec-section-subtitle">Based on your recent clients, you may benefit from <strong>3 additional PTSD specialists</strong> and <strong>2 child &amp; adolescent therapists</strong>. Aegis found <strong>{{ filteredRtCandidates.length }} high-match candidates</strong> below.</div>
        </div>
      </div>
      <div class="pn-search-wrap" style="margin-bottom:10px">
        <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
        <input class="form-input" style="padding-left:34px" v-model="rtSearch" placeholder="Search by name, specialty, location..." />
      </div>
      <div style="font-size:12px;color:var(--text-4);margin-bottom:14px">Showing {{ filteredRtCandidates.length }} AI suggestions</div>
      <div class="sai-grid">
        <div v-for="s in filteredRtCandidates" :key="s.name" class="sai-grid-card">
          <div class="rsc-ai-icon"><AegisIcon name="cpu" :size="12" /></div>
          <div class="sai-avatar">{{ s.initials }}</div>
          <div class="sai-card-name">{{ s.name }}</div>
          <div class="sai-card-role">{{ s.role }}</div>
          <div class="sai-card-meta">{{ s.location }}</div>
          <div class="spc-tags" style="justify-content:center;margin-bottom:12px"><span v-for="tag in s.tags" :key="tag" class="spc-tag">{{ tag }}</span></div>
          <div class="sai-card-actions">
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
      <div class="ph-stats">
        <div class="ph-stat"><div class="ph-stat-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)"><AegisIcon name="cpu" :size="18" /></div><div><div class="ph-stat-val">{{ shadowConnections.length }}</div><div class="ph-stat-lbl">Active Shadows</div></div></div>
      </div>
      <div style="font-size:12px;color:var(--text-4);margin-bottom:14px">Showing <strong>{{ shadowConnections.length }}</strong> shadow connections</div>
      <div class="sai-grid">
        <div v-for="s in shadowConnections" :key="s.id" class="sai-grid-card">
          <div class="rsc-ai-icon"><AegisIcon name="cpu" :size="12" /></div>
          <div class="sai-avatar">{{ s.shadow_initials }}</div>
          <div class="sai-card-name">{{ s.shadow_name }}</div>
          <div class="sai-card-role">{{ s.shadow_role }}</div>
          <div class="sai-card-meta">{{ s.shadow_location }}</div>
          <div class="spc-tags" style="justify-content:center;margin-bottom:12px">
            <span v-for="tag in tagList(s.shadow_specialty)" :key="tag" class="spc-tag">{{ tag }}</span>
          </div>
          <div class="sai-card-actions">
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
      <div class="section-head-title">Network Configuration</div>
      <div class="insight-box"><p style="font-size:13px;color:var(--text-3)">Network preferences and configuration options coming soon.</p></div>
    </div>

    <!-- ══════════ MODALS ══════════ -->

    <!-- Review Pending Requests -->
    <AegisModal v-model="modals.reviewRequests" title="Pending Connection Requests" :subtitle="pendingRequests.length + ' awaiting · ' + clinicalCount + ' clinical, ' + businessCount + ' business'" size="lg">
      <div class="alert alert-info" style="margin-bottom:14px">
        <AegisIcon name="info" :size="14" />
        <div><strong>Accepting</strong> adds the connection and notifies the requester. <strong>Declining</strong> removes the request — the other party is not notified.</div>
      </div>
      <div class="pvm-panel active" style="max-height:380px;overflow-y:auto">
        <div v-for="req in pendingRequests" :key="req.id" class="pvm-ref-item" style="display:flex;align-items:flex-start;gap:12px;padding:14px 0;border-bottom:1px solid var(--border)">
          <div class="spc-avatar" style="width:38px;height:38px;font-size:12px;border-radius:50%;flex-shrink:0">{{ req.requester_initials }}</div>
          <div class="pvm-info">
            <div class="pn-grid-name">{{ req.requester_name }}</div>
            <div class="pn-grid-role">{{ req.requester_role }}<span v-if="req.requester_location"> · {{ req.requester_location }}</span></div>
            <div v-if="req.message" class="rec-section-subtitle" style="font-style:italic">"{{ req.message }}"</div>
          </div>
          <div class="pvm-meta-row" style="flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0">
            <span class="badge is-quiet" :class="req.request_type === 'business' ? 'is-business' : 'is-clinical'">{{ req.request_type === 'business' ? 'Business' : 'Clinical' }}</span>
            <span style="font-size:11px;color:var(--text-4)">{{ timeAgo(req.created_at) }}</span>
          </div>
          <div class="pn-grid-actions">
            <button type="button" class="btn btn-primary btn-sm" :disabled="pendingActionId === req.id" @click="acceptRequest(req)"><AegisIcon name="check" :size="11" /> Accept</button>
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
        <button type="button" class="btn btn-primary" :disabled="inviteForm.processing" @click="submitInvite">
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
    <!-- Service Request — centralized modal -->
    <ServiceRequestModal ref="svcModalRef" />

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
        <button type="button" class="btn btn-primary" @click="submitBpHire"><AegisIcon name="user-cog" :size="16" /> Send Hire Request</button>
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
        <button type="button" class="btn btn-primary" @click="submitPostJob"><AegisIcon name="trending-up" :size="16" /> Post Job</button>
      </template>
    </AegisModal>

    <!-- Centralized ReferralModal — wired via openModal('referralModal') -->
    <ReferralModal :roster="roster" :network="referralNetwork" :preselect-slug="referralPreselectSlug" />
    </div><!-- /nw-page-root -->

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import ReferralModal         from '@/components/modals/ReferralModal.vue'
import ServiceRequestModal   from '@/components/modals/ServiceRequestModal.vue'
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
const { openModal, isOpen } = useModal()
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

// Pointer drag-to-scroll for a slider track. Suppresses the trailing click when
// the user actually dragged, so card clicks (viewProfile) still work on a tap.
function enableDragScroll(el, atStart, atEnd) {
  if (!el) return
  let down = false, startX = 0, startLeft = 0, moved = false
  el.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'mouse' && e.button !== 0) return
    down = true; moved = false; startX = e.clientX; startLeft = el.scrollLeft
    el.classList.add('is-dragging')
    try { el.setPointerCapture(e.pointerId) } catch (_) {}
  })
  el.addEventListener('pointermove', (e) => {
    if (!down) return
    const dx = e.clientX - startX
    if (Math.abs(dx) > 4) moved = true
    el.scrollLeft = startLeft - dx
    updateArrows(el, atStart, atEnd)
  })
  const end = (e) => {
    if (!down) return
    down = false
    el.classList.remove('is-dragging')
    try { el.releasePointerCapture(e.pointerId) } catch (_) {}
    if (moved) { el.dataset.dragged = '1'; setTimeout(() => { delete el.dataset.dragged }, 0) }
  }
  el.addEventListener('pointerup', end)
  el.addEventListener('pointercancel', end)
  el.addEventListener('click', (e) => {
    if (el.dataset.dragged) { e.stopPropagation(); e.preventDefault() }
  }, true)
}

// Slugify a display name into a public-profile slug (fallback for records
// whose slug isn't yet supplied by the backend search payload).
function slugify(name) {
  return String(name || '')
    .toLowerCase()
    .replace(/,.*$/, '')          // drop credentials after comma
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

onMounted(() => {
  // Backfill slugs so every "View Profile" control can navigate.
  ;[allProviders, aiShadowCandidates, rtCandidates].forEach((r) => {
    r.value.forEach((p) => { if (!p.slug) p.slug = slugify(p.name) })
  })
  if (rnpTrack.value) {
    rnpTrack.value.addEventListener('scroll', () => updateArrows(rnpTrack.value, rnpAtStart, rnpAtEnd))
    enableDragScroll(rnpTrack.value, rnpAtStart, rnpAtEnd)
    updateArrows(rnpTrack.value, rnpAtStart, rnpAtEnd)
  }
  if (spcTrack.value) {
    spcTrack.value.addEventListener('scroll', () => updateArrows(spcTrack.value, spcAtStart, spcAtEnd))
    enableDragScroll(spcTrack.value, spcAtStart, spcAtEnd)
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

// Reset slug after modal closes so it doesn't persist on re-open
watch(isOpen('referralModal'), (open) => {
  if (!open) referralPreselectSlug.value = ''
})

// Service Request — handled by centralized ServiceRequestModal.vue
const svcModalRef = ref(null)

function openSvcRequest(serviceName, providerName = '') {
  svcModalRef.value?.preselect(serviceName)
  openModal('serviceRequestModal')
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
  if (slug) router.visit(route('public.provider', { slug }))
}

// ── Search / filter helpers ────────────────────────────────────────────────
const clinicalSearch = ref('')
const bizSearch      = ref('')
// ── Filter state ──────────────────────────────────────────────────────────
const openGroups = reactive({ type: true, specialty: false, approach: false, insurance: false, format: false, location: false, credentials: false, rate: false, demographics: false })
const selectedFilters = reactive({ type: [], specialty: [], approach: [], insurance: [], format: [], location: [], credentials: [], rate: [], demographics: [] })
const search = reactive({ type: '', specialty: '', approach: '', insurance: '' })

const activeFilters = computed(() => {
  const out = []
  for (const [group, vals] of Object.entries(selectedFilters)) {
    for (const value of vals) out.push({ group, value })
  }
  return out
})

function toggleGroup(g) { openGroups[g] = !openGroups[g] }
function isSelected(g, v) { return selectedFilters[g].includes(v) }
function countGroup(g) { return selectedFilters[g].length || '' }
function toggleFilter(g, v) {
  const arr = selectedFilters[g]
  const i = arr.indexOf(v)
  if (i === -1) arr.push(v)
  else arr.splice(i, 1)
}
function removeFilter(f) {
  const arr = selectedFilters[f.group]
  const i = arr.indexOf(f.value)
  if (i !== -1) arr.splice(i, 1)
  const ap = appliedFilters[f.group]
  const j = ap.indexOf(f.value)
  if (j !== -1) ap.splice(j, 1)
}
function clearFilters() {
  for (const g of Object.keys(selectedFilters)) selectedFilters[g] = []
  for (const g of Object.keys(search)) search[g] = ''
  for (const g of Object.keys(appliedFilters)) appliedFilters[g] = []
}
function filteredOpts(arr, q) {
  if (!q) return arr
  return arr.filter(t => t.toLowerCase().includes(q.toLowerCase()))
}

// ── Applied filters → search results ───────────────────────────────────────
// selectedFilters = live sidebar selection; appliedFilters = committed via Apply.
const appliedFilters = reactive({ type: [], specialty: [], approach: [], insurance: [], format: [], location: [], credentials: [], rate: [], demographics: [] })

function providerHaystack(p) {
  return [
    p.name, p.role, p.location, ...(p.tags || []),
    p.telehealth ? 'telehealth online video remote' : 'in-person in office',
    p.networkStatus || '',
  ].join(' ').toLowerCase()
}

const searchResults = computed(() => {
  const groups = Object.entries(appliedFilters).filter(([, vals]) => vals.length)
  if (!groups.length) return allProviders.value
  return allProviders.value.filter((p) => {
    const hay = providerHaystack(p)
    // AND across groups, OR within a group
    return groups.every(([, vals]) => vals.some((v) => hay.includes(String(v).toLowerCase())))
  })
})

function applyFilters() {
  for (const g of Object.keys(appliedFilters)) appliedFilters[g] = [...selectedFilters[g]]
  toast.success(activeFilters.value.length ? 'Filters applied' : 'Showing all providers')
}

// ── Filter data ────────────────────────────────────────────────────────────
const providerTypes = ['Psychotherapist','Psychologist','Psychiatrist','Pain Management Specialist','Movement/Dance Specialist','Life Coach','Health Coach','Behavioral Therapist','Massage Therapist','Acupuncturist','Naturopathic Doctor (ND)','Functional Medicine Practitioner','Registered Dietitian (RD/RDN)','Integrative/Holistic Nutritionist','Certified Diabetes Educator (CDE)','Hypnotherapist','Somatic Practitioner','Personal Trainer','Doula','Sleep Specialist']

const specialtyGroups = {
  'Therapy Types': ['Individual Therapy','Family Therapy','Couple Therapy','Children Therapy','Children & Adolescent Therapy','Adult','Older Adult'],
  'Mental Health & Wellness': ['Stress Management & Burnout','Grief & Loss','Wellness Coaching','Sex Therapy','Sleep Disorders','Pain Management','Eating Disorders'],
  'Population / Identity': ['LGBTQIA+','Women\'s Health','Men\'s Health','BIPOC Communities','Veterans & Military Families','Faith-Based/Spiritual','Neurodivergent Individuals'],
  'Relationship & Family': ['Relationship Issues','Divorce & Separation','Parenting Support','Co-Parenting','Infidelity & Trust Issues','Premarital Counseling'],
  'Mental Health Disorders': ['Anxiety Disorders','Depression','PTSD & Trauma','OCD','Bipolar Disorder','Personality Disorders (BPD, etc.)','ADHD','Autism Spectrum Disorder (ASD)'],
  'Addiction & Substance Use': ['Substance Use Disorders','Alcohol Addiction','Drug Addiction','Behavioral Addictions','Relapse Prevention','Recovery Coaching'],
  'Life Transitions': ['Career Transitions & Work Stress','Identity Development','Life Purpose & Meaning','Midlife Crisis','Chronic Illness/Disability Adjustment'],
  'Medical / Physical Health': ['Chronic Disease Management','Hormonal Imbalances','Fertility & Reproductive Health','Pregnancy & Postpartum','Diabetes & Blood Sugar','Weight Management'],
  'Psychiatry-Specific': ['Medication Management','Treatment-Resistant Depression','Geriatric Psychiatry','Child & Adolescent Psychiatry'],
}

const approachGroups = {
  'Clinical Therapy': ['CBT','DBT','ACT','EMDR','Internal Family Systems (IFS)','Psychodynamic Therapy','Attachment-Based Therapy','Somatic Therapies','Emotionally Focused Therapy (EFT)','Gottman Method','Narrative Therapy','Gestalt Therapy','Humanistic Therapy','Solution-Focused Brief Therapy (SFBT)','Motivational Interviewing (MI)','MBSR','MBCT','Play Therapy','Art Therapy','Music Therapy','Trauma-Focused CBT','Prolonged Exposure Therapy'],
  'Psychiatry Approaches': ['Psychopharmacology','Medication Management','Combined Therapy & Medication','Treatment-Resistant Protocols','ECT / TMS / Ketamine Therapy'],
  'Nutrition Approaches': ['Intuitive Eating','Anti-Diet Approach','Health at Every Size (HAES)','Medical Nutrition Therapy (MNT)','Behavioral Nutrition Coaching'],
  'Functional Medicine': ['Systems Biology & Root-Cause Medicine','Integrative & Holistic Protocols','Lifestyle Medicine','Precision Medicine','Preventive & Longevity Medicine'],
}

const insuranceGroups = {
  'Commercial Plans': ['Aetna','Cigna / Evernorth','UnitedHealthcare / Optum','Humana','Kaiser Permanente','Oscar Health'],
  'Blue Cross Blue Shield': ['Anthem BCBS','BCBS (State/Regional)','Premera Blue Cross','Highmark BCBS','Blue Shield of California'],
  'Government Programs': ['Medicare','Medicaid','TRICARE'],
  'Behavioral Health': ['Carelon Behavioral Health (Beacon)','Magellan Healthcare','ComPsych','UMR'],
}

const formatGroups = {
  'Session Format': ['In-Person','Telehealth','Both (In-person & Telehealth)','Hybrid'],
  'Services Offered': ['Individual Therapy','Couples Therapy','Family Therapy','Group Therapy','Psychiatric Medication Management','Nutrition Counseling','Coaching (Life, Health, Wellness)','Testing / Assessments'],
}

const locationGroups = {
  'Distance': ['Within 5 miles','Within 10 miles','Within 25 miles','Any distance'],
  'State': ['New York','New Jersey','Connecticut','California','Texas','Florida'],
}

const credentialTypes = ['Licensed Psychologist (PhD/PsyD)','Licensed Clinical Social Worker (LCSW)','Licensed Professional Counselor (LPC)','Licensed Marriage & Family Therapist (LMFT)','Psychiatrist (MD/DO)','Nurse Practitioner (PMHNP)','Registered Dietitian (RD/RDN)','Certified Health Coach']

const rateTypes = ['Accepting New Clients','Sliding Scale Available','Under $100/session','$100–$150/session','$150–$200/session','$200+/session','Insurance Accepted','Self-Pay Only']

const demographicTypes = ['Female Provider','Male Provider','Non-Binary Provider','BIPOC Provider','LGBTQ+ Provider','Spanish-Speaking','Multilingual']
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
  { label:'Dietician',        desc:'Eating & metabolism',    count:3, icon:'dollar', priority:'medium' },
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

const allProviders = ref([
  { name:'Dr. Daniel Malik, MD',  id:'', slug:'', initials:'DM', role:'Psychiatrist',          location:'NYC, NY',       tags:['Anxiety','PTSD','Mood Disorders'],             rating:4.9, reviews:62, refs:'14 refs', acc:'80% acc', resp:'3.1h resp', telehealth:true,  networkStatus:'in-network'    },
  { name:'Dr. Lisa Chen, PhD',    id:'', slug:'', initials:'LC', role:'Psychologist',           location:'Brooklyn, NY',  tags:['CBT','Depression','Trauma'],                   rating:4.7, reviews:38, refs:'8 refs',  acc:'88% acc', resp:'2.0h resp', telehealth:true,  networkStatus:'in-network'    },
  { name:'Dr. Marcus Webb, LCSW', id:'', slug:'', initials:'MW', role:'Therapist / Counselor',  location:'Queens, NY',    tags:['DBT','Family Therapy','Addiction'],             rating:4.8, reviews:51, refs:'0 refs',  acc:'—',       resp:'2.1h resp', telehealth:false, networkStatus:'not-connected' },
  { name:'Dr. Aisha Patel, PsyD', id:'', slug:'', initials:'AP', role:'Psychologist',           location:'Manhattan, NY', tags:['Child & Adolescent','ADHD','Autism'],          rating:4.8, reviews:29, refs:'0 refs',  acc:'—',       resp:'4.5h resp', telehealth:false, networkStatus:'pending'       },
  { name:'Dr. James Torres, MD',  id:'', slug:'', initials:'JT', role:'Psychiatrist',           location:'Newark, NJ',    tags:['Geriatric Psych','Dementia','Medication Mgmt'],rating:4.5, reviews:18, refs:'5 refs',  acc:'80% acc', resp:'5.0h resp', telehealth:true,  networkStatus:'in-network'    },
  { name:'Dr. Sofia Kim, MD',     id:'', slug:'', initials:'SK', role:'Neurologist',            location:'Bronx, NY',     tags:['Epilepsy','Headaches','Neurocognitive'],        rating:4.8, reviews:44, refs:'0 refs',  acc:'—',       resp:'2.8h resp', telehealth:false, networkStatus:'not-connected' },
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
  { label:'Hourly Rate',      icon:'dollar' },
  { label:'Experience Level', icon:'star'        },
  { label:'Availability',     icon:'clock'       },
  { label:'Engagement Type',  icon:'briefcase' },
  { label:'Work Location',    icon:'map-pin' },
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
/* ══ ONLY layout guards that cannot live in _shared.css ══
   All component classes use 100% legacy PHP class names.
   All visual styles come from public/css/_shared.css.       */

/* Page root — prevents any child from causing horizontal scroll */
.nw-page-root { width:100%; overflow-x:hidden; }

/* Slider track — hides scrollbar, lets arrow buttons control scroll */
/* Slider tracks — override the auto-scroll from _shared.css to hide scrollbar and enable programmatic scroll */
.rec-partner-grid {
  flex-wrap: nowrap !important;
  overflow-x: auto;
  scroll-behavior: smooth;
  scrollbar-width: none;
  -ms-overflow-style: none;
  flex: 1;
  width: 0;
  min-width: 0;
  /* rnp-card gets fixed width from _shared.css rec-partner-grid > .rnp-card */
}
.rec-partner-grid::-webkit-scrollbar { display: none; }

.rec-shadow-grid {
  flex-wrap: nowrap !important;
  overflow-x: auto;
  scroll-behavior: smooth;
  scrollbar-width: none;
  -ms-overflow-style: none;
  flex: 1;
  width: 0;
  min-width: 0;
}
.rec-shadow-grid::-webkit-scrollbar { display: none; }

/* Drag-to-scroll affordance for both slider tracks */
.rec-partner-grid,
.rec-shadow-grid { cursor: grab; }
.rec-partner-grid.is-dragging,
.rec-shadow-grid.is-dragging {
  cursor: grabbing;
  scroll-behavior: auto;
  user-select: none;
}

/* Slider arrow buttons — not in legacy (slider is a Vue addition) */
.nw-slider-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  min-width: 0;
}
.nw-slider-arrow {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px;
  border-radius: var(--radius-full);
  border: none;
  background: transparent;
  color: var(--text-4);
  cursor: pointer; flex-shrink: 0;
  transition: color var(--transition), background var(--transition);
}
.nw-slider-arrow:hover:not(:disabled) {
  color: var(--gold-dark);
  background: var(--badge-bg-gold);
}
.nw-slider-arrow:disabled { opacity: .2; cursor: default; }

/* Sticky filter sidebar header — always visible at top */
.filter-sidebar-header {
  position: sticky;
  top: -14px;
  left: -10px;
  right: -10px;
  width: calc(100% + 20px);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 12px 16px;
  z-index: 2;
  margin: -14px -10px 3px;
}

/* Search Results divider label — centered with lines on both sides */
.nw-search-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: center;
  margin: 28px 0 20px;
  color: var(--text-4);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .08em;
}
.nw-search-divider::before,
.nw-search-divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* Sticky Apply Filters button at bottom of sidebar */
.filter-sidebar-apply {
  position: sticky;
  bottom: -14px;           /* pull into sidebar bottom padding so zero gap */
  left: -10px;             /* bleed past sidebar horizontal padding */
  right: -10px;
  width: calc(100% + 20px);
  background: var(--surface);
  border-top: 1px solid var(--border);
  padding: 14px 16px 20px; /* extra bottom padding covers any subpixel gap */
  margin-top: 3px;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
}
.filter-sidebar-apply .btn {
  width: 100%;
  justify-content: center;
}

/* Sub-tabs — match legacy #networkTabs .net-sub-tabs rule */
.tabs-twotier .tabs-segmented.net-sub-tabs {
  display: inline-flex;
  align-self: flex-start;
  margin-bottom: 0;
  margin-top: 6px;
}
</style>
