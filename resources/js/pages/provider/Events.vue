<!--
  pages/provider/Events.vue
  100% parity with events.php (1236 lines).
  Design pass only — props are stubs; Prompt 2 wires real backend data.
-->
<template>
  <AppLayout>
    <Head title="Events &amp; Trainings — Aegis" />

    <!-- ══ HERO (quiet) ══════════════════════════════════════════════ -->
    <AegisHeroBanner
      eyebrow="PROVIDER PORTAL"
      title="Events &amp; Trainings"
      subtitle="CEU courses, conferences, workshops, and networking for health and well-being professionals."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity', { event_type: 'event' })" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button class="btn-hero-solid is-on-light" @click="modals.submitEvent = true">
          <AegisIcon name="plus" :size="14" /> Submit Event
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ══ STAT CHIPS (sibling of hero) ══════════════════════════════ -->
    <div class="stat-chips-row">
      <AegisStatChip icon="calendar" :value="props.countTotal ?? 0" label="Upcoming Events" />
      <AegisStatChip icon="bookmark" :value="props.registeredCount ?? 0" label="Registered" />
      <AegisStatChip icon="award" :value="props.ceuEarned ?? '0'" label="CEUs Earned" />
    </div>

    <!-- ══ CEU PROGRESS TRACKER ═══════════════════════════════════════ -->
    <div class="card evt-ceu-card">
      <div class="card-header">
        <div>
          <div class="card-title">My 2026 CEU Progress</div>
          <div class="card-subtitle">Track licensure-renewal credits across categories.</div>
        </div>
        <button class="btn btn-outline btn-sm" @click="modals.ceu = true">
          <AegisIcon name="bar-chart" :size="14" /> View Transcript
        </button>
      </div>
      <div class="card-body">
        <div class="ceu-rows">
          <div
            v-for="row in ceuRows"
            :key="row.category"
            class="ceu-row"
          >
            <div class="ceu-row-name">
              <AegisIcon :name="row.icon || 'book'" :size="14" /> {{ row.category }}
            </div>
            <div class="ceu-row-bar">
              <div
                class="ceu-row-bar-fill"
                :class="`is-${row.status}`"
                :style="`width:${row.pct}%`"
              ></div>
            </div>
            <div class="ceu-row-meta" :class="{ 'is-danger': row.status === 'danger', 'is-done': row.status === 'done' }">
              {{ fmtCeu(row.earned_hrs) }} / {{ fmtCeu(row.required_hrs) }} hrs<template v-if="row.meta_label"> · {{ row.meta_label }}</template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ MAIN LAYOUT: feed + sidebar ═══════════════════════════════ -->
    <div class="evt-layout">

      <!-- ── FEED COLUMN ─────────────────────────────────────────── -->
      <div>

        <!-- TOOLBAR -->
        <div class="evt-toolbar">
          <div class="evt-search-wrap">
            <span class="evt-search-icon"><AegisIcon name="search" :size="16" /></span>
            <input
              v-model="searchQuery"
              type="text"
              class="evt-search-input"
              placeholder="Search events, topics, speakers..."
            >
          </div>
          <select v-model="categoryFilter" class="form-select evt-toolbar-select" aria-label="Filter by category">
            <option value="all">All categories</option>
            <option value="webinar">Webinars</option>
            <option value="conference">Conferences</option>
            <option value="training">CEU Training</option>
            <option value="networking">Networking</option>
            <option value="workshop">Workshops</option>
          </select>
          <select v-model="sortMode" class="form-select evt-toolbar-select" aria-label="Sort events">
            <option value="date">Soonest first</option>
            <option value="popular">Most popular</option>
            <option value="price-asc">Price: low to high</option>
            <option value="ceu">Most CEUs</option>
          </select>
        </div>

        <!-- RESULT BAR -->
        <div class="evt-result-bar">
          <strong>{{ visibleEvents.length }}</strong> event{{ visibleEvents.length !== 1 ? 's' : '' }}
        </div>

        <!-- EVENT LIST -->
        <div class="evt-list">
          <div
            v-for="(ev, i) in visibleEvents"
            :key="ev.id"
            class="evt-card"
            :data-category="evtCategory(ev)"
          >
            <!-- Date block -->
            <div class="evt-date-block">
              <div class="evt-date-month">{{ fmtMonth(ev.starts_at) }}</div>
              <div class="evt-date-day">{{ fmtDay(ev.starts_at) }}</div>
              <div class="evt-date-year">{{ fmtYear(ev.starts_at) }}</div>
            </div>

            <!-- Content -->
            <div class="evt-content">
              <div class="evt-content-top">
                <span class="evt-category" :class="evtCategory(ev)">{{ evtCategoryLabel(ev) }}</span>
                <span v-if="i === 0" class="evt-featured-badge">
                  <AegisIcon name="star" :size="11" /> Featured
                </span>
                <span
                  v-if="daysAway(ev.starts_at) >= 0 && daysAway(ev.starts_at) <= 7"
                  class="evt-urgent-badge"
                  :class="{ 'is-today': daysAway(ev.starts_at) === 0 }"
                >
                  <AegisIcon name="clock" :size="11" />
                  {{ daysAway(ev.starts_at) === 0 ? 'Today' : daysAway(ev.starts_at) === 1 ? 'Tomorrow' : daysAway(ev.starts_at) + ' days away' }}
                </span>
              </div>
              <div class="evt-title">{{ ev.title }}</div>
              <div v-if="ev.description" class="evt-desc">{{ ev.description }}</div>
              <div class="evt-meta">
                <div class="evt-meta-item">
                  <AegisIcon name="clock" :size="14" />
                  {{ fmtTimeRange(ev.starts_at, ev.ends_at) }}
                </div>
                <div v-if="ev.location" class="evt-meta-item">
                  <AegisIcon :name="ev.location === 'Online' ? 'monitor' : 'map-pin'" :size="14" />
                  {{ ev.location }}
                </div>
                <div v-if="ev.ceu_credits > 0" class="evt-meta-item is-ceu">
                  <AegisIcon name="award" :size="14" />
                  {{ fmtCeu(ev.ceu_credits) }} CEU Credit{{ ev.ceu_credits === 1 ? '' : 's' }}
                </div>
                <div v-if="ev.is_free" class="evt-meta-item is-free">
                  <AegisIcon name="check" :size="14" /> Free
                </div>
                <div v-else class="evt-meta-item is-paid">
                  <AegisIcon name="dollar" :size="14" /> Paid
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="evt-actions">
              <!-- External event: direct link, no in-app RSVP -->
              <template v-if="ev.is_external">
                <a
                  :href="ev.external_url"
                  target="_blank"
                  rel="noopener"
                  class="evt-btn evt-btn-primary"
                >
                  <AegisIcon name="external-link" :size="13" /> View Event
                </a>
              </template>
              <!-- Internal event: in-app RSVP flow -->
              <template v-else>
                <button
                  class="evt-btn evt-btn-primary"
                  :class="{ 'is-registered': registeredIds.has(ev.id) }"
                  @click="handleRegister(ev)"
                >
                  <template v-if="registeredIds.has(ev.id)">
                    <AegisIcon name="check" :size="13" /> Registered
                  </template>
                  <template v-else>
                    {{ ev.is_free ? 'Register Free' : 'Register Now' }}
                  </template>
                </button>
              </template>
              <button class="evt-btn evt-btn-secondary" @click="openDetail(ev)">Details</button>
            </div>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <AegisEmptyState
          v-if="visibleEvents.length === 0"
          icon="calendar"
          title="No events found"
          subtitle="Try a different category or clear the filter."
        >
          <template #actions>
            <button class="btn btn-outline btn-sm" @click="clearFilter">Clear filter</button>
          </template>
        </AegisEmptyState>

        <!-- LOAD MORE -->
        <div class="evt-load-more">
          <button class="btn btn-outline btn-sm" @click="loadMore">
            <AegisIcon name="refresh" :size="14" />
            {{ loadMoreText }}
          </button>
        </div>

      </div><!-- end feed column -->

      <!-- ── SIDEBAR ─────────────────────────────────────────────── -->
      <aside class="evt-sidebar">

        <!-- MY REGISTERED EVENTS -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">My Registered Events</div>
              <div class="card-subtitle">{{ props.registeredCount ?? 0 }} upcoming</div>
            </div>
            <button class="btn-icon" data-tooltip="View CEU transcript" @click="modals.ceu = true">
              <AegisIcon name="bar-chart" :size="14" />
            </button>
          </div>
          <div class="card-body">
            <div v-if="myEvents.length === 0" class="evt-no-reg">No upcoming registrations yet.</div>
            <div v-else class="evt-my-list">
              <div
                v-for="mev in myEvents"
                :key="mev.id"
                class="evt-my-item"
                @click="openDetail(mev)"
              >
                <div class="evt-my-dot" :class="evtCategory(mev)"></div>
                <div style="min-width:0">
                  <div class="evt-my-title">{{ mev.title }}</div>
                  <div class="evt-my-date">
                    {{ fmtShortDate(mev.starts_at) }} · {{ fmtTime(mev.starts_at) }}<template v-if="mev.ceu_credits"> · {{ fmtCeu(mev.ceu_credits) }} CEUs</template>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- EVENT CALENDAR -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Event Calendar</div>
              <div class="card-subtitle">Days marked have events.</div>
            </div>
          </div>
          <div class="card-body">
            <div class="evt-cal-nav">
              <button class="evt-cal-nav-btn" data-tooltip="Previous month" @click="changeCalMonth(-1)">
                <AegisIcon name="chevron-left" :size="14" />
              </button>
              <span class="evt-cal-month-label">{{ calMonthLabel }}</span>
              <button class="evt-cal-nav-btn" data-tooltip="Next month" @click="changeCalMonth(1)">
                <AegisIcon name="chevron-right" :size="14" />
              </button>
            </div>
            <div class="evt-cal">
              <div v-for="h in ['Su','Mo','Tu','We','Th','Fr','Sa']" :key="h" class="evt-cal-header">{{ h }}</div>
              <div
                v-for="cell in calCells"
                :key="cell.key"
                class="evt-cal-day"
                :class="{
                  'today': cell.isToday,
                  'has-event': cell.hasEvent && !cell.isToday,
                  'other-month': cell.otherMonth
                }"
              >{{ cell.day }}</div>
            </div>
          </div>
        </div>

      </aside>
    </div><!-- end evt-layout -->

    <!-- ══ MODALS ═════════════════════════════════════════════════════ -->

    <!-- EVENT DETAIL MODAL -->
    <AegisModal v-model="modals.detail" title="Event Details" size="lg">
      <template v-if="detailEvent">

        <!-- Eyebrow + title -->
        <div class="evt-detail-heading">
          <div class="evt-detail-eyebrow-row">
            <span class="evt-category" :class="evtCategory(detailEvent)">{{ evtCategoryLabel(detailEvent) }}</span>
            <span v-if="detailEvent.is_external" class="evt-external-badge">
              <AegisIcon name="external-link" :size="11" /> External Event
            </span>
          </div>
          <div class="evt-detail-title" style="margin-top:10px">{{ detailEvent.title }}</div>
          <div v-if="detailEvent.organizer" class="evt-detail-organizer">
            <AegisIcon name="users" :size="13" /> {{ detailEvent.organizer }}
          </div>
        </div>

        <!-- Key meta chips -->
        <div class="evt-detail-meta">
          <div class="evt-detail-chip">
            <AegisIcon name="calendar" :size="13" />
            <strong>{{ fmtFullDate(detailEvent.starts_at) }}</strong>
          </div>
          <div v-if="detailEvent.ends_at" class="evt-detail-chip">
            <AegisIcon name="clock" :size="13" />
            <strong>Ends {{ fmtFullDate(detailEvent.ends_at) }}</strong>
          </div>
          <div v-if="detailEvent.location" class="evt-detail-chip">
            <AegisIcon :name="detailEvent.location?.toLowerCase().includes('online') || detailEvent.location?.toLowerCase().includes('virtual') || detailEvent.location?.toLowerCase().includes('zoom') ? 'monitor' : 'map-pin'" :size="13" />
            <strong>{{ detailEvent.location }}</strong>
          </div>
          <div v-if="detailEvent.ceu_credits > 0" class="evt-detail-chip is-ceu">
            <AegisIcon name="award" :size="13" />
            <strong>{{ fmtCeu(detailEvent.ceu_credits) }} CEU Credit{{ detailEvent.ceu_credits === 1 ? '' : 's' }}</strong>
          </div>
          <div class="evt-detail-chip" :class="detailEvent.is_free ? 'is-free' : 'is-paid'">
            <AegisIcon :name="detailEvent.is_free ? 'check-circle' : 'dollar'" :size="13" />
            <strong>{{ detailEvent.is_free ? 'Free' : '$' + ((detailEvent.price_cents ?? 0) / 100).toFixed(2) }}</strong>
          </div>
          <div v-if="detailEvent.attendee_count > 0" class="evt-detail-chip">
            <AegisIcon name="users" :size="13" />
            <strong>{{ detailEvent.attendee_count }} registered</strong>
          </div>
        </div>

        <!-- Description -->
        <p v-if="detailEvent.description" class="evt-detail-desc" style="white-space:pre-line">{{ detailEvent.description }}</p>

        <!-- External link (reference only — not for RSVP) -->
        <div v-if="detailEvent.external_url" class="evt-detail-external">
          <AegisIcon name="external-link" :size="13" />
          <a :href="detailEvent.external_url" target="_blank" rel="noopener">
            View on external site →
          </a>
          <span class="evt-detail-external-note">Registration handled on the organizer's platform.</span>
        </div>

        <!-- Registration status banner (internal events) -->
        <div v-if="!detailEvent.is_external && registeredIds.has(detailEvent.id)" class="evt-detail-registered-banner">
          <AegisIcon name="check-circle" :size="16" />
          You're registered for this event. A confirmation was sent to your email.
        </div>

      </template>
      <template #footer>
        <button class="btn btn-outline" @click="modals.detail = false">Close</button>
        <template v-if="detailEvent">
          <!-- External event: open external site -->
          <a
            v-if="detailEvent.is_external"
            :href="detailEvent.external_url"
            target="_blank"
            rel="noopener"
            class="btn btn-primary"
          >
            <AegisIcon name="external-link" :size="13" /> Register on Site
          </a>
          <!-- Internal: already registered → cancel -->
          <button
            v-else-if="registeredIds.has(detailEvent.id)"
            class="btn btn-outline"
            @click="modals.detail = false; openCancelModal(detailEvent)"
          >
            <AegisIcon name="x" :size="13" /> Cancel Registration
          </button>
          <!-- Internal: not registered → register -->
          <button
            v-else
            class="btn btn-primary"
            @click="handleRegister(detailEvent); modals.detail = false"
          >
            {{ detailEvent.is_free ? 'Register Free' : 'Register Now' }}
          </button>
        </template>
      </template>
    </AegisModal>

    <!-- REGISTER CONFIRM MODAL -->
    <AegisModal v-model="modals.registerConfirm" title="Confirm Registration" size="sm">
      <div class="evt-detail-heading" style="text-align:center">
        <div class="evt-detail-eyebrow">You're registering for</div>
        <div class="evt-detail-title">{{ pendingEvent?.title }}</div>
      </div>
      <p style="text-align:center;font-size:13px;color:var(--text-3);margin-bottom:18px">Confirmation will be emailed.</p>
      <div class="form-group">
        <label class="form-label">Preferred contact email</label>
        <input v-model="regForm.email" type="email" class="form-input" />
      </div>
      <div class="form-group">
        <label class="form-label">Dietary restrictions or accessibility needs</label>
        <input v-model="regForm.notes" type="text" class="form-input" placeholder="Optional" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.registerConfirm = false">Cancel</button>
        <button class="btn btn-primary" @click="confirmRegistration">Confirm Registration →</button>
      </template>
    </AegisModal>

    <!-- CANCEL REGISTRATION MODAL -->
    <AegisModal v-model="modals.cancelReg" title="Cancel Registration" size="sm">
      <div class="evt-detail-heading" style="text-align:center">
        <div class="evt-detail-eyebrow">Are you sure?</div>
        <div class="evt-detail-title">{{ pendingEvent?.title }}</div>
      </div>
      <div class="alert alert-warning" style="margin-top:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Refund policy</div>
          <div>Cancellations within 48 hours of the event may not be eligible for a refund. Please review the event's policy before confirming.</div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.cancelReg = false">Keep Registration</button>
        <button class="btn btn-danger" @click="confirmCancel">Cancel Registration</button>
      </template>
    </AegisModal>

    <!-- SUBMIT EVENT MODAL -->
    <AegisModal v-model="modals.submitEvent" title="Submit an Event" size="lg">
      <div class="alert alert-info" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">For community review</div>
          <div>Approved events are listed on this page and promoted to the Aegis community.</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Event Title <span class="required">*</span></label>
        <input
          v-model="submitForm.title"
          type="text"
          class="form-input"
          :class="{ 'is-error': fieldError('title') }"
          placeholder="e.g. CBT for Anxiety Disorders — Advanced Workshop"
          @blur="v$.title.$touch()"
        />
        <div v-if="fieldError('title')" class="form-error">{{ fieldError('title') }}</div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group" :class="{ 'is-error': fieldError('type') }">
          <label class="form-label">Event Type <span class="required">*</span></label>
          <select
            v-model="submitForm.type"
            class="form-select"
            @change="v$.type.$touch()"
          >
            <option value="">Select type…</option>
            <option value="webinar">Webinar</option>
            <option value="conference">Conference</option>
            <option value="training">CEU Training</option>
            <option value="networking">Networking</option>
            <option value="workshop">Workshop</option>
          </select>
          <div v-if="fieldError('type')" class="form-error">{{ fieldError('type') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Event Date <span class="required">*</span></label>
          <input
            ref="submitDateRef"
            v-model="submitForm.date"
            type="date"
            class="form-input"
            :class="{ 'is-error': fieldError('date') }"
            @change="onSubmitDateChange"
          />
          <div v-if="fieldError('date')" class="form-error">{{ fieldError('date') }}</div>
        </div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label">Price (USD, leave 0 for free)</label>
          <input v-model="submitPriceDollars" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
        </div>
        <div class="form-group">
          <label class="form-label">CEU Credits Offered</label>
          <input v-model="submitForm.ceu" type="number" min="0" step="0.5" class="form-input" placeholder="0" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Location / Format</label>
        <input v-model="submitForm.location" type="text" class="form-input" placeholder="Online (Zoom) or City, State" />
      </div>
      <div class="form-group">
        <label class="form-label">Description <span class="required">*</span></label>
        <textarea
          v-model="submitForm.description"
          class="form-textarea"
          :class="{ 'is-error': fieldError('description') }"
          rows="4"
          placeholder="Describe the event, target audience, learning objectives…"
          @blur="v$.description.$touch()"
        ></textarea>
        <div v-if="fieldError('description')" class="form-error">{{ fieldError('description') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">External Event URL</label>
        <input
          v-model="submitForm.external_url"
          type="url"
          class="form-input"
          :class="{ 'is-error': fieldError('external_url') }"
          placeholder="https://… (only if event is hosted outside Aegis)"
          @blur="v$.external_url.$touch()"
        />
        <div v-if="fieldError('external_url')" class="form-error">{{ fieldError('external_url') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Organizer / Sponsor</label>
        <input v-model="submitForm.organizer" type="text" class="form-input" placeholder="Organization name" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.submitEvent = false">Cancel</button>
        <button class="btn btn-primary" @click="submitEvent">Submit for Review →</button>
      </template>
    </AegisModal>

    <!-- CEU TRANSCRIPT MODAL -->
    <AegisModal v-model="modals.ceu" title="My CEU Transcript" size="lg">
      <!-- Tab switcher -->
      <div class="tabs-segmented" style="margin-bottom:18px" role="tablist">
        <button type="button" class="tab-pill" role="tab"
          :class="{ active: ceuTab === 'transcript' }" @click="ceuTab = 'transcript'">
          <AegisIcon name="file-text" :size="12" /> Transcript
        </button>
        <button type="button" class="tab-pill" role="tab"
          :class="{ active: ceuTab === 'log' }" @click="ceuTab = 'log'">
          <AegisIcon name="plus" :size="12" /> Log CEU
        </button>
      </div>

      <!-- TRANSCRIPT TAB -->
      <div v-show="ceuTab === 'transcript'">
        <div class="stat-chips-row" style="margin-bottom:18px">
          <AegisStatChip icon="award" :value="fmtCeu(props.ceuEarned)" label="Total CEUs Earned" />
          <AegisStatChip
            v-for="row in props.ceuRows.slice(0,2)" :key="row.category"
            icon="award"
            :value="`${fmtCeu(row.earned_hrs)} / ${fmtCeu(row.required_hrs)}`"
            :label="row.category.split('—')[0].trim()"
          />
        </div>
        <AegisEmptyState v-if="ceuTranscript.length === 0"
          icon="award" title="No CEU entries yet"
          subtitle="Log your first continuing education credit using the Log CEU tab.">
          <template #actions>
            <button class="btn btn-primary btn-sm" @click="ceuTab = 'log'">
              <AegisIcon name="plus" :size="13" /> Log CEU
            </button>
          </template>
        </AegisEmptyState>
        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Course</th>
                <th>Provider</th>
                <th>Date</th>
                <th style="text-align:center">Credits</th>
                <th style="text-align:center">Certificate</th>
                <th style="text-align:center">Remove</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in ceuTranscript" :key="row.id">
                <td><strong>{{ row.course }}</strong></td>
                <td>{{ row.type }}</td>
                <td>{{ row.date }}</td>
                <td style="text-align:center;font-weight:700">{{ row.credits }}</td>
                <td style="text-align:center">
                  <a v-if="row.certificate_url" :href="row.certificate_url"
                    target="_blank" rel="noopener" class="btn-icon" data-tooltip="Download certificate">
                    <AegisIcon name="download" :size="14" />
                  </a>
                  <span v-else class="btn-icon" style="opacity:0.3;cursor:default" data-tooltip="No certificate on file">
                    <AegisIcon name="download" :size="14" />
                  </span>
                </td>
                <td style="text-align:center">
                  <button class="btn-icon" data-tooltip="Delete entry" @click="deleteCeuEntry(row.id, row.course)">
                    <AegisIcon name="trash" :size="14" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- LOG CEU TAB -->
      <div v-show="ceuTab === 'log'">
        <div class="alert alert-info" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
          <div class="alert-content">
            <div class="alert-title">Logging your own credits</div>
            <div>Enter details from a CEU certificate you received from a training provider (APA, NASW, etc.). Aegis is the record-keeper — not the course provider.</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Course / Training Name <span class="required">*</span></label>
          <input v-model="ceuLogForm.title" type="text" class="form-input"
            :class="{ 'is-error': ceuFieldError('title') }"
            placeholder="e.g. Ethics in Telehealth Practice"
            @blur="vCeu$.title.$touch()" />
          <div v-if="ceuFieldError('title')" class="form-error">{{ ceuFieldError('title') }}</div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Provider / Organization</label>
            <input v-model="ceuLogForm.provider_name" type="text" class="form-input" placeholder="e.g. NASW, APA" />
          </div>
          <div class="form-group">
            <label class="form-label">Credit Hours <span class="required">*</span></label>
            <input v-model="ceuLogForm.credit_hours" type="number" min="0.5" step="0.5" class="form-input"
              :class="{ 'is-error': ceuFieldError('credit_hours') }"
              placeholder="e.g. 3" @blur="vCeu$.credit_hours.$touch()" />
            <div v-if="ceuFieldError('credit_hours')" class="form-error">{{ ceuFieldError('credit_hours') }}</div>
          </div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Date Completed <span class="required">*</span></label>
            <input v-model="ceuLogForm.completed_on" type="date" class="form-input"
              :class="{ 'is-error': ceuFieldError('completed_on') }"
              @change="vCeu$.completed_on.$touch()" />
            <div v-if="ceuFieldError('completed_on')" class="form-error">{{ ceuFieldError('completed_on') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Expiry Date <span style="font-size:11px;color:var(--text-3)">(optional)</span></label>
            <input v-model="ceuLogForm.expires_on" type="date" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Certificate <span style="font-size:11px;color:var(--text-3)">(PDF, JPG or PNG · max 10 MB)</span></label>
          <AegisDropzone accept=".pdf,.jpg,.jpeg,.png" hint="Drop certificate here or click to browse"
            @files="ceuLogForm.certificate = $event[0]" />
        </div>
        <div style="margin-top:16px">
          <button class="btn btn-primary" :disabled="ceuLogForm.processing" @click="submitCeuLog">
            <AegisIcon name="check" :size="13" />
            {{ ceuLogForm.processing ? 'Saving…' : 'Save CEU Entry' }}
          </button>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="modals.ceu = false">Close</button>
        <button v-if="ceuTab === 'transcript' && ceuTranscript.length > 0"
          class="btn btn-primary" @click="exportTranscript">
          <AegisIcon name="download" :size="14" /> Export CSV
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import { useVuelidate }                                        from '@vuelidate/core'
import { required, maxLength, minValue, numeric, url as vUrl,
         helpers, minLength }                                  from '@vuelidate/validators'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast }   from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const { confirmAction } = useConfirm()

// ── Props (stubs — real data wired in Prompt 2) ─────────────────────────
const props = defineProps({
  events:             { type: Array,  default: () => [] },
  countTotal:         { type: Number, default: 0 },
  registeredCount:    { type: Number, default: 0 },
  ceuEarned:          { type: [Number, String], default: 0 },
  ceuRows:            { type: Array,  default: () => [] },
  ceuTranscript:      { type: Array,  default: () => [] },
  myEvents:           { type: Array,  default: () => [] },
  registeredEventIds: { type: Array,  default: () => [] },
  eventDays:          { type: Object, default: () => ({}) },
})

// ── Modal state ──────────────────────────────────────────────────────────
const modals = reactive({
  detail:          false,
  registerConfirm: false,
  cancelReg:       false,
  submitEvent:     false,
  ceu:             false,
})

// ── Filter / search / sort ───────────────────────────────────────────────
const searchQuery    = ref('')
const categoryFilter = ref('all')
const sortMode       = ref('date')
const loadMoreText   = ref('Load more events')

const registeredIds = ref(new Set(props.registeredEventIds))

const filteredEvents = computed(() => {
  let list = props.events ?? []
  if (categoryFilter.value !== 'all') {
    list = list.filter(e => evtCategory(e) === categoryFilter.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(e =>
      (e.title ?? '').toLowerCase().includes(q) ||
      (e.description ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

const visibleEvents = computed(() => {
  const list = [...filteredEvents.value]
  if (sortMode.value === 'date') {
    list.sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at))
  } else if (sortMode.value === 'price-asc') {
    list.sort((a, b) => (a.is_free ? 0 : 50) - (b.is_free ? 0 : 50))
  } else if (sortMode.value === 'popular') {
    list.sort((a, b) => {
      const countA = a.rsvps_json ? Object.keys(a.rsvps_json).length : 0
      const countB = b.rsvps_json ? Object.keys(b.rsvps_json).length : 0
      return countB - countA
    })
  } else if (sortMode.value === 'ceu') {
    list.sort((a, b) => (b.ceu_credits ?? 0) - (a.ceu_credits ?? 0))
  }
  return list
})

function clearFilter() {
  categoryFilter.value = 'all'
  searchQuery.value = ''
}

function loadMore() {
  loadMoreText.value = 'All caught up'
  toast.info('No more events to load')
}

// ── Pending event for modals ─────────────────────────────────────────────
const pendingEvent = ref(null)
const detailEvent  = ref(null)

const regForm = reactive({ email: '', notes: '' })
const submitForm = reactive({
  title: '', type: '', date: '', price_cents: 0, ceu: 0,
  location: '', description: '', external_url: '', organizer: '',
})

// Dollar display for submit form price
const submitPriceDollars = ref('')
watch(submitPriceDollars, v => {
  const n = parseFloat(v)
  submitForm.price_cents = isNaN(n) ? 0 : Math.round(n * 100)
})

// ── Vuelidate — submit form (mirrors SubmitEventRequest rules) ────────────
const submitRules = computed(() => ({
  title:       {
    required: helpers.withMessage('Event title is required.', required),
    max:      helpers.withMessage('Title must be 191 characters or less.', maxLength(191)),
  },
  type:        { required: helpers.withMessage('Please select an event type.', required) },
  date:        { required: helpers.withMessage('Event date is required.', required) },
  description: {
    required: helpers.withMessage('Description is required.', required),
    max:      helpers.withMessage('Description must be 2000 characters or less.', maxLength(2000)),
  },
  external_url: {
    validUrl: helpers.withMessage('Enter a valid URL (https://…).', (v) => !v || /^https?:\/\/.+/.test(v)),
  },
  ceu:         { min: helpers.withMessage('CEU credits cannot be negative.', minValue(0)) },
  price_cents: { min: helpers.withMessage('Price cannot be negative.', minValue(0)) },
}))

const v$ = useVuelidate(submitRules, submitForm)

// Unified error helper — client error wins while editing
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  return null
}

// Date input ref for flatpickr sync
const submitDateRef = ref(null)
function onSubmitDateChange(e) {
  submitForm.date = e.target.value || ''
  v$.value.date.$touch()
}

// ── Registration ─────────────────────────────────────────────────────────
function handleRegister(ev) {
  if (registeredIds.value.has(ev.id)) {
    toast.info(`You're already registered for ${ev.title}`)
    return
  }
  pendingEvent.value = ev
  regForm.email = ''
  regForm.notes = ''
  modals.registerConfirm = true
}

function confirmRegistration() {
  if (!pendingEvent.value) return
  router.post(route('provider.news.rsvp', { event: pendingEvent.value.id }), {
    status: 'going',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      registeredIds.value.add(pendingEvent.value.id)
      modals.registerConfirm = false
      toast.success(`Registered for ${pendingEvent.value.title} — check your email.`)
    },
    onError: () => {
      modals.registerConfirm = false
      toast.error('Registration failed.')
    },
  })
}

// ── Cancel ───────────────────────────────────────────────────────────────
function openCancelModal(ev) {
  pendingEvent.value = ev
  modals.cancelReg = true
}

function confirmCancel() {
  if (!pendingEvent.value) return
  router.delete(route('provider.news.events.cancel', { event: pendingEvent.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      registeredIds.value.delete(pendingEvent.value.id)
      modals.cancelReg = false
      toast.info('Registration cancelled')
    },
    onError: () => {
      modals.cancelReg = false
      toast.error('Cancellation failed.')
    },
  })
}

// ── Event detail ─────────────────────────────────────────────────────────
function openDetail(ev) {
  detailEvent.value = ev
  modals.detail = true
}

// ── Submit event ─────────────────────────────────────────────────────────
async function submitEvent() {
  const valid = await v$.value.$validate()
  if (!valid) {
    toast.error('Please fix the highlighted fields.')
    return
  }
  router.post(route('provider.news.events.submit'), { ...submitForm }, {
    preserveScroll: true,
    onSuccess: () => {
      modals.submitEvent = false
      toast.success("Event submitted for review — we'll respond within 2 business days.")
      Object.keys(submitForm).forEach(k => (submitForm[k] = k === 'price_cents' || k === 'ceu' ? 0 : ''))
      submitPriceDollars.value = ''
      if (submitDateRef.value?._flatpickr) submitDateRef.value._flatpickr.clear()
      v$.value.$reset()
    },
    onError: (errors) => {
      modals.submitEvent = false
      const first = Object.values(errors)[0]
      toast.error(first || 'Submission failed — please check the form.')
    },
  })
}

// ── Export transcript ────────────────────────────────────────────────────
function exportTranscript() {
  // GET route returns a streamed CSV download — use direct navigation, not Inertia POST
  window.location.href = route('provider.news.events.export-transcript')
  toast.success('Transcript downloading — check your downloads folder.')
}

// ── Format helpers ───────────────────────────────────────────────────────
function fmtMonth(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('en-US', { month: 'short' }).toUpperCase()
}
function fmtDay(iso) {
  if (!iso) return '—'
  return new Date(iso).getDate()
}
function fmtYear(iso) {
  if (!iso) return '—'
  return new Date(iso).getFullYear()
}
function fmtTime(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('en-US', { hour: 'numeric', minute: '2-digit' })
}
function fmtTimeRange(start, end) {
  if (!start) return '—'
  const s = fmtTime(start)
  if (!end) return s
  return s + ' – ' + fmtTime(end)
}
function fmtShortDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric' })
}
function fmtFullDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}
function fmtCeu(n) {
  if (n == null) return '0'
  const v = parseFloat(n)
  return v % 1 === 0 ? String(Math.round(v)) : String(parseFloat(v.toFixed(1)))
}
function daysAway(iso) {
  if (!iso) return -1
  return Math.max(0, Math.floor((new Date(iso) - Date.now()) / 86400000))
}

const CAT_MAP = { webinar: 'webinar', conference: 'conference', training: 'training', networking: 'networking', workshop: 'workshop' }
const CAT_LABELS = { webinar: 'Webinar', conference: 'Conference', training: 'CEU Training', networking: 'Networking', workshop: 'Workshop' }
function evtCategory(ev) {
  const c = (ev.category ?? '').toLowerCase()
  if (CAT_MAP[c]) return c
  const t = (ev.title ?? '').toLowerCase()
  if (t.includes('webinar') || t.includes('office hours')) return 'webinar'
  if (t.includes('workshop')) return 'workshop'
  if (t.includes('summit') || t.includes('conference')) return 'conference'
  if (t.includes('networking') || t.includes('meetup')) return 'networking'
  return 'training'
}
function evtCategoryLabel(ev) {
  return CAT_LABELS[evtCategory(ev)] ?? 'Event'
}

// ── CEU transcript rows ──────────────────────────────────────────────────
const ceuTranscript = computed(() => props.ceuTranscript ?? [])

// ── CEU modal tab state ───────────────────────────────────────────────────
const ceuTab = ref('transcript')
watch(() => modals.ceu, open => { if (!open) { ceuTab.value = 'transcript'; vCeu$.value.$reset() } })

// ── CEU Log form ──────────────────────────────────────────────────────────
const ceuLogForm = useForm({
  title: '', provider_name: '', credit_hours: '', completed_on: '', expires_on: '', certificate: null,
})

const ceuLogRules = computed(() => ({
  title:        { required: helpers.withMessage('Course name is required.', required) },
  credit_hours: {
    required: helpers.withMessage('Credit hours are required.', required),
    min:      helpers.withMessage('Must be at least 0.5 hours.', minValue(0.5)),
  },
  completed_on: { required: helpers.withMessage('Completion date is required.', required) },
}))

const vCeu$ = useVuelidate(ceuLogRules, ceuLogForm)

function ceuFieldError(field) {
  if (vCeu$.value[field]?.$error) return vCeu$.value[field].$errors[0]?.$message
  return null
}

async function submitCeuLog() {
  const valid = await vCeu$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  ceuLogForm.post(route('provider.ceus.log'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('CEU entry logged.')
      ceuLogForm.reset()
      vCeu$.value.$reset()
      ceuTab.value = 'transcript'
    },
    onError: (errors) => toast.error(Object.values(errors)[0] || 'Failed to save CEU entry.'),
  })
}

function deleteCeuEntry(id, courseName) {
  confirmAction(
    `Remove "${courseName || 'this CEU entry'}" from your transcript? This cannot be undone.`,
    () => {
      router.delete(route('provider.ceus.destroy', { ceu: id }), {
        preserveScroll: true,
        onSuccess: () => toast.info('CEU entry removed.'),
        onError:   () => toast.error('Could not remove entry.'),
      })
    }
  )
}

// ── Mini calendar ────────────────────────────────────────────────────────
const today    = new Date()
const calYear  = ref(today.getFullYear())
const calMonth = ref(today.getMonth())
const MONTHS   = ['January','February','March','April','May','June','July','August','September','October','November','December']

const calMonthLabel = computed(() => `${MONTHS[calMonth.value]} ${calYear.value}`)

const calCells = computed(() => {
  const cells = []
  const firstDay   = new Date(calYear.value, calMonth.value, 1).getDay()
  const daysInMon  = new Date(calYear.value, calMonth.value + 1, 0).getDate()
  const daysInPrev = new Date(calYear.value, calMonth.value, 0).getDate()
  for (let i = firstDay - 1; i >= 0; i--) {
    cells.push({ key: `p${i}`, day: daysInPrev - i, otherMonth: true, isToday: false, hasEvent: false })
  }
  for (let d = 1; d <= daysInMon; d++) {
    const key = `${calYear.value}-${calMonth.value}-${d}`
    const isToday = today.getFullYear() === calYear.value && today.getMonth() === calMonth.value && today.getDate() === d
    cells.push({ key, day: d, otherMonth: false, isToday, hasEvent: !!(props.eventDays?.[key]) })
  }
  const total   = firstDay + daysInMon
  const remain  = total % 7 === 0 ? 0 : 7 - (total % 7)
  for (let i = 1; i <= remain; i++) {
    cells.push({ key: `n${i}`, day: i, otherMonth: true, isToday: false, hasEvent: false })
  }
  return cells
})

function changeCalMonth(dir) {
  let m = calMonth.value + dir
  let y = calYear.value
  if (m > 11) { m = 0; y++ }
  if (m < 0)  { m = 11; y-- }
  calMonth.value = m
  calYear.value  = y
}
</script>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────────── */
.evt-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 24px;
}

/* ── CEU card spacing ────────────────────────────────────────────── */
.evt-ceu-card { margin-bottom: 22px; }

/* ── CEU rows ────────────────────────────────────────────────────── */
.ceu-rows { display: flex; flex-direction: column; gap: 14px; }
.ceu-row  { display: grid; grid-template-columns: minmax(140px, 200px) 1fr auto; gap: 14px; align-items: center; }
.ceu-row-name {
  font-size: 12px; font-weight: 700; color: var(--text);
  display: flex; align-items: center; gap: 8px;
}
.ceu-row-bar { height: 6px; background: var(--surface-3); border-radius: var(--radius-full); overflow: hidden; }
.ceu-row-bar-fill {
  height: 100%; border-radius: var(--radius-full);
  background: var(--gold-dark); transition: width 0.4s ease;
}
.ceu-row-bar-fill.is-warn   { background: var(--orange-dark); }
.ceu-row-bar-fill.is-danger { background: var(--red-dark); }
.ceu-row-bar-fill.is-done   { background: var(--green-dark); }
.ceu-row-meta { font-size: 11px; font-weight: 700; color: var(--text-2); white-space: nowrap; min-width: 110px; text-align: right; }
.ceu-row-meta.is-danger { color: var(--red-dark); }
.ceu-row-meta.is-done   { color: var(--green-dark); }

/* ── Toolbar ─────────────────────────────────────────────────────── */
.evt-toolbar {
  display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;
}
.evt-search-wrap { flex: 1; min-width: 220px; position: relative; }
.evt-search-icon {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  color: var(--text-4); pointer-events: none; display: flex;
}
.evt-search-input {
  width: 100%; padding: 9px 13px 9px 36px;
  border: 1.5px solid var(--border); border-radius: var(--radius);
  font-family: var(--font-sans); font-size: 13px; color: var(--text);
  background: var(--surface); transition: border-color var(--transition);
}
.evt-search-input:focus { outline: none; border-color: var(--gold-dark); box-shadow: 0 0 0 3px var(--badge-bg-gold); }
.evt-toolbar-select { min-width: 168px; }

/* ── Result bar ──────────────────────────────────────────────────── */
.evt-result-bar { font-size: 12px; color: var(--text-3); margin-bottom: 14px; }
.evt-result-bar strong { color: var(--text); font-weight: 700; }

/* ── Event list ──────────────────────────────────────────────────── */
.evt-list { display: flex; flex-direction: column; gap: 12px; }
.evt-card {
  display: grid;
  grid-template-columns: 64px minmax(0, 1fr) 152px;
  gap: 22px;
  align-items: stretch;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 20px 22px;
  transition: box-shadow var(--transition), border-color var(--transition);
}
.evt-card:hover { box-shadow: var(--shadow); border-color: var(--border-dark); }

/* Date block */
.evt-date-block {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  text-align: center;
  border-right: 1px solid var(--border);
  padding-right: 22px;
  line-height: 1;
}
.evt-date-month { font-size: 11px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: var(--gold-dark); }
.evt-date-day   { font-family: var(--font-serif); font-size: 30px; font-weight: 700; color: var(--text); margin: 3px 0 4px; }
.evt-date-year  { font-size: 11px; font-weight: 600; color: var(--text-4); }

/* Content */
.evt-content      { min-width: 0; }
.evt-content-top  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 9px; }

/* Category pills */
.evt-category {
  display: inline-flex; align-items: center;
  font-size: 10px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;
  padding: 3px 9px; border-radius: var(--radius-full);
  background: var(--surface-3); color: var(--text-3);
}
.evt-category.webinar    { background: var(--blue-light);   color: var(--blue-dark); }
.evt-category.conference { background: var(--purple-light); color: var(--purple-dark); }
.evt-category.workshop   { background: var(--orange-light); color: var(--orange-dark); }
.evt-category.networking { background: var(--green-light);  color: var(--green-dark); }
.evt-category.training   { background: var(--teal-light);   color: var(--teal-dark); }

/* Badges */
.evt-featured-badge, .evt-urgent-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase;
  padding: 3px 8px; border-radius: var(--radius-full);
}
.evt-featured-badge { background: var(--badge-bg-gold); color: var(--gold-dark); }
.evt-urgent-badge   { background: var(--orange-light); color: var(--orange-dark); }
.evt-urgent-badge.is-today { background: var(--red-light); color: var(--red-dark); }

.evt-title {
  font-family: var(--font-serif); font-size: 16px; font-weight: 700;
  color: var(--text); line-height: 1.35; margin-bottom: 5px;
}
.evt-desc {
  font-size: 13px; color: var(--text-3); line-height: 1.55; margin-bottom: 12px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Meta */
.evt-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; }
.evt-meta-item {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 500; color: var(--text-3);
}
.evt-meta-item.is-ceu  { font-weight: 700; color: var(--gold-dark); }
.evt-meta-item.is-free { font-weight: 700; color: var(--green-dark); }
.evt-meta-item.is-paid { font-weight: 700; color: var(--text-2); }

/* Actions column */
.evt-actions { display: flex; flex-direction: column; justify-content: center; gap: 8px; }
.evt-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 9px 14px; border-radius: var(--radius);
  font-family: var(--font-sans); font-size: 13px; font-weight: 700; letter-spacing: 0.2px;
  border: 1.5px solid transparent; cursor: pointer; white-space: nowrap; line-height: 1;
  transition: background var(--transition), border-color var(--transition), color var(--transition);
}
.evt-btn-primary          { background: var(--gold-dark); color: #fff; }
.evt-btn-primary:hover    { box-shadow: var(--shadow-gold); }
.evt-btn-primary.is-registered { background: var(--green-dark); cursor: default; }
.evt-btn-secondary        { background: transparent; color: var(--text-2); border-color: var(--border-dark); }
.evt-btn-secondary:hover  { color: var(--gold-dark); background: var(--surface-2); border-color: var(--gold-dark); }

/* Load more */
.evt-load-more { display: flex; justify-content: center; margin: 22px 0 0; }

/* ── Sidebar ─────────────────────────────────────────────────────── */
.evt-sidebar { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 84px; align-self: start; }

.evt-no-reg { font-size: 12px; color: var(--text-3); padding: 6px 0; }
.evt-my-list { display: flex; flex-direction: column; }
.evt-my-item {
  display: flex; gap: 10px; align-items: flex-start;
  padding: 10px 0; border-bottom: 1px solid var(--border);
  cursor: pointer; transition: color var(--transition);
}
.evt-my-item:first-child { padding-top: 0; }
.evt-my-item:last-child  { border-bottom: none; padding-bottom: 0; }
.evt-my-item:hover .evt-my-title { color: var(--gold-dark); }
.evt-my-dot { width: 8px; height: 8px; border-radius: var(--radius-full); flex-shrink: 0; margin-top: 6px; }
.evt-my-dot.webinar    { background: var(--blue-dark); }
.evt-my-dot.conference { background: var(--purple-dark); }
.evt-my-dot.workshop   { background: var(--orange-dark); }
.evt-my-dot.networking { background: var(--green-dark); }
.evt-my-dot.training   { background: var(--teal-dark); }
.evt-my-title { font-size: 13px; font-weight: 700; color: var(--text); line-height: 1.4; transition: color var(--transition); }
.evt-my-date  { font-size: 11px; color: var(--text-3); margin-top: 2px; }

/* Mini calendar */
.evt-cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.evt-cal-nav-btn {
  background: none; border: 1px solid var(--border); border-radius: var(--radius-sm);
  width: 26px; height: 26px; cursor: pointer; color: var(--text-3);
  transition: border-color var(--transition), color var(--transition);
  display: flex; align-items: center; justify-content: center;
}
.evt-cal-nav-btn:hover { border-color: var(--gold-dark); color: var(--gold-dark); }
.evt-cal-month-label { font-size: 13px; font-weight: 700; color: var(--text); font-family: var(--font-serif); }
.evt-cal {
  display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px;
}
.evt-cal-header {
  text-align: center; font-size: 10px; font-weight: 700;
  color: var(--text-4); padding: 2px 0; letter-spacing: 0.4px;
}
.evt-cal-day {
  text-align: center; font-size: 11px; padding: 5px 2px;
  border-radius: var(--radius-sm); color: var(--text-3);
  font-variant-numeric: tabular-nums;
}
.evt-cal-day.today     { background: var(--gold-dark); color: #fff; font-weight: 700; }
.evt-cal-day.has-event { background: var(--badge-bg-gold); color: var(--gold-dark); font-weight: 700; cursor: pointer; }
.evt-cal-day.has-event:hover { background: var(--gold-dark); color: #fff; }
.evt-cal-day.other-month { opacity: 0.35; }

/* ── Modal detail extras ─────────────────────────────────────────── */
.evt-detail-heading  { margin-bottom: 14px; }
.evt-detail-eyebrow-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.evt-detail-eyebrow  { font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-4); margin-bottom: 6px; }
.evt-detail-title    { font-family: var(--font-serif); font-size: 19px; font-weight: 700; color: var(--text); line-height: 1.35; }
.evt-detail-organizer {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--text-3); font-weight: 500; margin-top: 6px;
}
.evt-detail-desc     { font-size: 13px; color: var(--text-2); line-height: 1.65; margin: 0 0 16px; }
.evt-detail-meta     { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 16px; }
.evt-detail-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 7px 12px;
  font-size: 12px; color: var(--text-2);
}
.evt-detail-chip strong { color: var(--text); font-weight: 700; }
.evt-detail-chip.is-ceu  { background: var(--badge-bg-gold); border-color: var(--gold); color: var(--gold-dark); }
.evt-detail-chip.is-ceu strong { color: var(--gold-dark); }
.evt-detail-chip.is-free { background: var(--green-light); border-color: var(--green); color: var(--green-dark); }
.evt-detail-chip.is-free strong { color: var(--green-dark); }
.evt-detail-chip.is-paid { background: var(--surface-2); color: var(--text-2); }

.evt-detail-external {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  font-size: 12px; color: var(--gold-dark); font-weight: 600;
  background: var(--badge-bg-gold); border: 1px solid var(--gold);
  border-radius: var(--radius); padding: 10px 14px; margin-bottom: 14px;
}
.evt-detail-external a { color: var(--gold-dark); text-decoration: none; font-weight: 700; }
.evt-detail-external a:hover { text-decoration: underline; }
.evt-detail-external-note { font-size: 11px; color: var(--text-3); font-weight: 400; }

.evt-external-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase;
  padding: 3px 8px; border-radius: var(--radius-full);
  background: var(--blue-light); color: var(--blue-dark);
}

.evt-detail-registered-banner {
  display: flex; align-items: center; gap: 10px;
  background: var(--green-light); border: 1px solid var(--green);
  border-radius: var(--radius); padding: 12px 16px;
  font-size: 13px; font-weight: 600; color: var(--green-dark);
  margin-bottom: 4px;
}

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 1100px) {
  .evt-layout  { grid-template-columns: 1fr; }
  .evt-sidebar { position: static; }
}
@media (max-width: 680px) {
  .evt-card { grid-template-columns: 56px minmax(0, 1fr); gap: 16px; }
  .evt-date-block { padding-right: 16px; }
  .evt-actions { grid-column: 1 / -1; flex-direction: row; }
}
@media (max-width: 720px) {
  .evt-toolbar { flex-direction: column; align-items: stretch; }
  .evt-search-wrap, .evt-toolbar-select { width: 100%; }
}
@media (max-width: 600px) {
  .ceu-row { grid-template-columns: 1fr; gap: 6px; }
  .ceu-row-meta { text-align: left; }
}
</style>
