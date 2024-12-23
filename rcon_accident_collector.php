<?php
// Beállítások
$rss_feed_url = "https://szeged365.hu/feed/";
$accident_category = "Baleset";
$data_file = "accident_data.json";
$tram_keywords = ["villamos", "tram train", "szkt"];

// Elmentett adatok betöltése
if (file_exists($data_file)) {
    $accident_data = json_decode(file_get_contents($data_file), true);
} else {
    $accident_data = [
        "total_accidents" => 0,
        "tram_accidents" => 0,
        "entries" => []
    ];
}

// RSS feed beolvasása
$rss_content = file_get_contents($rss_feed_url);
if ($rss_content === false) {
    die("Nem sikerült elérni az RSS feedet.");
}

// RSS elemzése
$rss = simplexml_load_string($rss_content);
if (!$rss) {
    die("Az RSS feed hibás.");
}

// Baleset kategóriájú hírek keresése
foreach ($rss->channel->item as $item) {
    $categories = [];
    foreach ($item->category as $category) {
        $categories[] = (string)$category;
    }

    if (in_array($accident_category, $categories)) {
        $title = (string)$item->title;
        $link = (string)$item->link;
        $pub_date = (string)$item->pubDate;
        $description = (string)$item->description;

        // Ellenőrizzük, hogy már feldolgoztuk-e ezt a hírt
        $hash = md5($title . $link);
        if (!isset($accident_data['entries'][$hash])) {
            // Új bejegyzés hozzáadása
            $is_tram_accident = false;

            // Kulcsszavak keresése a cikk címében vagy leírásában
            foreach ($tram_keywords as $keyword) {
                if (stripos($title, $keyword) !== false || stripos($description, $keyword) !== false) {
                    $is_tram_accident = true;
                    break;
                }
            }

            // Bejegyzés hozzáadása
            $accident_data['entries'][$hash] = [
                "title" => $title,
                "link" => $link,
                "date" => $pub_date,
                "is_tram_accident" => $is_tram_accident
            ];

            // Statisztikák frissítése
            $accident_data["total_accidents"]++;
            if ($is_tram_accident) {
                $accident_data["tram_accidents"]++;
            }
        }
    }
}

// Adatok mentése
file_put_contents($data_file, json_encode($accident_data));
echo "Az adatok frissítése sikeres volt.\n";
?>
