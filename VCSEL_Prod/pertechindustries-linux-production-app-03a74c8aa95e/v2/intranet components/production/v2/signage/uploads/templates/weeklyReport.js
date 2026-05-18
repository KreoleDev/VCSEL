(async () => {

  const chartData = await window.axios.post(window.analyticsServer,
  {
      query: {
        dataset: 'reporting',
        action: 'getWeeklyReport',
        params: {

        }
      }
    },
    { headers: { 'Content-Type': 'application/json' } }
  )

  let productInfo = Object.entries(chartData.data.payload)

  const ctx = document.getElementById('weeklyChart').getContext('2d');
  const ctxToday = document.getElementById('todayChart').getContext('2d');

  for(let i = 0; i < productInfo.length; i++) {
    document.getElementById('weeklyHeading').innerHTML = productInfo[i][0] + ' Units Packed';
    window.gsap.to("#weeklyHeading", {opacity: 1, duration: 1})
    window.gsap.to("#todayHeading", {opacity: 1, duration: 1})
    window.gsap.to("#weeklyChart", {opacity: 1, duration: 0.2})

    // Weekly Chart
    if(productInfo[i][1].week) {
      let myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            datasets: [{
                data: [productInfo[i][1].week.Monday, productInfo[i][1].week.Tuesday, productInfo[i][1].week.Wednesday, productInfo[i][1].week.Thursday, productInfo[i][1].week.Friday, productInfo[i][1].week.Saturday, productInfo[i][1].week.Sunday],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.2)',
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                ],
                borderWidth: 3
            }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }

    // Today Chart
    if(productInfo[i][1].today) {
      let testInfo = Object.entries(productInfo[i][1].today);
      const todayDataset = [];
      for(let j = 0; j < testInfo.length; j++) {
        todayDataset.push({
          label: testInfo[j][0],
          data: [testInfo[j][1]['6AM'], testInfo[j][1]['7AM'], testInfo[j][1]['8AM'], testInfo[j][1]['9AM'], testInfo[j][1]['10AM'], testInfo[j][1]['11AM'], testInfo[j][1]['12PM'], testInfo[j][1]['1PM'], testInfo[j][1]['2PM'], testInfo[j][1]['3PM'], testInfo[j][1]['4PM'], testInfo[j][1]['5PM'], testInfo[j][1]['6PM']],
          borderColor: [
              'rgba('+(Math.floor(Math.random() * 140) + 80)+', '+(Math.floor(Math.random() * 140) + 80)+', '+(Math.floor(Math.random() * 140) + 80)+', 1)'
          ],
          borderWidth: 3
        });
      }

      const todayChart = new Chart(ctxToday, {
        type: 'line',
        data: {
            labels: ['6AM', '7AM', '8AM', '9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM'],
            datasets: todayDataset
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          },
        }
    });
  }


    // delay for 15 seconds
    await new Promise(resolve => setTimeout(resolve, 10000))
    window.gsap.to("#weeklyChart", {opacity: 0, duration: 1})
    window.gsap.to("#todayChart", {opacity: 0, duration: 1})
    window.gsap.to("#weeklyHeading", {opacity: 0, duration: 1})
    window.gsap.to("#todayHeading", {opacity: 0, duration: 1})
    await new Promise(resolve => setTimeout(resolve, 1000))
  }

  loadSlide();
})();