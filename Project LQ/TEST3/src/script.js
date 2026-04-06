(function () {
  'use strict';

  let currentLang = localStorage.getItem('lang') || 'fr';
  let currentTheme = localStorage.getItem('theme') || 'dark';
  let q2CountRange = 50;
  let q3CountRange = 50;
  let q4CountRange = 50;
  let q3InfoLimit = 50;
  let q4InfoLimit = 50;
  let toutInfoLimit = 50;
  let vieInfoGridLimit = 50;
  /** Grande Vie stats GN : fenêtre = derniers N tirages (dates distinctes), pas des jours */
  let vieGnTirageCount = 50;
  let q2ChartLimit = 100;
  let q2InfoGridLimit = 50;
  let q2InfoNorder = false;
  let q2InfoChartInstance = null;
  let isAltView = false;
  let originalHomeContent = null;
  let loadAbortController = null;
  let loadRequestId = 0;
  /** Promesse de chargement Chart.js (Q2 Info uniquement, une seule requête CDN) */
  let chartJsLoadPromise = null;

  const CHART_JS_CDN = 'https://cdn.jsdelivr.net/npm/chart.js';

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
    document.documentElement.classList.toggle('theme-light', currentTheme === 'light');
  }

  function updateThemeUI() {
    document.querySelectorAll('.theme-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.theme === currentTheme);
    });
  }

  function updateToggleLabels(page) {
    if (!labelOrder || !labelNimport) return;
    page = page || (container && container.getAttribute('data-page')) || '';
    if (page === 'toutourien/tout.php' || page === 'toutourien/Info/toutinfo.php') {
      if (currentLang === 'en') {
        labelOrder.textContent = '50 Draws';
        labelNimport.textContent = '200 Draws';
      } else {
        labelOrder.textContent = '50 Tirages';
        labelNimport.textContent = '200 Tirages';
      }
      labelOrder.classList.toggle('active', !toggleSwitch.checked);
      labelNimport.classList.toggle('active', toggleSwitch.checked);
      return;
    }
    if (currentLang === 'en') {
      labelOrder.innerHTML = 'Order';
      labelNimport.innerHTML = 'Any order';
    } else {
      labelOrder.innerHTML = 'Ordre';
      labelNimport.innerHTML = "N'importe";
    }
    labelOrder.classList.toggle('active', !toggleSwitch.checked);
    labelNimport.classList.toggle('active', toggleSwitch.checked);
  }

  function getInfoFetchPage(page) {
    if (!page) return '';
    if (page === 'quotidienne/q2.php') return 'quotidienne/QInfo/q2info.php';
    if (page === 'quotidienne/QInfo/q2info.php') return 'quotidienne/q2.php';
    if (page === 'quotidienne/q3.php') return 'quotidienne/QInfo/q3info.php';
    if (page === 'quotidienne/QInfo/q3info.php') return 'quotidienne/q3.php';
    if (page === 'quotidienne/q4.php') return 'quotidienne/QInfo/q4info.php';
    if (page === 'quotidienne/QInfo/q4info.php') return 'quotidienne/q4.php';
    if (page === 'toutourien/tout.php') return 'toutourien/Info/toutinfo.php';
    if (page === 'toutourien/Info/toutinfo.php') return 'toutourien/tout.php';
    if (page === 'vie/vie.php') return 'vie/Info/vieinfo.php';
    if (page === 'vie/Info/vieinfo.php') return 'vie/vie.php';
    return '';
  }

  function destroyQ2InfoChart() {
    if (q2InfoChartInstance) {
      try {
        q2InfoChartInstance.destroy();
      } catch (e) { /* ignore */ }
      q2InfoChartInstance = null;
    }
  }

  /** Charge Chart.js à la demande (première visite Q2 Info). */
  function loadChartJs() {
    if (typeof window.Chart !== 'undefined') {
      return Promise.resolve();
    }
    if (chartJsLoadPromise) {
      return chartJsLoadPromise;
    }
    chartJsLoadPromise = new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = CHART_JS_CDN;
      s.async = true;
      s.onload = function () {
        if (typeof window.Chart !== 'undefined') {
          resolve();
        } else {
          chartJsLoadPromise = null;
          reject(new Error('Chart.js indisponible après chargement'));
        }
      };
      s.onerror = function () {
        chartJsLoadPromise = null;
        reject(new Error('Échec du chargement Chart.js'));
      };
      document.head.appendChild(s);
    });
    return chartJsLoadPromise;
  }

  /** Couleurs thème (style.css → --q2info-chart-* sur <html>) pour Chart.js Q2 Info */
  function themeCssVar(name, fallback) {
    var v = getComputedStyle(document.documentElement).getPropertyValue(name);
    v = (v || '').trim();
    return v || fallback;
  }

  /** Textes des en-têtes de tableaux Q2 / Q3 / Q4 (pages principales) */
  var QUOTIDIENNE_TABLE_I18N = {
    en: {
      draw: 'Draw',
      last365: 'Last 365',
      days: 'Days<br>ago',
      previous: 'Previous<br>draw',
      times: 'Times',
      /** Dernière colonne du 1er tableau (Q3/Q4 : saut après « Max ») */
      maxDaysTight: 'Max<br>days ago',
      /** Dernière colonne Q2 (tous) et 2e tableau Q3/Q4 : « Max jours » / « Max days » */
      maxDaysLoose: 'Max days<br>ago',
      lastDraw: 'Last<br>draw',
      draws: 'Draws'
    },
    fr: {
      draw: 'Tirage',
      last365: '365 dernières',
      days: 'Jours<br>passés',
      previous: "L'avant<br>dernière",
      times: 'Fois',
      maxDaysTight: 'Max<br>jours passés',
      maxDaysLoose: 'Max jours<br>passés',
      lastDraw: 'Dernier<br>Tirage',
      draws: 'Tirages'
    }
  };

  /**
   * Indices des <th> à remplir par tableau (0-based).
   * k: draw365 | days | previous | times | maxTight | maxLoose | maxTimes | drawsSelect
   */
  var QUOTIDIENNE_TABLE_HEADER_MAP = {
    q2: [
      [{ i: 0, k: 'draw365' }, { i: 3, k: 'days' }, { i: 4, k: 'previous' }, { i: 5, k: 'times' }, { i: 6, k: 'maxLoose' }],
      [{ i: 2, k: 'days' }, { i: 3, k: 'lastDraw' }, { i: 4, k: 'maxTimes' }, { i: 5, k: 'maxLoose' }],
      [{ i: 1, k: 'days' }, { i: 2, k: 'drawsSelect', selectId: 'q2CountRange' }]
    ],
    q3: [
      [{ i: 0, k: 'draw365' }, { i: 4, k: 'days' }, { i: 5, k: 'previous' }, { i: 6, k: 'times' }, { i: 7, k: 'maxTight' }],
      [{ i: 3, k: 'days' }, { i: 4, k: 'lastDraw' }, { i: 5, k: 'maxTimes' }, { i: 6, k: 'maxLoose' }],
      [{ i: 1, k: 'days' }, { i: 2, k: 'drawsSelect', selectId: 'q3CountRange' }]
    ],
    q4: [
      [{ i: 0, k: 'draw365' }, { i: 5, k: 'days' }, { i: 6, k: 'previous' }, { i: 7, k: 'times' }, { i: 8, k: 'maxTight' }],
      [{ i: 4, k: 'days' }, { i: 5, k: 'lastDraw' }, { i: 6, k: 'maxTimes' }, { i: 7, k: 'maxLoose' }],
      [{ i: 1, k: 'days' }, { i: 2, k: 'drawsSelect', selectId: 'q4CountRange' }]
    ]
  };

  function applyQuotidienneTableTranslations(which) {
    var lang = currentLang === 'en' ? 'en' : 'fr';
    var texts = QUOTIDIENNE_TABLE_I18N[lang];
    var plan = QUOTIDIENNE_TABLE_HEADER_MAP[which];
    if (!plan || !texts) return;

    var tables = container.querySelectorAll('.interactive-table');

    function setHeader(th, entry) {
      if (!th) return;
      if (entry.k === 'draw365') {
        th.innerHTML = texts.draw + '<br><small>' + texts.last365 + '</small>';
      } else if (entry.k === 'maxTimes') {
        th.innerHTML = 'Max<br>' + texts.times;
      } else if (entry.k === 'drawsSelect') {
        var sel = entry.selectId ? th.querySelector('#' + entry.selectId) : null;
        if (sel) {
          th.innerHTML = texts.draws + ' &nbsp; <br>';
          th.appendChild(sel);
        }
      } else if (entry.k === 'maxTight') {
        th.innerHTML = texts.maxDaysTight;
      } else if (entry.k === 'maxLoose') {
        th.innerHTML = texts.maxDaysLoose;
      } else if (texts[entry.k]) {
        th.innerHTML = texts[entry.k];
      }
    }

    plan.forEach(function (rows, tableIdx) {
      var table = tables[tableIdx];
      if (!table || !rows) return;
      var headers = table.querySelectorAll('thead th');
      rows.forEach(function (entry) {
        setHeader(headers[entry.i], entry);
      });
    });
  }

  function updatePageTitleForLang(page) {
    if (!pageTitle || !page) return;

    var sub = pageTitle.querySelector('.sub');
    if (!sub) return;

    var countMatch = (sub.textContent || '').match(/\d+/);
    if (!countMatch) return;
    var count = countMatch[0];
    var suffix = currentLang === 'en' ? 'draws' : 'tirages';

    if (page.indexOf('q2') !== -1) {
      pageTitle.innerHTML = currentLang === 'en'
        ? 'Quotidienne 2 <span class="sub">' + count + ' draws since May 19, 2016</span>'
        : 'Quotidienne 2 <span class="sub">' + count + ' tirages depuis 19 mai 2016</span>';
    } else if (page.indexOf('q3') !== -1) {
      pageTitle.innerHTML = currentLang === 'en'
        ? 'Quotidienne 3 <span class="sub">' + count + ' draws since June 6, 1983</span>'
        : 'Quotidienne 3 <span class="sub">' + count + ' tirages depuis 06 juin 1983</span>';
    } else if (page.indexOf('q4') !== -1) {
      pageTitle.innerHTML = currentLang === 'en'
        ? 'Quotidienne 4 <span class="sub">' + count + ' draws since June 6, 1983</span>'
        : 'Quotidienne 4 <span class="sub">' + count + ' tirages depuis 06 juin 1983</span>';
    } else if (page.indexOf('toutourien/') === 0) {
      pageTitle.innerHTML = currentLang === 'en'
        ? 'Tout ou Rien <span class="sub">' + count + ' draws since November 17, 2014</span>'
        : 'Tout ou Rien <span class="sub">' + count + ' tirages depuis 17 novembre 2014</span>';
    } else if (page.indexOf('vie/') === 0) {
      pageTitle.innerHTML = currentLang === 'en'
        ? 'Grande Vie <span class="sub">' + count + ' draws since October 20, 2016</span>'
        : 'Grande Vie <span class="sub">' + count + ' tirages depuis 20 octobre 2016</span>';
    }
  }

  function loadPage(page, options) {
    if (!page) return;
    options = options || {};
    isAltView = !!options.preserveAltView;
    setNavOpen(false);

    var storedLang = localStorage.getItem('lang');
    if (storedLang === 'fr' || storedLang === 'en') {
      currentLang = storedLang;
    }

    var params = new URLSearchParams();
    var isToutMainPage = page.indexOf('toutourien/tout.php') !== -1;
    if (!isToutMainPage && page !== 'quotidienne/QInfo/q2info.php' && page !== 'quotidienne/QInfo/q3info.php' && page !== 'quotidienne/QInfo/q4info.php' && page !== 'toutourien/Info/toutinfo.php' && page !== 'vie/Info/vieinfo.php' && toggleSwitch.checked) {
      params.set('norder', '1');
    }
    if (page === 'quotidienne/QInfo/q2info.php' || page === 'quotidienne/QInfo/q3info.php' || page === 'quotidienne/QInfo/q4info.php' || page === 'toutourien/Info/toutinfo.php' || page === 'vie/Info/vieinfo.php') {
      if (currentLang === 'en') {
        params.set('lang', 'en');
      }
    } else {
      params.set('lang', currentLang);
    }

    if (page === 'quotidienne/q2.php') {
      params.set('count_range', String(q2CountRange || 50));
    } else if (page === 'quotidienne/q3.php') {
      params.set('count_range', String(q3CountRange || 50));
    } else if (page === 'quotidienne/QInfo/q2info.php') {
      params.set('limit', String(q2ChartLimit));
      params.set('grid_limit', String(q2InfoGridLimit || 50));
      if (q2InfoNorder) {
        params.set('norder', '1');
      }
    } else if (page === 'quotidienne/QInfo/q3info.php') {
      params.set('limit', String(q3InfoLimit || 50));
    } else if (page === 'quotidienne/QInfo/q4info.php') {
      params.set('grid_limit', String(q4InfoLimit || 50));
    } else if (page === 'toutourien/Info/toutinfo.php') {
      params.set('limit', String(toutInfoLimit || 50));
    } else if (page === 'quotidienne/q4.php') {
      params.set('count_range', String(q4CountRange || 50));
    } else if (page === 'toutourien/tout.php') {
      params.set('limit', toggleSwitch.checked ? '200' : '50');
    } else if (page === 'vie/Info/vieinfo.php') {
      params.set('grid_limit', String(vieInfoGridLimit || 50));
    } else if (page === 'vie/vie.php') {
      params.set('vie_gn_tirages', String(vieGnTirageCount || 50));
    }

    var query = params.toString();
    var url = query ? page + '?' + query : page;

    if (loadAbortController) {
      loadAbortController.abort();
    }
    loadAbortController = new AbortController();
    var requestId = ++loadRequestId;

    setSpinner(true);
    fetch(url, { signal: loadAbortController.signal, cache: 'no-store' })
      .then(function (html) {
        if (!html.ok) throw new Error('Erreur de chargement');
        return html.text();
      })
      .then(function (html) {
        if (requestId !== loadRequestId) return;
        if (q2InfoChartInstance) {
          destroyQ2InfoChart();
        }
        container.innerHTML = html;
        container.setAttribute('data-page', page);
        if (page === 'quotidienne/q2.php') {
          applyQuotidienneTableTranslations('q2');
        } else if (page === 'quotidienne/q3.php') {
          applyQuotidienneTableTranslations('q3');
        } else if (page === 'quotidienne/q4.php') {
          applyQuotidienneTableTranslations('q4');
        }
        updateToggleLabels(page);
        makeTablesSortable();

        /* QInfo до q2.php / q4.php: иначе indexOf('q2') совпадает с путём q2info.php */
        if (page === 'quotidienne/QInfo/q2info.php') {
          initQ2InfoPage(page);
        } else if (page === 'quotidienne/q2.php') {
          bindRangeSelect(container.querySelector('#q2CountRange'), 'q2', page);
        } else if (page === 'quotidienne/q3.php') {
          bindRangeSelect(container.querySelector('#q3CountRange'), 'q3', page);
        } else if (page === 'quotidienne/QInfo/q3info.php') {
          bindQ3InfoLimitSelect(container.querySelector('#q3InfoLimit'), page);
        } else if (page === 'quotidienne/QInfo/q4info.php') {
          initQ4InfoPage(page);
        } else if (page === 'toutourien/Info/toutinfo.php') {
          bindToutInfoLimitSelect(container.querySelector('#toutInfoLimit'), page);
        } else if (page === 'vie/Info/vieinfo.php') {
          bindVieInfoGridLimitSelect(container.querySelector('#vieInfoGridLimit'), page);
        } else if (page === 'toutourien/tout.php') {
          initToutVerifierBlock();
        } else if (page === 'quotidienne/q4.php') {
          bindRangeSelect(container.querySelector('#q4CountRange'), 'q4', page);
        } else if (page === 'vie/vie.php') {
          bindRangeSelect(container.querySelector('#vieGnTirageSelect'), 'vieGn', page);
        }

        var meta = container.querySelector('[id$="-meta"]');
        var count = meta ? meta.dataset.count || '?' : '?';
        if (page.indexOf('q2') !== -1) {
          pageTitle.innerHTML = currentLang === 'en'
            ? 'Quotidienne 2 <span class="sub">' + count + ' draws since May 19, 2016</span>'
            : 'Quotidienne 2 <span class="sub">' + count + ' tirages depuis 19 mai 2016</span>';
        } else if (page.indexOf('q3') !== -1) {
          pageTitle.innerHTML = currentLang === 'en'
            ? 'Quotidienne 3 <span class="sub">' + count + ' draws since June 6, 1983</span>'
            : 'Quotidienne 3 <span class="sub">' + count + ' tirages depuis 06 juin 1983</span>';
        } else if (page.indexOf('q4') !== -1) {
          pageTitle.innerHTML = currentLang === 'en'
            ? 'Quotidienne 4 <span class="sub">' + count + ' draws since June 6, 1983</span>'
            : 'Quotidienne 4 <span class="sub">' + count + ' tirages depuis 06 juin 1983</span>';
        } else if (page.indexOf('toutourien/') === 0) {
          pageTitle.innerHTML = currentLang === 'en'
            ? 'Tout ou Rien <span class="sub">' + count + ' draws since November 17, 2014</span>'
            : 'Tout ou Rien <span class="sub">' + count + ' tirages depuis 17 novembre 2014</span>';
        } else if (page.indexOf('vie/') === 0) {
          pageTitle.innerHTML = currentLang === 'en'
            ? 'Grande Vie <span class="sub">' + count + ' draws since October 20, 2016</span>'
            : 'Grande Vie <span class="sub">' + count + ' tirages depuis 20 octobre 2016</span>';
        }
        if (infoBtn) {
          infoBtn.style.visibility = '';
          infoBtn.style.pointerEvents = '';
        }
        infoBtn.textContent = '\u2139';
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        if (requestId !== loadRequestId) return;
        if (q2InfoChartInstance) {
          destroyQ2InfoChart();
        }
        container.innerHTML = '<p class="error">Impossible de charger la page.</p>';
      })
      .finally(function () {
        if (requestId !== loadRequestId) return;
        setSpinner(false);
      });
  }

  function initToutVerifierBlock() {
    var root = container.querySelector('#toutVerifierRoot');
    if (!root) return;

    var i18nEl = container.querySelector('#tout-verifier-i18n');
    var I18N = {};
    if (i18nEl) {
      try {
        I18N = JSON.parse(i18nEl.textContent);
      } catch (e) {
        I18N = {};
      }
    }

    var grid = container.querySelector('#toutVerifierGrid');
    var checkBtn = container.querySelector('#toutVerifierCheckBtn');
    var resetBtn = container.querySelector('#toutVerifierResetBtn');
    var counterEl = container.querySelector('#toutVerifierCounter');
    var distWrap = container.querySelector('#toutVerifierDistTableWrap');
    var placeholderTpl = container.querySelector('#toutVerifierPlaceholderTpl');

    if (!grid || !checkBtn || !distWrap || !placeholderTpl) return;

    var selected = new Set();

    function counterText(n) {
      return (I18N.counter || '').replace('{n}', String(n));
    }

    function updateCounter() {
      if (counterEl) counterEl.textContent = counterText(selected.size);
      var lock = selected.size >= 12;
      grid.querySelectorAll('.tout-verifier-circle').forEach(function (c) {
        if (!c.classList.contains('selected')) {
          c.classList.toggle('locked', lock);
        } else {
          c.classList.remove('locked');
        }
      });
    }

    function renderDistTable(rows) {
      var tbody = document.createElement('tbody');
      rows.forEach(function (r) {
        var tr = document.createElement('tr');
        var td1 = document.createElement('td');
        td1.textContent = String(r.k) + '/12';
        var td2 = document.createElement('td');
        td2.textContent = r.cnt === null || r.cnt === undefined ? '-' : String(r.cnt);
        var td3 = document.createElement('td');
        td3.textContent = r.pr ? String(r.pr) : '';
        tr.appendChild(td1);
        tr.appendChild(td2);
        tr.appendChild(td3);
        tbody.appendChild(tr);
      });
      var tbl = document.createElement('table');
      tbl.className = 'tout-verifier-dist';
      tbl.id = 'toutVerifierDistTable';
      tbl.appendChild(tbody);
      distWrap.innerHTML = '';
      distWrap.appendChild(tbl);
    }

    function resetCheckButton() {
      checkBtn.disabled = false;
      checkBtn.textContent = I18N.btnCheck || 'Check';
      checkBtn.classList.remove('tout-verifier-btn-success', 'tout-verifier-btn-warning', 'tout-verifier-btn-error');
    }

    function resetSelection() {
      selected.clear();
      grid.querySelectorAll('.tout-verifier-circle').forEach(function (c) {
        c.classList.remove('selected', 'locked');
      });
      updateCounter();
      resetCheckButton();
      distWrap.innerHTML = '';
      var clone = document.importNode(placeholderTpl.content, true);
      var tbl = clone.querySelector('table');
      if (tbl) distWrap.appendChild(tbl);
    }

    grid.querySelectorAll('.tout-verifier-circle').forEach(function (el) {
      var num = parseInt(el.getAttribute('data-num'), 10);
      el.addEventListener('click', function () {
        if (selected.has(num)) {
          selected.delete(num);
          el.classList.remove('selected');
        } else {
          if (selected.size >= 12) return;
          selected.add(num);
          el.classList.add('selected');
        }
        updateCounter();
      });
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', resetSelection);
    }

    checkBtn.addEventListener('click', function () {
      if (checkBtn.disabled) return;
      if (selected.size !== 12) {
        window.alert(I18N.alertNeed12 || '');
        return;
      }
      var body = new URLSearchParams();
      body.set('tout_verify', '1');
      body.set('numbers', Array.from(selected).sort(function (a, b) { return a - b; }).join(','));
      body.set('lang', currentLang === 'en' ? 'en' : 'fr');
      fetch('toutourien/tout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
        cache: 'no-store'
      })
        .then(function (r) {
          if (!r.ok) throw new Error('verify');
          return r.json();
        })
        .then(function (data) {
          if (!data) return;
          checkBtn.textContent = data.resultMessage || '';
          checkBtn.classList.remove('tout-verifier-btn-success', 'tout-verifier-btn-warning', 'tout-verifier-btn-error');
          if (data.resultColor === 'green') checkBtn.classList.add('tout-verifier-btn-success');
          else if (data.resultColor === 'orange') checkBtn.classList.add('tout-verifier-btn-warning');
          else if (data.resultColor === 'red') checkBtn.classList.add('tout-verifier-btn-error');
          if (data.ok && data.tableRows) {
            renderDistTable(data.tableRows);
            checkBtn.disabled = true;
          } else {
            checkBtn.disabled = false;
          }
        })
        .catch(function () {
          window.alert(currentLang === 'en' ? 'Network error.' : 'Erreur réseau.');
        });
    });

    updateCounter();
  }

  function bindRangeSelect(select, key, page) {
    if (!select) return;
    var range =
      key === 'q2' ? q2CountRange
        : key === 'q3' ? q3CountRange
          : key === 'vieGn' ? vieGnTirageCount
            : q4CountRange;
    select.value = String(range || 50);
    select.addEventListener('change', function () {
      var v = parseInt(select.value, 10) || 50;
      if (key === 'q2') q2CountRange = v;
      else if (key === 'q3') q3CountRange = v;
      else if (key === 'vieGn') vieGnTirageCount = v;
      else q4CountRange = v;
      loadPage(page);
    });
  }

  function bindQ3InfoLimitSelect(select, page) {
    if (!select) return;
    select.value = String(q3InfoLimit || 50);
    select.addEventListener('change', function () {
      q3InfoLimit = parseInt(select.value, 10) || 50;
      loadPage(page, { preserveAltView: true });
    });
  }

  function bindQ4InfoLimitSelect(select, page) {
    if (!select) return;
    select.value = String(q4InfoLimit || 50);
    select.addEventListener('change', function () {
      q4InfoLimit = parseInt(select.value, 10) || 50;
      loadPage(page, { preserveAltView: true });
    });
  }

  function bindToutInfoLimitSelect(select, page) {
    if (!select) return;
    toutInfoLimit = parseInt(select.value, 10) || 50;
    select.value = String(toutInfoLimit);
    select.addEventListener('change', function () {
      toutInfoLimit = parseInt(select.value, 10) || 50;
      loadPage(page, { preserveAltView: true });
    });
  }

  function bindVieInfoGridLimitSelect(select, page) {
    if (!select) return;
    vieInfoGridLimit = parseInt(select.value, 10) || 50;
    select.value = String(vieInfoGridLimit);
    select.addEventListener('change', function () {
      vieInfoGridLimit = parseInt(select.value, 10) || 50;
      loadPage(page, { preserveAltView: true });
    });
  }

  function initQ2InfoPage(page) {
    var gridSelect = container.querySelector('#q2InfoGridLimit');
    if (gridSelect) {
      q2InfoGridLimit = parseInt(gridSelect.value, 10) || 50;
      gridSelect.addEventListener('change', function () {
        q2InfoGridLimit = parseInt(gridSelect.value, 10) || 50;
        loadPage(page, { preserveAltView: true });
      });
    }

    var bootEl = container.querySelector('#q2info-bootstrap');
    if (!bootEl) return;

    var boot;
    try {
      boot = JSON.parse(bootEl.getAttribute('data-json') || '{}');
    } catch (e) {
      return;
    }

    var texts = boot.texts || {};
    var canvas = container.querySelector('#q2infoChart');
    var chartSelect = container.querySelector('#q2InfoChartLimit');
    var norderEl = container.querySelector('#q2infoNorderToggle');
    var statsBody = container.querySelector('#q2infoStatsBody');

    if (chartSelect) {
      var cv = parseInt(chartSelect.value, 10);
      q2ChartLimit = isNaN(cv) ? 100 : cv;
    }
    if (norderEl) {
      q2InfoNorder = !!norderEl.checked;
    }

    function formatData(dataFromPHP) {
      var scatterData = dataFromPHP.map(function (item) {
        var combo = (String(item.n1) + String(item.n2)).padStart(2, '0');
        return {
          x: parseInt(item.days, 10) || 0,
          y: combo
        };
      }).filter(function (point) {
        return !isNaN(point.x);
      });

      var uniqueYValues = [];
      scatterData.forEach(function (p) {
        if (uniqueYValues.indexOf(p.y) === -1) uniqueYValues.push(p.y);
      });
      uniqueYValues.sort();
      var yIndexMap = {};
      uniqueYValues.forEach(function (val, idx) {
        yIndexMap[val] = idx;
      });

      var scatterFill = themeCssVar('--q2info-chart-scatter', 'rgba(54, 162, 235, 0.6)');
      var scatterBorder = themeCssVar('--q2info-chart-scatter-border', 'rgba(54, 162, 235, 0.95)');
      var tickColor = themeCssVar('--q2info-chart-tick', '#64748b');
      var gridColor = themeCssVar('--q2info-chart-grid', 'rgba(100, 116, 139, 0.2)');

      var chartData = {
        datasets: [{
          label: texts.chartCombinations || '',
          data: scatterData.map(function (point) {
            return { x: point.x, y: yIndexMap[point.y] };
          }),
          backgroundColor: scatterFill,
          borderColor: scatterBorder,
          borderWidth: 1,
          pointRadius: 4,
          pointHoverRadius: 5
        }]
      };

      var config = {
        type: 'scatter',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: { display: true, text: texts.statsDays || '', color: tickColor },
              ticks: { color: tickColor },
              grid: { color: gridColor }
            },
            y: {
              ticks: {
                color: tickColor,
                callback: function (value) {
                  return uniqueYValues[value] || '';
                }
              },
              title: { display: true, text: texts.chartCombinations || '', color: tickColor },
              grid: { color: gridColor }
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (context) {
                  var index = context.raw.y;
                  var combo = uniqueYValues[index] || '';
                  var pat = texts.tooltipPattern || '';
                  return pat.replace('{combo}', combo).replace('{days}', String(context.raw.x));
                }
              }
            }
          }
        }
      };

      return { config: config, comboDays: scatterData };
    }

    function calculateStats(comboDays) {
      if (!statsBody) return;
      var ranges = { '1–50': 0, '1–100': 0, '1–200': 0, '201+': 0 };
      comboDays.forEach(function (item) {
        var days = item.x;
        if (days >= 1 && days <= 50) ranges['1–50']++;
        if (days >= 1 && days <= 100) ranges['1–100']++;
        if (days >= 1 && days <= 200) ranges['1–200']++;
        if (days >= 201) ranges['201+']++;
      });
      var total = comboDays.length;
      statsBody.innerHTML = '';
      Object.keys(ranges).forEach(function (label) {
        var count = ranges[label];
        var percent = total > 0 ? ((count / total) * 100).toFixed(1) : '0.0';
        statsBody.innerHTML += '<tr><td>' + label + '</td><td>' + count + '</td><td>' + percent + '%</td></tr>';
      });
    }

    function renderChart(data) {
      if (!canvas || typeof window.Chart === 'undefined') {
        return;
      }
      var formatted = formatData(data);
      destroyQ2InfoChart();
      q2InfoChartInstance = new window.Chart(canvas, formatted.config);
      calculateStats(formatted.comboDays);
    }

    function loadChartAjax() {
      var p = new URLSearchParams();
      p.set('limit', String(q2ChartLimit));
      p.set('grid_limit', String(q2InfoGridLimit || 50));
      p.set('lang', currentLang);
      p.set('ajax', '1');
      if (q2InfoNorder) p.set('norder', '1');
      fetch('quotidienne/QInfo/q2info.php?' + p.toString(), { cache: 'no-store' })
        .then(function (r) {
          if (!r.ok) throw new Error('q2info ajax');
          return r.json();
        })
        .then(function (data) {
          renderChart(data);
        })
        .catch(function (err) {
          console.error(err);
        });
    }

    var q2ChartUiBound = false;

    function startQ2ChartUiOnce() {
      if (!canvas || typeof window.Chart === 'undefined') {
        return false;
      }
      if (q2ChartUiBound) {
        return true;
      }
      q2ChartUiBound = true;

      var initial = boot.initialData;
      if (Array.isArray(initial)) {
        renderChart(initial);
      }

      if (chartSelect) {
        chartSelect.addEventListener('change', function () {
          var v = parseInt(chartSelect.value, 10);
          q2ChartLimit = isNaN(v) ? 100 : v;
          loadChartAjax();
        });
      }

      if (norderEl) {
        norderEl.addEventListener('change', function () {
          q2InfoNorder = !!norderEl.checked;
          loadChartAjax();
        });
      }

      return true;
    }

    loadChartJs()
      .then(function () {
        if (!canvas || !canvas.isConnected) {
          return;
        }
        startQ2ChartUiOnce();
      })
      .catch(function (err) {
        console.error(err);
      });
  }

  function applyQ4InfoFilter(tableId, filterValue) {
    var table = container.querySelector('#' + tableId);
    if (!table) return;

    var rows = table.querySelectorAll('tbody tr');
    var count = 0;
    rows.forEach(function (row) {
      var type = row.dataset.comboType;
      var visible = filterValue === 'all' || filterValue === type || !type;
      row.style.display = visible ? '' : 'none';
      if (visible) count++;
    });

    var titleEl = table.querySelector('.q4info-head-title');
    var header = titleEl || table.querySelector('thead tr:first-child th');
    if (!header) return;
    var baseTitle = table.dataset.baseTitle || '';
    var combsLabel = table.dataset.combsLabel || '';
    header.innerHTML = baseTitle + ' <span><strong>' + count + '</strong> ' + combsLabel + '</span>';
  }

  function initQ4InfoPage(page) {
    bindQ4InfoLimitSelect(container.querySelector('#q4InfoLimit'), page);

    container.querySelectorAll('.q4info-filter').forEach(function (select) {
      var tableId = select.dataset.tableId;
      applyQ4InfoFilter(tableId, select.value || 'all');
      select.addEventListener('change', function () {
        applyQ4InfoFilter(tableId, select.value || 'all');
      });
    });

    container.querySelectorAll('.q4info-members-btn').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.stopPropagation();
        container.querySelectorAll('.dropdown-panel.open').forEach(function (panel) {
          panel.classList.remove('open');
        });
        var targetId = btn.dataset.membersTarget;
        var panel = targetId ? container.querySelector('#' + targetId) : null;
        if (panel) panel.classList.add('open');
      });
    });
  }

  function makeTablesSortable() {
    container.querySelectorAll('.interactive-table').forEach(function (table) {
      if (table.closest('.number-stats-table')) return;
      if (table.id === 'statsOrderTable' || table.id === 'freeOrderTable') return;
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
    isAltView = false;
    setNavOpen(false);
    if (q2InfoChartInstance) {
      destroyQ2InfoChart();
    }
    if (originalHomeContent) {
      container.innerHTML = originalHomeContent;
    }
    container.setAttribute('data-page', '');
    pageTitle.textContent = 'Bienvenue';
    if (infoBtn) {
      infoBtn.style.visibility = '';
      infoBtn.style.pointerEvents = '';
    }
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
      var nextLang = btn.dataset.lang;
      if (nextLang !== 'fr' && nextLang !== 'en') return;
      if (nextLang === currentLang) return;
      currentLang = nextLang;
      localStorage.setItem('lang', currentLang);
      updateLangUI();
      updateToggleLabels();
      var page = container.getAttribute('data-page');
      if (!page) return;
      if (isAltView) {
        if (page === 'quotidienne/QInfo/q2info.php' || page === 'quotidienne/QInfo/q3info.php' || page === 'quotidienne/QInfo/q4info.php' || page === 'toutourien/Info/toutinfo.php' || page === 'vie/Info/vieinfo.php') {
          loadPage(page, { preserveAltView: true });
          return;
        }
        updatePageTitleForLang(page);
        loadPage(page, { preserveAltView: true });
        return;
      }
      loadPage(page);
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
      var fetchInfoPage = getInfoFetchPage(page);
      if (!fetchInfoPage) {
        return;
      }
      var nextAltView = fetchInfoPage === 'quotidienne/QInfo/q2info.php' || fetchInfoPage === 'quotidienne/QInfo/q3info.php' || fetchInfoPage === 'quotidienne/QInfo/q4info.php' || fetchInfoPage === 'toutourien/Info/toutinfo.php' || fetchInfoPage === 'vie/Info/vieinfo.php';
      loadPage(fetchInfoPage, { preserveAltView: nextAltView });
    });
  }

  document.addEventListener('click', function (event) {
    if (!container.querySelector('.q4info-layout')) return;
    if (event.target.closest('.dropdown')) return;
    container.querySelectorAll('.dropdown-panel.open').forEach(function (panel) {
      panel.classList.remove('open');
    });
  });

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
