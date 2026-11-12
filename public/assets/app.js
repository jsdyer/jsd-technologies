'use strict';
const filters = [...document.querySelectorAll('[data-filter]')];
const cards = [...document.querySelectorAll('[data-category]')];
const search = document.querySelector('#project-search');
let currentFilter = 'all';
function filterProjects() {
  const term = (search?.value || '').trim().toLocaleLowerCase();
  let count = 0;
  cards.forEach(card => {
    const matches = (currentFilter === 'all' || card.dataset.category === currentFilter) && card.dataset.search.includes(term);
    card.hidden = !matches;
    if (matches) count++;
  });
  const output = document.querySelector('#project-count');
  if (output) output.textContent = `${count} project${count === 1 ? '' : 's'}`;
  const empty = document.querySelector('#project-empty');
  if (empty) empty.hidden = count !== 0;
}
filters.forEach(button => button.addEventListener('click', () => {
  currentFilter = button.dataset.filter;
  filters.forEach(item => { item.classList.toggle('active', item === button); item.setAttribute('aria-pressed', String(item === button)); });
  filterProjects();
}));
search?.addEventListener('input', filterProjects);
document.querySelectorAll('.service-select').forEach(link => link.addEventListener('click', () => {
  const service = document.querySelector('#brief-service');
  if (service) service.value = link.dataset.service;
}));
const form = document.querySelector('#brief-form');
const output = document.querySelector('#brief-output');
form?.addEventListener('submit', event => {
  event.preventDefault();
  const values = new FormData(form);
  const details = String(values.get('details')).trim();
  if (!details) { form.querySelector('textarea').setCustomValidity('Please add a short description.'); form.reportValidity(); return; }
  const brief = `Hello Jeffrey,\n\nI’d like to discuss a project.\n\nService: ${values.get('service')}\nPlanning budget: ${values.get('budget')}\nTiming: ${String(values.get('timing')).trim() || 'Flexible / to discuss'}\n\nProject details:\n${details}\n\nThanks!`;
  output.value = brief;
  document.querySelector('#brief-result').hidden = false;
  document.querySelector('#brief-status').textContent = 'Your brief is ready. Nothing has been sent.';
  const mail = document.querySelector('#email-brief');
  mail.href = 'mailto:jeffdyer@gmail.com?subject=' + encodeURIComponent(`Project inquiry: ${values.get('service')}`) + '&body=' + encodeURIComponent(brief);
  output.focus();
});
form?.querySelector('textarea').addEventListener('input', event => event.target.setCustomValidity(''));
document.querySelector('#copy-brief')?.addEventListener('click', async () => {
  const status = document.querySelector('#brief-status');
  try { await navigator.clipboard.writeText(output.value); status.textContent = 'Copied. Paste it into an email whenever you’re ready.'; }
  catch { output.focus(); output.select(); status.textContent = 'Select and copy the brief using your keyboard or device menu.'; }
});
document.querySelector('#download-brief')?.addEventListener('click', () => {
  const url = URL.createObjectURL(new Blob([output.value], {type:'text/plain;charset=utf-8'}));
  const link = document.createElement('a'); link.href = url; link.download = 'jsd-project-brief.txt'; link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
  document.querySelector('#brief-status').textContent = 'Your brief has been prepared as a text file.';
});
