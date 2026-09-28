<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lucky - QNAP 原生管理面板</title>
    <link rel="icon" type="image/x-icon" href="static/favicon.ico">
    <style>
        :root {
            --primary: #10b981;
            --primary-hover: #059669;
            --primary-light: #ecfdf5;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --terminal-bg: #090d16;
            --terminal-text: #e2e8f0;
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0f172a;
                --card-bg: #1e293b;
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --primary-light: #064e3b;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            line-height: 1.5;
            padding: 24px 20px;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        /* 顶部导航与状态条 */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            background: var(--card-bg);
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }

        .header-info h1 {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-info p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-running {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-stopped {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
        }

        /* 进入原生控制台按钮 */
        .btn-launch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #10b981;
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
            border: none;
            cursor: pointer;
        }

        .btn-launch:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            background: #059669;
        }

        /* 状态看板网格 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
        }

        .stat-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            color: var(--text-muted);
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
        }

        .stat-desc {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* 运维控制台与卡片 */
        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header h3 {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cmd-button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .btn-cmd {
            background: var(--bg-color);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cmd:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .btn-cmd.btn-danger:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: #fef2f2;
        }

        /* 终端输出展示窗口 */
        .terminal-box {
            background: var(--terminal-bg);
            color: var(--terminal-text);
            border-radius: 12px;
            padding: 16px 20px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12.5px;
            line-height: 1.6;
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #1e293b;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .terminal-box::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .terminal-box::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        .terminal-prompt {
            color: #38bdf8;
            margin-right: 6px;
        }

        .terminal-success {
            color: #4ade80;
        }

        .terminal-error {
            color: #f87171;
        }

        /* 日志窗口控制 */
        .log-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 32px;
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- 顶部状态栏 -->
    <div class="header">
        <div class="header-brand">
            <img src="static/favicon.ico" class="header-logo" alt="Lucky Logo">
            <div class="header-info">
                <h1>Lucky 原生管理面板</h1>
                <p>端口转发 · 反向代理 · 动态域名 · 自动证书 · 网络唤醒</p>
            </div>
        </div>
        <div id="statusBadge" class="badge-status status-stopped">
            <div class="pulse-dot"></div>
            <span id="statusText">检测中...</span>
        </div>
    </div>



    <!-- 状态与环境指标网格 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-title">
                <span>程序版本</span>
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                </span>
            </div>
            <div id="statVersion" class="stat-value">--</div>
            <div id="statArch" class="stat-desc">架构检测中...</div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                <span>运行进程 PID</span>
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                </span>
            </div>
            <div id="statPid" class="stat-value">--</div>
            <div id="statUptime" class="stat-desc">持续运行时长: --</div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                <span>管理监听端口</span>
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                </span>
            </div>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <div id="statPort" class="stat-value">16601</div>
                <span id="statPortBadge" style="font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 600; background: #e2e8f0; color: #475569;">检测中</span>
            </div>
            <div id="statSafeUrl" class="stat-desc">安全入口: 未设置</div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                <span>资源占用</span>
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </span>
            </div>
            <div id="statResource" class="stat-value">0% / 0 MB</div>
            <div class="stat-desc">CPU / 内存占用率</div>
        </div>
    </div>

    <!-- 控制面板 (融合管理控制台直达入口、端口说明与运维指令) -->
    <div class="section-card">
        <div class="section-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3>控制面板</h3>
                <p id="heroPortTip" style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">当前监听端口：16601 · 安全入口：未设置</p>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a id="btnLaunchNative" href="http://127.0.0.1:16601" target="_blank" class="btn-launch">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>进入 Lucky 控制台</span>
                </a>
            </div>
        </div>

        <div class="cmd-button-group">
            <button class="btn-cmd" onclick="runCommand('reset_user', '重置管理员密码')">
                重置登录凭据 (666:666)
            </button>
            <button class="btn-cmd" onclick="runCommand('cancel_safeurl', '取消安全入口')">
                取消安全入口 (SafeURL)
            </button>
            <button class="btn-cmd" onclick="runCommand('unlock', '解除登录锁定')">
                解除登录锁定
            </button>
            <button class="btn-cmd" onclick="runCommand('disable_2fa', '禁用 2FA')">
                禁用双重验证 (2FA)
            </button>
            <button class="btn-cmd" onclick="runCommand('restart', '重启服务')">
                重启服务
            </button>
            <button class="btn-cmd btn-danger" onclick="runCommand('stop', '停止服务')">
                停止 Lucky
            </button>
            <button class="btn-cmd" style="color: var(--primary);" onclick="runCommand('start', '启动服务')">
                启动 Lucky
            </button>
        </div>

        <!-- 终端命令执行输出结果区域 -->
        <div id="terminalOutput" class="terminal-box">
<span class="terminal-prompt">$</span>就绪。点击上方按钮执行对应应急运维指令，执行状态与控制台输出将在此实时显示。
        </div>
    </div>

    <!-- NAS 当前端口占用速查卡片 (折叠组件) -->
    <div class="section-card" style="padding: 16px 24px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="togglePortsPanel()">
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span style="font-size: 14px; font-weight: 600;">NAS 当前已监听 TCP 端口参考</span>
                <span id="listeningPortsCount" style="font-size: 12px; color: var(--text-muted);">(共 -- 个)</span>
            </div>
            <span id="portsToggleText" style="font-size: 12px; color: var(--text-muted);">展开查看</span>
        </div>
        <div id="listeningPortsContainer" style="display: none; margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--border-color);">
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
                以下为 NAS 当前已被其他进程或系统服务占用的端口。若在 Lucky 中配置端口转发或修改 Web 控制台端口，请避开下列端口：
            </p>
            <div id="listeningPortsBadges" style="display: flex; flex-wrap: wrap; gap: 6px; max-height: 120px; overflow-y: auto;">
                <span style="font-size: 12px; color: var(--text-muted);">正在扫描系统监听端口...</span>
            </div>
        </div>
    </div>

    <!-- 实时运行日志看板 -->
    <div class="section-card">
        <div class="section-header">
            <h3>运行日志 (lucky.log)</h3>
            <div class="log-controls">
                <label style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <input type="checkbox" id="autoRefreshLog" checked> 自动刷新 (5s)
                </label>
                <button class="btn-cmd" style="padding: 4px 10px; font-size: 12px;" onclick="fetchLogs()">
                    立即刷新
                </button>
            </div>
        </div>
        <div id="logOutput" class="terminal-box" style="max-height: 320px;">
正在获取最新运行日志...
        </div>
    </div>

    <div class="footer">
        <p>Lucky QNAP 原生插件由 Dante 构建维护 · 官方主页: <a href="https://lucky666.cn/" target="_blank">lucky666.cn</a> · GitHub: <a href="https://github.com/gdy666/lucky" target="_blank">gdy666/lucky</a></p>
    </div>
</div>

<script>
    let currentAdminPort = 16601;
    let currentSafeUrl = "";

    function updateNativeUrl() {
        const host = window.location.hostname || "127.0.0.1";
        let url = `http://${host}:${currentAdminPort}`;
        if (currentSafeUrl && currentSafeUrl.trim() !== "") {
            let s = currentSafeUrl.trim();
            if (!s.startsWith("/")) s = "/" + s;
            url += s;
        }
        const btn = document.getElementById("btnLaunchNative");
        btn.href = url;
        document.getElementById("heroPortTip").innerText = `当前监听端口：${currentAdminPort}${currentSafeUrl ? ' · 安全入口：' + currentSafeUrl : ' · 安全入口：未设置'} · 点击右侧进入完整控制台`;
    }

    async function fetchStatus() {
        try {
            const resp = await fetch("api.php?action=get_status");
            const data = await resp.json();
            if (data.status === "success") {
                const badge = document.getElementById("statusBadge");
                const text = document.getElementById("statusText");

                if (data.is_running) {
                    badge.className = "badge-status status-running";
                    text.innerText = "运行中";
                } else {
                    badge.className = "badge-status status-stopped";
                    text.innerText = "已停止";
                }

                // PID & 运行时长
                document.getElementById("statPid").innerText = data.pid || "未运行";
                document.getElementById("statUptime").innerText = `持续运行时长: ${data.uptime || '--'}`;

                // 资源占用
                document.getElementById("statResource").innerText = `${data.cpu} / ${data.mem}`;

                // 版本 & 架构
                if (data.info && data.info.Version) {
                    document.getElementById("statVersion").innerText = `v${data.info.Version}`;
                    document.getElementById("statArch").innerText = `${data.info.ARCH} (${data.info.OS}) · ${data.info.GoVersion || ''}`;
                }

                // 端口与安全入口及冲突诊断
                if (data.config && data.config.AdminWebListenPort) {
                    currentAdminPort = data.config.AdminWebListenPort;
                    currentSafeUrl = data.config.SafeURL || "";
                    document.getElementById("statPort").innerText = currentAdminPort;
                    document.getElementById("statSafeUrl").innerText = currentSafeUrl ? `安全入口: ${currentSafeUrl}` : "安全入口: 未设置";
                    updateNativeUrl();
                }

                // 端口健康与冲突状态
                const portBadge = document.getElementById("statPortBadge");
                if (data.port_diagnostic) {
                    const pd = data.port_diagnostic;
                    if (pd.status === 'normal') {
                        portBadge.innerText = "监听正常";
                        portBadge.style.background = "#dcfce7";
                        portBadge.style.color = "#15803d";
                    } else if (pd.status === 'conflict') {
                        portBadge.innerText = `冲突被占用: ${pd.occupant || '其他进程'}`;
                        portBadge.style.background = "#fee2e2";
                        portBadge.style.color = "#b91c1c";
                    } else {
                        portBadge.innerText = "空闲";
                        portBadge.style.background = "#f1f5f9";
                        portBadge.style.color = "#64748b";
                    }
                }
            }
        } catch (e) {
            console.error("Failed to fetch status:", e);
        }
    }

    let portsPanelLoaded = false;
    function togglePortsPanel() {
        const container = document.getElementById("listeningPortsContainer");
        const toggleText = document.getElementById("portsToggleText");
        if (container.style.display === "none") {
            container.style.display = "block";
            toggleText.innerText = "收起折叠";
            if (!portsPanelLoaded) {
                fetchListeningPorts();
            }
        } else {
            container.style.display = "none";
            toggleText.innerText = "展开查看";
        }
    }

    async function fetchListeningPorts() {
        try {
            const resp = await fetch("api.php?action=get_listening_ports");
            const data = await resp.json();
            if (data.status === "success") {
                portsPanelLoaded = true;
                document.getElementById("listeningPortsCount").innerText = `(共 ${data.total} 个)`;
                const badgeBox = document.getElementById("listeningPortsBadges");
                if (data.listening_ports && data.listening_ports.length > 0) {
                    badgeBox.innerHTML = data.listening_ports.map(p => {
                        const isCurrent = (p == currentAdminPort);
                        const bg = isCurrent ? '#10b981' : 'var(--bg-color)';
                        const color = isCurrent ? '#ffffff' : 'var(--text-main)';
                        const border = isCurrent ? '#059669' : 'var(--border-color)';
                        return `<span style="font-size: 12px; font-family: monospace; padding: 3px 8px; border-radius: 6px; background: ${bg}; color: ${color}; border: 1px solid ${border}; font-weight: ${isCurrent ? '700' : '500'};">${p}${isCurrent ? ' (Lucky)' : ''}</span>`;
                    }).join("");
                } else {
                    badgeBox.innerHTML = "<span style='font-size: 12px; color: var(--text-muted);'>暂未探测到占用端口</span>";
                }
            }
        } catch (e) {
            console.error("Failed to fetch listening ports:", e);
        }
    }

    async function runCommand(cmdKey, cmdName) {
        const term = document.getElementById("terminalOutput");
        const timestamp = new Date().toLocaleTimeString();
        term.innerHTML += `\n<span class="terminal-prompt">[${timestamp}] $</span> 执行指令 [${cmdName}]...\n`;
        term.scrollTop = term.scrollHeight;

        try {
            const fd = new FormData();
            fd.append("cmd", cmdKey);
            const resp = await fetch("api.php?action=run_cmd", {
                method: "POST",
                body: fd
            });
            const res = await resp.json();

            if (res.status === "success") {
                term.innerHTML += `<span class="terminal-success">[成功] 返回码: ${res.exit_code}</span>\n${res.output || '(命令执行完成，无多余输出)'}\n`;
            } else {
                term.innerHTML += `<span class="terminal-error">[失败] 返回码: ${res.exit_code || -1}</span>\n${res.output || res.message || '未知错误'}\n`;
            }
            term.scrollTop = term.scrollHeight;

            // 延迟 1 秒后刷新状态
            setTimeout(fetchStatus, 1000);
            setTimeout(fetchLogs, 1500);
        } catch (e) {
            term.innerHTML += `<span class="terminal-error">[错误] 网络请求异常: ${e.message}</span>\n`;
            term.scrollTop = term.scrollHeight;
        }
    }

    async function fetchLogs() {
        try {
            const resp = await fetch("api.php?action=get_log&lines=80");
            const data = await resp.json();
            if (data.status === "success") {
                const logBox = document.getElementById("logOutput");
                logBox.innerText = data.log || "暂无日志";
                logBox.scrollTop = logBox.scrollHeight;
            }
        } catch (e) {
            console.error("Failed to fetch logs:", e);
        }
    }

    // 页面初始化与定时刷新
    fetchStatus();
    fetchLogs();
    fetchListeningPorts();
    setInterval(fetchStatus, 4000);
    setInterval(() => {
        if (document.getElementById("autoRefreshLog").checked) {
            fetchLogs();
        }
    }, 5000);
</script>

</body>
</html>
