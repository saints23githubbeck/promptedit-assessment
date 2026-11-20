
<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { url } from '@/utils/url';

const page = usePage();

function isActive(path) {
  const currentPath = page.url.split('?')[0];
  const targetPath = url(path);

  return currentPath === targetPath ||
      currentPath.startsWith(`${targetPath}/`);
}

function logout() {
  router.post(url('/logout'));
}
</script>

<template>
  <div class="shell">
    <aside class="sidebar">
      <!-- Brand -->
      <div class="brand">
        <span class="brand-mark">P</span>

        <span>
                    promptedit<span class="muted"> studio</span>
                </span>
      </div>

      <!-- Navigation -->
      <div class="nav-label">
        WORKSPACE
      </div>

      <Link
          :href="url('/generate')"
          class="nav-link"
          :class="{ active: isActive('/generate') }"
      >
        ✦
        <span>New generation</span>
      </Link>

      <Link
          :href="url('/history')"
          class="nav-link"
          :class="{ active: isActive('/history') }"
      >
        ▤
        <span>History</span>
      </Link>

      <Link
          v-if="page.props.auth.user?.is_admin"
          :href="url('/admin/report')"
          class="nav-link"
          :class="{ active: isActive('/admin/report') }"
      >
        ▥
        <span>Usage report</span>
      </Link>

      <!-- User account -->
      <div class="sidebar-bottom">
        <div class="avatar">
          {{ page.props.auth.user?.name?.[0] || 'U' }}
        </div>

        <div class="user-meta">
          <strong>
            {{ page.props.auth.user?.name }}
          </strong>

          <small>
            {{ page.props.auth.user?.email }}
          </small>
        </div>

        <button
            class="icon-button"
            type="button"
            title="Sign out"
            aria-label="Sign out"
            @click="logout"
        >
          ↗
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="main">
      <header class="topbar">
        <div>
                    <span class="eyebrow">
                        CREATIVE WORKSPACE
                    </span>

          <h1>
            <slot name="title">
              Your studio
            </slot>
          </h1>
        </div>

        <slot name="actions" />
      </header>

      <!-- Success notification -->
      <div
          v-if="page.props.flash?.success"
          class="flash"
      >
        {{ page.props.flash.success }}
      </div>

      <slot />
    </main>
  </div>
</template>
