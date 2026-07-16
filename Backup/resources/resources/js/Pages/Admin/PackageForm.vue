<template>
  <AppLayout :user="user" portal="admin" activePage="packages" :pageTitle="mode === 'create' ? 'Create Package' : 'Edit Package'">
    <div class="settings-layout">
      <!-- Left sidebar summary info -->
      <div class="summary-card">
        <h3 class="summary-title">{{ mode === 'create' ? 'New Package' : 'Edit Package' }}</h3>
        <p class="summary-desc">Configure the pricing plans and features for the Aegis platform. Tiers range from Free up to custom Enterprise limits.</p>
        <div class="summary-meta" v-if="mode === 'edit' && package">
          <div class="meta-row">
            <span class="meta-label">ID:</span>
            <span class="meta-value code">{{ package.id }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Slug:</span>
            <span class="meta-value">{{ package.slug }}</span>
          </div>
        </div>
      </div>

      <!-- Main Form Panel -->
      <div class="settings-panel">
        <h2 class="settings-section-title">Package Details</h2>
        <p class="settings-section-desc">Fill in the fields below to update package configurations.</p>

        <form @submit.prevent="submit">
          <div class="pg-form-group">
            <label class="pg-form-label">Package Name</label>
            <input
              v-model="form.name"
              type="text"
              class="pg-form-input"
              :class="{ error: form.errors.name }"
              placeholder="e.g. Practitioner Pro Plan"
              required
            />
            <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
          </div>

          <div class="pg-form-group">
            <label class="pg-form-label">Description</label>
            <textarea
              v-model="form.description"
              class="pg-form-input text-area"
              :class="{ error: form.errors.description }"
              placeholder="Describe what is included in this package..."
              rows="4"
            ></textarea>
            <span v-if="form.errors.description" class="form-error">{{ form.errors.description }}</span>
          </div>

          <div class="pg-form-row">
            <div class="pg-form-group">
              <label class="pg-form-label">Plan Tier</label>
              <select
                v-model="form.tier"
                class="pg-form-input select-input"
                :class="{ error: form.errors.tier }"
                required
              >
                <option value="free">Free</option>
                <option value="basic">Basic</option>
                <option value="pro">Pro</option>
                <option value="enterprise">Enterprise</option>
              </select>
              <span v-if="form.errors.tier" class="form-error">{{ form.errors.tier }}</span>
            </div>

            <div class="pg-form-group">
              <label class="pg-form-label">Max Allowed Users</label>
              <input
                v-model.number="form.max_users"
                type="number"
                min="1"
                class="pg-form-input"
                :class="{ error: form.errors.max_users }"
                required
              />
              <span v-if="form.errors.max_users" class="form-error">{{ form.errors.max_users }}</span>
            </div>
          </div>

          <div class="pg-form-row">
            <div class="pg-form-group">
              <label class="pg-form-label">Monthly Price ($)</label>
              <input
                v-model.number="form.price_monthly"
                type="number"
                step="0.01"
                min="0"
                class="pg-form-input"
                :class="{ error: form.errors.price_monthly }"
                required
              />
              <span v-if="form.errors.price_monthly" class="form-error">{{ form.errors.price_monthly }}</span>
            </div>

            <div class="pg-form-group">
              <label class="pg-form-label">Annual Price ($)</label>
              <input
                v-model.number="form.price_annual"
                type="number"
                step="0.01"
                min="0"
                class="pg-form-input"
                :class="{ error: form.errors.price_annual }"
                required
              />
              <span v-if="form.errors.price_annual" class="form-error">{{ form.errors.price_annual }}</span>
            </div>
          </div>

          <!-- DYNAMIC FEATURES CHECKLIST -->
          <div class="pg-form-group">
            <label class="pg-form-label">Key Features</label>
            <div class="feature-add-row">
              <input
                v-model="newFeature"
                type="text"
                class="pg-form-input"
                placeholder="Type a feature (e.g. HIPAA Compliant, End-to-End Encryption) and press Add"
                @keydown.enter.prevent="addFeature"
              />
              <button type="button" class="btn btn-outline btn-sm add-btn" @click="addFeature">Add</button>
            </div>
            <div class="features-list-wrapper">
              <div v-for="(feat, idx) in form.features" :key="idx" class="feature-item">
                <span class="feat-text">{{ feat }}</span>
                <button type="button" class="remove-feat-btn" @click="removeFeature(idx)">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
              <div v-if="form.features.length === 0" class="no-features-text">No features added yet. Add at least one feature to explain plan benefits.</div>
            </div>
          </div>

          <div class="pg-form-group checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_active" class="checkbox-input" />
              <span>Make plan active and visible to subscribers</span>
            </label>
          </div>

          <div class="form-actions">
            <a href="/admin/packages" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Save Package' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

const props = defineProps({
  user: Object,
  package: Object,
  mode: String,
});

const form = useForm({
  name: props.package?.name || '',
  description: props.package?.description || '',
  tier: props.package?.tier || 'free',
  price_monthly: props.package?.price_monthly ? parseFloat(props.package.price_monthly) : 0,
  price_annual: props.package?.price_annual ? parseFloat(props.package.price_annual) : 0,
  max_users: props.package?.max_users || 1,
  features: props.package?.features || [],
  is_active: props.package ? !!props.package.is_active : true,
});

const newFeature = ref('');

function addFeature() {
  const feat = newFeature.value.trim();
  if (feat) {
    form.features.push(feat);
    newFeature.value = '';
  }
}

function removeFeature(idx) {
  form.features.splice(idx, 1);
}

function submit() {
  if (props.mode === 'create') {
    form.post('/admin/packages');
  } else {
    form.put('/admin/packages/' + props.package.id);
  }
}
</script>

<style scoped>
.settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 28px; }
.summary-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); padding: 24px;
  height: fit-content;
}
.summary-title { font-family: var(--font-serif); font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 8px; }
.summary-desc { font-size: 12.5px; color: var(--text-3); line-height: 1.5; margin-bottom: 20px; }
.summary-meta { border-top: 1px solid var(--border); padding-top: 16px; display: flex; flex-direction: column; gap: 10px; }
.meta-row { display: flex; flex-direction: column; gap: 2px; }
.meta-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-4); }
.meta-value { font-size: 12.5px; color: var(--text); word-break: break-all; }
.meta-value.code { font-family: monospace; background: var(--surface-2); padding: 2px 6px; border-radius: 4px; font-size: 11px; }

.settings-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl, 18px); padding: 28px 30px; }
.settings-section-title { font-family: var(--font-serif); font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.settings-section-desc { font-size: 13px; color: var(--text-3); margin-bottom: 22px; line-height: 1.5; }

.pg-form-group { margin-bottom: 18px; }
.pg-form-label { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
.pg-form-input { display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm, 8px); transition: border-color var(--transition), box-shadow var(--transition); outline: none; }
.pg-form-input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,169,106,0.18); }
.pg-form-input.error { border-color: var(--red); }
.pg-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.text-area { resize: vertical; font-family: var(--font-sans); line-height: 1.5; }
.select-input { appearance: auto; cursor: pointer; }

.feature-add-row { display: flex; gap: 8px; margin-bottom: 12px; }
.add-btn { height: auto; }
.features-list-wrapper {
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 14px; display: flex; flex-direction: column; gap: 6px;
  min-height: 80px;
}
.feature-item {
  display: flex; justify-content: space-between; align-items: center;
  background: var(--surface); border: 1px solid var(--border);
  padding: 6px 12px; border-radius: var(--radius-sm);
}
.feat-text { font-size: 12.5px; color: var(--text-2); font-weight: 600; }
.remove-feat-btn { background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-4); display: flex; align-items: center; transition: color 0.18s; }
.remove-feat-btn:hover { color: var(--red); }
.no-features-text { font-size: 12px; color: var(--text-4); font-style: italic; text-align: center; margin-top: 14px; }

.checkbox-group { margin-top: 10px; margin-bottom: 24px; }
.checkbox-label { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-2); cursor: pointer; }
.checkbox-input { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border: 1px solid var(--border-dark); border-radius: 4px; background: var(--surface); cursor: pointer; transition: all var(--transition); }
.checkbox-input:checked { background: var(--gold); border-color: var(--gold); position: relative; }
.checkbox-input:checked::after { content: "\2713"; position: absolute; top: -1px; left: 3px; font-size: 13px; color: #fff; }

.form-actions { display: flex; gap: 10px; border-top: 1px solid var(--border); padding-top: 20px; justify-content: flex-end; }
.form-error { font-size: 11.5px; color: var(--red); margin-top: 4px; display: block; }

@media (max-width: 900px) {
  .settings-layout { grid-template-columns: 1fr; }
  .pg-form-row { grid-template-columns: 1fr; }
}
</style>
