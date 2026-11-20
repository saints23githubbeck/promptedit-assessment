<script setup>
import {useForm, Link} from '@inertiajs/vue3';

const form = useForm({email: '', password: '', remember: false});

function submit() {
  form.post('/login')
}
</script>
<template>
  <main class="auth-shell">
    <section class="auth-card">
      <div class="brand auth-brand"><span class="brand-mark">P</span><span>promptedit<span class="muted"> studio</span></span>
      </div>
      <span class="eyebrow">WELCOME BACK</span>
      <h1>Sign in to your studio</h1>
      <p class="auth-subtitle">Pick up where your creativity left off.</p>
      <form @submit.prevent="submit"><label class="field-label">Email address</label><input v-model="form.email"
                                                                                            type="email"
                                                                                            autocomplete="email"
                                                                                            required
                                                                                            placeholder="you@example.com">
        <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
        <label class="field-label">Password</label><input v-model="form.password" type="password"
                                                          autocomplete="current-password" required
                                                          placeholder="Your password">
        <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
        <label class="remember"><input v-model="form.remember" type="checkbox"> Keep me signed in</label>
        <button class="primary-button full" :disabled="form.processing">
          {{ form.processing ? 'Signing in…' : 'Sign in' }} <span>→</span></button>
      </form>
      <p class="auth-footer">New to PromptEdit?
        <Link href="/register">Create an account</Link>
      </p>
    </section>
  </main>
</template>
