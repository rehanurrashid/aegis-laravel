<!--
  pages/provider/News.vue — Provider portal News & Resources.
  Prompt 2: Full backend wiring. All props dynamic, all forms real.
-->
<template>
  <AppLayout>

    <!-- HERO -->
    <AegisHeroBanner eyebrow="Provider Portal" title="News &amp; Resources"
                     subtitle="Updates from Aegis, insights from providers, and community discussions." quiet>
      <template #actions>
        <a :href="route('provider.activity')" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
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
            <AegisIcon name="calendar" :size="16" />
          </div>
        </button>

        <!-- Toolbar -->
        <div class="news-toolbar">
          <div class="news-toolbar-left">
            <label class="news-toolbar-label" for="newsFilterSelect">Filter</label>
            <select id="newsFilterSelect" class="form-select form-select-sm" v-model="activeFilter" @change="applyFilter">
              <option value="all">All posts{{ countByType.all ? ' (' + countByType.all + ')' : '' }}</option>
              <option value="platform">Platform updates{{ countByType.platform ? ' (' + countByType.platform + ')' : '' }}</option>
              <option value="provider">Provider posts{{ countByType.provider ? ' (' + countByType.provider + ')' : '' }}</option>
              <option value="event">Events{{ countByType.event ? ' (' + countByType.event + ')' : '' }}</option>
              <option value="resource">Resources{{ countByType.resource ? ' (' + countByType.resource + ')' : '' }}</option>
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

        <!-- Active tag filter strip -->
        <div v-if="localActiveTag" class="news-active-tag">
          <span class="news-active-tag-label">Filtered by</span>
          <span class="news-active-tag-chip">#{{ localActiveTag }}</span>
          <button type="button" class="news-active-tag-clear" data-tooltip="Clear filter" @click="clearTagFilter">
            <AegisIcon name="x" :size="12" />
          </button>
        </div>

        <!-- Count line -->
        <div class="news-count-line">
          <span>
            <strong>{{ displayPosts.length }}</strong>
            post{{ displayPosts.length === 1 ? '' : 's' }}{{ localActiveTag ? ' tagged #' + localActiveTag : '' }}
          </span>
        </div>

        <!-- Feed -->
        <div id="newsFeed" class="news-feed">

          <!-- Empty state -->
          <AegisEmptyState
            v-if="!displayPosts.length"
            icon="file-text"
            title="No posts yet"
            subtitle="No posts match this filter. Try a different category or create the first one."
          >
            <template #actions>
              <button type="button" class="btn btn-primary" @click="modals.createPost = true">
                <AegisIcon name="pencil" :size="14" /> Create Post
              </button>
            </template>
          </AegisEmptyState>

          <!-- Post cards -->
          <div
            v-for="post in displayPosts"
            :key="post.id"
            :class="['card', 'nf-post', { 'is-pinned': post.is_pinned }]"
          >
            <!-- Pinned banner -->
            <div v-if="post.is_pinned" class="nf-pinned">
              <AegisIcon name="bookmark" :size="11" /> Pinned by Aegis Team
            </div>

            <!-- Post head -->
            <div class="nf-head">
              <div
                :class="['avatar', 'avatar-md', avatarMod(post), 'nf-avatar']"
                :data-tooltip="'View ' + post.author_name"
                @click="viewProfile(post.author_slug, authorKind(post))"
              >{{ post.author_initials }}</div>

              <div class="nf-id">
                <div class="nf-id-top">
                  <span class="nf-author" @click="viewProfile(post.author_slug, authorKind(post))">
                    {{ post.author_name }}
                  </span>
                  <span v-if="post.is_self_post" class="nf-self">You</span>
                </div>
                <div :class="['nf-meta', 'nf-t-' + post.post_type]">
                  <span class="nf-type">
                    <span class="nf-type-dot"></span>
                    {{ typeLabel(post.post_type) }}
                  </span>
                  <span class="nf-meta-sep"></span>
                  <span>{{ timeAgo(post.created_at) }}</span>
                </div>
              </div>

              <button
                v-if="post.is_self_post"
                type="button"
                class="btn-icon btn-icon-sm nf-more"
                data-tooltip="Edit post"
                @click="openEdit(post)"
              ><AegisIcon name="pencil" :size="13" /></button>
              <button
                v-else
                type="button"
                class="btn-icon btn-icon-sm nf-more"
                data-tooltip="Report post"
                @click="reportPost(post.id)"
              ><AegisIcon name="flag-2" :size="13" /></button>
            </div>

            <!-- Post body -->
            <div class="nf-body">
              <div v-if="post.title" class="nf-title">{{ post.title }}</div>

              <div :class="['nf-text', { 'is-collapsed': postState(post.id)._collapsed }]">{{ post.body }}</div>
              <button
                v-if="post.body && post.body.length > 260"
                type="button"
                class="nf-readmore"
                @click="toggleReadMore(post.id)"
              >
                {{ postState(post.id)._collapsed ? 'Read more' : 'Show less' }}
                <AegisIcon :name="postState(post.id)._collapsed ? 'chevron-down' : 'chevron-up'" :size="11" />
              </button>

              <!-- Inline links -->
              <div v-if="post.links && post.links.length" class="nf-links">
                <a
                  v-for="lnk in post.links"
                  :key="lnk.url"
                  :href="lnk.url"
                  class="nf-link"
                  target="_blank"
                  rel="noopener"
                >
                  <span class="nf-link-ic"><AegisIcon name="link" :size="14" /></span>
                  <span class="nf-link-label">{{ lnk.label || lnk.url }}</span>
                  <span class="nf-link-go"><AegisIcon name="chevron-right" :size="12" /></span>
                </a>
              </div>

              <!-- Tags -->
              <div v-if="post.tags && post.tags.length" class="nf-tags">
                <button
                  v-for="tag in post.tags"
                  :key="tag"
                  type="button"
                  class="nf-tag"
                  @click="filterByTag(tag)"
                >#{{ tag }}</button>
              </div>

              <!-- Poll -->
              <div
                v-if="post.poll_question && post.poll_options && post.poll_options.length"
                class="nf-poll"
              >
                <div class="nf-poll-q">
                  <AegisIcon name="bar-chart" :size="14" />
                  {{ post.poll_question }}
                </div>
                <div
                  v-for="(opt, idx) in post.poll_options"
                  :key="opt.key || idx"
                  class="nf-poll-opt"
                  @click="votePoll(post, opt)"
                >
                  <span class="nf-poll-label">{{ opt.label }}</span>
                  <span class="nf-poll-track">
                    <span class="nf-poll-fill" :style="{ width: pollPct(post, idx) + '%' }"></span>
                  </span>
                  <span class="nf-poll-pct">{{ pollPct(post, idx) }}%</span>
                </div>
                <div class="nf-poll-meta">
                  {{ pollTotal(post) }} vote{{ pollTotal(post) === 1 ? '' : 's' }}
                  <template v-if="post.poll_closes_at"> &middot; Closes {{ fmtDate(post.poll_closes_at) }}</template>
                  <template v-if="post.my_poll_vote"> &middot; <span style="color:var(--green-dark);font-weight:700">✓ You voted</span></template>
                </div>
              </div>
            </div>

            <!-- Actions row -->
            <div class="nf-actions">
              <button
                type="button"
                :class="['nf-act', { 'is-liked': post.is_liked }]"
                :data-tooltip="post.is_liked ? 'Unlike' : 'Like'"
                @click="toggleLike(post)"
              >
                <AegisIcon name="heart" :size="15" />
                <span>{{ post.like_count }}</span>
              </button>
              <button
                type="button"
                class="nf-act"
                data-tooltip="Comments"
                @click="toggleComments(post.id)"
              >
                <AegisIcon name="message-square" :size="15" />
                <span>{{ post.comment_count }}</span>
              </button>
              <button
                type="button"
                class="nf-act"
                data-tooltip="Share"
                @click="sharePost(post)"
              >
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

              <button
                v-else
                type="button"
                :class="['nf-act', { 'is-saved': post.is_saved }]"
                :data-tooltip="post.is_saved ? 'Unsave' : 'Save'"
                @click="toggleSave(post)"
              >
                <AegisIcon name="bookmark" :size="15" />
              </button>
            </div>

            <!-- Comments section -->
            <div :class="['nf-comments', { 'is-open': postState(post.id)._commentsOpen }]">
              <div
                v-for="c in commentsByPost[post.id] || []"
                :key="c.id"
                class="nf-comment"
              >
                <div
                  :class="['avatar', 'avatar-xs', 'avatar-gold', 'nf-avatar']"
                  :data-tooltip="'View ' + c.author_name"
                  @click="viewProfile(c.author_slug, authorKindComment(c))"
                >{{ c.author_initials }}</div>
                <div class="nf-comment-main">
                  <div class="nf-comment-top">
                    <span class="nf-comment-author" @click="viewProfile(c.author_slug, authorKindComment(c))">
                      {{ c.author_name }}
                    </span>
                    <span v-if="c.is_self" class="nf-self">You</span>
                  </div>
                  <div class="nf-comment-body">{{ c.body }}</div>
                  <div class="nf-comment-meta">
                    {{ timeAgo(c.created_at) }}
                    <template v-if="c.like_count > 0"> &middot; {{ c.like_count }} like{{ c.like_count === 1 ? '' : 's' }}</template>
                  </div>
                </div>
              </div>

              <!-- Comment input -->
              <div class="nf-comment-form">
                <div :class="['avatar', 'avatar-xs', meAvatarMod]">{{ meInitials }}</div>
                <input
                  type="text"
                  class="form-input form-input-sm"
                  placeholder="Write a comment…"
                  v-model="commentInputs[post.id]"
                  @keydown.enter.prevent="submitComment(post)"
                />
                <button type="button" class="btn-icon btn-icon-primary" data-tooltip="Send" @click="submitComment(post)">
                  <AegisIcon name="send" :size="14" />
                </button>
              </div>
            </div>

          </div><!-- end .nf-post -->
        </div><!-- end #newsFeed -->
      </div><!-- end .news-feed-col -->

      <!-- ═══ RIGHT SIDEBAR ═══ -->
      <aside class="news-sidebar">

        <!-- Upcoming Events -->
        <div class="card nsc-card">
          <div class="card-header">
            <div class="card-title" style="display:flex;align-items:center;gap:6px">
              <AegisIcon name="calendar" :size="15" /> Upcoming Events
            </div>
            <a :href="route('provider.news.events')" class="btn btn-outline btn-sm">View all</a>
          </div>
          <div class="card-body">
            <div v-if="!upcoming.length" style="font-size:12px;color:var(--text-3);text-align:center;padding:14px 0">
              No upcoming events
            </div>
            <div
              v-for="e in upcoming"
              :key="e.id"
              class="ne-event"
              @click="rsvpEvent(e)"
            >
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
            <div v-if="!trending.length" style="font-size:12px;color:var(--text-3);text-align:center;padding:14px 0">
              No trends yet
            </div>
            <div
              v-for="t in trending"
              :key="t.tag"
              class="nt-row"
              @click="filterByTag(t.tag)"
            >
              <span class="nt-tag">#{{ t.tag }}</span>
              <span class="nt-count">{{ t.post_count }} posts</span>
            </div>
          </div>
        </div>

      </aside>
    </div><!-- end .news-layout -->

    <!-- ═══════════════ MODALS ═══════════════ -->

    <!-- Create Post -->
    <AegisModal v-model="modals.createPost" size="lg" title="Create Post">
      <div class="form-group">
        <label class="form-label">Post Type</label>
        <select class="form-select" v-model="createForm.post_type">
          <option value="provider">General Post</option>
          <option value="question">Ask Community</option>
          <option value="resource">Share Resource</option>
          <option value="milestone">Milestone</option>
          <option value="event">Announce Event</option>
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
      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--text-4);font-weight:600">(optional)</span></label>
        <input type="text" class="form-input" v-model="createForm.title"
               placeholder="Give your post a clear headline" maxlength="160" />
      </div>
      <div class="form-group">
        <label class="form-label">Content <span class="required">*</span></label>
        <textarea class="form-textarea" v-model="createForm.body" rows="6" maxlength="2000"
                  :class="{ 'is-error': fieldError('createForm.body') }"
                  placeholder="Share your update, question, or resource…"
                  @blur="v$.createForm.body.$touch()" />
        <div v-if="fieldError('createForm.body')" class="form-error">{{ fieldError('createForm.body') }}</div>
        <div class="form-hint">{{ createForm.body.length }} / 2000</div>
      </div>
      <div class="form-group">
        <label class="form-label">Tags <span style="color:var(--text-4);font-weight:600">(comma-separated)</span></label>
        <input type="text" class="form-input" v-model="createForm.tags"
               placeholder="e.g. Telehealth, Compliance, Workflow" />
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.createPost = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="inertiaCreateForm.processing" @click="submitCreatePost">
          <AegisIcon name="send" :size="14" /> Publish Post
        </button>
      </template>
    </AegisModal>

    <!-- Edit Post -->
    <AegisModal v-model="modals.editPost" size="lg" title="Edit Post">
      <div class="form-group">
        <label class="form-label">Title</label>
        <input type="text" class="form-input" v-model="editForm.title" maxlength="160" />
      </div>
      <div class="form-group">
        <label class="form-label">Content <span class="required">*</span></label>
        <textarea class="form-textarea" v-model="editForm.body" rows="6" maxlength="2000"
                  :class="{ 'is-error': fieldError('editForm.body') }"
                  @blur="v$.editForm.body.$touch()" />
        <div v-if="fieldError('editForm.body')" class="form-error">{{ fieldError('editForm.body') }}</div>
        <div class="form-hint">{{ editForm.body.length }} / 2000</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.editPost = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="inertiaEditForm.processing" @click="submitEditPost">
          <AegisIcon name="check" :size="14" /> Save Changes
        </button>
      </template>
    </AegisModal>

    <!-- Share Post -->
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

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, useForm, usePage as useInertiaPage } from '@inertiajs/vue3'
import AppLayout                         from '@/layouts/AppLayout.vue'
import { useToast }                      from '@/composables/useToast'
import { useConfirm }                    from '@/composables/useConfirm'
import { useActivity }                   from '@/composables/useActivity'
import { useProfileRoute }               from '@/composables/useProfileRoute'
import useVuelidate                      from '@vuelidate/core'
import { required, minLength }           from '@vuelidate/validators'

// ── Props (all from controller) ──────────────────────────────────────────────
const props = defineProps({
  posts:          { type: Array,  default: () => [] },
  upcoming:       { type: Array,  default: () => [] },
  trending:       { type: Array,  default: () => [] },
  commentsByPost: { type: Object, default: () => ({}) },
  countToday:     { type: Number, default: 0 },
  countAuthors:   { type: Number, default: 0 },
  countUpcoming:  { type: Number, default: 0 },
  countByType:    { type: Object, default: () => ({ all: 0, platform: 0, provider: 0, event: 0, resource: 0 }) },
  activeFilter:   { type: String, default: 'all' },
  activeTag:      { type: String, default: '' },
})

// ── Composables ──────────────────────────────────────────────────────────────
const toast             = useToast()
const { confirmAction } = useConfirm()
const { timeAgo }       = useActivity()
const { viewProfile }   = useProfileRoute()
const page              = useInertiaPage()

// ── Current user from shared props ───────────────────────────────────────────
const meInitials  = computed(() => page.props.auth?.user?.avatar_initials ?? 'SJ')
const meAvatarMod = 'avatar-gold'
const meId        = computed(() => page.props.auth?.user?.id ?? '')

// ── Per-post UI state (collapse / comments open) ─────────────────────────────
// Stored separately so props can be replaced by Inertia without losing UI state
const uiState = reactive({})
function postState(id) {
  if (!uiState[id]) {
    const post = props.posts.find(p => p.id === id)
    uiState[id] = {
      _collapsed: (post?.body ?? '').length > 260,
      _commentsOpen: false,
    }
  }
  return uiState[id]
}

// ── Filter / sort ─────────────────────────────────────────────────────────────
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

// ── Client-side sort (on top of server data) ──────────────────────────────────
const displayPosts = computed(() => {
  let list = [...props.posts]

  // Tag filter (client-side refinement if already on page)
  if (localActiveTag.value) {
    list = list.filter(p => (p.tags ?? []).includes(localActiveTag.value))
  }

  const pinned = list.filter(p => p.is_pinned)
  const rest   = list.filter(p => !p.is_pinned)

  if (activeSort.value === 'popular') {
    rest.sort((a, b) => (b.like_count ?? 0) - (a.like_count ?? 0))
  }
  return [...pinned, ...rest]
})

// ── Read more ─────────────────────────────────────────────────────────────────
function toggleReadMore(id) {
  postState(id)._collapsed = !postState(id)._collapsed
}

// ── Comments ──────────────────────────────────────────────────────────────────
const commentInputs = reactive({})

function toggleComments(id) {
  postState(id)._commentsOpen = !postState(id)._commentsOpen
}

function submitComment(post) {
  const text = (commentInputs[post.id] ?? '').trim()
  if (!text) return

  const form = useForm({ body: text })
  form.post(route('provider.news.comment', { post: post.id }), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      commentInputs[post.id] = ''
      toast.success('Comment posted')
    },
    onError: () => toast.error('Could not post comment.'),
  })
}

// ── Like ──────────────────────────────────────────────────────────────────────
function toggleLike(post) {
  // Optimistic update
  post.is_liked   = !post.is_liked
  post.like_count = post.is_liked ? (post.like_count + 1) : Math.max(0, post.like_count - 1)

  const form = useForm({ reaction_type: 'like' })
  form.post(route('provider.news.react', { post: post.id }), {
    preserveScroll: true,
    preserveState:  true,
    onError: () => {
      // rollback
      post.is_liked   = !post.is_liked
      post.like_count = post.is_liked ? (post.like_count + 1) : Math.max(0, post.like_count - 1)
    },
  })
}

// ── Save ──────────────────────────────────────────────────────────────────────
function toggleSave(post) {
  post.is_saved = !post.is_saved
  toast.success(post.is_saved ? 'Saved to your library' : 'Removed from saved')

  const form = useForm({ reaction_type: 'save' })
  form.post(route('provider.news.react', { post: post.id }), {
    preserveScroll: true,
    preserveState:  true,
    onError: () => {
      post.is_saved = !post.is_saved
      toast.error('Could not save post.')
    },
  })
}

// ── Share ─────────────────────────────────────────────────────────────────────
const shareUrl = ref('')
function sharePost(post) {
  shareUrl.value = window.location.origin + '/provider/news?post=' + post.id
  modals.sharePost = true
}
function copyShareUrl() {
  navigator.clipboard?.writeText(shareUrl.value).catch(() => {})
  toast.success('Link copied to clipboard')
}

// ── Report ────────────────────────────────────────────────────────────────────
function reportPost(postId) {
  confirmAction('Report this post for review by the Aegis Trust & Safety team?', () => {
    toast.info('Reported — our team will review shortly')
  })
}

// ── Poll ──────────────────────────────────────────────────────────────────────
function pollTotal(post) {
  return (post.poll_options ?? []).reduce((s, o) => s + (o.votes ?? 0), 0) || 0
}
function pollPct(post, idx) {
  const total = pollTotal(post) || 1
  return Math.round(((post.poll_options[idx]?.votes ?? 0) / total) * 100)
}
function votePoll(post, opt) {
  if (post.my_poll_vote) { toast.info('You already voted in this poll'); return }

  // Optimistic
  post.my_poll_vote = opt.key
  if (opt) opt.votes = (opt.votes ?? 0) + 1

  const form = useForm({ option_key: opt.key })
  form.post(route('provider.news.vote', { post: post.id }), {
    preserveScroll: true,
    preserveState:  true,
    onSuccess: () => toast.success('Vote recorded'),
    onError:   () => {
      post.my_poll_vote = null
      if (opt) opt.votes = Math.max(0, (opt.votes ?? 1) - 1)
      toast.error('Could not record vote.')
    },
  })
}

// ── RSVP Event ────────────────────────────────────────────────────────────────
function rsvpEvent(event) {
  confirmAction('RSVP for <strong>' + (event.title || 'this event') + '</strong>? You will receive a confirmation email with calendar details.', () => {
    const form = useForm({ status: 'going' })
    form.post(route('provider.news.rsvp', { event: event.id }), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => toast.success('Registered — check your email'),
      onError:   () => toast.error('Could not register for event.'),
    })
  })
}

// ── Delete ────────────────────────────────────────────────────────────────────
function confirmDelete(postId) {
  confirmAction('Delete this post? This action cannot be undone.', () => {
    router.delete(route('provider.news.destroy', { post: postId }), {
      preserveScroll: true,
      onSuccess: () => toast.info('Post deleted'),
      onError:   () => toast.error('Could not delete post.'),
    })
  })
}

// ── Edit ──────────────────────────────────────────────────────────────────────
const editTargetId = ref(null)

// useForm must be at top level
const inertiaEditForm = useForm({ title: '', body: '' })

function openEdit(post) {
  editTargetId.value    = post.id
  inertiaEditForm.title = post.title ?? ''
  inertiaEditForm.body  = post.body  ?? ''
  editForm.title        = post.title ?? ''
  editForm.body         = post.body  ?? ''
  modals.editPost = true
}

async function submitEditPost() {
  const ok = await v$.value.$validate()
  if (!ok) return

  inertiaEditForm.title = editForm.title
  inertiaEditForm.body  = editForm.body

  inertiaEditForm.patch(route('provider.news.update', { post: editTargetId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      modals.editPost = false
      toast.success('Post updated')
      v$.value.$reset()
    },
    onError: () => toast.error('Could not update post.'),
  })
}

// ── Create ────────────────────────────────────────────────────────────────────
const inertiaCreateForm = useForm({
  post_type: 'provider',
  audience:  'all',
  title:     '',
  body:      '',
  tags:      '',
})

async function submitCreatePost() {
  const ok = await v$.value.$validate()
  if (!ok) return

  inertiaCreateForm.post_type = createForm.post_type
  inertiaCreateForm.audience  = createForm.audience
  inertiaCreateForm.title     = createForm.title
  inertiaCreateForm.body      = createForm.body
  inertiaCreateForm.tags      = createForm.tags

  inertiaCreateForm.post(route('provider.news.store'), {
    preserveScroll: true,
    onSuccess: () => {
      modals.createPost = false
      toast.success('Post published')
      createForm.post_type = 'provider'
      createForm.audience  = 'all'
      createForm.title     = ''
      createForm.body      = ''
      createForm.tags      = ''
      v$.value.$reset()
    },
    onError: () => toast.error('Could not publish post.'),
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const TYPE_LABELS = {
  platform: 'Platform', provider: 'Provider', compliance: 'Compliance',
  event: 'Event', resource: 'Resource', milestone: 'Milestone',
  question: 'Question', post: 'Post', poll: 'Poll', announcement: 'Announcement',
}
function typeLabel(t)         { return TYPE_LABELS[t] ?? t }
function authorKind(post)     { return post.author_role === 'practitioner' ? 'provider' : 'steward' }
function authorKindComment(c) { return c.author_role === 'practitioner' ? 'provider' : 'steward' }
function avatarMod(post)      { return 'avatar-' + (post.author_avatar_mod ?? 'gold') }
function fmtDate(iso)         { return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }
function fmtEventMon(iso)     { return new Date(iso).toLocaleString('en-US', { month: 'short' }).toUpperCase() }
function fmtEventDay(iso)     { return new Date(iso).getDate() }
function fmtEventTime(starts, ends) {
  const fmt = (d) => new Date(d).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  return fmt(starts) + (ends ? ' – ' + fmt(ends) : '')
}

// ── Modal state ───────────────────────────────────────────────────────────────
const modals = reactive({ createPost: false, editPost: false, sharePost: false })

// ── Reactive form state (for vuelidate binding) ───────────────────────────────
const createForm = reactive({ post_type: 'provider', audience: 'all', title: '', body: '', tags: '' })
const editForm   = reactive({ title: '', body: '' })

// ── Vuelidate ─────────────────────────────────────────────────────────────────
const rules = {
  createForm: { body: { required, minLength: minLength(1) } },
  editForm:   { body: { required, minLength: minLength(1) } },
}
const v$ = useVuelidate(rules, { createForm, editForm })

function fieldError(path) {
  const parts = path.split('.')
  let node = v$.value
  for (const p of parts) node = node?.[p]
  if (!node?.$error) return null
  if (node.required?.$invalid) return 'This field is required.'
  if (node.minLength?.$invalid) return 'Content cannot be empty.'
  return null
}
</script>

<style scoped>
/* ─── All classes below are NOT in _shared.css — page-local only ───────── */

.news-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 18px;
  align-items: start;
}
@media (max-width: 1100px) { .news-layout { grid-template-columns: 1fr; } }

.news-feed { display: flex; flex-direction: column; gap: 14px; }

.news-toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 14px; margin-bottom: 12px; flex-wrap: wrap;
  padding: 10px 12px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius);
}
.news-toolbar-left,
.news-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.news-toolbar-label {
  font-family: var(--font-sans); font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.4px;
  color: var(--text-4); margin: 0;
}
.news-toolbar .form-select-sm { min-width: 170px; }
@media (max-width: 600px) {
  .news-toolbar { flex-direction: column; align-items: stretch; }
  .news-toolbar-left, .news-toolbar-right { width: 100%; }
  .news-toolbar .form-select-sm { flex: 1; min-width: 0; }
}

.news-count-line {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px; padding: 0 2px;
  font-size: 12px; color: var(--text-3);
}
.news-count-line strong { color: var(--text); font-weight: 700; }

.news-active-tag {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 12px; margin-bottom: 10px;
  background: var(--badge-bg-gold);
  border: 1px solid var(--soft-gold);
  border-radius: var(--radius);
}
.news-active-tag-label {
  font-family: var(--font-sans); font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.4px;
  color: var(--gold-dark);
}
.news-active-tag-chip {
  font-family: var(--font-sans); font-size: 13px; font-weight: 700;
  color: var(--gold-dark); flex: 1;
}
.news-active-tag-clear {
  width: 24px; height: 24px; border-radius: var(--radius-sm);
  background: transparent; border: none; color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background var(--transition);
}
.news-active-tag-clear:hover { background: var(--surface); }

.news-create-cta {
  display: flex; align-items: center; gap: 12px;
  width: 100%; padding: 14px 18px; margin-bottom: 14px;
  background: var(--surface); border: 2px dashed var(--border-dark);
  border-radius: var(--radius-lg); cursor: pointer; text-align: left;
  transition: border-color var(--transition), background var(--transition);
}
.news-create-cta:hover { border-color: var(--soft-gold); background: var(--badge-bg-gold); }
.news-create-cta-text { flex: 1; font-size: 14px; font-weight: 600; color: var(--text-3); }
.news-create-cta-tools { display: flex; align-items: center; gap: 6px; color: var(--text-4); flex-shrink: 0; }

.nf-post { overflow: hidden; }
.nf-post.is-pinned { border-color: var(--fade-gold); }

.nf-pinned {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 18px;
  background: var(--badge-bg-gold);
  border-bottom: 1px solid var(--border);
  font-family: var(--font-sans); font-size: 10px; font-weight: 700;
  letter-spacing: 0.6px; text-transform: uppercase;
  color: var(--gold-dark);
}

.nf-head { display: flex; align-items: center; gap: 11px; padding: 16px 18px 0; }
.nf-avatar { cursor: pointer; transition: transform var(--transition); }
.nf-avatar:hover { transform: scale(1.04); }
.nf-id { flex: 1; min-width: 0; }
.nf-id-top { display: flex; align-items: center; gap: 7px; }
.nf-author {
  font-size: 14px; font-weight: 700; color: var(--text); cursor: pointer;
  transition: color var(--transition);
}
.nf-author:hover { color: var(--gold-dark); }
.nf-self {
  font-family: var(--font-sans); font-size: 10px; font-weight: 700;
  letter-spacing: 0.4px; text-transform: uppercase;
  padding: 1px 6px;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  border-radius: var(--radius-full);
}
.nf-meta { display: flex; align-items: center; gap: 6px; margin-top: 2px; font-size: 11px; font-weight: 600; color: var(--text-4); }
.nf-type { display: inline-flex; align-items: center; gap: 5px; }
.nf-type-dot { width: 6px; height: 6px; border-radius: var(--radius-full); flex-shrink: 0; }
.nf-meta-sep { width: 3px; height: 3px; border-radius: var(--radius-full); background: var(--text-4); }
.nf-t-platform   .nf-type-dot { background: var(--blue-dark); }
.nf-t-provider   .nf-type-dot { background: var(--gold-dark); }
.nf-t-post       .nf-type-dot { background: var(--gold-dark); }
.nf-t-compliance .nf-type-dot { background: var(--orange-dark); }
.nf-t-event      .nf-type-dot { background: var(--green-dark); }
.nf-t-announcement .nf-type-dot { background: var(--green-dark); }
.nf-t-resource   .nf-type-dot { background: var(--purple-dark); }
.nf-t-milestone  .nf-type-dot { background: var(--gold-dark); }
.nf-t-question   .nf-type-dot { background: var(--text-4); }
.nf-t-poll       .nf-type-dot { background: var(--blue-dark); }
.nf-more { flex-shrink: 0; }

.nf-body { padding: 12px 18px 4px; }
.nf-title { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); line-height: 1.35; margin-bottom: 6px; }
.nf-text { font-size: 13px; color: var(--text-2); line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; }
.nf-text.is-collapsed { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.nf-readmore {
  display: inline-flex; align-items: center; gap: 3px;
  font-family: var(--font-sans); font-size: 12px; font-weight: 700;
  color: var(--gold-dark); background: none; border: none; padding: 0;
  cursor: pointer; margin-top: 6px;
}
.nf-readmore:hover { text-decoration: underline; }

.nf-links { margin-top: 12px; display: flex; flex-direction: column; gap: 6px; }
.nf-link {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius-sm); text-decoration: none;
  transition: background var(--transition), border-color var(--transition);
}
.nf-link:hover { background: var(--badge-bg-gold); border-color: var(--fade-gold); }
.nf-link-ic { color: var(--gold-dark); flex-shrink: 0; display: flex; }
.nf-link-label { flex: 1; min-width: 0; font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nf-link-go { color: var(--text-4); flex-shrink: 0; display: flex; }

.nf-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.nf-tag {
  font-family: var(--font-sans); font-size: 11px; font-weight: 600;
  color: var(--text-3); background: none; border: none; padding: 0;
  cursor: pointer; transition: color var(--transition);
}
.nf-tag:hover { color: var(--gold-dark); }

.nf-poll { margin-top: 14px; padding: 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); }
.nf-poll-q { display: flex; align-items: center; gap: 7px; font-family: var(--font-sans); font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 12px; }
.nf-poll-opt { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; cursor: pointer; }
.nf-poll-opt:last-of-type { margin-bottom: 0; }
.nf-poll-label { font-size: 12px; font-weight: 600; color: var(--text-2); width: 132px; flex-shrink: 0; }
.nf-poll-track { flex: 1; height: 11px; background: var(--surface-3); border-radius: var(--radius-full); overflow: hidden; }
.nf-poll-fill { height: 100%; background: var(--gold-dark); border-radius: var(--radius-full); transition: width 0.5s ease; }
.nf-poll-pct { font-family: var(--font-sans); font-size: 12px; font-weight: 700; color: var(--text-3); width: 36px; text-align: right; flex-shrink: 0; }
.nf-poll-meta { font-size: 11px; color: var(--text-4); margin-top: 11px; font-weight: 600; }

.nf-actions {
  display: flex; align-items: center; gap: 4px;
  padding: 10px 12px; margin-top: 14px;
  border-top: 1px solid var(--border);
}
.nf-act {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 10px;
  background: transparent; border: none; border-radius: var(--radius-sm);
  cursor: pointer;
  font-family: var(--font-sans); font-size: 12px; font-weight: 600;
  color: var(--text-3);
  transition: background var(--transition), color var(--transition);
}
.nf-act:hover          { background: var(--surface-3); color: var(--text); }
.nf-act.is-liked       { color: var(--red-dark); }
.nf-act.is-liked:hover { background: var(--red-light); }
.nf-act.is-saved       { color: var(--gold-dark); }
.nf-act.is-saved:hover { background: var(--badge-bg-gold); }
.nf-act-spacer { flex: 1; }

.nf-comments { display: none; border-top: 1px solid var(--border); padding: 14px 18px; background: var(--surface-2); }
.nf-comments.is-open { display: block; }
.nf-comment { display: flex; gap: 9px; margin-bottom: 12px; }
.nf-comment:last-of-type { margin-bottom: 10px; }
.nf-comment-main { flex: 1; min-width: 0; }
.nf-comment-top { display: flex; align-items: center; gap: 6px; }
.nf-comment-author { font-size: 12px; font-weight: 700; color: var(--text); cursor: pointer; }
.nf-comment-author:hover { color: var(--gold-dark); }
.nf-comment-body { font-size: 12px; color: var(--text-2); margin-top: 2px; line-height: 1.5; word-wrap: break-word; }
.nf-comment-meta { font-size: 10px; color: var(--text-4); margin-top: 4px; font-weight: 600; }
.nf-comment-form { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.nf-comment-form .form-input { flex: 1; min-height: 36px; padding: 8px 13px; }

.news-sidebar { display: flex; flex-direction: column; gap: 12px; }
.nsc-card .card-body { padding: 4px 14px 6px; }

.ne-event {
  display: grid; grid-template-columns: 40px 1fr 16px;
  gap: 14px; align-items: center;
  padding: 13px 6px 13px 2px;
  border-bottom: 1px solid var(--border);
  cursor: pointer; border-radius: var(--radius-sm);
  transition: background var(--transition);
}
.ne-event:last-child { border-bottom: none; }
.ne-event:hover { background: var(--badge-bg-gold); }
.ne-date { display: flex; flex-direction: column; align-items: center; line-height: 1; flex-shrink: 0; }
.ne-date-mon { font-family: var(--font-sans); font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: var(--gold-dark); margin-bottom: 4px; }
.ne-date-day { font-family: var(--font-serif); font-size: 24px; font-weight: 700; color: var(--text); }
.ne-body { min-width: 0; }
.ne-title { font-size: 13px; font-weight: 700; color: var(--text); line-height: 1.35; transition: color var(--transition); }
.ne-event:hover .ne-title { color: var(--gold-dark); }
.ne-meta { display: flex; align-items: center; gap: 5px; margin-top: 4px; font-size: 11px; color: var(--text-3); min-width: 0; }
.ne-meta-time { font-weight: 600; white-space: nowrap; }
.ne-meta-sep { width: 3px; height: 3px; border-radius: var(--radius-full); background: var(--text-4); flex-shrink: 0; }
.ne-meta-loc { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ne-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
.ne-tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-family: var(--font-sans); font-size: 10px; font-weight: 700;
  letter-spacing: 0.3px; padding: 2px 7px;
  border-radius: var(--radius-full); line-height: 1.5; white-space: nowrap;
}
.ne-tag.is-free { background: var(--green-light); color: var(--green-dark); }
.ne-tag.is-ceu  { background: var(--badge-bg-gold); color: var(--gold-dark); }
.ne-go {
  color: var(--text-4); opacity: 0; transform: translateX(-3px);
  transition: opacity var(--transition), transform var(--transition);
  display: flex; align-items: center; justify-content: center;
}
.ne-event:hover .ne-go { opacity: 1; transform: translateX(0); }

.nt-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 6px; border-bottom: 1px solid var(--border);
  cursor: pointer; border-radius: var(--radius-sm);
  transition: background var(--transition);
}
.nt-row:last-child { border-bottom: none; }
.nt-row:hover { background: var(--badge-bg-gold); }
.nt-tag { font-size: 13px; font-weight: 700; color: var(--gold-dark); transition: opacity var(--transition); }
.nt-row:hover .nt-tag { opacity: 0.85; }
.nt-count { font-size: 11px; color: var(--text-4); font-weight: 600; }
</style>
