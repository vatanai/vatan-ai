import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import vm from 'node:vm';

class FakeSelect {
  constructor(options) {
    this.options = options.map((option) => {
      const item = { ...option, hidden: false, disabled: false, dataset: option.dataset || {} };
      let selected = Boolean(option.selected);
      Object.defineProperty(item, 'selected', {
        get: () => selected,
        set: (value) => {
          selected = Boolean(value);
          if (selected) {
            for (const other of this.options) {
              if (other !== item) other.selected = false;
            }
          }
        },
      });
      return item;
    });
    this.listeners = {};
    this.dataset = {};
  }

  get selectedIndex() {
    return this.options.findIndex((option) => option.selected);
  }

  get value() {
    return this.options[this.selectedIndex]?.value || '';
  }

  set value(value) {
    const matching = this.options.find((option) => option.value === value);
    for (const option of this.options) option.selected = false;
    if (matching) matching.selected = true;
  }

  addEventListener(type, listener) {
    this.listeners[type] = listener;
  }

  fire(type) {
    return this.listeners[type]?.();
  }
}

test('step two preserves the provider and saves edits to the selected preset', async () => {
  const template = readFileSync('resources/views/admin/products/partials/model-quality-architecture-preview.blade.php', 'utf8');
  const script = template.match(/<script>\s*([\s\S]*?)\s*<\/script>/)?.[1];
  assert.ok(script);

  const configuration = (provider, modelId) => ({
    quality_architecture_enabled: true,
    quality_models: { standard: { primary: { provider, model_id: modelId } } },
    free_quality_models: {},
  });
  const presets = {
    first: { name: 'اول', configuration: configuration('openrouter', 'shared/model') },
    second: { name: 'دوم', configuration: configuration('fal', 'shared/model') },
  };
  const presetSelect = new FakeSelect([
    { value: 'first', selected: true },
    { value: 'second' },
    { value: 'custom' },
  ]);
  const providerSelect = new FakeSelect([
    { value: '' },
    { value: 'openrouter', selected: true },
    { value: 'fal' },
  ]);
  const modelSelect = new FakeSelect([
    { value: '' },
    { value: 'shared/model', selected: true, dataset: { provider: 'openrouter' } },
    { value: 'shared/model', dataset: { provider: 'fal' } },
    { value: 'new/model', dataset: { provider: 'fal' } },
  ]);
  modelSelect.dataset = { group: 'quality_models', quality: 'standard', role: 'primary' };
  providerSelect.dataset = { ...modelSelect.dataset };
  const hiddenProvider = { value: 'openrouter' };
  const cost = { textContent: '' };
  const label = {
    querySelector(selector) {
      if (selector === '[data-quality-provider]') return hiddenProvider;
      if (selector === '[data-model-cost]') return cost;
      return null;
    },
  };
  modelSelect.closest = () => label;
  const status = { textContent: '', style: {} };
  const enabled = { value: '1' };
  const toggle = new FakeSelect([]);
  toggle.setAttribute = () => {};
  const toggleLabel = { textContent: '' };
  const content = { classList: { toggle() {} } };
  const saveButton = new FakeSelect([]);
  const elements = {
    '[data-quality-preset]': presetSelect,
    '[data-preset-save-status]': status,
    '[data-quality-architecture-toggle]': toggle,
    '[data-quality-architecture-content]': content,
    '[data-quality-architecture-toggle-label]': toggleLabel,
    '[data-quality-architecture-enabled]': enabled,
    '[data-fix-quality-preset]': saveButton,
  };
  const root = {
    dataset: {
      presetConfigurations: JSON.stringify(presets),
      presetUrls: JSON.stringify({ first: '/presets/first', second: '/presets/second' }),
      presetDeleteUrls: '{}',
    },
    querySelector(selector) {
      if (selector.startsWith('[data-quality-provider-select][data-group=')) return providerSelect;
      return elements[selector] || null;
    },
    querySelectorAll(selector) {
      if (selector === '[data-quality-model]') return [modelSelect];
      if (selector === '[data-quality-provider-select]') return [providerSelect];
      return [];
    },
  };
  const requests = [];
  const window = {};
  const document = {
    querySelector(selector) {
      return selector === '[data-model-quality-architecture]' ? root : null;
    },
    getElementById() { return null; },
    dispatchEvent() {},
  };

  vm.runInNewContext(script, {
    document,
    window,
    CustomEvent: class {},
    fetch: async (url, options) => {
      requests.push({ url, options });
      return {
        ok: true,
        json: async () => ({ preset: { configuration: JSON.parse(options.body).configuration } }),
      };
    },
  });

  presetSelect.value = 'second';
  presetSelect.fire('change');
  assert.equal(providerSelect.value, 'fal');
  assert.equal(modelSelect.selectedIndex, 2);

  modelSelect.value = 'new/model';
  modelSelect.fire('change');
  assert.equal(presetSelect.value, 'second');
  assert.equal(await window.saveStepTwoModelQualityPresetIfDirty(), true);
  assert.equal(requests.length, 1);
  assert.equal(requests[0].url, '/presets/second');
  assert.equal(JSON.parse(requests[0].options.body).configuration.quality_models.standard.primary.model_id, 'new/model');
  assert.equal(JSON.parse(requests[0].options.body).configuration.quality_models.standard.primary.provider, 'fal');

  modelSelect.options[2].selected = true;
  modelSelect.fire('change');
  assert.equal(await saveButton.fire('click'), true);
  assert.equal(requests.length, 2);
  assert.equal(requests[1].url, '/presets/second');
  assert.equal(JSON.parse(requests[1].options.body).configuration.quality_models.standard.primary.model_id, 'shared/model');
  assert.equal(await window.saveStepTwoModelQualityPresetIfDirty(), true);
  assert.equal(requests.length, 2);
});
