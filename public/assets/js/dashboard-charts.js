// public/assets/js/dashboard-charts.js

/**
 * This function specifically finds and initializes the expense chart on the dashboard.
 */
function setupExpenseChart() {
    // Find the canvas element on the page.
    const chartCanvas = document.getElementById('expenseChart');
    
    // If the canvas doesn't exist on this page, stop right away to prevent errors.
    if (!chartCanvas) {
        return;
    }

    // This is the clever part: we get the data that was embedded in the canvas's data attribute.
    const dataString = chartCanvas.dataset.chartData;
    if (!dataString) {
        console.error('Chart data not found on canvas element.');
        return;
    }

    // The data is a JSON string, so we need to parse it back into a JavaScript object.
    const chartData = JSON.parse(dataString);

    // Now we create the chart, just like before, using the data we retrieved.
    new Chart(chartCanvas, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Daily Spending',
                data: chartData.data,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                tension: 0.3,
                pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(59, 130, 246, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#9ca3af',
                        callback: function(value) { return '£' + value; }
                    },
                    grid: { color: '#374151' }
                },
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(context.parsed.y);
                        }
                    }
                }
            }
        }
    });
}

// Run our setup function when the page is ready.
document.addEventListener('DOMContentLoaded', setupExpenseChart);
