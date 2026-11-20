
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import { url } from '@/utils/url';

defineProps({
  days: {
    type: Array,
    default: () => [],
  },
  range: {
    type: Object,
    default: () => ({
      start: '',
      end: '',
    }),
  },
  provider_total: {
    type: [String, Number],
    default: 0,
  },
});

const money = value => `$${Number(value ?? 0).toFixed(9)}`;
</script>

<template>
  <Head title="Usage & Profit" />

  <AppLayout>
    <template #title>
      Usage & Profit
    </template>

    <!-- Page navigation -->
    <div class="page-navigation">
      <Link :href="url('/generate')">
        Generate
      </Link>

      <Link
          :href="url('/admin/report')"
          class="active"
      >
        Usage & Profit
      </Link>
    </div>

    <div class="panel">
      <div class="report-header">
        <div>
                    <span class="eyebrow">
                        PROVIDER SOURCE OF TRUTH
                    </span>

          <h2>{{ money(provider_total) }}</h2>

          <p class="helper">
            Pixelforge usage ·
            {{ range.start }} to {{ range.end }} UTC
          </p>
        </div>
      </div>

      <p class="helper">
        Daily provider cost comes directly from GET /v1/usage.
        Local generation costs are reconciled against that amount.
      </p>

      <div
          v-for="day in days"
          :key="day.date"
          class="report-day"
      >
        <h3>
          {{ day.date }}

          <span class="helper">
                        · {{ day.provider_generations }} provider generations
                    </span>
        </h3>

        <div class="report-totals">
                    <span>
                        Credit spend
                        <b>{{ money(day.charged) }}</b>
                    </span>

          <span>
                        Pixelforge cost
                        <b>{{ money(day.provider_cost) }}</b>
                    </span>

          <span>
                        Profit
                        <b>{{ money(day.profit) }}</b>
                    </span>
        </div>

        <p class="helper">
          Local provider-cost sum:
          {{ money(day.local_provider_cost) }}
          · Reconciliation delta:
          {{ money(day.provider_delta) }}
        </p>

        <div
            v-for="user in day.users"
            :key="user.user"
            class="report-user"
        >
          <span>{{ user.user }}</span>

          <span>
                        {{ money(user.charged) }} charged ·
                        {{ money(user.provider_cost) }} provider ·
                        {{ money(user.profit) }} profit
                    </span>
        </div>
      </div>

      <p v-if="!days.length">
        No usage in this date range.
      </p>
    </div>
  </AppLayout>
</template>
