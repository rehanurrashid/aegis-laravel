<template>
  <AppLayout>
    <!-- ── HERO ── -->
    <AegisHeroBanner
      eyebrow="Document Vault"
      title="Secure Document Storage"
      subtitle="Securely store sensitive documents and access information for use during a verified critical moment."
      quiet
    >
      <template #actions>
        <a :href="route('activity.index', { module: 'vault' })" class="btn-hero-ghost is-on-light" data-tooltip="Module activity">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button type="button" class="btn-hero-ghost is-on-light" @click="modals.permissions = true">
          <AegisIcon name="users" :size="14" /> Permissions
        </button>
        <button
          type="button"
          class="btn-hero-ghost is-on-light"
          :data-tooltip="attestedAt ? 'Vault attested — click to update or clear' : 'Attest your Vault contains the essential supplemental info for your Continuity Plan'"
          @click="modals.attest = true"
        >
          <AegisIcon :name="attestedAt ? 'shield-check' : 'shield'" :size="14" />
          {{ attestedAt ? 'Vault Attested' : 'Attest Vault' }}
        </button>
        <button type="button" class="btn-hero-solid is-on-light" @click="openUploadModal()">
          <AegisIcon name="upload" :size="14" /> Upload Document
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ── STAT CHIPS ── -->
    <div class="stat-chips-row">
      <AegisStatChip icon="file-text" :value="(zones.standard?.length ?? 0) + (zones.emergency?.length ?? 0)" label="Total Documents" />
      <AegisStatChip icon="lock"      :value="zones.emergency?.length ?? 0"  label="Sensitive Information" />
      <AegisStatChip icon="clock"     :value="actionNeededCount"              label="Action Needed" />
      <AegisStatChip icon="users"     :value="stewardsWithAccess"             label="People granted access during a critical moment" />
    </div>

    <!-- ANNUAL REVIEW ALERT -->
    <PlanReviewAlert
      :plan-status="planStatus"
      :annual-review-date="annualReviewDate"
      :has-draft-in-progress="hasDraftInProgress"
      :draft-plan-version="draftPlanVersion"
      context="vault"
    />

    <!-- ── ATTESTATION BANNERS ── -->
    <div v-if="attestedAt" class="alert alert-success vault-attest-banner">
      <div class="alert-icon"><AegisIcon name="shield-check" :size="16" /></div>
      <div class="alert-content">
        <div class="alert-title">Vault attested as complete</div>
        <div class="vault-attest-meta">You attested on {{ fmtDate(attestedAt) }}. Your Continuity Stewards and Support Stewards can see this confirmation.</div>
      </div>
      <button type="button" class="btn btn-outline" @click="modals.attest = true">Update</button>
    </div>
    <div v-else-if="planStatus !== 'none'" class="alert alert-warning vault-attest-banner">
      <div class="alert-icon"><AegisIcon name="clock" :size="16" /></div>
      <div class="alert-content">
        <div class="alert-title">Vault not yet attested</div>
        <div class="vault-attest-meta">Once you've uploaded the supplemental documents referenced by your Continuity Plan, attest that the Vault is complete so your Stewards know the essential information is in place.</div>
      </div>
      <button type="button" class="btn btn-primary" @click="modals.attest = true">Attest Vault</button>
    </div>

    <!-- DOCS-REQUIRED GUIDANCE -->
    <div class="vault-gold-alert">
      <div class="vault-gold-alert-inner">
        <div class="vault-gold-icon"><AegisIcon name="clipboard-check" :size="16" /></div>
        <div class="vault-gold-body">
          <div class="vault-gold-title">Documents required per critical incident</div>
          <div class="vault-gold-desc">Configure which documents each incident type requires (e.g., Death Certificate, Doctor's Note, Police Report) in the Continuity Plan Builder. Required documents are checked against this Vault when your Continuity Steward verifies an incident.</div>
        </div>
        <a :href="route('provider.plan.index')" class="btn btn-outline vault-gold-cta">
          Open Builder <AegisIcon name="arrow-right-line" :size="12" />
        </a>
      </div>
    </div>

    <!-- NEEDS ATTENTION -->
    <div v-if="needsAttention.length" class="card vault-attention-card">
      <div class="card-header">
        <div class="card-title vault-section-title-row">
          <span class="vault-section-icon"><AegisIcon name="alert-triangle" :size="15" /></span>
          Needs Attention
          <span class="section-badge">{{ needsAttention.length }}</span>
        </div>
      </div>
      <div class="list-group vault-list-group-flush">
        <div v-for="alert in needsAttention" :key="alert.item.id" class="list-group-item vault-attention-item">
          <div class="vault-attention-icon-wrap">
            <AegisIcon name="clock" :size="14" :class="alert.urgent ? 'text-red' : 'text-orange'" />
          </div>
          <div class="vault-attention-body">
            <div class="vault-attention-name">{{ alert.item.title }}</div>
            <div class="vault-attention-when" :class="alert.urgent ? 'text-red' : 'text-orange'">
              {{ alert.when }} &mdash; {{ fmtDateShort(alert.item.expires_at) }}
            </div>
          </div>
          <button v-if="alert.urgent" class="btn btn-danger" @click="openUploadModal('standard')">Update Now</button>
          <button v-else class="btn btn-outline" @click="openUploadModal('standard')">Update</button>
        </div>
      </div>
    </div>

    <!-- SECURITY STATUS BANNER -->
    <div class="alert alert-success vault-security-banner">
      <div class="alert-icon"><AegisIcon name="shield-check" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Vault Status: Secure</div>
        <div>All documents encrypted at rest and in transit. Last security scan: Today at 6:00 AM.</div>
        <div class="vault-compliance-badges">
          <span class="badge badge-green">HIPAA</span>
          <span class="badge badge-green">SOC 2</span>
          <span class="badge badge-green">HITECH</span>
          <span class="badge badge-green">ZERO-KNOWLEDGE</span>
        </div>
      </div>
    </div>

    <!-- STORAGE BAR -->
    <div class="vault-storage-bar">
      <div class="vault-storage-label">Storage: 1.2 GB / 10 GB</div>
      <div class="progress vault-storage-progress"><div class="progress-bar" style="width:12%"></div></div>
      <div class="vault-storage-used">12% used</div>
      <a :href="route('provider.settings.index')" class="vault-storage-upgrade">
        Upgrade Plan <AegisIcon name="chevron-right" :size="12" />
      </a>
    </div>

    <!-- SENSITIVE INFORMATION RESTRICTED ACCESS BANNER -->
    <div class="alert alert-danger vault-restricted-banner">
      <div class="alert-icon"><AegisIcon name="lock" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Sensitive Information &mdash; Restricted Access</div>
        <div>{{ zones.emergency?.length ?? 0 }} items are stored in your Sensitive Information. These are only accessible to your named Continuity Steward after a verified critical incident is triggered by your Support Steward. You can manage these directly but access by others is strictly controlled.</div>
        <div class="vault-restricted-triggers">
          <AegisIcon name="alert-triangle" :size="14" />
          <span><strong>Trigger Conditions:</strong> Death &middot; Short-Term Incapacitation &middot; Long-Term Incapacitation &middot; Missing Person &middot; Detainment &middot; Natural Disaster &middot; Geopolitical or Conflict-Related Events</span>
        </div>
        <div class="vault-restricted-actions">
          <button class="btn btn-danger" @click="setActiveTab('emergency')">Manage Sensitive Information</button>
        </div>
      </div>
    </div>

    <!-- TWO-TIER TABS -->
    <div class="tabs-twotier">

      <!-- Primary tabs -->
      <div class="tabs-primary" role="tablist" aria-label="Document Vault sections">
        <button
          v-for="tab in primaryTabs"
          :key="tab.key"
          type="button"
          class="tab-primary"
          :class="{ active: activeTab === tab.key }"
          role="tab"
          @click="setActiveTab(tab.key)"
        >
          <AegisIcon :name="tab.icon" :size="15" />
          {{ tab.label }}
          <span class="tab-count">{{ tab.count }}</span>
        </button>
      </div>

      <!-- Secondary pills — scoped per primary tab -->
      <div class="tabs-segmented tabs-secondary-pills" role="tablist">
        <!-- All Documents pills -->
        <button
          v-for="pill in allDocPills"
          v-show="activeTab === 'all' || activeTab === 'emergency'"
          :key="'all-' + pill.cat"
          type="button"
          class="tab-pill"
          :class="{ active: activeCat === pill.cat }"
          @click="activeCat = pill.cat"
        >
          {{ pill.label }} <span class="badge-pill">{{ pill.count }}</span>
        </button>

        <!-- Client Roster pills -->
        <button
          v-for="pill in rosterPills"
          v-show="activeTab === 'clientroster'"
          :key="'roster-' + pill.cat"
          type="button"
          class="tab-pill"
          :class="{ active: activeCat === pill.cat }"
          @click="activeCat = pill.cat"
        >
          {{ pill.label }} <span class="badge-pill">{{ pill.count }}</span>
        </button>
      </div>
    </div>

    <!-- TAB CONTENT WRAPPER -->
    <div class="vault-tab-content">

        <!-- ══════ TAB: ALL DOCUMENTS ══════ -->
        <div v-show="activeTab === 'all'">
          <!-- Toolbar -->
          <div class="vault-toolbar">
            <div class="input-group vault-search">
              <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
              <input v-model="searchQuery" type="text" class="form-input form-input-sm" placeholder="Search documents by name, type, tags..." autocomplete="off" />
            </div>
            <select v-model="filterType" class="form-select form-select-sm">
              <option value="all">All Types</option>
              <option value="pdf">PDF</option>
              <option value="docx">Word (.docx)</option>
              <option value="image">Image</option>
            </select>
            <select v-model="sortOrder" class="form-select form-select-sm">
              <option value="newest">Sort: Newest First</option>
              <option value="oldest">Sort: Oldest First</option>
              <option value="name">Sort: Name A&ndash;Z</option>
              <option value="expiring">Sort: Expiring Soon</option>
            </select>
          </div>

          <div class="section-header">
            <div class="section-title">
              All Documents
              <span class="section-badge">{{ filteredStandard.length }} total &middot; grouped by category</span>
            </div>
            <button class="btn btn-primary" @click="openUploadModal()">
              <AegisIcon name="plus" :size="14" /> Add Document
            </button>
          </div>

          <AegisEmptyState
            v-if="!filteredStandard.length"
            icon="file-text"
            title="No documents yet"
            subtitle="Upload your first document to start building your continuity vault."
          >
            <template #action>
              <button class="btn btn-primary" @click="openUploadModal('standard')">Upload Document</button>
            </template>
          </AegisEmptyState>

          <template v-else>
            <div
              v-for="(items, cat) in filteredByCategory"
              :key="cat"
              class="vault-category-section"
              :data-category="cat"
            >
              <div class="vault-category-header">
                <div class="vault-category-icon"><AegisIcon :name="categoryIcon(cat)" :size="18" /></div>
                <div class="vault-category-name">{{ cat }}</div>
                <span class="vault-category-count">{{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}</span>
                <div class="vault-category-line"></div>
              </div>
              <div class="doc-grid">
                <div
                  v-for="doc in items"
                  :key="doc.id"
                  class="doc-card"
                  @click="openDocDetail(doc)"
                >
                  <div class="doc-card-top">
                    <div class="doc-file-icon"><AegisIcon name="file-text" :size="20" /></div>
                    <div class="doc-card-meta">
                      <div class="doc-card-name">{{ doc.title }}</div>
                      <div v-if="doc.sub_label" class="doc-card-sub">{{ doc.sub_label }}</div>
                    </div>
                  </div>
                  <div class="doc-card-tags">
                    <span v-if="doc._sensitive" class="badge badge-red">Sensitive</span>
                    <span v-else-if="statusBadge(doc.status)" :class="['badge', statusBadge(doc.status).cls]">{{ statusBadge(doc.status).label }}</span>
                    <span v-for="tag in (doc.tags ?? [])" :key="tag" class="badge badge-gold">{{ tag }}</span>
                  </div>
                  <div class="doc-card-footer">
                    <div class="doc-card-date">{{ dateLine(doc) }}</div>
                    <div v-if="daysUntil(doc.expires_at) !== null && daysUntil(doc.expires_at) <= 30 && daysUntil(doc.expires_at) >= 0" class="expiry-warning">
                      <AegisIcon name="alert-triangle" :size="11" />&nbsp;Expires in {{ daysUntil(doc.expires_at) }} day{{ daysUntil(doc.expires_at) === 1 ? '' : 's' }}
                    </div>
                    <div v-else-if="daysUntil(doc.expires_at) !== null && daysUntil(doc.expires_at) < 0" class="expiry-warning expiry-warning--expired">
                      <AegisIcon name="alert-triangle" :size="11" />&nbsp;Expired
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- ══════ TAB: SENSITIVE INFORMATION ══════ -->
        <div v-show="activeTab === 'emergency'">
          <div class="card vault-emergency-completeness">
            <div class="card-header">
              <div class="card-title vault-section-title-row">
                <span class="vault-section-icon vault-section-icon--red"><AegisIcon name="alert-triangle" :size="15" /></span>
                Sensitive Information Completeness
              </div>
            </div>
            <div class="card-body">
              <div class="vault-emergency-progress-desc">
                <strong>{{ zones.emergency?.length ?? 0 }} of 6</strong> recommended items uploaded. Add the rest so your Continuity Steward has everything they need in an incident.
              </div>
              <div class="progress vault-emergency-progress-bar">
                <div class="progress-bar red" :style="{ width: emergencyProgressPct + '%' }"></div>
              </div>
              <div class="vault-emergency-pct">{{ emergencyProgressPct }}% complete</div>
            </div>
          </div>

          <div class="alert alert-danger">
            <div class="alert-icon"><AegisIcon name="lock" :size="18" /></div>
            <div class="alert-content">
              <div class="alert-title">Sensitive Information &mdash; Maximum Security</div>
              <div>Items in this vault are encrypted with an additional layer. They are <strong>only accessible to your designated Continuity Steward</strong> after a verified critical incident is triggered by your Support Steward.</div>
            </div>
          </div>

          <div class="section-header">
            <div class="section-title">
              Sensitive Information Items
              <span class="section-badge">{{ zones.emergency?.length ?? 0 }} stored &middot; CS access only</span>
            </div>
            <button class="btn btn-danger" @click="openUploadModal('emergency')">
              <AegisIcon name="plus" :size="14" /> Add to Vault
            </button>
          </div>

          <AegisEmptyState
            v-if="!(zones.emergency?.length)"
            icon="lock"
            title="Sensitive Information is empty"
            subtitle="Add the documents your Continuity Steward will need: client notification letters, transfer SOPs, POA, succession instructions."
          >
            <template #action>
              <button class="btn btn-danger" @click="openUploadModal('emergency')">Add First Item</button>
            </template>
          </AegisEmptyState>

          <template v-else>
            <div class="doc-grid">
              <div
                v-for="doc in zones.emergency"
                :key="doc.id"
                class="doc-card"
                @click="openDocDetail(doc)"
              >
                <div class="doc-card-top">
                  <div class="doc-file-icon doc-file-icon--sensitive"><AegisIcon name="lock" :size="20" /></div>
                  <div class="doc-card-meta">
                    <div class="doc-card-name">{{ doc.title }}</div>
                    <div v-if="doc.sub_label" class="doc-card-sub">{{ doc.sub_label }}</div>
                  </div>
                </div>
                <div class="doc-card-tags">
                  <span class="badge badge-red">Emergency Only</span>
                  <span v-for="tag in (doc.tags ?? [])" :key="tag" class="badge badge-gold">{{ tag }}</span>
                </div>
                <div class="doc-card-footer">
                  <div class="doc-card-date">{{ dateLine(doc) }}</div>
                </div>
              </div>
            </div>
          </template>
        </div>



        <!-- ══════ TAB: SYSTEM ACCESS CREDENTIALS ══════ -->
        <div v-show="activeTab === 'credentials'">
          <div class="alert alert-gold">
            <div class="alert-icon"><AegisIcon name="key" :size="18" /></div>
            <div class="alert-content">
              <div class="alert-title">System Access Credentials Vault</div>
              <div>Credentials are <strong>vault-locked</strong> &mdash; only your Continuity Steward can view them after a verified critical incident is activated.</div>
            </div>
          </div>

          <div class="section-header">
            <div class="section-title">
              Practice System Credentials
              <span class="section-badge">{{ zones.credentials?.length ?? 0 }} stored &middot; CS access only</span>
            </div>
            <button class="btn btn-primary" @click="modals.addCredential = true">
              <AegisIcon name="plus" :size="14" /> Add Credential
            </button>
          </div>

          <AegisEmptyState
            v-if="!(zones.credentials?.length)"
            icon="key"
            title="No credentials stored"
            subtitle="Add EHR, banking, billing, and other practice system logins so your Continuity Steward can access them during a critical incident."
          >
            <template #action>
              <button class="btn btn-primary" @click="modals.addCredential = true">Add First Credential</button>
            </template>
          </AegisEmptyState>

          <template v-else>
            <div v-for="cred in zones.credentials" :key="cred.id" class="cred-card">
              <div class="cred-icon"><AegisIcon name="key" :size="18" /></div>
              <div class="cred-body">
                <div class="cred-name">{{ cred.title }}</div>
                <div v-if="cred.credential_username" class="cred-username">{{ cred.credential_username }}</div>
                <a v-if="cred.credential_url" :href="cred.credential_url" target="_blank" rel="noopener" class="cred-url">
                  {{ cred.credential_url.replace(/^https?:\/\/(www\.)?/, '') }}
                </a>
                <div v-if="cred.sub_label" class="cred-note">{{ cred.sub_label }}</div>
                <div v-if="cred.credential_username" class="cred-password">
                  <span>&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;</span>
                  <span class="cred-password-label">Hidden &mdash; CS access only</span>
                </div>
              </div>
              <div class="cred-actions">
                <button v-if="cred.credential_username" class="btn-icon" data-tooltip="Copy username" @click="toast.success('Username copied')">
                  <AegisIcon name="copy" :size="14" />
                </button>
                <button class="btn-icon btn-icon-danger" data-tooltip="Remove credential" @click="deleteItem(cred)">
                  <AegisIcon name="trash" :size="14" />
                </button>
              </div>
            </div>

            <AegisPagination
              v-if="(zones.credentials?.length ?? 0) > 10"
              v-model:page="credentialsPage"
              :total="zones.credentials?.length ?? 0"
              :per-page="10"
            />
          </template>

          <div class="alert alert-info vault-cred-info">
            <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
            <div class="alert-content">
              Passwords are hidden during normal operations. They are only revealed to your Continuity Steward after a verified critical incident is activated.
            </div>
          </div>
        </div>

        <!-- ══════ TAB: CLIENT ROSTER ══════ -->
        <div v-show="activeTab === 'clientroster'">
          <div class="alert alert-gold">
            <div class="alert-icon"><AegisIcon name="lock" :size="18" /></div>
            <div class="alert-content">
              <div class="alert-title">Client Roster &mdash; Vault Protected</div>
              <div>This roster is <strong>locked during normal operations</strong> and only accessible to your designated Continuity Steward after a verified critical incident is activated.</div>
            </div>
          </div>

          <div class="alert alert-info">
            <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
            <div class="alert-content">
              <strong>Please identify clients who need priority outreach or specialized support in critical moments.</strong>
            </div>
          </div>

          <div class="vault-toolbar vault-roster-toolbar">
            <div class="input-group vault-search">
              <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
              <input v-model="rosterSearch" type="text" class="form-input form-input-sm" placeholder="Search clients..." autocomplete="off" />
            </div>
            <label class="form-check vault-reminder-check">
              <input v-model="quarterlyReminders" type="checkbox" @change="toast.info(quarterlyReminders ? 'Quarterly reminders enabled' : 'Quarterly reminders disabled')" />
              <span class="form-check-label">Quarterly update reminders</span>
            </label>
            <button class="btn btn-primary vault-roster-add" @click="modals.addClient = true">
              <AegisIcon name="plus" :size="14" /> Add Client
            </button>
          </div>

          <div class="section-header">
            <div class="section-title">
              Active Clients
              <span class="section-badge">{{ filteredActiveClients.length }}</span>
            </div>
          </div>

          <AegisEmptyState
            v-if="!filteredActiveClients.length"
            icon="users"
            title="No clients on your roster yet"
            subtitle="Add clients here so your Continuity Steward knows who to reach in a critical incident."
          >
            <template #action>
              <button class="btn btn-primary" @click="modals.addClient = true">Add Client</button>
            </template>
          </AegisEmptyState>

          <template v-else>
            <div class="cr-table-wrap">
              <table class="cr-table">
                <colgroup>
                  <col class="cr-col-name" /><col class="cr-col-service" /><col class="cr-col-pri" /><col class="cr-col-actions" />
                </colgroup>
                <thead>
                  <tr>
                    <th class="cr-col-name">Client Name &amp; Location</th>
                    <th class="cr-col-service">Service &amp; Phone</th>
                    <th class="cr-col-pri">Priority</th>
                    <th class="cr-col-actions">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="client in paginatedClients" :key="client.id">
                    <td class="cr-col-name">
                      {{ client.client_name }}
                      <div v-if="client.client_location" class="cr-sub">{{ client.client_location }}</div>
                    </td>
                    <td class="cr-col-service">
                      {{ client.client_service || '&mdash;' }}
                      <div v-if="client.client_phone" class="cr-sub">{{ client.client_phone }}</div>
                    </td>
                    <td class="cr-col-pri">
                      <span v-if="client.client_priority" class="badge badge-red">Priority</span>
                      <span v-else class="badge badge-gray">Standard</span>
                    </td>
                    <td class="cr-col-actions">
                      <div class="cr-action-group">
                        <button class="btn-icon" data-tooltip="View" @click="viewClient(client)"><AegisIcon name="eye" :size="14" /></button>
                        <button class="btn-icon" data-tooltip="Edit" @click="editClient(client)"><AegisIcon name="pencil" :size="14" /></button>
                        <button class="btn-icon btn-icon-danger" data-tooltip="Discharge / Close" @click="dischargeClient(client)"><AegisIcon name="check" :size="14" /></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <AegisPagination
              v-if="filteredActiveClients.length > rosterPageSize"
              v-model:page="rosterPage"
              :total="filteredActiveClients.length"
              :per-page="rosterPageSize"
            />
          </template>

          <div v-if="dischargedClients.length" class="vault-discharged-section">
            <div class="section-header">
              <div class="section-title">
                Discharged / Closed
                <span class="section-badge">{{ dischargedClients.length }}</span>
              </div>
            </div>
            <div class="cr-table-wrap cr-table-wrap--dimmed">
              <table class="cr-table">
                <colgroup><col class="cr-discharged-name" /><col class="cr-discharged-service" /><col class="cr-discharged-date" /><col class="cr-discharged-status" /></colgroup>
                <thead><tr><th>Client Name</th><th>Service Type</th><th>Closed Date</th><th class="cr-col-right">Status</th></tr></thead>
                <tbody>
                  <tr v-for="client in dischargedClients" :key="client.id">
                    <td class="cr-discharged-name-cell">
                      {{ client.client_name }}
                      <div v-if="client.client_location" class="cr-sub">{{ client.client_location }}</div>
                    </td>
                    <td class="cr-muted">{{ client.client_service || '—' }}</td>
                    <td class="cr-muted">&mdash;</td>
                    <td class="cr-col-right"><span class="badge badge-gray">Discharged</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

    </div><!-- /vault-tab-content -->

    <!-- ══════════════ MODALS ══════════════ -->

    <!-- UPLOAD DOCUMENT -->
    <AegisModal v-model="modals.upload" size="lg" title="Upload Document" @close="closeUploadModal">
      <div class="form-group">
        <AegisDropzone
          @files="uploadFiles = $event"
          @rejected="toast.error('File rejected — check type and size.')"
          :accept="['.pdf','.doc','.docx','.jpg','.jpeg','.png']"
          :max-size="50"
        />
      </div>
      <div class="form-group">
        <label class="form-label" for="up-title">Document Name <span class="required">*</span></label>
        <input
          id="up-title"
          v-model="uploadForm.title"
          type="text"
          class="form-input"
          :class="{ 'is-error': fieldError('title') }"
          placeholder="e.g., NY License 2025"
          @blur="vUpload$.title.$touch()"
        />
        <div v-if="fieldError('title')" class="form-error">{{ fieldError('title') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="up-cat">Category <span class="required">*</span></label>
          <select
            id="up-cat"
            v-model="uploadForm.category"
            class="form-select"
            :class="{ 'is-error': fieldError('category') }"
            @blur="vUpload$.category.$touch()"
          >
            <option value="">Select category...</option>
            <option>Agreements &amp; Contracts</option>
            <option>Insurance Policies</option>
            <option>Clinical Documents</option>
            <option>Financial Documents</option>
          </select>
          <div v-if="fieldError('category')" class="form-error">{{ fieldError('category') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="up-type">Document Type</label>
          <select id="up-type" v-model="uploadForm.doc_type" class="form-select">
            <option>General Document</option>
            <option>License</option>
            <option>Agreement / Contract</option>
            <option>Insurance Policy</option>
            <option>Tax Document</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="up-issued">Issue Date</label>
          <input id="up-issued" v-model="uploadForm.issued_at" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label" for="up-expires">Expiry Date (if applicable)</label>
          <input id="up-expires" v-model="uploadForm.expires_at" type="date" class="form-input" />
        </div>
      </div>
      <div class="form-group">
        <label class="vault-check-label">
          <input v-model="uploadForm.is_sensitive" type="checkbox" />
          Mark as sensitive (emergency-only access)
        </label>
        <p class="form-hint">Only accessible to your Continuity Steward after a verified critical incident</p>
      </div>
      <div class="form-group">
        <label class="form-label" for="up-notes">Notes / Description</label>
        <textarea id="up-notes" v-model="uploadForm.description" class="form-textarea" placeholder="Optional: add context, license numbers, policy details..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Who can access this document?</label>
        <div class="list-group vault-access-list">
          <div v-if="!stewards.length" class="list-group-item vault-no-stewards">
            No stewards assigned yet.
          </div>
          <div v-for="s in stewards" :key="s.id" class="list-group-item">
            <div class="avatar avatar-sm avatar-gold">{{ s.avatar_initials }}</div>
            <div class="vault-steward-info">
              <div class="vault-steward-name">{{ s.display_name }}</div>
              <div class="vault-steward-role">{{ s.role_label }}</div>
            </div>
            <select class="form-select form-select-sm vault-access-select">
              <option :selected="s.vault_access === 'full'">Full Access</option>
              <option>View &amp; Download</option>
              <option :selected="s.vault_access !== 'full'">View Only</option>
              <option>No Access</option>
            </select>
          </div>
        </div>
        <div class="form-hint">You can change permissions anytime from the Permissions panel.</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeUploadModal">Cancel</button>
        <button class="btn btn-primary" :disabled="uploadBusy" @click="submitUpload">
          <AegisIcon v-if="uploadBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="upload" :size="13" />
          {{ uploadBusy ? 'Uploading...' : 'Upload & Save' }}
        </button>
      </template>
    </AegisModal>

    <!-- ADD CREDENTIAL -->
    <AegisModal v-model="modals.addCredential" size="lg" title="Add Secure Credential" @close="closeCredentialModal">
      <div class="vault-cred-notice">
        Credentials are vault-locked — only accessible to your Continuity Steward after a verified emergency.
      </div>
      <div class="form-group">
        <label class="form-label" for="cr-name">System / Portal Name <span class="required">*</span></label>
        <input id="cr-name" v-model="credForm.title" type="text" class="form-input" :class="{ 'is-error': fieldErrorCred('title') }" placeholder="e.g. SimplePractice EHR" @blur="vCred$.title.$touch()" />
        <div v-if="fieldErrorCred('title')" class="form-error">{{ fieldErrorCred('title') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="cr-cat">Category <span class="required">*</span></label>
        <select id="cr-cat" v-model="credForm.category" class="form-select" :class="{ 'is-error': fieldErrorCred('category') }" @blur="vCred$.category.$touch()">
          <option value="">Select category…</option>
          <option>EHR / Practice Management</option>
          <option>Medical Billing</option>
          <option>Insurance Portal</option>
          <option>Business Banking</option>
          <option>Practice Email</option>
          <option>Telehealth Platform</option>
          <option>Licensing Board Portal</option>
          <option>Other</option>
        </select>
        <div v-if="fieldErrorCred('category')" class="form-error">{{ fieldErrorCred('category') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="cr-user">Username / Email <span class="required">*</span></label>
          <input id="cr-user" v-model="credForm.credential_username" type="text" class="form-input" :class="{ 'is-error': fieldErrorCred('credential_username') }" placeholder="e.g. dr.smith@practice.com" @blur="vCred$.credential_username.$touch()" />
          <div v-if="fieldErrorCred('credential_username')" class="form-error">{{ fieldErrorCred('credential_username') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="cr-pass">Password <span class="required">*</span></label>
          <div class="ob-password-wrap">
            <input id="cr-pass" v-model="credForm.credential_password" :type="showCredPass ? 'text' : 'password'" class="form-input" :class="{ 'is-error': fieldErrorCred('credential_password') }" placeholder="Enter password" @blur="vCred$.credential_password.$touch()" />
            <button type="button" class="ob-password-toggle" @click="showCredPass = !showCredPass">
              <AegisIcon :name="showCredPass ? 'eye-off' : 'eye'" :size="14" />
            </button>
          </div>
          <div v-if="fieldErrorCred('credential_password')" class="form-error">{{ fieldErrorCred('credential_password') }}</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="cr-url">Portal URL</label>
        <input id="cr-url" v-model="credForm.credential_url" type="url" class="form-input" placeholder="https://portal.example.com" />
      </div>
      <div class="form-group">
        <label class="form-label" for="cr-notes">Notes for Continuity Steward</label>
        <textarea id="cr-notes" v-model="credForm.description" class="form-textarea" rows="2" placeholder="e.g. 2FA via authenticator app · support: help@example.com"></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeCredentialModal">Cancel</button>
        <button class="btn btn-primary" :disabled="credBusy" @click="submitCredential">
          <AegisIcon v-if="credBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="key" :size="13" />
          {{ credBusy ? 'Saving...' : 'Save Credential' }}
        </button>
      </template>
    </AegisModal>

    <!-- ADD CLIENT -->
    <AegisModal v-model="modals.addClient" size="lg" title="Add Client" @close="closeClientModal">
      <div class="vault-client-notice">
        Please identify clients who need priority outreach or specialized support in critical moments.
      </div>
      <div class="form-group">
        <label class="form-label" for="cl-name">Client Full Name <span class="required">*</span></label>
        <input id="cl-name" v-model="clientForm.client_name" type="text" class="form-input" :class="{ 'is-error': fieldErrorClient('client_name') }" placeholder="e.g. Jane Smith" @blur="vClient$.client_name.$touch()" />
        <div v-if="fieldErrorClient('client_name')" class="form-error">{{ fieldErrorClient('client_name') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="cl-city">City <span class="required">*</span></label>
          <input id="cl-city" v-model="clientForm.client_city" type="text" class="form-input" :class="{ 'is-error': fieldErrorClient('client_city') }" placeholder="e.g. Brooklyn" @blur="vClient$.client_city.$touch()" />
          <div v-if="fieldErrorClient('client_city')" class="form-error">{{ fieldErrorClient('client_city') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="cl-state">State <span class="required">*</span></label>
          <input id="cl-state" v-model="clientForm.client_state" type="text" class="form-input" :class="{ 'is-error': fieldErrorClient('client_state') }" placeholder="NY" maxlength="2" @blur="vClient$.client_state.$touch()" />
          <div v-if="fieldErrorClient('client_state')" class="form-error">{{ fieldErrorClient('client_state') }}</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="cl-phone">Phone</label>
          <input id="cl-phone" v-model="clientForm.client_phone" type="tel" class="form-input" placeholder="(555) 000-0000" />
        </div>
        <div class="form-group">
          <label class="form-label" for="cl-email">Email</label>
          <input id="cl-email" v-model="clientForm.client_email" type="email" class="form-input" placeholder="client@email.com" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="cl-service">Service Type <span class="required">*</span></label>
        <input id="cl-service" v-model="clientForm.client_service" type="text" class="form-input" :class="{ 'is-error': fieldErrorClient('client_service') }" placeholder="e.g. Individual therapy" @blur="vClient$.client_service.$touch()" />
        <div v-if="fieldErrorClient('client_service')" class="form-error">{{ fieldErrorClient('client_service') }}</div>
      </div>
      <div class="form-group">
        <label class="vault-check-label">
          <input v-model="clientForm.client_priority" type="checkbox" />
          Mark as Priority Response
        </label>
        <p class="form-hint">Flag for immediate outreach in a critical moment</p>
      </div>
      <div class="form-group">
        <label class="form-label" for="cl-notes">Unique Needs or Considerations</label>
        <textarea id="cl-notes" v-model="clientForm.client_notes" class="form-textarea" rows="3" placeholder="Time-sensitive needs, elevated risk, or individualized care considerations…"></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeClientModal">Cancel</button>
        <button class="btn btn-primary" :disabled="clientBusy" @click="submitClient">
          <AegisIcon v-if="clientBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="check" :size="13" />
          {{ clientBusy ? 'Saving...' : 'Save to Roster' }}
        </button>
      </template>
    </AegisModal>

    <!-- VIEW / EDIT CLIENT -->
    <AegisModal v-model="modals.editClient" size="lg" :title="editClientMode === 'view' ? 'Client Details' : 'Edit Client'" @close="closeEditClientModal">
      <template v-if="editClientMode === 'view' && activeClient">
        <div class="vault-detail-grid">
          <div><div class="vault-detail-label">Full Name</div><div class="vault-detail-val">{{ activeClient.client_name }}</div></div>
          <div><div class="vault-detail-label">Location</div><div class="vault-detail-val">{{ activeClient.client_location || '—' }}</div></div>
          <div><div class="vault-detail-label">Phone</div><div class="vault-detail-val">{{ activeClient.client_phone || '—' }}</div></div>
          <div><div class="vault-detail-label">Email</div><div class="vault-detail-val">{{ activeClient.client_email || '—' }}</div></div>
          <div class="vault-detail-full"><div class="vault-detail-label">Service Type</div><div class="vault-detail-val">{{ activeClient.client_service || '—' }}</div></div>
          <div class="vault-detail-full">
            <div class="vault-detail-label">Priority Status</div>
            <span v-if="activeClient.client_priority" class="badge badge-red">Priority Response</span>
            <span v-else class="badge badge-gray">Standard</span>
          </div>
          <div v-if="activeClient.client_notes" class="vault-detail-full">
            <div class="vault-detail-label">Unique Needs</div>
            <div class="vault-client-notes">{{ activeClient.client_notes }}</div>
          </div>
        </div>
      </template>
      <template v-else-if="editClientMode === 'edit'">
        <div class="form-group">
          <label class="form-label">Client Full Name <span class="required">*</span></label>
          <input v-model="editClientForm.client_name" type="text" class="form-input" :class="{ 'is-error': fieldErrorEditClient('client_name') }" @blur="vEditClient$.client_name.$touch()" />
          <div v-if="fieldErrorEditClient('client_name')" class="form-error">{{ fieldErrorEditClient('client_name') }}</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City <span class="required">*</span></label>
            <input v-model="editClientForm.client_city" type="text" class="form-input" :class="{ 'is-error': fieldErrorEditClient('client_city') }" @blur="vEditClient$.client_city.$touch()" />
            <div v-if="fieldErrorEditClient('client_city')" class="form-error">{{ fieldErrorEditClient('client_city') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">State <span class="required">*</span></label>
            <input v-model="editClientForm.client_state" type="text" class="form-input" maxlength="2" :class="{ 'is-error': fieldErrorEditClient('client_state') }" @blur="vEditClient$.client_state.$touch()" />
            <div v-if="fieldErrorEditClient('client_state')" class="form-error">{{ fieldErrorEditClient('client_state') }}</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input v-model="editClientForm.client_phone" type="tel" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input v-model="editClientForm.client_email" type="email" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Service Type <span class="required">*</span></label>
          <input v-model="editClientForm.client_service" type="text" class="form-input" :class="{ 'is-error': fieldErrorEditClient('client_service') }" @blur="vEditClient$.client_service.$touch()" />
          <div v-if="fieldErrorEditClient('client_service')" class="form-error">{{ fieldErrorEditClient('client_service') }}</div>
        </div>
        <div class="form-group">
          <label class="vault-check-label">
            <input v-model="editClientForm.client_priority" type="checkbox" /> Priority Response
          </label>
        </div>
        <div class="form-group">
          <label class="form-label">Unique Needs or Considerations</label>
          <textarea v-model="editClientForm.client_notes" class="form-textarea" rows="3"></textarea>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-outline" @click="closeEditClientModal">Close</button>
        <button v-if="editClientMode === 'view'" class="btn btn-primary" @click="editClientMode = 'edit'">Edit Client</button>
        <button v-else class="btn btn-primary" :disabled="editClientBusy" @click="submitEditClient">
          <AegisIcon v-if="editClientBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="check" :size="13" />
          {{ editClientBusy ? 'Saving...' : 'Save Changes' }}
        </button>
      </template>
    </AegisModal>

    <!-- DOCUMENT DETAIL -->
    <AegisModal v-model="modals.docDetail" size="lg" title="Document Details" @close="modals.docDetail = false">
      <template v-if="activeDoc">
        <div class="vault-doc-detail-head">
          <div class="doc-detail-icon" :class="activeDoc.zone === 'emergency' ? 'doc-detail-icon--sensitive' : 'doc-detail-icon--standard'">
            <AegisIcon :name="activeDoc.zone === 'emergency' ? 'lock' : 'file-text'" :size="26" />
          </div>
          <div class="vault-doc-detail-info">
            <div class="vault-doc-detail-title">{{ activeDoc.title }}</div>
            <div class="vault-doc-detail-sub">{{ activeDoc.sub_label || (activeDoc.zone === 'emergency' ? 'Sensitive Information item' : 'Vault document') }}</div>
            <div class="vault-doc-detail-tags">
              <span v-if="statusBadge(activeDoc.status)" :class="['badge', statusBadge(activeDoc.status).cls]">{{ statusBadge(activeDoc.status).label }}</span>
              <span v-for="tag in (activeDoc.tags ?? [])" :key="tag" class="badge badge-gold">{{ tag }}</span>
            </div>
          </div>
          <div class="vault-doc-detail-download">
            <a :href="route('vault.download', { item: activeDoc.id })" target="_blank" class="btn btn-primary">Download</a>
          </div>
        </div>

        <div class="modal-section-label">Document Information</div>
        <div class="list-group">
          <div class="list-group-item vault-detail-row">
            <span class="vault-detail-key">Category</span>
            <span class="vault-detail-value">{{ activeDoc.category || '—' }}</span>
          </div>
          <div v-if="activeDoc.issued_at" class="list-group-item vault-detail-row">
            <span class="vault-detail-key">Date Issued</span>
            <span class="vault-detail-value">{{ fmtDateShort(activeDoc.issued_at) }}</span>
          </div>
          <div v-if="activeDoc.expires_at" class="list-group-item vault-detail-row">
            <span class="vault-detail-key">Expires</span>
            <span class="vault-detail-value">{{ fmtDateShort(activeDoc.expires_at) }}</span>
          </div>
          <div v-if="activeDoc.file_ref" class="list-group-item vault-detail-row">
            <span class="vault-detail-key">File Reference</span>
            <span class="vault-detail-value vault-detail-mono">{{ activeDoc.file_ref }}</span>
          </div>
        </div>

        <div class="modal-section-label">Recent Activity</div>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-dot blue"></div>
            <div class="timeline-time">Today</div>
            <div class="timeline-title">You viewed this document</div>
          </div>
          <div v-if="activeDoc.created_at" class="timeline-item">
            <div class="timeline-dot green"></div>
            <div class="timeline-time">{{ fmtDateShort(activeDoc.created_at) }}</div>
            <div class="timeline-title">Document uploaded</div>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-outline" @click="modals.docDetail = false">Close</button>
        <div class="vault-footer-actions">
          <button class="btn btn-outline vault-delete-btn" @click="deleteItem(activeDoc)">
            <AegisIcon name="trash" :size="14" /> Delete
          </button>
          <a v-if="activeDoc" :href="route('vault.download', { item: activeDoc.id })" target="_blank" class="btn btn-primary">
            <AegisIcon name="download" :size="14" /> Download
          </a>
        </div>
      </template>
    </AegisModal>

    <!-- ATTEST VAULT -->
    <AegisModal v-model="modals.attest" size="md" :title="attestedAt ? 'Update Vault Attestation' : 'Attest Vault is Complete'" @close="closeAttestModal">
      <div class="alert alert-info">
        <div class="alert-icon"><AegisIcon name="shield-check" :size="14" /></div>
        <div class="alert-content">By attesting, you confirm that you have uploaded the supplemental documents, credentials, and access information your Continuity Stewards and Support Stewards will need during a verified critical moment. Your Stewards will be notified.</div>
      </div>
      <div v-if="attestedAt" class="alert alert-success">
        <div class="alert-icon"><AegisIcon name="check" :size="14" /></div>
        <div class="alert-content">
          <strong>Currently attested</strong> on {{ fmtDate(attestedAt) }}.
          <div v-if="attestNote" class="vault-attest-note">&#8220;{{ attestNote }}&#8221;</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="attest-note">Note (optional)</label>
        <textarea id="attest-note" v-model="attestForm.note" class="form-textarea" rows="3" placeholder="Anything your Stewards should know about what's in the Vault."></textarea>
      </div>
      <template #footer>
        <button v-if="attestedAt" class="btn btn-outline" :disabled="attestBusy" @click="clearAttest">
          <AegisIcon v-if="attestBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="x" :size="13" />
          {{ attestBusy ? 'Clearing...' : 'Clear Attestation' }}
        </button>
        <button class="btn btn-outline" @click="closeAttestModal">Cancel</button>
        <button class="btn btn-primary" :disabled="attestBusy" @click="submitAttest">
          <AegisIcon v-if="attestBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="shield-check" :size="13" />
          {{ attestBusy ? 'Attesting...' : (attestedAt ? 'Re-Attest' : 'Attest Vault is Complete') }}
        </button>
      </template>
    </AegisModal>

    <!-- VAULT PERMISSIONS -->
    <AegisModal v-model="modals.permissions" size="lg" title="Vault Permissions &amp; Access" @close="modals.permissions = false">
      <div class="alert alert-gold">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">How Vault Permissions Work</div>
          <div>You control who can see which documents. <strong>Sensitive Information</strong> documents have a fixed trigger rule — they unlock only after a Support Steward-verified critical incident and Continuity Steward confirmation.</div>
        </div>
      </div>

      <div class="modal-section-label">Access Level Reference</div>
      <div class="vault-access-levels">
        <div class="vault-access-card"><div class="vault-access-card-label">Full Access</div><div class="vault-access-card-desc">All documents + Sensitive Information. CS only — unlocked after verified critical incident.</div></div>
        <div class="vault-access-card"><div class="vault-access-card-label">Limited Access</div><div class="vault-access-card-desc">Agreements, Licenses, Insurance only. No Sensitive Information. Suitable for administrative staff.</div></div>
        <div class="vault-access-card"><div class="vault-access-card-label">View Only</div><div class="vault-access-card-desc">Read access only. Cannot download, share, or modify.</div></div>
      </div>

      <div class="section-header">
        <div class="section-title">Document Access Permissions</div>
        <button class="btn btn-primary" @click="modals.permissions = false; modals.addPermission = true">Add Person</button>
      </div>

      <div class="list-group">
        <div v-if="!stewards.length" class="list-group-item vault-no-stewards">
          No stewards assigned yet. Add them from the
          <a :href="route('provider.stewards.index')" class="vault-inline-link">Continuity Stewards</a> page.
        </div>
        <div v-for="s in stewards" :key="s.id" class="list-group-item">
          <div class="avatar avatar-sm avatar-gold">{{ s.avatar_initials }}</div>
          <div class="vault-steward-info">
            <div class="vault-steward-name">{{ s.display_name }} &mdash; {{ s.role_label }}</div>
            <div class="vault-steward-role">{{ accessDesc(s.vault_access) }}</div>
          </div>
          <span :class="['badge', accessBadge(s.vault_access).cls]">{{ accessBadge(s.vault_access).label }}</span>
          <button class="btn btn-outline" @click="openEditPermission(s)">Edit</button>
        </div>
      </div>

      <div class="alert alert-gold">
        <div class="alert-icon"><AegisIcon name="lock" :size="18" /></div>
        <div class="alert-content">
          <strong>Sensitive Information Access:</strong> These documents are governed by your Continuity Plan. Access cannot be manually granted — it is released automatically when your Support Steward triggers a critical incident.
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.permissions = false">Close</button>
      </template>
    </AegisModal>

    <!-- ADD PERMISSION -->
    <AegisModal v-model="modals.addPermission" size="md" title="Grant Vault Access" @close="closeAddPermModal">
      <div class="vault-cred-notice">
        You can only grant vault access to verified Continuity Stewards or Support Stewards already linked to your account.
      </div>
      <div class="form-group">
        <label class="form-label" for="pm-person">Person <span class="required">*</span></label>
        <select id="pm-person" v-model="permForm.steward_id" class="form-select" :class="{ 'is-error': fieldErrorPerm('steward_id') }" @blur="vPerm$.steward_id.$touch()">
          <option value="">Select person…</option>
          <option v-for="s in stewards" :key="s.id" :value="s.id">{{ s.display_name }} &mdash; {{ s.role_label }}</option>
        </select>
        <div v-if="fieldErrorPerm('steward_id')" class="form-error">{{ fieldErrorPerm('steward_id') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="pm-level">Access Level <span class="required">*</span></label>
        <select id="pm-level" v-model="permForm.level" class="form-select" :class="{ 'is-error': fieldErrorPerm('level') }" @blur="vPerm$.level.$touch()">
          <option value="">Select level…</option>
          <option value="full">Full — All documents including Sensitive Information (CS only)</option>
          <option value="scoped">Limited — Agreements, Licenses, Insurance only</option>
          <option value="metadata">View Only — Can view but not download or share</option>
        </select>
        <div v-if="fieldErrorPerm('level')" class="form-error">{{ fieldErrorPerm('level') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="pm-expiry">Access Expiry (optional)</label>
        <input id="pm-expiry" v-model="permForm.expires_at" type="date" class="form-input" />
      </div>
      <div class="form-group">
        <label class="vault-check-label">
          <input v-model="permForm.notify" type="checkbox" /> Notify this person by email
        </label>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeAddPermModal">Cancel</button>
        <button class="btn btn-primary" :disabled="permBusy" @click="submitPermission">
          <AegisIcon v-if="permBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="check" :size="13" />
          {{ permBusy ? 'Granting...' : 'Grant Access' }}
        </button>
      </template>
    </AegisModal>

    <!-- EDIT PERMISSION -->
    <AegisModal v-model="modals.editPermission" size="md" title="Edit Vault Access" @close="closeEditPermModal">
      <div class="form-group">
        <label class="form-label" for="ep-level">Access Level</label>
        <select id="ep-level" v-model="editPermForm.level" class="form-select">
          <option value="full">Full — All documents including Sensitive Information</option>
          <option value="scoped">Limited — Agreements, Licenses, Insurance only</option>
          <option value="metadata">View Only — Can view but not download or share</option>
          <option value="none">No Access</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="ep-expiry">Access Expiry (optional)</label>
        <input id="ep-expiry" v-model="editPermForm.expires_at" type="date" class="form-input" />
      </div>
      <div class="form-group">
        <label class="vault-check-label">
          <input v-model="editPermForm.notify" type="checkbox" /> Notify this person of changes
        </label>
      </div>
      <div class="vault-revoke-row">
        <button class="btn btn-outline vault-revoke-btn" @click="confirmAction('Revoke access entirely?', () => revokeAccess())">
          Revoke access entirely
        </button>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeEditPermModal">Cancel</button>
        <button class="btn btn-primary" :disabled="editPermBusy" @click="submitEditPerm">
          <AegisIcon v-if="editPermBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="check" :size="13" />
          {{ editPermBusy ? 'Saving...' : 'Save Changes' }}
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import PlanReviewAlert from '@/components/PlanReviewAlert.vue'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'

// ── Props ────────────────────────────────────────────────────
const props = defineProps({
  zones:              { type: Object,  default: () => ({ standard: [], emergency: [], credentials: [], roster: [] }) },
  planStatus:         { type: String,  default: 'none' },
  attestedAt:         { type: String,  default: null },
  attestNote:         { type: String,  default: '' },
  totalCount:         { type: Number,  default: 0 },
  stewards:           { type: Array,   default: () => [] },
  annualReviewDate:   { type: String,  default: null },
  hasDraftInProgress: { type: Boolean, default: false },
  draftPlanVersion:   { type: Number,  default: null },
})

const toast = useToast()
const { confirmAction } = useConfirm()

// ── Tab state ────────────────────────────────────────────────
const activeTab   = ref('all')
const activeCat   = ref('__all__')
const searchQuery = ref('')
const filterType  = ref('all')
const sortOrder   = ref('newest')

function setActiveTab(tab) {
  activeTab.value = tab
  activeCat.value = '__all__'
  searchQuery.value = ''
}

const primaryTabs = computed(() => [
  { key: 'all',          label: 'All Documents',             icon: 'file-text', count: props.zones.standard?.length ?? 0 },
  { key: 'emergency',    label: 'Sensitive Information',     icon: 'lock',      count: props.zones.emergency?.length ?? 0 },
  { key: 'credentials',  label: 'System Access Credentials', icon: 'key',       count: props.zones.credentials?.length ?? 0 },
  { key: 'clientroster', label: 'Client Roster',             icon: 'users',     count: props.zones.roster?.length ?? 0 },
])

// ── Secondary pills ──────────────────────────────────────────
const allDocPills = computed(() => {
  const cats = {}
  const allDocs = [...(props.zones.standard ?? [])]
  for (const doc of allDocs) {
    const c = doc.category || 'Other'
    cats[c] = (cats[c] ?? 0) + 1
  }
  const shortLabel = {
    'Agreements & Contracts': 'Agreements',
    'Licenses & Credentials': 'Licenses',
    'Insurance Policies':     'Insurance',
    'Clinical Documents':     'Clinical',
    'Financial Documents':    'Financial',
  }
  const pills = [{ cat: '__all__', label: 'All', count: allDocs.length }]
  for (const [cat, count] of Object.entries(cats)) {
    pills.push({ cat, label: shortLabel[cat] ?? cat, count })
  }
  return pills
})

const rosterPills = computed(() => {
  const roster = props.zones.roster ?? []
  const priority   = roster.filter(r => r.client_priority).length
  const standard   = roster.filter(r => !r.client_priority && r.status !== 'discharged').length
  const discharged = roster.filter(r => r.status === 'discharged').length
  const pills = [{ cat: '__all__', label: 'All', count: roster.length }]
  if (priority)   pills.push({ cat: 'priority',   label: 'Priority',   count: priority })
  if (standard)   pills.push({ cat: 'standard',   label: 'Standard',   count: standard })
  if (discharged) pills.push({ cat: 'discharged', label: 'Discharged', count: discharged })
  return pills
})

// ── Filtered docs ────────────────────────────────────────────
const filteredStandard = computed(() => {
  let items = [...(props.zones.standard ?? [])]
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    items = items.filter(d => d.title?.toLowerCase().includes(q))
  }
  if (activeCat.value !== '__all__') {
    items = items.filter(d => (d.category ?? 'Other') === activeCat.value)
  }
  return items
})

const filteredByCategory = computed(() => {
  const out = {}
  for (const doc of filteredStandard.value) {
    const c = doc.category || 'Other'
    if (!out[c]) out[c] = []
    out[c].push(doc)
  }
  return out
})

// ── Roster ───────────────────────────────────────────────────
const rosterSearch       = ref('')
const quarterlyReminders = ref(false)
const rosterPage         = ref(1)
const rosterPageSize     = 10

const activeClients     = computed(() => (props.zones.roster ?? []).filter(r => r.status !== 'discharged'))
const dischargedClients = computed(() => (props.zones.roster ?? []).filter(r => r.status === 'discharged'))

const filteredActiveClients = computed(() => {
  let items = activeClients.value
  if (rosterSearch.value.trim()) {
    const q = rosterSearch.value.toLowerCase()
    items = items.filter(r => (r.client_name ?? '').toLowerCase().includes(q))
  }
  if (activeCat.value === 'priority') return items.filter(r => r.client_priority)
  if (activeCat.value === 'standard') return items.filter(r => !r.client_priority)
  return items
})

const paginatedClients = computed(() => {
  const start = (rosterPage.value - 1) * rosterPageSize
  return filteredActiveClients.value.slice(start, start + rosterPageSize)
})

// ── Stat chip computeds ──────────────────────────────────────
const actionNeededCount = computed(() => {
  const today = Date.now()
  return [...(props.zones.standard ?? []), ...(props.zones.emergency ?? [])].filter(d => {
    if (!d.expires_at) return false
    const days = Math.floor((new Date(d.expires_at).getTime() - today) / 86400000)
    return days <= 60 && days >= -7
  }).length
})

const stewardsWithAccess = computed(() =>
  (props.stewards ?? []).filter(s => s.vault_access && s.vault_access !== 'none').length
)

const emergencyProgressPct = computed(() => {
  const count = props.zones.emergency?.length ?? 0
  return Math.min(100, Math.round((count / 6) * 100))
})

const needsAttention = computed(() => {
  const today = Date.now()
  return [...(props.zones.standard ?? []), ...(props.zones.emergency ?? [])]
    .filter(d => {
      if (!d.expires_at) return false
      const days = Math.floor((new Date(d.expires_at).getTime() - today) / 86400000)
      return days <= 60 && days >= -7
    })
    .map(d => {
      const days = Math.floor((new Date(d.expires_at).getTime() - today) / 86400000)
      return {
        item: d,
        urgent: days <= 7,
        when: days < 0
          ? `Expired ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'} ago`
          : `Expires in ${days} day${days === 1 ? '' : 's'}`,
      }
    })
})

// ── Pagination ───────────────────────────────────────────────
const credentialsPage = ref(1)

// ── Modals ───────────────────────────────────────────────────
const modals = reactive({
  upload: false, addCredential: false,
  addClient: false, editClient: false, docDetail: false,
  attest: false, permissions: false, addPermission: false, editPermission: false,
})

const activeDoc      = ref(null)
const activeClient   = ref(null)
const activeSteward  = ref(null)
const editClientMode = ref('view')

// ── Helpers ──────────────────────────────────────────────────
function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}
function fmtDateShort(iso) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
function daysUntil(iso) {
  if (!iso) return null
  return Math.floor((new Date(iso).getTime() - Date.now()) / 86400000)
}
function dateLine(doc) {
  const issued  = fmtDateShort(doc.issued_at)
  const expires = fmtDateShort(doc.expires_at)
  if (issued && expires) return `Signed ${issued} · Expires ${expires}`
  if (expires) return `Expires ${expires}`
  if (issued)  return `Signed ${issued}`
  return '—'
}
function statusBadge(status) {
  const map = {
    active:         { label: 'Active',         cls: 'badge-green' },
    renewing:       { label: 'Renewing',       cls: 'badge-orange' },
    renew_soon:     { label: 'Renew Soon',     cls: 'badge-orange' },
    expiring:       { label: 'Expiring',       cls: 'badge-orange' },
    expired:        { label: 'Expired',        cls: 'badge-red' },
    emergency_only: { label: 'Emergency Only', cls: 'badge-red' },
    vault_only:     { label: 'Vault Only',     cls: 'badge-red' },
    priority:       { label: 'Priority',       cls: 'badge-red' },
  }
  return map[status] ?? null
}
function categoryIcon(cat) {
  const map = {
    'Agreements & Contracts': 'file-text',
    'Licenses & Credentials': 'shield',
    'Insurance Policies':     'shield',
    'Clinical Documents':     'activity',
    'Financial Documents':    'briefcase',
  }
  return map[cat] ?? 'file-text'
}
function accessBadge(level) {
  return level === 'full'     ? { label: 'Full',    cls: 'badge-green' }
       : level === 'scoped'   ? { label: 'Limited', cls: 'badge-gold' }
       : level === 'metadata' ? { label: 'View',    cls: 'badge-gray' }
                              : { label: 'None',    cls: 'badge-gray' }
}
function accessDesc(level) {
  return level === 'full'     ? 'Full access · Sensitive Information included'
       : level === 'scoped'   ? 'Sensitive Information only · Released on critical incident'
       : level === 'none'     ? 'No vault access'
                              : 'View only · Standard documents'
}

// ── Doc detail ───────────────────────────────────────────────
function openDocDetail(doc) { activeDoc.value = doc; modals.docDetail = true }

function deleteItem(item) {
  if (!item) return
  confirmAction(`Delete "${item.title}"? This action cannot be undone.`, () => {
    router.delete(route('vault.destroy', { item: item.id }), {
      preserveScroll: true,
      onSuccess: () => { toast.success('Item deleted.'); modals.docDetail = false },
      onError:   () => toast.error('Could not delete item.'),
    })
  })
}

// ── Client actions ───────────────────────────────────────────
function viewClient(client) {
  activeClient.value = client
  editClientMode.value = 'view'
  modals.editClient = true
}
function editClient(client) {
  activeClient.value = client
  editClientMode.value = 'edit'
  const loc = (client.client_location ?? '').split(',')
  editClientForm.client_name     = client.client_name ?? ''
  editClientForm.client_city     = loc[0]?.trim() ?? ''
  editClientForm.client_state    = loc[1]?.trim() ?? ''
  editClientForm.client_phone    = client.client_phone ?? ''
  editClientForm.client_email    = client.client_email ?? ''
  editClientForm.client_service  = client.client_service ?? ''
  editClientForm.client_priority = !!client.client_priority
  editClientForm.client_notes    = client.client_notes ?? ''
  modals.editClient = true
}
function dischargeClient(client) {
  confirmAction(`Discharge ${client.client_name} from your active roster?`, () => {
    router.delete(route('vault.destroy', { item: client.id }), {
      preserveScroll: true,
      onSuccess: () => toast.info('Client moved to Discharged / Closed'),
      onError:   () => toast.error('Could not discharge client'),
    })
  })
}

// ── Upload modal ─────────────────────────────────────────────
const uploadForm  = reactive({ zone: 'standard', title: '', category: '', doc_type: '', issued_at: '', expires_at: '', description: '', is_sensitive: false })
const uploadFiles = ref([])
const uploadBusy  = ref(false)

const uploadRules = computed(() => ({
  title:    { required: helpers.withMessage('Document name is required.', required) },
  category: { required: helpers.withMessage('Category is required.', required) },
}))
const vUpload$ = useVuelidate(uploadRules, uploadForm, { $scope: false })

function fieldError(field) {
  if (vUpload$.value[field]?.$error) return vUpload$.value[field].$errors[0]?.$message
  return null
}
function openUploadModal(zone = 'standard') {
  uploadForm.is_sensitive = zone === 'emergency'
  uploadForm.zone = zone
  modals.upload = true
}
function closeUploadModal() {
  modals.upload = false
  Object.assign(uploadForm, { zone: 'standard', title: '', category: '', doc_type: '', issued_at: '', expires_at: '', description: '', is_sensitive: false })
  uploadFiles.value = []
  vUpload$.value.$reset()
}
async function submitUpload() {
  const valid = await vUpload$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  uploadBusy.value = true
  const data = new FormData()
  data.append('zone', uploadForm.is_sensitive ? 'emergency' : 'standard')
  data.append('title', uploadForm.title)
  if (uploadForm.description) data.append('description', uploadForm.description)
  if (uploadFiles.value.length) data.append('file', uploadFiles.value[0])
  router.post(route('vault.upload'), data, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { toast.success('Document uploaded.'); closeUploadModal() },
    onError:   () => toast.error('Upload failed. Please try again.'),
    onFinish:  () => { uploadBusy.value = false },
  })
}

// ── Credential modal ─────────────────────────────────────────
const credForm    = reactive({ title: '', category: '', credential_username: '', credential_password: '', credential_url: '', description: '' })
const credBusy    = ref(false)
const showCredPass = ref(false)

const credRules = computed(() => ({
  title:               { required: helpers.withMessage('System name is required.', required) },
  category:            { required: helpers.withMessage('Category is required.', required) },
  credential_username: { required: helpers.withMessage('Username is required.', required) },
  credential_password: { required: helpers.withMessage('Password is required.', required) },
}))
const vCred$ = useVuelidate(credRules, credForm, { $scope: false })

function fieldErrorCred(field) {
  if (vCred$.value[field]?.$error) return vCred$.value[field].$errors[0]?.$message
  return null
}
function closeCredentialModal() {
  modals.addCredential = false
  Object.assign(credForm, { title: '', category: '', credential_username: '', credential_password: '', credential_url: '', description: '' })
  showCredPass.value = false
  vCred$.value.$reset()
}
async function submitCredential() {
  const valid = await vCred$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  credBusy.value = true
  router.post(route('vault.upload'), {
    zone: 'credentials',
    title: credForm.title,
    category: credForm.category,
    credential_username: credForm.credential_username,
    credential_password: credForm.credential_password,
    credential_url: credForm.credential_url,
    description: credForm.description,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Credential saved to vault.'); closeCredentialModal() },
    onError:   () => toast.error('Could not save credential.'),
    onFinish:  () => { credBusy.value = false },
  })
}

// ── Add client ───────────────────────────────────────────────
const clientForm = reactive({ client_name: '', client_city: '', client_state: '', client_phone: '', client_email: '', client_service: '', client_priority: false, client_notes: '' })
const clientBusy = ref(false)

const clientRules = computed(() => ({
  client_name:    { required: helpers.withMessage('Client name is required.', required) },
  client_city:    { required: helpers.withMessage('City is required.', required) },
  client_state:   { required: helpers.withMessage('State is required.', required) },
  client_service: { required: helpers.withMessage('Service type is required.', required) },
}))
const vClient$ = useVuelidate(clientRules, clientForm, { $scope: false })

function fieldErrorClient(field) {
  if (vClient$.value[field]?.$error) return vClient$.value[field].$errors[0]?.$message
  return null
}
function closeClientModal() {
  modals.addClient = false
  Object.assign(clientForm, { client_name: '', client_city: '', client_state: '', client_phone: '', client_email: '', client_service: '', client_priority: false, client_notes: '' })
  vClient$.value.$reset()
}
async function submitClient() {
  const valid = await vClient$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  clientBusy.value = true
  router.post(route('vault.upload'), {
    zone: 'roster',
    title: clientForm.client_name,
    client_name: clientForm.client_name,
    client_location: `${clientForm.client_city}, ${clientForm.client_state}`,
    client_phone: clientForm.client_phone,
    client_service: clientForm.client_service,
    client_priority: clientForm.client_priority ? 1 : 0,
    description: clientForm.client_notes,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Client added to roster.'); closeClientModal() },
    onError:   () => toast.error('Could not save client.'),
    onFinish:  () => { clientBusy.value = false },
  })
}

// ── Edit client ──────────────────────────────────────────────
const editClientForm = reactive({ client_name: '', client_city: '', client_state: '', client_phone: '', client_email: '', client_service: '', client_priority: false, client_notes: '' })
const editClientBusy = ref(false)

const editClientRules = computed(() => ({
  client_name:    { required: helpers.withMessage('Client name is required.', required) },
  client_city:    { required: helpers.withMessage('City is required.', required) },
  client_state:   { required: helpers.withMessage('State is required.', required) },
  client_service: { required: helpers.withMessage('Service type is required.', required) },
}))
const vEditClient$ = useVuelidate(editClientRules, editClientForm, { $scope: false })

function fieldErrorEditClient(field) {
  if (vEditClient$.value[field]?.$error) return vEditClient$.value[field].$errors[0]?.$message
  return null
}
function closeEditClientModal() {
  modals.editClient = false
  activeClient.value = null
  editClientMode.value = 'view'
  vEditClient$.value.$reset()
}
async function submitEditClient() {
  const valid = await vEditClient$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  if (!activeClient.value) return
  editClientBusy.value = true
  router.put(route('vault.update', { item: activeClient.value.id }), {
    client_name:     editClientForm.client_name,
    client_location: `${editClientForm.client_city}, ${editClientForm.client_state}`,
    client_phone:    editClientForm.client_phone,
    client_service:  editClientForm.client_service,
    client_priority: editClientForm.client_priority ? 1 : 0,
    description:     editClientForm.client_notes,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Client updated.'); closeEditClientModal() },
    onError:   () => toast.error('Could not save changes.'),
    onFinish:  () => { editClientBusy.value = false },
  })
}

// ── Attest ───────────────────────────────────────────────────
const attestForm = reactive({ note: props.attestNote ?? '' })
const attestBusy = ref(false)

function closeAttestModal() { modals.attest = false; attestForm.note = props.attestNote ?? '' }

async function submitAttest() {
  attestBusy.value = true
  router.post(route('vault.attest'), { note: attestForm.note }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Vault attested — your Stewards have been notified.'); modals.attest = false },
    onError:   () => toast.error('Could not record attestation.'),
    onFinish:  () => { attestBusy.value = false },
  })
}
async function clearAttest() {
  attestBusy.value = true
  router.post(route('vault.attest'), { note: null, clear: true }, {
    preserveScroll: true,
    onSuccess: () => { toast.info('Attestation cleared.'); modals.attest = false },
    onError:   () => toast.error('Could not clear attestation.'),
    onFinish:  () => { attestBusy.value = false },
  })
}

// ── Permissions ──────────────────────────────────────────────
const permForm = reactive({ steward_id: '', level: '', expires_at: '', notify: true })
const permBusy = ref(false)

const permRules = computed(() => ({
  steward_id: { required: helpers.withMessage('Please select a person.', required) },
  level:      { required: helpers.withMessage('Please select an access level.', required) },
}))
const vPerm$ = useVuelidate(permRules, permForm, { $scope: false })

function fieldErrorPerm(field) {
  if (vPerm$.value[field]?.$error) return vPerm$.value[field].$errors[0]?.$message
  return null
}
function closeAddPermModal() {
  modals.addPermission = false
  Object.assign(permForm, { steward_id: '', level: '', expires_at: '', notify: true })
  vPerm$.value.$reset()
}
async function submitPermission() {
  const valid = await vPerm$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  permBusy.value = true
  router.post(route('vault.permissions'), {
    steward_ids: [permForm.steward_id],
    vault_access: permForm.level,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Access granted.'); closeAddPermModal() },
    onError:   () => toast.error('Could not grant access.'),
    onFinish:  () => { permBusy.value = false },
  })
}

const editPermForm = reactive({ level: 'none', expires_at: '', notify: false })
const editPermBusy = ref(false)

function openEditPermission(steward) {
  activeSteward.value = steward
  editPermForm.level      = steward.vault_access ?? 'none'
  editPermForm.expires_at = ''
  editPermForm.notify     = false
  modals.editPermission = true
}
function closeEditPermModal() { modals.editPermission = false; activeSteward.value = null }

async function submitEditPerm() {
  if (!activeSteward.value) return
  editPermBusy.value = true
  router.post(route('vault.permissions'), {
    steward_ids: [activeSteward.value.id],
    vault_access: editPermForm.level,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Permissions updated.'); closeEditPermModal() },
    onError:   () => toast.error('Could not update permissions.'),
    onFinish:  () => { editPermBusy.value = false },
  })
}
function revokeAccess() {
  if (!activeSteward.value) return
  editPermBusy.value = true
  router.post(route('vault.permissions'), {
    steward_ids: [activeSteward.value.id],
    vault_access: 'none',
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.warning('Access revoked.'); closeEditPermModal() },
    onError:   () => toast.error('Could not revoke access.'),
    onFinish:  () => { editPermBusy.value = false },
  })
}
</script>

<style scoped>
/* ── Layout ─────────────────────────────────────────────── */
.vault-tab-content { padding-bottom: 40px; }

/* ── Banners & alerts ───────────────────────────────────── */
.vault-attest-banner {
  margin-bottom: 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.vault-attest-meta { font-size: 12px; color: var(--text-2); margin-top: 2px; }
.vault-attest-note { font-size: 12px; color: var(--text-2); margin-top: 4px; font-style: italic; }

.vault-gold-alert {
  background: var(--badge-bg-gold);
  border: 1px solid var(--gold);
  border-radius: var(--radius);
  padding: 14px 16px;
  box-shadow: var(--shadow-sm);
  margin-bottom: 20px;
}
.vault-gold-alert-inner { display: flex; align-items: flex-start; gap: 12px; }
.vault-gold-icon  { color: var(--gold-dark); flex-shrink: 0; margin-top: 2px; }
.vault-gold-body  { flex: 1; }
.vault-gold-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.vault-gold-desc  { font-size: 12px; color: var(--text-2); line-height: 1.5; }
.vault-gold-cta   { flex-shrink: 0; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; }

/* ── Attention card ─────────────────────────────────────── */
.vault-attention-card  { margin-bottom: 20px; }
.vault-list-group-flush { border-radius: 0; }
.vault-attention-item   { gap: 10px; }
.vault-attention-icon-wrap { display: flex; align-items: center; flex-shrink: 0; }
.vault-attention-body   { flex: 1; min-width: 0; }
.vault-attention-name   { font-size: 13px; font-weight: 700; color: var(--text); }
.vault-attention-when   { font-size: 11px; font-weight: 700; margin-top: 2px; }
.vault-section-title-row { display: flex; align-items: center; gap: 8px; }

/* ── Toolbar ────────────────────────────────────────────── */
.vault-toolbar  { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.vault-search   { flex: 1; min-width: 220px; }
.vault-reminder-check { white-space: nowrap; font-size: 12px; flex-shrink: 0; }
.vault-roster-add     { margin-left: auto; flex-shrink: 0; }
.vault-roster-toolbar { margin-bottom: 18px; }
.vault-cred-info      { margin-top: 20px; }

/* ── Category sections ──────────────────────────────────── */
.vault-category-section { }
.vault-category-header  { display: flex; align-items: center; gap: 12px; margin: 24px 0 16px; }
.vault-category-icon    { width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.vault-category-name    { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.vault-category-count   { font-size: 11px; font-weight: 700; background: var(--surface-3); color: var(--text-3); padding: 3px 10px; border-radius: var(--radius-full); }
.vault-category-line    { flex: 1; height: 1px; background: var(--border); }
.vault-section-icon     { width: 28px; height: 28px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* ── Doc grid + cards ───────────────────────────────────── */
.doc-grid        { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
.doc-card        { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; cursor: pointer; transition: all var(--transition); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px; }
.doc-card:hover  { border-color: var(--soft-gold); box-shadow: var(--shadow); transform: translateY(-1px); }
.doc-card-top    { display: flex; align-items: flex-start; gap: 12px; }
.doc-file-icon   { width: 40px; height: 40px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--icon-bg-gold); color: var(--gold-dark); }
.doc-card-meta   { flex: 1; min-width: 0; }
.doc-card-name   { font-family: var(--font-serif); font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; line-height: 1.3; }
.doc-card-sub    { font-size: 11px; color: var(--text-3); }
.doc-card-tags   { display: flex; flex-wrap: wrap; gap: 6px; }
.doc-card-footer { padding-top: 12px; border-top: 1px solid var(--border); }
.doc-card-date   { font-size: 11px; color: var(--text-4); font-weight: 600; }
.expiry-warning         { background: var(--orange-light); border-left: 3px solid var(--orange-dark); padding: 4px 8px; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; font-size: 10px; font-weight: 700; color: var(--orange-dark); margin-top: 5px; display: inline-flex; align-items: center; gap: 4px; }
.expiry-warning--expired { background: var(--red-light); border-left-color: var(--red-dark); color: var(--red-dark); }

/* ── Credential cards ───────────────────────────────────── */
.cred-card         { border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 18px; background: var(--surface); margin-bottom: 12px; display: flex; align-items: flex-start; gap: 14px; transition: border-color var(--transition), box-shadow var(--transition); box-shadow: var(--shadow-sm); }
.cred-card:hover   { border-color: var(--soft-gold); box-shadow: var(--shadow); }
.cred-icon         { width: 40px; height: 40px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cred-body         { flex: 1; min-width: 0; }
.cred-name         { font-family: var(--font-serif); font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.cred-username     { font-size: 12px; color: var(--text-3); margin-bottom: 2px; font-family: var(--font-mono); font-weight: 600; }
.cred-url          { font-size: 12px; color: var(--gold-dark); text-decoration: none; font-weight: 600; }
.cred-url:hover    { text-decoration: underline; }
.cred-note         { font-size: 12px; color: var(--text-4); margin-top: 6px; line-height: 1.5; }
.cred-password     { font-family: var(--font-mono); font-size: 12px; color: var(--text-3); letter-spacing: 2px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 10px; display: inline-flex; align-items: center; gap: 8px; margin-top: 8px; }
.cred-password-label { color: var(--text-4); font-size: 11px; letter-spacing: 0; }
.cred-actions      { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }

/* ── Client roster table ────────────────────────────────── */
.cr-table-wrap         { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; width: 100%; background: var(--surface); }
.cr-table-wrap--dimmed { opacity: 0.75; }
.cr-table              { width: 100%; border-collapse: collapse; table-layout: fixed; }
.cr-table th           { padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-3); background: var(--surface-2); text-align: left; }
.cr-table td           { padding: 12px 14px; font-size: 13px; color: var(--text-2); border-top: 1px solid var(--border); vertical-align: middle; }
.cr-table td:first-child { font-weight: 700; color: var(--text); }
.cr-table tr:hover td  { background: var(--surface-2); }
.cr-col-name    { width: 30%; }
.cr-col-service { width: 28%; }
.cr-col-pri     { width: 20%; }
.cr-col-actions { width: 22%; text-align: right; }
.cr-col-right   { text-align: right; }
.cr-discharged-name    { width: 35%; }
.cr-discharged-service { width: 25%; }
.cr-discharged-date    { width: 25%; }
.cr-discharged-status  { width: 15%; }
.cr-discharged-name-cell { text-decoration: line-through; color: var(--text-3); }
.cr-sub        { font-size: 11px; font-weight: 600; color: var(--text-4); margin-top: 2px; }
.cr-muted      { color: var(--text-4); }
.cr-action-group { display: flex; gap: 6px; justify-content: flex-end; }
.vault-discharged-section { margin-top: 28px; }

/* ── Modal internals ────────────────────────────────────── */
.vault-cred-notice   { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: var(--text-2); }
.vault-client-notice { font-size: 13px; color: var(--text-3); margin-bottom: 16px; line-height: 1.6; background: var(--surface-2); padding: 10px 14px; border-radius: var(--radius); border-left: 3px solid var(--gold-dark); }
.vault-check-label   { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--text-2); }
.vault-access-list   { margin-bottom: 8px; }
.vault-no-stewards   { justify-content: center; color: var(--text-3); font-weight: 600; font-size: 12px; }
.vault-steward-info  { flex: 1; min-width: 0; }
.vault-steward-name  { font-size: 13px; font-weight: 700; color: var(--text); }
.vault-steward-role  { font-size: 11px; color: var(--text-4); font-weight: 600; }
.vault-access-select { width: auto; }
.vault-inline-link   { color: var(--gold-dark); font-weight: 700; text-decoration: none; }
.vault-inline-link:hover { text-decoration: underline; }
.vault-revoke-row    { margin-top: 16px; }
.vault-revoke-btn    { color: var(--red-dark); border-color: var(--red-light); }

/* Detail modal */
.vault-doc-detail-head   { display: flex; align-items: center; gap: 18px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
.vault-doc-detail-info   { flex: 1; min-width: 0; }
.vault-doc-detail-title  { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.vault-doc-detail-sub    { font-size: 12px; color: var(--text-3); font-weight: 600; }
.vault-doc-detail-tags   { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
.vault-doc-detail-download { display: flex; flex-direction: column; gap: 8px; }
.doc-detail-icon           { width: 56px; height: 56px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.doc-detail-icon--standard { background: var(--icon-bg-gold); color: var(--gold-dark); }
.doc-detail-icon--sensitive { background: var(--red-light); color: var(--red-dark); }
.vault-detail-row  { gap: 10px; }
.vault-detail-key  { font-size: 12px; color: var(--text-3); min-width: 140px; font-weight: 600; }
.vault-detail-value { font-size: 13px; font-weight: 700; color: var(--text); }
.vault-detail-mono  { font-family: var(--font-mono), monospace; font-size: 13px; font-weight: 600; color: var(--text-2); }

.vault-footer-actions { display: flex; gap: 8px; margin-left: auto; }
.vault-delete-btn     { color: var(--red-dark); }

/* Client detail grid */
.vault-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
.vault-detail-full { grid-column: 1 / -1; }
.vault-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); font-weight: 700; margin-bottom: 4px; }
.vault-detail-val   { font-size: 14px; font-weight: 700; color: var(--text); }
.vault-client-notes { font-size: 13px; color: var(--text-2); line-height: 1.6; background: var(--surface-2); padding: 10px 14px; border-radius: var(--radius); border-left: 3px solid var(--gold-dark); }

/* Permissions */
.vault-access-levels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
.vault-access-card   { border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; background: var(--surface); }
.vault-access-card-label { font-size: 10px; font-weight: 700; color: var(--text-4); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }
.vault-access-card-desc  { font-size: 12px; color: var(--text-2); line-height: 1.6; font-weight: 600; }

/* ── Security banner ────────────────────────────────────── */
.vault-security-banner { margin-bottom: 20px; }
.vault-compliance-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }

/* ── Storage bar ────────────────────────────────────────── */
.vault-storage-bar {
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
  padding: 16px 18px; margin-bottom: 24px;
  display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
}
.vault-storage-label   { font-size: 13px; font-weight: 700; color: var(--text-2); white-space: nowrap; }
.vault-storage-progress { flex: 1; min-width: 160px; }
.vault-storage-used    { font-size: 11px; color: var(--text-4); font-weight: 600; }
.vault-storage-upgrade {
  font-size: 11px; color: var(--gold-dark); font-weight: 700;
  text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
}
.vault-storage-upgrade:hover { text-decoration: underline; }

/* ── Restricted access banner ───────────────────────────── */
.vault-restricted-banner  { margin-bottom: 20px; box-shadow: var(--shadow-sm); }
.vault-restricted-triggers {
  display: flex; align-items: flex-start; gap: 8px; margin-top: 10px;
  padding: 10px 12px; background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-sm); font-size: 12px; color: var(--text-3);
}
.vault-restricted-triggers strong { color: var(--text-2); }
.vault-restricted-actions { margin-top: 10px; }

/* ── Sensitive info tab ─────────────────────────────────── */
.vault-emergency-completeness   { margin-bottom: 16px; }
.vault-emergency-progress-desc  { font-size: 13px; color: var(--text-2); margin-bottom: 12px; line-height: 1.5; font-weight: 600; }
.vault-emergency-progress-bar   { margin-bottom: 10px; }
.vault-emergency-pct            { font-size: 11px; color: var(--text-3); font-weight: 600; }
.vault-section-icon--red        { background: var(--red-light) !important; color: var(--red-dark) !important; }
.doc-file-icon--sensitive       { background: var(--red-light); color: var(--red-dark); }

/* ── Responsive ─────────────────────────────────────────── */
@media (max-width: 600px) {
  .doc-grid             { grid-template-columns: 1fr; }
  .vault-access-levels  { grid-template-columns: 1fr; }
  .vault-detail-grid    { grid-template-columns: 1fr; }
  .vault-detail-full    { grid-column: 1; }
}
</style>
