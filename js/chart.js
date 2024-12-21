function drawChart(canvasId, xData, yData) {
    const canvas = document.getElementById(canvasId);
    if (!canvas.getContext) {
        console.error('Canvas non supportato!');
        return;
    }

    const ctx = canvas.getContext('2d');

    // Pulizia del canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Dimensioni e margini
    const width = canvas.width;
    const height = canvas.height;
    const margin = 50;

    // Definizione dell'area di disegno
    const chartWidth = width - 2 * margin;
    const chartHeight = height - 2 * margin;

    // Dati di esempio
    const temperatures = yData[0].data; // Temperature
    const humidity = yData[1].data; // Humidity
    const labels = xData; // Date labels

    // Calcolo dei valori massimi
    const maxTemp = Math.max(...temperatures);
    const maxHumidity = Math.max(...humidity);
    const maxY = Math.max(maxTemp, maxHumidity);

    // Disegna l'asse X e Y
    ctx.beginPath();
    ctx.moveTo(margin, margin);
    ctx.lineTo(margin, height - margin); // Y-axis
    ctx.lineTo(width - margin, height - margin); // X-axis
    ctx.stroke();

    // Disegna i valori degli assi Y
    for (let i = 0; i <= 5; i++) {
        const y = height - margin - (i * chartHeight) / 5;
        const value = ((maxY / 5) * i).toFixed(1);

        ctx.fillText(value, margin - 30, y + 5); // Etichetta asse Y
        ctx.beginPath();
        ctx.moveTo(margin - 5, y);
        ctx.lineTo(margin, y);
        ctx.stroke();
    }

    // Disegna i valori degli assi X
    labels.forEach((label, index) => {
        const x = margin + (index * chartWidth) / (labels.length - 1);
        ctx.fillText(label, x - 15, height - margin + 20); // Etichetta asse X
    });

    // Disegna la linea della temperatura
    ctx.beginPath();
    ctx.strokeStyle = 'red';
    temperatures.forEach((temp, index) => {
        const x = margin + (index * chartWidth) / (labels.length - 1);
        const y = height - margin - (temp / maxY) * chartHeight;

        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    ctx.stroke();

    // Disegna la linea dell'umidità
    ctx.beginPath();
    ctx.strokeStyle = 'blue';
    humidity.forEach((hum, index) => {
        const x = margin + (index * chartWidth) / (labels.length - 1);
        const y = height - margin - (hum / maxY) * chartHeight;

        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    ctx.stroke();

    // Aggiungi una legenda
    ctx.fillStyle = 'red';
    ctx.fillRect(width - 150, margin, 10, 10);
    ctx.fillStyle = 'black';
    ctx.fillText('Temperature [°C]', width - 130, margin + 10);

    ctx.fillStyle = 'blue';
    ctx.fillRect(width - 150, margin + 20, 10, 10);
    ctx.fillStyle = 'black';
    ctx.fillText('Humidity [%]', width - 130, margin + 30);
}
