const chartConfig = {
    padding: { top: 20, right: 150, bottom: 50, left: 50 },
    legend: { width: 150, itemHeight: 20, spacing: 10 },
    colors: { temperature: "#FFA500", humidity: "blue", axes: "#000" },
    font: { family: "Arial", size: 12, color: "#000" },
    tooltip: { width: 160, height: 50, backgroundColor: "rgba(255,255,255,0.9)" },
    maxLabels: 30, // Numero massimo di etichette per asse X
};

export function drawChart(canvasId, xData, yData) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.error("Canvas element not found:", canvasId);
        return;
    }

    // Configura il canvas
    const containerWidth = canvas.parentElement.offsetWidth;
    const containerHeight = 400;
    canvas.width = containerWidth;
    canvas.height = containerHeight;

    const ctx = canvas.getContext("2d");
    if (!ctx) {
        console.error("Unable to get canvas context");
        return;
    }

    // **Cancella completamente il canvas**
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Configura dimensioni del grafico
    const chartWidth = canvas.width - chartConfig.padding.left - chartConfig.padding.right;
    const chartHeight = canvas.height - chartConfig.padding.top - chartConfig.padding.bottom;
    const startX = chartConfig.padding.left;
    const startY = chartConfig.padding.top;

    const maxTemperature = Math.max(...yData[0].data);
    const maxHumidity = Math.max(...yData[1].data);
    const maxValue = Math.max(maxTemperature, maxHumidity);

    const xStep = chartWidth / (xData.length - 1);
    const yStep = chartHeight / maxValue;

    // Disegna il grafico una sola volta e memorizzalo
    function renderBaseChart() {
        // Disegna assi
        ctx.beginPath();
        ctx.moveTo(startX, startY);
        ctx.lineTo(startX, startY + chartHeight);
        ctx.lineTo(startX + chartWidth, startY + chartHeight);
        ctx.strokeStyle = chartConfig.colors.axes;
        ctx.stroke();

        // Disegna linea della temperatura
        ctx.beginPath();
        ctx.strokeStyle = chartConfig.colors.temperature;
        ctx.lineWidth = 2; // Spessore linea temperatura
        for (let i = 0; i < yData[0].data.length; i++) {
            const x = startX + i * xStep;
            const y = startY + chartHeight - yData[0].data[i] * yStep;
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.stroke();

        // Disegna linea dell'umidità
        ctx.beginPath();
        ctx.strokeStyle = chartConfig.colors.humidity;
        ctx.lineWidth = 2; // Spessore linea umidità
        for (let i = 0; i < yData[1].data.length; i++) {
            const x = startX + i * xStep;
            const y = startY + chartHeight - yData[1].data[i] * yStep;
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.stroke();

        // Etichette degli assi
        ctx.font = `${chartConfig.font.size}px ${chartConfig.font.family}`;
        ctx.fillStyle = chartConfig.font.color;

        // Etichette asse X
        const maxLabels = Math.max(5, Math.floor(chartWidth / 100)); // Determina dinamicamente il numero massimo di etichette
        const labelInterval = Math.ceil(xData.length / maxLabels);

        for (let i = 0; i < xData.length; i++) {
            if (i % labelInterval === 0 || i === xData.length - 1) {
                const x = startX + i * xStep;
                const y = startY + chartHeight + 20;
                ctx.fillText(xData[i], x - 15, y);
            }
        }

        // Etichette asse Y
        for (let i = 0; i <= maxValue; i += Math.round(maxValue / 5)) {
            const x = startX - 40;
            const y = startY + chartHeight - i * yStep;
            ctx.fillText(i, x, y + 5);
        }

        // Legenda
        ctx.fillStyle = chartConfig.colors.temperature;
        ctx.fillRect(canvas.width - chartConfig.legend.width + 10, chartConfig.padding.top, 10, 10);
        ctx.fillStyle = chartConfig.font.color;
        ctx.fillText("Temperature [°C]", canvas.width - chartConfig.legend.width + 30, chartConfig.padding.top + 10);

        ctx.fillStyle = chartConfig.colors.humidity;
        ctx.fillRect(canvas.width - chartConfig.legend.width + 10, chartConfig.padding.top + chartConfig.legend.itemHeight, 10, 10);
        ctx.fillStyle = chartConfig.font.color;
        ctx.fillText("Humidity [%]", canvas.width - chartConfig.legend.width + 30, chartConfig.padding.top + chartConfig.legend.itemHeight + 10);
    }

    renderBaseChart();

    // Salva lo stato iniziale del grafico
    const baseChart = canvas.toDataURL();

    // Interazione mouseover
    canvas.addEventListener("mousemove", (event) => {
        const rect = canvas.getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        // Cancella il canvas e ridisegna il grafico di base
        const img = new Image();
        img.src = baseChart;
        img.onload = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);

            // Trova l'indice più vicino al mouse
            const index = Math.round((mouseX - startX) / xStep);
            if (index >= 0 && index < xData.length) {
                const temp = yData[0].data[index];
                const hum = yData[1].data[index];

                // Disegna punto
                const pointX = startX + index * xStep;
                const pointYTemp = startY + chartHeight - temp * yStep;
                const pointYHum = startY + chartHeight - hum * yStep;

                ctx.beginPath();
                ctx.arc(pointX, pointYTemp, 5, 0, 2 * Math.PI);
                ctx.fillStyle = chartConfig.colors.temperature;
                ctx.fill();

                ctx.beginPath();
                ctx.arc(pointX, pointYHum, 5, 0, 2 * Math.PI);
                ctx.fillStyle = chartConfig.colors.humidity;
                ctx.fill();

                // Disegna tooltip
                const tooltipX = Math.min(mouseX + 10, canvas.width - chartConfig.tooltip.width - 10);
                const tooltipY = Math.max(mouseY - 10 - chartConfig.tooltip.height, 10);

                ctx.fillStyle = chartConfig.tooltip.backgroundColor;
                ctx.fillRect(tooltipX, tooltipY, chartConfig.tooltip.width, chartConfig.tooltip.height);
                ctx.strokeStyle = chartConfig.colors.axes;
                ctx.strokeRect(tooltipX, tooltipY, chartConfig.tooltip.width, chartConfig.tooltip.height);

                ctx.fillStyle = chartConfig.font.color;
                ctx.fillText(`Time: ${xData[index]}`, tooltipX + 10, tooltipY + 15);
                ctx.fillText(`Temp: ${temp.toFixed(1)}°C, Hum: ${hum.toFixed(1)}%`, tooltipX + 10, tooltipY + 30);
            }
        };
    });
}
