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
          return isAscending ? bVal - aVal : aVal - bVal;
        });
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
        th.classList.toggle("sort-asc", !isAscending);
        th.classList.toggle("sort-desc", isAscending);
      });
    });
  });
}

function loadPage(page) {
  const isOrdered = document.getElementById("toggleSwitch").checked;
  const mode = isOrdered ? "norder=1" : "";

  // ✅ Показываем контейнер, прячем график
  document.getElementById("container").style.display = "block";
  document.getElementById("chartZone").innerHTML = "";
  document.getElementById("chartZone").style.display = "none";

  const menu = document.getElementById("dropdownMenu");
  const container = document.getElementById("container");
  const spinner = document.getElementById("loadingSpinner");

  menu.style.display = "none";
  spinner.classList.remove("hidden");

  fetch(`${page}?${mode}`)
    .then(res => {
      if (!res.ok) throw new Error("Ошибка загрузки страницы");
      return res.text();
    })
    .then(html => {
      container.innerHTML = html;
      container.setAttribute("data-page", page);
      updateToggleStyles();
      makeTablesSortable();
      if (page.includes("q2")) document.getElementById("pageTitle").textContent = "Quotidienne2";
      else if (page.includes("q3")) document.getElementById("pageTitle").textContent = "Quotidienne3";
      else if (page.includes("q4")) document.getElementById("pageTitle").textContent = "Quotidienne4";
    })
    .catch(err => {
      container.innerHTML = "<p>Ошибка загрузки страницы</p>";
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

  toggle.addEventListener("change", () => {
    updateToggleStyles();
    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");
    if (currentPage) loadPage(currentPage);
  });

  document.getElementById("cornerButton").addEventListener("click", (e) => {
    const container = document.getElementById("container");
    const page = container.getAttribute("data-page");
    if (page && page.includes("q2")) {
      e.preventDefault();
      renderQ2Chart(!document.getElementById("toggleSwitch").checked, 100);
    }
  });

  document.getElementById("toggleViewButton").addEventListener("click", () => {
    const chartZone = document.getElementById("chartZone");
    const container = document.getElementById("container");
    const isGraphVisible = chartZone.style.display !== "none";
    const currentPage = container.getAttribute("data-page");

    if (isGraphVisible) {
      chartZone.style.display = "none";
      container.style.display = "block";
      document.getElementById("toggleViewButton").textContent = "Показать график";
    } else {
      container.style.display = "none";
      chartZone.style.display = "flex";
      document.getElementById("toggleViewButton").textContent = "Показать таблицу";

      if (currentPage.includes("q2")) {
        renderQ2Chart(!document.getElementById("toggleSwitch").checked, 100);
      } else {
        chartZone.innerHTML = "<p style='color: gray; background: rgba(255,255,255,0.6); padding: 1em; border-radius: 10px;'>График будет доступен позже для этой страницы.</p>";
      }
    }
  });

  updateToggleStyles();
  loadPage("q2.php");
});