<!--
  pages/provider/News.vue — Provider portal News & Resources feed only.
  Events removed — live at /provider/events (Events.vue).
  Prompt 2 backend-wired. Full feature set.
-->
<template>
  <AppLayout>

    <!-- HERO -->
    <AegisHeroBanner eyebrow="Provider Portal" title="News &amp; Resources"
                     subtitle="Updates from Aegis, insights from providers, and community discussions." quiet>
      <template #actions>
        <a :href="route('provider.activity') + '?event_type=news'" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button type="button" class="btn-hero-ghost is-on-light" @click="openMyLibrary">
          <AegisIcon name="bookmark" :size="14" /> My Library
        </button>
        <button type="button" class="btn-hero-solid is-on-light" @click="modals.createPost = true">
          <AegisIcon name="pencil" :size="14" /> Create Post
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS -->
    <div class="stat-chips-row">
      <AegisStatChip icon="activity" :value="countToday"    label="New Today" />
      <AegisStatChip icon="users"    :value="countAuthors"  label="Active Authors" />
      <AegisStatChip icon="calendar" :value="countUpcoming" label="Upcoming Events" />
    </div>

    <!-- TWO-COLUMN LAYOUT -->
    <div class="news-layout">

      <!-- ═══ FEED COLUMN ═══ -->
      <div class="news-feed-col">

        <!-- Create-post quick CTA -->
        <button type="button" class="news-create-cta" @click="modals.createPost = true">
          <div :class="['avatar', 'avatar-md', meAvatarMod]">{{ meInitials }}</div>
          <div class="news-create-cta-text">Share something with the Aegis community&hellip;</div>
          <div class="news-create-cta-tools">
            <AegisIcon name="camera"   :size="16" />
            <AegisIcon name="link"     :size="16" />
            <AegisIcon name="bar-chart" :size="16" />
          </div>
        </button>

        <!-- Toolbar -->
        <div class="news-toolbar">
          <div class="news-toolbar-left">
            <label class="news-toolbar-label" for="newsFilterSelect">Filter</label>
            <select id="newsFilterSelect" class="form-select form-select-sm" v-model="activeFilter" @change="applyFilter">
              <option value="all">All posts{{ countByType.all ? ' (' + countByType.all + ')' : '' }}</option>
              <option value="platform">Platform{{ countByType.platform ? ' (' + countByType.platform + ')' : '' }}</option>
              <option value="provider">Posts{{ countByType.provider ? ' (' + countByType.provider + ')' : '' }}</option>
              <option value="question">Questions{{ countByType.question ? ' (' + countByType.question + ')' : '' }}</option>
              <option value="resource">Resources{{ countByType.resource ? ' (' + countByType.resource + ')' : '' }}</option>
              <option value="question">Questions{{ countByType.question ? ' (' + countByType.question + ')' : '' }}</option>
              <option value="milestone">Milestones</option>
            </select>
          </div>
          <div class="news-toolbar-right">
            <label class="news-toolbar-label" for="newsSortSelect">Sort</label>
            <select id="newsSortSelect" class="form-select form-select-sm" v-model="activeSort" aria-label="Sort feed">
              <option value="recent">Most Recent</option>
              <option value="popular">Most Popular</option>
            </select>
          </div>
        </div>

        <!-- Active tag -->
        <div v-if="localActiveTag" class="news-active-tag">
          <span class="news-active-tag-label">Filtered by</span>
          <span class="news-active-tag-chip">#{{ localActiveTag }}</span>
          <button type="button" class="news-active-tag-clear" data-tooltip="Clear filter" @click="clearTagFilter">
            <AegisIcon name="x" :size="12" />
          </button>
        </div>

        <!-- Count line -->
        <div class="news-count-line">
          <span><strong>{{ displayPosts.length }}</strong> post{{ displayPosts.length === 1 ? '' : 's' }}{{ localActiveTag ? ' tagged #' + localActiveTag : '' }}</span>
        </div>

        <!-- Feed -->
        <div id="newsFeed" class="news-feed">
          <AegisEmptyState v-if="!displayPosts.length" icon="file-text" title="No posts yet"
            subtitle="No posts match this filter. Try a different category or create the first one.">
            <template #actions>
              <button type="button" class="btn btn-primary" @click="modals.createPost = true">
                <AegisIcon name="pencil" :size="14" /> Create Post
              </button>
            </template>
          </AegisEmptyState>

          <!-- Post cards -->
          <div v-for="post in displayPosts" :key="post.id"
               :class="['card', 'nf-post', { 'is-pinned': post.is_pinned }]">

            <!-- Pinned banner -->
            <div v-if="post.is_pinned" class="nf-pinned">
  <AegisIcon name="bookmark" :size="11" :filled="true" /> Pinned by Aegis Team
            </div>

            <!-- Post head -->
            <div class="nf-head">
              <div :class="['avatar', 'avatar-md', avatarMod(post), 'nf-avatar']"
                   :data-tooltip="'View ' + post.author_name"
                   @click="viewProfile(post.author_slug, authorKind(post))">{{ post.author_initials }}</div>
              <div class="nf-id">
                <div class="nf-id-top">
                  <span class="nf-author" @click="viewProfile(post.author_slug, authorKind(post))">{{ post.author_name }}</span>
                  <span v-if="post.is_self_post" class="nf-self">You</span>
                </div>
                <div :class="['nf-meta', 'nf-t-' + post.post_type]">
                  <span class="nf-type"><span class="nf-type-dot"></span>{{ typeLabel(post.post_type) }}</span>
                  <span class="nf-meta-sep"></span>
                  <span>{{ timeAgo(post.created_at) }}</span>
                </div>
              </div>
              <button v-if="post.is_self_post" type="button" class="btn-icon btn-icon-sm nf-more"
                      data-tooltip="Edit post" @click="openEdit(post)">
                <AegisIcon name="pencil" :size="13" />
              </button>
              <button v-else type="button" class="btn-icon btn-icon-sm nf-more"
                      data-tooltip="Report post" @click="reportPost(post.id)">
                <AegisIcon name="flag-2" :size="13" />
              </button>
            </div>

            <!-- Post body -->
            <div class="nf-body">
              <!-- Clickable title → detail modal -->
              <div v-if="post.title" class="nf-title nf-title-link" @click="openPostDetail(post)">{{ post.title }}</div>

              <div :class="['nf-text', { 'is-collapsed': postState(post.id)._collapsed }]">{{ post.body }}</div>
              <button v-if="post.body && post.body.length > 260" type="button" class="nf-readmore"
                      @click="toggleReadMore(post.id)">
                {{ postState(post.id)._collapsed ? 'Read more' : 'Show less' }}
                <AegisIcon :name="postState(post.id)._collapsed ? 'chevron-down' : 'chevron-up'" :size="11" />
              </button>

              <!-- Media grid -->
              <div v-if="post.media && post.media.length" class="nf-media"
                   :class="{ 'nf-media-single': post.media.length === 1, 'nf-media-multi': post.media.length > 1 }">
                <div v-for="(m, mi) in post.media.slice(0, 4)" :key="mi" class="nf-media-item"
                     @click="openPostDetail(post)">
                  <img v-if="m.type === 'image'" :src="m.url" :alt="m.name || 'Post image'"
                       class="nf-media-img" loading="lazy" />
                  <div v-else-if="m.type === 'video'" class="nf-media-video-placeholder">
                    <AegisIcon name="video" :size="24" />
                    <span>{{ m.name || 'Video' }}</span>
                  </div>
                  <div v-if="post.media.length > 4 && mi === 3" class="nf-media-overflow">
                    +{{ post.media.length - 4 }}
                  </div>
                </div>
              </div>

              <!-- Poll -->
              <div v-if="(post.post_type === 'poll' || post.post_type === 'question') && post.poll_question && post.poll_options && post.poll_options.length"
                   class="nf-poll">
                <div class="nf-poll-q">
                  <AegisIcon name="bar-chart" :size="14" />
                  {{ post.poll_question }}
                </div>
                <div v-for="(opt, idx) in post.poll_options" :key="opt.key || idx"
                     class="nf-poll-opt"
                     :class="{ 'is-voted': post.my_poll_vote === (opt.key || String(idx)), 'is-disabled': !!post.my_poll_vote }"
                     @click="votePoll(post, opt, idx)">
                  <span class="nf-poll-label">{{ opt.label }}</span>
                  <span class="nf-poll-track">
                    <span class="nf-poll-fill" :style="{ width: pollPct(post, idx) + '%' }"></span>
                  </span>
                  <span class="nf-poll-pct">{{ pollPct(post, idx) }}%</span>
                  <AegisIcon v-if="post.my_poll_vote === (opt.key || String(idx))" name="check" :size="12" style="color:var(--green-dark);flex-shrink:0" />
                </div>
                <div class="nf-poll-meta">
                  {{ pollTotal(post) }} vote{{ pollTotal(post) === 1 ? '' : 's' }}
                  <template v-if="post.poll_closes_at"> &middot; Closes {{ fmtDate(post.poll_closes_at) }}</template>
                  <template v-if="post.my_poll_vote"> &middot; <span style="color:var(--green-dark);font-weight:700">✓ You voted</span></template>
                </div>
              </div>

              <!-- Inline links -->
              <div v-if="post.links && post.links.length" class="nf-links">
                <a v-for="lnk in post.links" :key="lnk.url" :href="lnk.url"
                   class="nf-link" target="_blank" rel="noopener">
                  <span class="nf-link-ic"><AegisIcon name="link" :size="14" /></span>
                  <span class="nf-link-label">{{ lnk.label || lnk.url }}</span>
                  <span class="nf-link-go"><AegisIcon name="chevron-right" :size="12" /></span>
                </a>
              </div>

              <!-- Tags -->
              <div v-if="post.tags && post.tags.length" class="nf-tags">
                <button v-for="tag in post.tags" :key="tag" type="button" class="nf-tag"
                        @click="filterByTag(tag)">#{{ tag }}</button>
              </div>
            </div>

            <!-- Actions row -->
            <div class="nf-actions">
              <button type="button" :class="['nf-act', { 'is-liked': post.is_liked }]"
                      :data-tooltip="post.is_liked ? 'Unlike' : 'Like'"
                      @click="toggleLike(post)">
                <AegisIcon name="heart" :size="15" :filled="post.is_liked" />
                <span>{{ post.like_count }}</span>
              </button>
              <button type="button" :class="['nf-act', { 'is-commented': postState(post.id)._commentsOpen }]"
                      data-tooltip="Comments"
                      @click="toggleComments(post.id)">
                <AegisIcon name="message-square" :size="15" :filled="postState(post.id)._commentsOpen" />
                <span>{{ post.comment_count }}</span>
              </button>
              <button type="button" class="nf-act" data-tooltip="Share" @click="sharePost(post)">
                <AegisIcon name="share" :size="15" />
              </button>
              <span class="nf-act-spacer"></span>
              <template v-if="post.is_self_post">
                <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Edit post" @click="openEdit(post)">
                  <AegisIcon name="pencil" :size="13" />
                </button>
                <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Delete post" @click="confirmDelete(post.id)">
                  <AegisIcon name="trash" :size="13" />
                </button>
              </template>
              <button v-else type="button" :class="['nf-act', { 'is-saved': post.is_saved }]"
                      :data-tooltip="post.is_saved ? 'Unsave' : 'Save'" @click="toggleSave(post)">
                <AegisIcon name="bookmark" :size="15" :filled="post.is_saved" />
              </button>
            </div>

            <!-- Comments section -->
            <div :class="['nf-comments', { 'is-open': postState(post.id)._commentsOpen }]">
              <div v-for="c in commentsByPost[post.id] || []" :key="c.id" class="nf-comment">
                <div :class="['avatar', 'avatar-xs', 'avatar-gold', 'nf-avatar']"
                     :data-tooltip="'View ' + c.author_name"
                     @click="viewProfile(c.author_slug, authorKindComment(c))">{{ c.author_initials }}</div>
                <div class="nf-comment-main">
                  <div class="nf-comment-top">
                    <span class="nf-comment-author" @click="viewProfile(c.author_slug, authorKindComment(c))">{{ c.author_name }}</span>
                    <span v-if="c.is_self" class="nf-self">You</span>
                  </div>
                  <div class="nf-comment-body">{{ c.body }}</div>
                  <div class="nf-comment-meta">
                    {{ timeAgo(c.created_at) }}
                    <template v-if="c.like_count > 0"> &middot; {{ c.like_count }} like{{ c.like_count === 1 ? '' : 's' }}</template>
                  </div>
                </div>
              </div>
              <div class="nf-comment-form">
                <div :class="['avatar', 'avatar-xs', meAvatarMod]">{{ meInitials }}</div>
                <input type="text" class="form-input form-input-sm" placeholder="Write a comment…"
                       v-model="commentInputs[post.id]"
                       @keydown.enter.prevent="submitComment(post)" />
                <button type="button" class="btn-icon btn-icon-primary" data-tooltip="Send"
                        @click="submitComment(post)">
                  <AegisIcon name="send" :size="14" />
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ═══ RIGHT SIDEBAR ═══ -->
      <aside class="news-sidebar">

        <!-- Upcoming Events — keeps card, links to /events -->
        <div class="card nsc-card">
          <div class="card-header">
            <div class="card-title" style="display:flex;align-items:center;gap:6px">
              <AegisIcon name="calendar" :size="15" /> Upcoming Events
            </div>
            <a :href="route('provider.events.index')" class="btn btn-outline btn-sm">View all</a>
          </div>
          <div class="card-body">
            <div v-if="!upcoming.length" style="font-size:12px;color:var(--text-3);text-align:center;padding:14px 0">
              No upcoming events
            </div>
            <div v-for="e in upcoming" :key="e.id" class="ne-event" @click="openEventDetail(e)">
              <div class="ne-date">
                <span class="ne-date-mon">{{ fmtEventMon(e.starts_at) }}</span>
                <span class="ne-date-day">{{ fmtEventDay(e.starts_at) }}</span>
              </div>
              <div class="ne-body">
                <div class="ne-title">{{ e.title }}</div>
                <div class="ne-meta">
                  <AegisIcon name="clock" :size="11" />
                  <span class="ne-meta-time">{{ fmtEventTime(e.starts_at, e.ends_at) }}</span>
                  <template v-if="e.location">
                    <span class="ne-meta-sep"></span>
                    <span class="ne-meta-loc">{{ e.location }}</span>
                  </template>
                </div>
                <div v-if="e.ceu_credits > 0 || e.is_free" class="ne-tags">
                  <span v-if="e.ceu_credits > 0" class="ne-tag is-ceu">
                    <AegisIcon name="award" :size="10" /> {{ e.ceu_credits }} CEU{{ e.ceu_credits == 1 ? '' : 's' }}
                  </span>
                  <span v-if="e.is_free" class="ne-tag is-free">Free</span>
                </div>
              </div>
              <span class="ne-go"><AegisIcon name="chevron-right" :size="15" /></span>
            </div>
          </div>
        </div>

        <!-- Trending Topics -->
        <div class="card nsc-card">
          <div class="card-header">
            <div class="card-title" style="display:flex;align-items:center;gap:6px">
              <AegisIcon name="chart-trend" :size="15" /> Trending Topics
            </div>
          </div>
          <div class="card-body">
            <div v-if="!trending.length" style="font-size:12px;color:var(--text-3);text-align:center;padding:14px 0">No trends yet</div>
            <div v-for="t in trending" :key="t.tag" class="nt-row" @click="filterByTag(t.tag)">
              <span class="nt-tag">#{{ t.tag }}</span>
              <span class="nt-count">{{ t.post_count }} posts</span>
            </div>
          </div>
        </div>

      </aside>
    </div>

    <!-- ══════════ MODALS ══════════ -->

    <!-- Post Detail Modal -->
    <AegisModal v-model="modals.postDetail" size="lg" :title="detailPost?.title || 'Post'">
      <template v-if="detailPost">
        <!-- Author + meta -->
        <div class="pd-head">
          <div :class="['avatar', 'avatar-md', avatarMod(detailPost), 'nf-avatar']"
               @click="viewProfile(detailPost.author_slug, authorKind(detailPost))">
            {{ detailPost.author_initials }}
          </div>
          <div>
            <div style="font-size:14px;font-weight:700;color:var(--text)">{{ detailPost.author_name }}</div>
            <div :class="['nf-meta', 'nf-t-' + detailPost.post_type, 'pd-post-meta']">
              <span class="nf-type"><span class="nf-type-dot"></span>{{ typeLabel(detailPost.post_type) }}</span>
              <span class="nf-meta-sep"></span>
              <span>{{ timeAgo(detailPost.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Media -->
        <div v-if="detailPost.media && detailPost.media.length" class="pd-media">
          <div v-for="(m, i) in detailPost.media" :key="i" class="pd-media-item">
            <img v-if="m.type === 'image'" :src="m.url" :alt="m.name" class="pd-media-img" />
          </div>
        </div>

        <!-- Body -->
        <div class="pd-body">{{ detailPost.body }}</div>

        <!-- Poll -->
        <div v-if="(detailPost.post_type === 'poll' || detailPost.post_type === 'question') && detailPost.poll_question" class="nf-poll">
          <div class="nf-poll-q"><AegisIcon name="bar-chart" :size="14" /> {{ detailPost.poll_question }}</div>
          <div v-for="(opt, idx) in detailPost.poll_options" :key="opt.key || idx"
               class="nf-poll-opt"
               :class="{ 'is-voted': detailPost.my_poll_vote === (opt.key || String(idx)), 'is-disabled': !!detailPost.my_poll_vote }"
               @click="votePoll(detailPost, opt, idx)">
            <span class="nf-poll-label">{{ opt.label }}</span>
            <span class="nf-poll-track"><span class="nf-poll-fill" :style="{ width: pollPct(detailPost, idx) + '%' }"></span></span>
            <span class="nf-poll-pct">{{ pollPct(detailPost, idx) }}%</span>
            <AegisIcon v-if="detailPost.my_poll_vote === (opt.key || String(idx))" name="check" :size="12" style="color:var(--green-dark)" />
          </div>
          <div class="nf-poll-meta">
            {{ pollTotal(detailPost) }} vote{{ pollTotal(detailPost) === 1 ? '' : 's' }}
            <template v-if="detailPost.my_poll_vote"> &middot; <span style="color:var(--green-dark);font-weight:700">✓ You voted</span></template>
          </div>
        </div>

        <!-- Links -->
        <div v-if="detailPost.links && detailPost.links.length" class="nf-links pd-section">
          <a v-for="lnk in detailPost.links" :key="lnk.url" :href="lnk.url" class="nf-link" target="_blank" rel="noopener">
            <span class="nf-link-ic"><AegisIcon name="link" :size="14" /></span>
            <span class="nf-link-label">{{ lnk.label || lnk.url }}</span>
            <span class="nf-link-go"><AegisIcon name="chevron-right" :size="12" /></span>
          </a>
        </div>

        <!-- Tags -->
        <div v-if="detailPost.tags && detailPost.tags.length" class="nf-tags pd-section">
          <button v-for="tag in detailPost.tags" :key="tag" type="button" class="nf-tag" @click="modals.postDetail=false;filterByTag(tag)">#{{ tag }}</button>
        </div>

        <!-- Divider + action bar -->
        <div class="pd-actions">
          <button type="button" :class="['nf-act', { 'is-liked': detailPost.is_liked }]"
                  :data-tooltip="detailPost.is_liked ? 'Unlike' : 'Like'"
                  @click="toggleLike(detailPost)">
            <AegisIcon name="heart" :size="15" :filled="detailPost.is_liked" /><span>{{ detailPost.like_count }}</span>
          </button>
          <button type="button" class="nf-act" data-tooltip="Share" @click="sharePost(detailPost)">
            <AegisIcon name="share" :size="15" />
          </button>
          <span class="nf-act-spacer"></span>
          <button v-if="!detailPost.is_self_post" type="button"
                  :class="['nf-act', { 'is-saved': detailPost.is_saved }]"
                  :data-tooltip="detailPost.is_saved ? 'Unsave' : 'Save'"
                  @click="toggleSave(detailPost)">
            <AegisIcon name="bookmark" :size="15" :filled="detailPost.is_saved" />
          </button>
        </div>

        <!-- Comments in detail modal -->
        <div class="pd-comments">
          <div class="pd-comment-count">
            {{ (commentsByPost[detailPost.id] || []).length }} Comment{{ (commentsByPost[detailPost.id] || []).length === 1 ? '' : 's' }}
          </div>
          <div v-for="c in commentsByPost[detailPost.id] || []" :key="c.id" class="nf-comment">
            <div :class="['avatar', 'avatar-xs', 'avatar-gold']">{{ c.author_initials }}</div>
            <div class="nf-comment-main">
              <div class="nf-comment-top">
                <span class="nf-comment-author">{{ c.author_name }}</span>
                <span v-if="c.is_self" class="nf-self">You</span>
              </div>
              <div class="nf-comment-body">{{ c.body }}</div>
              <div class="nf-comment-meta">{{ timeAgo(c.created_at) }}</div>
            </div>
          </div>
          <div class="nf-comment-form">
            <div :class="['avatar', 'avatar-xs', meAvatarMod]">{{ meInitials }}</div>
            <input type="text" class="form-input form-input-sm" placeholder="Write a comment…"
                   v-model="commentInputs[detailPost.id]"
                   @keydown.enter.prevent="submitComment(detailPost)" />
            <button type="button" class="btn-icon btn-icon-primary" data-tooltip="Send"
                    @click="submitComment(detailPost)">
              <AegisIcon name="send" :size="14" />
            </button>
          </div>
        </div>
      </template>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.postDetail = false">Close</button>
      </template>
    </AegisModal>

    <!-- Event Detail Modal — centralized EventDetailModal component -->
    <EventDetailModal
      v-model="modals.eventDetail"
      :event="detailEvent"
      :is-registered="detailEvent ? sidebarRegisteredIds.has(detailEvent.id) : false"
      @register="openEventRegisterModal"
      @cancel="openEventCancelModal"
    />


    <!-- Event Register Confirm Modal -->
    <AegisModal v-model="modals.eventRegisterConfirm" title="Confirm Registration" size="sm">
      <div class="evt-detail-heading" style="text-align:center">
        <div class="evt-detail-eyebrow">You're registering for</div>
        <div class="evt-detail-title">{{ pendingEvent?.title }}</div>
      </div>
      <p style="text-align:center;font-size:13px;color:var(--text-3);margin-bottom:18px">Confirmation will be emailed to you.</p>
      <div class="form-group">
        <label class="form-label">Preferred contact email</label>
        <input v-model="eventRegForm.email" type="email" class="form-input" placeholder="your@email.com" />
      </div>
      <div class="form-group">
        <label class="form-label">Dietary restrictions or accessibility needs</label>
        <input v-model="eventRegForm.notes" type="text" class="form-input" placeholder="Optional" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.eventRegisterConfirm = false">Cancel</button>
        <button class="btn btn-primary" @click="confirmEventRegistration">Confirm Registration →</button>
      </template>
    </AegisModal>

    <!-- Event Cancel Confirm Modal -->
    <AegisModal v-model="modals.eventCancelConfirm" title="Cancel Registration" size="sm">
      <div class="evt-detail-heading" style="text-align:center">
        <div class="evt-detail-eyebrow">Are you sure?</div>
        <div class="evt-detail-title">{{ pendingEvent?.title }}</div>
      </div>
      <p style="text-align:center;font-size:13px;color:var(--text-3);margin-bottom:6px">
        Your spot will be released and you'll receive a cancellation confirmation.
      </p>
      <template #footer>
        <button class="btn btn-outline" @click="modals.eventCancelConfirm = false">Keep Registration</button>
        <button class="btn btn-danger" @click="confirmEventCancel">Yes, Cancel</button>
      </template>
    </AegisModal>


    <!-- Create Post Modal -->
    <AegisModal v-model="modals.createPost" size="lg" title="Create Post">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Post Type <span class="required">*</span></label>
          <select class="form-select" v-model="createForm.post_type">
            <option value="provider">General Post</option>
            <option value="question">Ask Community / Quiz</option>
            <option value="resource">Share Resource</option>
            <option value="milestone">Milestone</option>
                      </select>
        </div>
        <div class="form-group">
          <label class="form-label">Audience</label>
          <select class="form-select" v-model="createForm.audience">
            <option value="all">All members</option>
            <option value="providers">Providers only</option>
            <option value="stewards">Stewards only</option>
            <option value="business_partners">Business partners only</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
        <input type="text" class="form-input" v-model="createForm.title"
               :placeholder="createTitlePlaceholder" maxlength="160" />
      </div>

      <div class="form-group">
        <label class="form-label">
          {{ createForm.post_type === 'question' ? 'Context / Description' : 'Content' }}
          <span v-if="createForm.post_type !== 'question'" class="required">*</span>
          <span v-else style="color:var(--text-4);font-weight:600"> (optional)</span>
        </label>
        <textarea class="form-textarea" v-model="createForm.body" rows="4" maxlength="2000"
                  :class="{ 'is-error': createFieldError('body') }"
                  :placeholder="createBodyPlaceholder"
                  @blur="vCreate.body?.$touch()" />
        <div v-if="createFieldError('body')" class="form-error">{{ createFieldError('body') }}</div>
        <div class="form-hint">{{ createForm.body.length }} / 2000</div>
      </div>

      <!-- Poll / Quiz builder — shown for 'question' type -->
      <template v-if="createForm.post_type === 'question'">
        <div class="form-group">
          <label class="form-label">Poll Question <span class="required">*</span></label>
          <input type="text" class="form-input"
                 :class="{ 'is-error': createFieldError('poll_question') }"
                 v-model="createForm.poll_question"
                 placeholder="What would you like to ask?" maxlength="300"
                 @blur="vCreate.poll_question?.$touch()" />
          <div v-if="createFieldError('poll_question')" class="form-error">{{ createFieldError('poll_question') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Options <span class="required">*</span> <span style="color:var(--text-4);font-weight:600">(2–4)</span></label>
          <div class="poll-options-builder">
            <div v-for="(opt, idx) in pollOptions" :key="idx" class="poll-option-row">
              <span class="poll-option-num">{{ idx + 1 }}</span>
              <input type="text" class="form-input" v-model="opt.label" :placeholder="'Option ' + (idx + 1)" maxlength="120" />
              <button v-if="pollOptions.length > 2" type="button" class="btn-icon btn-icon-sm btn-icon-danger"
                      data-tooltip="Remove" @click="removePollOption(idx)">
                <AegisIcon name="x" :size="13" />
              </button>
            </div>
            <button v-if="pollOptions.length < 4" type="button" class="btn btn-outline btn-sm poll-add-btn" @click="addPollOption">
              <AegisIcon name="plus" :size="13" /> Add Option
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Poll Closes <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
          <input type="date" class="form-input" v-model="createForm.poll_closes_at" :min="new Date().toISOString().split('T')[0]" />
        </div>
      </template>

      <!-- Resource link -->
      <template v-if="createForm.post_type === 'resource'">
        <div class="form-group">
          <label class="form-label">Resource URL <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
          <input type="url" class="form-input" v-model="createForm.resource_url" placeholder="https://…" />
        </div>
      </template>

      <!-- Media upload — AegisDropzone (canonical file input component) -->
      <div class="form-group">
        <label class="form-label">Photo / Video <span style="color:var(--text-4);font-weight:600">(optional, up to 4)</span></label>
        <AegisDropzone
          accept="image/*,video/*"
          hint="JPG, PNG, GIF, MP4 — max 20 MB each"
          :multiple="true"
          :max-size-mb="20"
          @files="onMediaFiles"
        />
      </div>

      <div class="form-group">
        <label class="form-label">Tags <span style="color:var(--text-4);font-weight:600">(comma-separated)</span></label>
        <input type="text" class="form-input" v-model="createForm.tags" placeholder="e.g. Telehealth, Compliance, Workflow" />
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.createPost = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="createForm.processing" @click="submitCreatePost">
          <AegisIcon name="send" :size="14" /> Publish Post
        </button>
      </template>
    </AegisModal>

    <!-- Edit Post Modal — fully post-type aware -->
    <AegisModal v-model="modals.editPost" size="lg" :title="'Edit — ' + typeLabel(editForm.post_type || 'provider')">
      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
        <input type="text" class="form-input" v-model="editForm.title" maxlength="160"
               :placeholder="editForm.post_type === 'question' ? 'Question or quiz title' : 'Post headline'" />
      </div>
      <div class="form-group">
        <label class="form-label">
          {{ editForm.post_type === 'question' ? 'Context / Description' : 'Content' }}
          <span v-if="editForm.post_type !== 'question'" class="required">*</span>
          <span v-else style="color:var(--text-4);font-weight:600"> (optional)</span>
        </label>
        <textarea class="form-textarea" v-model="editForm.body" rows="5" maxlength="2000"
                  :class="{ 'is-error': editFieldError('body') }"
                  @blur="vEdit.body?.$touch()" />
        <div v-if="editFieldError('body')" class="form-error">{{ editFieldError('body') }}</div>
        <div class="form-hint">{{ editForm.body.length }} / 2000</div>
      </div>
      <template v-if="editForm.post_type === 'question'">
        <div class="form-group">
          <label class="form-label">Poll Question <span class="required">*</span></label>
          <input type="text" class="form-input" v-model="editForm.poll_question"
                 :class="{ 'is-error': editFieldError('poll_question') }"
                 placeholder="What would you like to ask?" maxlength="300"
                 @blur="vEdit.poll_question?.$touch()" />
          <div v-if="editFieldError('poll_question')" class="form-error">{{ editFieldError('poll_question') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Options <span class="required">*</span> <span style="color:var(--text-4);font-weight:600">(2–4)</span></label>
          <div class="poll-options-builder">
            <div v-for="(opt, idx) in editPollOptions" :key="idx" class="poll-option-row">
              <span class="poll-option-num">{{ idx + 1 }}</span>
              <input type="text" class="form-input" v-model="opt.label" :placeholder="'Option ' + (idx + 1)" maxlength="120" />
              <button v-if="editPollOptions.length > 2" type="button" class="btn-icon btn-icon-sm btn-icon-danger"
                      data-tooltip="Remove" @click="editPollOptions.splice(idx, 1)">
                <AegisIcon name="x" :size="13" />
              </button>
            </div>
            <button v-if="editPollOptions.length < 4" type="button" class="btn btn-outline btn-sm poll-add-btn"
                    @click="editPollOptions.push({ label: '' })">
              <AegisIcon name="plus" :size="13" /> Add Option
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Poll Closes <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
          <input type="date" class="form-input" v-model="editForm.poll_closes_at"
                 :min="new Date().toISOString().split('T')[0]" />
        </div>
      </template>
      <template v-if="editForm.post_type === 'resource'">
        <div class="form-group">
          <label class="form-label">Resource URL <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
          <input type="url" class="form-input" v-model="editForm.resource_url" placeholder="https://…" />
        </div>
      </template>
      <div class="form-group">
        <label class="form-label">Tags <span style="color:var(--text-4);font-weight:600">(comma-separated)</span></label>
        <input type="text" class="form-input" v-model="editForm.tags"
               placeholder="e.g. Telehealth, Compliance, Workflow" />
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.editPost = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="editForm.processing" @click="submitEditPost">
          <AegisIcon name="check" :size="14" /> Save Changes
        </button>
      </template>
    </AegisModal>


    <!-- Share Modal -->
    <AegisModal v-model="modals.sharePost" size="sm" title="Share Post">
      <div class="form-group">
        <label class="form-label">Post link</label>
        <input type="text" class="form-input" :value="shareUrl" readonly />
        <div class="form-hint">Anyone with this link can view the post.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.sharePost = false">Close</button>
        <button type="button" class="btn btn-primary" @click="copyShareUrl">
          <AegisIcon name="copy" :size="14" /> Copy Link
        </button>
      </template>
    </AegisModal>

    <!-- My Library Modal -->
    <AegisModal v-model="modals.myLibrary" size="lg" title="My Library">
      <div class="tabs-segmented">
        <button type="button" :class="['tab-pill', { active: libraryTab === 'saved' }]" @click="libraryTab = 'saved'">
          <AegisIcon name="bookmark" :size="12" :filled="libraryTab === 'saved'" /> Saved
          <span v-if="librarySaved.length" class="badge-pill">{{ librarySaved.length }}</span>
        </button>
        <button type="button" :class="['tab-pill', { active: libraryTab === 'reported' }]" @click="libraryTab = 'reported'">
          <AegisIcon name="flag-2" :size="12" /> Reported
          <span v-if="libraryReported.length" class="badge-pill">{{ libraryReported.length }}</span>
        </button>
      </div>

      <div v-if="libraryLoading" style="text-align:center;padding:32px 0;color:var(--text-4);font-size:13px">Loading…</div>

      <template v-else-if="libraryTab === 'saved'">
        <AegisEmptyState v-if="!librarySaved.length" icon="bookmark" title="No saved posts"
          subtitle="Tap the bookmark icon on any post to save it here." />
        <div v-else class="lib-table">
          <div class="lib-table-head">
            <span>Post</span><span>Type</span><span>Author</span><span></span>
          </div>
          <div v-for="p in librarySaved" :key="p.id" class="lib-table-row">
            <div>
              <div class="lib-post-title lib-post-clickable" @click="openPostFromLibrary(p)">
                {{ p.title || p.body?.slice(0, 55) || '(no content)' }}{{ (!p.title && p.body?.length > 55) ? '…' : '' }}
              </div>
              <div class="lib-post-body">{{ p.body?.slice(0, 70) }}{{ p.body?.length > 70 ? '…' : '' }}</div>
            </div>
            <span><span :class="['badge', typeBadgeClass(p.post_type)]">{{ typeLabel(p.post_type) }}</span></span>
            <span style="font-size:12px;color:var(--text-3)">{{ p.author_name }}</span>
            <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Unsave" @click="unsaveFromLibrary(p)">
              <AegisIcon name="bookmark" :size="13" />
            </button>
          </div>
        </div>
      </template>

      <template v-else>
        <AegisEmptyState v-if="!libraryReported.length" icon="flag-2" title="No reported posts"
          subtitle="Posts you've flagged for review appear here." />
        <div v-else class="lib-table">
          <div class="lib-table-head">
            <span>Post</span><span>Type</span><span>Author</span><span>Reported</span>
          </div>
          <div v-for="p in libraryReported" :key="p.id" class="lib-table-row">
            <div>
              <div class="lib-post-title">{{ p.title || '(untitled)' }}</div>
              <div class="lib-post-body">{{ p.body }}</div>
            </div>
            <span><span :class="['badge', typeBadgeClass(p.post_type)]">{{ typeLabel(p.post_type) }}</span></span>
            <span style="font-size:12px;color:var(--text-3)">{{ p.author_name }}</span>
            <span style="font-size:11px;color:var(--text-4)">{{ fmtDate(p.reported_at) }}</span>
          </div>
        </div>
      </template>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.myLibrary = false">Close</button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { router, useForm, usePage as useInertiaPage } from '@inertiajs/vue3'
import EventDetailModal from '@/components/modals/EventDetailModal.vue'
import AegisDropzone    from '@/components/ui/AegisDropzone.vue'
import AppLayout       from '@/layouts/AppLayout.vue'
import { useToast }    from '@/composables/useToast'
import { useConfirm }  from '@/composables/useConfirm'
import { useActivity } from '@/composables/useActivity'
import { useProfileRoute } from '@/composables/useProfileRoute'
import useVuelidate    from '@vuelidate/core'
import { required, minLength, helpers } from '@vuelidate/validators'

const props = defineProps({
  posts:          { type: Array,  default: () => [] },
  upcoming:       { type: Array,  default: () => [] },
  trending:       { type: Array,  default: () => [] },
  commentsByPost: { type: Object, default: () => ({}) },
  countToday:     { type: Number, default: 0 },
  countAuthors:   { type: Number, default: 0 },
  countUpcoming:  { type: Number, default: 0 },
  countByType:    { type: Object, default: () => ({ all: 0, platform: 0, provider: 0, question: 0, resource: 0, poll: 0 }) },
  activeFilter:   { type: String, default: 'all' },
  activeTag:      { type: String, default: '' },
})

const toast             = useToast()
const { confirmAction } = useConfirm()
const { timeAgo }       = useActivity()
const { viewProfile }   = useProfileRoute()
const page              = useInertiaPage()

const meInitials  = computed(() => page.props.auth?.user?.avatar_initials ?? 'SJ')
const meAvatarMod = 'avatar-gold'

// ── per-post UI state ────────────────────────────────────────────────────────
const uiState = reactive({})
function postState(id) {
  if (!uiState[id]) {
    const post = localPosts.value.find(p => p.id === id)
    uiState[id] = { _collapsed: (post?.body ?? '').length > 260, _commentsOpen: false }
  }
  return uiState[id]
}

// ── filter / sort ─────────────────────────────────────────────────────────────
const activeFilter   = ref(props.activeFilter)
const localActiveTag = ref(props.activeTag)
const activeSort     = ref('recent')

function applyFilter() {
  router.get(route('provider.news.index'), {
    filter: activeFilter.value !== 'all' ? activeFilter.value : undefined,
    tag:    localActiveTag.value || undefined,
  }, { preserveScroll: true, preserveState: false })
}
function filterByTag(tag) {
  localActiveTag.value = tag
  router.get(route('provider.news.index'), {
    filter: activeFilter.value !== 'all' ? activeFilter.value : undefined,
    tag,
  }, { preserveScroll: true, preserveState: false })
}
function clearTagFilter() {
  localActiveTag.value = ''
  router.get(route('provider.news.index'), {
    filter: activeFilter.value !== 'all' ? activeFilter.value : undefined,
  }, { preserveScroll: true, preserveState: false })
}

const localPosts = ref([...props.posts])
// Sync when Inertia reloads props after a server round-trip (replaces optimistic entries with real data)
watch(() => props.posts, (fresh) => { localPosts.value = [...fresh] })

const displayPosts = computed(() => {
  let list = [...localPosts.value]
  if (localActiveTag.value) {
    list = list.filter(p => (p.tags ?? []).includes(localActiveTag.value))
  }
  const pinned = list.filter(p => p.is_pinned)
  const rest   = list.filter(p => !p.is_pinned)
  if (activeSort.value === 'popular') rest.sort((a, b) => (b.like_count ?? 0) - (a.like_count ?? 0))
  return [...pinned, ...rest]
})

// ── read more ────────────────────────────────────────────────────────────────
function toggleReadMore(id) { postState(id)._collapsed = !postState(id)._collapsed }

// ── comments ─────────────────────────────────────────────────────────────────
const commentInputs = reactive({})
function toggleComments(id) { postState(id)._commentsOpen = !postState(id)._commentsOpen }
function submitComment(post) {
  const text = (commentInputs[post.id] ?? '').trim()
  if (!text) return
  router.post(route('provider.news.comment', { post: post.id }), { body: text }, {
    preserveScroll: true, preserveState: true,
    onSuccess: () => { commentInputs[post.id] = ''; toast.success('Comment posted') },
    onError:   () => toast.error('Could not post comment.'),
  })
}

// ── like ─────────────────────────────────────────────────────────────────────
function toggleLike(post) {
  post.is_liked   = !post.is_liked
  post.like_count = post.is_liked ? (post.like_count + 1) : Math.max(0, post.like_count - 1)
  router.post(route('provider.news.react', { post: post.id }), { reaction_type: 'like' }, {
    preserveScroll: true, preserveState: true,
    onError: () => {
      post.is_liked   = !post.is_liked
      post.like_count = post.is_liked ? (post.like_count + 1) : Math.max(0, post.like_count - 1)
    },
  })
}

// ── save ─────────────────────────────────────────────────────────────────────
function toggleSave(post) {
  post.is_saved = !post.is_saved
  toast.success(post.is_saved ? 'Saved to your library' : 'Removed from saved')
  router.post(route('provider.news.react', { post: post.id }), { reaction_type: 'save' }, {
    preserveScroll: true, preserveState: true,
    onError: () => { post.is_saved = !post.is_saved; toast.error('Could not save post.') },
  })
}

// ── share ─────────────────────────────────────────────────────────────────────
const shareUrl = ref('')
function sharePost(post) {
  shareUrl.value = window.location.origin + '/provider/news?post=' + post.id
  modals.sharePost = true
}
function copyShareUrl() {
  navigator.clipboard?.writeText(shareUrl.value).catch(() => {})
  toast.success('Link copied to clipboard')
}

// ── report ───────────────────────────────────────────────────────────────────
function reportPost(postId) {
  confirmAction('Report this post for review by the Aegis Trust & Safety team?', () => {
    router.post(route('provider.news.react', { post: postId }), { reaction_type: 'report' }, {
      preserveScroll: true, preserveState: true,
      onSuccess: () => toast.info('Reported — our team will review shortly'),
      onError:   () => toast.error('Could not submit report.'),
    })
  })
}

// ── poll ─────────────────────────────────────────────────────────────────────
function pollTotal(post) {
  return (post.poll_options ?? []).reduce((s, o) => s + (o.votes ?? 0), 0) || 0
}
function pollPct(post, idx) {
  const total = pollTotal(post) || 1
  return Math.round(((post.poll_options?.[idx]?.votes ?? 0) / total) * 100)
}
function votePoll(post, opt, idx) {
  if (post.my_poll_vote) { toast.info('You already voted in this poll'); return }
  if (String(post.id).startsWith('_opt_')) { toast.info('Post is still saving — try again in a moment.'); return }
  const key = opt.key || String(idx)
  post.my_poll_vote = key
  if (post.poll_options?.[idx]) post.poll_options[idx].votes = (post.poll_options[idx].votes ?? 0) + 1
  router.post(route('provider.news.vote', { post: post.id }), { option_key: key }, {
    preserveScroll: true, preserveState: true,
    onSuccess: () => toast.success('Vote recorded'),
    onError:   () => {
      post.my_poll_vote = null
      if (post.poll_options?.[idx]) post.poll_options[idx].votes = Math.max(0, (post.poll_options[idx].votes ?? 1) - 1)
      toast.error('Could not record vote.')
    },
  })
}

// ── events — full parity with Events.vue ─────────────────────────────────────
const detailEvent         = ref(null)
const pendingEvent        = ref(null)
const eventRegForm        = reactive({ email: '', notes: '' })
const sidebarRegisteredIds = ref(new Set(
  (props.upcoming ?? []).filter(e => e.is_attending).map(e => e.id)
))

function openEventDetail(e) { detailEvent.value = e; modals.eventDetail = true }

function openEventRegisterModal(ev) {
  if (sidebarRegisteredIds.value.has(ev.id)) {
    toast.info(`You're already registered for ${ev.title}`)
    return
  }
  pendingEvent.value = ev
  eventRegForm.email = ''
  eventRegForm.notes = ''
  modals.eventRegisterConfirm = true
}

function confirmEventRegistration() {
  if (!pendingEvent.value) return
  router.post(route('provider.news.rsvp', { event: pendingEvent.value.id }), { status: 'going' }, {
    preserveScroll: true,
    onSuccess: () => {
      sidebarRegisteredIds.value.add(pendingEvent.value.id)
      modals.eventRegisterConfirm = false
      toast.success(`Registered for ${pendingEvent.value.title} — check your email.`)
    },
    onError: () => { modals.eventRegisterConfirm = false; toast.error('Registration failed.') },
  })
}

function openEventCancelModal(ev) {
  pendingEvent.value = ev
  modals.eventCancelConfirm = true
}

function confirmEventCancel() {
  if (!pendingEvent.value) return
  router.delete(route('provider.news.events.cancel', { event: pendingEvent.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      sidebarRegisteredIds.value.delete(pendingEvent.value.id)
      modals.eventCancelConfirm = false
      toast.info('Registration cancelled')
    },
    onError: () => { modals.eventCancelConfirm = false; toast.error('Cancellation failed.') },
  })
}

// Category helpers — matches Events.vue exactly
const CAT_MAP    = { webinar: 'webinar', conference: 'conference', training: 'training', networking: 'networking', workshop: 'workshop' }
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
function evtCategoryLabel(ev) { return CAT_LABELS[evtCategory(ev)] ?? 'Event' }
function fmtCeu(n) {
  if (n == null) return '0'
  const v = parseFloat(n)
  return v % 1 === 0 ? String(Math.round(v)) : String(parseFloat(v.toFixed(1)))
}

// ── post detail modal ────────────────────────────────────────────────────────
const detailPost = ref(null)
function openPostDetail(post) { detailPost.value = post; modals.postDetail = true }
function openPostFromLibrary(p) {
  modals.myLibrary = false
  const found = localPosts.value.find(post => post.id === p.id)
  if (found) { detailPost.value = found; modals.postDetail = true }
}

// ── delete ───────────────────────────────────────────────────────────────────
function confirmDelete(postId) {
  confirmAction('Delete this post? This action cannot be undone.', () => {
    router.delete(route('provider.news.destroy', { post: postId }), {
      preserveScroll: true,
      onSuccess: () => {
        localPosts.value = localPosts.value.filter(p => p.id !== postId)
        toast.info('Post deleted')
      },
      onError:   () => toast.error('Could not delete post.'),
    })
  })
}

// ── edit ─────────────────────────────────────────────────────────────────────
const editTargetId = ref(null)
const editPollOptions = reactive([{ label: '' }, { label: '' }])

// editForm holds ALL post-type fields so template can bind them
const editForm = useForm({
  title: '', body: '', post_type: 'provider',
  poll_question: '', poll_closes_at: '', poll_options: null,
  tags: '', resource_url: '',
})

const editRules = {
  body:          { requiredIfNotQuiz: helpers.withMessage(
    'Content is required.',
    (v) => editForm.post_type === 'question' || (v && v.trim().length > 0)
  )},
  poll_question: { requiredIfQuiz: helpers.withMessage(
    'Poll question is required.',
    (v) => editForm.post_type !== 'question' || (v && v.trim().length > 0)
  )},
}
const vEdit = useVuelidate(editRules, editForm)

// Normalize legacy 'poll' type → 'question' for the unified quiz/poll type
function normalizePostType(t) {
  return t === 'poll' ? 'question' : (t || 'provider')
}

function openEdit(post) {
  editTargetId.value    = post.id
  editForm.title        = post.title ?? ''
  editForm.body         = post.body  ?? ''
  // Normalize 'poll' → 'question' (they are the same type in the UI)
  editForm.post_type    = normalizePostType(post.post_type)
  editForm.poll_question = post.poll_question ?? ''
  editForm.poll_closes_at = post.poll_closes_at ? post.poll_closes_at.split('T')[0] : ''
  editForm.tags = (post.tags ?? []).join(', ')
  editForm.resource_url = (post.links && post.links[0]?.url) ? post.links[0].url : ''

  // Populate editable poll options from existing data
  const opts = post.poll_options ?? []
  editPollOptions.splice(0, editPollOptions.length)
  if (opts.length >= 2) {
    opts.forEach(o => editPollOptions.push({ label: o.label || '' }))
  } else {
    editPollOptions.push({ label: '' }, { label: '' })
  }

  editForm.clearErrors()
  vEdit.value.$reset()
  modals.editPost = true
}

async function submitEditPost() {
  const ok = await vEdit.value.$validate()
  if (!ok) return

  // Build poll_options from editable list
  if (editForm.post_type === 'question') {
    const valid = editPollOptions.filter(o => o.label.trim())
    if (valid.length < 2) { toast.error('Add at least 2 poll options.'); return }
    editForm.poll_options = valid.map((o, i) => ({ key: String(i), label: o.label.trim() }))
  }

  editForm.patch(route('provider.news.update', { post: editTargetId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      modals.editPost = false
      toast.success('Post updated')
      vEdit.value.$reset()
    },
    onError: () => toast.error('Could not update post.'),
  })
}

// ── media upload ─────────────────────────────────────────────────────────────
// ── media upload — AegisDropzone owns display; we just track the file array ────
const mediaFiles = ref([])

function onMediaFiles(files) {
  mediaFiles.value = files.slice(0, 4)
  if (files.length > 4) toast.error('Maximum 4 files per post.')
}

// ── poll builder ─────────────────────────────────────────────────────────────
const pollOptions = reactive([{ label: '' }, { label: '' }])
function addPollOption()       { if (pollOptions.length < 4) pollOptions.push({ label: '' }) }
function removePollOption(idx) { pollOptions.splice(idx, 1) }

// ── create ───────────────────────────────────────────────────────────────────
const createForm = useForm({
  post_type: 'provider', audience: 'all', title: '',
  body: '', tags: '', poll_question: '', poll_closes_at: '', resource_url: '',
})

// Vuelidate rules — use static rules + conditional check in submit for reliability
// (computed rules with useForm can have reactivity edge cases with TomSelect)
const createRules = {
  body:          { requiredIfNotQuiz: helpers.withMessage(
    'Content is required.',
    (v) => createForm.post_type === 'question' || (v && v.trim().length > 0)
  )},
  poll_question: { requiredIfQuiz: helpers.withMessage(
    'Poll question is required.',
    (v) => createForm.post_type !== 'question' || (v && v.trim().length > 0)
  )},
}
const vCreate = useVuelidate(createRules, createForm)


async function submitCreatePost() {
  const ok = await vCreate.value.$validate()
  if (!ok) return

  let resolvedPollOptions = null
  if (createForm.post_type === 'question') {
    const validOpts = pollOptions.filter(o => o.label.trim())
    if (validOpts.length < 2) { toast.error('Add at least 2 poll options.'); return }
    resolvedPollOptions = validOpts.map((o, i) => ({ key: String(i), label: o.label.trim(), votes: 0 }))
    createForm.poll_options = resolvedPollOptions
  }

  if (createForm.post_type === 'resource' && createForm.resource_url) {
    createForm.links = [{ label: 'View Resource', url: createForm.resource_url }]
  }

  // Read image files as base64 so the card can render them immediately after publish
  let resolvedMedia = []
  if (mediaFiles.value.length) {
    resolvedMedia = await Promise.all(
      mediaFiles.value.slice(0, 4).map(f => new Promise(resolve => {
        if (f.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = e => resolve({ type: 'image', name: f.name, url: e.target.result })
          reader.readAsDataURL(f)
        } else {
          resolve({ type: 'video', name: f.name, url: '' })
        }
      }))
    )
    createForm.media = resolvedMedia.map(m => ({ type: m.type, name: m.name, url: '' }))
  }

  // Snapshot before reset for the optimistic card
  const snapshot = {
    post_type:      createForm.post_type,
    audience:       createForm.audience,
    title:          createForm.title,
    body:           createForm.body,
    tags:           createForm.tags ? createForm.tags.split(',').map(t => t.trim()).filter(Boolean) : [],
    poll_question:  createForm.poll_question,
    poll_closes_at: createForm.poll_closes_at,
    poll_options:   resolvedPollOptions,
    media:          resolvedMedia,
    resource_url:   createForm.resource_url,
  }

  createForm.post(route('provider.news.store'), {
    preserveScroll: true,
    onSuccess: (page) => {
      const authUser = page.props?.auth?.user ?? {}
      const optimistic = {
        id:            '_opt_' + Date.now(),
        post_type:     snapshot.post_type,
        audience:      snapshot.audience,
        title:         snapshot.title || null,
        body:          snapshot.body || null,
        tags:          snapshot.tags,
        poll_question: snapshot.poll_question || null,
        poll_closes_at: snapshot.poll_closes_at || null,
        poll_options:  snapshot.poll_options,
        media:         snapshot.media,
        links:         snapshot.post_type === 'resource' && snapshot.resource_url
                         ? [{ label: 'View Resource', url: snapshot.resource_url }] : [],
        author_name:   authUser.name ?? 'You',
        author_slug:   authUser.slug ?? null,
        is_owner:      true,
        is_pinned:     false,
        like_count:    0,
        comment_count: 0,
        my_like:       false,
        my_poll_vote:  null,
        created_at:    new Date().toISOString(),
      }
      localPosts.value.unshift(optimistic)

      modals.createPost = false
      toast.success('Post published')
      createForm.reset()
      createForm.post_type = 'provider'
      createForm.audience  = 'all'
      mediaFiles.value = []
      pollOptions.splice(0, pollOptions.length, { label: '' }, { label: '' })
      vCreate.value.$reset()
    },
    onError: () => toast.error('Could not publish post.'),
  })
}

// ── my library ───────────────────────────────────────────────────────────────
const libraryTab      = ref('saved')
const libraryLoading  = ref(false)
const librarySaved    = ref([])
const libraryReported = ref([])

async function openMyLibrary() {
  modals.myLibrary = true
  libraryLoading.value = true
  try {
    const res = await fetch(route('provider.news.my-library'), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    const data = await res.json()
    librarySaved.value    = data.saved    ?? []
    libraryReported.value = data.reported ?? []
  } catch { toast.error('Could not load library.') }
  finally  { libraryLoading.value = false }
}
function unsaveFromLibrary(p) {
  router.post(route('provider.news.react', { post: p.id }), { reaction_type: 'save' }, {
    preserveScroll: true, preserveState: true,
    onSuccess: () => { librarySaved.value = librarySaved.value.filter(x => x.id !== p.id); toast.success('Removed from saved') },
    onError:   () => toast.error('Could not unsave.'),
  })
}

// ── helpers ───────────────────────────────────────────────────────────────────
const TYPE_LABELS = {
  platform: 'Platform', provider: 'General Post', post: 'Post',
  event: 'Event', announcement: 'Announcement', resource: 'Resource',
  milestone: 'Milestone', question: 'Community Q&A / Poll', poll: 'Poll', compliance: 'Compliance',
}
function typeLabel(t)         { return TYPE_LABELS[t] ?? t }
function typeBadgeClass(t) {
  const m = { platform:'badge-blue', provider:'badge-gold', post:'badge-gold', event:'badge-green', announcement:'badge-green', resource:'badge-purple', milestone:'badge-gold', question:'badge-gray', poll:'badge-blue' }
  return m[t] ?? 'badge-gray'
}
function authorKind(post)     { return post.author_role === 'practitioner' ? 'provider' : 'steward' }
function authorKindComment(c) { return c.author_role === 'practitioner' ? 'provider' : 'steward' }
function avatarMod(post)      { return 'avatar-' + (post.author_avatar_mod ?? 'gold') }
function fmtDate(iso)         { return iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '' }
function fmtEventMon(iso)     { return iso ? new Date(iso).toLocaleString('en-US', { month: 'short' }).toUpperCase() : '' }
function fmtEventDay(iso)     { return iso ? new Date(iso).getDate() : '' }
function fmtEventTime(s, e) {
  const fmt = d => new Date(d).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  return s ? fmt(s) + (e ? ' – ' + fmt(e) : '') : ''
}
function fmtFullEventDate(iso) {
  return iso ? new Date(iso).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''
}

// type-specific placeholders
const createTitlePlaceholder = computed(() => {
  return { provider: 'Give your post a clear headline', question: 'Your question or quiz title (optional)', resource: 'Name of the resource or guide', milestone: 'Celebrate your achievement', event: 'Event name and date' }[createForm.post_type] ?? 'Give your post a clear headline'
})
const createBodyPlaceholder = computed(() => {
  return { provider: 'Share your update, insight, or experience…', question: 'Add context or background for your question / quiz (optional)…', resource: 'Briefly describe what this resource covers…', milestone: 'Tell the community what you accomplished…', event: 'Event details — date, location, what to expect…' }[createForm.post_type] ?? 'Share something with the Aegis community…'
})

// ── modal state ──────────────────────────────────────────────────────────────
const modals = reactive({ createPost: false, editPost: false, sharePost: false, myLibrary: false, postDetail: false, eventDetail: false, eventRegisterConfirm: false, eventCancelConfirm: false })

// ── vuelidate helpers ─────────────────────────────────────────────────────────
// Pattern mirrors Events.vue: fieldError uses .value internally on named v$ instances.
// Two helpers: one for create form, one for edit form.
function createFieldError(field) {
  if (vCreate.value[field]?.$error) return vCreate.value[field].$errors[0]?.$message ?? 'Invalid.'
  if (createForm.errors?.[field])   return createForm.errors[field]
  return null
}
function editFieldError(field) {
  if (vEdit.value[field]?.$error)   return vEdit.value[field].$errors[0]?.$message ?? 'Invalid.'
  if (editForm.errors?.[field])     return editForm.errors[field]
  return null
}
// Generic for any passed v$ + form (used internally only, NOT from template)
function anyError(v$ref, form, field) {
  const node = v$ref?.value?.[field] ?? v$ref?.[field]
  if (node?.$error) return node.$errors[0]?.$message ?? 'Invalid.'
  return form.errors?.[field] ?? null
}
</script>

<style scoped>
.news-layout { display: grid; grid-template-columns: 1fr 300px; gap: 18px; align-items: start; }
@media (max-width: 1100px) { .news-layout { grid-template-columns: 1fr; } }
.news-feed { display: flex; flex-direction: column; gap: 14px; }
.news-toolbar { display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:12px;flex-wrap:wrap;padding:10px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius); }
.news-toolbar-left,.news-toolbar-right { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.news-toolbar-label { font-family:var(--font-sans);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--text-4);margin:0; }
.news-toolbar .form-select-sm { min-width:170px; }
.news-count-line { display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding:0 2px;font-size:12px;color:var(--text-3); }
.news-count-line strong { color:var(--text);font-weight:700; }
.news-active-tag { display:flex;align-items:center;gap:8px;padding:9px 12px;margin-bottom:10px;background:var(--badge-bg-gold);border:1px solid var(--soft-gold);border-radius:var(--radius); }
.news-active-tag-label { font-family:var(--font-sans);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--gold-dark); }
.news-active-tag-chip { font-family:var(--font-sans);font-size:13px;font-weight:700;color:var(--gold-dark);flex:1; }
.news-active-tag-clear { width:24px;height:24px;border-radius:var(--radius-sm);background:transparent;border:none;color:var(--gold-dark);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background var(--transition); }
.news-active-tag-clear:hover { background:var(--surface); }
.news-create-cta { display:flex;align-items:center;gap:12px;width:100%;padding:14px 18px;margin-bottom:14px;background:var(--surface);border:2px dashed var(--border-dark);border-radius:var(--radius-lg);cursor:pointer;text-align:left;transition:border-color var(--transition),background var(--transition); }
.news-create-cta:hover { border-color:var(--soft-gold);background:var(--badge-bg-gold); }
.news-create-cta-text { flex:1;font-size:14px;font-weight:600;color:var(--text-3); }
.news-create-cta-tools { display:flex;align-items:center;gap:6px;color:var(--text-4);flex-shrink:0; }
/* Post card */
.nf-post { overflow:hidden; }
.nf-post.is-pinned { border-color:var(--fade-gold); }
.nf-pinned { display:flex;align-items:center;gap:6px;padding:7px 18px;background:var(--badge-bg-gold);border-bottom:1px solid var(--border);font-family:var(--font-sans);font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--gold-dark); }
.nf-head { display:flex;align-items:center;gap:11px;padding:16px 18px 0; }
.nf-avatar { cursor:pointer;transition:transform var(--transition); }
.nf-avatar:hover { transform:scale(1.04); }
.nf-id { flex:1;min-width:0; }
.nf-id-top { display:flex;align-items:center;gap:7px; }
.nf-author { font-size:14px;font-weight:700;color:var(--text);cursor:pointer;transition:color var(--transition); }
.nf-author:hover { color:var(--gold-dark); }
.nf-self { font-family:var(--font-sans);font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;padding:1px 6px;background:var(--badge-bg-gold);color:var(--gold-dark);border-radius:var(--radius-full); }
.nf-meta { display:flex;align-items:center;gap:6px;margin-top:2px;font-size:11px;font-weight:600;color:var(--text-4); }
.nf-type { display:inline-flex;align-items:center;gap:5px; }
.nf-type-dot { width:6px;height:6px;border-radius:var(--radius-full);flex-shrink:0; }
.nf-meta-sep { width:3px;height:3px;border-radius:var(--radius-full);background:var(--text-4); }
.nf-t-platform .nf-type-dot   { background:var(--blue-dark); }
.nf-t-provider .nf-type-dot, .nf-t-post .nf-type-dot, .nf-t-milestone .nf-type-dot { background:var(--gold-dark); }
.nf-t-compliance .nf-type-dot, .nf-t-event .nf-type-dot, .nf-t-announcement .nf-type-dot { background:var(--green-dark); }
.nf-t-resource .nf-type-dot   { background:var(--purple-dark); }
.nf-t-question .nf-type-dot   { background:var(--text-4); }
.nf-t-poll .nf-type-dot       { background:var(--blue-dark); }
.nf-more { flex-shrink:0; }
.nf-body { padding:12px 18px 4px; }
.nf-title { font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text);line-height:1.35;margin-bottom:6px; }
.nf-title-link { cursor:pointer;transition:color var(--transition); }
.nf-title-link:hover { color:var(--gold-dark); }
.nf-text { font-size:13px;color:var(--text-2);line-height:1.6;white-space:pre-wrap;word-wrap:break-word; }
.nf-text.is-collapsed { display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden; }
.nf-readmore { display:inline-flex;align-items:center;gap:3px;font-family:var(--font-sans);font-size:12px;font-weight:700;color:var(--gold-dark);background:none;border:none;padding:0;cursor:pointer;margin-top:6px; }
.nf-readmore:hover { text-decoration:underline; }
/* Media */
.nf-media { margin-top:12px;border-radius:var(--radius);overflow:hidden;display:grid;gap:2px; }
.nf-media-single { grid-template-columns:1fr; }
.nf-media-multi   { grid-template-columns:1fr 1fr;max-height:320px; }
.nf-media-item { position:relative;overflow:hidden;cursor:pointer;background:var(--surface-3);min-height:120px; }
.nf-media-img { width:100%;height:100%;object-fit:cover;display:block;transition:transform var(--transition); }
.nf-media-item:hover .nf-media-img { transform:scale(1.02); }
.nf-media-video-placeholder { display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;gap:8px;color:var(--text-3);font-size:12px;font-weight:600; }
.nf-media-overflow { position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;font-family:var(--font-serif);font-size:22px;font-weight:700;color:#fff; }
/* Links / Tags */
.nf-links { margin-top:12px;display:flex;flex-direction:column;gap:6px; }
.nf-link { display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;transition:background var(--transition),border-color var(--transition); }
.nf-link:hover { background:var(--badge-bg-gold);border-color:var(--fade-gold); }
.nf-link-ic { color:var(--gold-dark);flex-shrink:0;display:flex; }
.nf-link-label { flex:1;min-width:0;font-family:var(--font-sans);font-size:13px;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.nf-link-go { color:var(--text-4);flex-shrink:0;display:flex; }
.nf-tags { display:flex;flex-wrap:wrap;gap:6px;margin-top:12px; }
.nf-tag { font-family:var(--font-sans);font-size:11px;font-weight:600;color:var(--text-3);background:none;border:none;padding:0;cursor:pointer;transition:color var(--transition); }
.nf-tag:hover { color:var(--gold-dark); }
/* Poll */
.nf-poll { margin-top:14px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius); }
.nf-poll-q { display:flex;align-items:center;gap:7px;font-family:var(--font-sans);font-size:13px;font-weight:700;color:var(--text);margin-bottom:12px; }
.nf-poll-opt { display:flex;align-items:center;gap:10px;margin-bottom:10px;cursor:pointer;padding:6px 8px;border-radius:var(--radius-sm);transition:background var(--transition); }
.nf-poll-opt:hover:not(.is-disabled) { background:var(--surface-3); }
.nf-poll-opt.is-voted { background:var(--green-light); }
.nf-poll-opt.is-disabled { cursor:default; }
.nf-poll-opt:last-of-type { margin-bottom:0; }
.nf-poll-label { font-size:12px;font-weight:600;color:var(--text-2);width:132px;flex-shrink:0; }
.nf-poll-track { flex:1;height:11px;background:var(--surface-3);border-radius:var(--radius-full);overflow:hidden; }
.nf-poll-fill { height:100%;background:var(--gold-dark);border-radius:var(--radius-full);transition:width .5s ease; }
.nf-poll-pct { font-family:var(--font-sans);font-size:12px;font-weight:700;color:var(--text-3);width:36px;text-align:right;flex-shrink:0; }
.nf-poll-meta { font-size:11px;color:var(--text-4);margin-top:11px;font-weight:600; }
/* Actions */
.nf-actions { display:flex;align-items:center;gap:4px;padding:10px 12px;margin-top:14px;border-top:1px solid var(--border); }
.nf-act { display:inline-flex;align-items:center;gap:6px;padding:6px 10px;background:transparent;border:none;border-radius:var(--radius-sm);cursor:pointer;font-family:var(--font-sans);font-size:12px;font-weight:600;color:var(--text-3);transition:background var(--transition),color var(--transition); }
.nf-act:hover      { background:var(--surface-3);color:var(--text); }
.nf-act.is-liked   { color:var(--red-dark); }
.nf-act.is-liked:hover { background:var(--red-light); }
.nf-act.is-saved   { color:var(--gold-dark); }
.nf-act.is-saved:hover { background:var(--badge-bg-gold); }
.nf-act.is-commented { color:var(--blue-dark); }
.nf-act.is-commented:hover { background:var(--blue-light); }
.nf-act-spacer { flex:1; }
/* Comments */
.nf-comments { display:none;border-top:1px solid var(--border);padding:14px 18px;background:var(--surface-2); }
.nf-comments.is-open { display:block; }
.nf-comment { display:flex;gap:9px;margin-bottom:12px; }
.nf-comment:last-of-type { margin-bottom:10px; }
.nf-comment-main { flex:1;min-width:0; }
.nf-comment-top { display:flex;align-items:center;gap:6px; }
.nf-comment-author { font-size:12px;font-weight:700;color:var(--text);cursor:pointer; }
.nf-comment-author:hover { color:var(--gold-dark); }
.nf-comment-body { font-size:12px;color:var(--text-2);margin-top:2px;line-height:1.5;word-wrap:break-word; }
.nf-comment-meta { font-size:10px;color:var(--text-4);margin-top:4px;font-weight:600; }
.nf-comment-form { display:flex;align-items:center;gap:8px;margin-top:4px; }
.nf-comment-form .form-input { flex:1;min-height:36px;padding:8px 13px; }
/* Sidebar */
.news-sidebar { display:flex;flex-direction:column;gap:12px; }
.nsc-card .card-body { padding:4px 14px 6px; }
.ne-event { display:grid;grid-template-columns:40px 1fr 16px;gap:14px;align-items:center;padding:13px 6px 13px 2px;border-bottom:1px solid var(--border);cursor:pointer;border-radius:var(--radius-sm);transition:background var(--transition); }
.ne-event:last-child { border-bottom:none; }
.ne-event:hover { background:var(--badge-bg-gold); }
.ne-date { display:flex;flex-direction:column;align-items:center;line-height:1;flex-shrink:0; }
.ne-date-mon { font-family:var(--font-sans);font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--gold-dark);margin-bottom:4px; }
.ne-date-day { font-family:var(--font-serif);font-size:24px;font-weight:700;color:var(--text); }
.ne-body { min-width:0; }
.ne-title { font-size:13px;font-weight:700;color:var(--text);line-height:1.35;transition:color var(--transition); }
.ne-event:hover .ne-title { color:var(--gold-dark); }
.ne-meta { display:flex;align-items:center;gap:5px;margin-top:4px;font-size:11px;color:var(--text-3);min-width:0; }
.ne-meta-time { font-weight:600;white-space:nowrap; }
.ne-meta-sep { width:3px;height:3px;border-radius:var(--radius-full);background:var(--text-4);flex-shrink:0; }
.ne-meta-loc { overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.ne-tags { display:flex;flex-wrap:wrap;gap:5px;margin-top:7px; }
.ne-tag { display:inline-flex;align-items:center;gap:4px;font-family:var(--font-sans);font-size:10px;font-weight:700;letter-spacing:.3px;padding:2px 7px;border-radius:var(--radius-full);line-height:1.5;white-space:nowrap; }
.ne-tag.is-free { background:var(--green-light);color:var(--green-dark); }
.ne-tag.is-ceu  { background:var(--badge-bg-gold);color:var(--gold-dark); }
.ne-go { color:var(--text-4);opacity:0;transform:translateX(-3px);transition:opacity var(--transition),transform var(--transition);display:flex;align-items:center;justify-content:center; }
.ne-event:hover .ne-go { opacity:1;transform:translateX(0); }
.nt-row { display:flex;align-items:center;justify-content:space-between;padding:9px 6px;border-bottom:1px solid var(--border);cursor:pointer;border-radius:var(--radius-sm);transition:background var(--transition); }
.nt-row:last-child { border-bottom:none; }
.nt-row:hover { background:var(--badge-bg-gold); }
.nt-tag { font-size:13px;font-weight:700;color:var(--gold-dark); }
.nt-count { font-size:11px;color:var(--text-4);font-weight:600; }
/* Event detail CSS */
.evt-detail-chip { display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-full);font-size:12px;color:var(--text-2); }
.evt-detail-chip.is-ceu  { background:var(--badge-bg-gold);border-color:var(--soft-gold);color:var(--gold-dark); }
.evt-detail-chip.is-free { background:var(--green-light);border-color:var(--fade-green);color:var(--green-dark); }
.evt-detail-chip.is-paid { background:var(--surface-2);color:var(--text-2); }
.evt-detail-registered-banner { margin-top:16px; }
/* Post detail */
.pd-head { display:flex;align-items:center;gap:12px;margin-bottom:16px; }
.pd-media { margin:16px 0;display:grid;gap:4px;border-radius:var(--radius);overflow:hidden; }
.pd-media-item { width:100%; }
.pd-media-img { width:100%;max-height:420px;object-fit:contain;display:block;background:var(--surface-3); }
.pd-actions { display:flex;align-items:center;gap:4px;padding:12px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);margin:16px 0; }
.pd-comments { padding-top:4px; }
/* Media upload */
/* Poll builder */
.poll-options-builder { display:flex;flex-direction:column;gap:8px; }
.poll-option-row { display:flex;align-items:center;gap:8px; }
.poll-option-num { width:22px;height:22px;border-radius:var(--radius-full);background:var(--badge-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0; }
.poll-option-row .form-input { flex:1; }
/* Library */
.lib-table { display:flex;flex-direction:column;gap:0; }
.lib-table-head { display:grid;grid-template-columns:1fr 90px 110px 36px;gap:10px;align-items:center;padding:8px 12px;font-family:var(--font-sans);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-4);background:var(--surface-2);border-radius:var(--radius-sm);margin-bottom:4px; }
.lib-table-row { display:grid;grid-template-columns:1fr 90px 110px 36px;gap:10px;align-items:center;padding:10px 12px;border-bottom:1px solid var(--border);transition:background var(--transition); }
.lib-table-row:last-child { border-bottom:none; }
.lib-table-row:hover { background:var(--surface-2); }
.lib-post-title { font-size:13px;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.lib-post-clickable { cursor:pointer;transition:color var(--transition); }
.lib-post-clickable:hover { color:var(--gold-dark); }
.lib-post-body { font-size:11px;color:var(--text-4);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
/* Utilities */

@media (max-width:600px) { .news-toolbar { flex-direction:column;align-items:stretch; } .news-toolbar-left,.news-toolbar-right { width:100%; } .news-toolbar .form-select-sm { flex:1;min-width:0; } }

/* ── Gaps fix — all layout via CSS, no inline styles ── */
.pd-post-meta { margin-top:2px; }
.pd-section { margin-top:12px; }
.pd-comments .nf-comment { margin-bottom:12px; }
.pd-comments .nf-comment-form { margin-top:8px; }
.pd-body { white-space:pre-wrap;font-size:14px;line-height:1.7;color:var(--text-2);margin:16px 0; }
.pd-comment-count { font-size:12px;font-weight:700;color:var(--text-4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px; }
.evt-organizer { display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-3);margin-top:6px; }
.evt-detail-title { font-family:var(--font-serif);font-size:22px;font-weight:700;color:var(--text);margin-top:10px;line-height:1.3; }
.evt-detail-meta { display:flex;flex-wrap:wrap;gap:8px;margin:16px 0; }
.evt-detail-registered-banner { display:flex;align-items:center;gap:8px;padding:12px 16px;background:var(--green-light);border:1px solid var(--green);border-radius:var(--radius);margin-top:16px;font-size:13px;color:var(--green-dark);font-weight:600; }
.evt-desc-text { font-size:14px;line-height:1.7;color:var(--text-2);white-space:pre-line;margin:0; }
/* Add Option button — compact and centered */
.poll-add-btn { margin-top:8px;align-self:center;width:auto; }
.poll-options-builder { display:flex;flex-direction:column;gap:8px;align-items:stretch; }
/* tabs-segmented spacing */
.tabs-segmented { margin-bottom:16px; }

/* ── Event detail modal CSS — full parity with Events.vue ───────────── */
.evt-detail-heading { margin-bottom:14px; }
.evt-detail-eyebrow-row { display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px; }
.evt-detail-eyebrow { font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--text-4);margin-bottom:6px; }
.evt-detail-organizer { display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text-3);font-weight:500;margin-top:6px; }
.evt-detail-desc { font-size:13px;color:var(--text-2);line-height:1.65;margin:0 0 16px; }
.evt-detail-chip strong { color:var(--text);font-weight:700; }
.evt-detail-chip.is-ceu strong { color:var(--gold-dark); }
.evt-detail-chip.is-free strong { color:var(--green-dark); }
.evt-detail-external {
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  font-size:12px;color:var(--gold-dark);font-weight:600;
  background:var(--badge-bg-gold);border:1px solid var(--gold);
  border-radius:var(--radius);padding:10px 14px;margin-bottom:14px;
}
.evt-detail-external a { color:var(--gold-dark);text-decoration:none;font-weight:700; }
.evt-detail-external a:hover { text-decoration:underline; }
.evt-detail-external-note { font-size:11px;color:var(--text-3);font-weight:400; }
.evt-external-badge {
  display:inline-flex;align-items:center;gap:4px;
  font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;
  padding:3px 8px;border-radius:var(--radius-full);
  background:var(--blue-light);color:var(--blue-dark);
}
/* Category badge colors */
.evt-category {
  display:inline-flex;align-items:center;
  font-family:var(--font-sans);font-size:10px;font-weight:700;
  letter-spacing:.5px;text-transform:uppercase;
  padding:3px 10px;border-radius:var(--radius-full);
  background:var(--surface-3);color:var(--text-3);
}
.evt-category.webinar    { background:var(--blue-light);   color:var(--blue-dark); }
.evt-category.conference { background:var(--purple-light); color:var(--purple-dark); }
.evt-category.workshop   { background:var(--orange-light); color:var(--orange-dark); }
.evt-category.networking { background:var(--green-light);  color:var(--green-dark); }
.evt-category.training   { background:var(--teal-light);   color:var(--teal-dark); }
</style>
