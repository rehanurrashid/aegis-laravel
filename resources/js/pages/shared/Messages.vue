<!--
  pages/shared/Messages.vue — Messages page used by all five portals.
  Mirrors legacy PHP messages template: two-column layout (contacts list with
  search + bucket filter pills · chat area with daily-grouped bubbles + compose),
  plus a contact details drawer, new-message modal with recipient picker, and
  send-on-Enter compose behavior.
-->
<template>
  <AppLayout>
    <!-- ── Layout shell ──────────────────────────────── -->
    <div class="msg-layout">

      <!-- LEFT — Contacts pane -->
      <div class="msg-pane is-contacts">
        <div class="msg-contacts-head">
          <div class="msg-contacts-icon-row">
            <button
              type="button"
              class="btn-icon"
              :class="{ 'is-active-icon': searchContactsOpen }"
              data-tooltip="Search messages"
              @click="searchContactsOpen = !searchContactsOpen"
            >
              <AegisIcon name="search" :size="16" />
            </button>
            <button
              type="button"
              class="btn-icon"
              data-tooltip="New message"
              @click="openCompose"
            >
              <AegisIcon name="plus" :size="16" />
            </button>
            <button
              type="button"
              class="btn-icon"
              :data-tooltip="`Status: ${availabilityOptions.find(o => o.value === currentStatus)?.label ?? 'Available'}`"
              @click="modals.availability = true"
            >
              <span class="avail-status-dot" :class="`avail-status-dot--${currentStatus}`"></span>
            </button>
            <a :href="activityUrl" class="btn-icon" data-tooltip="Message activity log">
              <AegisIcon name="activity" :size="16" />
            </a>
          </div>
        </div>

        <!-- Collapsible contacts search bar -->
        <div v-if="searchContactsOpen" class="msg-contacts-search-bar">
          <div class="msg-search-input-wrap">
            <AegisIcon name="search" :size="18" />
            <input
              ref="searchInputRef"
              v-model="filter"
              type="text"
              class="msg-search-input"
              placeholder="Search messages…"
              @keydown.esc="filter = ''; searchContactsOpen = false"
            />
            <button
              v-if="filter"
              type="button"
              class="msg-search-clear"
              data-tooltip="Clear"
              @click="filter = ''"
            >
              <AegisIcon name="x" :size="13" />
            </button>
          </div>
        </div>

        <!-- Bucket filter dropdown -->
        <div class="msg-filter-row">
          <div class="msg-filter-select-wrap">
            <AegisIcon name="filter" :size="18" class="msg-filter-icon" />
            <select v-model="activeBucket" class="msg-filter-select">
              <option v-for="b in buckets" :key="b.key" :value="b.key">
                {{ b.label }} ({{ bucketCounts[b.key] ?? 0 }})
              </option>
            </select>
            <AegisIcon name="chevron-down" :size="18" class="msg-filter-chevron" />
          </div>
        </div>

        <!-- Thread list -->
        <div class="msg-contact-list">
          <div v-if="!filteredThreads.length" class="msg-list-empty">
            <template v-if="filter.trim()">
              No results for "<strong>{{ filter.trim() }}</strong>"
            </template>
            <template v-else>
              No conversations yet. Start one with the New Message button above.
            </template>
          </div>
          <div
            v-for="t in filteredThreads"
            :key="t.id"
            class="msg-contact-item"
            :class="{ 'is-active': t.id === activeThread?.id }"
            @click="selectThread(t)"
          >
            <div class="msg-avatar-wrap">
              <div
                class="msg-avatar"
                :class="{ 'is-gold': t.is_continuity_contact }"
                :style="t.counterpart?.avatar_url ? { backgroundImage: `url(${t.counterpart.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}"
              ><template v-if="!t.counterpart?.avatar_url">{{ t.counterpart?.avatar_initials || '·' }}</template></div>
              <span class="msg-presence-dot msg-presence-dot--available"></span>
            </div>
            <div class="msg-contact-info">
              <div class="msg-contact-name-row">
                <span v-if="t.last_message_unread" class="msg-unread-indicator"></span>
                <div class="msg-contact-name" :class="{ 'is-unread': t.last_message_unread }">{{ t.counterpart?.display_name }}</div>
              </div>
              <div class="msg-contact-preview" :class="{ 'is-unread': t.last_message_unread }">
                {{ t.last_message_snippet }}
              </div>
            </div>
            <div class="msg-contact-meta">
              <div class="msg-contact-time">{{ activity.timeAgo(t.last_message_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- CENTER — Chat pane -->
      <div class="msg-pane is-chat">
        <!-- Empty state -->
        <div v-if="!activeThread" class="msg-empty-state">
          <div class="msg-empty-icon"><AegisIcon name="message" :size="24" /></div>
          <div class="msg-empty-title">No conversation selected</div>
          <div class="msg-empty-text">Pick a contact on the left, or start a new conversation.</div>
          <button
            type="button"
            class="btn btn-primary"
            style="margin-top: 0.875rem;"
            @click="openCompose"
          >
            <AegisIcon name="plus" :size="18" />
            Start a Conversation
          </button>
        </div>

        <template v-else>
          <!-- Chat header -->
          <div class="msg-chat-head">
            <div class="msg-avatar-wrap">
              <div
                class="msg-avatar is-sm"
                :class="{ 'is-gold': activeThread.is_continuity_contact }"
                :style="activeThread.counterpart?.avatar_url ? { backgroundImage: `url(${activeThread.counterpart.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}"
              ><template v-if="!activeThread.counterpart?.avatar_url">{{ activeThread.counterpart?.avatar_initials || '·' }}</template></div>
              <span class="msg-presence-dot msg-presence-dot--available"></span>
            </div>
            <div class="msg-chat-head-info">
              <component
                :is="activeThread.counterpart?.slug ? 'a' : 'div'"
                class="msg-chat-head-name"
                :class="{ 'is-link-name': activeThread.counterpart?.slug }"
                :href="activeThread.counterpart?.slug ? profileUrl(activeThread.counterpart) : undefined"
              >{{ activeThread.counterpart?.display_name }}</component>
              <div class="msg-chat-head-sub">
                {{ activeThread.counterpart?.role_label }}
                <template v-if="activeThread.is_continuity_contact">
                  <span class="msg-sub-dot">·</span>
                  <span class="msg-continuity-flag">Continuity Contact</span>
                </template>
              </div>
            </div>
            <div class="msg-chat-head-actions">
              <button
                type="button"
                class="btn-icon"
                :class="{ 'is-active-icon': searchOpen }"
                data-tooltip="Search in chat"
                @click="searchOpen = !searchOpen"
              >
                <AegisIcon name="search" :size="16" />
              </button>
              <button
                type="button"
                class="btn-icon"
                :class="{ 'is-active-icon': showInfo }"
                data-tooltip="Conversation info"
                @click="showInfo = !showInfo"
              >
                <AegisIcon name="more" :size="16" />
              </button>
            </div>
          </div>

          <!-- Encryption note -->
          <div class="msg-system-note">
            <AegisIcon name="lock" :size="18" />
            End-to-end encrypted
          </div>

          <!-- Inline chat search -->
          <div class="msg-chat-search" :class="{ 'is-open': searchOpen }">
            <div class="msg-search-input-wrap" style="flex:1">
              <AegisIcon name="search" :size="18" />
              <input
                ref="chatSearchInputRef"
                v-model="searchQuery"
                type="text"
                class="msg-search-input"
                placeholder="Search in this conversation…"
                @keydown.esc="searchOpen = false; searchQuery = ''"
                @keydown.enter="stepSearchMatch(1)"
              />
            </div>
            <template v-if="searchQuery">
              <span class="msg-chat-search-counter">
                {{ searchMatches.length ? `${searchMatchIdx + 1} / ${searchMatches.length}` : '0 results' }}
              </span>
              <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Previous" @click="stepSearchMatch(-1)">
                <AegisIcon name="chevron-up" :size="14" />
              </button>
              <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Next" @click="stepSearchMatch(1)">
                <AegisIcon name="chevron-down" :size="14" />
              </button>
            </template>
            <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Close" @click="searchOpen = false; searchQuery = ''">
              <AegisIcon name="x" :size="14" />
            </button>
          </div>

          <!-- Messages stream -->
          <div ref="msgStream" class="msg-stream">
            <template v-for="(m, i) in activeMessages" :key="m.id">
              <div
                v-if="shouldShowDayChip(m, i)"
                class="msg-day-chip"
              >{{ formatDay(m.sent_at) }}</div>
              <div
                :id="`msg-${m.id}`"
                class="msg-row"
                :class="[
                  m.is_sent ? 'is-sent' : 'is-received',
                  searchQuery && m.body?.toLowerCase().includes(searchQuery.toLowerCase().trim()) ? 'is-search-match' : '',
                ]"
              >
                <div
                  class="msg-avatar is-xs"
                  :class="{
                    'is-gold': m.is_sent || activeThread.is_continuity_contact,
                  }"
                  :style="m.is_sent && currentUserAvatarUrl ? { backgroundImage: `url(${currentUserAvatarUrl})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}"
                ><template v-if="!(m.is_sent && currentUserAvatarUrl)">{{ m.is_sent ? currentUserInitials : (activeThread.counterpart?.avatar_initials || '·') }}</template></div>
                <div class="msg-bubble-wrap">
                  <div class="msg-bubble">
                    <span v-if="m.body" v-html="highlightMatch(m.body, searchQuery)"></span>
                    <div v-if="m.attachments && m.attachments.length" class="msg-bubble-attachments">
                      <a
                        v-for="att in m.attachments"
                        :key="att.url"
                        :href="att.url"
                        :download="att.name"
                        target="_blank"
                        rel="noopener"
                        class="msg-bubble-attach-row"
                      >
                        <span class="msg-bubble-attach-icon"><AegisIcon :name="attachIcon(att.mime)" :size="13" /></span>
                        <div class="msg-bubble-attach-body">
                          <div class="msg-bubble-attach-name">{{ att.name }}</div>
                          <div v-if="att.size" class="msg-bubble-attach-size">{{ att.size }}</div>
                        </div>
                        <AegisIcon name="download" :size="12" />
                      </a>
                    </div>
                  </div>
                  <div class="msg-bubble-time">
                    {{ formatTime(m.sent_at) }}
                    <AegisIcon v-if="m.is_sent" name="check" :size="10" />
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Compose -->
          <div class="msg-compose">
            <div class="msg-compose-row">
              <div class="msg-compose-tools">
                <button type="button" class="btn-icon" data-tooltip="Attach file" @click="$refs.fileInput.click()">
                  <AegisIcon name="upload" :size="16" />
                </button>
                <input
                  ref="fileInput"
                  type="file"
                  class="sr-only"
                  accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                  @change="onFileSelected"
                />
                <button type="button" class="btn-icon" data-tooltip="Use template" @click="modals.templatePicker = true">
                  <AegisIcon name="file-text" :size="16" />
                </button>
              </div>
              <div v-if="pendingFile" class="msg-attach-chip">
                <AegisIcon name="file-text" :size="13" />
                <span class="msg-attach-chip-name">{{ pendingFile.name }}</span>
                <button type="button" class="msg-attach-chip-remove" data-tooltip="Remove" @click="clearPendingFile">
                  <AegisIcon name="x" :size="11" />
                </button>
              </div>
              <textarea
                v-model="replyForm.body"
                class="msg-compose-input"
                :placeholder="pendingFile ? 'Add a message (optional)…' : 'Type your message…'"
                rows="1"
                @keydown="onComposeKey"
                @input="autoResize"
              ></textarea>
              <button
                type="button"
                class="btn-icon btn-icon-primary"
                data-tooltip="Send"
                :disabled="(!replyForm.body.trim() && !pendingFile) || replyForm.processing"
                @click="sendReply"
              >
                <AegisIcon name="send" :size="16" />
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- RIGHT — Conversation info side panel -->
      <transition name="slide">
        <div
          v-if="activeThread && showInfo"
          class="msg-info-panel"
        >
          <!-- Panel header -->
          <div class="msg-info-head">
            <div class="msg-info-title">Conversation Info</div>
            <button type="button" class="btn-icon" data-tooltip="Close" @click="showInfo = false">
              <AegisIcon name="x" :size="14" />
            </button>
          </div>

          <div class="msg-info-body">

            <!-- ▌ Identity header -->
            <div class="mip-identity">
              <div class="msg-avatar mip-avatar" :class="{ 'is-gold': activeThread.is_continuity_contact }"
                   :style="activeThread.counterpart?.avatar_url ? { backgroundImage: `url(${activeThread.counterpart.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
                <template v-if="!activeThread.counterpart?.avatar_url">{{ activeThread.counterpart?.avatar_initials || '·' }}</template>
              </div>
              <component
                :is="activeThread.counterpart?.slug ? 'a' : 'div'"
                class="mip-name"
                :href="activeThread.counterpart?.slug ? profileUrl(activeThread.counterpart) : undefined"
              >{{ activeThread.counterpart?.display_name }}</component>
              <div class="mip-role">
                {{ activeThread.counterpart?.role_label }}
                <template v-if="activeThread.is_continuity_contact">
                  · <span style="color:var(--gold-dark);font-weight:700">Continuity Contact</span>
                </template>
              </div>
              <div v-if="activeThread.counterpart?.organization" class="mip-org">
                {{ activeThread.counterpart.organization }}
              </div>
              <div class="mip-ctas">
                <a
                  v-if="activeThread.counterpart?.slug"
                  :href="profileUrl(activeThread.counterpart)"
                  class="btn btn-outline btn-sm"
                >
                  <AegisIcon name="user" :size="12" /> View Profile
                </a>
              </div>
            </div>

            <!-- ▌ Contact info -->
            <div class="msg-info-section" v-if="activeThread.counterpart?.email || activeThread.counterpart?.phone || activeThread.counterpart?.location">
              <div class="msg-info-section-label">Contact</div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.email">
                <span class="msg-info-icon"><AegisIcon name="mail" :size="14" /></span>
                <span class="msg-info-label">Email</span>
                <a class="msg-info-value is-link" :href="`mailto:${activeThread.counterpart.email}`">{{ activeThread.counterpart.email }}</a>
              </div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.phone">
                <span class="msg-info-icon"><AegisIcon name="phone" :size="14" /></span>
                <span class="msg-info-label">Phone</span>
                <span class="msg-info-value">{{ activeThread.counterpart.phone }}</span>
              </div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.location">
                <span class="msg-info-icon"><AegisIcon name="map-pin" :size="14" /></span>
                <span class="msg-info-label">Based</span>
                <span class="msg-info-value">{{ activeThread.counterpart.location }}</span>
              </div>
              <div class="msg-info-row">
                <span class="msg-info-icon"><AegisIcon name="clock" :size="14" /></span>
                <span class="msg-info-label">Latest</span>
                <span class="msg-info-value">{{ activity.timeAgo(activeThread.last_message_at) }} ago</span>
              </div>
            </div>

            <!-- ▌ Media / Files / Links -->
            <div class="msg-info-section">
              <div class="msg-info-section-label">Media, Files &amp; Links</div>

              <!-- Tab strip -->
              <div class="mip-attach-tabs">
                <button
                  v-for="tab in attachTabs"
                  :key="tab.key"
                  type="button"
                  class="tab-pill"
                  :class="{ active: infoAttachTab === tab.key }"
                  @click="infoAttachTab = tab.key"
                >
                  {{ tab.label }}
                  <span class="badge-pill">{{ tab.count }}</span>
                </button>
              </div>

              <!-- Media panel -->
              <div v-if="infoAttachTab === 'media'">
                <div v-if="!attachMedia.length" class="mip-attach-empty">No media shared yet</div>
                <template v-else>
                  <div class="mip-media-grid">
                    <div
                      v-for="(item, i) in attachMedia.slice(0, 6)"
                      :key="i"
                      class="mip-media-thumb"
                      :data-tooltip="item.name"
                      @click="openImageViewer(item)"
                    >
                      <img
                        v-if="item.url && /\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i.test(item.name || '')"
                        :src="item.url"
                        :alt="item.name"
                        class="mip-media-img"
                      />
                      <AegisIcon v-else name="camera" :size="18" />
                    </div>
                  </div>
                  <button type="button" class="mip-attach-viewall" @click="modals.allMedia = true">
                    <AegisIcon name="grid" :size="12" /> View All Media
                  </button>
                </template>
              </div>

              <!-- Files panel -->
              <div v-else-if="infoAttachTab === 'files'">
                <div v-if="!attachFiles.length" class="mip-attach-empty">No files shared yet</div>
                <template v-else>
                  <a
                    v-for="(f, i) in attachFiles.slice(0, 4)"
                    :key="i"
                    :href="f.url"
                    :download="f.name"
                    target="_blank"
                    rel="noopener"
                    class="mip-file-row"
                  >
                    <span class="mip-file-icon"><AegisIcon name="file-text" :size="14" /></span>
                    <div class="mip-file-body">
                      <div class="mip-file-name">{{ f.name }}</div>
                      <div class="mip-file-size">{{ f.size }}</div>
                    </div>
                    <span class="mip-file-dl"><AegisIcon name="download" :size="14" /></span>
                  </a>
                  <button type="button" class="mip-attach-viewall" @click="modals.allMedia = true">
                    <AegisIcon name="folder" :size="12" /> View All Files
                  </button>
                </template>
              </div>

              <!-- Links panel -->
              <div v-else-if="infoAttachTab === 'links'">
                <div v-if="!attachLinks.length" class="mip-attach-empty">No links shared yet</div>
                <template v-else>
                  <a
                    v-for="(l, i) in attachLinks.slice(0, 3)"
                    :key="i"
                    :href="l.url"
                    target="_blank"
                    rel="noopener"
                    class="mip-file-row"
                  >
                    <span class="mip-file-icon"><AegisIcon name="link" :size="14" /></span>
                    <div class="mip-file-body">
                      <div class="mip-file-name">{{ l.title }}</div>
                      <div class="mip-file-size" style="color:var(--gold-dark)">{{ l.url }}</div>
                    </div>
                    <span class="mip-file-dl"><AegisIcon name="external-link" :size="14" /></span>
                  </a>
                  <button type="button" class="mip-attach-viewall" @click="modals.allMedia = true">
                    <AegisIcon name="link" :size="12" /> View All Links
                  </button>
                </template>
              </div>
            </div>

            <!-- ▌ Quick actions -->
            <div class="msg-info-section">
              <div class="msg-info-section-label">Actions</div>
              <div class="mip-action-row" @click="modals.exportChat = true; showInfo = false">
                <span class="mip-action-icon"><AegisIcon name="download" :size="14" /></span>
                <span class="mip-action-label">Export Chat</span>
                <AegisIcon name="chevron-right" :size="13" />
              </div>
              <div class="mip-action-row" @click="modals.muteNotif = true; showInfo = false">
                <span class="mip-action-icon"><AegisIcon name="bell" :size="14" /></span>
                <span class="mip-action-label">Mute Notifications</span>
                <AegisIcon name="chevron-right" :size="13" />
              </div>
              <div class="mip-action-row is-danger" @click="blockContact">
                <span class="mip-action-icon"><AegisIcon name="x-circle" :size="14" /></span>
                <span class="mip-action-label">Block Contact</span>
                <AegisIcon name="chevron-right" :size="13" />
              </div>
            </div>

          </div>
        </div>
      </transition>
    </div>

    <!-- ── New Message Modal ──────────────────────── -->
    <AegisModal
      v-model="modals.compose"
      title="New Message"
      size="md"
      @close="resetCompose"
    >
      <div class="form-group">
        <label class="form-label" for="compose-search">To <span class="text-danger">*</span></label>
        <div class="compose-search-wrap">
          <AegisIcon name="search" :size="18" />
          <input
            id="compose-search"
            v-model="recipientFilter"
            type="text"
            class="compose-search-input"
            placeholder="Search by name…"
            autocomplete="off"
          />
        </div>
        <div class="compose-recipient-list">
          <div
            v-for="r in filteredRecipients"
            :key="r.id"
            class="compose-recipient-row"
            :class="{ active: selectedRecipientId === r.id }"
            @click="selectedRecipientId = r.id"
          >
            <div class="msg-avatar is-sm" :style="r.avatar_url ? { backgroundImage: `url(${r.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
              <template v-if="!r.avatar_url">{{ r.avatar_initials }}</template>
            </div>
            <div class="compose-recipient-body">
              <div class="compose-recipient-name">{{ r.display_name }}</div>
              <div class="compose-recipient-role">{{ r.role_label }}{{ r.organization ? ' · ' + r.organization : '' }}</div>
            </div>
          </div>
          <div v-if="!filteredRecipients.length" class="compose-recipient-empty">No matches.</div>
        </div>
        <div v-if="composeFieldError('participant_ids')" class="form-error">{{ composeFieldError('participant_ids') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="compose-title">Subject</label>
        <input
          id="compose-title"
          v-model="composeForm.title"
          type="text"
          class="form-input"
          placeholder="Optional subject line"
          maxlength="200"
        />
      </div>
      <div class="form-group">
        <label class="form-label" for="compose-body">Message <span class="text-danger">*</span></label>
        <textarea
          id="compose-body"
          v-model="composeForm.body"
          class="form-input"
          :class="{ 'is-error': composeFieldError('body') }"
          rows="5"
          placeholder="Type your message…"
          @blur="v$.body.$touch()"
        ></textarea>
        <div v-if="composeFieldError('body')" class="form-error">{{ composeFieldError('body') }}</div>
        <p class="form-hint">Messages are end-to-end encrypted and audit-logged.</p>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.compose = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="composeForm.processing"
          @click="sendNewThread"
        >
          <AegisIcon name="send" :size="18" />
          {{ composeForm.processing ? 'Sending…' : 'Send Message' }}
        </button>
      </template>
    </AegisModal>

    <!-- ── Conversation Info Modal ───────────────────── -->
    <AegisModal v-model="modals.chatInfo" title="Conversation Info" size="md">
      <div v-if="activeThread" style="text-align:center;margin-bottom:20px">
        <div class="msg-avatar" :class="{ 'is-gold': activeThread.is_continuity_contact }"
             :style="activeThread.counterpart?.avatar_url
               ? { width: '56px', height: '56px', margin: '0 auto', backgroundImage: `url(${activeThread.counterpart.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' }
               : { width: '56px', height: '56px', fontSize: '16px', margin: '0 auto' }">
          <template v-if="!activeThread.counterpart?.avatar_url">{{ activeThread.counterpart?.avatar_initials || '·' }}</template>
        </div>
        <div style="font-family:var(--font-serif);font-size:18px;font-weight:700;margin-top:14px">{{ activeThread.counterpart?.display_name }}</div>
        <div style="font-size:13px;color:var(--text-3);margin-top:4px">{{ activeThread.counterpart?.role_label }}</div>
        <div v-if="activeThread.is_continuity_contact" style="margin-top:8px">
          <span class="badge badge-gold">Continuity Contact</span>
        </div>
      </div>
      <div v-if="activeThread" class="list-group">
        <div v-if="activeThread.counterpart?.email" class="list-group-item" style="gap:10px">
          <span style="color:var(--gold-dark);display:inline-flex"><AegisIcon name="mail" :size="18" /></span>
          <div>
            <div style="font-size:11px;color:var(--text-4);font-weight:700;text-transform:uppercase;letter-spacing:.5px">Email</div>
            <a :href="`mailto:${activeThread.counterpart.email}`" style="font-size:13px;font-weight:700;color:var(--gold-dark)">{{ activeThread.counterpart.email }}</a>
          </div>
        </div>
        <div v-if="activeThread.counterpart?.phone" class="list-group-item" style="gap:10px">
          <span style="color:var(--gold-dark);display:inline-flex"><AegisIcon name="phone" :size="18" /></span>
          <div>
            <div style="font-size:11px;color:var(--text-4);font-weight:700;text-transform:uppercase;letter-spacing:.5px">Phone</div>
            <div style="font-size:13px;font-weight:700">{{ activeThread.counterpart.phone }}</div>
          </div>
        </div>
        <div v-if="activeThread.counterpart?.location" class="list-group-item" style="gap:10px">
          <span style="color:var(--gold-dark);display:inline-flex"><AegisIcon name="map-pin" :size="18" /></span>
          <div>
            <div style="font-size:11px;color:var(--text-4);font-weight:700;text-transform:uppercase;letter-spacing:.5px">Location</div>
            <div style="font-size:13px;font-weight:700">{{ activeThread.counterpart.location }}</div>
          </div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.chatInfo = false">Close</button>
      </template>
    </AegisModal>

    <!-- ── Export Chat Modal ──────────────────────────── -->
    <AegisModal v-model="modals.exportChat" title="Export Chat" size="md">
      <p style="font-size:13px;color:var(--text-3)">Export this conversation as a plain text or JSON file for your records.</p>
      <div class="form-group" style="margin-top:16px">
        <label class="form-label">Format</label>
        <select v-model="exportFormat" class="form-select">
          <option value="txt">Plain Text (.txt)</option>
          <option value="json">JSON (raw)</option>
        </select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.exportChat = false">Cancel</button>
        <a
          v-if="activeThread"
          :href="route('messages.export', activeThread.id) + '?format=' + exportFormat"
          class="btn btn-primary"
          @click="modals.exportChat = false"
        >
          <AegisIcon name="download" :size="16" />
          Download
        </a>
      </template>
    </AegisModal>

    <!-- ── Mute Notifications Modal ───────────────────── -->
    <AegisModal v-model="modals.muteNotif" title="Mute Notifications" size="sm">
      <p style="font-size:13px;color:var(--text-3);margin-bottom:16px">Pause notifications for this conversation:</p>
      <div class="list-group">
        <div
          v-for="opt in muteOptions"
          :key="opt.value"
          class="list-group-item clickable"
          style="cursor:pointer;gap:12px"
          @click="muteThread(opt)"
        >
          <span style="display:inline-flex;color:var(--gold-dark)"><AegisIcon :name="opt.icon" :size="16" /></span>
          <div style="flex:1">
            <div style="font-size:13px;font-weight:600">{{ opt.label }}</div>
            <div style="font-size:11px;color:var(--text-4)">{{ opt.sub }}</div>
          </div>
          <span style="display:inline-flex;color:var(--text-4)"><AegisIcon name="chevron-right" :size="14" /></span>
        </div>
      </div>
      <div v-if="activeThread?.is_muted" style="margin-top:12px;text-align:center">
        <button type="button" class="btn btn-outline btn-sm" @click="unmuteThread">
          <AegisIcon name="bell" :size="14" />
          Unmute conversation
        </button>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.muteNotif = false">Cancel</button>
      </template>
    </AegisModal>

    <!-- ── Availability Modal ─────────────────────────── -->
    <AegisModal v-model="modals.availability" title="Set Availability" size="sm">
      <div class="list-group">
        <div v-for="s in availabilityOptions" :key="s.value" class="list-group-item clickable avail-row" style="cursor:pointer" @click="setAvailability(s)">
          <span class="avail-dot" :class="`avail-dot--${s.value}`"></span>
          <div style="flex:1;font-size:13px;font-weight:500">{{ s.label }}</div>
          <AegisIcon v-if="currentStatus === s.value" name="check" :size="14" style="color:var(--green-dark)" />
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.availability = false">Close</button>
      </template>
    </AegisModal>

    <!-- ── All Attachments Modal ──────────────────────── -->
    <AegisModal v-model="modals.allMedia" title="Media, Files &amp; Links" size="lg">
      <!-- Tab strip -->
      <div class="mip-attach-tabs" style="margin-bottom:14px">
        <button
          v-for="tab in attachTabs"
          :key="tab.key"
          type="button"
          class="tab-pill"
          :class="{ active: allMediaTab === tab.key }"
          @click="allMediaTab = tab.key"
        >
          {{ tab.label }}
          <span class="badge-pill">{{ tab.count }}</span>
        </button>
      </div>

      <!-- Media -->
      <div v-if="allMediaTab === 'media'">
        <div v-if="!attachMedia.length" class="mcd-attach-empty">No media shared yet</div>
        <div v-else class="all-media-grid">
          <div
            v-for="(item, i) in attachMedia"
            :key="i"
            class="mip-media-thumb"
            :data-tooltip="item.name"
            @click="openImageViewer(item)"
          >
            <img
              v-if="item.url && /\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i.test(item.name || '')"
              :src="item.url"
              :alt="item.name"
              class="mip-media-img"
            />
            <AegisIcon v-else name="camera" :size="22" />
          </div>
        </div>
      </div>

      <!-- Files -->
      <div v-else-if="allMediaTab === 'files'">
        <div v-if="!attachFiles.length" class="mcd-attach-empty">No files shared yet</div>
        <div v-else class="all-files-list">
          <a
            v-for="(f, i) in attachFiles"
            :key="i"
            :href="f.url"
            :download="f.name"
            target="_blank"
            rel="noopener"
            class="mip-file-row"
          >
            <span class="mip-file-icon"><AegisIcon :name="attachIcon(f.mime)" :size="16" /></span>
            <div class="mip-file-body">
              <div class="mip-file-name">{{ f.name }}</div>
              <div v-if="f.size" class="mip-file-size">{{ f.size }}</div>
            </div>
            <span class="mip-file-dl"><AegisIcon name="download" :size="14" /></span>
          </a>
        </div>
      </div>

      <!-- Links -->
      <div v-else-if="allMediaTab === 'links'">
        <div v-if="!attachLinks.length" class="mcd-attach-empty">No links shared yet</div>
        <div v-else class="all-files-list">
          <a
            v-for="(l, i) in attachLinks"
            :key="i"
            :href="l.url"
            target="_blank"
            rel="noopener"
            class="mip-file-row"
          >
            <span class="mip-file-icon"><AegisIcon name="link" :size="16" /></span>
            <div class="mip-file-body">
              <div class="mip-file-name">{{ l.title }}</div>
              <div class="mip-file-size" style="color:var(--gold-dark)">{{ l.url }}</div>
            </div>
            <span class="mip-file-dl"><AegisIcon name="external-link" :size="14" /></span>
          </a>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.allMedia = false">Close</button>
      </template>
    </AegisModal>

    <!-- ── Image / Media Preview Modal ───────────────── -->
    <AegisModal v-model="modals.imageViewer" title="Media Preview" size="lg">
      <div class="image-viewer-area">
        <img
          v-if="imageViewerUrl && /\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i.test(imageViewerName || '')"
          :src="imageViewerUrl"
          :alt="imageViewerName"
          style="max-width:100%;max-height:380px;border-radius:var(--radius);display:block;margin:0 auto"
        />
        <div v-else class="image-viewer-icon"><AegisIcon name="camera" :size="36" /></div>
        <div class="image-viewer-name" style="margin-top:12px">{{ imageViewerName || '—' }}</div>
        <div class="image-viewer-meta">{{ imageViewerMeta || '—' }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.imageViewer = false">Close</button>
        <a
          v-if="imageViewerUrl"
          :href="imageViewerUrl"
          :download="imageViewerName"
          target="_blank"
          rel="noopener"
          class="btn btn-primary"
        >
          <AegisIcon name="download" :size="18" />
          Download
        </a>
      </template>
    </AegisModal>

    <!-- ── Template Picker Modal ──────────────────────── -->
    <AegisModal v-model="modals.templatePicker" title="Message Templates" size="md">
      <div class="list-group">
        <div v-for="tmpl in messageTemplates" :key="tmpl.title" class="list-group-item clickable" style="cursor:pointer" @click="useTemplate(tmpl.body)">
          <span style="display:inline-flex"><AegisIcon name="file-text" :size="18" /></span>
          <div style="flex:1">
            <div style="font-size:13px;font-weight:700">{{ tmpl.title }}</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:2px">{{ tmpl.preview }}</div>
          </div>
          <span style="color:var(--gold-dark);display:inline-flex"><AegisIcon name="chevron-right" :size="18" /></span>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.templatePicker = false">Cancel</button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, nextTick, onMounted, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { useConfirm } from '@/composables/useConfirm'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, helpers } from '@vuelidate/validators'

const props = defineProps({
  threads:              { type: Array,  default: () => [] },
  activeThread:         { type: Object, default: null },
  activeMessages:       { type: Array,  default: () => [] },
  recipients:           { type: Array,  default: () => [] },
  unreadCounts:         { type: Object, default: () => ({}) },
  buckets:              { type: Array,  default: () => [{ key: 'all', label: 'All', tip: 'All conversations' }] },
  bucketCounts:         { type: Object, default: () => ({}) },
  currentUserId:        { type: String, default: '' },
  currentUserInitials:  { type: String, default: 'U' },
  currentUserAvatarUrl: { type: String, default: null },
})

const page     = usePage()
const toast    = useToast()
const activity = useActivity()
const { confirmAction } = useConfirm()
const filter   = ref('')
const activeBucket = ref('all')
const showInfo            = ref(false)
const allMediaTab         = ref('media')
const imageViewerName     = ref('')
const imageViewerMeta     = ref('')
const imageViewerUrl      = ref('')
const searchOpen          = ref(false)
const searchQuery         = ref('')
const chatSearchInputRef  = ref(null)
const searchContactsOpen  = ref(false)
const fileInput           = ref(null)
const searchInputRef      = ref(null)
const searchMatchIdx      = ref(0)
const infoAttachTab       = ref('media')
const recipientFilter = ref('')
const selectedRecipientId = ref('')
const msgStream = ref(null)

const modals = reactive({
  compose:       false,
  chatInfo:      false,
  exportChat:    false,
  muteNotif:     false,
  allMedia:      false,
  imageViewer:   false,
  templatePicker:false,
  availability:  false,
})

// ── Thread list filtering ────────────────────────
const filteredThreads = computed(() => {
  let list = props.threads
  if (activeBucket.value !== 'all') {
    list = list.filter(t => t.bucket === activeBucket.value)
  }
  const q = filter.value.trim().toLowerCase()
  if (q) {
    list = list.filter(t =>
      `${t.counterpart?.display_name ?? ''} ${t.last_message_snippet ?? ''}`
        .toLowerCase()
        .includes(q)
    )
  }
  return list
})

function selectThread(t) {
  router.get(route('messages.index', { thread: t.id }), {}, {
    preserveScroll: true,
    preserveState:  true,
    only: ['activeThread', 'activeMessages', 'unreadCounts', 'threads'],
  })
}

// ── Day chip + message formatting ────────────────
function shouldShowDayChip(msg, idx) {
  if (idx === 0) return true
  const prev = props.activeMessages[idx - 1]
  return formatDay(prev.sent_at) !== formatDay(msg.sent_at)
}

function formatDay(ts) {
  if (!ts) return ''
  const d = new Date(ts)
  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(today.getDate() - 1)
  const sameDay = (a, b) =>
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  if (sameDay(d, today))     return 'Today'
  if (sameDay(d, yesterday)) return 'Yesterday'
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatTime(ts) {
  if (!ts) return ''
  const d = new Date(ts)
  return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
}

// ── Compose (new thread) ──────────────────────────
const composeForm = useForm({
  participant_ids: [],
  title:           '',
  body:            '',
})

const composeRules = computed(() => ({
  participant_ids: {
    required: helpers.withMessage('Please select a recipient.',
      v => Array.isArray(v) ? v.length > 0 : !!v),
  },
  body: {
    required: helpers.withMessage('Message body is required.', required),
    minLen:   helpers.withMessage('Message must be at least 2 characters.', minLength(2)),
  },
}))
const v$ = useVuelidate(composeRules, composeForm)

function composeFieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (composeForm.errors[field]) return composeForm.errors[field]
  return null
}

const filteredRecipients = computed(() => {
  const q = recipientFilter.value.trim().toLowerCase()
  if (!q) return props.recipients.slice(0, 15)
  return props.recipients
    .filter(r =>
      `${r.display_name} ${r.role_label} ${r.organization ?? ''}`
        .toLowerCase()
        .includes(q)
    )
    .slice(0, 15)
})

function openCompose() {
  resetCompose()
  modals.compose = true
}

function resetCompose() {
  composeForm.reset()
  composeForm.participant_ids = []
  v$.value.$reset()
  recipientFilter.value = ''
  selectedRecipientId.value = ''
}

async function sendNewThread() {
  composeForm.participant_ids = selectedRecipientId.value
    ? [selectedRecipientId.value]
    : []
  const ok = await v$.value.$validate()
  if (!ok) return toast.error('Please fix the highlighted fields.')

  composeForm.post(route('messages.store'), {
    onSuccess: () => {
      modals.compose = false
      toast.success('Message sent.')
      resetCompose()
    },
    onError: () => toast.error('Could not send message. Please try again.'),
  })
}

// ── Inline reply ──────────────────────────────────
const pendingFile = ref(null)           // File object awaiting send
const replyForm = useForm({
  body:        '',
  attachments: [],                      // populated just before submit
})

function onComposeKey(ev) {
  if (ev.key === 'Enter' && !ev.shiftKey) {
    ev.preventDefault()
    sendReply()
  }
}

function autoResize(ev) {
  const ta = ev.target
  ta.style.height = '32px'
  ta.style.height = Math.min(ta.scrollHeight, 160) + 'px'
}

function sendReply() {
  if (!replyForm.body.trim() && !pendingFile.value) return
  if (!props.activeThread) return
  // Pack the pending file into the form right before submit
  if (pendingFile.value) {
    replyForm.attachments = [pendingFile.value]
  }
  replyForm.post(route('messages.reply', props.activeThread.id), {
    forceFormData:  true,
    preserveScroll: true,
    onSuccess: () => {
      replyForm.reset()
      replyForm.attachments = []
      pendingFile.value = null
      nextTick(() => scrollToBottom())
    },
    onError: () => toast.error('Could not send reply.'),
  })
}

function scrollToBottom() {
  if (msgStream.value) {
    msgStream.value.scrollTop = msgStream.value.scrollHeight
  }
}

// Auto-focus search input when contacts search bar opens
watch(searchContactsOpen, async (open) => {
  if (open) {
    await nextTick()
    searchInputRef.value?.focus()
  } else {
    filter.value = ''
  }
})

onMounted(() => nextTick(() => scrollToBottom()))
watch(() => props.activeMessages, () => nextTick(() => scrollToBottom()))

// ── Attachment data (derived from activeMessages) ─────────────────
// Flatten all attachments from every message in the thread
const allAttachments = computed(() => {
  if (!props.activeThread) return []
  return props.activeMessages.flatMap(msg =>
    (msg.attachments || []).map(att => ({ ...att, sent_at: msg.sent_at }))
  )
})

const IMAGE_RE = /\.(jpg|jpeg|png|gif|webp|svg|bmp|tiff|heic)$/i
const FILE_RE  = /\.(pdf|doc|docx|xls|xlsx|csv|txt|zip|rar|7z|ppt|pptx|mp4|mov|avi|mp3|wav)$/i

const attachMedia = computed(() =>
  allAttachments.value
    .filter(a => IMAGE_RE.test(a.name || '') || (a.mime || '').startsWith('image/'))
    .slice(0, 6)
)

const attachFiles = computed(() =>
  allAttachments.value
    .filter(a => !IMAGE_RE.test(a.name || '') && !(a.mime || '').startsWith('image/'))
    .slice(0, 8)
)

const attachLinks = computed(() => {
  if (!props.activeThread) return []
  return props.activeMessages
    .filter(msg => /^https?:\/\//i.test(msg.body || ''))
    .map(msg => ({ title: (msg.body || '').slice(0, 40), url: msg.body, sent_at: msg.sent_at }))
    .slice(0, 3)
})

const attachTabs = computed(() => [
  { key: 'media', label: 'Media', count: attachMedia.value.length },
  { key: 'files', label: 'Files', count: attachFiles.value.length },
  { key: 'links', label: 'Links', count: attachLinks.value.length },
])

function openImageViewer(item) {
  imageViewerName.value = item.name || '—'
  imageViewerMeta.value = [item.size, item.sent_at ? 'Shared ' + item.sent_at : ''].filter(Boolean).join(' · ')
  imageViewerUrl.value  = item.url || ''
  modals.imageViewer = true
}

function markUnread() {
  toast.success('Marked as unread.')
}

const blockForm = useForm({})
async function blockContact() {
  const name = props.activeThread?.counterpart?.display_name ?? 'this contact'
  const ok = await confirmAction(
    `Block ${name}? They won't be able to message you. You can unblock them from Settings.`,
    { title: 'Block Contact', confirmLabel: 'Block', destructive: true }
  )
  if (!ok) return
  // No dedicated block endpoint yet — mark thread archived + show feedback
  // TODO: wire to messages.block when endpoint is built
  showInfo.value = false
  toast.warning(`${name} has been blocked.`)
}

// ── In-chat search ───────────────────────────────────
const searchMatches = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return []
  return props.activeMessages
    .filter(msg => msg.body?.toLowerCase().includes(q))
    .map(msg => msg.id)
})

watch(searchQuery, () => { searchMatchIdx.value = 0 })
watch(searchOpen, async (open) => {
  if (open) {
    await nextTick()
    chatSearchInputRef.value?.focus()
  } else {
    searchQuery.value = ''
    searchMatchIdx.value = 0
  }
})

function stepSearchMatch(dir) {
  if (!searchMatches.value.length) return
  const len = searchMatches.value.length
  searchMatchIdx.value = ((searchMatchIdx.value + dir) % len + len) % len
  const id = searchMatches.value[searchMatchIdx.value]
  const el = document.getElementById(`msg-${id}`)
  if (!el) return
  el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  // WhatsApp-style flash: add class, let CSS transition fade it out
  const bubble = el.querySelector('.msg-bubble')
  if (bubble) {
    bubble.classList.remove('msg-bubble-flash')
    // Force reflow so animation restarts if same bubble hit again
    void bubble.offsetWidth
    bubble.classList.add('msg-bubble-flash')
  }
}

function highlightMatch(text, query) {
  if (!query?.trim() || !text) return escapeHtml(text)
  const q = query.trim()
  const re = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return escapeHtml(text).replace(re, '<mark class="msg-search-hl">$1</mark>')
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

// ── Profile URL helper ─────────────────────────────
function profileUrl(counterpart) {
  if (!counterpart?.slug) return '#'
  const roleMap = {
    practitioner:       'public.provider',
    continuity_steward: 'public.cs',
    support_steward:    'public.ss',
    business_partner:   'public.bp',
  }
  const routeName = roleMap[counterpart.role] ?? 'public.provider'
  try { return route(routeName, counterpart.slug) } catch { return '#' }
}

// ── File attachment handler ─────────────────────────
function onFileSelected(ev) {
  const file = ev.target.files?.[0]
  if (!file) return
  pendingFile.value = file
  // Reset input so the same file can be re-selected if needed
  ev.target.value = ''
}
function clearPendingFile() {
  pendingFile.value = null
  replyForm.attachments = []
}

// ── Attachment icon helper ──────────────────────────
function attachIcon(mime) {
  if (!mime) return 'file-text'
  if (mime.startsWith('image/'))  return 'camera'
  if (mime.startsWith('video/'))  return 'video'
  if (mime.startsWith('audio/'))  return 'music'
  if (mime.includes('pdf'))       return 'file-text'
  if (mime.includes('sheet') || mime.includes('excel') || mime.includes('csv')) return 'table'
  return 'file-text'
}

// ── Activity URL — portal-prefixed + module filter ──
const activityUrl = computed(() => {
  const portal = page.props.auth?.portal ?? 'provider'
  const routeMap = {
    provider: 'provider.activity',
    cs:       'cs.activity',
    ss:       'ss.activity',
    bp:       'bp.activity',
    admin:    'admin.activity',
  }
  const name = routeMap[portal] ?? 'provider.activity'
  try {
    return route(name) + '?event_type=message'
  } catch {
    return '/activity?event_type=message'
  }
})

// ── Export format ──────────────────────────────────
const exportFormat = ref('txt')

// ── Mute options ───────────────────────────────────
const muteOptions = [
  { value: '8',     hours: 8,   icon: 'clock',    label: '8 hours',       sub: 'Until later today' },
  { value: '24',    hours: 24,  icon: 'moon',     label: '24 hours',      sub: 'Until tomorrow' },
  { value: '168',   hours: 168, icon: 'calendar', label: '1 week',        sub: '7 days from now' },
  { value: '0',     hours: 0,   icon: 'bell-off', label: 'Indefinitely',  sub: 'Until you unmute' },
]

const muteForm = useForm({ hours: 8 })
function muteThread(opt) {
  if (!props.activeThread) return
  muteForm.hours = opt.hours
  muteForm.post(route('messages.mute', props.activeThread.id), {
    preserveScroll: true,
    preserveState:  true,
    only: ['threads', 'activeThread'],
    onSuccess: () => {
      modals.muteNotif = false
      toast.success(`Notifications muted — ${opt.label}.`)
    },
    onError: () => toast.error('Could not mute conversation.'),
  })
}

const unmuteForm = useForm({})
function unmuteThread() {
  if (!props.activeThread) return
  unmuteForm.post(route('messages.unmute', props.activeThread.id), {
    preserveScroll: true,
    preserveState:  true,
    only: ['threads', 'activeThread'],
    onSuccess: () => {
      modals.muteNotif = false
      toast.success('Conversation unmuted.')
    },
  })
}

// ── Availability options ───────────────────────────
const availabilityOptions = [
  { value: 'available', label: 'Available' },
  { value: 'away',      label: 'Away' },
  { value: 'busy',      label: 'Busy — Do Not Disturb' },
]
const currentStatus = ref('available')
function setAvailability(s) {
  currentStatus.value = s.value
  modals.availability = false
  toast.success(`Status set to: ${s.label}`)
}

// ── Message templates ──────────────────────────────
const messageTemplates = computed(() => [
  {
    title:   'Continuity check-in',
    body:    'Hi — just checking in to confirm your availability and review any updates to the continuity protocol.',
    preview: 'Hi — just checking in to confirm your availability…',
  },
  {
    title:   'Incident coordination follow-up',
    body:    'Following up on the incident activation — please confirm your current status and any actions completed.',
    preview: 'Following up on the incident activation…',
  },
  {
    title:   'Plan review reminder',
    body:    'The annual plan review is coming up. Please confirm receipt of the updated task list when you have a moment.',
    preview: 'The annual plan review is coming up…',
  },
])

function useTemplate(body) {
  replyForm.body = body
  modals.templatePicker = false
  toast.success('Template inserted.')
}
</script>

<style scoped>
/* ── Layout shell ──────────────────────────────── */
.msg-layout {
  display: grid;
  grid-template-columns: 300px 1fr auto;
  gap: 0.875rem;
  height: calc(100vh - 220px);
  min-height: 540px;
}
.msg-pane {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
}

/* ── LEFT contacts pane ────────────────────────── */
.msg-contacts-head {
  padding: 6px 10px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
}
.msg-contacts-icon-row {
  display: flex;
  align-items: center;
  gap: 2px;
  justify-content: flex-end;
}
.msg-contacts-search-bar {
  padding: 6px 10px 8px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
}
.msg-search-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.msg-search-input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 8px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-3);
}
.msg-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 12px;
  color: var(--text);
  font-family: inherit;
}
.msg-search-input::placeholder { color: var(--text-4); }
.msg-search-clear {
  display: inline-flex;
  align-items: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-4);
  padding: 0;
}
.msg-search-clear:hover { color: var(--text); }

/* ── Active-state shim for toggle buttons ─────────── */
/* Uses global .btn-icon from _shared.css — no re-definition needed */
.is-active-icon {
  background: var(--badge-bg-gold) !important;
  border-color: var(--soft-gold) !important;
  color: var(--gold-dark) !important;
}

/* Bucket filter — compact dropdown ───────────────── */
.msg-filter-row {
  padding: 6px 10px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
}
.msg-filter-select-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: 100%;
}
.msg-filter-icon { color: var(--text-4); flex-shrink: 0; }
.msg-filter-select {
  flex: 1;
  appearance: none;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 4px 28px 4px 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  outline: none;
  font-family: inherit;
  transition: border-color var(--transition-fast);
}
.msg-filter-select:focus { border-color: var(--gold); }
.msg-filter-chevron {
  position: absolute;
  right: 8px;
  pointer-events: none;
  color: var(--text-4);
}

/* Badge pill (kept for other uses) */
.badge-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 16px;
  padding: 0 0.3rem;
  border-radius: var(--radius-full);
  background: var(--surface);
  color: var(--text-3);
  font-size: 10px;
  font-weight: 700;
  margin-left: 0.35rem;
  border: 1px solid var(--border);
}

/* Contact list */
.msg-contact-list {
  flex: 1;
  overflow-y: auto;
}
.msg-list-empty {
  padding: 1.5rem 1rem;
  text-align: center;
  font-size: 12px;
  color: var(--text-4);
  line-height: 1.55;
}
.msg-contact-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.8rem 0.875rem;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.msg-contact-item:hover { background: var(--surface-2); }
.msg-contact-item.is-active {
  background: var(--badge-bg-gold);
  border-left: 3px solid var(--gold-dark);
}

.msg-avatar-wrap {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
}
.msg-avatar {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-full);
  background: var(--surface-3);
  color: var(--text-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.msg-avatar.is-gold {
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
}
.msg-avatar.is-sm { width: 30px; height: 30px; font-size: 11px; }
.msg-avatar.is-xs { width: 26px; height: 26px; font-size: 10px; }

/* ── Presence dot — top-right of avatar like Facebook ─ */
.msg-presence-dot {
  position: absolute;
  top: 0;
  right: 0;
  width: 9px;
  height: 9px;
  border-radius: var(--radius-full);
  border: 2px solid var(--surface);
  flex-shrink: 0;
}
.msg-presence-dot--available { background: var(--green, #22c55e); }
.msg-presence-dot--busy      { background: var(--orange, #f97316); }
.msg-presence-dot--away      { background: var(--surface-3); }

.msg-contact-name-row {
  display: flex;
  align-items: center;
  gap: 5px;
}
.msg-unread-indicator {
  width: 7px;
  height: 7px;
  border-radius: var(--radius-full);
  background: var(--text);
  flex-shrink: 0;
}
.msg-contact-name.is-unread { font-weight: 800; color: var(--text); }
.msg-contact-info { flex: 1; min-width: 0; }
.msg-contact-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.15rem;
}
.msg-contact-preview {
  font-size: 12px;
  color: var(--text-3);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.msg-contact-preview.is-unread { color: var(--text-2); font-weight: 600; }
.msg-contact-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.3rem;
  flex-shrink: 0;
}
.msg-contact-time { font-size: 11px; color: var(--text-4); }
.msg-contact-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
}

/* ── CENTER chat pane ──────────────────────────── */
.msg-chat-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.125rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
}
.msg-chat-head-info { flex: 1; min-width: 0; }
.msg-chat-head-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.msg-chat-head-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.15rem;
}
.msg-chat-head-sub {
  font-size: 12px;
  color: var(--text-3);
}
.is-link-name {
  color: var(--text);
  text-decoration: none;
  cursor: pointer;
}
.is-link-name:hover { color: var(--gold-dark); }
.msg-sub-dot { margin: 0 0.3rem; color: var(--text-4); }
.msg-continuity-flag {
  color: var(--gold-dark);
  font-weight: 700;
}

.msg-empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem;
}
.msg-empty-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-full);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}
.msg-empty-title {
  font-family: var(--font-serif);
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.4rem;
}
.msg-empty-text {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
  max-width: 320px;
}

/* Message stream */
.msg-stream {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.125rem;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: var(--surface-2);
}
.msg-day-chip {
  align-self: center;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-4);
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-full);
  margin: 0.5rem 0;
}
.msg-row {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  max-width: 80%;
}
.msg-row.is-sent {
  align-self: flex-end;
  flex-direction: row-reverse;
}
.msg-bubble-wrap {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.msg-row.is-sent .msg-bubble-wrap { align-items: flex-end; }
.msg-bubble {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 5px 10px;
  font-size: 13px;
  color: var(--text);
  line-height: 1.45;
  white-space: pre-line;
  word-break: break-word;
}
.msg-row.is-sent .msg-bubble {
  background: var(--badge-bg-gold);
  border-color: var(--soft-gold);
  color: var(--text);
}
.msg-bubble-time {
  font-size: 10px;
  color: var(--text-4);
  margin-top: 0.2rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

/* Compose */
.msg-compose {
  padding: 8px 12px;
  border-top: 1px solid var(--border);
  background: var(--surface);
}
.msg-compose-row {
  display: flex;
  align-items: center;
  gap: 6px;
}
.msg-compose-input {
  flex: 1;
  resize: none;
  min-height: 32px;
  height: 32px;
  max-height: 120px;
  padding: 0 10px;
  box-sizing: border-box;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 13px;
  line-height: 30px;
  color: var(--text);
  font-family: inherit;
  outline: none;
  overflow: hidden;
  transition: border-color var(--transition-fast);
}
.msg-compose-input:focus { border-color: var(--gold); }

/* ── RIGHT info panel ────────────────── */
.msg-info-panel {
  width: 300px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.msg-info-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
  flex-shrink: 0;
}
.msg-info-title {
  font-family: var(--font-serif);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}
.msg-info-body {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
}
.msg-info-section {
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
}
.msg-info-section:last-child { border-bottom: none; }
.msg-info-section-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-4);
  margin-bottom: 8px;
}
.msg-info-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 0;
  font-size: 12px;
}
.msg-info-icon {
  display: inline-flex;
  align-items: center;
  color: var(--gold-dark);
  flex-shrink: 0;
}
.msg-info-label {
  color: var(--text-3);
  font-weight: 600;
  min-width: 44px;
  flex-shrink: 0;
  font-size: 11px;
}
.msg-info-value {
  color: var(--text);
  flex: 1;
  font-size: 12px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.msg-info-value.is-link { color: var(--gold-dark); text-decoration: none; }
.msg-info-value.is-link:hover { text-decoration: underline; }

/* Identity header */
.mip-identity {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  text-align: center;
  padding: 18px 14px 14px;
  border-bottom: 1px solid var(--border);
}
.mip-avatar {
  width: 52px !important;
  height: 52px !important;
  font-size: 16px !important;
  margin-bottom: 6px;
}
.mip-name {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  text-decoration: none;
  cursor: default;
  line-height: 1.2;
}
a.mip-name { cursor: pointer; }
a.mip-name:hover { color: var(--gold-dark); }
.mip-role { font-size: 12px; color: var(--text-3); margin-top: 2px; line-height: 1.4; }
.mip-org  { font-size: 11px; color: var(--text-4); margin-top: 2px; }
.mip-ctas { display: flex; gap: 6px; justify-content: center; margin-top: 12px; }

/* Attachment tabs */
.mip-attach-tabs {
  display: flex;
  gap: 3px;
  background: var(--surface-2);
  padding: 3px;
  border-radius: var(--radius-sm);
  margin-bottom: 10px;
}
.mip-attach-tabs .tab-pill {
  flex: 1;
  font-size: 11px;
  padding: 4px 6px;
  justify-content: center;
  text-align: center;
  border-width: 0.5px;
  gap: 5px;
}
/* Thin border on all tab-pills in this page */
.tab-pill {
  border-width: 0.5px;
}
/* Badge pill redesign inside attach tabs */
.mip-attach-tabs .badge-pill {
  min-width: 16px;
  height: 15px;
  padding: 0 4px;
  font-size: 9px;
  font-weight: 800;
  border-radius: 99px;
  margin-left: 0;
  background: var(--surface-3);
  border: none;
  color: var(--text-3);
  letter-spacing: 0.2px;
}
.mip-attach-tabs .tab-pill.active .badge-pill {
  background: var(--gold-dark);
  color: #fff;
}

/* Media grid */
.mip-media-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
  margin-bottom: 8px;
}
.mip-media-thumb {
  aspect-ratio: 1;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-3);
  cursor: pointer;
  overflow: hidden;
  transition: background var(--transition-fast), color var(--transition-fast), border-color var(--transition-fast);
}
.mip-media-thumb:hover {
  background: var(--badge-bg-gold);
  border-color: var(--soft-gold);
  color: var(--gold-dark);
}
.mip-media-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* File/link rows */
.mip-file-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 6px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.mip-file-row:hover { background: var(--surface-2); }
.mip-file-icon { display: inline-flex; align-items: center; color: var(--gold-dark); flex-shrink: 0; }
.mip-file-body { flex: 1; min-width: 0; }
.mip-file-name { font-size: 12px; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mip-file-size { font-size: 10px; color: var(--text-4); font-weight: 600; }
.mip-file-dl { display: inline-flex; align-items: center; color: var(--text-4); flex-shrink: 0; }

/* Attach empty + view-all */
.mip-attach-empty {
  text-align: center;
  font-size: 11px;
  color: var(--text-4);
  font-weight: 600;
  padding: 14px 8px;
  background: var(--surface-2);
  border: 1px dashed var(--border);
  border-radius: var(--radius-sm);
}
.mip-attach-viewall {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  width: 100%;
  margin-top: 8px;
  padding: 6px;
  font-size: 11px;
  font-weight: 700;
  color: var(--gold-dark);
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background var(--transition-fast), border-color var(--transition-fast);
}
.mip-attach-viewall:hover { background: var(--badge-bg-gold); border-color: var(--soft-gold); }

/* Quick action rows */
.mip-action-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 6px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.mip-action-row:hover { background: var(--surface-2); }
.mip-action-icon { display: inline-flex; align-items: center; color: var(--gold-dark); flex-shrink: 0; }
.mip-action-label { flex: 1; }
.mip-action-row.is-danger { color: var(--red-dark); }
.mip-action-row.is-danger .mip-action-icon { color: var(--red-dark); }
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border: 0;
}

.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* ── Compose modal recipient picker ────────────── */
.compose-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-3);
  margin-bottom: 0.5rem;
}
.compose-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: var(--text);
  font-family: inherit;
}
.compose-recipient-list {
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
}
.compose-recipient-row {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.compose-recipient-row:last-child { border-bottom: none; }
.compose-recipient-row:hover { background: var(--surface-2); }
.compose-recipient-row.active {
  background: var(--badge-bg-gold);
  border-left: 3px solid var(--gold-dark);
}
.compose-recipient-body { flex: 1; min-width: 0; }
.compose-recipient-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}
.compose-recipient-role {
  font-size: 11px;
  color: var(--text-3);
  margin-top: 0.1rem;
}
.compose-recipient-empty {
  padding: 1rem;
  text-align: center;
  font-size: 12px;
  color: var(--text-4);
}

@media (max-width: 1100px) {
  .msg-layout { grid-template-columns: 260px 1fr auto; }
  .msg-info-panel { width: 240px; }
}
/* ── System note (encryption banner) ──────────── */
.msg-system-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: 0.3px;
  color: var(--text-4);
  background: #fff;
  border-bottom: 1px solid var(--border);
  padding: 5px 14px;
  flex-shrink: 0;
}
.msg-system-note .aegis-icon {
  color: var(--green-dark, #2e7d52);
  flex-shrink: 0;
}

/* ── Inline chat search ──────────────────────── */
.msg-chat-search {
  display: none;
  padding: 8px 12px;
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.msg-chat-search.is-open { display: flex; }

/* ── Compose tools ───────────────────────────── */
.msg-compose-tools {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

/* ── Image viewer area ───────────────────────── */
.image-viewer-area {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 56px 32px;
  text-align: center;
}
.image-viewer-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 16px;
  border-radius: var(--radius-full);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
}
.image-viewer-name {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  word-break: break-all;
}
.image-viewer-meta { font-size: 12px; color: var(--text-3); margin-top: 4px; }

/* ── Attachment empty ─────────────────────────── */
.mcd-attach-empty {
  text-align: center;
  font-size: 12px;
  color: var(--text-4);
  padding: 16px 8px;
  background: var(--surface-2);
  border: 1px dashed var(--border);
  border-radius: var(--radius-sm);
}

@media (max-width: 820px) {
  .msg-layout {
    grid-template-columns: 1fr;
    grid-auto-rows: minmax(0, 1fr);
    height: auto;
  }
  .msg-info-panel { display: none; }
  .msg-pane.is-contacts { max-height: 50vh; }
}

/* ── Availability status dots ─────────────────────── */
.avail-row { gap: 12px; }
.avail-status-dot {
  width: 10px;
  height: 10px;
  border-radius: var(--radius-full);
  border: none;
  flex-shrink: 0;
  display: inline-block;
  transition: background var(--transition-fast);
}
.avail-status-dot--available { background: var(--green, #22c55e); }
.avail-status-dot--busy      { background: var(--orange, #f97316); }
.avail-status-dot--away      {
  background: transparent;
  border: 1.5px solid var(--border-dark);
}

.avail-dot {
  width: 12px;
  height: 12px;
  border-radius: var(--radius-full);
  border: none;
  flex-shrink: 0;
  display: inline-block;
}
.avail-dot--available {
  background: var(--green, #22c55e);
}
.avail-dot--busy {
  background: var(--orange, #f97316);
}
.avail-dot--away {
  background: transparent;
  border: 1.5px solid var(--border-dark);
}

/* ── Pending file chip in compose bar ─────────────── */
.msg-attach-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--badge-bg-gold);
  border: 1px solid var(--soft-gold);
  border-radius: var(--radius-sm);
  padding: 3px 6px 3px 8px;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--gold-dark);
  white-space: nowrap;
  max-width: 180px;
  flex-shrink: 0;
}
.msg-attach-chip-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}
.msg-attach-chip-remove {
  display: inline-flex;
  align-items: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--gold-dark);
  padding: 0;
  flex-shrink: 0;
  opacity: 0.7;
}
.msg-attach-chip-remove:hover { opacity: 1; }

/* ── Attachments inside message bubbles ───────────── */
.msg-bubble-attachments {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: 6px;
}
.msg-bubble-attach-row {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--surface-2);
  border: 1px solid var(--border-dark);
  border-radius: var(--radius-sm);
  padding: 6px 8px;
  text-decoration: none;
  color: var(--text);
  transition: background var(--transition-fast);
}
.msg-bubble-attach-row:hover {
  background: var(--surface-2);
  border-color: var(--border-dark);
}
/* :deep() pierces AegisIcon's child component scope */
.msg-bubble-attach-row :deep(svg),
.msg-bubble-attach-row :deep(*) {
  color: var(--text) !important;
  stroke: var(--text) !important;
}
/* Sent bubble attach row */
.msg-row.is-sent .msg-bubble-attach-row {
  background: var(--surface-2);
  border-color: var(--border-dark);
  color: var(--text);
}
.msg-row.is-sent .msg-bubble-attach-row:hover {
  background: var(--surface-2);
  border-color: var(--border-dark);
}
.msg-row.is-sent .msg-bubble-attach-row :deep(svg),
.msg-row.is-sent .msg-bubble-attach-row :deep(*) {
  color: var(--text) !important;
  stroke: var(--text) !important;
}
.msg-bubble-attach-icon {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
}
.msg-bubble-attach-body { flex: 1; min-width: 0; }
.msg-bubble-attach-name {
  font-size: 12px;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.msg-bubble-attach-size {
  font-size: 10px;
  opacity: 0.7;
  margin-top: 1px;
}

/* ── All media modal grid + list ──────────────────── */
.all-media-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.all-files-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* ── In-chat search ────────────────────────────────── */
.msg-chat-search-counter {
  font-size: 11px;
  color: var(--text-4);
  font-weight: 600;
  white-space: nowrap;
  padding: 0 4px;
}
/* Subtle match outline on all matching bubbles */
.msg-row.is-search-match .msg-bubble {
  outline: 1px dashed var(--soft-gold);
  outline-offset: 2px;
}
/* WhatsApp-style flash on the active match */
@keyframes msg-flash {
  0%   { background: var(--gold-dark); }
  40%  { background: var(--badge-bg-gold); }
  100% { background: inherit; }
}
.msg-bubble-flash {
  animation: msg-flash 1.2s ease-out forwards;
}
.msg-row.is-sent .msg-bubble-flash {
  animation: none;
  outline: 3px solid rgba(255,255,255,0.7);
  outline-offset: 2px;
  animation: msg-flash-sent 1.2s ease-out forwards;
}
@keyframes msg-flash-sent {
  0%   { box-shadow: 0 0 0 4px rgba(255,255,255,0.6); }
  100% { box-shadow: none; }
}
/* Highlighted text inside bubble */
.msg-search-hl {
  background: var(--gold-dark);
  color: #fff;
  border-radius: 2px;
  padding: 0 1px;
}
.msg-row.is-sent .msg-search-hl {
  background: rgba(255,255,255,0.9);
  color: var(--gold-dark);
}
</style>