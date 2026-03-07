// src/script.js

// Переключение отображения меню
function toggleMenu() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

// Закрытие меню при клике вне
document.addEventListener('click', function (e) {
  const menu = document.getElementById("dropdownMenu");
  const icon = document.querySelector('.menu-icon');
  if (!menu.contains(e.target) && !icon.contains(e.target)) {
    menu.style.display = "none";
  }
});

// Переключение языка FR / EN
document.addEventListener("click", (e) => {
  const btn = e.target.closest(".lang-btn");
  if (!btn) return;

  currentLang = btn.dataset.lang;
  localStorage.setItem("lang", currentLang);
  updateLangUI();

  const container = document.getElementById("container");
  const currentPage = container.getAttribute("data-page");

  if (currentPage) {
    loadPage(currentPage);
  }
});


// 🔁 Сортировка таблиц
function makeTablesSortable() {
  const tables = document.querySelectorAll(".interactive-table");

  tables.forEach(table => {
    // не сортируем таблицу дней (третья таблица Q2)
    if (table.closest(".number-stats-table")) {
      return;
    }

    const headers = table.querySelectorAll("th");
    headers.forEach((th, columnIndex) => {
      th.style.cursor = "pointer";
      th.addEventListener("click", () => {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const isAscending = th.classList.contains("sort-asc");

        headers.forEach(h => h.classList.remove("sort-asc", "sort-desc"));

        const sortedRows = rows.sort((a, b) => {
          const aText = a.children[columnIndex].innerText.trim();
          const bText = b.children[columnIndex].innerText.trim();

          const aVal = isNaN(aText) ? aText : parseFloat(aText);
          const bVal = isNaN(bText) ? bText : parseFloat(bText);

          if (aVal < bVal) return isAscending ? 1 : -1;
          if (aVal > bVal) return isAscending ? -1 : 1;
          return 0;
        });

        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));

        th.classList.toggle("sort-asc", !isAscending);
        th.classList.toggle("sort-desc", isAscending);
      });
    });
  });
}

function updateLangUI() {
  document.querySelectorAll(".lang-btn").forEach(btn => {
    btn.classList.toggle("lang-active", btn.dataset.lang === currentLang);
  });
}


let isAltView = false;
let originalHomeContent = null;
let currentLang = localStorage.getItem("lang") || "fr";
let q2CountRange = 50; // диапазон для Q2 по умолчанию
let q3CountRange = 50; // диапазон для Q3 по умолчанию
let q4CountRange = 50; // диапазон для Q4 по умолчанию
let astroCountRange = 50; // диапазон для Astro (3-я таблица) по умолчанию

// 📥 Загрузка страниц
function loadPage(page) {
  const toggleSwitch = document.getElementById("toggleSwitch");
  const isOrdered = toggleSwitch.checked;
  const mode = isOrdered ? "norder=1" : "";

  const menu = document.getElementById("dropdownMenu");
  menu.style.display = "none";

  const container = document.getElementById("container");
  const spinner = document.getElementById("loadingSpinner");
  const cornerButton = document.getElementById("cornerButton");

  isAltView = false;
  spinner.classList.remove("hidden");

  const neonSwitch = document.getElementById("neonSwitch");
  toggleSwitch.disabled = false;
  neonSwitch.classList.remove("disabled-switch");

  // === Параметры для разных страниц
  let extraParam = "";

  // Banco / Tout: limit зависит от переключателя Order/N'import
  if (page.includes("banco") || page.includes("tout")) {
    extraParam = "limit=" + (toggleSwitch.checked ? "200" : "50");
  }

  // Q2: добавляем count_range
  if (page.includes("q2")) {
    extraParam += (extraParam ? "&" : "") + "count_range=" + (q2CountRange || 50);
  }

  // Q3: добавляем count_range
  if (page.includes("q3")) {
    extraParam += (extraParam ? "&" : "") + "count_range=" + (q3CountRange || 50);
  }
  // Q4: добавляем count_range
  if (page.includes("q4")) {
    extraParam += (extraParam ? "&" : "") + "count_range=" + (q4CountRange || 50);
  }
  // Astro: добавляем count_range для таблицы по дням
  if (page.includes("astro") && !page.includes("Info")) {
    extraParam += (extraParam ? "&" : "") + "count_range=" + (astroCountRange || 50);
  }

  extraParam += (extraParam ? "&" : "") + "lang=" + currentLang;

  // Собираем queryString корректно
  let queryParts = [];
  if (mode) queryParts.push(mode);
  if (extraParam) queryParts.push(extraParam);
  const queryString = queryParts.join("&");

  const url = queryString ? `${page}?${queryString}` : page;

  fetch(url)
    .then(response => {
      if (!response.ok) throw new Error("Erreur de chargement de la page");
      return response.text();
    })
    .then(html => {
      container.innerHTML = html;
      container.setAttribute("data-page", page);
      updateToggleStyles();
      makeTablesSortable();

      // Q2: привязка выпадающего меню диапазона (10 / 20 / 50 / 100 / 365)
      if (page.includes("q2")) {
        const rangeSelect = container.querySelector("#q2CountRange");
        if (rangeSelect) {
          rangeSelect.value = String(q2CountRange || 50);
          rangeSelect.addEventListener("change", () => {
            q2CountRange = parseInt(rangeSelect.value, 10) || 50;
            loadPage(page);
          });
        }
      }

      // Q3: привязка выпадающего меню диапазона
      if (page.includes("q3")) {
        const rangeSelect = container.querySelector("#q3CountRange");
        if (rangeSelect) {
          rangeSelect.value = String(q3CountRange || 50);
          rangeSelect.addEventListener("change", () => {
            q3CountRange = parseInt(rangeSelect.value, 10) || 50;
            loadPage(page);
          });
        }
      }

      // Q4: привязка выпадающего меню диапазона
      if (page.includes("q4")) {
        const rangeSelect = container.querySelector("#q4CountRange");
        if (rangeSelect) {
          rangeSelect.value = String(q4CountRange || 50);
          rangeSelect.addEventListener("change", () => {
            q4CountRange = parseInt(rangeSelect.value, 10) || 50;
            loadPage(page);
          });
        }
      }

      // Astro: привязка выпадающего меню диапазона (10…365)
      if (page.includes("astro") && !page.includes("Info")) {
        const rangeSelect = container.querySelector("#astroCountRange");
        if (rangeSelect) {
          rangeSelect.value = String(astroCountRange || 50);
          rangeSelect.addEventListener("change", () => {
            astroCountRange = parseInt(rangeSelect.value, 10) || 50;
            loadPage(page);
          });
        }
      }

      const pageTitle = document.getElementById("pageTitle");

      if (page.includes("q2")) {
        const metaDiv = container.querySelector("#q2-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Quotidienne2<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;"> ${count} - Tirages depuis 19 mai 2016 </span>`;
      } 
      else if (page.includes("q3")) {
        const metaDiv = container.querySelector("#q3-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Quotidienne3<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 06 juin 1983</span>`;
      }
      else if (page.includes("q4")) {
        const metaDiv = container.querySelector("#q4-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Quotidienne4<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 06 juin 1983</span>`;
      }
      else if (page.includes("tout")) {
        const metaDiv = container.querySelector("#tout-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Tout ou Rien<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 17 novembre 2014</span>`;
      }
      else if (page.includes("banco")) {
        const metaDiv = container.querySelector("#banco-meta");
        const total = metaDiv?.dataset.count || "?";
        const shown = metaDiv?.dataset.limit || "50";
        pageTitle.innerHTML = `Banco<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${total} - Tirages affichés: ${shown} derniers</span>`;
      }
      else if (page.includes("astro")) {
        const metaDiv = container.querySelector("#astro-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Astro<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 13 janvier 2006</span>`;
      }

      cornerButton.innerHTML = "&#8505;";
    })
    .catch(err => {
      container.innerHTML = "<p>Impossible de charger la page.</p>";
      console.error(err);
    })
    .finally(() => {
      spinner.classList.add("hidden");
    });
}

// ⚙️ Стили переключателя
function updateToggleStyles() {
  const toggle = document.getElementById("toggleSwitch");
  const labelOrder = document.getElementById("labelOrder");
  const labelNimport = document.getElementById("labelNimport");
  const neonSwitch = document.getElementById("neonSwitch");
  const container = document.getElementById("container");

  const isChecked = toggle.checked;
  labelOrder.classList.toggle("active", !isChecked);
  labelNimport.classList.toggle("active", isChecked);
  neonSwitch.classList.toggle("active", isChecked);

  // ✅ Изменяем подписи Banco Tout
  const currentPage = container.getAttribute("data-page") || "";

  if (currentPage.includes("banco") || currentPage.includes("tout")) {
    labelOrder.textContent = "50 Tirages";
    labelNimport.textContent = "200 Tirages";
  } else {
    labelOrder.textContent = "Order";
    labelNimport.textContent = "N'import";
  }
}

// 🏠 На главную
function goHome() {
  const container = document.getElementById("container");
  const spinner = document.getElementById("loadingSpinner");
  const toggleSwitch = document.getElementById("toggleSwitch");
  const neonSwitch = document.getElementById("neonSwitch");
  const cornerButton = document.getElementById("cornerButton");

  spinner.classList.remove("hidden");

  setTimeout(() => {
    if (originalHomeContent) {
      container.innerHTML = originalHomeContent;
    }

    container.setAttribute("data-page", "");
    document.getElementById("pageTitle").textContent = "Page principalle";
    cornerButton.innerHTML = "&#8505;";
    toggleSwitch.disabled = false;
    neonSwitch.classList.remove("disabled-switch");
    updateToggleStyles();
    spinner.classList.add("hidden");
  }, 300);
}

// === DOMContentLoaded ===
document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("toggleSwitch");
  const cornerButton = document.getElementById("cornerButton");
  const container = document.getElementById("container");

  originalHomeContent = container.innerHTML;

  toggle.addEventListener("change", () => {
    updateToggleStyles();
    const currentPage = container.getAttribute("data-page");
    if (currentPage && !isAltView) {
      loadPage(currentPage);
    }
  });

  cornerButton.addEventListener("click", (e) => {
    e.preventDefault();
    const currentPage = container.getAttribute("data-page");
    const toggleSwitch = document.getElementById("toggleSwitch");
    const neonSwitch = document.getElementById("neonSwitch");

    if (!currentPage) return;

    isAltView = !isAltView;

    if (isAltView) {
      toggleSwitch.disabled = true;
      neonSwitch.classList.add("disabled-switch");

      if (currentPage.includes("q2")) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q2info.php?table=Q2_stats_order" style="width:100%; height:100%; border:none;"></iframe>`;
      } else if (currentPage.includes("q3")) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q3info.php?table=Q3_stats_order" style="width:100%; height:100%; border:none;"></iframe>`;
      } else if (currentPage.includes("q4")) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q4info.php?table=Q4_stats_order" style="width:100%; height:100%; border:none;"></iframe>`;
      } else if (currentPage.includes("tout")) {
        container.innerHTML = `<iframe src="toutourien/Info/toutinfo.php?limit=${toggleSwitch.checked ? 200 : 50}" style="width:100%; height:100%; border:none;"></iframe>`;
      } else if (currentPage.includes("banco")) {
        container.innerHTML = `<iframe src="banco/Info/bancoinfo2.php" style="width:100%; height:100%; border:none;"></iframe>`;
      } else if (currentPage.includes("astro")) {
        container.innerHTML = `<iframe src="astro/Info/astroinfo.php" style="width:100%; height:100%; border:none;"></iframe>`;
      }

      cornerButton.innerHTML = "&#x21c6;"; // ✅ Вернуть иконку Info
    } else {
      toggleSwitch.disabled = false;
      neonSwitch.classList.remove("disabled-switch");
      container.style.maxWidth = "";    // ✅ Сброс ширины
      container.style.width = "";       // ✅ Сброс ширины
      loadPage(currentPage);
    }
  });

  updateLangUI();
  updateToggleStyles();
});