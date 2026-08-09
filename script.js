const tikEl = document.getElementById('tik');
function blip() {
  const now = new Date();
  tikEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}
blip();
setInterval(blip, 1000 * 30);

const frm = document.getElementById('frog1');
const inp = document.getElementById('wug');
const lst = document.getElementById('spag');
const numEl = document.getElementById('nummy');
const noteEl = document.getElementById('blankz');
const fillEl = document.getElementById('fillz');
const lablEl = document.getElementById('labl');
const doneEl = document.getElementById('donez');
const totEl = document.getElementById('totz');

let stuff = [];
let cnt = 0;

function doStuff() {
  lst.innerHTML = stuff.map((t, i) => `
    <li class="wobrow${t.done ? ' done' : ''}" data-id="${t.id}">
      <span class="idxthing">${String(i + 1).padStart(2, '0')}</span>
      <span class="chek"></span>
      <span class="txtblob">${fixTxt(t.text)}</span>
      <button class="zap2" aria-label="Delete task">×</button>
    </li>
  `).join('');

  const openCount = stuff.filter(t => !t.done).length;
  const doneCount = stuff.length - openCount;
  const pct = stuff.length ? Math.round((doneCount / stuff.length) * 100) : 0;

  if (numEl.textContent !== String(openCount)) {
    numEl.textContent = openCount;
    numEl.classList.add('bump');
    setTimeout(() => numEl.classList.remove('bump'), 250);
  }

  fillEl.style.width = pct + '%';
  lablEl.textContent = pct + '%';
  doneEl.textContent = doneCount + ' done';
  totEl.textContent = stuff.length + ' total';

  noteEl.classList.toggle('show', stuff.length === 0);
}

function fixTxt(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

frm.addEventListener('submit', (e) => {
  e.preventDefault();
  const val = inp.value.trim();
  if (!val) return;
  stuff.push({ id: cnt++, text: val, done: false });
  inp.value = '';
  doStuff();
});

lst.addEventListener('click', (e) => {
  const row = e.target.closest('.wobrow');
  if (!row) return;
  const id = parseInt(row.dataset.id, 10);

  if (e.target.closest('.zap2')) {
    stuff = stuff.filter(t => t.id !== id);
  } else {
    const thing = stuff.find(t => t.id === id);
    if (thing) thing.done = !thing.done;
  }
  doStuff();
});

doStuff();
