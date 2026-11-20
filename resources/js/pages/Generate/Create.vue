<script setup>
import {computed, onBeforeUnmount, ref, watch} from 'vue';
import {useForm} from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({pricing: Object, defaults: Object});
const form = useForm({prompt: '', size: props.defaults.size, quality: props.defaults.quality, reference_images: []});
const refsText = ref('');
const quote = ref(null);
const quoteLoading = ref(false);
const error = ref('');
let timer;
let estimateController = null;
let estimateVersion = 0;
const canQuote = computed(() => form.prompt.trim().length > 0 && form.prompt.length <= 4000);
watch(() => [form.prompt, form.size, form.quality, refsText.value], () => {
  const version = ++estimateVersion;
  clearTimeout(timer);
  estimateController?.abort();
  estimateController = null;
  quote.value = null;
  error.value = '';
  quoteLoading.value = false;

  if (!canQuote.value) return;

  timer = setTimeout(async () => {
    const controller = new AbortController();
    estimateController = controller;
    quoteLoading.value = true;

    try {
      const response = await window.axios.post('/generate/estimate', {
        prompt: form.prompt,
        size: form.size,
        quality: form.quality,
        reference_images: refsText.value.split('\n').map(x => x.trim()).filter(Boolean)
      }, {signal: controller.signal});

      // A slow response for an older prompt must never replace the latest quote.
      if (version === estimateVersion) quote.value = response.data;
    } catch (e) {
      if (version === estimateVersion && !window.axios.isCancel?.(e) && e.code !== 'ERR_CANCELED') {
        error.value = e.response?.data?.message || 'Unable to calculate estimate. Please try again.';
      }
    } finally {
      if (version === estimateVersion) {
        quoteLoading.value = false;
        estimateController = null;
      }
    }
  }, 350);
});

onBeforeUnmount(() => {
  clearTimeout(timer);
  estimateController?.abort();
});

function submit() {
  if (!quote.value || quoteLoading.value || !canQuote.value || form.processing) return;
  form.reference_images = refsText.value.split('\n').map(x => x.trim()).filter(Boolean);
  form.post('/generate', {
    onError: () => {
      error.value = 'Please review the form and try again.';
    }
  })
}

const money = v => v == null ? '—' : `$${Number(v).toFixed(9)}`;
</script>
<template>
  <AppLayout>
    <template #title>New generation</template>
    <div class="workspace-grid">
      <section class="panel composer">
        <div class="panel-heading">
          <div><span class="eyebrow">IMAGE STUDIO</span>
            <h2>Bring an idea to life</h2>
            <p>Describe what you want to create. Your estimate updates as you work.</p></div>
          <span class="sparkle">✦</span></div>
        <form @submit.prevent="submit"><label class="field-label">Your prompt</label><textarea v-model="form.prompt"
                                                                                               class="prompt-input"
                                                                                               maxlength="4000"
                                                                                               placeholder="A quiet Japanese garden at golden hour, soft cinematic light, delicate details…"
                                                                                               required></textarea>
          <div class="field-foot"><span>Be descriptive for more intentional results</span><span>{{ form.prompt.length }} / 4,000</span>
          </div>
          <div v-if="form.errors.prompt" class="field-error">{{ form.errors.prompt }}</div>
          <div class="form-row">
            <div class="field"><label class="field-label">Canvas size</label><select v-model="form.size">
              <option value="1024x1024">Square · 1:1</option>
              <option value="1536x1024">Landscape · 3:2</option>
              <option value="1024x1536">Portrait · 2:3</option>
            </select></div>
            <div class="field"><label class="field-label">Quality</label><select v-model="form.quality">
              <option value="low">Draft · Low</option>
              <option value="medium">Standard · Medium</option>
              <option value="high">Ultra · High</option>
            </select></div>
          </div>
          <div class="field"><label class="field-label">Reference images <span
              class="optional">OPTIONAL · UP TO 4 URLS</span></label><textarea v-model="refsText"
                                                                               class="reference-input"
                                                                               placeholder="https://example.com/reference.jpg&#10;One image URL per line"></textarea><small
              class="helper">Public HTTP or HTTPS image URLs only.</small></div>
          <div class="estimate-card">
            <div><span class="eyebrow">ESTIMATED TOTAL</span>
              <div class="estimate-price">{{ quoteLoading ? 'Calculating…' : money(quote?.estimated_cost) }}</div>
              <p>Provider estimate + 25% service markup</p></div>
            <div class="estimate-icon">$</div>
          </div>
          <div v-if="error" class="field-error">{{ error }}</div>
          <button class="primary-button full" type="submit" :disabled="form.processing || quoteLoading || !quote || !canQuote">
            {{ form.processing ? 'Submitting…' : 'Generate image' }} <span>→</span></button>
          <p class="fine-print">Final cost is calculated from actual token usage. Failed generations are free.</p>
        </form>
      </section>
      <aside class="side-column">
        <div class="panel preview-panel"><span class="eyebrow">PREVIEW</span>
          <div class="preview-empty">
            <div class="preview-art">✧</div>
            <strong>Your canvas awaits</strong><span>Your generated image will appear in History once complete.</span>
          </div>
        </div>
        <div class="panel info-panel"><h3>Transparent pricing</h3>
          <div class="info-line"><span>Provider cost</span><b>At cost</b></div>
          <div class="info-line"><span>Service markup</span><b>25%</b></div>
          <div class="info-line"><span>Failed generations</span><b class="green">Free</b></div>
          <p>We show an estimate before you submit. Your final charge is based on the exact usage reported by
            Pixelforge.</p></div>
      </aside>
    </div>
  </AppLayout>
</template>
