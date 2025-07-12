// === script.js ===

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

// 🔁 Сортировка таблиц
function makeTablesSortable() {
  const tables = document.querySelectorAll(".interactive-table");

  tables.forEach(table => {
    const headers = table.querySelectorAll("th");
    headers.forEach((th, columnIndex) => {
      th.style.cursor = "pointer";
      th.addEventListener("click", () => {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const isAscending = th.classList.contains("sort-asc");

        headers.forEach(h => h.classList.remove("sort-asc", "sort-desc"));

        const sortedRows = rows.sort((a, b) => {
          const aCell = a.children[columnIndex];
          const bCell = b.children[columnIndex];
          if (!aCell || !bCell) return 0;

          const aText = aCell.innerText.trim();
          const bText = bCell.innerText.trim();

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

// 🔎 Фильтрация таблиц по типу комбинаций
function applyFilter(tableId, filterValue) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");
  let count = 0;

  rows.forEach(row => {
    const type = row.dataset.comboType;
    const show = filterValue === 'all' || filterValue === type;
    row.style.display = show ? '' : 'none';
    if (show) count++;
  });

  const countSpan = document.getElementById(
    tableId === 'statsOrderTable' ? 'statsOrderCount' :
    tableId === 'freeOrderTable' ? 'freeOrderCount' : null
  );
  if (countSpan) countSpan.textContent = count;
}

const altViewState = {
  q2: { isAlt: false },
  q3: { isAlt: false },
  q4: { isAlt: false, prev: "" }
};

function loadPage(page) {
  const isOrdered = document.getElementById("toggleSwitch").checked;
  const mode = isOrdered ? "norder=1" : "";

  const menu = document.getElementById("dropdownMenu");
  menu.style.display = "none";

  const container = document.getElementById("container");
  const spinner = document.getElementById("loadingSpinner");
  const cornerButton = document.getElementById("cornerButton");

  spinner.classList.remove("hidden");

  const toggleSwitch = document.getElementById("toggleSwitch");
  const neonSwitch = document.getElementById("neonSwitch");
  toggleSwitch.disabled = false;
  neonSwitch.classList.remove("disabled-switch");

  fetch(`${page}?${mode}`)
    .then(response => {
      if (!response.ok) throw new Error("Ошибка загрузки страницы");
      return response.text();
    })
    .then(html => {
      container.innerHTML = html;
      container.setAttribute("data-page", page);
      updateToggleStyles();
      if (!page.includes("q4info")) {
        makeTablesSortable();
      }

      const pageTitle = document.getElementById("pageTitle");

      if (page.includes("q2")) {
        const metaDiv = container.querySelector("#q2-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Quotidienne2<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;"> ${count} - Tirages depuis 19 mai 2016 </span>`;
      } else if (page.includes("q3")) {
        const metaDiv = container.querySelector("#q3-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Quotidienne3<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 06 juin 1983</span>`;
      } else if (page.includes("q4")) {
        const metaDiv = container.querySelector("#q4-meta");
        const count = metaDiv?.dataset.count || "?";
        const jamais = metaDiv?.dataset.jamais || "?";
        pageTitle.innerHTML = `Quotidienne4<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 06 juin 1983</span>`;
      }

      if (page.includes("q4info")) {
        applyFilter("statsOrderTable", "all");
        applyFilter("freeOrderTable", "all");

        const selects = container.querySelectorAll("select");
        selects.forEach(select => {
          select.addEventListener("change", function () {
            const tableId = this.closest("table").id;
            applyFilter(tableId, this.value);
          });
        });
      }

      document.getElementById("cornerButton").innerHTML = "&#8505;";
    })
    .catch(err => {
      container.innerHTML = "<p>Не удалось загрузить страницу.</p>";
      console.error(err);
    })
    .finally(() => {
      spinner.classList.add("hidden");
    });
}

function updateToggleStyles() {
  const toggle = document.getElementById("toggleSwitch");
  const labelOrder = document.getElementById("labelOrder");
  const labelNimport = document.getElementById("labelNimport");
  const neonSwitch = document.getElementById("neonSwitch");

  const isChecked = toggle.checked;
  labelOrder.classList.toggle("active", !isChecked);
  labelNimport.classList.toggle("active", isChecked);
  neonSwitch.classList.toggle("active", isChecked);
}

function goHome() {
  const container = document.getElementById("container");
  const spinner = document.getElementById("loadingSpinner");
  const toggleSwitch = document.getElementById("toggleSwitch");
  const neonSwitch = document.getElementById("neonSwitch");
  const cornerButton = document.getElementById("cornerButton");

  spinner.classList.remove("hidden");
  setTimeout(() => {
    container.innerHTML = `<div class="welcome-placeholder"><h2>Добро пожаловать в Quotidienne</h2><p>Пожалуйста, выберите страницу из меню слева.</p></div>`;
    container.setAttribute("data-page", "");
    document.getElementById("pageTitle").textContent = "Main";
    cornerButton.innerHTML = "&#8505;";
    toggleSwitch.disabled = false;
    neonSwitch.classList.remove("disabled-switch");
    updateToggleStyles();
    spinner.classList.add("hidden");
  }, 300);
}

document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("toggleSwitch");
  const cornerButton = document.getElementById("cornerButton");

  toggle.addEventListener("change", () => {
    updateToggleStyles();
    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");
    if (currentPage && !altViewState.q4.isAlt) {
      loadPage(currentPage);
    }
  });

  cornerButton.addEventListener("click", (e) => {
    e.preventDefault();

    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");
    const toggleSwitch = document.getElementById("toggleSwitch");
    const neonSwitch = document.getElementById("neonSwitch");
    const cornerButton = document.getElementById("cornerButton");

    if (!currentPage) return;

    if (currentPage.includes("q2")) {
      const state = altViewState.q2;
      state.isAlt = !state.isAlt;
      toggleSwitch.disabled = state.isAlt;
      neonSwitch.classList.toggle("disabled-switch", state.isAlt);

      if (state.isAlt) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q2info.php?table=Q2_stats_order" style="width:100%; height:85vh; border:none;"></iframe>`;
        cornerButton.innerHTML = "&#x21c6;";
      } else {
        loadPage("quotidienne/q2.php");
        cornerButton.innerHTML = "&#8505;";
      }
      return;
    }

    if (currentPage.includes("q3")) {
      const state = altViewState.q3;
      state.isAlt = !state.isAlt;
      toggleSwitch.disabled = state.isAlt;
      neonSwitch.classList.toggle("disabled-switch", state.isAlt);

      if (state.isAlt) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q3info.php?table=Q3_stats_order" style="width:100%; height:85vh; border:none;"></iframe>`;
        cornerButton.innerHTML = "&#x21c6;";
      } else {
        loadPage("quotidienne/q3.php");
        cornerButton.innerHTML = "&#8505;";
      }
      return;
    }

    if (currentPage.includes("q4info")) {
      const prev = altViewState.q4.prev;
      if (prev) {
        loadPage(prev);
        container.setAttribute("data-page", prev);
        cornerButton.innerHTML = "&#8505;";
        altViewState.q4.prev = "";
        altViewState.q4.isAlt = false;
      }
      return;
    }

    if (currentPage.includes("q4")) {
      altViewState.q4.prev = currentPage;
      altViewState.q4.isAlt = true;

      toggleSwitch.disabled = true;
      neonSwitch.classList.add("disabled-switch");
      loadPage("quotidienne/QInfo/q4info.php");
      container.setAttribute("data-page", "q4info");
      cornerButton.innerHTML = "&#x21c6;";
      return;
    }
  });

  updateToggleStyles();
});