<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$db = new FirestoreClient([
    'projectId'   => 'safeotw1',   // find this in Firebase Project Settings
    'keyFilePath' => __DIR__ . '/service_account.json',
]);

// --- Get one document ---
$docRef = $db->collection('users')->document('alice');
$snapshot = $docRef->snapshot();

if ($snapshot->exists()) {
    echo "Alice: " . $snapshot['name'] . " (Age: " . $snapshot['age'] . ")<br>";
} else {
    echo "Document not found!<br>";
}

// --- Get all documents ---
$users = $db->collection('users')->documents();
echo "<h3>All Users</h3>";
foreach ($users as $doc) {
    $data = $doc->data();
    echo $doc->id() . " → " . $data['name'] . " (" . $data['age'] . ")<br>";
}
