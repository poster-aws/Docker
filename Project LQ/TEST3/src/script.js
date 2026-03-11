(function () {
  'use strict';

  let currentLang = localStorage.getItem('lang') || 'fr';
  let currentTheme = localStorage.getItem('theme') || 'dark';
  let q2CountRange = 50;
  let q3CountRange = 50;
  let q4CountRange = 50;
  let isAltView = false;
  let originalHomeContent = null;

  const container = document.getElementById('container');
  const pageTitle = document.getElementById('pageTitle');
  const spinner = document.getElementById('loadingSpinner');
  const toggleSwitch = document.getElementById('toggleSwitch');
  const infoBtn = document.getElementById('infoBtn');
  const labelOrder = document.getElementById('labelOrder');
  const labelNimport = document.getElementById('labelNimport');
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');
  const navOverlay = document.getElementById('navOverlay');

  function setNavOpen(open) {
    if (mainNav) mainNav.classList.toggle('is-open', open);
    if (navToggle) navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (navOverlay) {
      navOverlay.classList.toggle('is-open', open);
      navOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    }
  }

  function setSpinner(show) {
    spinner.setAttribute('aria-hidden', show ? 'false' : 'true');
  }

  function updateLangUI() {
    document.querySelectorAll('.lang-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.lang === currentLang);
    });
  }

  function applyTheme() {
    var app = document.querySelector('.app');
    if (app) app.classList.toggle('theme-light', currentTheme === 'light');
  }

  function updateThemeUI() {
    document.querySelectorAll('.theme-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.theme === currentTheme);
    });
  }

  function updateToggleLabels(page) {
    if (!labelOrder || !labelNimport) return;
    labelOrder.classList.toggle('active', !toggleSwitch.checked);
    labelNimport.classList.toggle('active', toggleSwitch.checked);
  }

  function loadPage(page) {
    if (!page) return;
    setNavOpen(false);

    var isOrdered = toggleSwitch.checked;
    var mode = isOrdered ? 'norder=1' : '';
    var extraParam = 'lang=' + currentLang;

    if (page.indexOf('q2') !== -1) {
      extraParam += '&count_range=' + (q2CountRange || 50);
    } else if (page.indexOf('q3') !== -1) {
      extraParam += '&count_range=' + (q3CountRange || 50);
    } else if (page.indexOf('q4') !== -1) {
      extraParam += '&count_range=' + (q4CountRange || 50);
    }

    var query = [mode, extraParam].filter(Boolean).join('&');
    var url = query ? page + '?' + query : page;

    setSpinner(true);
    fetch(url)
      .then(function (r) {
        if (!r.ok) throw new Error('Erreur de chargement');
        return r.text();
      })
      .then(function (html) {
        container.innerHTML = html;
        container.setAttribute('data-page', page);
        updateToggleLabels(page);
        makeTablesSortable();

        if (page.indexOf('q2') !== -1) {
          bindRangeSelect(container.querySelector('#q2CountRange'), 'q2', page);
        } else if (page.indexOf('q3') !== -1) {
          bindRangeSelect(container.querySelector('#q3CountRange'), 'q3', page);
        } else if (page.indexOf('q4') !== -1) {
          bindRangeSelect(container.querySelector('#q4CountRange'), 'q4', page);
        }

        var meta = container.querySelector('[id$="-meta"]');
        var count = meta ? meta.dataset.count || '?' : '?';
        if (page.indexOf('q2') !== -1) {
          pageTitle.innerHTML = 'Quotidienne 2 <span class="sub">' + count + ' tirages</span>';
        } else if (page.indexOf('q3') !== -1) {
          pageTitle.innerHTML = 'Quotidienne 3 <span class="sub">' + count + ' tirages</span>';
        } else if (page.indexOf('q4') !== -1) {
          pageTitle.innerHTML = 'Quotidienne 4 <span class="sub">' + count + ' tirages</span>';
        }
        infoBtn.textContent = '\u2139';
      })
      .catch(function (err) {
        container.innerHTML = '<p class="error">Impossible de charger la page.</p>';
        console.error(err);
      })
      .finally(function () {
        setSpinner(false);
      });
  }

  function bindRangeSelect(select, key, page) {
    if (!select) return;
    var range = key === 'q2' ? q2CountRange : key === 'q3' ? q3CountRange : q4CountRange;
    select.value = String(range || 50);
    select.addEventListener('change', function () {
      var v = parseInt(select.value, 10) || 50;
      if (key === 'q2') q2CountRange = v;
      else if (key === 'q3') q3CountRange = v;
      else q4CountRange = v;
      loadPage(page);
    });
  }

  function makeTablesSortable() {
    container.querySelectorAll('.interactive-table').forEach(function (table) {
      if (table.closest('.number-stats-table')) return;
      var headers = table.querySelectorAll('thead th');
      var tbody = table.querySelector('tbody');
      if (!tbody) return;
      headers.forEach(function (th, colIndex) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
          var rows = Array.from(tbody.querySelectorAll('tr'));
          var asc = th.classList.contains('sort-asc');
          headers.forEach(function (h) {
            h.classList.remove('sort-asc', 'sort-desc');
          });
          rows.sort(function (a, b) {
            var aText = (a.children[colIndex] && a.children[colIndex].innerText) ? a.children[colIndex].innerText.trim() : '';
            var bText = (b.children[colIndex] && b.children[colIndex].innerText) ? b.children[colIndex].innerText.trim() : '';
            var aVal = isNaN(aText) ? aText : parseFloat(aText);
            var bVal = isNaN(bText) ? bText : parseFloat(bText);
            if (aVal < bVal) return asc ? 1 : -1;
            if (aVal > bVal) return asc ? -1 : 1;
            return 0;
          });
          tbody.innerHTML = '';
          rows.forEach(function (r) { tbody.appendChild(r); });
          th.classList.toggle('sort-asc', !asc);
          th.classList.toggle('sort-desc', asc);
        });
      });
    });
  }

  function goHome() {
    setNavOpen(false);
    if (originalHomeContent) {
      container.innerHTML = originalHomeContent;
    }
    container.setAttribute('data-page', '');
    pageTitle.textContent = 'Bienvenue';
    infoBtn.textContent = '\u2139';
    updateToggleLabels();
  }

  window.loadPage = loadPage;
  window.goHome = goHome;

  document.querySelectorAll('.nav-list a[data-page]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      loadPage(a.dataset.page);
    });
  });

  document.querySelectorAll('.lang-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      currentLang = btn.dataset.lang;
      localStorage.setItem('lang', currentLang);
      updateLangUI();
      var page = container.getAttribute('data-page');
      if (page) loadPage(page);
    });
  });

  document.querySelectorAll('.theme-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      currentTheme = btn.dataset.theme;
      localStorage.setItem('theme', currentTheme);
      applyTheme();
      updateThemeUI();
    });
  });

  if (toggleSwitch) {
    toggleSwitch.addEventListener('change', function () {
      updateToggleLabels();
      var page = container.getAttribute('data-page');
      if (page && !isAltView) loadPage(page);
    });
  }

  if (infoBtn) {
    infoBtn.addEventListener('click', function () {
      var page = container.getAttribute('data-page');
      if (!page) return;
      isAltView = !isAltView;
      if (isAltView) {
        var infoUrl;
        if (page.indexOf('q2') !== -1) infoUrl = 'quotidienne/QInfo/q2info.php?table=Q2_stats_order';
        else if (page.indexOf('q3') !== -1) infoUrl = 'quotidienne/QInfo/q3info.php?table=Q3_stats_order';
        else if (page.indexOf('q4') !== -1) infoUrl = 'quotidienne/QInfo/q4info.php?table=Q4_stats_order';
        else return;
        container.innerHTML = '<iframe src="' + infoUrl + '" class="info-iframe"></iframe>';
        infoBtn.textContent = '\u21c6';
      } else {
        loadPage(page);
      }
    });
  }

  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      var open = !mainNav.classList.contains('is-open');
      setNavOpen(open);
    });
  }
  if (navOverlay) {
    navOverlay.addEventListener('click', function () { setNavOpen(false); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    originalHomeContent = container.innerHTML;
    applyTheme();
    updateThemeUI();
    updateLangUI();
    updateToggleLabels();
  });
})();
