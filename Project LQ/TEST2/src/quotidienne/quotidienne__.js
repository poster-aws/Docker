// === quotidienne.js ===
// Специфичная логика для страниц Q2/Q3/Q4

document.addEventListener("DOMContentLoaded", () => {
  // 📌 Инициализация при загрузке
  applyRowHighlights();
  applyZeroFoisHighlight();
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

// 🟢 Подсветка комбинаций с Fois = 0
function applyZeroFoisHighlight() {
  const comboTable = document.querySelector(".combo-table-container table");
  if (!comboTable) return;

  const rows = comboTable.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const cells = row.querySelectorAll("td");
    const foisCell = cells[cells.length - 1]; // обычно последний столбец

    if (foisCell && foisCell.textContent.trim() === "0") {
      row.classList.add("zero-fois");

      // Опционально: сделать кружочки внутри зелёными
      cells.forEach(cell => {
        const span = cell.querySelector(".circle");
        if (span) {
          span.style.backgroundColor = "#a0ffc8";
          span.style.color = "#000";
        }
      });
    }
  });
}