<?php
require 'remote_seed.php'; 
$stmt = $pdo->query('SELECT data_survey_json, data_simperum_json FROM sf_housing_queue ORDER BY id DESC LIMIT 1');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
