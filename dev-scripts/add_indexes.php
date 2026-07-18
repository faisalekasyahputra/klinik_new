<?php
require 'remote_seed.php';

try {
    $pdo->exec("ALTER TABLE sf_housing_queue ADD INDEX idx_status_antrean (status_antrean)");
    echo "Index status_antrean added.\n";
} catch (Exception $e) {
    echo "Error (status_antrean): " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE sf_housing_queue ADD INDEX idx_created_at (created_at)");
    echo "Index created_at added.\n";
} catch (Exception $e) {
    echo "Error (created_at): " . $e->getMessage() . "\n";
}
