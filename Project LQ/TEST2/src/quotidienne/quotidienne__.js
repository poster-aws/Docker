// === quotidienne.js ===
// Специфичная логика для страниц Q2/Q3/Q4

document.addEventListener("DOMContentLoaded", () => {
  applyRowHighlights();
  applyFoisCircleColors(); // ✅ только для первой таблицы
});

// 🔦 Подсветка строк с уникальными числами (пример для Q3/Q4)
function applyRowHighlights() {
  const table = document.querySelector(".table-container table");
  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const cells = Array.from(row.querySelectorAll("td"));
    const numbers = cells.slice(1, -3).map(td => td.textContent.trim());

    // Подсвечивать, если все числа разные
    if (new Set(numbers).size === numbers.length) {
      row.classList.add("highlight-row");
    }
  });
}

// 🎨 Подсветка .circle в первой таблице по Fois = 1 или 2
function applyFoisCircleColors() {
  const table = document.querySelector(".table-container table");
  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const cells = row.querySelectorAll("td");
    const foisCell = cells[cells.length - 2]; // Предпоследняя ячейка — Fois
    const fois = foisCell?.textContent.trim();

    cells.forEach(cell => {
      const circle = cell.querySelector(".circle");
      if (!circle) return;

      if (fois === "1") {
        circle.style.backgroundColor = "#ffe08a"; // 🟡 жёлтый
        circle.style.color = "#000";
      } else if (fois === "2") {
        circle.style.backgroundColor = "#f79ba5"; // 🔴 розовый
        circle.style.color = "#000";
      }
    });
  });
}