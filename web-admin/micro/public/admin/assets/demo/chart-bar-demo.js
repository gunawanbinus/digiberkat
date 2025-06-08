var ctx = document.getElementById("myBarChart");
var myBarChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ["January", "February", "March", "April"],
        datasets: [{
            label: "Revenue",
            backgroundColor: "#4e73df",
            borderColor: "#4e73df",
            data: [4215, 5312, 6251, 7841],
        }],
    },
    options: {
        maintainAspectRatio: false,
        legend: { display: false }
    }
});
