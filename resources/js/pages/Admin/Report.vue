
<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

defineProps({
  days: Array,
  range: Object,
  provider_total: String,
});

const money = v => `$${Number(v ?? 0).toFixed(9)}`;
</script>

<template>
  <AppLayout>
    <template #title>Usage & Profit</template>

    <!-- Page navigation -->
    <div class="page-navigation">
      <Link :href="route('generate.create')">
        Generate
      </Link>

      <Link :href="route('admin.report')" class="active">
        Usage & Profit
      </Link>
    </div>

    <div class="panel">
      <div class="report-header">
        <div>
          <span class="eyebrow">PROVIDER SOURCE OF TRUTH</span>

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
            v-for="u in day.users"
            :key="u.user"
            class="report-user"
        >
          <span>{{ u.user }}</span>

          <span>
                        {{ money(u.charged) }} charged ·
                        {{ money(u.provider_cost) }} provider ·
                        {{ money(u.profit) }} profit
                    </span>
        </div>
      </div>

      <p v-if="!days.length">
        No usage in this date range.
      </p>
    </div>
  </AppLayout>
</template>
