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

function loadPage(page) {
  const isOrdered = document.getElementById("toggleSwitch").checked;
  const mode = isOrdered ? "order" : "nimport";
  document.getElementById("contentFrame").src = `${page}?mode=${mode}`;
}

document.getElementById("toggleSwitch").addEventListener("change", function () {
  const switchEl = document.getElementById("neonSwitch");
  switchEl.classList.toggle("active", this.checked);
  loadPage(document.getElementById("contentFrame").src.split("?")[0]);
});
