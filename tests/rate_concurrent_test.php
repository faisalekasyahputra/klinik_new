<?php
define('BASEPATH', __DIR__ . '/../system/');

class FakeResult {
    private $row;
    public function __construct(array $row) { $this->row = $row; }
    public function row_array() { return $this->row; }
}
class FakeDatabase {
    public $count = 0;
    public $locks = [];
    private $last_lock = '';
    public function query($sql, $args = []) {
        if (strpos($sql, 'INSERT INTO sys_rate_limits') !== false) { $this->count++; return true; }
        if (strpos($sql, 'GET_LOCK') !== false) {
            $name = $args[0];
            if (isset($this->locks[$name])) { return new FakeResult(['acquired' => 0]); }
            $this->locks[$name] = true;
            return new FakeResult(['acquired' => 1]);
        }
        if (strpos($sql, 'RELEASE_LOCK') !== false) { unset($this->locks[$args[0]]); return true; }
        throw new RuntimeException('Unexpected query');
    }
    public function select($fields, $escape = true) { return $this; }
    public function where($key, $value = null, $escape = true) { return $this; }
    public function get($table) { return new FakeResult(['failed_attempts' => $this->count, 'retry_after' => 60]); }
}
class FakeConfig {
    public function load($name, $section = false) {}
    public function item($key, $section = null) {
        if ($key === 'rate_limit_policies') return [
            'sensitive' => ['limit' => 3, 'window' => 3600,
                'dimensions' => ['account'], 'concurrent_dimension' => 'account'],
        ];
        return 'test-secret';
    }
}
class FakeLoad { public function database() {} }
class FakeInput { public function ip_address() { return '127.0.0.1'; } }
class FakeRouter {
    public function fetch_class() { return 'Test'; }
    public function fetch_method() { return 'sensitive'; }
}
$ci = (object) ['db' => new FakeDatabase(), 'config' => new FakeConfig(),
    'load' => new FakeLoad(), 'input' => new FakeInput(), 'router' => new FakeRouter()];
function &get_instance() { global $ci; return $ci; }
function log_message($level, $message) {}
require __DIR__ . '/../application/libraries/Rate_limiter.php';

$first = new Rate_limiter();
$second = new Rate_limiter();
$a = $first->hit('sensitive', ['account_id' => 7]);
$b = $second->hit('sensitive', ['account_id' => 7]);
if (empty($a['allowed']) || !empty($b['allowed']) || $b['warning_type'] !== 'concurrent_access') {
    throw new RuntimeException('Concurrent access was not detected');
}
$first->release_concurrent_locks();
$c = $second->hit('sensitive', ['account_id' => 7]);
if (empty($c['allowed'])) { throw new RuntimeException('Lock was not released'); }
$second->release_concurrent_locks();
$d = $first->hit('sensitive', ['account_id' => 7]);
if (!empty($d['allowed']) || $d['warning_type'] !== 'continuous_access') {
    throw new RuntimeException('Sustained access was not detected');
}
echo "rate_concurrent_test: OK\n";
