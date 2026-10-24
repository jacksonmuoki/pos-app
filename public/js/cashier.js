(function () {
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const POS = window.POS || {};

  const toast = $('#toast');
  if (toast) setTimeout(() => toast.classList.add('hide'), 6000);

  const on = $('.chip.on');
  if (on && on.scrollIntoView) on.scrollIntoView({ inline: 'center', block: 'nearest' });

  /* Product search, category filter, grid/list */
  const search = $('#search'), tiles = $$('.tile-form'), noMatch = $('#noMatch'), grid = $('#tiles');
  let cat = '';
  function filter() {
    const q = (search ? search.value : '').trim().toLowerCase();
    let shown = 0;
    tiles.forEach(t => {
      const ok = (!cat || t.dataset.cat === cat) && (!q || t.dataset.name.includes(q));
      t.hidden = !ok;
      if (ok) shown++;
    });
    if (noMatch) noMatch.hidden = shown > 0;
  }
  if (search) search.addEventListener('input', filter);
  $$('.cat').forEach(b => b.addEventListener('click', () => {
    cat = b.dataset.cat;
    $$('.cat').forEach(x => x.classList.toggle('on', x === b));
    filter();
  }));
  function setView(v) {
    if (grid) grid.classList.toggle('list', v === 'list');
    $$('.view button').forEach(b => b.classList.toggle('on', b.dataset.view === v));
    try { localStorage.setItem('pos_view', v); } catch (e) {}
  }
  $$('.view button').forEach(b => b.addEventListener('click', () => setView(b.dataset.view)));
  try { setView(localStorage.getItem('pos_view') || 'grid'); } catch (e) {}

  /* Mobile ticket sheet */
  const mbarBtn = $('#mbarBtn'), ticketClose = $('#ticketClose');
  if (mbarBtn) mbarBtn.addEventListener('click', () => document.body.classList.toggle('sheet'));
  if (ticketClose) ticketClose.addEventListener('click', () => document.body.classList.remove('sheet'));

  /* Dialogs */
  const pay = $('#payModal'), openPay = $('#openPay'), sales = $('#salesModal'), openSales = $('#openSales');
  const showPay   = () => { if (pay) { pay.hidden = false; setTimeout(() => { const t = $('#cashReceived'); t && t.select(); }, 50); } };
  const hidePay   = () => { if (pay) pay.hidden = true; };
  const showSales = () => { if (sales) sales.hidden = false; };
  const hideSales = () => { if (sales) sales.hidden = true; };
  if (openPay) openPay.addEventListener('click', showPay);
  if (openSales) openSales.addEventListener('click', showSales);
  [pay, sales].forEach(m => m && m.addEventListener('click', e => {
    if (e.target === m || e.target.closest('[data-close]')) m.hidden = true;
  }));

  /* Cash form */
  const form = $('#payForm');
  if (form) {
    const R = parseFloat(form.dataset.due), CUR = form.dataset.cur;
    const fmt = n => CUR + ' ' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const tend = $('#cashReceived'), chgBox = $('#chgBox'), chgLbl = $('#chgLbl'), chgAmt = $('#chgAmt');
    const errBox = $('#payErr'), submit = $('#paySubmit');

    function state() {
      const t = parseFloat(tend.value) || 0;
      if (t + 0.005 >= R) {
        chgLbl.textContent = 'Change due';
        chgAmt.textContent = fmt(t - R);
        chgBox.className = 'chg num ok';
      } else {
        chgLbl.textContent = 'Still short';
        chgAmt.textContent = fmt(R - t);
        chgBox.className = 'chg num short';
      }
      errBox.hidden = true;
    }
    tend.addEventListener('input', state);
    $$('.quick-btn', form).forEach(b => b.addEventListener('click', () => {
      tend.value = Number(b.dataset.amt).toFixed(2);
      state();
      tend.focus();
    }));
    form.addEventListener('submit', e => {
      const t = parseFloat(tend.value) || 0;
      if (t + 0.005 < R) {
        e.preventDefault();
        errBox.textContent = 'Cash received is less than the amount due.';
        errBox.hidden = false;
        return;
      }
      if (!confirm('Record cash payment of ' + fmt(R) + '?')) { e.preventDefault(); return; }
      submit.disabled = true;
    });
    state();
  }

  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
  }));

  /* Barcode (Code 39) of the receipt number */
  const C39 = {'0':'nnnwwnwnn','1':'wnnwnnnnw','2':'nnwwnnnnw','3':'wnwwnnnnn','4':'nnnwwnnnw','5':'wnnwwnnnn','6':'nnwwwnnnn','7':'nnnwnnwnw','8':'wnnwnnwnn','9':'nnwwnnwnn','A':'wnnnnwnnw','B':'nnwnnwnnw','C':'wnwnnwnnn','D':'nnnnwwnnw','E':'wnnnwwnnn','F':'nnwnwwnnn','G':'nnnnnwwnw','H':'wnnnnwwnn','I':'nnwnnwwnn','J':'nnnnwwwnn','K':'wnnnnnnww','L':'nnwnnnnww','M':'wnwnnnnwn','N':'nnnnwnnww','O':'wnnnwnnwn','P':'nnwnwnnwn','Q':'nnnnnnwww','R':'wnnnnnwwn','S':'nnwnnnwwn','T':'nnnnwnwwn','U':'wwnnnnnnw','V':'nwwnnnnnw','W':'wwwnnnnnn','X':'nwnnwnnnw','Y':'wwnnwnnnn','Z':'nwwnwnnnn','-':'nwnnnnwnw','*':'nwnnwnwnn'};
  function code39(txt) {
    let x = 0, r = '';
    for (const ch of '*' + txt.toUpperCase() + '*') {
      const p = C39[ch];
      if (!p) continue;
      for (let i = 0; i < 9; i++) {
        const w = p[i] === 'w' ? 3 : 1;
        if (i % 2 === 0) r += '<rect x="' + x + '" y="0" width="' + w + '" height="40"/>';
        x += w;
      }
      x += 1;
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + x + ' 40" preserveAspectRatio="none" fill="#000">' + r + '</svg>';
  }
  $$('[data-bc]').forEach(el => { try { el.insertAdjacentHTML('afterbegin', code39(el.dataset.bc)); } catch (e) {} });

  /* QR code */
  $$('[data-qr]').forEach(el => {
    try {
      if (typeof qrcode !== 'function') { el.hidden = true; return; }
      const q = qrcode(0, 'M');
      q.addData(el.dataset.qr);
      q.make();
      el.innerHTML = q.createSvgTag({ cellSize: 3, margin: 0 });
    } catch (e) { el.hidden = true; }
  });

  /* Print only the receipt, through a hidden frame */
  function doPrint() {
    const src = $('#rcPaper');
    if (!src) return;
    const old = $('#printFrame');
    if (old) old.remove();
    const f = document.createElement('iframe');
    f.id = 'printFrame';
    f.setAttribute('aria-hidden', 'true');
    f.title = 'Receipt';
    f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
    document.body.appendChild(f);
    const d = f.contentDocument;
    d.open();
    d.write('<!doctype html><html><head><meta charset="utf-8"><title>Receipt</title><style>' + POS.rcCss +
      '@page{margin:0;size:' + POS.paper + 'mm auto}html,body{margin:0;padding:0;background:#fff}' +
      '.rc{margin:0;box-shadow:none;max-width:none;-webkit-print-color-adjust:exact;print-color-adjust:exact}</style></head><body>' +
      src.outerHTML + '</body></html>');
    d.close();
    setTimeout(() => { try { f.contentWindow.focus(); f.contentWindow.print(); } catch (e) { window.print(); } }, 250);
  }
  const printBtn = $('#printBtn');
  if (printBtn) printBtn.addEventListener('click', doPrint);
  if (POS.autoPrint && POS.hasReceipt) window.addEventListener('load', () => setTimeout(doPrint, 400));

  /* Keyboard: / search, F2 new order, F8 sales, F9 pay, P print, Esc close */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      if (pay && !pay.hidden) return hidePay();
      if (sales && !sales.hidden) return hideSales();
      const rc = $('#rcStage');
      if (rc) { location.href = rc.dataset.close; return; }
      document.body.classList.remove('sheet');
      return;
    }
    if (/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName || '')) return;
    if (e.key === '/') { e.preventDefault(); search && search.focus(); }
    else if (e.key === 'F2') { e.preventDefault(); const t = $('#tableNo'); t && t.focus(); }
    else if (e.key === 'F8') { e.preventDefault(); showSales(); }
    else if (e.key === 'F9') { e.preventDefault(); if (openPay && !openPay.disabled) showPay(); }
    else if ((e.key === 'p' || e.key === 'P') && POS.hasReceipt && !e.ctrlKey && !e.metaKey) { e.preventDefault(); doPrint(); }
  });
})();