// Переключение отображения меню
function toggleMenu() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

document.addEventListener('click', function (e) {
  const menu = document.getElementById("dropdownMenu");
  const icon = document.querySelector('.menu-icon');
  if (!menu.contains(e.target) && !icon.contains(e.target)) {
    menu.style.display = "none";
  }
});

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

function tryUpdateDaysGraph(norder, retries = 10) {
  const frame = document.getElementById("daysFrame");
  if (!frame || !frame.contentWindow) return;

  if (typeof frame.contentWindow.loadDaysGraph === "function") {
    frame.contentWindow.loadDaysGraph(norder);
  } else if (retries > 0) {
    setTimeout(() => tryUpdateDaysGraph(norder, retries - 1), 200);
  }
}

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
        pageTitle.textContent = "Quotidienne2";
      } else if (page.includes("q3")) {
        pageTitle.textContent = "Quotidienne3";
      } else if (page.includes("q4")) {
        pageTitle.textContent = "Quotidienne4";
      } else {
        pageTitle.textContent = "Quotidienne";
      }

      cornerButton.textContent = "Информация";
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

document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("toggleSwitch");
  const cornerButton = document.getElementById("cornerButton");

  toggle.addEventListener("change", () => {
    updateToggleStyles();
    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");

    if (!currentPage) return;

    if (!isAltView) {
      loadPage(currentPage);
    } else {
      const norder = toggle.checked ? '1' : '0';
      tryUpdateDaysGraph(norder);
    }
  });

  cornerButton.addEventListener("click", (e) => {
    e.preventDefault();
    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");

    if (!currentPage) return;

    isAltView = !isAltView;

    if (isAltView) {
      if (currentPage.includes("q2")) {
        const isNorder = document.getElementById("toggleSwitch").checked ? 1 : 0;
        container.innerHTML = `
          <iframe src="days.php?norder=${isNorder}&limit=100" style="width:100%; height:85vh; border:none;" id="daysFrame"></iframe>
        `;
        cornerButton.textContent = "Таблицы";
      } 
      else if (currentPage.includes("q3")) {
        container.innerHTML = `
          <div class="info-placeholder">
            <h2>Информация по Q3</h2>
            <p>Заглушка. График будет позже.</p>
          </div>
        `;
        cornerButton.textContent = "Таблицы";

      } else if (currentPage.includes("q4")) {
        container.innerHTML = `
          <div class="info-placeholder">
            <h2>Информация по Q4</h2>
            <p>Заглушка. График будет позже.</p>
          </div>
        `;
        cornerButton.textContent = "Таблицы";
      }

    } else {
      loadPage(currentPage);
    }
  });

  updateToggleStyles();
  loadPage("q2.php");
});