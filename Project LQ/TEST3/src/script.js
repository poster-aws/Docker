(function () {
  'use strict';

  let currentLang = localStorage.getItem('lang') || 'fr';
  let currentTheme = localStorage.getItem('theme') || 'dark';
  let q2CountRange = 50;
  let q3CountRange = 50;
  let q4CountRange = 50;
  let q3InfoLimit = 50;
  let q4InfoLimit = 50;
  let q2ChartLimit = 100;
  let q2InfoGridLimit = 50;
  let q2InfoNorder = false;
  let q2InfoChartInstance = null;
  let isAltView = false;
  let originalHomeContent = null;
  let loadAbortController = null;
  let loadRequestId = 0;

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

  function applyQ2Translations() {
    var texts = currentLang === 'en'
      ? {
          draw: 'Draw',
          last365: 'Last 365',
          days: 'Days<br>ago',
          previous: 'Previous<br>draw',
          times: 'Times',
          maxDays: 'Max days<br>ago',
          lastDraw: 'Last<br>draw',
          draws: 'Draws'
        }
      : {
          draw: 'Tirage',
          last365: '365 dernières',
          days: 'Jours<br>passés',
          previous: "L'avant<br>dernière",
          times: 'Fois',
          maxDays: 'Max jours<br>passés',
          lastDraw: 'Dernier<br>Tirage',
          draws: 'Tirages'
        };

    var tables = container.querySelectorAll('.interactive-table');
    if (tables[0]) {
      var headers1 = tables[0].querySelectorAll('thead th');
      if (headers1[0]) headers1[0].innerHTML = texts.draw + '<br><small>' + texts.last365 + '</small>';
      if (headers1[3]) headers1[3].innerHTML = texts.days;
      if (headers1[4]) headers1[4].innerHTML = texts.previous;
      if (headers1[5]) headers1[5].innerHTML = texts.times;
      if (headers1[6]) headers1[6].innerHTML = texts.maxDays;
    }
    if (tables[1]) {
      var headers2 = tables[1].querySelectorAll('thead th');
      if (headers2[2]) headers2[2].innerHTML = texts.days;
      if (headers2[3]) headers2[3].innerHTML = texts.lastDraw;
      if (headers2[4]) headers2[4].innerHTML = 'Max<br>' + texts.times;
      if (headers2[5]) headers2[5].innerHTML = texts.maxDays;
    }
    if (tables[2]) {
      var headers3 = tables[2].querySelectorAll('thead th');
      if (headers3[1]) headers3[1].innerHTML = texts.days;
      if (headers3[2]) {
        var rangeSelect = headers3[2].querySelector('#q2CountRange');
        if (rangeSelect) {
          headers3[2].innerHTML = texts.draws + ' &nbsp; <br>';
          headers3[2].appendChild(rangeSelect);
        }
      }
    }
  }

  function applyQ3Translations() {
    var texts = currentLang === 'en'
      ? {
          draw: 'Draw',
          last365: 'Last 365',
          days: 'Days<br>ago',
          previous: 'Previous<br>draw',
          times: 'Times',
          maxDays: 'Max<br>days ago',
          lastDraw: 'Last<br>draw',
          draws: 'Draws'
        }
      : {
          draw: 'Tirage',
          last365: '365 dernières',
          days: 'Jours<br>passés',
          previous: "L'avant<br>dernière",
          times: 'Fois',
          maxDays: 'Max<br>jours passés',
          lastDraw: 'Dernier<br>Tirage',
          draws: 'Tirages'
        };

    var tables = container.querySelectorAll('.interactive-table');
    if (tables[0]) {
      var headers1 = tables[0].querySelectorAll('thead th');
      if (headers1[0]) headers1[0].innerHTML = texts.draw + '<br><small>' + texts.last365 + '</small>';
      if (headers1[4]) headers1[4].innerHTML = texts.days;
      if (headers1[5]) headers1[5].innerHTML = texts.previous;
      if (headers1[6]) headers1[6].innerHTML = texts.times;
      if (headers1[7]) headers1[7].innerHTML = texts.maxDays;
    }
    if (tables[1]) {
      var headers2 = tables[1].querySelectorAll('thead th');
      if (headers2[3]) headers2[3].innerHTML = texts.days;
      if (headers2[4]) headers2[4].innerHTML = texts.lastDraw;
      if (headers2[5]) headers2[5].innerHTML = 'Max<br>' + texts.times;
      if (headers2[6]) headers2[6].innerHTML = 'Max jours<br>passés';
      if (currentLang === 'en' && headers2[6]) headers2[6].innerHTML = 'Max days<br>ago';
    }
    if (tables[2]) {
      var headers3 = tables[2].querySelectorAll('thead th');
      if (headers3[1]) headers3[1].innerHTML = texts.days;
      if (headers3[2]) {
        var rangeSelect = headers3[2].querySelector('#q3CountRange');
        if (rangeSelect) {
          headers3[2].innerHTML = texts.draws + ' &nbsp; <br>';
          headers3[2].appendChild(rangeSelect);
        }
      }
    }
  }

  function applyQ4Translations() {
    var texts = currentLang === 'en'
      ? {
          draw: 'Draw',
          last365: 'Last 365',
          days: 'Days<br>ago',
          previous: 'Previous<br>draw',
          times: 'Times',
          maxDays: 'Max<br>days ago',
          lastDraw: 'Last<br>draw',
          draws: 'Draws'
        }
      : {
          draw: 'Tirage',
          last365: '365 dernières',
          days: 'Jours<br>passés',
          previous: "L'avant<br>dernière",
          times: 'Fois',
          maxDays: 'Max<br>jours passés',
          lastDraw: 'Dernier<br>Tirage',
          draws: 'Tirages'
        };

    var tables = container.querySelectorAll('.interactive-table');
    if (tables[0]) {
      var headers1 = tables[0].querySelectorAll('thead th');
      if (headers1[0]) headers1[0].innerHTML = texts.draw + '<br><small>' + texts.last365 + '</small>';
      if (headers1[5]) headers1[5].innerHTML = texts.days;
      if (headers1[6]) headers1[6].innerHTML = texts.previous;
      if (headers1[7]) headers1[7].innerHTML = texts.times;
      if (headers1[8]) headers1[8].innerHTML = texts.maxDays;
    }
    if (tables[1]) {
      var headers2 = tables[1].querySelectorAll('thead th');
      if (headers2[4]) headers2[4].innerHTML = texts.days;
      if (headers2[5]) headers2[5].innerHTML = texts.lastDraw;
      if (headers2[6]) headers2[6].innerHTML = 'Max<br>' + texts.times;
      if (headers2[7]) headers2[7].innerHTML = currentLang === 'en' ? 'Max days<br>ago' : 'Max jours<br>passés';
    }
    if (tables[2]) {
      var headers3 = tables[2].querySelectorAll('thead th');
      if (headers3[1]) headers3[1].innerHTML = texts.days;
      if (headers3[2]) {
        var rangeSelect = headers3[2].querySelector('#q4CountRange');
        if (rangeSelect) {
          headers3[2].innerHTML = texts.draws + ' &nbsp; <br>';
          headers3[2].appendChild(rangeSelect);
        }
      }
    }
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
    if (page !== 'quotidienne/QInfo/q2info.php' && page !== 'quotidienne/QInfo/q3info.php' && page !== 'quotidienne/QInfo/q4info.php' && toggleSwitch.checked) {
      params.set('norder', '1');
    }
    if (page === 'quotidienne/QInfo/q2info.php' || page === 'quotidienne/QInfo/q3info.php' || page === 'quotidienne/QInfo/q4info.php') {
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
    } else if (page === 'quotidienne/q4.php') {
      params.set('count_range', String(q4CountRange || 50));
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
          applyQ2Translations();
        } else if (page === 'quotidienne/q3.php') {
          applyQ3Translations();
        } else if (page === 'quotidienne/q4.php') {
          applyQ4Translations();
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
        } else if (page === 'quotidienne/q4.php') {
          bindRangeSelect(container.querySelector('#q4CountRange'), 'q4', page);
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

      var chartData = {
        datasets: [{
          label: texts.chartCombinations || '',
          data: scatterData.map(function (point) {
            return { x: point.x, y: yIndexMap[point.y] };
          }),
          backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
      };

      var config = {
        type: 'scatter',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { title: { display: true, text: texts.statsDays || '' } },
            y: {
              ticks: {
                callback: function (value) {
                  return uniqueYValues[value] || '';
                }
              },
              title: { display: true, text: texts.chartCombinations || '' }
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
      if (!canvas || typeof Chart === 'undefined') {
        return;
      }
      var formatted = formatData(data);
      destroyQ2InfoChart();
      q2InfoChartInstance = new Chart(canvas, formatted.config);
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

    function startQ2ChartUiOnce() {
      if (!canvas || typeof Chart === 'undefined') {
        return false;
      }

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

    if (!startQ2ChartUiOnce()) {
      var tries = 0;
      var t = setInterval(function () {
        tries++;
        if (startQ2ChartUiOnce() || tries > 80) {
          clearInterval(t);
        }
      }, 50);
    }
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
        if (page === 'quotidienne/QInfo/q2info.php' || page === 'quotidienne/QInfo/q3info.php' || page === 'quotidienne/QInfo/q4info.php') {
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
      var nextAltView = fetchInfoPage === 'quotidienne/QInfo/q2info.php' || fetchInfoPage === 'quotidienne/QInfo/q3info.php' || fetchInfoPage === 'quotidienne/QInfo/q4info.php';
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
