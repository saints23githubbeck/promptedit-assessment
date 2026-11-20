
<script setup>
import { Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
  generations: Object,
});

const money = v => v == null ? '—' : `$${Number(v).toFixed(9)}`;

const statusLabel = s => ({
  queued: 'Queued',
  processing: 'Processing',
  succeeded: 'Complete',
  failed: 'Failed',
}[s] || s);

function refresh() {
  router.reload({
    only: ['generations'],
  });
}

let poller;

onMounted(() => {
  poller = window.setInterval(() => {
    if (
        props.generations.data.some(g =>
            ['queued', 'processing'].includes(g.status)
        )
    ) {
      refresh();
    }
  }, 5000);
});

onUnmounted(() => {
  window.clearInterval(poller);
});
</script>

<template>
  <AppLayout>
    <template #title>Generation history</template>

    <template #actions>
      <Link
          :href="route('generate.create')"
          class="primary-button"
      >
        ＋ New generation
      </Link>
    </template>

    <!-- Page navigation -->
    <div class="page-navigation">
      <Link :href="route('generate.create')">
        Generate
      </Link>

      <Link :href="route('history.index')" class="active">
        History
      </Link>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
      <div class="stat-card">
        <span>GENERATIONS</span>
        <strong>{{ generations.total }}</strong>
        <small>Across your workspace</small>
      </div>

      <div class="stat-card">
        <span>THIS PAGE</span>
        <strong>
          {{
            generations.data.filter(
                g => g.status === 'succeeded'
            ).length
          }}
        </strong>
        <small>Successful images</small>
      </div>
    </div>

    <!-- Section heading -->
    <div class="section-heading">
      <div>
        <h2>Your creations</h2>
        <p>Track every generation and its final cost.</p>
      </div>

      <button
          class="subtle-button"
          @click="refresh"
      >
        ↻ Refresh status
      </button>
    </div>

    <!-- Empty state -->
    <div
        v-if="!generations.data.length"
        class="panel empty-state"
    >
      <div class="preview-art">✧</div>

      <h3>Nothing here yet</h3>

      <p>
        Your generated images and their costs will appear here.
      </p>

      <Link
          :href="route('generate.create')"
          class="primary-button"
      >
        Create your first image
      </Link>
    </div>

    <!-- Generation list -->
    <div v-else class="history-list">
      <article
          v-for="item in generations.data"
          :key="item.id"
          class="history-card"
      >
        <div class="thumb">
          <img
              v-if="item.image_url && item.status === 'succeeded'"
              :src="item.image_url"
              :alt="item.prompt"
          >

          <div v-else class="thumb-placeholder">
            {{ item.status === 'failed' ? '!' : '✧' }}
          </div>
        </div>

        <div class="history-content">
          <div class="history-top">
                        <span
                            class="status"
                            :class="item.status"
                        >
                            <i></i>
                            {{ statusLabel(item.status) }}
                        </span>

            <time>
              {{ new Date(item.created_at).toLocaleString() }}
            </time>
          </div>

          <h3>{{ item.prompt }}</h3>

          <p>
            {{ item.size }} · {{ item.quality }} quality
          </p>

          <p
              v-if="item.error"
              class="field-error"
          >
            {{ item.error }}
          </p>

          <div class="cost-row">
                        <span>
                            Estimate
                            <b>{{ money(item.estimated_cost) }}</b>
                        </span>

            <span>
                            Final charge
                            <b>{{ money(item.actual_cost) }}</b>
                        </span>
          </div>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <div
        v-if="generations.links?.length > 3"
        class="pagination"
    >
      <template
          v-for="link in generations.links"
          :key="link.label"
      >
        <Link
            v-if="link.url"
            :href="link.url"
            :class="{ active: link.active }"
            preserve-scroll
            v-html="link.label"
        />

        <span
            v-else
            class="disabled"
            v-html="link.label"
        />
      </template>
    </div>
  </AppLayout>
</template>
