<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// 确定 QPKG 根目录
$qpkg_root = "";
if (file_exists('/sbin/getcfg')) {
    $qpkg_root = trim(shell_exec('/sbin/getcfg lucky Install_Path -f /etc/config/qpkg.conf 2>/dev/null'));
}
if (empty($qpkg_root) || !is_dir($qpkg_root)) {
    $qpkg_root = dirname(dirname(dirname(__FILE__)));
}

$core_bin = "$qpkg_root/lucky";
$service_sh = "$qpkg_root/lucky.sh";
if (!file_exists($service_sh) && file_exists("$qpkg_root/shared/lucky.sh")) {
    $service_sh = "$qpkg_root/shared/lucky.sh";
}
$conf_dir = "$qpkg_root/conf";
$log_file = "$conf_dir/lucky.log";
$pid_file = "$qpkg_root/lucky.pid";

$action = isset($_GET['action']) ? $_GET['action'] : '';

function run_shell($cmd) {
    $output = [];
    $ret = 0;
    exec($cmd . " 2>&1", $output, $ret);
    return [
        'code' => $ret,
        'output' => implode("\n", $output)
    ];
}

function is_process_lucky($pid) {
    if (empty($pid) || !is_numeric($pid)) return false;
    if (!file_exists("/proc/$pid")) return false;
    $comm = trim(@file_get_contents("/proc/$pid/comm") ?: '');
    if ($comm !== 'lucky' && $comm !== 'lucky_wanji') return false;

    // 检查 cmdline，排除短命的 CLI 工具进程（如 -baseConfInfo 或 -info）
    $cmdline = @file_get_contents("/proc/$pid/cmdline") ?: '';
    if (preg_match('/-(baseConfInfo|info|rResetUser|rCancelSafeURL|rUnlock|rDisable2FA|h|v)\b/', $cmdline)) {
        return false;
    }
    return true;
}

if ($action === 'get_status') {
    // 1. 检查运行状态与 PID
    $is_running = false;
    $current_pid = "";
    
    // 执行状态脚本自愈探测
    if (file_exists($service_sh)) {
        $status_res = run_shell("sh \"$service_sh\" status");
        if ($status_res['code'] === 0) {
            if (preg_match('/PID:\s*(\d+)/', $status_res['output'], $m)) {
                $candidate = $m[1];
                if (is_process_lucky($candidate)) {
                    $is_running = true;
                    $current_pid = $candidate;
                }
            }
        }
    }
    if (!$is_running && file_exists($pid_file)) {
        $saved_pid = trim(@file_get_contents($pid_file));
        if (!empty($saved_pid) && is_process_lucky($saved_pid)) {
            $is_running = true;
            $current_pid = $saved_pid;
        }
    }

    // 2. 获取 lucky -info (静态信息带本地轻量缓存，避免每次轮询都重复 fork 进程)
    $info_data = [];
    $info_cache_file = "$conf_dir/.info_cache.json";
    if (file_exists($info_cache_file) && file_exists($core_bin) && filemtime($info_cache_file) >= filemtime($core_bin)) {
        $cached_info = @json_decode(file_get_contents($info_cache_file), true);
        if (is_array($cached_info)) {
            $info_data = $cached_info;
        }
    }
    if (empty($info_data) && file_exists($core_bin)) {
        $info_raw = trim(shell_exec("\"$core_bin\" -info 2>/dev/null"));
        if (!empty($info_raw)) {
            $decoded = json_decode($info_raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $info_data = $decoded;
                if (is_dir($conf_dir) && is_writable($conf_dir)) {
                    @file_put_contents($info_cache_file, $info_raw);
                }
            }
        }
    }

    // 解析构建版本 (包含构建日期，对齐 EasyTier 规范：如 2.27.2-20260928)
    $build_ver_file = "$qpkg_root/build_version";
    if (!file_exists($build_ver_file) && file_exists("$qpkg_root/shared/build_version")) {
        $build_ver_file = "$qpkg_root/shared/build_version";
    }
    $build_version = "";
    if (file_exists($build_ver_file)) {
        $build_version = trim(file_get_contents($build_ver_file));
    }
    if (empty($build_version)) {
        $core_ver = !empty($info_data['Version']) ? $info_data['Version'] : '2.27.2';
        $build_version = $core_ver . '-20260928';
    }
    $info_data['BuildVersion'] = $build_version;

    // 3. 获取 lucky -baseConfInfo
    $base_conf = [
        'AdminWebListenPort' => 16601,
        'SafeURL' => ''
    ];
    if (file_exists($core_bin) && is_dir($conf_dir)) {
        $conf_raw = shell_exec("\"$core_bin\" -baseConfInfo -cd \"$conf_dir\" 2>/dev/null");
        if (!empty($conf_raw)) {
            // 提取第一行有效 JSON
            foreach (explode("\n", $conf_raw) as $line) {
                $line = trim($line);
                if (strpos($line, '{"BaseConfigure"') !== false) {
                    $c_decoded = json_decode($line, true);
                    if (isset($c_decoded['BaseConfigure'])) {
                        $base_conf = array_merge($base_conf, $c_decoded['BaseConfigure']);
                    }
                    break;
                }
            }
        }
    }

    // 4. 获取进程资源占用
    $cpu_usage = "0.0%";
    $mem_usage = "0.0 MB";
    $uptime = "--";
    if ($is_running && !empty($current_pid)) {
        $ps_out = shell_exec("ps -p $current_pid -o %cpu,%mem,etime 2>/dev/null | tail -n 1");
        if ($ps_out) {
            $parts = preg_split('/\s+/', trim($ps_out));
            if (count($parts) >= 3) {
                $cpu_usage = $parts[0] . "%";
                $mem_usage = $parts[1] . "%";
                $uptime = $parts[2];
            }
        }
    }

    // 5. 端口占用与冲突诊断
    $admin_port = intval($base_conf['AdminWebListenPort']);
    $port_status = 'free';       // free | normal | conflict
    $port_occupant = '';

    $fuser_raw = shell_exec("fuser $admin_port/tcp 2>/dev/null");
    $fuser_out = trim($fuser_raw !== null ? $fuser_raw : '');
    if (!empty($fuser_out)) {
        $occupied_pids = preg_split('/\s+/', $fuser_out);
        $first_occ = $occupied_pids[0];
        $p_name_raw = shell_exec("cat /proc/$first_occ/comm 2>/dev/null");
        $p_name = trim($p_name_raw !== null ? $p_name_raw : '');
        if (empty($p_name)) $p_name = "PID $first_occ";

        if ($is_running && !empty($current_pid) && ($first_occ == $current_pid || strpos($p_name, 'lucky') !== false)) {
            $port_status = 'normal';
            $port_occupant = "Lucky ($first_occ)";
        } else {
            $port_status = 'conflict';
            $port_occupant = "$p_name ($first_occ)";
        }
    } elseif ($is_running) {
        // 如果没有 fuser 命令，降级使用 netstat
        $ns_out = shell_exec("netstat -tuln 2>/dev/null | grep -E '[:\s]$admin_port\s'");
        if (!empty($ns_out)) {
            $port_status = 'normal';
            $port_occupant = "Lucky";
        }
    }

    // 获取开机自启配置 (对齐 EasyTier 架构：读取 qpkg_settings.json，默认为 1 开启)
    $settings_file = "$conf_dir/qpkg_settings.json";
    $autostart = 1;
    if (file_exists($settings_file)) {
        $s_data = @json_decode(file_get_contents($settings_file), true);
        if (is_array($s_data) && isset($s_data['autostart'])) {
            $autostart = intval($s_data['autostart']);
        }
    }

    echo json_encode([
        'status' => 'success',
        'is_running' => $is_running,
        'pid' => $current_pid,
        'uptime' => $uptime,
        'info' => $info_data,
        'config' => $base_conf,
        'autostart' => ($autostart !== 0),
        'port_diagnostic' => [
            'port' => $admin_port,
            'status' => $port_status,
            'occupant' => $port_occupant
        ]
    ]);
    exit;
}

if ($action === 'set_autostart') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $target_val = (!empty($input['autostart']) && $input['autostart'] != '0' && $input['autostart'] !== false) ? 1 : 0;
    
    $settings_file = "$conf_dir/qpkg_settings.json";
    $settings_data = ['autostart' => $target_val];
    if (file_exists($settings_file)) {
        $existing = @json_decode(file_get_contents($settings_file), true);
        if (is_array($existing)) {
            $settings_data = array_merge($existing, $settings_data);
        }
    }
    @file_put_contents($settings_file, json_encode($settings_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    @chmod($settings_file, 0666);

    $is_on = ($target_val === 1);
    echo json_encode([
        'status' => 'success',
        'autostart' => $is_on,
        'message' => $is_on ? '开机自启动已开启' : '开机自启动已关闭'
    ]);
    exit;
}

if ($action === 'get_listening_ports') {
    // 扫描 NAS 当前所有被监听的 TCP 端口
    $raw = shell_exec("netstat -tuln 2>/dev/null || ss -tuln 2>/dev/null");
    $ports = [];
    if (!empty($raw)) {
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            // 匹配 tcp/tcp6 的 LISTEN 端口
            if (preg_match('/tcp[46]?\s+\d+\s+\d+\s+([0-9a-fA-F\.\:]+):(\d+)\s+.*LISTEN/i', $line, $m)) {
                $p = intval($m[2]);
                if ($p > 0 && $p <= 65535) {
                    $ports[$p] = true;
                }
            }
        }
    }
    $sorted_ports = array_keys($ports);
    sort($sorted_ports, SORT_NUMERIC);

    echo json_encode([
        'status' => 'success',
        'total' => count($sorted_ports),
        'listening_ports' => $sorted_ports
    ]);
    exit;
}

if ($action === 'run_cmd') {
    $cmd_key = isset($_POST['cmd']) ? trim($_POST['cmd']) : '';

    $sudo_prefix = "";
    $test_sudo = @shell_exec("sudo -n true 2>&1");
    if ($test_sudo === null || trim($test_sudo) === '') {
        $sudo_prefix = "sudo -n ";
    }

    $allowed_commands = [
        'reset_user' => "\"$core_bin\" -rResetUser",
        'cancel_safeurl' => "\"$core_bin\" -rCancelSafeURL",
        'unlock' => "\"$core_bin\" -rUnlock",
        'disable_2fa' => "\"$core_bin\" -rDisable2FA",
        'restart' => "{$sudo_prefix}sh \"$service_sh\" restart",
        'start' => "{$sudo_prefix}sh \"$service_sh\" start force",
        'stop' => "{$sudo_prefix}sh \"$service_sh\" stop"
    ];

    if (!isset($allowed_commands[$cmd_key])) {
        echo json_encode([
            'status' => 'error',
            'message' => '不支持的控制指令'
        ]);
        exit;
    }

    if (!file_exists($core_bin)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Lucky 核心二进制程序不存在'
        ]);
        exit;
    }

    $exec_cmd = $allowed_commands[$cmd_key];
    $res = run_shell($exec_cmd);

    echo json_encode([
        'status' => $res['code'] === 0 ? 'success' : 'error',
        'command' => $cmd_key,
        'exit_code' => $res['code'],
        'output' => $res['output']
    ]);
    exit;
}

if ($action === 'get_log') {
    $lines = isset($_GET['lines']) ? intval($_GET['lines']) : 50;
    if ($lines <= 0 || $lines > 500) $lines = 50;

    $log_content = "";
    if (file_exists($log_file)) {
        $log_content = shell_exec("tail -n $lines " . escapeshellarg($log_file) . " 2>/dev/null");
    } else {
        $log_content = "暂无日志文件 (等待服务写入...)";
    }

    echo json_encode([
        'status' => 'success',
        'log' => $log_content
    ]);
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'Invalid action'
]);
exit;
