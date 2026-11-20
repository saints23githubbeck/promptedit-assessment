<script setup>
import {useForm, Link} from '@inertiajs/vue3';

const form = useForm({name: '', email: '', password: '', password_confirmation: ''});

function submit() {
  form.post('/register')
}
</script>
<template>
  <main class="auth-shell">
    <section class="auth-card">
      <div class="brand auth-brand"><span class="brand-mark">P</span><span>promptedit<span class="muted"> studio</span></span>
      </div>
      <span class="eyebrow">GET STARTED</span>
      <h1>Create your studio</h1>
      <p class="auth-subtitle">A workspace for ideas worth seeing.</p>
      <form @submit.prevent="submit"><label class="field-label">Your name</label><input v-model="form.name"
                                                                                        autocomplete="name" required
                                                                                        placeholder="Full name">
        <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
        <label class="field-label">Email address</label><input v-model="form.email" type="email" autocomplete="email"
                                                               required placeholder="you@example.com">
        <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
        <label class="field-label">Password</label><input v-model="form.password" type="password"
                                                          autocomplete="new-password" required
                                                          placeholder="At least 8 characters">
        <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
        <label class="field-label">Confirm password</label><input v-model="form.password_confirmation" type="password"
                                                                  autocomplete="new-password" required
                                                                  placeholder="Enter password again">
        <button class="primary-button full" :disabled="form.processing">
          {{ form.processing ? 'Creating account…' : 'Create account' }} <span>→</span></button>
      </form>
      <p class="auth-footer">Already have an account?
        <Link href="/login">Sign in</Link>
      </p>

    </section>
  </main>
</template>
