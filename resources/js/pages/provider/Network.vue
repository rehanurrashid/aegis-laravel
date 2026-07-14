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
          @click="viewProfile(req.requester_slug, req.request_type === 'business' ? 'business' : 'provider')"
        >
          <div class="card-top">
            <span
              class="badge"
              :class="req.request_type === 'business' ? 'badge-gold' : 'badge-green'"
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
        <button type="button" class="tab-primary" :class="{ active: scope === 'cs' }" @click="scope = 'cs'">
          <AegisIcon name="shield" :size="15" /> Continuity Stewards
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
              style="cursor:pointer"
              @click="openCategoryFilter(cat)"
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
                <button type="button" class="btn rnp-cta" data-tooltip="Find matching providers" @click.stop="openCategoryFilter(cat)">
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
                  <AegisIcon name="sparkle-cluster" :size="10" /> {{ p.match }}% <span style="font-size:9px;font-weight:600;opacity:.75;letter-spacing:0.3px">AI Match</span>
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
                <button v-if="p.networkStatus === 'pending-received'" type="button" class="rsp-connect" @click="acceptPendingRequest(p)"><AegisIcon name="check" :size="12" /> Accept</button>
                <button v-else-if="p.networkStatus === 'not-connected' || !p.networkStatus" type="button" class="rsp-connect" @click="openConnect(p)"><AegisIcon name="user-plus" :size="12" /> Connect</button>
                <span v-else-if="p.networkStatus === 'pending'" class="rsp-connect" style="opacity:.5;cursor:default"><AegisIcon name="clock" :size="12" /> Pending</span>
                <span v-else class="rsp-connect is-connected"><AegisIcon name="user-check" :size="12" /> In Network</span>
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

          <!-- Clinical-service providers toggle -->
          <div class="filter-group nw-sbp-clinical-toggle">
            <label class="nw-sbp-clinical-label">
              <span class="nw-sbp-clinical-text">
                <AegisIcon name="briefcase-rx" :size="14" />
                Clinical-service providers
                <span class="sbp-info-tip" data-tooltip="Practitioners with Services Mode enabled offer services to other providers. Toggle on to surface them at the top of results.">
                  <AegisIcon name="info" :size="12" />
                </span>
              </span>
              <button
                type="button"
                class="toggle"
                :class="{ on: clinicalServiceOnly }"
                @click="clinicalServiceOnly = !clinicalServiceOnly"
                aria-label="Prioritise clinical-service providers"
              ></button>
            </label>
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
            <span class="results-count"><strong>{{ pagedSearchResults.length }}</strong> of {{ searchResults.length }} providers</span>
            <div style="display:flex;align-items:center;gap:6px">
              <span style="font-size:12px;color:var(--text-4)">Sort:</span>
              <select class="form-select" v-model="searchSort" style="font-size:12px;padding:4px 8px;min-width:160px">
                <option>Best Match</option><option>Highest Rated</option><option>Closest</option>
              </select>
            </div>
          </div>
          <div class="search-results-grid">
            <div
              v-for="p in pagedSearchResults"
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
                <span v-if="p.has_services" class="spc-svc-icon" data-tooltip="Offers services to other providers — supervision, consultation &amp; more"><AegisIcon name="briefcase-rx" :size="12" /></span>
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
                <button v-if="p.has_services" type="button" class="btn-icon" data-tooltip="Request Service" @click="openSvcRequest('Services', p)"><AegisIcon name="briefcase-rx" :size="14" /></button>
                <button
                  v-if="p.networkStatus === 'pending-received'"
                  type="button" class="btn-icon" data-tooltip="Accept connection request"
                  @click="acceptPendingRequest(p)"
                ><AegisIcon name="check" :size="14" /></button>
                <button
                  v-else-if="p.networkStatus === 'not-connected'"
                  type="button" class="btn-icon" data-tooltip="Send Connection Request"
                  @click="openConnect(p)"
                ><AegisIcon name="user-plus" :size="14" /></button>
                <span
                  v-else-if="p.networkStatus === 'pending'"
                  class="btn-icon" data-tooltip="Connection request pending" style="opacity:.45;cursor:default"
                ><AegisIcon name="clock" :size="14" /></span>
                <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(p.slug)"><AegisIcon name="eye" :size="14" /></button>
              </div>
            </div>
          </div>
          <AegisEmptyState v-if="!searchResults.length" icon="search-lg" title="No providers found" subtitle="Try adjusting your filters or search terms." />
          <div ref="spSentinel" class="nw-scroll-sentinel" aria-hidden="true"></div>
        </div>
      </div>
    </div><!-- /search providers -->

    <!-- MY NETWORK -->
    <div v-show="scope === 'clinical' && clinicalTab === 'mynetwork'">
      <div class="stat-chips-row">
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="users" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ stats.clinical }}</div>
            <div class="stat-chip-label">Active Partners</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="refresh" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ stats.total_refs || '—' }}</div>
            <div class="stat-chip-label">Referrals Exchanged</div>
          </div>
        </div>
        <div class="stat-chip" :data-tooltip="stats.avg_acc ? stats.avg_acc + '% of incoming referrals accepted' : 'No incoming referrals yet'">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="check-badge" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ stats.avg_acc ? stats.avg_acc + '%' : '—' }}</div>
            <div class="stat-chip-label">Avg Acceptance</div>
          </div>
        </div>
        <div class="stat-chip" :data-tooltip="stats.avg_resp ? 'Average ' + stats.avg_resp + 'h to respond to referrals' : 'No response data yet'">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="clock" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ stats.avg_resp ? stats.avg_resp + 'h' : '—' }}</div>
            <div class="stat-chip-label">Avg Response Time</div>
          </div>
        </div>
      </div>
      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search by name, specialty, location..." v-model="clinicalSearch" />
        </div>
      </div>
      <div class="results-topbar">
        <span class="results-count" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="users" :size="13" style="color:var(--text-4);flex-shrink:0" /><strong>{{ pagedClinical.length }}</strong> of {{ filteredClinical.length }}<span v-if="filteredClinical.length !== stats.clinical">of {{ stats.clinical }}</span>provider{{ filteredClinical.length === 1 ? '' : 's' }} in your network</span>
      </div>
      <div class="provider-grid">
        <div v-for="nc in pagedClinical" :key="nc.id" class="card-v2 pn-card spc-card" @click="viewProfile(nc.partner_slug)">
          <div class="spc-top-pills">
            <span class="spc-status-icon ok" data-tooltip="In Network — this provider is connected to your network"><AegisIcon name="user-check" :size="12" /></span>
            <span v-if="nc.partner_telehealth" class="spc-svc-icon" data-tooltip="Telehealth available"><AegisIcon name="video" :size="12" /></span>
            <span v-if="nc.partner_has_services" class="spc-svc-icon" data-tooltip="Services Available"><AegisIcon name="briefcase-rx" :size="12" /></span>
          </div>
          <div class="spc-rating" :data-tooltip="ratingTooltip(nc)">
            <AegisIcon name="star" :size="11" />
            {{ ratingDisplay(nc) }}
          </div>
          <div class="spc-body">
            <div class="spc-avatar">{{ nc.partner_initials }}</div>
            <div class="spc-name">{{ nc.partner_name }}</div>
            <div class="spc-role">{{ nc.partner_role }}</div>
            <div class="spc-loc">{{ nc.partner_location }}</div>
            <div class="spc-tags">
              <span v-for="tag in visibleTags(nc.partner_specialty)" :key="tag" class="spc-tag">{{ tag }}</span>
              <span v-if="overflowTagCount(nc.partner_specialty) > 0" class="spc-tag spc-tag-more">+{{ overflowTagCount(nc.partner_specialty) }}</span>
            </div>
          </div>
          <div class="spc-stats">{{ connectionStatsLine(nc) }}</div>
          <div class="spc-actions" @click.stop>
            <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === nc.partner_id" @click="openConversation(nc.partner_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForConnection(nc)"><AegisIcon name="refresh" :size="14" /></button>
            <button v-if="nc.partner_has_services" type="button" class="btn-icon" data-tooltip="Request Service" @click="openSvcRequest('Services', { id: nc.partner_id, name: nc.partner_name })"><AegisIcon name="briefcase-rx" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(nc.partner_slug, 'business')"><AegisIcon name="eye" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Remove from network" @click="confirmDisconnect(nc)"><AegisIcon name="trash-2" :size="14" /></button>
          </div>
        </div>
      </div>
      <AegisEmptyState v-if="!filteredClinical.length" icon="users" title="No clinical connections yet" subtitle="Search providers and send connection requests to build your network.">
        <template #actions><button type="button" class="btn btn-primary" @click="clinicalTab = 'search'">Search Providers</button></template>
      </AegisEmptyState>
      <div v-if="hasMoreClinical" style="text-align:center;margin-top:24px">
        <button type="button" class="btn btn-outline" @click="myNetworkPage++">Load More</button>
      </div>
    </div><!-- /my network -->

    <!-- ══════════ BUSINESS PARTNERS ══════════ -->

    <!-- SEARCH BUSINESS PARTNERS -->
    <div v-show="scope === 'business' && businessTab === 'search'">
      <div class="sbp-layout">
        <!-- ── FILTER SIDEBAR ── -->
        <aside class="filter-sidebar" id="sbpFilterSidebar">
          <div class="filter-sidebar-header">
            <div class="filter-sidebar-title"><AegisIcon name="filter" :size="14" /> Filters</div>
            <button type="button" class="filter-clear-btn" @click="sbpClearAll">Clear All</button>
          </div>

          <!-- Clinical-service providers toggle -->
          <div class="filter-group nw-sbp-clinical-toggle">
            <label class="nw-sbp-clinical-label">
              <span class="nw-sbp-clinical-text">
                <AegisIcon name="briefcase-rx" :size="14" />
                Clinical-service providers
                <span class="sbp-info-tip" data-tooltip="Toggle to surface Aegis providers with Services Mode at the top of results."><AegisIcon name="info" :size="12" /></span>
              </span>
              <button type="button" class="toggle" :class="{ on: bpClinicalOnly }" @click="bpClinicalOnly = !bpClinicalOnly" aria-label="Show clinical-service providers"></button>
            </label>
          </div>

          <!-- 1. Category -->
          <div class="filter-group" :class="{ open: sbpGroups.category }">
            <div class="filter-group-header" @click="sbpGroups.category = !sbpGroups.category">
              <span class="filter-group-label">
                <AegisIcon name="briefcase" :size="16" /> Category
                <span v-if="bpCategory" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <div class="filter-subcat">Select one</div>
              <span
                v-for="cat in bpCategories" :key="cat.val"
                class="ftag"
                :class="{ selected: bpCategory === cat.val }"
                @click="bpCategory = bpCategory === cat.val ? '' : cat.val"
              >{{ cat.label }}</span>
            </div>
          </div>

          <!-- 2. Partner Type -->
          <div class="filter-group" :class="{ open: sbpGroups.type }">
            <div class="filter-group-header" @click="sbpGroups.type = !sbpGroups.type">
              <span class="filter-group-label">
                <AegisIcon name="users" :size="16" /> Partner Type
                <span v-if="bpTypeFilter" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span
                v-for="t in ['Freelancer','Agency','Consultant','Firm','Solopreneur']" :key="t"
                class="ftag"
                :class="{ selected: bpTypeFilter === t.toLowerCase() }"
                @click="bpTypeFilter = bpTypeFilter === t.toLowerCase() ? '' : t.toLowerCase()"
              >{{ t }}</span>
            </div>
          </div>

          <!-- 3. Hourly Rate -->
          <div class="filter-group" :class="{ open: sbpGroups.rate }">
            <div class="filter-group-header" @click="sbpGroups.rate = !sbpGroups.rate">
              <span class="filter-group-label">
                <AegisIcon name="dollar" :size="16" /> Hourly Rate
                <span v-if="bpRateMin > 0 || bpRateMax < 9999" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <div class="filter-subcat">Quick range</div>
              <span class="ftag" :class="{ selected: bpRateMin===0&&bpRateMax===25 }" @click="bpRateMin=0;bpRateMax=25">Under $25</span>
              <span class="ftag" :class="{ selected: bpRateMin===25&&bpRateMax===50 }" @click="bpRateMin=25;bpRateMax=50">$25–$50</span>
              <span class="ftag" :class="{ selected: bpRateMin===50&&bpRateMax===100 }" @click="bpRateMin=50;bpRateMax=100">$50–$100</span>
              <span class="ftag" :class="{ selected: bpRateMin===100&&bpRateMax===200 }" @click="bpRateMin=100;bpRateMax=200">$100–$200</span>
              <span class="ftag" :class="{ selected: bpRateMin===200&&bpRateMax===9999 }" @click="bpRateMin=200;bpRateMax=9999">$200+</span>
              <div class="filter-subcat">Custom range ($/hr)</div>
              <div class="sbp-range-row">
                <input v-model.number="bpRateMin" class="filter-inner-search" type="number" placeholder="Min" min="0" />
                <input v-model.number="bpRateMax" class="filter-inner-search" type="number" placeholder="Max" min="0" />
              </div>
            </div>
          </div>

          <!-- 4. Experience Level -->
          <div class="filter-group" :class="{ open: sbpGroups.exp }">
            <div class="filter-group-header" @click="sbpGroups.exp = !sbpGroups.exp">
              <span class="filter-group-label">
                <AegisIcon name="graduation-cap" :size="16" /> Experience Level
                <span v-if="bpExpLevel" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <label v-for="opt in bpExpOptions" :key="opt.val" class="sbp-radio-row" @click="bpExpLevel = bpExpLevel === opt.val ? '' : opt.val">
                <div class="sbp-radio" :class="{ active: bpExpLevel === opt.val }"></div>
                <div>
                  <div class="sbp-radio-label">{{ opt.label }}</div>
                  <div v-if="opt.sub" class="sbp-radio-sub">{{ opt.sub }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- 5. Minimum Rating -->
          <div class="filter-group" :class="{ open: sbpGroups.rating }">
            <div class="filter-group-header" @click="sbpGroups.rating = !sbpGroups.rating">
              <span class="filter-group-label">
                <AegisIcon name="star" :size="16" /> Minimum Rating
                <span v-if="bpMinRating > 0" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <label v-for="opt in bpRatingOptions" :key="opt.val" class="sbp-radio-row" @click="bpMinRating = bpMinRating === opt.val ? 0 : opt.val">
                <div class="sbp-radio" :class="{ active: bpMinRating === opt.val }"></div>
                <span class="sbp-radio-label">{{ opt.label }}<AegisIcon v-if="opt.val > 0" name="star" :size="11" /></span>
              </label>
            </div>
          </div>

          <!-- 6. Availability -->
          <div class="filter-group" :class="{ open: sbpGroups.avail }">
            <div class="filter-group-header" @click="sbpGroups.avail = !sbpGroups.avail">
              <span class="filter-group-label">
                <AegisIcon name="clock" :size="16" /> Availability
                <span v-if="bpAvail" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span class="ftag" :class="{ selected: bpAvail === 'immediate' }" @click="bpAvail = bpAvail === 'immediate' ? '' : 'immediate'">Available Now</span>
              <span class="ftag" :class="{ selected: bpAvail === 'within_week' }" @click="bpAvail = bpAvail === 'within_week' ? '' : 'within_week'">This Week</span>
              <span class="ftag" :class="{ selected: bpAvail === 'within_month' }" @click="bpAvail = bpAvail === 'within_month' ? '' : 'within_month'">This Month</span>
            </div>
          </div>

          <!-- 7. Engagement Type -->
          <div class="filter-group" :class="{ open: sbpGroups.eng }">
            <div class="filter-group-header" @click="sbpGroups.eng = !sbpGroups.eng">
              <span class="filter-group-label">
                <AegisIcon name="briefcase" :size="16" /> Engagement Type
                <span v-if="bpEngTypes.length" class="filter-group-count visible">{{ bpEngTypes.length }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <label v-for="opt in bpEngOptions" :key="opt.val" class="sbp-check-row">
                <input type="checkbox" :value="opt.val" v-model="bpEngTypes" />
                <span class="sbp-check-label">{{ opt.label }}</span>
              </label>
            </div>
          </div>

          <!-- 8. Work Location -->
          <div class="filter-group" :class="{ open: sbpGroups.location }">
            <div class="filter-group-header" @click="sbpGroups.location = !sbpGroups.location">
              <span class="filter-group-label">
                <AegisIcon name="map-pin" :size="16" /> Work Location
                <span v-if="bpLocation" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span class="ftag" :class="{ selected: bpLocation === 'remote' }" @click="bpLocation = bpLocation === 'remote' ? '' : 'remote'">Remote</span>
              <span class="ftag" :class="{ selected: bpLocation === 'onsite' }" @click="bpLocation = bpLocation === 'onsite' ? '' : 'onsite'">On-Site</span>
              <span class="ftag" :class="{ selected: bpLocation === 'hybrid' }" @click="bpLocation = bpLocation === 'hybrid' ? '' : 'hybrid'">Hybrid</span>
            </div>
          </div>

          <!-- 9. Quality Badges -->
          <div class="filter-group" :class="{ open: sbpGroups.quality }">
            <div class="filter-group-header" @click="sbpGroups.quality = !sbpGroups.quality">
              <span class="filter-group-label">
                <AegisIcon name="shield" :size="16" /> Quality Badges
                <span v-if="Object.values(bpBadges).some(Boolean)" class="filter-group-count visible">{{ Object.values(bpBadges).filter(Boolean).length }}</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <label class="sbp-toggle-row">
                <span class="sbp-toggle-label"><AegisIcon name="shield" :size="13" /> Verified Profile</span>
                <button type="button" class="toggle" :class="{ on: bpBadges.verified }" @click="bpBadges.verified = !bpBadges.verified"></button>
              </label>
              <label class="sbp-toggle-row">
                <span class="sbp-toggle-label"><AegisIcon name="award-2" :size="13" /> Top Rated (4.8+)</span>
                <button type="button" class="toggle" :class="{ on: bpBadges.topRated }" @click="bpBadges.topRated = !bpBadges.topRated"></button>
              </label>
              <label class="sbp-toggle-row">
                <span class="sbp-toggle-label"><AegisIcon name="zap" :size="13" /> Fast Responder</span>
                <button type="button" class="toggle" :class="{ on: bpBadges.fast }" @click="bpBadges.fast = !bpBadges.fast"></button>
              </label>
              <label class="sbp-toggle-row">
                <span class="sbp-toggle-label"><AegisIcon name="lock-closed" :size="13" /> HIPAA Compliant</span>
                <button type="button" class="toggle" :class="{ on: bpBadges.hipaa }" @click="bpBadges.hipaa = !bpBadges.hipaa"></button>
              </label>
              <label class="sbp-toggle-row">
                <span class="sbp-toggle-label"><AegisIcon name="sparkle-cluster" :size="13" /> New on Aegis</span>
                <button type="button" class="toggle" :class="{ on: bpBadges.newMember }" @click="bpBadges.newMember = !bpBadges.newMember"></button>
              </label>
            </div>
          </div>

          <!-- 10. Jobs Completed -->
          <div class="filter-group" :class="{ open: sbpGroups.jobs }">
            <div class="filter-group-header" @click="sbpGroups.jobs = !sbpGroups.jobs">
              <span class="filter-group-label">
                <AegisIcon name="check-badge" :size="16" /> Jobs Completed
                <span v-if="bpMinJobs > 0" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span class="ftag" :class="{ selected: bpMinJobs === 0 }" @click="bpMinJobs = 0">Any</span>
              <span class="ftag" :class="{ selected: bpMinJobs === 1 }" @click="bpMinJobs = 1">1+</span>
              <span class="ftag" :class="{ selected: bpMinJobs === 10 }" @click="bpMinJobs = 10">10+</span>
              <span class="ftag" :class="{ selected: bpMinJobs === 50 }" @click="bpMinJobs = 50">50+</span>
              <span class="ftag" :class="{ selected: bpMinJobs === 100 }" @click="bpMinJobs = 100">100+</span>
            </div>
          </div>

          <!-- 11. Language -->
          <div class="filter-group" :class="{ open: sbpGroups.lang }">
            <div class="filter-group-header" @click="sbpGroups.lang = !sbpGroups.lang">
              <span class="filter-group-label">
                <AegisIcon name="globe" :size="16" /> Language
                <span v-if="bpLang" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <input class="filter-inner-search" type="text" placeholder="Search languages..." v-model="bpLangSearch" />
              <span
                v-for="lang in filteredBpLangs" :key="lang"
                class="ftag"
                :class="{ selected: bpLang === lang.toLowerCase() }"
                @click="bpLang = bpLang === lang.toLowerCase() ? '' : lang.toLowerCase()"
              >{{ lang }}</span>
            </div>
          </div>

          <div class="filter-sidebar-apply">
            <button type="button" class="btn btn-primary" @click="toast.success('Filters applied')">Apply Filters</button>
          </div>
        </aside>

        <!-- ── RESULTS PANEL ── -->
        <div class="results-panel">
          <div class="results-topbar">
            <div class="results-count">
              <strong>{{ filteredPartners.length }}</strong>
              {{ bpClinicalOnly ? 'results (BPs + clinical providers)' : `of ${businessPartners.length} partners` }}
            </div>
            <div class="results-sort">
              <span class="nw-sort-label">Sort:</span>
              <select v-model="bpSort" class="form-select nw-sort-select" style="min-width:220px">
                <option>Best Match</option>
                <option>Highest Rated</option>
                <option>Most Jobs</option>
                <option>Lowest Rate</option>
              </select>
            </div>
          </div>

          <div class="pn-toolbar" style="margin-bottom:14px">
            <div class="pn-search-wrap">
              <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
              <input v-model="bpSearch" class="form-input" type="text" placeholder="Search by name, skill, company, keyword..." />
            </div>
          </div>

          <div id="sbpResultsGrid" class="search-results-grid">
            <div v-for="p in pagedPartners" :key="p.id || p.name" class="sbp-card spc-card" @click="viewProfile(p.slug, p.kind || 'business')">
              <div class="spc-top-pills">
                <span class="spc-status-icon" :class="p.networkStatus === 'in-network' ? 'ok' : (p.networkStatus === 'pending' ? 'pend' : 'off')"
                  :data-tooltip="p.networkStatus === 'in-network' ? 'In your network' : (p.networkStatus === 'pending' ? 'Request pending' : 'Not connected')">
                  <AegisIcon :name="p.networkStatus === 'in-network' ? 'user-check' : 'user-plus'" :size="12" />
                </span>
                <span v-if="p.has_services" class="spc-svc-icon" data-tooltip="Offers services to practitioners"><AegisIcon name="briefcase-rx" :size="12" /></span>
              </div>
              <div class="spc-rating" v-if="p.rating" :data-tooltip="p.rating + ' from ' + p.reviews + ' reviews'">
                <AegisIcon name="star" :size="11" />{{ p.rating }}
              </div>
              <div class="spc-body">
                <div class="spc-avatar">{{ p.initials }}</div>
                <div class="spc-name">{{ p.name }}</div>
                <div class="spc-role">{{ p.partnerType ? p.partnerType + ' · ' : '' }}{{ p.role }}</div>
                <div class="spc-loc">{{ p.location }}</div>
                <div class="spc-tags">
                  <span v-for="tag in (p.tags || []).slice(0, 3)" :key="tag" class="spc-tag">{{ tag }}</span>
                  <span v-if="(p.tags || []).length > 3" class="spc-tag spc-tag-more">+{{ p.tags.length - 3 }}</span>
                </div>
              </div>
              <div v-if="p.rate && p.rate !== '—'" class="spc-stats">{{ p.rate }}<span v-if="p.reviews"> · {{ p.reviews }} reviews</span><span v-if="p.jobs"> · {{ p.jobs }} jobs</span></div>
              <div class="spc-actions" @click.stop>
                <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === p.id" @click="openConversation(p.id)"><AegisIcon name="message-square" :size="14" /></button>
                <button v-if="p.kind !== 'provider'" type="button" class="btn-icon" data-tooltip="Hire" @click="openBpHire(p)"><AegisIcon name="briefcase" :size="14" /></button>
                <button v-if="p.kind !== 'provider'" type="button" class="btn-icon" data-tooltip="Request Quote" @click="openBpQuote(p)"><AegisIcon name="clipboard" :size="14" /></button>
                <button v-if="p.kind !== 'provider'" type="button" class="btn-icon" data-tooltip="Schedule Consultation" @click="openBpSchedule(p)"><AegisIcon name="calendar" :size="14" /></button>
                <button
                  v-if="p.networkStatus === 'pending-received'"
                  type="button" class="btn-icon" data-tooltip="Accept connection request"
                  @click="acceptPendingRequest(p)"
                ><AegisIcon name="check" :size="14" /></button>
                <button
                  v-else-if="p.networkStatus === 'not-connected'"
                  type="button" class="btn-icon" data-tooltip="Send Connection Request"
                  @click="openConnect(p)"
                ><AegisIcon name="user-plus" :size="14" /></button>
                <span v-else-if="p.networkStatus === 'pending'" class="btn-icon" data-tooltip="Request pending" style="opacity:.45;cursor:default"><AegisIcon name="clock" :size="14" /></span>
                <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(p.slug, p.kind || 'business')"><AegisIcon name="eye" :size="14" /></button>
              </div>
            </div>
          </div>

          <AegisEmptyState v-if="!filteredPartners.length" icon="briefcase" title="No partners found" subtitle="Try adjusting your search or filters." />

          <div ref="bpSentinel" class="nw-scroll-sentinel" aria-hidden="true"></div>
        </div>
      </div>
    </div><!-- /search business -->

    <!-- MY PARTNERS -->
    <div v-show="scope === 'business' && businessTab === 'mypartners'">
      <div class="stat-chips-row">
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="briefcase" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ bpConnections.length || '—' }}</div>
            <div class="stat-chip-label">Business Partners</div>
          </div>
        </div>
        <div class="stat-chip" :data-tooltip="stats.bp_count ? stats.bp_count + ' active contracts' : 'No active contracts yet'">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="check-badge" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ stats.bp_count || '—' }}</div>
            <div class="stat-chip-label">Active Contracts</div>
          </div>
        </div>
        <div class="stat-chip" :data-tooltip="avgBpRatingDisplay !== '—' ? avgBpRatingDisplay + ' avg rating across partners' : 'No ratings yet'">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="star" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ avgBpRatingDisplay }}</div>
            <div class="stat-chip-label">Avg Partner Rating</div>
          </div>
        </div>
        <div class="stat-chip" :data-tooltip="stats.bp_pending > 0 ? stats.bp_pending + ' outbound connection requests pending' : 'No pending requests sent'">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="clock" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ stats.bp_pending !== undefined ? (stats.bp_pending || '—') : '—' }}</div>
            <div class="stat-chip-label">Pending Requests</div>
          </div>
        </div>
      </div>
      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search business partners by name, type, service..." v-model="bizSearch" />
        </div>
      </div>
      <div class="results-topbar">
        <span class="results-count" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="briefcase" :size="13" style="color:var(--text-4);flex-shrink:0" /><strong>{{ filteredBpConnections.length }}</strong><span v-if="filteredBpConnections.length !== bpConnections.length">of {{ bpConnections.length }}</span>business partner{{ filteredBpConnections.length === 1 ? '' : 's' }}</span>
      </div>
      <div id="bizGridView" class="provider-grid nw-biz-grid">
        <div v-for="nc in pagedBpConnections" :key="nc.id" class="biz-grid-card spc-card" @click="viewProfile(nc.partner_slug, 'business')">
          <!-- Top pills: type icon + services badge -->
          <div class="spc-top-pills">
            <span class="spc-status-icon ok" data-tooltip="Business partner in your network"><AegisIcon name="user-check" :size="12" /></span>
            <span v-if="nc.partner_has_services" class="spc-svc-icon" data-tooltip="Offers services to practitioners"><AegisIcon name="briefcase-rx" :size="12" /></span>
            <!-- Category as compact icon+tooltip (replaces overflowing text pill) -->
            <span v-if="nc.partner_categories" class="spc-svc-icon" :data-tooltip="nc.partner_categories"><AegisIcon name="bookmark" :size="12" /></span>
          </div>
          <div v-if="ratingDisplay(nc) !== '—'" class="spc-rating" :data-tooltip="ratingTooltip(nc, 'partner rating')">
            <AegisIcon name="star" :size="11" />{{ ratingDisplay(nc) }}
          </div>
          <div class="spc-body">
            <div class="spc-avatar">{{ nc.partner_initials }}</div>
            <div class="spc-name">{{ nc.partner_name }}</div>
            <div class="spc-role">{{ nc.partner_role }}</div>
            <div class="spc-loc">{{ nc.partner_location }}</div>
          </div>
          <div class="spc-actions" @click.stop>
            <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === nc.partner_id" @click="openConversation(nc.partner_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Hire" @click="openBpHire(nc)"><AegisIcon name="briefcase" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Request Quote" @click="openBpQuote({ id: nc.partner_id, display_name: nc.partner_name, slug: nc.partner_slug })"><AegisIcon name="clipboard" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Schedule Consultation" @click="openBpSchedule({ id: nc.partner_id, display_name: nc.partner_name, slug: nc.partner_slug })"><AegisIcon name="calendar" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(nc.partner_slug, 'business')"><AegisIcon name="eye" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Remove" @click="confirmDisconnect(nc)"><AegisIcon name="trash-2" :size="14" /></button>
          </div>
        </div>
      </div>
      <AegisEmptyState v-if="!filteredBpConnections.length" icon="briefcase" title="No business partners yet" subtitle="Search the partner directory to find billing, legal, IT, and other practice services.">
        <template #actions><button type="button" class="btn btn-primary" @click="businessTab = 'search'">Search Partners</button></template>
      </AegisEmptyState>
      <div v-if="hasMoreBpConnections" style="text-align:center;margin-top:24px">
        <button type="button" class="btn btn-outline" @click="myPartnersPage++">Load More</button>
      </div>
    </div><!-- /my partners -->

    <!-- ══════════ REFERRALS & TOOLS ══════════ -->

    <!-- REFERRAL LIST (AI Shadow Suggestions) -->
    <div v-show="scope === 'tools' && toolsTab === 'list'">
      <div class="nw-section-heading">Shadow Network</div>

      <div class="insight-box nw-insight-box">
        <div class="nw-insight-icon"><AegisIcon name="target-2" :size="16" /></div>
        <div class="nw-insight-body">
          <div class="insight-box-title">This week's AI insights</div>
          <div class="nw-insight-text">Based on your recent clients, you may benefit from <strong>3 additional PTSD specialists</strong> and <strong>2 child &amp; adolescent therapists</strong>. Aegis found <strong>{{ filteredRtCandidates.length }} high-match candidates</strong> below — sorted by compatibility score.</div>
        </div>
      </div>

      <div class="pn-toolbar">
        <div class="pn-search-wrap" style="flex:1;min-width:220px">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" style="padding-left:34px" v-model="rtSearch" placeholder="Search by name, specialty, location..." />
        </div>
      </div>

      <div class="results-topbar">
        <span class="results-count" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="sparkle-cluster" :size="13" style="color:var(--text-4);flex-shrink:0" /><strong>{{ filteredRtCandidates.length }}</strong>AI suggestion{{ filteredRtCandidates.length === 1 ? '' : 's' }}</span>
        <span style="font-size:11px;color:var(--text-4)">Sorted by compatibility score</span>
      </div>

      <div class="provider-grid">
        <div v-for="s in pagedRtCandidates" :key="s.name" class="sai-grid-card spc-card" @click="viewProfile(s.slug)">
          <!-- Top-left: AI sparkle icon -->
          <div class="spc-top-pills">
            <span v-if="s.match" class="rsp-match" :data-tooltip="s.match + '% AI match based on your clinical focus and referral patterns'">
              <AegisIcon name="sparkle-cluster" :size="10" /> {{ s.match }}% <span style="font-size:9px;font-weight:600;opacity:.75;letter-spacing:0.3px">AI Match</span>
            </span>
            <span v-else class="rsc-ai-icon" data-tooltip="AI suggested based on your clinical focus and referral patterns">
              <AegisIcon name="sparkle-cluster" :size="12" />
            </span>
          </div>
          <!-- Top-right: network status only -->
          <div style="position:absolute;top:10px;right:10px;display:flex;align-items:center;gap:5px;z-index:2">
            <span v-if="s.networkStatus === 'in-network'" class="spc-status-icon ok" data-tooltip="In your network">
              <AegisIcon name="user-check" :size="12" />
            </span>
            <span v-else-if="s.networkStatus === 'pending' || s.networkStatus === 'pending-received'" class="spc-status-icon pend" data-tooltip="Connection request pending">
              <AegisIcon name="clock" :size="12" />
            </span>
          </div>
          <div class="spc-body">
            <div class="spc-avatar">{{ s.initials }}</div>
            <div class="spc-name">{{ s.name }}</div>
            <div class="spc-role">{{ s.role }}</div>
            <div class="spc-loc">{{ s.location }}</div>
            <div class="spc-tags">
              <span v-for="tag in (s.tags || []).slice(0, 3)" :key="tag" class="spc-tag">{{ tag }}</span>
              <span v-if="(s.tags || []).length > 3" class="spc-tag spc-tag-more">+{{ s.tags.length - 3 }}</span>
            </div>
          </div>
          <div class="spc-stats">{{ s.referral_count || 0 }} refs · {{ s.acceptance_rate || 0 }}% acc · {{ s.response_time_hours ? Number(s.response_time_hours).toFixed(1) : 0 }}h resp</div>
          <div class="spc-actions" @click.stop>
            <button type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === s.id || !s.id" @click="s.id && openConversation(s.id)"><AegisIcon name="message-square" :size="14" /></button>
            <button v-if="s.slug" type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForProvider(s)"><AegisIcon name="share-tree" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Add to My Shadows" @click="addShadowDirect(s)"><AegisIcon name="user-plus" :size="14" /></button>
            <button v-if="s.slug" type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(s.slug, 'provider')"><AegisIcon name="eye" :size="14" /></button>
          </div>
        </div>
      </div>

      <AegisEmptyState v-if="!filteredRtCandidates.length" icon="cpu" title="No AI suggestions right now" subtitle="Aegis is still learning your referral patterns. Check back after you've sent a few referrals — or add providers manually.">
        <template #actions>
          <button type="button" class="btn btn-outline" @click="openManualReferralEntry">Add Manually</button>
        </template>
      </AegisEmptyState>

      <div v-if="hasMoreRtCandidates" style="text-align:center;margin-top:24px">
        <button type="button" class="btn btn-outline" @click="rtPage++">
          <AegisIcon name="refresh" :size="14" />
          Load More
        </button>
      </div>
    </div><!-- /referral list -->

    <!-- MY SHADOWS -->
    <div v-show="scope === 'tools' && toolsTab === 'shadows'">
      <div class="nw-section-heading">My Shadows</div>

      <div class="stat-chips-row">
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="users" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ shadowConnections.length }}</div>
            <div class="stat-chip-label">Active Shadows</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="refresh" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ shadowTotalReferrals }}</div>
            <div class="stat-chip-label">Total Referrals</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="trending-up" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ shadowAvgMatchDisplay }}</div>
            <div class="stat-chip-label">Avg Match Score</div>
          </div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon nw-chip-gold"><AegisIcon name="clock" :size="18" /></div>
          <div>
            <div class="stat-chip-value">{{ shadowAvgRespDisplay }}</div>
            <div class="stat-chip-label">Avg Response Time</div>
          </div>
        </div>
      </div>

      <div class="pn-toolbar">
        <div class="pn-search-wrap">
          <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
          <input class="form-input" type="text" placeholder="Search shadows by name, specialty..." v-model="myShadowSearch" />
        </div>
      </div>

      <div class="results-topbar">
        <span class="results-count" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="cpu" :size="13" style="color:var(--text-4);flex-shrink:0" /><strong>{{ filteredMyShadows.length }}</strong>shadow connection{{ filteredMyShadows.length === 1 ? '' : 's' }}</span>
      </div>

      <div class="provider-grid">
        <div v-for="s in pagedMyShadows" :key="s.id" class="ms-grid-card spc-card" @click="viewProfile(s.shadow_slug)">
          <!-- Top-left: AI match badge -->
          <div class="spc-top-pills">
            <span v-if="s.match_score" class="rsp-match" :data-tooltip="s.match_score + '% AI match based on your clinical focus'">
              <AegisIcon name="sparkle-cluster" :size="10" /> {{ s.match_score }}% <span style="font-size:9px;font-weight:600;opacity:.75;letter-spacing:0.3px">AI Match</span>
            </span>
            <span v-else class="rsc-ai-icon" data-tooltip="AI matched shadow">
              <AegisIcon name="sparkle-cluster" :size="12" />
            </span>
          </div>
          <!-- Top-right: in-network status icon only -->
          <div style="position:absolute;top:10px;right:10px;z-index:2">
            <span class="spc-status-icon ok" data-tooltip="In your network">
              <AegisIcon name="user-check" :size="12" />
            </span>
          </div>
          <div class="spc-body">
            <div class="spc-avatar">{{ s.shadow_initials }}</div>
            <div class="spc-name">{{ s.shadow_name }}</div>
            <div class="spc-role">{{ s.shadow_role }}</div>
            <div class="spc-loc">{{ s.shadow_location }}</div>
            <div class="spc-tags">
              <span v-for="tag in visibleTags(s.shadow_specialty)" :key="tag" class="spc-tag">{{ tag }}</span>
              <span v-if="overflowTagCount(s.shadow_specialty) > 0" class="spc-tag spc-tag-more">+{{ overflowTagCount(s.shadow_specialty) }}</span>
            </div>
          </div>
          <div class="spc-stats">{{ shadowStatsLine(s) }}</div>
          <div class="spc-actions" @click.stop>
            <button v-if="s.shadow_user_id" type="button" class="btn-icon" data-tooltip="Message" :disabled="msgLoading === s.shadow_user_id" @click="openConversation(s.shadow_user_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Refer Client" @click="openReferralForShadow(s)"><AegisIcon name="refresh" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(s.shadow_slug)"><AegisIcon name="eye" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Remove Shadow" @click="confirmRemoveShadow(s)"><AegisIcon name="trash-2" :size="14" /></button>
          </div>
        </div>
      </div>

      <div v-if="hasMoreMyShadows" style="text-align:center;margin-top:24px">
        <button type="button" class="btn btn-outline" @click="myShadowPage++">Load More</button>
      </div>
      <AegisEmptyState v-if="!filteredMyShadows.length" icon="cpu" title="No shadow connections yet" subtitle="Shadow providers are backup clinicians who mirror your profile. Add them from the Referral List.">
        <template #actions>
          <button type="button" class="btn btn-primary" @click="toolsTab = 'list'">Browse Referral List</button>
        </template>
      </AegisEmptyState>
    </div><!-- /my shadows -->

    <!-- CONFIGURATION -->
    <div v-show="scope === 'tools' && toolsTab === 'config'">
      <div class="cfg-alert">
        <div class="cfg-alert-icon"><AegisIcon name="lightbulb" :size="16" /></div>
        <div>
          <div class="cfg-alert-title">Configure Your Network Profile</div>
          <p class="cfg-alert-text">Select your interdisciplinary team, specialties, treatment approaches, insurance, and demographics to help referral partners and clients find you. All changes are saved to your public provider profile.</p>
        </div>
      </div>

      <div class="config-layout">
        <!-- LEFT NAV -->
        <nav class="config-nav">
          <div class="config-nav-header"><AegisIcon name="settings-3" :size="16" /> Settings</div>
          <a
            v-for="item in configNav"
            :key="item.id"
            class="config-nav-item"
            :class="{ active: activeConfigPanel === item.id }"
            @click="scrollToConfigPanel(item.id)"
          >
            <AegisIcon :name="item.icon" :size="16" />
            {{ item.label }}
          </a>
        </nav>

        <!-- RIGHT CONTENT -->
        <div class="config-content">
          <!-- 1. INTERDISCIPLINARY TEAM -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-team' }" id="cfg-team">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-team')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="badge-id" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Pre-Designed Interdisciplinary Team Network</div>
                  <div class="cfg-panel-subtitle">Select provider types you want in your referral network</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('team') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-team'" class="cfg-panel-body">
              <input v-model="cfgSearch.team" class="cfg-search" type="text" placeholder="Search provider types..." />
              <div class="cfg-provider-grid">
                <div
                  v-for="opt in filteredTeamOptions"
                  :key="opt.label"
                  class="cfg-provider-item"
                  :class="{ selected: cfgSelected.team.includes(opt.label) }"
                  @click="toggleCfg('team', opt.label)"
                >
                  <span class="cfg-prov-icon"><AegisIcon :name="opt.icon" :size="16" /></span>
                  <span class="cfg-prov-name">{{ opt.label }}</span>
                  <span class="cfg-check"></span>
                </div>
              </div>
            </div></Transition>
          </div>

          <!-- 2. SPECIALTIES -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-specialties' }" id="cfg-specialties">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-specialties')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="star" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Your Specialties</div>
                  <div class="cfg-panel-subtitle">Select all specialties that apply to your practice</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('specialties') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-specialties'" class="cfg-panel-body">
              <input v-model="cfgSearch.specialties" class="cfg-search" type="text" placeholder="Search specialties..." />
              <template v-for="group in filteredSpecialtyGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-tag-grid">
                  <span
                    v-for="tag in group.items"
                    :key="tag"
                    class="ctag"
                    :class="{ selected: cfgSelected.specialties.includes(tag) }"
                    @click="toggleCfg('specialties', tag)"
                  >{{ tag }}</span>
                </div>
              </template>
            </div></Transition>
          </div>

          <!-- 3. TREATMENT APPROACHES -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-approaches' }" id="cfg-approaches">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-approaches')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="leaves" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Treatment Approaches</div>
                  <div class="cfg-panel-subtitle">Modalities you use in practice — CBT, DBT, EMDR, IFS, and more</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('approaches') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-approaches'" class="cfg-panel-body">
              <input v-model="cfgSearch.approaches" class="cfg-search" type="text" placeholder="Search treatment approaches..." />
              <template v-for="group in filteredApproachGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-tag-grid">
                  <span
                    v-for="tag in group.items"
                    :key="tag"
                    class="ctag"
                    :class="{ selected: cfgSelected.approaches.includes(tag) }"
                    @click="toggleCfg('approaches', tag)"
                  >{{ tag }}</span>
                </div>
              </template>
            </div></Transition>
          </div>

          <!-- 4. INSURANCE ACCEPTED -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-insurance' }" id="cfg-insurance">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-insurance')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="credit-card" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Insurance Accepted</div>
                  <div class="cfg-panel-subtitle">Select all insurance plans you currently accept</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('insurance') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-insurance'" class="cfg-panel-body">
              <input v-model="cfgSearch.insurance" class="cfg-search" type="text" placeholder="Search insurance..." />
              <template v-for="group in filteredInsuranceGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-insurance-grid">
                  <div
                    v-for="opt in group.items"
                    :key="opt"
                    class="cfg-insurance-item"
                    :class="{ selected: cfgSelected.insurance.includes(opt) }"
                    @click="toggleCfg('insurance', opt)"
                  >
                    <span>{{ opt }}</span>
                    <AegisIcon v-if="cfgSelected.insurance.includes(opt)" name="check" :size="14" />
                  </div>
                </div>
              </template>
            </div></Transition>
          </div>

          <!-- 5. CREDENTIALS & LICENSES -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-credentials' }" id="cfg-credentials">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-credentials')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="graduation-cap" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Credentials &amp; Licenses</div>
                  <div class="cfg-panel-subtitle">Select all credentials and licenses you hold</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('credentials') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-credentials'" class="cfg-panel-body">
              <input v-model="cfgSearch.credentials" class="cfg-search" type="text" placeholder="Search credentials..." />
              <template v-for="group in filteredCredentialGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-tag-grid">
                  <span
                    v-for="tag in group.items"
                    :key="tag"
                    class="ctag"
                    :class="{ selected: cfgSelected.credentials.includes(tag) }"
                    @click="toggleCfg('credentials', tag)"
                  >{{ tag }}</span>
                </div>
              </template>
              <div class="cfg-subcat">License Numbers</div>
              <input v-model="cfgFields.license_number" class="form-input" type="text" placeholder="Comma-separate multiple license numbers (kept private)" />
            </div></Transition>
          </div>

          <!-- 6. SERVICES & FORMAT -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-services' }" id="cfg-services">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-services')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="umbrella-2" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Services Offered &amp; Format</div>
                  <div class="cfg-panel-subtitle">How and what you provide to clients</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('services') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-services'" class="cfg-panel-body">
              <template v-for="group in cfgServicesGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-tag-grid">
                  <span
                    v-for="tag in group.items"
                    :key="tag"
                    class="ctag"
                    :class="{ selected: cfgSelected.services.includes(tag) }"
                    @click="toggleCfg('services', tag)"
                  >{{ tag }}</span>
                </div>
              </template>
            </div></Transition>
          </div>

          <!-- 7. LOCATION & PRACTICE GEOGRAPHY -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-location' }" id="cfg-location">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-location')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="map-pin" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Location &amp; Practice Geography</div>
                  <div class="cfg-panel-subtitle">Where you are licensed and available to practice</div>
                </div>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-location'" class="cfg-panel-body">
              <div class="cfg-subcat">Primary Practice Location</div>
              <div class="cfg-field-row nw-cfg-2col">
                <div>
                  <label class="cfg-field-label">Primary State</label>
                  <select v-model="cfgFields.primary_state" class="form-select">
                    <option value="">Select state...</option>
                    <optgroup v-for="region in primaryStates" :key="region.region" :label="region.region">
                      <option v-for="[code, name] in region.items" :key="code" :value="code">{{ name }}</option>
                    </optgroup>
                  </select>
                </div>
                <div>
                  <label class="cfg-field-label">Years in Practice</label>
                  <select v-model="cfgFields.years_in_practice" class="form-select">
                    <option value="">Select...</option>
                    <option v-for="y in yearsInPracticeOptions" :key="y">{{ y }}</option>
                  </select>
                </div>
              </div>

              <div class="cfg-subcat">Additional Licensed States</div>
              <p class="nw-cfg-help">Select all states where you hold an active license</p>
              <div class="cfg-state-grid">
                <div
                  v-for="st in usStates"
                  :key="st"
                  class="cfg-state-item"
                  :class="{ selected: cfgSelected.states.includes(st) }"
                  @click="toggleCfg('states', st)"
                >{{ st }}</div>
              </div>
            </div></Transition>
          </div>

          <!-- 8. PROVIDER DEMOGRAPHICS -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-demographics' }" id="cfg-demographics">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-demographics')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="user" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Provider Demographics</div>
                  <div class="cfg-panel-subtitle">Helps clients find culturally matched practitioners</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('demographics') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-demographics'" class="cfg-panel-body">
              <div class="cfg-subcat">Sex &amp; Pronouns</div>
              <div class="cfg-field-row nw-cfg-2col">
                <div>
                  <label class="cfg-field-label">Sex Assigned at Birth</label>
                  <select v-model="cfgFields.sex_assigned" class="form-select">
                    <option v-for="opt in sexAssignedOptions" :key="opt">{{ opt }}</option>
                  </select>
                </div>
              </div>
              <template v-for="group in demographicsGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-tag-grid">
                  <span
                    v-for="tag in group.items"
                    :key="tag"
                    class="ctag"
                    :class="{ selected: cfgSelected.demographics.includes(tag) }"
                    @click="toggleCfg('demographics', tag)"
                  >{{ tag }}</span>
                </div>
              </template>
            </div></Transition>
          </div>

          <!-- 9. LANGUAGES SPOKEN -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-languages' }" id="cfg-languages">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-languages')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="send-2" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Languages Spoken</div>
                  <div class="cfg-panel-subtitle">Languages you can conduct sessions in</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('languages') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-languages'" class="cfg-panel-body">
              <input v-model="cfgSearch.languages" class="cfg-search" type="text" placeholder="Search languages..." />
              <div class="cfg-tag-grid">
                <span
                  v-for="lang in filteredLanguageOptions"
                  :key="lang"
                  class="ctag"
                  :class="{ selected: cfgSelected.languages.includes(lang) }"
                  @click="toggleCfg('languages', lang)"
                >{{ lang }}</span>
              </div>
            </div></Transition>
          </div>

          <!-- 10. IDENTITY & AFFILIATIONS -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-identity' }" id="cfg-identity">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-identity')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="circle-user" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Identity &amp; Affiliations</div>
                  <div class="cfg-panel-subtitle">LGBTQ+ identity and religious/spiritual orientation — all optional</div>
                </div>
              </div>
              <div class="cfg-panel-meta" style="display:flex;align-items:center;gap:10px">
                <span class="cfg-selected-count show">{{ selectedCount('identity') }} selected</span>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-identity'" class="cfg-panel-body">
              <template v-for="group in cfgIdentityGroups" :key="group.subcat">
                <div class="cfg-subcat">{{ group.subcat }}</div>
                <div class="cfg-tag-grid">
                  <span
                    v-for="tag in group.items"
                    :key="tag"
                    class="ctag"
                    :class="{ selected: cfgSelected.identity.includes(tag) }"
                    @click="toggleCfg('identity', tag)"
                  >{{ tag }}</span>
                </div>
              </template>
            </div></Transition>
          </div>

          <!-- 11. RATES & AVAILABILITY -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-rates' }" id="cfg-rates">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-rates')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="dollar" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Rates &amp; Availability</div>
                  <div class="cfg-panel-subtitle">Session fees, sliding scale, and network preferences</div>
                </div>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-rates'" class="cfg-panel-body">
              <div class="cfg-subcat">Session Rate (Out-of-Pocket / Self-Pay)</div>
              <div class="cfg-field-row nw-cfg-2col">
                <div>
                  <label class="cfg-field-label">Session Length</label>
                  <select v-model="cfgFields.session_length" class="form-select">
                    <option v-for="opt in sessionLengthOptions" :key="opt">{{ opt }}</option>
                  </select>
                </div>
                <div>
                  <label class="cfg-field-label">Rate per Session</label>
                  <div class="nw-cfg-currency">
                    <span class="nw-cfg-currency-symbol">$</span>
                    <input v-model.number="cfgFields.rate_per_session" class="form-input" type="number" min="0" step="5" placeholder="200" />
                  </div>
                </div>
              </div>

              <div class="cfg-subcat">Sliding Scale</div>
              <div class="cfg-tag-grid">
                <span
                  v-for="opt in slidingScaleOptions"
                  :key="opt"
                  class="ctag"
                  :class="{ selected: cfgSelected.rates.includes(opt) }"
                  @click="toggleCfg('rates', opt)"
                >{{ opt }}</span>
              </div>
              <div class="cfg-field-row nw-cfg-2col">
                <div>
                  <label class="cfg-field-label">Sliding Scale Min ($)</label>
                  <input v-model.number="cfgFields.sliding_scale_min" class="form-input" type="number" min="0" step="5" placeholder="80" />
                </div>
                <div>
                  <label class="cfg-field-label">Sliding Scale Max ($)</label>
                  <input v-model.number="cfgFields.sliding_scale_max" class="form-input" type="number" min="0" step="5" placeholder="180" />
                </div>
              </div>

              <div class="cfg-subcat">Network Preferences</div>
              <div class="cfg-field-row nw-cfg-4col">
                <div>
                  <label class="cfg-field-label">Max Provider Partners</label>
                  <select v-model="cfgFields.max_partners" class="form-select">
                    <option v-for="opt in maxPartnersOptions" :key="opt">{{ opt }}</option>
                  </select>
                </div>
                <div>
                  <label class="cfg-field-label">Geographic Search Radius</label>
                  <select v-model="cfgFields.geographic_radius" class="form-select">
                    <option v-for="opt in geographicRadiusOptions" :key="opt">{{ opt }}</option>
                  </select>
                </div>
                <div>
                  <label class="cfg-field-label">Default Referral Urgency</label>
                  <select v-model="cfgFields.referral_urgency" class="form-select">
                    <option v-for="opt in referralUrgencyOptions" :key="opt">{{ opt }}</option>
                  </select>
                </div>
                <div>
                  <label class="cfg-field-label">AI Matching Frequency</label>
                  <select v-model="cfgFields.ai_match_frequency" class="form-select">
                    <option v-for="opt in aiMatchFrequencyOptions" :key="opt">{{ opt }}</option>
                  </select>
                </div>
              </div>
            </div></Transition>
          </div>

          <!-- 12. NETWORK NOTIFICATIONS -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-notifications' }" id="cfg-notifications">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-notifications')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="bell-2" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Network Notifications</div>
                  <div class="cfg-panel-subtitle">Control what alerts you receive</div>
                </div>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-notifications'" class="cfg-panel-body">
              <div
                v-for="n in notifSettings"
                :key="n.key"
                class="toggle-wrap"
              >
                <div>
                  <div class="toggle-info-label">{{ n.label }}</div>
                  <div class="toggle-info-sub">{{ n.desc }}</div>
                </div>
                <button type="button" class="toggle" :class="{ on: cfgNotifications[n.key] }" @click="cfgNotifications[n.key] = !cfgNotifications[n.key]"></button>
              </div>
            </div></Transition>
          </div>

          <!-- 13. PRIVACY & AI MATCHING -->
          <div class="cfg-panel" :class="{ open: activeConfigPanel === 'cfg-privacy' }" id="cfg-privacy">
            <div class="cfg-panel-header" @click="toggleConfigPanel('cfg-privacy')">
              <div class="cfg-panel-header-left">
                <div class="cfg-panel-icon"><AegisIcon name="lock-closed" :size="16" /></div>
                <div>
                  <div class="cfg-panel-title">Privacy &amp; AI Matching</div>
                  <div class="cfg-panel-subtitle">Control your visibility and how the AI uses your data</div>
                </div>
              </div>
              <span class="cfg-chevron"><AegisIcon name="chevron-down" :size="16" /></span>
            </div>
            <Transition name="cfg-slide"><div v-show="activeConfigPanel === 'cfg-privacy'" class="cfg-panel-body">
              <div
                v-for="p in privacyToggles"
                :key="p.key"
                class="toggle-wrap"
              >
                <div>
                  <div class="toggle-info-label">{{ p.label }}</div>
                  <div class="toggle-info-sub">{{ p.desc }}</div>
                </div>
                <button type="button" class="toggle" :class="{ on: cfgPrivacy[p.key] }" @click="cfgPrivacy[p.key] = !cfgPrivacy[p.key]"></button>
              </div>
            </div></Transition>
          </div>

          <!-- Save bar -->
          <div class="nw-cfg-save-bar">
            <span class="cfg-save-hint">{{ configDirtyCount }} unsaved change{{ configDirtyCount === 1 ? '' : 's' }}</span>
            <button type="button" class="btn btn-outline" :disabled="cfgResetting" @click="resetConfig">
              {{ cfgResetting ? 'Resetting…' : 'Reset Changes' }}
            </button>
            <button type="button" class="btn btn-primary" :disabled="cfgSaving" @click="saveConfig">
              <AegisIcon :name="cfgSaving ? 'refresh' : 'save'" :size="14" :style="cfgSaving ? 'animation:spin 1s linear infinite' : ''" />
              {{ cfgSaving ? 'Saving…' : 'Save Configuration' }}
            </button>
          </div>
        </div>
      </div>
    </div><!-- /config -->

    <!-- ══════════ CONTINUITY STEWARDS ══════════ -->
    <div v-show="scope === 'cs'">

      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Find a Continuity Steward</div>
          <div>Browse licensed clinicians registered as Business CS on Aegis. Designate one and they receive a formal invitation to accept and countersign your plan.</div>
        </div>
      </div>

      <div class="sbp-layout">

        <!-- ── CS FILTER SIDEBAR — mirrors sbpFilterSidebar exactly ── -->
        <aside class="filter-sidebar" id="csSidebar">
          <div class="filter-sidebar-header">
            <div class="filter-sidebar-title"><AegisIcon name="filter" :size="14" /> Filters</div>
            <button type="button" class="filter-clear-btn" @click="csClearAll">Clear All</button>
          </div>

          <!-- Availability toggle -->
          <div class="filter-group nw-sbp-clinical-toggle">
            <label class="nw-sbp-clinical-label">
              <span class="nw-sbp-clinical-text">
                <AegisIcon name="check-circle" :size="14" />
                Accepting new CS roles
                <span class="sbp-info-tip" data-tooltip="Show only Continuity Stewards currently accepting new agreements."><AegisIcon name="info" :size="12" /></span>
              </span>
              <button type="button" class="toggle" :class="{ on: csAvailOnly }" @click="csAvailOnly = !csAvailOnly" aria-label="Show available only"></button>
            </label>
          </div>

          <!-- 1. Specialty -->
          <div class="filter-group" :class="{ open: csGroups.specialty }">
            <div class="filter-group-header" @click="csGroups.specialty = !csGroups.specialty">
              <span class="filter-group-label">
                <AegisIcon name="star" :size="16" /> Specialty
                <span v-if="csSpecialty" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <span
                v-for="s in (csFilters?.specialties ?? [])" :key="s"
                class="ftag"
                :class="{ selected: csSpecialty === s }"
                @click="csSpecialty = csSpecialty === s ? '' : s"
              >{{ s }}</span>
            </div>
          </div>

          <!-- 2. License State -->
          <div class="filter-group" :class="{ open: csGroups.state }">
            <div class="filter-group-header" @click="csGroups.state = !csGroups.state">
              <span class="filter-group-label">
                <AegisIcon name="map-pin" :size="16" /> License State
                <span v-if="csState" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <input class="filter-inner-search" type="text" placeholder="Search states..." v-model="csStateSearch" />
              <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">
                <span
                  v-for="st in filteredCsStates" :key="st"
                  class="ftag"
                  :class="{ selected: csState === st }"
                  @click="csState = csState === st ? '' : st"
                >{{ st }}</span>
              </div>
            </div>
          </div>

          <!-- 3. Rate Range -->
          <div class="filter-group" :class="{ open: csGroups.rate }">
            <div class="filter-group-header" @click="csGroups.rate = !csGroups.rate">
              <span class="filter-group-label">
                <AegisIcon name="dollar" :size="16" /> Rate / Incident
                <span v-if="csRateMax > 0" class="filter-group-count visible">1</span>
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <div class="filter-subcat">Quick range</div>
              <span class="ftag" :class="{ selected: csRateMax===0 }"   @click="csRateMax=0">Any</span>
              <span class="ftag" :class="{ selected: csRateMax===100 }" @click="csRateMax=100">Under $100</span>
              <span class="ftag" :class="{ selected: csRateMax===200 }" @click="csRateMax=200">Under $200</span>
              <span class="ftag" :class="{ selected: csRateMax===300 }" @click="csRateMax=300">Under $300</span>
              <span class="ftag" :class="{ selected: csRateMax===500 }" @click="csRateMax=500">Under $500</span>
              <div class="filter-subcat">Custom max ($/incident)</div>
              <div class="sbp-range-row">
                <input v-model.number="csRateCustom" class="filter-inner-search" type="number" placeholder="e.g. 250" min="0" @change="csRateMax = csRateCustom || 0" />
              </div>
            </div>
          </div>

          <!-- 4. Sort -->
          <div class="filter-group" :class="{ open: csGroups.sort }">
            <div class="filter-group-header" @click="csGroups.sort = !csGroups.sort">
              <span class="filter-group-label">
                <AegisIcon name="trending-up" :size="16" /> Sort By
              </span>
              <span class="filter-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </div>
            <div class="filter-group-body">
              <label v-for="opt in csSortOptions" :key="opt.val" class="sbp-radio-row" @click="csSort = opt.val">
                <div class="sbp-radio" :class="{ active: csSort === opt.val }"></div>
                <div>
                  <div class="sbp-radio-label">{{ opt.label }}</div>
                  <div v-if="opt.sub" class="sbp-radio-sub">{{ opt.sub }}</div>
                </div>
              </label>
            </div>
          </div>

          <div class="filter-sidebar-apply">
            <button type="button" class="btn btn-primary" @click="toast.success('Showing ' + filteredCS.length + ' Continuity Steward' + (filteredCS.length === 1 ? '' : 's'))">Apply Filters</button>
          </div>
        </aside>

        <!-- ── RESULTS PANEL ── -->
        <div class="results-panel">

          <!-- Topbar — count + sort select -->
          <div class="results-topbar">
            <div class="results-count">
              <strong>{{ filteredCS.length }}</strong>
              {{ filteredCS.length !== (csStewards?.length ?? 0) ? `of ${csStewards?.length ?? 0} ` : '' }}Continuity Steward{{ filteredCS.length === 1 ? '' : 's' }}
            </div>
            <div class="results-sort">
              <span class="nw-sort-label">Sort:</span>
              <select v-model="csSort" class="form-select nw-sort-select" style="min-width:160px">
                <option value="best">Best Match</option>
                <option value="rate_asc">Rate: Low to High</option>
                <option value="rate_desc">Rate: High to Low</option>
                <option value="newest">Newest</option>
              </select>
            </div>
          </div>

          <!-- Search toolbar — full width row below topbar -->
          <div class="pn-toolbar" style="margin-bottom:14px;">
            <div class="pn-search-wrap">
              <span class="search-icon"><AegisIcon name="search-lg" :size="14" /></span>
              <input v-model="csSearch" class="form-input" type="text" placeholder="Search by name, specialty, bio..." />
            </div>
          </div>

          <div class="search-results-grid">
            <div
              v-for="cs in filteredCS"
              :key="cs.id"
              class="sbp-card spc-card"
              @click="viewProfile(cs.slug, 'cs')"
            >
              <div class="spc-top-pills">
                <!-- Designation status pill — takes priority over availability -->
                <span v-if="cs.designation_status === 'active'"
                  class="spc-status-icon ok"
                  data-tooltip="You have an active agreement with this CS"
                >
                  <AegisIcon name="check-circle" :size="12" />
                </span>
                <span v-else-if="cs.designation_status === 'pending' || cs.designation_status === 'invited'"
                  class="spc-status-icon pend"
                  data-tooltip="Invitation sent — awaiting their response"
                >
                  <AegisIcon name="clock" :size="12" />
                </span>
                <span v-else
                  class="spc-status-icon"
                  :class="cs.cs_availability ? 'ok' : 'off'"
                  :data-tooltip="cs.cs_availability ? 'Accepting new CS roles' : 'Not currently available'"
                >
                  <AegisIcon :name="cs.cs_availability ? 'user-check' : 'clock'" :size="12" />
                </span>
              </div>

              <div class="spc-body">
                <div class="spc-avatar">{{ cs.avatar_initials }}</div>
                <div class="spc-name">{{ cs.display_name }}<span v-if="cs.credentials" style="font-size:11px;color:var(--text-3);font-weight:400;">, {{ cs.credentials }}</span></div>
                <div class="spc-role" v-if="cs.license_state">Licensed in {{ cs.license_state }}</div>
                <div class="spc-loc" v-if="cs.location">{{ cs.location }}</div>
                <div class="spc-tags" v-if="cs.specialties?.length">
                  <span v-for="tag in (cs.specialties ?? []).slice(0,3)" :key="tag" class="spc-tag">{{ tag }}</span>
                  <span v-if="(cs.specialties ?? []).length > 3" class="spc-tag spc-tag-more">+{{ (cs.specialties ?? []).length - 3 }}</span>
                </div>
              </div>

              <div v-if="cs.rate_min_cents || cs.rate_max_cents" class="spc-stats">{{ formatCsRate(cs) }}</div>

              <div class="spc-actions" @click.stop>
                <!-- Active agreement -->
                <span v-if="cs.designation_status === 'active'"
                  class="badge badge-green"
                  style="flex:1;justify-content:center;font-size:12px;display:inline-flex;align-items:center;gap:5px;padding:8px 12px;border-radius:var(--radius);"
                >
                  <AegisIcon name="check-circle" :size="13" /> Active CS Agreement
                </span>

                <!-- Pending invitation -->
                <span v-else-if="cs.designation_status === 'pending' || cs.designation_status === 'invited'"
                  class="badge badge-yellow"
                  style="flex:1;justify-content:center;font-size:12px;display:inline-flex;align-items:center;gap:5px;padding:8px 12px;border-radius:var(--radius);"
                >
                  <AegisIcon name="clock" :size="13" /> Invitation Sent
                </span>

                <!-- Available to designate -->
                <button
                  v-else
                  type="button"
                  class="btn btn-primary"
                  style="flex:1;justify-content:center;font-size:12px;display:inline-flex;align-items:center;gap:5px;"
                  :disabled="!cs.cs_availability"
                  :data-tooltip="!cs.cs_availability ? 'Not currently accepting new CS roles' : 'Send a formal CS designation invitation'"
                  @click="openDesignate(cs)"
                >
                  <AegisIcon name="shield" :size="13" /> Designate as My CS
                </button>

                <button type="button" class="btn-icon" data-tooltip="View Profile" @click="viewProfile(cs.slug, 'cs')">
                  <AegisIcon name="eye" :size="14" />
                </button>
              </div>
            </div>
          </div>

          <AegisEmptyState
            v-if="!filteredCS.length"
            icon="shield"
            title="No Continuity Stewards match your search"
            description="Try adjusting your filters or clearing the search."
          >
            <template #actions>
              <button type="button" class="btn btn-outline" @click="csClearAll">Clear All Filters</button>
            </template>
          </AegisEmptyState>
        </div>
      </div>
    </div><!-- /cs stewards -->

    <!-- ══════════ MODALS ══════════ -->

    <!-- Review Pending Requests -->
    <AegisModal v-model="modals.reviewRequests" title="Pending Connection Requests" :subtitle="pendingRequests.length + ' awaiting · ' + clinicalCount + ' clinical, ' + businessCount + ' business'" size="lg">
      <div class="alert alert-info" style="margin-bottom:20px">
        <AegisIcon name="info" :size="14" />
        <div><strong>Accepting</strong> adds the connection and notifies the requester. <strong>Declining</strong> removes the request — the other party is not notified.</div>
      </div>

      <div v-if="!pendingRequests.length" style="text-align:center;padding:36px;font-size:13px;color:var(--text-4)">
        <AegisIcon name="check" :size="28" />
        <div style="margin-top:8px">All requests have been reviewed.</div>
      </div>

      <div v-else style="display:flex;flex-direction:column;gap:12px">
        <div v-for="req in pagedPendingRequests" :key="req.id" class="card" style="padding:20px;gap:0">

          <!-- Identity row -->
          <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:12px">
            <div class="person-avatar" style="flex-shrink:0;width:44px;height:44px;font-size:14px;font-weight:700;border-radius:var(--radius-full)">
              {{ req.requester_initials }}
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:15px;font-weight:700;margin-bottom:3px">
                <a :href="req.request_type === 'business' ? route('public.bp', req.requester_slug) : route('public.provider', req.requester_slug)"
                   target="_blank" style="color:var(--gold-dark);text-decoration:none;font-weight:700" @click.stop>
                  {{ req.requester_name }}
                </a>
              </div>
              <div style="font-size:12px;color:var(--text-3);line-height:1.4">
                {{ req.requester_role }}<span v-if="req.requester_location"> · {{ req.requester_location }}</span>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0">
              <span class="badge" :class="req.request_type === 'business' ? 'badge-gold' : 'badge-green'">
                <AegisIcon :name="req.request_type === 'business' ? 'briefcase' : 'users'" :size="10" />
                {{ req.request_type === 'business' ? 'Business' : 'Clinical' }}
              </span>
              <span style="font-size:11px;color:var(--text-4);font-weight:500">{{ timeAgo(req.created_at) }}</span>
            </div>
          </div>

          <!-- Message preview -->
          <div v-if="req.message" style="padding:10px 14px;background:var(--surface-2);border-left:3px solid var(--gold-dark);border-radius:0 var(--radius-sm) var(--radius-sm) 0;font-size:12.5px;color:var(--text-2);font-style:italic;line-height:1.6;margin-bottom:14px">
            "{{ req.message }}"
          </div>

          <!-- Actions — no eye icon; name is the link -->
          <div style="display:flex;align-items:center;gap:8px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="button" class="btn btn-primary btn-sm" :disabled="pendingActionId === req.id" @click="acceptRequest(req)">
              <AegisIcon name="check" :size="12" /> Accept
            </button>
            <button type="button" class="btn btn-outline btn-sm" :disabled="pendingActionId === req.id" @click="declineRequest(req)">
              Decline
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <AegisPagination
        v-if="prTotalPages > 1"
        :current-page="prPage"
        :total-pages="prTotalPages"
        :show-meta="false"
        style="margin-top:20px"
        @change="prPage = $event"
      />

      <template #footer>
        <span style="font-size:12px;color:var(--text-4)">{{ pendingRequests.length }} total · showing {{ PR_PER_PAGE }} per page</span>
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
    <ConnectionRequestModal
      :recipient-id="connectTarget.id"
      :recipient-name="connectTarget.name"
      :recipient-role="connectTarget.role"
      @sent="router.reload({ only: ['clinicalConnections', 'pendingRequests', 'stats'] })"
    />

    <!-- Service Request Modal — centralized. provider-id / provider-label
         are bound reactively so the modal always targets the card the user
         clicked from (Search Providers, My Network, Recommended Shadow etc). -->
    <ServiceRequestModal
      ref="svcModalRef"
      :provider-id="svcTarget.id"
      :provider-label="svcTarget.label"
    />

    <!-- BP Hire Modal — centralized BpEngageModal (4-step wizard) -->
    <BpEngageModal
      v-model="modals.bpHire"
      :partner="bpHireTarget"
      @submitted="toast.success('Engagement request sent — partner notified via Aegis.')"
    />

    <!-- BP Quote Modal — centralized -->
    <BpQuoteModal
      v-model="modals.bpQuote"
      :partner="bpQuoteTarget"
    />

    <!-- BP Schedule Consultation Modal — centralized -->
    <BpScheduleModal
      v-model="modals.bpSchedule"
      :partner="bpScheduleTarget"
    />

    <!-- Post Job — centralized 4-step wizard (PostJobModal.vue).
         Posts to provider.jobs.store. Replaces the old local one-step form
         so the Network tab and the dedicated Support & Services page use
         the same wizard. -->
    <PostJobModal v-model="modals.postJob" />

    <!-- Add to Referral List — local, small. Creates a manual ShadowConnection
         via provider.network.shadow.add so a practitioner can jot down a
         referral partner who isn't (yet) on Aegis. No email is sent. -->
    <AegisModal v-model="modals.manualReferralEntry" title="Add to Referral List" size="md">
      <div class="alert alert-gold" style="margin-bottom:14px">
        <AegisIcon name="info" :size="14" />
        <div>Use this to keep a private note of a provider you refer to. No email is sent — the entry is visible only to you under <strong>Referrals &amp; Tools · My Shadows</strong>.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Provider Name <span class="req">*</span></label>
        <input
          class="form-input"
          type="text"
          v-model="manualShadowForm.display_name"
          placeholder="e.g. Dr. Alex Rivera, MD"
          :class="{ 'is-error': manualShadowForm.errors.display_name }"
        />
        <div v-if="manualShadowForm.errors.display_name" class="form-error">{{ manualShadowForm.errors.display_name }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Note (optional)</label>
        <textarea
          class="form-textarea"
          v-model="manualShadowForm.note"
          rows="3"
          placeholder="Why you refer to this provider — specialty, patient fit, contact info…"
          :class="{ 'is-error': manualShadowForm.errors.note }"
        ></textarea>
        <div v-if="manualShadowForm.errors.note" class="form-error">{{ manualShadowForm.errors.note }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" :disabled="manualShadowForm.processing" @click="modals.manualReferralEntry = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="manualShadowForm.processing" @click="submitManualShadow">
          <AegisIcon name="user-plus" :size="14" />
          {{ manualShadowForm.processing ? 'Saving…' : 'Add to List' }}
        </button>
      </template>
    </AegisModal>

    <!-- Centralized ReferralModal — wired via openModal('referralModal').
         :roster uses controller-served VaultItem-based referralRoster so the
         client picker matches Dashboard exactly. -->
    <ReferralModal
      :roster="referralRoster.length ? referralRoster : roster"
      :network="referralNetwork"
      :preselected-recipient="referralPreselectRecipient"
    />
    <!-- Designate CS Modal — centralized DesignateCsModal -->
    <DesignateCsModal
      v-model="showDesignateModal"
      :preselected-user="designateTarget"
      context="network"
      @success="onCsDesignated"
    />

    </div><!-- /nw-page-root -->

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisPagination        from '@/components/ui/AegisPagination.vue'
import ReferralModal          from '@/components/modals/ReferralModal.vue'
import ServiceRequestModal    from '@/components/modals/ServiceRequestModal.vue'
import ConnectionRequestModal from '@/components/modals/ConnectionRequestModal.vue'
import PostJobModal           from '@/components/modals/PostJobModal.vue'
import BpEngageModal          from '@/components/modals/BpEngageModal.vue'
import BpQuoteModal           from '@/components/modals/BpQuoteModal.vue'
import BpScheduleModal        from '@/components/modals/BpScheduleModal.vue'
import DesignateCsModal       from '@/components/modals/DesignateCsModal.vue'
import { useModal }         from '@/composables/useModal'
import { useToast }         from '@/composables/useToast'
import { useConfirm }       from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  clinicalConnections:          { type: Array,  default: () => [] },
  bpConnections:                { type: Array,  default: () => [] },
  bpDirectory:                  { type: Array,  default: () => [] },
  pendingRequests:              { type: Array,  default: () => [] },
  shadowConnections:            { type: Array,  default: () => [] },
  referralNetwork:              { type: Array,  default: () => [] },
  recommendedPartnerCategories: { type: Array,  default: () => [] },
  recommendedShadowProviders:   { type: Array,  default: () => [] },
  searchProviders:              { type: Array,  default: () => [] },
  referralCandidates:           { type: Array,  default: () => [] },
  referralRoster:               { type: Array,  default: () => [] },
  roster:                       { type: Array,  default: () => [] },
  stats:                        { type: Object, default: () => ({}) },
  networkConfig:                { type: Object, default: () => ({}) },
  csStewards:                   { type: Array,  default: () => [] },
  csFilters:                    { type: Object, default: () => ({ specialties: [], states: [] }) },
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

// ── Infinite scroll sentinels ─────────────────────────────────────────────
// Template refs for the sentinel divs placed after each results grid.
// When they scroll into view, we increment the page counter — no button click needed.
const spSentinel = ref(null)   // Search Providers sentinel
const bpSentinel = ref(null)   // Search Partners sentinel
let spObserver   = null
let bpObserver   = null

function makeObserver(hasMore, incrementPage) {
  return new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && hasMore.value) {
        incrementPage()
      }
    },
    { rootMargin: '200px' }
  )
}

onUnmounted(() => {
  spObserver?.disconnect()
  bpObserver?.disconnect()
})

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
  ;[allProviders, rtCandidates].forEach((r) => {
    r.value.forEach((p) => { if (!p.slug) p.slug = slugify(p.name) })
  })
  if (rnpTrack.value) {
    rnpTrack.value.addEventListener('scroll', () => updateArrows(rnpTrack.value, rnpAtStart, rnpAtEnd))
    updateArrows(rnpTrack.value, rnpAtStart, rnpAtEnd)
  }
  if (spcTrack.value) {
    spcTrack.value.addEventListener('scroll', () => updateArrows(spcTrack.value, spcAtStart, spcAtEnd))
    updateArrows(spcTrack.value, spcAtStart, spcAtEnd)
  }
  // Infinite scroll: observe sentinels for Search Providers + Search Partners
  if (spSentinel.value) {
    spObserver = makeObserver(hasMoreSearchResults, () => { spPage.value++ })
    spObserver.observe(spSentinel.value)
  }
  if (bpSentinel.value) {
    bpObserver = makeObserver(hasMorePartners, () => { bpPage.value++ })
    bpObserver.observe(bpSentinel.value)
  }
})

// ── Tab / scope state ──────────────────────────────────────────────────────
const scope       = ref('clinical')
const clinicalTab = ref('search')
const businessTab = ref('search')
const toolsTab    = ref('list')

// ── Local modal state ──────────────────────────────────────────────────────
const modals = reactive({
  reviewRequests:       false,
  inviteProvider:       false,
  bpHire:               false,
  bpQuote:              false,
  bpSchedule:           false,
  postJob:              false,
  manualReferralEntry:  false,
})

// ── Computed ───────────────────────────────────────────────────────────────
const clinicalCount = computed(() => props.pendingRequests.filter(r => r.request_type !== 'business').length)
const businessCount = computed(() => props.pendingRequests.filter(r => r.request_type === 'business').length)

// Pending requests pagination — 3 per page inside modal
const prPage      = ref(1)
const PR_PER_PAGE = 3
const prTotalPages = computed(() => Math.max(1, Math.ceil(props.pendingRequests.length / PR_PER_PAGE)))
const pagedPendingRequests = computed(() =>
  props.pendingRequests.slice((prPage.value - 1) * PR_PER_PAGE, prPage.value * PR_PER_PAGE)
)
// Reset to page 1 each time the modal opens
watch(() => modals.reviewRequests, (val) => { if (val) prPage.value = 1 })

// ── Accept / Decline ───────────────────────────────────────────────────────
const pendingActionId = ref(null)

function acceptRequest(req) {
  pendingActionId.value = req.id
  router.post(route('provider.network.accept', { networkRequest: req.id }), {}, {
    onSuccess: () => {
      toast.success(req.requester_name + ' accepted')
      modals.reviewRequests = false
      router.reload({ only: ['recommendedShadowProviders', 'searchProviders', 'bpDirectory', 'pendingRequests', 'stats'], preserveScroll: true })
    },
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

// Accept an inbound pending request from a search card (p.networkStatus === 'pending-received')
// Looks up the NetworkRequest ID from pendingRequests prop by requester_id
function acceptPendingRequest(p) {
  const userId = p.id ?? p.partner_id
  const req = props.pendingRequests.find(r => r.requester_id === userId)
  if (!req) { toast.error('Could not find the request. Try refreshing.'); return }
  pendingActionId.value = req.id
  router.post(route('provider.network.accept', { networkRequest: req.id }), {}, {
    onSuccess: () => {
      toast.success((p.name ?? p.display_name ?? 'Request') + ' accepted — now in your network.')
      router.reload({ only: ['searchProviders', 'bpDirectory', 'recommendedShadowProviders', 'pendingRequests', 'stats'], preserveScroll: true })
    },
    onError:  () => toast.error('Could not accept request.'),
    onFinish: () => { pendingActionId.value = null },
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
    () => router.delete(route('provider.network.shadow.remove', { shadowConnection: s.id }), {
      preserveScroll: true,
      onSuccess: () => toast.info('Shadow removed.'),
    })
  )
}

// Direct-add from RT candidate card — no modal, immediate POST
const shadowAddForm = useForm({ shadow_user_id: '', display_name: '' })
function addShadowDirect(s) {
  shadowAddForm.shadow_user_id = s.id    ?? ''
  shadowAddForm.display_name   = s.name  ?? s.shadow_name ?? ''
  shadowAddForm.post(route('provider.network.shadow.add'), {
    preserveScroll: true,
    onSuccess: () => {
      // Remove from list immediately — match by id when available, else by name
      if (s.id) {
        addedToShadowIds.value.add(s.id)
        rtCandidates.value = rtCandidates.value.filter(c => c.id !== s.id)
      } else {
        rtCandidates.value = rtCandidates.value.filter(c => c.name !== s.name)
      }
      toast.success((s.name || 'Provider') + ' added to My Shadows.')
      router.reload({ only: ['shadowConnections', 'referralCandidates', 'stats'], preserveScroll: true })
    },
    onError: () => toast.error('Could not add to shadows.'),
  })
}

// ── Invite Provider ────────────────────────────────────────────────────────
const inviteForm = useForm({ display_name: '', email: '', note: '', specialty: '', network_type: 'Network' })

function submitInvite() {
  inviteForm.post(route('provider.network.invite'), {
    onSuccess: () => { modals.inviteProvider = false; inviteForm.reset(); toast.success('Invitation sent!') },
  })
}

// ── Connect (send request) ─────────────────────────────────────────────────
// ── Connection Request — centralized via ConnectionRequestModal ────────────
const connectTarget = reactive({ id: '', name: '', role: '' })

function openConnect(p) {
  connectTarget.id   = p.id   ?? p.shadow_user_id ?? ''
  connectTarget.name = p.name ?? p.shadow_name    ?? ''
  connectTarget.role = p.role ?? p.shadow_role    ?? ''
  openModal('connectionRequestModal')
}

// ── ReferralModal — centralized via useModal ───────────────────────────────
const referralPreselectRecipient = ref(null)

function openReferralForProvider(p) {
  referralPreselectRecipient.value = {
    id:           p.id,
    display_name: p.name ?? p.display_name ?? '',
    slug:         p.slug ?? '',
    initials:     p.initials ?? '',
    avatar_url:   p.avatar_url ?? null,
    specialty:    Array.isArray(p.tags) ? p.tags.join(', ') : (p.specialty ?? ''),
    location:     p.location ?? '',
    is_connected: p.networkStatus === 'in-network',
  }
  openModal('referralModal')
}

function openReferralForConnection(nc) {
  referralPreselectRecipient.value = {
    id:           nc.partner_id,
    display_name: nc.partner_name ?? '',
    slug:         nc.partner_slug ?? '',
    initials:     nc.partner_initials ?? '',
    avatar_url:   nc.avatar_url ?? null,
    specialty:    nc.partner_specialty ?? '',
    location:     nc.partner_location ?? '',
    is_connected: true,
  }
  openModal('referralModal')
}

// Reset preselect after modal closes
watch(isOpen('referralModal'), (open) => {
  if (!open) referralPreselectRecipient.value = null
})

// Service Request — handled by centralized ServiceRequestModal.vue.
// The modal needs a provider-id and provider-label; we keep them in a
// reactive object so any card can retarget the modal at open time.
const svcModalRef = ref(null)
const svcTarget   = reactive({ id: '', label: '' })

/**
 * Open the Service Request modal for a given provider card.
 *
 * Overloads accepted (backwards-compatible with older callsites):
 *   openSvcRequest(serviceName, providerName)             — string form
 *   openSvcRequest(serviceName, providerObject)           — object form
 *   openSvcRequest({ service, id, label })                — one-arg form
 */
function openSvcRequest(serviceName, providerRef = '') {
  // One-arg form
  if (typeof serviceName === 'object' && serviceName !== null) {
    const cfg = serviceName
    svcTarget.id    = cfg.id    ?? ''
    svcTarget.label = cfg.label ?? ''
    svcModalRef.value?.preselect(cfg.service ?? 'Services')
  }
  // Object provider form
  else if (typeof providerRef === 'object' && providerRef !== null) {
    svcTarget.id    = providerRef.id   ?? providerRef.partner_id ?? ''
    svcTarget.label = providerRef.name ?? providerRef.partner_name ?? ''
    svcModalRef.value?.preselect(serviceName)
  }
  // Legacy: string provider name only (no id available — modal will
  // still open but the caller should migrate to the object form).
  else {
    svcTarget.id    = ''
    svcTarget.label = providerRef || ''
    svcModalRef.value?.preselect(serviceName)
  }
  openModal('serviceRequestModal')
}

// ── BP Hire ────────────────────────────────────────────────────────────────
const bpHireTarget = ref(null)

function openBpHire(p) {
  bpHireTarget.value = p
  modals.bpHire = true
}

// ── Quote request ──────────────────────────────────────────────────────────
const bpQuoteTarget = ref(null)

function openBpQuote(p) {
  bpQuoteTarget.value = { id: p.id ?? p.partner_id, display_name: p.display_name ?? p.name ?? p.partner_name }
  modals.bpQuote = true
}

// ── Consultation ───────────────────────────────────────────────────────────
const bpScheduleTarget = ref(null)

function openBpSchedule(p) {
  bpScheduleTarget.value = { id: p.id ?? p.partner_id, display_name: p.display_name ?? p.name ?? p.partner_name }
  modals.bpSchedule = true
}

// ── Post Job ───────────────────────────────────────────────────────────────
// The Post Job flow is now handled entirely by PostJobModal.vue (centralized
// 4-step wizard, posts to provider.jobs.store). No local form or submit
// handler is needed here — the modal owns its own useForm() instance.

// ── Add to Referral List (manual shadow add) ───────────────────────────────
const manualShadowForm = useForm({
  display_name: '',
  note:         '',
})

function openManualReferralEntry() {
  manualShadowForm.reset()
  manualShadowForm.clearErrors()
  modals.manualReferralEntry = true
}

function submitManualShadow() {
  manualShadowForm.post(route('provider.network.shadow.add'), {
    preserveScroll: true,
    onSuccess: () => {
      modals.manualReferralEntry = false
      manualShadowForm.reset()
      toast.success('Added to your referral list.')
    },
    onError: () => {
      toast.error('Could not add — check the highlighted fields.')
    },
  })
}

// ── Profile navigation ─────────────────────────────────────────────────────
function viewProfile(slug, kind = 'provider') {
  if (!slug) return
  const routeMap = {
    provider: 'public.provider',
    business: 'public.bp',
    cs:       'public.cs',
    ss:       'public.ss',
  }
  const routeName = routeMap[kind] ?? 'public.provider'
  router.visit(route(routeName, { slug }))
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
  clinicalServiceOnly.value = false
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
  let base = allProviders.value

  // Keyword search (clinicalSearch input in the toolbar)
  const q = clinicalSearch.value.toLowerCase().trim()
  if (q) base = base.filter(p => providerHaystack(p).includes(q))

  if (groups.length) {
    base = base.filter((p) => {
      const hay = providerHaystack(p)
      return groups.every(([, vals]) => vals.some((v) => hay.includes(String(v).toLowerCase())))
    })
  }

  // Sort
  if (searchSort.value === 'Highest Rated') base = [...base].sort((a, b) => (b.rating || 0) - (a.rating || 0))

  // Clinical-service toggle: float services-enabled providers to top
  if (clinicalServiceOnly.value) {
    return [...base].sort((a, b) => (b.has_services ? 1 : 0) - (a.has_services ? 1 : 0))
  }
  return base
})
const pagedSearchResults    = computed(() => searchResults.value.slice(0, spPage.value * PAGE_SIZE))
const hasMoreSearchResults  = computed(() => searchResults.value.length > pagedSearchResults.value.length)

function applyFilters() {
  for (const g of Object.keys(appliedFilters)) appliedFilters[g] = [...selectedFilters[g]]
  toast.success(activeFilters.value.length ? 'Filters applied' : 'Showing all providers')
}

/**
 * rnp-card arrow CTA handler.
 * 1. Clears all active filters (fresh drill-down).
 * 2. Routes label → `type` group if it's a known provider role, `specialty` otherwise.
 * 3. Commits to both selectedFilters + appliedFilters so searchResults reacts.
 * 4. Switches to Search Providers sub-tab.
 * 5. Smooth-scrolls to the results grid after Vue re-renders.
 */
/**
 * rnp-card arrow CTA — routes a recommended category card to the correct
 * search tab with the right filter pre-applied.
 *
 * Routing rules (matched against the seeded category labels):
 *   - `is-biz` tier (e.g. "Medical Billing") → Business Partners tab, bpSearch
 *   - Provider type labels (exact match in providerTypes) → clinical search, type filter
 *   - Everything else → clinical search, specialty filter (keyword search fallback)
 *
 * For labels not in providerTypes we also try a keyword match against the
 * provider haystack (name/role/tags) so categories like "Therapist / LCSW",
 * "Neurologist", "Primary Care", "Dietitian" still surface relevant results.
 */
function openCategoryFilter(cat) {
  const label = cat?.label
  if (!label) return

  // ── Business Partner category ─────────────────────────────────────────────
  if (cat.tier === 'is-biz' || cat.priority === 'biz') {
    bpSearch.value   = label          // pre-fill BP keyword search
    bpCategory.value = ''             // clear category select (keyword wins)
    scope.value      = 'business'
    businessTab.value = 'search'
    toast.info(`Showing ${label} business partners`)
    nextTick(() => {
      const el = document.querySelector('#sbpResultsGrid')
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
    return
  }

  // ── Clinical category ─────────────────────────────────────────────────────
  // Clear all clinical filters first
  for (const g of Object.keys(selectedFilters)) selectedFilters[g] = []
  for (const g of Object.keys(appliedFilters))  appliedFilters[g]  = []
  clinicalServiceOnly.value = false

  if (providerTypes.includes(label)) {
    // Exact provider-type match → type filter
    selectedFilters.type.push(label)
    appliedFilters.type.push(label)
  } else {
    // Fuzzy keyword: extract core word (drop " / LCSW" suffix) and search
    // across name/role/tags. "Dietitian" matches "Registered Dietitian",
    // "Therapist" matches all therapist variants, etc.
    clinicalSearch.value = label.split('/')[0].trim()
  }

  scope.value       = 'clinical'
  clinicalTab.value = 'search'
  toast.info(`Showing ${label} providers`)

  nextTick(() => {
    const el = document.querySelector('.search-results-grid')
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
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
// Clinical-service toggle for Search Providers sidebar
const clinicalServiceOnly = ref(false)

const bpSort         = ref('Best Match')
const bpClinicalOnly = ref(false)
const bpCategory     = ref('')

// ── Per-tab pagination ─────────────────────────────────────────────────────
const PAGE_SIZE      = 12
const spPage         = ref(1)  // Search Providers
const bpPage         = ref(1)  // Search Partners
const rtPage         = ref(1)  // Referral List
const myShadowPage   = ref(1)  // My Shadows
const myNetworkPage  = ref(1)  // My Network
const myPartnersPage = ref(1)  // My Partners

// Reset pages when filters change — placed here so all refs are already declared
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
const pagedClinical      = computed(() => filteredClinical.value.slice(0, myNetworkPage.value * PAGE_SIZE))
const hasMoreClinical    = computed(() => filteredClinical.value.length > pagedClinical.value.length)

const filteredBpConnections = computed(() => {
  const q = bizSearch.value.toLowerCase()
  if (!q) return props.bpConnections
  return props.bpConnections.filter(nc =>
    (nc.partner_name ?? '').toLowerCase().includes(q) ||
    (nc.partner_role ?? '').toLowerCase().includes(q)
  )
})
const pagedBpConnections    = computed(() => filteredBpConnections.value.slice(0, myPartnersPage.value * PAGE_SIZE))
const hasMoreBpConnections  = computed(() => filteredBpConnections.value.length > pagedBpConnections.value.length)

const filteredPartners = computed(() => {
  let results = businessPartners.value.filter(p => {
    const q = bpSearch.value.toLowerCase().trim()
    if (q) {
      const hay = [p.name, p.role, p.location, ...(p.tags || [])].join(' ').toLowerCase()
      if (!hay.includes(q)) return false
    }
    if (bpCategory.value) {
      const cat = (p.category || p.role || '').toLowerCase()
      if (!cat.includes(bpCategory.value.toLowerCase())) return false
    }
    if (bpTypeFilter.value) {
      const pt = (p.partnerType || '').toLowerCase()
      if (!pt.includes(bpTypeFilter.value)) return false
    }
    if (bpRateMin.value > 0 || bpRateMax.value < 9999) {
      const rateNum = parseInt(String(p.rate || '').replace(/[^0-9]/g, '')) || 0
      if (rateNum > 0 && (rateNum < bpRateMin.value || rateNum > bpRateMax.value)) return false
    }
    if (bpMinRating.value > 0 && (p.rating || 0) < bpMinRating.value) return false
    if (bpMinJobs.value > 0 && (p.jobs || 0) < bpMinJobs.value) return false
    if (bpLocation.value) {
      const loc = (p.location || '').toLowerCase()
      if (bpLocation.value === 'remote' && !loc.includes('remote')) return false
      if (bpLocation.value === 'onsite' && loc.includes('remote')) return false
    }
    return true
  })

  // Sort BPs
  if (bpSort.value === 'Highest Rated') results = [...results].sort((a, b) => (b.rating || 0) - (a.rating || 0))
  if (bpSort.value === 'Most Jobs')     results = [...results].sort((a, b) => (b.jobs || 0) - (a.jobs || 0))
  if (bpSort.value === 'Lowest Rate')   results = [...results].sort((a, b) => {
    const ra = a.rate_cents || (parseInt(String(a.rate || '').replace(/[^0-9]/g, '')) * 100) || 999999
    const rb = b.rate_cents || (parseInt(String(b.rate || '').replace(/[^0-9]/g, '')) * 100) || 999999
    return ra - rb
  })

  // Clinical-service toggle: prepend practitioners with services_mode=1 above BPs.
  // They are real Aegis providers offering services (supervision, consultation etc.)
  // to other practitioners — semantically different from BPs, shown with a distinct
  // "Clinical Services" badge. kind='provider' routes them to /public/provider/{slug}.
  if (bpClinicalOnly.value) {
    const q = bpSearch.value.toLowerCase().trim()
    const clinicians = allProviders.value
      .filter(p => p.has_services)
      .filter(p => {
        if (!q) return true
        const hay = [p.name, p.role, p.location, ...(p.tags || [])].join(' ').toLowerCase()
        return hay.includes(q)
      })
      .map(p => ({
        ...p,
        kind:        'provider',
        partnerType: 'CLINICAL',
        rate:        p.rate ?? '—',
        reviews:     p.reviews ?? 0,
        jobs:        p.jobs ?? 0,
      }))
    return [...clinicians, ...results]
  }

  return results
})
const pagedPartners      = computed(() => filteredPartners.value.slice(0, bpPage.value * PAGE_SIZE))
const hasMorePartners    = computed(() => filteredPartners.value.length > pagedPartners.value.length)

const filteredRtCandidates = computed(() => {
  const q = rtSearch.value.toLowerCase()
  // Filter by ID for real users; name-based filter handled by direct splice in addShadowDirect
  let list = rtCandidates.value.filter(s => !s.id || !addedToShadowIds.value.has(s.id))
  if (!q) return list
  return list.filter(s =>
    s.name.toLowerCase().includes(q) || s.role.toLowerCase().includes(q) || s.location.toLowerCase().includes(q)
  )
})
const pagedRtCandidates  = computed(() => filteredRtCandidates.value.slice(0, rtPage.value * PAGE_SIZE))
const hasMoreRtCandidates = computed(() => filteredRtCandidates.value.length > pagedRtCandidates.value.length)

function removeRtCandidate(s) {
  rtCandidates.value = rtCandidates.value.filter(c => c.name !== s.name)
}

// ── Recommendations (dynamic from network_recommendations table) ──────────
// Fed by NetworkController::index → NetworkService::getRecommended*.
// If the DB is empty, the shape falls back to [] and the carousels render
// their empty state without crashing.
const recommendedCategories = computed(() => props.recommendedPartnerCategories ?? [])
const aiShadowCandidates    = computed(() => props.recommendedShadowProviders   ?? [])

// Search Providers directory — server-serialized from NetworkController.
// Every row carries a real user id + slug so Message / Refer Client /
// Connect / View Profile all wire through to real endpoints (no more
// silent-fail 404s from empty ids/slugs).
const allProviders = computed(() => props.searchProviders ?? [])



// Business Partner directory — server-serialized from NetworkController.
// Real user IDs + slugs so Message / Connect / View Profile all work.
const businessPartners = computed(() => props.bpDirectory ?? [])

// ── BP Sidebar — group open/close state ───────────────────────────────────
const sbpGroups = reactive({
  category: true,
  type:     false,
  rate:     false,
  exp:      false,
  rating:   false,
  avail:    false,
  eng:      false,
  location: false,
  quality:  false,
  jobs:     false,
  lang:     false,
})

// ── BP Sidebar — filter values ────────────────────────────────────────────
const bpTypeFilter  = ref('')
const bpRateMin     = ref(0)
const bpRateMax     = ref(9999)
const bpExpLevel    = ref('')
const bpMinRating   = ref(0)
const bpAvail       = ref('')
const bpEngTypes    = ref([])
const bpLocation    = ref('')
const bpMinJobs     = ref(0)
const bpLang        = ref('')
const bpBadges      = reactive({ verified: false, topRated: false, fast: false, hipaa: false, newMember: false })

// BP category options (replaces the old <select>)
const bpCategories = [
  { val: 'it',            label: 'IT / Software' },
  { val: 'billing',       label: 'Medical Billing' },
  { val: 'accounting',    label: 'Accounting / CPA' },
  { val: 'legal',         label: 'Legal / Healthcare Law' },
  { val: 'marketing',     label: 'Marketing & Growth' },
  { val: 'hr',            label: 'HR / Staffing' },
  { val: 'credentialing', label: 'Credentialing' },
  { val: 'consulting',    label: 'Practice Consulting' },
  { val: 'design',        label: 'Design & Branding' },
  { val: 'admin',         label: 'Admin / VA' },
]

const bpAllLangs = ['English','Spanish','Mandarin','French','Arabic','Hindi','Portuguese','Russian','Korean','Japanese']
const bpLangSearch = ref('')
const filteredBpLangs = computed(() => {
  const q = bpLangSearch.value.toLowerCase().trim()
  if (!q) return bpAllLangs
  return bpAllLangs.filter(l => l.toLowerCase().includes(q))
})
const bpExpOptions = [
  { val: '',       label: 'Any Level' },
  { val: 'entry',  label: 'Entry', sub: '0–2 years experience' },
  { val: 'mid',    label: 'Mid-Level', sub: '3–7 years experience' },
  { val: 'senior', label: 'Senior', sub: '8–15 years experience' },
  { val: 'expert', label: 'Expert', sub: '15+ years or nationally recognized' },
]
const bpRatingOptions = [
  { val: 0,   label: 'Any Rating' },
  { val: 3.0, label: '3.0+' },
  { val: 4.0, label: '4.0+' },
  { val: 4.5, label: '4.5+' },
  { val: 4.8, label: '4.8+' },
]
const bpEngOptions = [
  { val: 'one_time',  label: 'One-Time Project' },
  { val: 'retainer',  label: 'Monthly Retainer' },
  { val: 'hourly',    label: 'Hourly' },
  { val: 'part_time', label: 'Part-Time Ongoing' },
  { val: 'full_time', label: 'Full-Time Contract' },
]

// ── Pagination watchers — placed here so every ref they touch is declared ──
watch([clinicalSearch, clinicalServiceOnly, searchSort], () => { spPage.value = 1 })

// Re-observe sentinels when switching into the relevant search tabs
// (sentinel may not have been in the DOM or visible when onMounted ran)
watch([() => scope.value, () => clinicalTab.value], async () => {
  if (scope.value === 'clinical' && clinicalTab.value === 'search') {
    await nextTick()
    if (spSentinel.value && spObserver) {
      spObserver.disconnect()
      spObserver.observe(spSentinel.value)
    } else if (spSentinel.value) {
      spObserver = makeObserver(hasMoreSearchResults, () => { spPage.value++ })
      spObserver.observe(spSentinel.value)
    }
  }
})
watch([() => scope.value, () => businessTab.value], async () => {
  if (scope.value === 'business' && businessTab.value === 'search') {
    await nextTick()
    if (bpSentinel.value && bpObserver) {
      bpObserver.disconnect()
      bpObserver.observe(bpSentinel.value)
    } else if (bpSentinel.value) {
      bpObserver = makeObserver(hasMorePartners, () => { bpPage.value++ })
      bpObserver.observe(bpSentinel.value)
    }
  }
})
watch(rtSearch, () => { rtPage.value = 1 })
watch(bizSearch, () => { myNetworkPage.value = 1; myPartnersPage.value = 1 })
watch([bpSearch, bpCategory, bpTypeFilter, bpRateMin, bpRateMax, bpExpLevel,
       bpMinRating, bpAvail, bpEngTypes, bpLocation, bpMinJobs, bpLang,
       bpClinicalOnly, bpSort], () => { bpPage.value = 1 }, { deep: true })

function sbpClearAll() {
  bpSearch.value      = ''
  bpCategory.value    = ''
  bpClinicalOnly.value = false
  bpTypeFilter.value  = ''
  bpRateMin.value     = 0
  bpRateMax.value     = 9999
  bpExpLevel.value    = ''
  bpMinRating.value   = 0
  bpAvail.value       = ''
  bpEngTypes.value    = []
  bpLocation.value    = ''
  bpMinJobs.value     = 0
  bpLang.value        = ''
  Object.keys(bpBadges).forEach(k => { bpBadges[k] = false })
  Object.keys(sbpGroups).forEach(k => { sbpGroups[k] = k === 'category' })
  toast.info('All filters cleared')
}

const avgBpRatingDisplay = computed(() => {
  const vals = props.bpConnections
    .map(c => Number(c.peer_rating || 0))
    .filter(v => v > 0)
  if (!vals.length) return '—'
  return (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1)
})

// Track IDs added to shadows this session — hide from RT list immediately
const addedToShadowIds = ref(new Set(
  (props.shadowConnections || []).map(s => s.shadow_user_id).filter(Boolean)
))

// Referral List candidates — sourced from backend (role=practitioner only, no CS/SS/BP).
// Mutable ref so we can splice cards out immediately when added to shadows.
const rtCandidates = ref([...(props.referralCandidates || [])])

// Sync when Inertia reloads referralCandidates (after shadow add)
watch(() => props.referralCandidates, (newList) => {
  // Only add new entries; don't re-add ones we've already hidden this session
  rtCandidates.value = (newList || []).filter(c => !addedToShadowIds.value.has(c.id))
}, { deep: true })

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

// ── Card display helpers (parity with PHP net_provider_card / net_bp_card) ─
function visibleTags(str) {
  return tagList(str).slice(0, 3)
}
function overflowTagCount(str) {
  const t = tagList(str)
  return t.length > 3 ? t.length - 3 : 0
}
function ratingDisplay(nc) {
  const v = Number(nc.peer_rating ?? nc.rating ?? 0)
  return v > 0 ? v.toFixed(1) : '—'
}
function ratingTooltip(nc, label = 'peer reviews') {
  const v = Number(nc.peer_rating ?? nc.rating ?? 0)
  const reviews = Number(nc.peer_reviews ?? nc.reviews ?? 0)
  if (v <= 0) return 'No ratings yet'
  return reviews > 0 ? `${v.toFixed(1)} from ${reviews} ${label}` : `${v.toFixed(1)} ${label}`
}
function connectionStatsLine(nc) {
  const refs = Number(nc.referral_count ?? nc.refs ?? 0)
  const acc  = Number(nc.acceptance_rate ?? nc.acc  ?? 0)
  const resp = Number(nc.response_time_hours ?? nc.resp ?? 0)
  return `${refs} refs · ${acc}% acc · ${resp.toFixed(1)}h resp`
}
function shadowStatsLine(s) {
  const match = Number(s.match_score ?? 0)
  const refs  = Number(s.referral_count ?? 0)
  const acc   = Number(s.acceptance_rate ?? 0)
  const resp  = Number(s.response_time_hours ?? 0)
  const prefix = match > 0 ? `${match}% match · ` : ''
  return `${prefix}${refs} refs · ${acc}% acc · ${resp.toFixed(1)}h resp`
}
function bpCategoryLabel(nc) {
  const cats = (nc.partner_categories || '').split(',').map(s => s.trim()).filter(Boolean)
  if (cats.length) return cats[0]
  return nc.partner_role || 'Services'
}
function bpCategoryTooltip(nc) {
  const cats = (nc.partner_categories || '').split(',').map(s => s.trim()).filter(Boolean)
  return cats.length > 1 ? `${cats.length} categories · ${cats.join(', ')}` : bpCategoryLabel(nc)
}

// ── My Shadows: filters, aggregates, referral shortcut ────────────────────
const myShadowSearch = ref('')
const filteredMyShadows = computed(() => {
  const q = myShadowSearch.value.trim().toLowerCase()
  if (!q) return props.shadowConnections
  return props.shadowConnections.filter((s) => {
    const hay = [s.shadow_name, s.shadow_role, s.shadow_location, s.shadow_specialty]
      .filter(Boolean).join(' ').toLowerCase()
    return hay.includes(q)
  })
})
const pagedMyShadows     = computed(() => filteredMyShadows.value.slice(0, myShadowPage.value * PAGE_SIZE))
const hasMoreMyShadows   = computed(() => filteredMyShadows.value.length > pagedMyShadows.value.length)
const shadowTotalReferrals = computed(() =>
  props.shadowConnections.reduce((sum, s) => sum + Number(s.referral_count || 0), 0)
)
const shadowAvgMatchDisplay = computed(() => {
  const vals = props.shadowConnections.map(s => Number(s.match_score || 0)).filter(v => v > 0)
  if (!vals.length) return '—'
  return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length) + '%'
})
const shadowAvgRespDisplay = computed(() => {
  const vals = props.shadowConnections.map(s => Number(s.response_time_hours || 0)).filter(v => v > 0)
  if (!vals.length) return '—'
  return (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1) + 'h'
})

function openReferralForShadow(s) {
  referralPreselectRecipient.value = {
    id:           s.shadow_user_id ?? null,
    display_name: s.shadow_name ?? '',
    slug:         s.shadow_slug ?? '',
    initials:     s.initials ?? '',
    avatar_url:   s.avatar_url ?? null,
    specialty:    s.shadow_specialty ?? '',
    location:     s.shadow_location ?? '',
    is_connected: false,
  }
  openModal('referralModal')
}

// ── CS Steward directory ────────────────────────────────────────────────────
// ── CS Steward directory ────────────────────────────────────────────────────
const csSearch       = ref('')
const csSpecialty    = ref('')
const csState        = ref('')
const csStateSearch  = ref('')
const csAvailOnly    = ref(false)
const csRateMax      = ref(0)
const csRateCustom   = ref(0)
const csSort         = ref('best')
const designateTarget = ref(null)
const showDesignateModal = ref(false)

const csGroups = reactive({ specialty: true, state: false, rate: false, sort: false })

const csSortOptions = [
  { val: 'best',      label: 'Best Match',        sub: 'Default ordering' },
  { val: 'rate_asc',  label: 'Rate: Low to High', sub: 'Lowest per-incident rate first' },
  { val: 'rate_desc', label: 'Rate: High to Low', sub: 'Highest per-incident rate first' },
  { val: 'newest',    label: 'Newest',             sub: 'Recently joined Aegis' },
]

const filteredCsStates = computed(() => {
  const q = csStateSearch.value.toLowerCase().trim()
  const states = props.csFilters?.states ?? []
  if (!q) return states
  return states.filter(s => s.toLowerCase().includes(q))
})

function csClearAll() {
  csSearch.value      = ''
  csSpecialty.value   = ''
  csState.value       = ''
  csStateSearch.value = ''
  csAvailOnly.value   = false
  csRateMax.value     = 0
  csRateCustom.value  = 0
  csSort.value        = 'best'
  Object.keys(csGroups).forEach(k => { csGroups[k] = k === 'specialty' })
  toast.info('All CS filters cleared')
}

const filteredCS = computed(() => {
  let list = [...(props.csStewards ?? [])]
  const q = csSearch.value.toLowerCase().trim()
  if (q) list = list.filter(cs =>
    (cs.display_name ?? '').toLowerCase().includes(q) ||
    (cs.bio          ?? '').toLowerCase().includes(q) ||
    (cs.specialties  ?? []).some(s => s.toLowerCase().includes(q))
  )
  if (csSpecialty.value)
    list = list.filter(cs => (cs.specialties ?? []).some(s => s.toLowerCase().includes(csSpecialty.value.toLowerCase())))
  if (csState.value)
    list = list.filter(cs => (cs.license_state ?? '').toLowerCase() === csState.value.toLowerCase())
  if (csAvailOnly.value)
    list = list.filter(cs => cs.cs_availability)
  if (csRateMax.value > 0)
    list = list.filter(cs => !cs.rate_max_cents || cs.rate_max_cents / 100 <= csRateMax.value)
  if (csSort.value === 'rate_asc')
    list = [...list].sort((a, b) => (a.rate_min_cents ?? 0) - (b.rate_min_cents ?? 0))
  else if (csSort.value === 'rate_desc')
    list = [...list].sort((a, b) => (b.rate_min_cents ?? 0) - (a.rate_min_cents ?? 0))
  return list
})

function formatCsRate(cs) {
  if (!cs.rate_min_cents && !cs.rate_max_cents) return 'Rate TBD'
  const min = cs.rate_min_cents ? '$' + Math.round(cs.rate_min_cents / 100) : ''
  const max = cs.rate_max_cents ? '$' + Math.round(cs.rate_max_cents / 100) : ''
  if (min && max && min !== max) return `${min}–${max}/incident`
  return (min || max) + '/incident'
}

function openDesignate(cs) {
  designateTarget.value = cs
  showDesignateModal.value = true
}

function onCsDesignated() {
  showDesignateModal.value = false
  // Reload csStewards so card status updates in-place, then visit stewards page
  router.reload({ only: ['csStewards'], preserveScroll: true, onSuccess: () => {
    router.visit(route('provider.stewards.index'))
  }})
}


const activeConfigPanel = ref('cfg-team')

const configNav = [
  { id: 'cfg-team',          label: 'Interdisciplinary Team',  icon: 'id-card' },
  { id: 'cfg-specialties',   label: 'Specialties',             icon: 'star' },
  { id: 'cfg-approaches',    label: 'Treatment Approaches',    icon: 'leaves' },
  { id: 'cfg-insurance',     label: 'Insurance Accepted',      icon: 'credit-card' },
  { id: 'cfg-credentials',   label: 'Credentials & Licenses',  icon: 'graduation-cap' },
  { id: 'cfg-services',      label: 'Services & Format',       icon: 'umbrella-2' },
  { id: 'cfg-location',      label: 'Location & Availability', icon: 'map-pin' },
  { id: 'cfg-demographics',  label: 'Demographics',            icon: 'user' },
  { id: 'cfg-languages',     label: 'Languages',               icon: 'send-2' },
  { id: 'cfg-identity',      label: 'Identity & Affiliations', icon: 'circle-user' },
  { id: 'cfg-rates',         label: 'Rates & Availability',    icon: 'dollar' },
  { id: 'cfg-notifications', label: 'Notifications',           icon: 'bell-2' },
  { id: 'cfg-privacy',       label: 'Privacy & Matching',      icon: 'lock-closed' },
]

// First two panels get fully-populated option lists — matches PHP fidelity for
// the "above the fold" panels while remaining 11 render as functional
// scaffolds pending Phase 4 wiring.
// ─── Panel 1: Interdisciplinary Team — full 27 provider types (PHP parity) ─
const teamOptions = [
  { label: 'Psychotherapist',                       icon: 'leaves' },
  { label: 'Psychologist',                          icon: 'user' },
  { label: 'Psychiatrist',                          icon: 'flame' },
  { label: 'Pain Management Specialist',            icon: 'heart-2' },
  { label: 'Movement/Dance Specialist',             icon: 'trending-up' },
  { label: 'Life / Health Coach',                   icon: 'target-2' },
  { label: 'Behavioral Therapist',                  icon: 'clipboard' },
  { label: 'Massage Therapist',                     icon: 'moon' },
  { label: 'Acupuncturist',                         icon: 'umbrella-2' },
  { label: 'Naturopathic Doctor (ND)',              icon: 'leaves' },
  { label: 'Functional Medicine Practitioner',      icon: 'flask' },
  { label: 'Registered Dietitian (RD/RDN)',         icon: 'heart-2' },
  { label: 'Integrative Nutrition Practitioner',    icon: 'leaves' },
  { label: 'Holistic Nutrition Practitioner',       icon: 'leaves' },
  { label: 'Detox/Functional Nutrition Specialist', icon: 'flask' },
  { label: 'Certified Diabetes Educator (CDE)',     icon: 'clipboard' },
  { label: 'Hypnotherapist',                        icon: 'moon' },
  { label: 'Reiki/Energy Healing Practitioner',     icon: 'sparkle-cluster' },
  { label: 'Homeopath',                             icon: 'leaves' },
  { label: 'Herbalist',                             icon: 'leaves' },
  { label: 'Ayurveda Practitioner',                 icon: 'leaves' },
  { label: 'Certified Nurse-Midwife (CNM)',         icon: 'heart-2' },
  { label: 'Doula',                                 icon: 'heart-2' },
  { label: 'Sleep Specialist',                      icon: 'moon' },
  { label: 'Genetic Counselor',                     icon: 'flask' },
  { label: 'Personal Trainer',                      icon: 'trending-up' },
  { label: 'Somatic Practitioner',                  icon: 'heart-2' },
]

// ─── Panel 2: Specialties — full 11 subcats × ~90 items (PHP parity) ───────
const cfgSpecialtyGroups = [
  { subcat: 'Therapy Types',            items: ['Individual Therapy','Family Therapy','Couple Therapy','Children Therapy','Children & Adolescent Therapy','Adult','Older Adult'] },
  { subcat: 'Mental Health & Wellness', items: ['Stress Management & Burnout','Grief & Loss','Wellness Coaching','Sex Therapy','Healthy Aging','Animal Assisted Therapy','Lifestyle Coaching','Dance Therapy','Fitness and Health Coaching','Eco-Psychology','Art Therapy / Play Therapy','Spirituality & Religion','Sleep Disorders','Pain Management'] },
  { subcat: 'Population & Identity',    items: ['LGBTQIA+',"Women's Health","Men's Health",'BIPOC Communities','Veterans & Military Families','Immigrant & Refugee Populations','Neurodivergent Individuals','Disability / Chronic Illness','Cultural Stress'] },
  { subcat: 'Relationship & Family',    items: ['Infidelity & Trust Issues','Couples','Divorce & Separation','Intimate Partner Violence','Family Conflict','Parenting Support','Co-Parenting','Blended Family Dynamics','Premarital Counseling'] },
  { subcat: 'Mental Health Disorders',  items: ['Anxiety Disorders','Depression / Depressive Disorders','PTSD & Trauma','Mood Disorders','Bipolar Disorders','OCD','Schizophrenia & Psychotic Disorders','ADHD','Autism Spectrum Disorder (ASD)','Eating Disorders','Body Image Issues','Dissociative Disorders','Personality Disorders (BPD, etc.)','Treatment-Resistant Depression'] },
  { subcat: 'Addiction & Substance Use', items: ['Substance Use Disorders','Alcohol Addiction','Drug Addiction','Behavioral Addictions','Relapse Prevention','Recovery Coaching'] },
  { subcat: 'Life Transitions & Personal Growth', items: ['Career Transitions & Work Stress','Career Counseling','Self-Esteem & Identity','Life Purpose & Meaning','Midlife Crisis','Retirement Adjustment','Empty Nest Syndrome'] },
  { subcat: 'Medical & Physical Health', items: ['Chronic Illness','Chronic Disease Management','Autoimmune Disorders','Thyroid Health','Hormonal Imbalances','Menopause & Perimenopause','Fertility & Reproductive Health','Pregnancy & Postpartum','PCOS','Diabetes & Blood Sugar','Cardiovascular Health','Cancer'] },
  { subcat: 'Specialty Psychiatry',     items: ['General Psychiatry','Medication Management','Geriatric Psychiatry','Child & Adolescent Psychiatry','Reproductive Psychiatry','Forensic Psychiatry','Psychopharmacology'] },
  { subcat: 'Nutrition & Dietetics',    items: ['Weight Management','Metabolic Health','Cardiovascular Nutrition','Gut Health & Microbiome','Food Sensitivities & Allergies','Eating Disorders & Disordered Eating','Sports Nutrition','Prenatal & Postnatal Nutrition','Plant-Based Nutrition','Functional Nutrition','Pediatric Nutrition'] },
  { subcat: 'Functional Medicine',      items: ['Root-Cause Medicine','Hormone Optimization','Chronic Fatigue & Fibromyalgia','Inflammation & Immune Dysregulation','Detoxification & Environmental Medicine','Longevity & Preventive Medicine','Mind-Body Medicine','Integrative Mental Health'] },
]

// ─── Panel 3: Treatment Approaches — 4 subcats × 73 items (PHP parity) ────
const cfgApproachGroups = [
  { subcat: 'Clinical Therapy Frameworks', items: ['Cognitive Behavioral Therapy (CBT)','Dialectical Behavioral Therapy (DBT)','Acceptance and Commitment Therapy (ACT)','EMDR','Internal Family Systems (IFS)','Psychodynamic Therapy','Psychoanalytic','Attachment-Based Therapy','Somatic Therapy / Somatic Experiencing','Trauma-Focused Therapy','Emotionally Focused Therapy (EFT)','Gottman Method Couples Therapy','Narrative Therapy','Collaborative Language Therapy','Structural Family Therapy','Gestalt Therapy','Jungian Therapy','Adlerian Therapy','Existential Therapy','Humanistic / Person-Centered Therapy','Solution-Focused Brief Therapy (SFBT)','Motivational Interviewing (MI)','Mindfulness-Based Stress Reduction (MBSR)','Mindfulness-Based Cognitive Therapy (MBCT)','Compassion-Focused Therapy (CFT)','Polyvagal-Informed Therapy','Sensorimotor Psychotherapy','Trauma-Focused CBT (TF-CBT)','Prolonged Exposure Therapy (PE)','Interpersonal Therapy (IPT)','Imago Relationship Therapy','Brainspotting','Hypnotherapy/Hypnosis','Play Therapy','Art Therapy','Music Therapy','Dance/Movement Therapy','Equine-Assisted Therapy','Wilderness Therapy','Family Systems Therapy','Eclectic / Integrative'] },
  { subcat: 'Nutrition & Dietetics Approaches', items: ['Medical Nutrition Therapy (MNT)','Personalized Nutrition Planning','Behavior Change Counseling','Intuitive Eating','Anti-Diet Approach','Health at Every Size (HAES)','Anti-Inflammatory Nutrition','Therapeutic Diets (low-FODMAP, DASH, Mediterranean)','Elimination Diets','Meal Planning & Dietary Structuring','Weight-Neutral Nutrition','Functional Nutrition Therapy'] },
  { subcat: 'Functional Medicine Approaches',  items: ['Root-Cause Analysis','Systems Biology Approach','Personalized / Precision Medicine','Lifestyle Medicine','Hormone Optimization','Gut Restoration Protocols','Detoxification Protocols','Nutraceutical & Supplement Therapy','Mind-Body Medicine','Environmental Medicine'] },
  { subcat: 'Psychiatry Approaches',           items: ['Psychopharmacology','Medication Management','Combined Therapy & Medication','Diagnostic Evaluation & Monitoring','Evidence-Based Prescribing','Treatment-Resistant Protocols','Long-Acting Injectable Therapy','ECT / TMS / Ketamine Therapy','Collaborative Care Models','Psychiatric Assessment & Care Planning'] },
]

// ─── Panel 4: Insurance — 5 subcats × 25 plans (PHP parity) ────────────────
const cfgInsuranceGroups = [
  { subcat: 'Commercial Health Plans',              items: ['Aetna','Cigna / Evernorth','UnitedHealthcare / Optum','Humana','Kaiser Permanente','Oscar Health'] },
  { subcat: 'Blue Cross Blue Shield Network',       items: ['Anthem BCBS','BCBS (State/Regional)','BCBS Federal Employee Program','Premera Blue Cross','Highmark BCBS','Regence BlueCross BlueShield','Wellmark BCBS','Blue Shield of California'] },
  { subcat: 'Government Programs',                  items: ['Medicare','Medicaid (Various State Plans)','TRICARE'] },
  { subcat: 'Behavioral Health Networks / Carve-Outs', items: ['Carelon Behavioral Health (Beacon)','Magellan Healthcare','Beacon Health Options'] },
  { subcat: 'Other / Third-Party Administrators',   items: ['UMR (United Medical Resources)','ComPsych','Quest Behavioral Health'] },
]

// ─── Panel 5: Credentials — 9 subcats × 40 credentials (PHP parity) ────────
const cfgCredentialGroups = [
  { subcat: 'Medical & Psychiatric',            items: ['MD (Doctor of Medicine)','DO (Doctor of Osteopathic Medicine)','Psychiatric Nurse Practitioner (PMHNP)','Physician Assistant (PA)'] },
  { subcat: 'Psychology',                       items: ['PhD (Psychology)','PsyD (Doctor of Psychology)','Licensed Psychologist','Clinical Psychologist','Neuropsychologist'] },
  { subcat: 'Clinical Social Work & Counseling', items: ['LCSW','LMSW','LMFT','LPC','LPCC','LMHC','LCPC','NCC (National Certified Counselor)'] },
  { subcat: 'Addiction & Substance Abuse',      items: ['CADC','LCADC','CASAC','CAC (Certified Addiction Counselor)'] },
  { subcat: 'Nutrition & Dietetics',            items: ['RD (Registered Dietitian)','RDN (Registered Dietitian Nutritionist)','LD (Licensed Dietitian)','CDN','CN (Certified Nutritionist)','CDE (Certified Diabetes Educator)','CDCES'] },
  { subcat: 'Functional & Integrative Medicine', items: ['IFMCP','ND (Naturopathic Doctor)','DC (Doctor of Chiropractic)','LAc (Licensed Acupuncturist)','DAOM'] },
  { subcat: 'Coaching & Alternative',           items: ['NBC-HWC','CHC (Certified Health Coach)','ACC (ICF)','PCC (ICF)','MCC (ICF)','CLC (Certified Life Coach)'] },
  { subcat: 'Specialized Certifications',       items: ['EMDR Certified Therapist','DBT Certified Clinician','CBT Certified Therapist','Certified Sex Therapist (AASECT)','Certified Play Therapist (RPT)','Art Therapist (ATR, ATR-BC)','Somatic Experiencing Practitioner (SEP)'] },
  { subcat: 'Nursing',                          items: ['RN (Registered Nurse)','NP (Nurse Practitioner)','CNM (Certified Nurse-Midwife)','FNP (Family Nurse Practitioner)'] },
]

// ─── Panel 6: Services & Format — 3 subcats × 25 items (PHP parity) ───────
const cfgServicesGroups = [
  { subcat: 'Services Offered',           items: ['Individual Therapy','Couples Therapy','Family Therapy','Group Therapy','Medication Management','Psychiatric Evaluation','Psychological Testing / Assessment','Neuropsychological Assessment','Nutritional Counseling','Wellness Coaching','Life Coaching','Career Counseling','Crisis Intervention','Case Management','Workshops & Seminars','Clinical Supervision / Training'] },
  { subcat: 'Session Format',             items: ['In-Person Only','Telehealth Only','Both In-Person & Telehealth','Hybrid'] },
  { subcat: 'Clinical Supervisor Status', items: ['Yes — I Provide Clinical Supervision','No — I Do Not Provide Supervision','Approved Supervisor (Licensed)','Accepting Supervisees','Not Accepting Supervisees Currently'] },
]

// ─── Panel 7: Location — full 50-state grid + primary state select ─────────
const primaryStates = [
  { region: 'Northeast', items: [['CT','Connecticut'],['ME','Maine'],['MA','Massachusetts'],['NH','New Hampshire'],['NJ','New Jersey'],['NY','New York'],['PA','Pennsylvania'],['RI','Rhode Island'],['VT','Vermont']] },
  { region: 'Southeast', items: [['AL','Alabama'],['AR','Arkansas'],['DE','Delaware'],['FL','Florida'],['GA','Georgia'],['KY','Kentucky'],['LA','Louisiana'],['MD','Maryland'],['MS','Mississippi'],['NC','North Carolina'],['SC','South Carolina'],['TN','Tennessee'],['VA','Virginia'],['WV','West Virginia']] },
  { region: 'Midwest',   items: [['IL','Illinois'],['IN','Indiana'],['IA','Iowa'],['KS','Kansas'],['MI','Michigan'],['MN','Minnesota'],['MO','Missouri'],['NE','Nebraska'],['ND','North Dakota'],['OH','Ohio'],['SD','South Dakota'],['WI','Wisconsin']] },
  { region: 'Southwest', items: [['AZ','Arizona'],['NM','New Mexico'],['OK','Oklahoma'],['TX','Texas']] },
  { region: 'West',      items: [['AK','Alaska'],['CA','California'],['CO','Colorado'],['HI','Hawaii'],['ID','Idaho'],['MT','Montana'],['NV','Nevada'],['OR','Oregon'],['UT','Utah'],['WA','Washington'],['WY','Wyoming']] },
  { region: 'Territories', items: [['DC','Washington D.C.'],['PR','Puerto Rico'],['GU','Guam']] },
]
const yearsInPracticeOptions = ['0–2 years (Early Career)','3–5 years','6–10 years','11–15 years','16–20 years','21–25 years','26–30 years','30+ years']
const usStates = ['NY','NJ','CT','PA','MA','VT','NH','RI','ME','DE','MD','VA','WV','NC','SC','GA','FL','AL','MS','TN','KY','OH','IN','IL','MI','WI','MN','IA','MO','AR','LA','TX','OK','KS','NE','SD','ND','MT','WY','CO','NM','AZ','UT','NV','ID','WA','OR','CA','AK','HI','DC']

// ─── Panel 8: Demographics — 6 subcats × 50+ items (PHP parity) ────────────
const demographicsGroups = [
  { subcat: 'Pronouns',                             items: ['She/Her/Hers','He/Him/His','They/Them/Theirs','Ze/Zir/Zirs','Any Pronouns','Ask Me'] },
  { subcat: 'Race / Ethnicity',                     items: ['American Indian or Alaska Native','Asian','Black or African American','Hispanic or Latino/a/x','Middle Eastern or North African','Native Hawaiian or Pacific Islander','White or Caucasian','Multiracial or Biracial','South Asian','East Asian','Southeast Asian','Caribbean','African (non-American)','Prefer not to say'] },
  { subcat: 'Parenting Status',                     items: ['Parent','Not a Parent','Parent of Young Children (0–5)','Parent of School-Age Children (6–12)','Parent of Teenagers (13–17)','Parent of Adult Children (18+)','Single Parent','Co-Parent','Adoptive Parent','Expecting Parent','Prefer not to say'] },
  { subcat: 'Disability / Neurodivergent Identity', items: ['Person with Disability','Neurodivergent','ADHD','Autism / Autistic','Dyslexia','Chronic Illness','D/deaf or Hard of Hearing','Blind or Low Vision','Mobility Disability','Mental Health Disability','Disability Ally','Prefer not to say'] },
  { subcat: 'Veteran Status',                       items: ['Military Veteran','Active Duty Military','Reservist / National Guard','Military Spouse','Military Family Member','Veteran-Affirming (Non-Veteran)','Not Applicable'] },
]
const sexAssignedOptions = ['Prefer not to say','Male','Female','Intersex']

// ─── Panel 9: Languages — full 29 languages (PHP parity) ───────────────────
const languageOptions = [
  'English','Spanish','Mandarin Chinese','Cantonese','French','German','Italian','Portuguese',
  'Russian','Arabic','Hindi','Urdu','Korean','Japanese','Vietnamese','Tagalog (Filipino)',
  'Polish','Farsi (Persian)','Hebrew','Greek','Turkish','Bengali','Punjabi','Tamil','Telugu',
  'Thai','Dutch','Swedish','American Sign Language (ASL)',
]

// ─── Panel 10: Identity & Affiliations — 4 subcats × 45 items (PHP parity) ─
const cfgIdentityGroups = [
  { subcat: 'LGBTQ+ Identity',                                         items: ['LGBTQ+ Identifying Provider','LGBTQ+ Affirming (Ally)','Gay','Lesbian','Bisexual','Transgender','Non-Binary','Queer','Asexual','Pansexual','Two-Spirit','Gender Non-Conforming','Genderqueer'] },
  { subcat: 'Religious / Spiritual Orientation — Major World Religions', items: ['Christianity (General)','Catholic','Protestant','Eastern Orthodox','Baptist','Evangelical','Judaism (General)','Orthodox Judaism','Reform Judaism','Islam (General)','Sunni Islam','Shia Islam','Hinduism','Buddhism','Sikhism','Jainism'] },
  { subcat: 'Eastern & Indigenous Traditions',                          items: ['Taoism','Zen Buddhism','Tibetan Buddhism','Indigenous Spirituality','Native American Spirituality','African Traditional Religions'] },
  { subcat: 'Alternative & Contemporary',                               items: ['Spiritual but Not Religious','Agnostic','Atheist','Secular Humanist','Unitarian Universalist','Mindfulness-Based','Yogic Philosophy','Interfaith','Non-Denominational','Prefer not to say'] },
]

// ─── Panel 11: Rates & Availability — sliding scale ctags + network prefs ─
const slidingScaleOptions   = ['Yes — Sliding Scale Available','Limited Sliding Scale Spots','No Sliding Scale']
const sessionLengthOptions  = ['30 minutes','45 minutes','50 minutes','60 minutes','75 minutes','90 minutes','120 minutes']
const maxPartnersOptions    = ['25 partners','50 partners','100 partners','Unlimited']
const geographicRadiusOptions = ['5 miles','15 miles','25 miles','50 miles','State-wide','National']
const referralUrgencyOptions  = ['Routine (standard)','Soon (48–72 hrs)','Urgent (24 hrs)']
const aiMatchFrequencyOptions = ['Daily','Weekly','Monthly','Disabled']

// ─── Panel 12: Notifications — exact PHP labels/descriptions ───────────────
const notifSettings = [
  { key: 'connection_requests', label: 'New connection requests',       desc: 'Get notified when someone requests to join your network' },
  { key: 'referral_activity',   label: 'Referral activity alerts',      desc: 'Notifications for new referrals sent/received' },
  { key: 'shadow_suggestions',  label: 'AI Shadow suggestions',         desc: 'Weekly digest of new shadow match recommendations' },
  { key: 'member_news',         label: 'Network member news',           desc: 'Updates when network members achieve milestones or post updates' },
  { key: 'read_receipts',       label: 'Message read receipts',         desc: 'Know when your messages have been read' },
  { key: 'weekly_digest',       label: 'Weekly network digest email',   desc: 'Summary of your network activity each week' },
  { key: 'feature_updates',     label: 'New Aegis features & updates',  desc: 'Be the first to know about new platform features' },
]

// ─── Panel 13: Privacy & AI Matching — exact PHP labels/descriptions ───────
const privacyToggles = [
  { key: 'searchable',           label: 'Show profile in provider search',                     desc: 'Allow other Aegis providers to find and view your profile' },
  { key: 'share_stats',          label: 'Share referral statistics publicly',                  desc: 'Display your response time and acceptance rate on your public profile' },
  { key: 'ai_matching',          label: 'Allow AI shadow matching',                            desc: 'Let Aegis AI suggest high-compatibility shadow connections' },
  { key: 'manual_approval',      label: 'Require manual approval for connections',             desc: 'Review and approve all connection requests before they join your network' },
  { key: 'hide_business',        label: 'Hide from business network search',                   desc: 'Business contacts cannot find you without a direct invite' },
  { key: 'ai_data_use',          label: 'Allow use of my data to improve AI matching',         desc: "Anonymized referral and matching data helps improve Aegis's AI recommendations" },
  { key: 'show_demographics',    label: 'Show demographics on public profile',                 desc: 'Display race, language, and identity details on your public-facing profile' },
]

// ── Reactive selection + form state ─────────────────────────────────────────
const cfgSearch = reactive({
  team:        '',
  specialties: '',
  approaches:  '',
  insurance:   '',
  credentials: '',
  languages:   '',
})
// ── Config state — initialized from DB via props.networkConfig ───────────────
const nc = props.networkConfig || {}

const cfgSelected = reactive({
  team:         nc.team         || [],
  specialties:  nc.specialties  || [],
  approaches:   nc.approaches   || [],
  insurance:    nc.insurance    || [],
  credentials:  nc.credentials  || [],
  services:     nc.services     || [],
  states:       nc.states       || [],
  demographics: nc.demographics || [],
  languages:    nc.languages    || [],
  identity:     nc.identity     || [],
  rates:        nc.rates        || [],
})
const cfgFields = reactive({
  license_number:     nc.license_number     ?? '',
  primary_state:      nc.primary_state      ?? '',
  years_in_practice:  nc.years_in_practice  ?? '',
  session_length:     nc.session_length      ?? '',
  rate_per_session:   nc.rate_per_session   ?? 0,
  sliding_scale_min:  nc.sliding_scale_min  ?? 0,
  sliding_scale_max:  nc.sliding_scale_max  ?? 0,
  max_partners:       nc.max_partners       ?? '',
  geographic_radius:  nc.geographic_radius  ?? '',
  referral_urgency:   nc.referral_urgency   ?? '',
  ai_match_frequency: nc.ai_match_frequency ?? '',
  sex_assigned:       nc.sex_assigned       ?? '',
})
const cfgNotifications = reactive({
  connection_requests: nc.notifications?.connection_requests ?? true,
  referral_activity:   nc.notifications?.referral_activity   ?? true,
  shadow_suggestions:  nc.notifications?.shadow_suggestions  ?? true,
  member_news:         nc.notifications?.member_news         ?? false,
  read_receipts:       nc.notifications?.read_receipts       ?? true,
  weekly_digest:       nc.notifications?.weekly_digest       ?? true,
  feature_updates:     nc.notifications?.feature_updates     ?? false,
})
const cfgPrivacy = reactive({
  searchable:        nc.privacy?.searchable        ?? true,
  share_stats:       nc.privacy?.share_stats       ?? true,
  ai_matching:       nc.privacy?.ai_matching       ?? true,
  manual_approval:   nc.privacy?.manual_approval   ?? false,
  hide_business:     nc.privacy?.hide_business     ?? false,
  ai_data_use:       nc.privacy?.ai_data_use       ?? true,
  show_demographics: nc.privacy?.show_demographics ?? true,
})

// ── Computed filters + counts ───────────────────────────────────────────────
const filteredTeamOptions = computed(() => {
  const q = cfgSearch.team.trim().toLowerCase()
  if (!q) return teamOptions
  return teamOptions.filter(o => o.label.toLowerCase().includes(q))
})

function filterGroups(groups, q) {
  const query = q.trim().toLowerCase()
  if (!query) return groups
  return groups
    .map(g => ({ subcat: g.subcat, items: g.items.filter(t => t.toLowerCase().includes(query)) }))
    .filter(g => g.items.length > 0)
}

const filteredSpecialtyGroups   = computed(() => filterGroups(cfgSpecialtyGroups,   cfgSearch.specialties))
const filteredApproachGroups    = computed(() => filterGroups(cfgApproachGroups,    cfgSearch.approaches))
const filteredInsuranceGroups   = computed(() => filterGroups(cfgInsuranceGroups,   cfgSearch.insurance))
const filteredCredentialGroups  = computed(() => filterGroups(cfgCredentialGroups,  cfgSearch.credentials))
const filteredLanguageOptions   = computed(() => {
  const q = cfgSearch.languages.trim().toLowerCase()
  if (!q) return languageOptions
  return languageOptions.filter(o => o.toLowerCase().includes(q))
})

// Rough dirty-count for the save bar hint
const configDirtyCount = computed(() => {
  return (
    cfgSelected.specialties.length +
    cfgSelected.approaches.length +
    cfgSelected.insurance.length +
    cfgSelected.credentials.length +
    cfgSelected.services.length +
    cfgSelected.states.length +
    cfgSelected.demographics.length +
    cfgSelected.identity.length +
    cfgSelected.rates.length
  )
})

function selectedCount(key) {
  return cfgSelected[key]?.length ?? 0
}
function toggleCfg(key, value) {
  const arr = cfgSelected[key]
  const idx = arr.indexOf(value)
  if (idx === -1) arr.push(value)
  else arr.splice(idx, 1)
}
function scrollToConfigPanel(id) {
  activeConfigPanel.value = id
  // Wait for the panel to expand (Vue tick) before scrolling to it,
  // otherwise scrollIntoView measures the collapsed height.
  nextTick(() => {
    const el = document.getElementById(id)
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}
function toggleConfigPanel(id) {
  activeConfigPanel.value = activeConfigPanel.value === id ? '' : id
}

// Single useForm for all config — declared at setup time
const cfgForm = useForm({})

const cfgSaving = ref(false)
function saveConfig() {
  cfgSaving.value = true
  cfgForm
    .transform(() => ({
      // Arrays
      team:         [...cfgSelected.team],
      specialties:  [...cfgSelected.specialties],
      approaches:   [...cfgSelected.approaches],
      insurance:    [...cfgSelected.insurance],
      credentials:  [...cfgSelected.credentials],
      services:     [...cfgSelected.services],
      states:       [...cfgSelected.states],
      demographics: [...cfgSelected.demographics],
      languages:    [...cfgSelected.languages],
      identity:     [...cfgSelected.identity],
      rates:        [...cfgSelected.rates],
      // Scalar fields
      ...cfgFields,
      // Nested objects
      notifications: { ...cfgNotifications },
      privacy:       { ...cfgPrivacy },
    }))
    .put(route('provider.network.config.save'), {
      preserveScroll: true,
      onSuccess: () => { cfgSaving.value = false; toast.success('Configuration saved.') },
      onError:   () => { cfgSaving.value = false; toast.error('Could not save configuration.') },
    })
}

const cfgResetting = ref(false)
function resetConfig() {
  confirmAction({
    title:   'Reset Configuration',
    message: 'This will clear all selections, fields, and preferences. Cannot be undone.',
    confirmLabel: 'Reset',
    destructive:  true,
  }, () => {
    cfgResetting.value = true
    // Clear local state immediately
    Object.keys(cfgSelected).forEach(k => { cfgSelected[k] = [] })
    Object.keys(cfgSearch).forEach(k => { cfgSearch[k] = '' })
    Object.assign(cfgFields, {
      license_number:'', primary_state:'', years_in_practice:'', session_length:'',
      rate_per_session:0, sliding_scale_min:0, sliding_scale_max:0,
      max_partners:'', geographic_radius:'', referral_urgency:'', ai_match_frequency:'', sex_assigned:'',
    })
    Object.keys(cfgNotifications).forEach(k => { cfgNotifications[k] = false })
    Object.keys(cfgPrivacy).forEach(k => { cfgPrivacy[k] = false })
    // Persist reset to DB
    cfgForm.post(route('provider.network.config.reset'), {
      preserveScroll: true,
      onFinish: () => { cfgResetting.value = false },
      onSuccess: () => toast.success('Configuration reset.'),
      onError:   () => toast.error('Reset failed — please try again.'),
    })
  })
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

/* sbp row label helpers — typography only, _shared.css owns the row layout */
.sbp-radio-label   { font-size: 12px; font-weight: 600; color: var(--text-2); display: inline-flex; align-items: center; gap: 4px; }
.sbp-radio-sub     { font-size: 10px; color: var(--text-3); margin-top: 1px; }
.sbp-check-label   { font-size: 12px; font-weight: 600; color: var(--text-2); }
.sbp-toggle-label  { font-size: 12px; font-weight: 600; color: var(--text-2); display: inline-flex; align-items: center; gap: 5px; flex: 1; }

/* ── Filter sidebars: page-sidebar-group visual pattern ─────────────────────
   Override the default filter-sidebar chrome so both #filterSidebar and
   #sbpFilterSidebar match the .page-sidebar / .page-sidebar-group design.
   Filter-option internals (ftags, filter-group-body, inner-search) are left
   untouched — only the outer chrome, header, and group row headers change.  */

/* 1. Outer container — match .page-sidebar chrome exactly */
:deep(#filterSidebar),
:deep(#sbpFilterSidebar),
:deep(#csSidebar) {
  top: 81px;
  padding: 0;        /* remove the 14px 10px 0 default; groups own their spacing */
  border-radius: var(--radius-lg);
}

/* 2. Sidebar header row — becomes the page-sidebar "label" style header */
:deep(#filterSidebar .filter-sidebar-header),
:deep(#sbpFilterSidebar .filter-sidebar-header),
:deep(#csSidebar .filter-sidebar-header) {
  padding: 12px 14px 10px;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
  margin: 0;
  position: sticky;
  top: 0;
  z-index: 2;
}

:deep(#filterSidebar .filter-sidebar-title),
:deep(#sbpFilterSidebar .filter-sidebar-title),
:deep(#csSidebar .filter-sidebar-title) {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-3);
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* 3. Each filter-group becomes a page-sidebar-group — bottom divider, no margin */
:deep(#filterSidebar .filter-group),
:deep(#sbpFilterSidebar .filter-group),
:deep(#csSidebar .filter-group) {
  margin: 0;
  padding: 0;
  border-bottom: 1px solid var(--border);
}
:deep(#filterSidebar .filter-group:last-of-type),
:deep(#sbpFilterSidebar .filter-group:last-of-type),
:deep(#csSidebar .filter-group:last-of-type) {
  border-bottom: none;
}

/* Clinical-service toggle group — same divider treatment */
:deep(#filterSidebar .nw-sbp-clinical-toggle),
:deep(#sbpFilterSidebar .nw-sbp-clinical-toggle),
:deep(#csSidebar .nw-sbp-clinical-toggle) {
  margin: 0;
  border-radius: 0;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
  padding: 10px 14px;
}

/* 4. Group header rows — match page-sidebar-item style */
:deep(#filterSidebar .filter-group-header),
:deep(#sbpFilterSidebar .filter-group-header),
:deep(#csSidebar .filter-group-header) {
  padding: 9px 14px;
  border-radius: 0;
  border-left: none;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  letter-spacing: 0.01em;
  position: relative;
  transition: background var(--transition), color var(--transition);
}
:deep(#filterSidebar .filter-group-header:hover),
:deep(#sbpFilterSidebar .filter-group-header:hover),
:deep(#csSidebar .filter-group-header:hover) {
  background: var(--surface-2);
  color: var(--text);
}
:deep(#filterSidebar .filter-group.open > .filter-group-header),
:deep(#sbpFilterSidebar .filter-group.open > .filter-group-header),
:deep(#csSidebar .filter-group.open > .filter-group-header) {
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  font-weight: 700;
}
:deep(#filterSidebar .filter-group.open > .filter-group-header)::before,
:deep(#sbpFilterSidebar .filter-group.open > .filter-group-header)::before,
:deep(#csSidebar .filter-group.open > .filter-group-header)::before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  bottom: 8px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: var(--gold-dark);
}

/* Icon color in open state */
:deep(#filterSidebar .filter-group.open .filter-group-label svg),
:deep(#filterSidebar .filter-group.open .filter-group-label .aegis-icon),
:deep(#sbpFilterSidebar .filter-group.open .filter-group-label svg),
:deep(#sbpFilterSidebar .filter-group.open .filter-group-label .aegis-icon),
:deep(#csSidebar .filter-group.open .filter-group-label .aegis-icon) {
  color: var(--gold-dark);
}

/* 5. Filter body — left-indent to align with page-sidebar-item text */
:deep(#filterSidebar .filter-group-body),
:deep(#sbpFilterSidebar .filter-group-body),
:deep(#csSidebar .filter-group-body) {
  padding: 8px 14px 14px 14px;
  border-left: 3px solid var(--badge-bg-gold);
  background: var(--surface);
}

/* 6. Active filter pills row — sits just below the sticky header */
:deep(#filterSidebar .active-filters-row),
:deep(#sbpFilterSidebar .active-filters-row),
:deep(#csSidebar .active-filters-row) {
  padding: 8px 14px 6px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
}

/* filter-sidebar-apply — sticky footer for both filter sidebars */
.filter-sidebar-apply {
  position: sticky;
  bottom: 0;
  margin: 0;
  background: var(--surface);
  border-top: 1px solid var(--border);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.filter-sidebar-apply .btn {
  width: 100%;
  justify-content: center;
  text-align: center;
}

/* sbp-info-tip — keep tooltip visible when cursor moves into the child icon.
   pointer-events:none on the svg child prevents the tooltip element from
   losing hover state as the cursor crosses the icon boundary. */
:deep(.sbp-info-tip) svg,
:deep(.sbp-info-tip) .aegis-icon { pointer-events: none; }

/* Sub-tabs — match legacy #networkTabs .net-sub-tabs rule */
.tabs-twotier .tabs-segmented.net-sub-tabs {
  display: inline-flex;
  align-self: flex-start;
  margin-bottom: 0;
  margin-top: 6px;
}

/* ═══════════ Design-parity add-ons — legacy classes not in _shared.css ═══════════ */

/* pn-card — Provider Network variant of spc-card (card-v2 chrome).
   In legacy PHP, .pn-card was defined in network.php's page <style>. */
.pn-card {
  padding-top: 40px; /* space for spc-top-pills + spc-rating */
}

/* Section heading used above Referral List / My Shadows tabs */
.nw-section-heading {
  font-family: var(--font-serif);
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 16px;
}

/* Insight box layout — icon block + body */
.nw-insight-box {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 16px;
}
.nw-insight-icon {
  flex-shrink: 0;
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  background: var(--gold-dark);
  color: var(--surface);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.nw-insight-body { flex: 1; }
.nw-insight-text {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.5;
}

/* Gold-toned stat chip icon — replaces inline background/color on stat-chip-icon */
.nw-chip-gold {
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
}

/* Configuration tab — placeholder body copy + save bar */
.nw-cfg-placeholder {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
  padding: 8px 4px 4px;
  margin: 0;
}
.nw-cfg-save-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: flex-end;
  padding: 18px 0 6px;
  margin-top: 8px;
  border-top: 1px solid var(--border);
  position: sticky;
  bottom: 0;
  background: var(--surface-2);
}
.cfg-save-hint {
  flex: 1;
  font-size: 12px;
  color: var(--text-3);
  font-weight: 600;
}

/* Tag grid — shared layout for specialty/approach/language/demographic
   ctag pickers. .ctag itself is defined in _shared.css. */
.cfg-tag-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 14px;
}

/* Config field row column overrides — PHP uses inline styles; we lift them
   to scoped classes so the template stays clean. */
.nw-cfg-2col.cfg-field-row { grid-template-columns: 1fr 1fr; }
.nw-cfg-4col.cfg-field-row { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }

/* Clickable panel header — accordion toggle */
:deep(.cfg-panel-header) { cursor: pointer; }

/* Currency input group (Rates panel) */
.nw-cfg-currency {
  display: flex;
  align-items: center;
  gap: 8px;
}
.nw-cfg-currency-symbol {
  font-size: 18px;
  font-weight: 700;
  color: var(--primary);
}

/* Help text under a cfg-subcat (Location panel) */
.nw-cfg-help {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0 0 8px;
}

/* My Partners — bizGridView layout */
.nw-biz-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

/* Search Business Partners — filter-sidebar internals not covered by legacy */
.nw-sbp-clinical-toggle {
  padding: 10px 12px;
  margin-bottom: 8px;
  background: var(--surface-2);
  border-radius: var(--radius);
}
.nw-sbp-clinical-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  cursor: pointer;
  margin: 0;
}
.nw-sbp-clinical-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
}
.nw-filter-placeholder {
  font-size: 12px;
  color: var(--text-3);
  line-height: 1.5;
  margin: 8px 4px 0;
  padding: 6px 8px;
  background: var(--surface-2);
  border-radius: var(--radius-sm);
}
/* Search Business Partners — results-panel top bar */
.nw-sort-label {
  font-size: 12px;
  color: var(--text-4);
  font-weight: 600;
}
.nw-sort-select {
  font-size: 12px;
  padding: 4px 8px;
  min-width: 160px;
}

/* Ensure filter-sidebar toggle matches global button.toggle design */
button.toggle {
  width: 42px; height: 24px;
  border-radius: var(--radius-full);
  background: var(--border-dark);
  border: none;
  cursor: pointer;
  position: relative;
  transition: background var(--transition);
  flex-shrink: 0;
  outline: none;
  padding: 0;
  display: inline-block;
  vertical-align: middle;
}
button.toggle::after {
  content: '';
  position: absolute;
  top: 50%; left: 3px;
  width: 18px; height: 18px;
  background: var(--surface);
  border-radius: var(--radius-full);
  transform: translateY(-50%);
  transition: transform var(--transition);
  box-shadow: 0 1px 4px rgba(44,34,24,0.22), 0 0 1px rgba(44,34,24,0.10);
}
button.toggle.on::after { transform: translate(18px, -50%); }
button.toggle.on        { background: var(--gold-dark); }
button.toggle:focus-visible { box-shadow: var(--focus-ring); }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Infinite scroll sentinel — invisible 1px trigger zone */
.nw-scroll-sentinel { height: 1px; margin-top: 20px; }

/* Config accordion slide-fade animation */
.cfg-slide-enter-active { transition: opacity 0.22s ease, transform 0.22s ease; }
.cfg-slide-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.cfg-slide-enter-from   { opacity: 0; transform: translateY(-8px); }
.cfg-slide-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
