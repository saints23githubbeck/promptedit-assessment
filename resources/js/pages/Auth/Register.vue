<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { url } from '@/utils/url';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

function submit() {
  form.post(url('/register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}
</script>

<template>
  <Head title="Create an account" />

  <main class="auth-shell">
    <section class="auth-card">
      <div class="brand auth-brand">
        <span class="brand-mark">P</span>
        <span>
                    promptedit<span class="muted"> studio</span>
                </span>
      </div>

      <span class="eyebrow">GET STARTED</span>
      <h1>Create your studio account</h1>
      <p class="auth-subtitle">
        Start generating images in under a minute.
      </p>

      <form @submit.prevent="submit" novalidate>
        <label class="field-label" for="name">Your name</label>
        <input
            id="name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            required
            autofocus
            placeholder="Ada Lovelace"
            :aria-invalid="!!form.errors.name"
        />
        <div v-if="form.errors.name" class="field-error" role="alert">
          {{ form.errors.name }}
        </div>

        <label class="field-label" for="email">Mail address</label>
        <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            inputmode="email"
            required
            placeholder="you@example.com"
            :aria-invalid="!!form.errors.email"
        />
        <div v-if="form.errors.email" class="field-error" role="alert">
          {{ form.errors.email }}
        </div>

        <label class="field-label" for="password">Password</label>
        <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            required
            placeholder="At least 8 characters"
            :aria-invalid="!!form.errors.password"
        />
        <div v-if="form.errors.password" class="field-error" role="alert">
          {{ form.errors.password }}
        </div>

        <label class="field-label" for="password_confirmation">Confirm password</label>
        <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
            placeholder="Repeat your password"
        />

        <button
            class="primary-button full"
            type="submit"
            :disabled="form.processing"
        >
          <template v-if="form.processing">
            Creating account…
          </template>
          <template v-else>
            Create account <span aria-hidden="true">→</span>
          </template>
        </button>
      </form>

      <p class="auth-footer">
        Already have an account?
        <Link :href="url('/login')">Sign in</Link>
      </p>
    </section>
  </main>
</template>