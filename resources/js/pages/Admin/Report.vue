
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

const shortMoney = value => {
  const amount = Number(value ?? 0);

  if (Math.abs(amount) >= 1000) {
    return `$${amount.toLocaleString('en-US', {
      maximumFractionDigits: 2,
    })}`;
  }

  return money(amount);
};
</script>

<template>
  <Head title="Usage & Profit" />

  <AppLayout>
    <template #title>
      Financial Overview
    </template>

    <div class="report-page">

      <!-- Navigation -->
      <div class="report-toolbar">
        <div class="report-breadcrumb">
          <span>Workspace</span>
          <span class="breadcrumb-separator">/</span>
          <strong>Financial Overview</strong>
        </div>

        <div class="report-actions">
          <Link
              :href="url('/generate')"
              class="report-button report-button-secondary"
          >
            <span>＋</span>
            New generation
          </Link>

          <Link
              :href="url('/admin/report')"
              class="report-button report-button-primary"
          >
            Usage & Profit
          </Link>
        </div>
      </div>

      <!-- Heading -->
      <div class="report-intro">
        <div>
          <span class="report-eyebrow">FINANCIAL ANALYTICS</span>

          <h2>Usage & Profit</h2>

          <p>
            Monitor provider expenditure, credit revenue and
            generation profitability.
          </p>
        </div>

        <div class="report-period">
          <span class="period-label">REPORTING PERIOD</span>
          <strong>{{ range.start || '—' }}</strong>
          <span class="period-divider">to</span>
          <strong>{{ range.end || '—' }}</strong>
          <small>All dates in UTC</small>
        </div>
      </div>

      <!-- Provider total -->
      <section class="provider-summary">
        <div class="provider-summary-content">
          <div class="provider-icon">
            $
          </div>

          <div>
                        <span class="provider-label">
                            TOTAL PIXELFORGE PROVIDER COST
                        </span>

            <h3>{{ money(provider_total) }}</h3>

            <p>
              Official provider expenditure for the selected
              reporting period.
            </p>
          </div>
        </div>

        <div class="provider-source">
          <span class="source-dot"></span>
          Provider source of truth
        </div>
      </section>

      <!-- Daily reports -->
      <section class="daily-section">
        <div class="section-heading">
          <div>
            <span class="report-eyebrow">PERFORMANCE BREAKDOWN</span>
            <h3>Daily financial activity</h3>
            <p>
              Revenue, provider costs and profitability by day.
            </p>
          </div>

          <span class="days-count">
                        {{ days.length }} {{ days.length === 1 ? 'day' : 'days' }}
                    </span>
        </div>

        <div v-if="days.length" class="daily-list">
          <article
              v-for="day in days"
              :key="day.date"
              class="daily-card"
          >
            <!-- Date heading -->
            <div class="daily-card-header">
              <div>
                <span class="date-label">REPORT DATE</span>
                <h4>{{ day.date }}</h4>
              </div>

              <div class="generation-count">
                <span class="generation-indicator"></span>
                {{ day.provider_generations }} provider generations
              </div>
            </div>

            <!-- Financial metrics -->
            <div class="financial-grid">
              <div class="financial-metric">
                                <span class="metric-label">
                                    Credit revenue
                                </span>

                <strong>
                  {{ money(day.charged) }}
                </strong>

                <small>Customer credit spend</small>
              </div>

              <div class="financial-metric">
                                <span class="metric-label">
                                    Provider cost
                                </span>

                <strong>
                  {{ money(day.provider_cost) }}
                </strong>

                <small>Pixelforge expenditure</small>
              </div>

              <div class="financial-metric profit-metric">
                                <span class="metric-label">
                                    Net profit
                                </span>

                <strong>
                  {{ money(day.profit) }}
                </strong>

                <small>Revenue less provider cost</small>
              </div>
            </div>

            <!-- Reconciliation -->
            <div class="reconciliation">
              <div class="reconciliation-heading">
                <div>
                  <span class="reconciliation-icon">↔</span>
                  <strong>Cost reconciliation</strong>
                </div>

                <span class="reconciliation-caption">
                                    Local vs provider records
                                </span>
              </div>

              <div class="reconciliation-grid">
                <div>
                  <span>Local provider-cost sum</span>
                  <strong>
                    {{ money(day.local_provider_cost) }}
                  </strong>
                </div>

                <div>
                  <span>Reconciliation delta</span>
                  <strong>
                    {{ money(day.provider_delta) }}
                  </strong>
                </div>
              </div>

              <p>
                Daily provider cost comes directly from
                GET /v1/usage. Local generation costs are
                reconciled against that amount.
              </p>
            </div>

            <!-- User breakdown -->
            <div class="users-section">
              <div class="users-heading">
                <div>
                  <h5>User breakdown</h5>
                  <span>
                                        Revenue and provider expenditure by user
                                    </span>
                </div>

                <span class="users-count">
                                    {{ day.users?.length || 0 }} users
                                </span>
              </div>

              <div
                  v-if="day.users?.length"
                  class="users-table-wrapper"
              >
                <table class="users-table">
                  <thead>
                  <tr>
                    <th>User</th>
                    <th>Credit revenue</th>
                    <th>Provider cost</th>
                    <th>Profit</th>
                  </tr>
                  </thead>

                  <tbody>
                  <tr
                      v-for="user in day.users"
                      :key="user.user"
                  >
                    <td class="user-name">
                      {{ user.user }}
                    </td>

                    <td>
                      {{ money(user.charged) }}
                    </td>

                    <td>
                      {{ money(user.provider_cost) }}
                    </td>

                    <td class="user-profit">
                      {{ money(user.profit) }}
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="no-users">
                No user-level activity recorded for this day.
              </div>
            </div>
          </article>
        </div>

        <div v-else class="report-empty">
          <div class="empty-icon">▤</div>
          <h3>No usage data available</h3>
          <p>
            There are no provider generations recorded for
            this reporting period.
          </p>
          <Link
              :href="url('/generate')"
              class="report-button report-button-primary"
          >
            Create a generation
          </Link>
        </div>
      </section>

      <footer class="report-footer">
        <span>PIXELFORGE FINANCIAL REPORTING</span>
        <span>Provider usage reconciliation · UTC</span>
      </footer>
    </div>
  </AppLayout>
</template>


