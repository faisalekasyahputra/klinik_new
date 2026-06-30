<?php
$env = file_get_contents('.env');
$lines = explode("\n", $env);
$envVars = [];
foreach($lines as $line) {
    if(trim($line) && strpos(trim($line), '#') !== 0) {
        $parts = explode('=', trim($line), 2);
        if(count($parts) == 2) {
            $envVars[$parts[0]] = $parts[1];
        }
    }
}

$host = $envVars['DB_HOST'] ?? 'localhost';
$db   = $envVars['DB_NAME'] ?? 'klinikpkp';
$user = $envVars['DB_USER'] ?? 'root';
$pass = $envVars['DB_PASS'] ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// 1. Rename tables
$renames = [
    'programs' => 'sf_programs',
    'program_kategori' => 'sf_program_kategori',
    'housing_queue' => 'sf_housing_queue',
    'diskusi' => 'forum_diskusi',
    'komentar' => 'forum_komentar',
    'users' => 'usr_users',
    'user_documents' => 'usr_documents',
    'menu' => 'sys_menu',
    'multi' => 'sys_multi',
    'settings' => 'sys_settings',
    'sosmed_perumahan' => 'data_sosmed_perumahan'
];

foreach ($renames as $old => $new) {
    try {
        $pdo->exec("RENAME TABLE `$old` TO `$new`");
        echo "Renamed $old to $new\n";
    } catch (PDOException $e) {
        echo "Error renaming $old: " . $e->getMessage() . "\n";
    }
}

// 2. Seed accounts
$password_plain = 'password';
$password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

$accounts = [
    [
        'email' => 'admin@klinikpkp.jatengprov.go.id',
        'username' => 'admin',
        'password' => $password_hash,
        'name' => 'Administrator',
        'role' => 'admin',
        'profile_completed' => 1,
        'kategori' => 'admin'
    ],
    [
        'email' => 'warga@example.com',
        'username' => 'warga_demo',
        'password' => $password_hash,
        'name' => 'Warga Demo',
        'role' => 'warga',
        'profile_completed' => 1,
        'kategori' => 'warga'
    ],
    [
        'email' => 'dev1@example.com',
        'username' => 'developer1',
        'password' => $password_hash,
        'name' => 'Developer Demo',
        'role' => 'pages/pengembang/pengembang',
        'profile_completed' => 1,
        'kategori' => 'pages/pengembang/pengembang'
    ]
];

foreach ($accounts as $acc) {
    $stmt = $pdo->prepare("SELECT id FROM usr_users WHERE username = ? OR email = ?");
    $stmt->execute([$acc['username'], $acc['email']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $sql = "INSERT INTO usr_users (email, username, password, name, role, profile_completed, kategori) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $acc['email'],
            $acc['username'],
            $acc['password'],
            $acc['name'],
            $acc['role'],
            $acc['profile_completed'],
            $acc['kategori']
        ]);
        echo "Created user: {$acc['username']}\n";
    } else {
        $sql = "UPDATE usr_users SET password = ?, role = ? WHERE username = ?";
        $pdo->prepare($sql)->execute([$password_hash, $acc['role'], $acc['username']]);
        echo "Updated user: {$acc['username']}\n";
    }
}
echo "Seeding completed on remote DB.\n";
