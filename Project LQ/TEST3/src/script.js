
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('nav ul li a');
    const content = document.getElementById('content');
    const menuToggle = document.getElementById('menu-toggle');
    const menu = document.getElementById('menu');

    menuToggle.addEventListener('click', () => {
        menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
    });

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            fetch(`pages/${this.dataset.page}`)
                .then(res => res.ok ? res.text() : Promise.reject('Ошибка загрузки страницы'))
                .then(html => {
                    content.innerHTML = html;
                    menu.style.display = 'none';
                })
                .catch(err => content.innerHTML = `<p style="color:red">${err}</p>`);
        });
    });
});
