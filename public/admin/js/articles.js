(() => {
  'use strict';

  const form = document.querySelector('[data-article-form]');
  if (!form) return;

  const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

  const blockLabels = {
    paragraph: 'پاراگراف', heading: 'تیتر', lead: 'مقدمه برجسته', note: 'نکته',
    quote: 'نقل‌قول', prompt: 'پرامپت', image: 'تصویر', video: 'ویدیو', list: 'فهرست', cta: 'دعوت به اقدام',
  };
  const blockFields = {
    paragraph: ['content'], lead: ['content'], quote: ['content'], list: ['content'],
    heading: ['content', 'level'], note: ['title', 'content'], prompt: ['title', 'content'],
    image: ['url', 'alt', 'caption'], video: ['url', 'caption'],
    cta: ['title', 'content', 'button_label', 'button_url'],
  };

  const refreshBlock = (block) => {
    const type = block.querySelector('[data-block-type]')?.value || 'paragraph';
    const allowed = blockFields[type] || ['content'];
    block.querySelector('[data-block-label]').textContent = blockLabels[type] || 'بلوک محتوا';
    block.querySelectorAll('[data-block-field]').forEach((field) => {
      field.hidden = !allowed.includes(field.dataset.blockField);
    });
  };

  const reindexBlocks = () => {
    const blocks = [...form.querySelectorAll('[data-content-block]')];
    blocks.forEach((block, index) => {
      block.querySelector('[data-block-number]').textContent = String(index + 1);
      block.querySelectorAll('[name]').forEach((input) => {
        input.name = input.name.replace(/content_blocks\[(?:\d+|__INDEX__)\]/, `content_blocks[${index}]`);
      });
      refreshBlock(block);
    });
    const count = form.querySelector('[data-block-count]');
    if (count) count.textContent = String(blocks.length);
  };

  const bindBlock = (block) => {
    block.querySelector('[data-block-type]')?.addEventListener('change', () => refreshBlock(block));
    block.querySelector('[data-remove-block]')?.addEventListener('click', () => {
      if (form.querySelectorAll('[data-content-block]').length <= 1) return;
      block.remove();
      reindexBlocks();
    });
    block.querySelectorAll('[data-move-block]').forEach((button) => {
      button.addEventListener('click', () => {
        if (button.dataset.moveBlock === 'up' && block.previousElementSibling) block.parentElement.insertBefore(block, block.previousElementSibling);
        if (button.dataset.moveBlock === 'down' && block.nextElementSibling) block.parentElement.insertBefore(block.nextElementSibling, block);
        reindexBlocks();
      });
    });
    refreshBlock(block);
  };

  form.querySelectorAll('[data-content-block]').forEach(bindBlock);
  form.querySelector('[data-add-block]')?.addEventListener('click', () => {
    const template = document.getElementById('article-block-template');
    const list = form.querySelector('[data-block-list]');
    if (!template || !list) return;
    const index = list.querySelectorAll('[data-content-block]').length;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).trim();
    const block = wrapper.firstElementChild;
    const requestedType = form.querySelector('[data-new-block-type]')?.value || 'paragraph';
    block.querySelector('[data-block-type]').value = requestedType;
    list.appendChild(block);
    bindBlock(block);
    reindexBlocks();
    block.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  reindexBlocks();

  const galleryList = form.querySelector('[data-gallery-list]');
  const galleryEmpty = form.querySelector('[data-gallery-empty]');

  const galleryIndexOf = (editor) => {
    const named = editor.querySelector('[name^="galleries["]');
    return named?.name.match(/galleries\[([^\]]+)\]/)?.[1] || '0';
  };

  const reindexGalleries = () => {
    const editors = [...form.querySelectorAll('[data-gallery-editor]')];
    editors.forEach((editor, index) => {
      const previous = galleryIndexOf(editor);
      editor.querySelectorAll('[name]').forEach((input) => {
        input.name = input.name.replace(new RegExp(`galleries\\[${escapeRegExp(previous)}\\]`), `galleries[${index}]`);
      });
    });
    if (galleryEmpty) galleryEmpty.hidden = editors.length > 0;
  };

  const refreshGallerySource = (editor) => {
    const source = editor.querySelector('[data-gallery-source]')?.value || 'manual';
    editor.querySelectorAll('[data-gallery-source-panel]').forEach((panel) => {
      panel.hidden = panel.dataset.gallerySourcePanel !== source;
    });
  };

  const bindManualItem = (item) => {
    item.querySelector('[data-remove-manual-item]')?.addEventListener('click', (event) => {
      const removeInput = item.querySelector('[data-remove-item-input]');
      if (event.currentTarget.dataset.itemId && removeInput) {
        removeInput.value = '1';
        item.hidden = true;
      } else {
        item.remove();
      }
    });
  };

  const addManualItem = (editor) => {
    const template = document.getElementById('article-manual-item-template');
    const list = editor.querySelector('[data-manual-items]');
    if (!template || !list) return;
    const galleryIndex = galleryIndexOf(editor);
    const itemIndex = list.querySelectorAll('[data-manual-item]').length;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML
      .replaceAll('__GALLERY__', galleryIndex)
      .replaceAll('__ITEM__', String(itemIndex)).trim();
    const item = wrapper.firstElementChild;
    list.appendChild(item);
    bindManualItem(item);
  };

  const bindGallery = (editor) => {
    editor.querySelector('[data-gallery-source]')?.addEventListener('change', () => refreshGallerySource(editor));
    editor.querySelector('[data-gallery-title]')?.addEventListener('input', (event) => {
      const label = editor.querySelector('[data-gallery-label]');
      if (label) label.textContent = event.target.value || 'گالری جدید';
    });
    editor.querySelector('[data-add-manual-item]')?.addEventListener('click', () => addManualItem(editor));
    editor.querySelectorAll('[data-manual-item]').forEach(bindManualItem);
    editor.querySelector('[data-remove-gallery]')?.addEventListener('click', (event) => {
      const id = event.currentTarget.dataset.galleryId;
      if (id) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'remove_gallery_ids[]';
        hidden.value = id;
        form.appendChild(hidden);
      }
      editor.remove();
      reindexGalleries();
    });
    refreshGallerySource(editor);
  };

  form.querySelectorAll('[data-gallery-editor]').forEach(bindGallery);
  form.querySelector('[data-add-gallery]')?.addEventListener('click', () => {
    const template = document.getElementById('article-gallery-template');
    if (!template || !galleryList) return;
    const index = galleryList.querySelectorAll('[data-gallery-editor]').length;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replaceAll('__GALLERY__', String(index)).trim();
    const editor = wrapper.firstElementChild;
    galleryList.appendChild(editor);
    bindGallery(editor);
    reindexGalleries();
    editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  reindexGalleries();

  const publishStatus = form.querySelector('[data-publish-status]');
  const scheduleField = form.querySelector('[data-schedule-field]');
  const refreshSchedule = () => {
    if (!publishStatus || !scheduleField) return;
    const isScheduled = publishStatus.value === 'scheduled';
    scheduleField.hidden = !isScheduled;
    scheduleField.querySelector('input')?.toggleAttribute('required', isScheduled);
  };
  publishStatus?.addEventListener('change', refreshSchedule);
  refreshSchedule();

  form.querySelector('[data-product-search]')?.addEventListener('input', (event) => {
    const needle = event.target.value.trim().toLocaleLowerCase('fa');
    form.querySelectorAll('[data-product-choice]').forEach((choice) => {
      choice.hidden = needle !== '' && !choice.dataset.search.toLocaleLowerCase('fa').includes(needle);
    });
  });

  const title = form.querySelector('[data-seo-title]');
  const excerpt = form.querySelector('[data-seo-excerpt]');
  const auto = form.querySelector('[data-seo-auto]');
  const slug = form.querySelector('[data-seo-slug]');
  const metaTitle = form.querySelector('[data-seo-meta-title]');
  const metaDescription = form.querySelector('[data-seo-meta-description]');
  const slugify = (value) => value.trim().toLocaleLowerCase('fa')
    .replace(/[\u200c\s_]+/g, '-').replace(/[^\p{L}\p{N}-]+/gu, '').replace(/-+/g, '-').replace(/^-|-$/g, '');

  const refreshSeoPreview = () => {
    const slugValue = slug?.value || 'article-slug';
    form.querySelectorAll('[data-slug-preview]').forEach((item) => { item.textContent = slugValue; });
    const urlPreview = form.querySelector('[data-seo-url]');
    if (urlPreview) urlPreview.textContent = `${window.location.origin}/articles/${slugValue}`;
    const titlePreview = form.querySelector('[data-seo-preview-title]');
    const descriptionPreview = form.querySelector('[data-seo-preview-description]');
    if (titlePreview) titlePreview.textContent = metaTitle?.value || title?.value || 'عنوان مقاله';
    if (descriptionPreview) descriptionPreview.textContent = metaDescription?.value || excerpt?.value || 'توضیح کوتاه نتیجه جست‌وجو اینجا دیده می‌شود.';
  };
  const autoFillSeo = () => {
    if (!auto?.checked) return refreshSeoPreview();
    if (title && slug) slug.value = slugify(title.value);
    if (title && metaTitle) metaTitle.value = `${title.value.trim()} | وطن`.slice(0, 180);
    if (excerpt && metaDescription) metaDescription.value = excerpt.value.trim().slice(0, 300);
    refreshSeoPreview();
  };
  [title, excerpt].forEach((input) => input?.addEventListener('input', autoFillSeo));
  [slug, metaTitle, metaDescription].forEach((input) => input?.addEventListener('input', refreshSeoPreview));
  auto?.addEventListener('change', autoFillSeo);
  refreshSeoPreview();

  form.addEventListener('submit', () => {
    reindexBlocks();
    reindexGalleries();
  });
})();
