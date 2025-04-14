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

// 🔁 Загрузка страницы внутрь контейнера
function loadPage(page) {
  const isOrdered = document.getElementById("toggleSwitch").checked;
  const mode = isOrdered ? "norder=1" : "";

  const menu = document.getElementById("dropdownMenu");
  menu.style.display = "none";

  fetch(`${page}?${mode}`)
    .then(response => {
      if (!response.ok) throw new Error("Ошибка загрузки страницы");
      return response.text();
    })
    .then(html => {
      const container = document.getElementById("container");
      container.innerHTML = html;
      container.setAttribute("data-page", page);
      updateToggleStyles();
      makeTablesSortable(); // ✅ сортировка активируется ЗДЕСЬ

      const pageTitle = document.getElementById("pageTitle");
      if (page.includes("q2")) pageTitle.textContent = "Quotidienne2";
      else if (page.includes("q3")) pageTitle.textContent = "Quotidienne3";
      else if (page.includes("q4")) pageTitle.textContent = "Quotidienne4";
      else pageTitle.textContent = "Quotidienne";
    })
    .catch(err => {
      document.getElementById("container").innerHTML = "<p>Не удалось загрузить страницу.</p>";
      console.error(err);
    });
}

// 🔁 Обновление стилей переключателя
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

// 🔁 При загрузке страницы
document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("toggleSwitch");

  toggle.addEventListener("change", () => {
    updateToggleStyles();
    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");
    if (currentPage) {
      loadPage(currentPage);
    }
  });

  updateToggleStyles();
  loadPage("q2.php"); // стартовая загрузка
});