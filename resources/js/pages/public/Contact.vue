<!--
  pages/public/Contact.vue — Contact form + direct contact info.
  Route: GET /contact (contact) · POST /contact (contact.send)
  Layout: PublicLayout (no auth required)
-->
<template>
  <Head title="Contact — Aegis" />
  <PublicLayout>

    <section class="public-hero public-hero--quiet">
      <div class="public-hero-inner">
        <div class="public-hero-eyebrow">Get in touch</div>
        <h1 class="public-hero-title">We're here to help.</h1>
        <p class="public-hero-sub">
          Questions about Aegis, your account, or our services? Reach out directly
          or send us a message and we'll respond within one business day.
        </p>
      </div>
    </section>

    <section class="public-section">
      <div class="public-section-inner public-two-col">

        <!-- Contact details -->
        <div>
          <h2 class="public-section-title">Contact details</h2>

          <div class="contact-info-list">
            <div class="contact-info-item">
              <div class="contact-info-icon">
                <AegisIcon name="phone" :size="20" />
              </div>
              <div class="contact-info-body">
                <div class="contact-info-label">Phone</div>
                <a href="tel:+19094889415" class="contact-info-value">(909) 488-9415</a>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-info-icon">
                <AegisIcon name="mail" :size="20" />
              </div>
              <div class="contact-info-body">
                <div class="contact-info-label">Email</div>
                <a href="mailto:support@maatpracticefirm.com" class="contact-info-value">
                  support@maatpracticefirm.com
                </a>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-info-icon">
                <AegisIcon name="clock" :size="20" />
              </div>
              <div class="contact-info-body">
                <div class="contact-info-label">Response time</div>
                <div class="contact-info-value">Within one business day</div>
              </div>
            </div>
          </div>

          <a
            href="mailto:support@maatpracticefirm.com?subject=Aegis Inquiry"
            class="btn btn-outline contact-email-btn"
          >
            <AegisIcon name="mail" :size="14" />
            Email us directly
          </a>
        </div>

        <!-- Contact form -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Send a message</span>
          </div>
          <div class="card-body">

            <!-- Success state -->
            <div v-if="$page.props.flash?.success" class="alert alert--success">
              <AegisIcon name="check-circle" :size="16" />
              <span>{{ $page.props.flash.success }}</span>
            </div>

            <form v-else @submit.prevent="submit" novalidate>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="contact-name">Full name</label>
                  <input
                    id="contact-name"
                    v-model="form.name"
                    type="text"
                    class="form-input"
                    :class="{ 'is-error': fieldError('name') }"
                    placeholder="Your name"
                    @blur="v$.name.$touch()"
                    @input="form.clearErrors('name')"
                  />
                  <div v-if="fieldError('name')" class="form-error">{{ fieldError('name') }}</div>
                </div>
                <div class="form-group">
                  <label class="form-label" for="contact-email">Email address</label>
                  <input
                    id="contact-email"
                    v-model="form.email"
                    type="email"
                    class="form-input"
                    :class="{ 'is-error': fieldError('email') }"
                    placeholder="you@example.com"
                    @blur="v$.email.$touch()"
                    @input="form.clearErrors('email')"
                  />
                  <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="contact-subject">Subject</label>
                <input
                  id="contact-subject"
                  v-model="form.subject"
                  type="text"
                  class="form-input"
                  :class="{ 'is-error': fieldError('subject') }"
                  placeholder="What's this about?"
                  @blur="v$.subject.$touch()"
                  @input="form.clearErrors('subject')"
                />
                <div v-if="fieldError('subject')" class="form-error">{{ fieldError('subject') }}</div>
              </div>

              <div class="form-group">
                <label class="form-label" for="contact-message">Message</label>
                <textarea
                  id="contact-message"
                  v-model="form.message"
                  class="form-textarea"
                  :class="{ 'is-error': fieldError('message') }"
                  rows="5"
                  placeholder="Tell us how we can help..."
                  @blur="v$.message.$touch()"
                  @input="form.clearErrors('message')"
                />
                <div v-if="fieldError('message')" class="form-error">{{ fieldError('message') }}</div>
              </div>

              <button
                type="submit"
                class="btn btn-primary btn-block"
                :disabled="form.processing"
              >
                {{ form.processing ? 'Sending…' : 'Send message' }}
              </button>
            </form>

          </div>
        </div>

      </div>
    </section>

  </PublicLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const form = useForm({
  name:    '',
  email:   '',
  subject: '',
  message: '',
})

const rules = computed(() => ({
  name: {
    required: helpers.withMessage('Name is required.', required),
  },
  email: {
    required: helpers.withMessage('Email is required.', required),
    email:    helpers.withMessage('Enter a valid email address.', email),
  },
  subject: {
    required: helpers.withMessage('Subject is required.', required),
  },
  message: {
    required: helpers.withMessage('Message is required.', required),
    min:      helpers.withMessage('Please write at least 10 characters.', minLength(10)),
  },
}))

const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (form.errors[field])      return form.errors[field]
  return null
}

async function submit() {
  const valid = await v$.value.$validate()
  if (!valid) return
  form.post(route('contact.send'), {
    onSuccess: () => { form.reset(); v$.value.$reset() },
    onError:   () => toast.error('Something went wrong. Please try again.'),
  })
}
</script>

<style scoped>
.contact-email-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 24px;
}
</style>
