<?php
$pdo = new PDO("mysql:host=localhost;dbname=klinikpkp", "root", "");
$stmt = $pdo->query("SELECT * FROM usr_users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
