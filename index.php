<?php
// Adatok betöltése
$data_file = "accident_data.json";
if (file_exists($data_file)) {
    $accident_data = json_decode(file_get_contents($data_file), true);
} else {
    die("Nincs elérhető adat.");
}

// Időzóna beállítása
date_default_timezone_set('Europe/Budapest');

// Statisztikák előkészítése
$today = date('Y-m-d');
$this_week = date('o-W');
$this_month = date('Y-m');
$this_year = date('Y');

$total_accidents = count($accident_data["entries"]);
$daily_counts = [];
$tram_daily_counts = [];
$today_count = 0;
$week_count = 0;
$month_count = 0;
$year_count = 0;
$tram_today_count = 0;
$tram_week_count = 0;
$tram_month_count = 0;
$tram_year_count = 0;

// Időtartam számítások
$last_accident_time = null;
$max_time_between = 0;
$min_time_between = PHP_INT_MAX;
$previous_time = null;
$previous_difference = null; // Az előző számláló

foreach ($accident_data["entries"] as $index => $entry) {
    // Csak a dátumot vesszük figyelembe
    $date = date('Y-m-d', strtotime($entry['date']));
    $timestamp = strtotime($entry['date']);
    $is_tram = $entry['is_tram_accident'];

    // Napi adatok
    if (!isset($daily_counts[$date])) {
        $daily_counts[$date] = 0;
        $tram_daily_counts[$date] = 0;
    }
    $daily_counts[$date]++;
    if ($is_tram) {
        $tram_daily_counts[$date]++;
    }

    // Időszakos adatok
    if ($date === $today) {
        $today_count++;
        if ($is_tram) $tram_today_count++;
    }
    if (date('o-W', strtotime($date)) === $this_week) {
        $week_count++;
        if ($is_tram) $tram_week_count++;
    }
    if (date('Y-m', strtotime($date)) === $this_month) {
        $month_count++;
        if ($is_tram) $tram_month_count++;
    }
    if (date('Y', strtotime($date)) === $this_year) {
        $year_count++;
        if ($is_tram) $tram_year_count++;
    }

    // Időtartam számítások
    // Az előző különbség számítása (0. és 1. index közötti idő)
$entries = array_values($accident_data["entries"]); // Asszociatív tömb indexek rendezése
if (isset($entries[0]) && isset($entries[1])) {
    $first_time = strtotime($entries[0]['date']);
    $second_time = strtotime($entries[1]['date']);
    $previous_difference = $first_time - $second_time;
}


    if ($last_accident_time === null || $timestamp > $last_accident_time) {
        $last_accident_time = $timestamp;
    }
    if ($previous_time !== null) {
        $time_difference = $timestamp - $previous_time;
        if ($time_difference > $max_time_between) {
            $max_time_between = $time_difference;
        }
        if ($time_difference < $min_time_between) {
            $min_time_between = $time_difference;
        }
    }
    $previous_time = $timestamp;
}
ksort($daily_counts);
ksort($tram_daily_counts);

$time_since_last_accident = $last_accident_time ? time() - $last_accident_time : null;
$max_time_between = $max_time_between ? gmdate('H:i:s', $max_time_between) : 'N/A';
$min_time_between = $min_time_between < PHP_INT_MAX ? gmdate('H:i:s', $min_time_between) : 'N/A';
$time_since_last_accident = $time_since_last_accident ? gmdate('H:i:s', $time_since_last_accident) : "N/A";
$previous_difference = $previous_difference ? gmdate('H:i:s', $previous_difference) : "N/A";


?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szegedi Baleseti Statisztika</title
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container my-5">
        <h1 class="mb-4">Szegedi Baleseti Statisztika</h1>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Összes baleset</h5>
                        <p class="card-text" id="totalAccidents"><?= $total_accidents ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">SZKT balesetek</h5>
                        <p class="card-text" id="tramAccidents"><?= array_sum($tram_daily_counts) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-bg-secondary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Mai balesetek</h5>
                        <p class="card-text" id="todayCount">Összes: <?= $today_count ?><br>SZKT: <?= $tram_today_count ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Heti balesetek</h5>
                        <p class="card-text" id="weekCount">Összes: <?= $week_count ?><br>SZKT: <?= $tram_week_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Havi balesetek</h5>
                        <p class="card-text" id="monthCount">Összes: <?= $month_count ?><br>SZKT: <?= $tram_month_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Éves balesetek</h5>
                        <p class="card-text" id="yearCount">Összes: <?= $year_count ?><br>SZKT: <?= $tram_year_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Idő az utolsó baleset óta</h5>
                        <p class="card-text" id="timeSinceLastAccident"><?= $time_since_last_accident ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Előző számláló</h5>
                        <p class="card-text" id="previousDifference"><?= $previous_difference ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Max / Min időtartam balesetek között</h5>
                        <p class="card-text">Max: <?= $max_time_between ?><br>Min: <?= $min_time_between ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mb-4">Napi balesetek diagramja</h2>
<canvas id="dailyChart" height="100"></canvas>

<script>
    function fetchAndUpdateData() {
        $.getJSON("accident_data.json", function(data) {
            $("#totalAccidents").text(data.total_accidents);
            $("#tramAccidents").text(data.tram_accidents);
            // Frissítéshez további elemek hozzáadása itt
        });
    }

    setInterval(fetchAndUpdateData, 600000); // 10 percenkénti frissítés

    const dailyLabels = <?= json_encode(array_keys($daily_counts)) ?>;
    const dailyData = <?= json_encode(array_values($daily_counts)) ?>;
    const tramDailyData = <?= json_encode(array_values($tram_daily_counts)) ?>;

    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [
                {
                    label: 'Összes baleset',
                    data: dailyData,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'SZKT balesetek',
                    data: tramDailyData,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Dátum'
                    },
                    stacked: false // Adjust this if you want a stacked bar chart
                },
                y: {
                    title: {
                        display: true,
                        text: 'Balesetek száma'
                    }
                }
            }
        }
    });

    // Indulási idő kiszámítása PHP-ból
let lastAccidentTimestamp = <?= $last_accident_time ?> * 1000; // PHP timestamp to JavaScript (ms)

// Frissítés
function updateElapsedTime() {
    const now = Date.now(); // Jelenlegi idő ms-ban
    const elapsedTime = now - lastAccidentTimestamp;

    let seconds = Math.floor(elapsedTime / 1000);
    const hours = Math.floor(seconds / 3600);
    seconds %= 3600;
    const minutes = Math.floor(seconds / 60);
    seconds %= 60;

    const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    $("#timeSinceLastAccident").text(formattedTime);
}

// Frissítés másodpercenként
setInterval(updateElapsedTime, 1000);

// Első frissítés
updateElapsedTime();

</script>

        <h2 class="mb-4">Balesetek listája</h2>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Dátum</th>
                <th>Esemény</th>
                <th>SZKT baleset</th>
            </tr>
        </thead>
        <tbody>
            <?php
// Rendezés dátum szerint csökkenő sorrendben
usort($accident_data["entries"], function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
            <?php 
            
            foreach ($accident_data["entries"] as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($entry['date']))) ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($entry['link']) ?>" target="_blank" class="text-primary">
                            <?= htmlspecialchars($entry['title']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge <?= $entry['is_tram_accident'] ? 'bg-danger' : 'bg-success' ?>">
                            <?= $entry['is_tram_accident'] ? 'Igen' : 'Nem' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    </div>
</body>
</html>
