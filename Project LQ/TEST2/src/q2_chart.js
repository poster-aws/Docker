
function renderQ2Chart(isNorder = false, limit = 100) {
    const endpoint = `q2_chart_data.php?norder=${isNorder ? 1 : 0}&limit=${limit}`;
  
    console.log("📡 Загружаем данные с:", endpoint);
  
    fetch(endpoint)
      .then(response => {
        if (!response.ok) throw new Error("Ошибка сети");
        return response.json();
      })
      .then(data => {
        console.log("📊 Получено точек:", data.length);
  
        const chartZone = document.getElementById("chartZone");
        chartZone.innerHTML = `
          <div style="width: 70vw; max-height: 70vh; background: rgba(255,255,255,0.8); padding: 1em; border-radius: 10px;">
            <canvas id="q2Chart" style="width: 100%; height: 60vh;"></canvas>
            <div style="margin-top: 1em; text-align: center;">
              <label for="daysFilter">Показать: </label>
              <select id="daysFilter">
                <option value="100" ${limit === 100 ? 'selected' : ''}>100</option>
                <option value="200" ${limit === 200 ? 'selected' : ''}>200</option>
                <option value="300" ${limit === 300 ? 'selected' : ''}>300</option>
                <option value="500" ${limit === 500 ? 'selected' : ''}>500</option>
                <option value="1000" ${limit === 1000 ? 'selected' : ''}>1000</option>
                <option value="9999" ${limit === 99999 ? 'selected' : ''}>Все</option>
              </select>
            </div>
          </div>
        `;
  
        const ctx = document.getElementById("q2Chart").getContext("2d");
  
        new Chart(ctx, {
          type: 'scatter',
          data: {
            datasets: [{
              label: 'Q2 Combinations',
              data: data.map(point => ({
                x: point.days,
                y: Number(point.combo)
              })),
              backgroundColor: 'rgba(0, 42, 255, 0.6)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                title: { display: true, text: 'Jours passés' }
              },
              y: {
                title: { display: true, text: 'Combinaison' },
                ticks: {
                  callback: value => ('0' + value).slice(-2)
                }
              }
            },
            plugins: {
              legend: { display: false }
            }
          }
        });
  
        // динамическая смена диапазона
        document.getElementById("daysFilter").addEventListener("change", (e) => {
          const newLimit = parseInt(e.target.value, 10);
          renderQ2Chart(isNorder, newLimit);
        });
      })
      .catch(error => {
        console.error("❌ Ошибка при построении графика:", error);
        document.getElementById("chartZone").innerHTML = "<p style='color:red'>Ошибка загрузки графика</p>";
      });
  }
  