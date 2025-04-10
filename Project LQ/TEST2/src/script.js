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

// Загрузка страницы внутрь #container
function loadPage(page) {
  const isOrdered = document.getElementById("toggleSwitch").checked;
  const mode = isOrdered ? "norder=1" : "";

  // Спрячем меню
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = "none";

  // Загружаем контент
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

      // 🆕 Меняем заголовок
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

// Обновление стилей переключателя (подсветка активной надписи и т.д.)
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

// Обработка изменения переключателя
document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("toggleSwitch");
  toggle.addEventListener("change", () => {
    updateToggleStyles();
    const container = document.getElementById("container");
    const currentPage = container.getAttribute("data-page");
    if (currentPage) {
      loadPage(currentPage); // перезагрузить текущую страницу с новым режимом
    }
  });

  updateToggleStyles(); // начальная инициализация
    // Автозагрузка q2.php при первом запуске
    loadPage("q2.php");
});