// script.js

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

let isAltView = false;
let originalHomeContent = null;

// 📥 Загрузка страниц
function loadPage(page) {
  const isOrdered = document.getElementById("toggleSwitch").checked;
  const mode = isOrdered ? "norder=1" : "";

  const menu = document.getElementById("dropdownMenu");
  menu.style.display = "none";

  const container = document.getElementById("container");
  const spinner = document.getElementById("loadingSpinner");
  const cornerButton = document.getElementById("cornerButton");

  isAltView = false;
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
      makeTablesSortable();

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
        const jamais = metaDiv?.dataset.jamais || "?";
        pageTitle.innerHTML = `Quotidienne4<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis 06 juin 1983</span>`;
      }
      else if (page.includes("tout")) {
        const metaDiv = container.querySelector("#tout-meta");
        const count = metaDiv?.dataset.count || "?";
        pageTitle.innerHTML = `Tout ou Rien<br><span id="subTitle" style="display:block; font-size: 0.5em; font-weight: normal; line-height: 1.1;">${count} - Tirages depuis ??</span>`;
      }

      cornerButton.innerHTML = "&#8505;";
    })
    .catch(err => {
      container.innerHTML = "<p>Не удалось загрузить страницу.</p>";
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

  const isChecked = toggle.checked;
  labelOrder.classList.toggle("active", !isChecked);
  labelNimport.classList.toggle("active", isChecked);
  neonSwitch.classList.toggle("active", isChecked);
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
        container.innerHTML = `<iframe src="quotidienne/QInfo/q2info.php?table=Q2_stats_order" style="width:100%; height:85vh; border:none;"></iframe>`;
      } else if (currentPage.includes("q3")) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q3info.php?table=Q3_stats_order" style="width:100%; height:85vh; border:none;"></iframe>`;
      } else if (currentPage.includes("q4")) {
        container.innerHTML = `<iframe src="quotidienne/QInfo/q4info.php?table=Q4_stats_order" style="width:100%; height:85vh; border:none;"></iframe>`;
      } else if (currentPage.includes("tout")) {
        container.innerHTML = `<iframe src="toutourien/tout.php?limit=100" style="width:100%; height:85vh; border:none;"></iframe>`;
}

      cornerButton.innerHTML = "&#x21c6;";
    } else {
      toggleSwitch.disabled = false;
      neonSwitch.classList.remove("disabled-switch");
      loadPage(currentPage);
    }
  });

  updateToggleStyles();
});