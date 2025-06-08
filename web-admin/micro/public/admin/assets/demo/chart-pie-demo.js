var ctx = document.getElementById("myPieChart");
var myPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["Direct", "Referral", "Social"],
        datasets: [{
            data: [55, 30, 15],
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
        }],
    },
    options: {
        maintainAspectRatio: false,
        legend: {
            position: 'right'
        },
        cutoutPercentage: 60
    }
});
