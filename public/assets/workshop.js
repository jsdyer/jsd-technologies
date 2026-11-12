'use strict';
// Independent search scopes keep guides, tips and project filters from colliding.
document.querySelectorAll('[data-library]').forEach(library => {
  const search = library.querySelector('[data-library-search]');
  const filters = [...library.querySelectorAll('button[data-topic]')];
  const items = [...library.querySelectorAll('[data-library-item]')];
  const count = library.querySelector('[data-library-count]');
  const noun = library.id === 'tips' ? 'tip' : 'guide';
  let topic = 'all';
  function update() {
    const words = (search?.value || '').trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
    let visible = 0;
    items.forEach(item => {
      const match = (topic === 'all' || item.dataset.topic === topic) && words.every(word => item.dataset.keywords.includes(word));
      item.hidden = !match;
      if (match) visible++;
    });
    if (count) count.textContent = `${visible} ${noun}${visible === 1 ? '' : 's'}`;
    library.querySelector('[data-library-empty]').hidden = visible !== 0;
  }
  search?.addEventListener('input', update);
  filters.forEach(button => button.addEventListener('click', () => {
    topic = button.dataset.topic;
    filters.forEach(filter => {
      filter.classList.toggle('active', filter === button);
      filter.setAttribute('aria-pressed', String(filter === button));
    });
    update();
  }));
});
document.querySelectorAll('[data-copy-code]').forEach(button => button.addEventListener('click', async () => {
  const box = button.closest('.code-sample');
  const code = box.querySelector('code');
  const status = box.querySelector('.copy-status');
  try {
    await navigator.clipboard.writeText(code.textContent);
    status.textContent = 'Copied. Review the example paths and addresses before running.';
  } catch {
    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(code); selection.removeAllRanges(); selection.addRange(range);
    box.querySelector('pre').focus();
    status.textContent = 'Commands selected. Use your keyboard or device menu to copy.';
  }
}));
