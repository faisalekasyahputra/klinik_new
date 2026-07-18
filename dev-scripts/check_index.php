<?php
require 'remote_seed.php';
$stmt = $pdo->query('SHOW INDEX FROM sf_housing_queue');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
