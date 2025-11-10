<?php
// Debug script per vedere le entries
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>Entries nel Database:</h2>";
$entries = App\Models\Entry::select('id', 'title', 'contest_id', 'likes_count', 'ratings_count', 'average_rating', 'vote_score')
    ->orderBy('id')
    ->get();

if ($entries->isEmpty()) {
    echo "<p>Nessuna entry trovata! Esegui il seeder.</p>";
} else {
    echo "<ul>";
    foreach ($entries as $entry) {
        echo "<li><strong>ID: {$entry->id}</strong> - {$entry->title} (Contest: {$entry->contest_id})<br>";
        echo "Likes: {$entry->likes_count}, Ratings: {$entry->ratings_count}, Media: {$entry->average_rating}, Score: {$entry->vote_score}</li>";
    }
    echo "</ul>";
}

echo "<h2>Contest nel Database:</h2>";
$contests = App\Models\Contest::select('id', 'title')->get();
echo "<ul>";
foreach ($contests as $contest) {
    echo "<li>ID: {$contest->id} - {$contest->title}</li>";
}
echo "</ul>";
