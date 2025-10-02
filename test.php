<!DOCTYPE html>
<html>
<head>
    <title>Infographic Donut Chart (PHP Customizable)</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .chart-container {
            width: 700px; /* กำหนดความกว้างของกราฟ */
            height: 700px; /* กำหนดความสูงของกราฟ */
            margin: 50px auto;
            position: relative; /* สำคัญสำหรับการจัดองค์ประกอบกราฟ */
        }
    </style>
</head>
<body>

<?php
// =================================================================
// 💰 ส่วนที่ 1: กำหนดข้อมูลใน PHP (CUSTOMIZABLE DATA)
// =================================================================

// 1. กำหนดข้อมูลหลัก (ตัวเลขควรมีผลรวมเป็น 100 หากต้องการให้เป็นเปอร์เซ็นต์รวม)
// ข้อมูลในภาพตัวอย่าง: 30, 18, 7, 14, 22, 9  (รวมกันได้ 100)
$chart_data = [
    ['label' => 'ส่วนที่ 01: การสื่อสาร', 'value' => 30, 'color' => '#f26522'], // สีส้ม
    ['label' => 'ส่วนที่ 02: อุปกรณ์พกพา', 'value' => 18, 'color' => '#36a2eb'], // สีฟ้า
    ['label' => 'ส่วนที่ 03: แท็บเล็ต', 'value' => 7,  'color' => '#14c1c7'], // สีเขียวมิ้นต์
    ['label' => 'ส่วนที่ 04: อีเมล', 'value' => 14, 'color' => '#4bc0c0'], // สีเขียว
    ['label' => 'ส่วนที่ 05: โซเชียลมีเดีย', 'value' => 22, 'color' => '#364756'], // สีเทาเข้ม
    ['label' => 'ส่วนที่ 06: แล็ปท็อป', 'value' => 9,  'color' => '#5c7a95'], // สีน้ำเงินเทา
];

// 2. แยกข้อมูลออกเป็น Array สำหรับ Chart.js
$labels = [];
$values = [];
$colors = [];
$total_value = 0;

foreach ($chart_data as $item) {
    $labels[] = $item['label'];
    $values[] = $item['value'];
    $colors[] = $item['color'];
    $total_value += $item['value'];
}

// 3. แปลง PHP Array ให้เป็น JSON String
$json_labels = json_encode($labels);
$json_values = json_encode($values, JSON_NUMERIC_CHECK);
$json_colors = json_encode($colors);

// ข้อความตรงกลาง (คุณสามารถเพิ่มส่วนนี้เพื่อแสดงค่ารวมหรือข้อความสำคัญ)
$center_text = "TOTAL: $total_value%";
?>

    <div class="chart-container">
        <canvas id="infographicDoughnutChart"></canvas>
        
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; font-size: 1.2em; font-weight: bold; color: #555;">
            <p style="margin: 0; line-height: 1.2;"><?php echo $center_text; ?></p>
        </div>
    </div>

    <script>
        // =================================================================
        // ส่วนที่ 2: การทำงานของ JavaScript (Chart.js) เพื่อวาดกราฟ
        // =================================================================

        // 4. รับข้อมูล JSON จาก PHP มาเก็บในตัวแปร JavaScript
        const phpLabels = <?php echo $json_labels; ?>;
        const phpData = <?php echo $json_values; ?>;
        const phpColors = <?php echo $json_colors; ?>;
        
        // 5. สร้างตัวแปร Config สำหรับ Chart.js
        const config = {
            type: 'doughnut', // ใช้ Doughnut Chart
            data: {
                labels: phpLabels,
                datasets: [{
                    data: phpData,
                    backgroundColor: phpColors,
                    borderColor: 'white', // เส้นขอบสีขาวเหมือนในภาพ
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%', // กำหนดขนาดรูตรงกลาง (เป็นวงโดนัท)
                plugins: {
                    legend: {
                        display: true, // ซ่อน legend เพราะจะใช้ label ภายนอก
                        position: 'right',
                        labels: {
                            generateLabels: (chart) => {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = ((value / <?php echo $total_value; ?>) * 100).toFixed(0);
                                        return {
                                            text: `${label}: ${percentage}%`, // รวม label กับ %
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor,
                                            lineWidth: data.datasets[0].borderWidth,
                                            hidden: !chart.isDatasetVisible(0) || data.datasets[0].data[i] === 0,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    const percentage = ((context.parsed / <?php echo $total_value; ?>) * 100).toFixed(2);
                                    label += context.parsed + ' (' + percentage + '%)';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        };

        // 6. วาดกราฟ
        new Chart(
            document.getElementById('infographicDoughnutChart'),
            config
        );
    </script>
    
</body>
</html>