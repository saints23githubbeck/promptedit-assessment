<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3';
import {computed} from 'vue';
import {url} from '@/utils/url';

const props = defineProps({
  canResetPassword: {type: Boolean, default: true},
  status: {type: String, default: null},
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const hasErrors = computed(() =>
    Object.keys(form.errors).length > 0
);

function submit() {
  form.post(url('/login'), {
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <Head title="Sign in"/>

  <main class="auth-shell">
    <section class="auth-card">
      <div class="brand auth-brand">
        <span class="brand-mark">P</span>
        <span>
                    promptedit<span class="muted"> studio</span>
                </span>
      </div>

      <span class="eyebrow">WELCOME BACK</span>
      <h1>Sign in to your studio</h1>
      <p class="auth-subtitle">
        Pick up where your creativity left off.
      </p>

      <!-- Flash status (e.g. "password reset link sent") -->
      <div v-if="status" class="field-success" role="status">
        {{ status }}
      </div>

      <form @submit.prevent="submit" novalidate>
        <label class="field-label" for="email">Mail address</label>
        <input
            id="email"
            v-model="form.email"
            type="email"
            name="email"
            autocomplete="email"
            inputmode="email"
            required
            autofocus
            placeholder="you@example.com"
            :aria-invalid="!!form.errors.email"
            :aria-describedby="form.errors.email ? 'email-error' : undefined"
        />
        <div
            v-if="form.errors.email"
            id="email-error"
            class="field-error"
            role="alert"
        >
          {{ form.errors.email }}
        </div>

        <div class="field-label-row">
          <label class="field-label" for="password">Password</label>
<!--          <Link-->
<!--              v-if="canResetPassword"-->
<!--              :href="url('/forgot-password')"-->
<!--              class="field-inline-link"-->
<!--          >-->
<!--            Forgot?-->
<!--          </Link>-->
        </div>
        <input
            id="password"
            v-model="form.password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
            placeholder="Your password"
            :aria-invalid="!!form.errors.password"
            :aria-describedby="form.errors.password ? 'password-error' : undefined"
        />
        <div
            v-if="form.errors.password"
            id="password-error"
            class="field-error"
            role="alert"
        >
          {{ form.errors.password }}
        </div>

        <label class="remember">
          <input
              v-model="form.remember"
              type="checkbox"
              name="remember"
          />
          Keep me signed in
        </label>
        <button
            class="primary-button full"
            type="submit"
            :disabled="form.processing || !form.email || !form.password"
        >
          <template v-if="form.processing">
            Signing in…
          </template>
          <template v-else>
            Sign in <span aria-hidden="true">→</span>
          </template>
        </button>
      </form>

      <p class="auth-footer">
        New to PromptEdit?
        <Link :href="url('/register')">Create an account</Link>
      </p>
    </section>
  </main>
</template>