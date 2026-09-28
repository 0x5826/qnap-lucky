<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lucky - QNAP 原生管理面板</title>
    <link rel="shortcut icon" href="static/favicon.ico" type="image/x-icon">
    <style>
        /* 统一采用 EasyTier 原生暗黑极客质感配色系统 */
        :root {
            --bg-base: #0f172a;
            --bg-surface: #1e293b;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --border-color: rgba(255, 255, 255, 0.08);
            --border-focus: #0ea5e9;

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;

            --primary: #0ea5e9;
            --primary-hover: #38bdf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;

            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;

            --shadow-card: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.25);
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            font-size: 14px;
            line-height: 1.5;
            min-height: 100vh;
            padding: 16px;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
        }

        /* 头部导航 (对齐 EasyTier 架构) */
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .official-logo {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            object-fit: contain;
            filter: drop-shadow(0 3px 10px rgba(14, 165, 233, 0.3));
        }

        .title-meta h1 {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-version {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-radius: 4px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-family: var(--font-mono);
        }

        .app-subtitle {
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* 状态指示盒与开机自启动控制器 */
        .status-indicator-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0, 0, 0, 0.25);
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot-running {
            background-color: var(--success);
            box-shadow: 0 0 10px var(--success);
            animation: pulse 2s infinite;
        }

        .status-dot-stopped {
            background-color: var(--danger);
            box-shadow: 0 0 8px var(--danger);
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .status-text {
            font-size: 13px;
            font-weight: 500;
        }

        .status-divider {
            width: 1px;
            height: 14px;
            background: rgba(255, 255, 255, 0.15);
            margin: 0 2px;
        }

        .autostart-control {
            display: flex;
            align-items: center;
            gap: 8px;
            user-select: none;
        }

        .autostart-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        /* 微型开关组件规范 (对齐 EasyTier) */
        .switch {
            position: relative;
            display: inline-block;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #334155;
            transition: .25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .slider:before {
            position: absolute;
            content: "";
            background-color: #94a3b8;
            transition: .25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        input:checked + .slider {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        input:checked + .slider:before {
            background-color: #ffffff;
        }

        .switch-sm {
            width: 32px;
            height: 18px;
        }

        .switch-sm .slider {
            border-radius: 18px;
        }

        .switch-sm .slider:before {
            height: 14px;
            width: 14px;
            left: 2px;
            bottom: 1px;
            border-radius: 50%;
        }

        .switch-sm input:checked + .slider:before {
            transform: translateX(14px);
        }

        /* 按钮规范 */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            line-height: 1.4;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-color);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-1px);
        }

        .btn-launch {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }

        .btn-launch:hover {
            background: var(--success);
            color: white;
            transform: translateY(-1px);
        }

        /* 核心状态指标卡片 (紧凑两列网格，高度精炼) */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }

        @media (max-width: 640px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .bg-blue {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(14, 165, 233, 0.25);
        }

        .bg-emerald {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .stat-content {
            flex: 1;
            min-width: 0;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 3px;
        }

        .stat-val {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            font-family: var(--font-mono);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .stat-desc {
            font-size: 12px;
            color: var(--text-dim);
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge-status-tag {
            font-size: 11px;
            font-weight: 600;
            padding: 1px 7px;
            border-radius: 4px;
            font-family: sans-serif;
        }

        /* 通用卡片容器 */
        .section-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 8px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-desc {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* 高级配置指令按钮组 (增强对比度与极客科技质感) */
        .cmd-button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
        }

        .btn-action-cmd {
            background: rgba(14, 165, 233, 0.08);
            border: 1px solid rgba(14, 165, 233, 0.25);
            color: #f8fafc;
            padding: 10px 15px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
        }

        .btn-action-cmd:hover {
            background: rgba(14, 165, 233, 0.18);
            border-color: #38bdf8;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .btn-action-cmd:active {
            transform: translateY(0);
        }

        .btn-action-cmd span.cmd-sub {
            font-size: 11px;
            font-weight: 600;
            color: #38bdf8;
            background: rgba(14, 165, 233, 0.15);
            border: 1px solid rgba(14, 165, 233, 0.3);
            border-radius: 4px;
            padding: 2px 7px;
            font-family: var(--font-mono);
        }

        /* 命令执行反馈框 */
        .exec-feedback {
            margin-top: 12px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            display: none;
            position: relative;
        }

        .feedback-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .feedback-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .feedback-close {
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 16px;
            cursor: pointer;
        }

        .feedback-content {
            margin-top: 6px;
            font-family: var(--font-mono);
            white-space: pre-wrap;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.25);
            padding: 8px;
            border-radius: 4px;
            max-height: 140px;
            overflow-y: auto;
            color: var(--text-main);
        }

        /* 运行日志窗口 (终端配色与高对比呈现) */
        .terminal-box {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: #e2e8f0;
            line-height: 1.55;
            height: 240px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .terminal-box::-webkit-scrollbar {
            width: 6px;
        }
        .terminal-box::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        /* 顶部居中浮动提示 Toast (对齐 EasyTier 交互规范) */
        .toast {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translate(-50%, -20px);
            padding: 12px 24px;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            font-size: 13.5px;
            font-weight: 500;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            z-index: 9999;
            white-space: nowrap;
            color: #f8fafc;
        }

        .toast.show {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    </style>
</head>
<body>

<div class="container">
    <!-- 头部导航 (对齐 EasyTier 交互标准) -->
    <header class="app-header">
        <div class="header-left">
            <img src="static/logo.png" class="official-logo" alt="Lucky">
            <div class="title-meta">
                <h1>Lucky <span class="badge-version" id="headerVersion">v2.27.2-20260928</span></h1>
                <span class="app-subtitle">软硬路由与公网穿透管理平台</span>
            </div>
        </div>

        <div class="header-right">
            <!-- 状态胶囊与开机自启动开关 -->
            <div class="status-indicator-box">
                <span class="pulse-dot status-dot-stopped" id="headerStatusDot"></span>
                <span class="status-text" id="headerStatusText">检测中...</span>
                <span class="status-divider"></span>
                <div class="autostart-control" title="设置系统开机或重启时是否自动运行 Lucky">
                    <span class="autostart-label">开机自启</span>
                    <label class="switch switch-sm">
                        <input type="checkbox" id="autostart_switch" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- 操作按钮组 -->
            <div class="action-buttons">
                <button class="btn btn-outline" id="btnRefresh" onclick="fetchStatus(true)" title="立即刷新服务状态">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <span>刷新</span>
                </button>
                <button id="btnToggleService" class="btn btn-primary" onclick="toggleService()">
                    启动服务
                </button>
                <a id="btnLaunchNative" href="http://127.0.0.1:16601" target="_blank" class="btn btn-launch" style="display: none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>进入控制台</span>
                </a>
            </div>
        </div>
    </header>

    <!-- 核心状态指标卡片 (紧凑两列网格，高度精炼) -->
    <section class="stat-grid">
        <!-- 卡片 1：管理监听端口 -->
        <div class="stat-card">
            <div class="stat-icon bg-blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Web 管理端口</div>
                <div class="stat-val">
                    <span id="statPort">16601</span>
                    <span id="statPortBadge" class="badge-status-tag" style="background: rgba(255,255,255,0.08); color: var(--text-muted);">检测中</span>
                </div>
                <div id="statSafeUrl" class="stat-desc">安全入口: 未设置</div>
            </div>
        </div>

        <!-- 卡片 2：服务核心进程 -->
        <div class="stat-card">
            <div class="stat-icon bg-emerald">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">核心常驻进程 (PID)</div>
                <div class="stat-val">
                    <span id="statPid">未运行</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 高级配置 -->
    <section class="section-card">
        <div class="section-header">
            <div>
                <div class="section-title">高级配置</div>
                <div class="section-desc">官方 CLI 安全重置与应急运维指令</div>
            </div>
        </div>

        <div class="cmd-button-group">
            <button class="btn-action-cmd" onclick="runCommand('reset_user', '重置管理员')">
                <span>重置登录凭据</span>
                <span class="cmd-sub">666:666</span>
            </button>
            <button class="btn-action-cmd" onclick="runCommand('cancel_safeurl', '取消安全入口')">
                <span>清除安全入口</span>
                <span class="cmd-sub">SafeURL</span>
            </button>
            <button class="btn-action-cmd" onclick="runCommand('unlock', '解除登录锁定')">
                <span>解除限制锁定</span>
                <span class="cmd-sub">Unlock</span>
            </button>
            <button class="btn-action-cmd" onclick="runCommand('disable_2fa', '禁用 2FA')">
                <span>关闭双重验证</span>
                <span class="cmd-sub">Disable2FA</span>
            </button>
        </div>

        <!-- 动态命令执行反馈条 -->
        <div id="execFeedback" class="exec-feedback">
            <button class="feedback-close" onclick="hideFeedback()">×</button>
            <strong id="feedbackTitle">执行结果</strong>
            <div id="feedbackBody" class="feedback-content"></div>
        </div>
    </section>

    <!-- NAS 当前端口占用参考 (折叠卡片) -->
    <section class="section-card" style="padding: 12px 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="togglePortsPanel()">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span style="font-size: 13px; font-weight: 600;">NAS 当前已监听 TCP 端口参考</span>
                <span id="listeningPortsCount" style="font-size: 12px; color: var(--text-muted);">(共 -- 个)</span>
            </div>
            <span id="portsToggleText" style="font-size: 12px; color: var(--text-muted);">展开查看</span>
        </div>
        <div id="listeningPortsContainer" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                以下为 NAS 当前被系统或其他容器占用的 TCP 端口。在 Lucky 中配置端口转发或修改 Web 控制台端口时，请避开下列端口：
            </p>
            <div id="listeningPortsBadges" style="display: flex; flex-wrap: wrap; gap: 6px; max-height: 120px; overflow-y: auto;">
                <span style="font-size: 12px; color: var(--text-muted);">正在扫描系统监听端口...</span>
            </div>
        </div>
    </section>

    <!-- 运行日志窗口 (程序运行与启停日志统一收敛) -->
    <section class="section-card">
        <div class="section-header">
            <div>
                <div class="section-title">运行日志 (lucky.log)</div>
                <div class="section-desc">统一呈现核心服务输出与启停异常日志</div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; font-size: 12px; color: var(--text-muted);">
                <label style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <input type="checkbox" id="autoRefreshLog" checked> 自动刷新 (5s)
                </label>
                <button class="btn btn-outline" style="padding: 3px 8px; font-size: 11.5px;" onclick="fetchLogs()">
                    刷新日志
                </button>
                <button class="btn btn-outline" style="padding: 3px 8px; font-size: 11.5px;" onclick="clearLogView()">
                    清屏
                </button>
            </div>
        </div>

        <div id="logOutput" class="terminal-box">正在加载运行日志...</div>
    </section>
</div>

<!-- 浮动通知 Toast (对齐 EasyTier 交互规范) -->
<div id="toast" class="toast"></div>

<script>
    let currentAdminPort = 16601;
    let currentSafeUrl = "";
    let isServiceRunning = false;

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
    }

    async function fetchStatus(isManual = false) {
        try {
            const resp = await fetch("api.php?action=get_status");
            const data = await resp.json();
            if (data.status === "success") {
                isServiceRunning = !!data.is_running;

                // 顶栏状态指示灯 (呼吸圆点与文案)
                const dot = document.getElementById("headerStatusDot");
                const text = document.getElementById("headerStatusText");
                const launchBtn = document.getElementById("btnLaunchNative");
                const toggleBtn = document.getElementById("btnToggleService");

                if (isServiceRunning) {
                    dot.className = "pulse-dot status-dot-running";
                    text.innerText = "运行中";
                    text.style.color = "var(--success)";
                    launchBtn.style.display = "inline-flex";
                    toggleBtn.className = "btn btn-danger";
                    toggleBtn.innerText = "停止服务";
                } else {
                    dot.className = "pulse-dot status-dot-stopped";
                    text.innerText = "已停止";
                    text.style.color = "var(--danger)";
                    launchBtn.style.display = "none";
                    toggleBtn.className = "btn btn-primary";
                    toggleBtn.innerText = "启动服务";
                }

                // 开机自启动开关状态同步
                const autostartSwitch = document.getElementById("autostart_switch");
                if (autostartSwitch && typeof data.autostart !== 'undefined') {
                    autostartSwitch.checked = !!data.autostart;
                }

                if (isManual) {
                    showToast("服务状态已刷新");
                }

                // 核心进程 PID
                document.getElementById("statPid").innerText = isServiceRunning ? (data.pid || "运行中") : "未运行";

                // 版本号 (优先展示带构建日期的完整版本，如 v2.27.2-20260928)
                if (data.info && data.info.BuildVersion) {
                    document.getElementById("headerVersion").innerText = `v${data.info.BuildVersion}`;
                } else if (data.info && data.info.Version) {
                    document.getElementById("headerVersion").innerText = `v${data.info.Version}`;
                }

                // 端口与安全入口路径
                if (data.config && data.config.AdminWebListenPort) {
                    currentAdminPort = data.config.AdminWebListenPort;
                    currentSafeUrl = data.config.SafeURL || "";
                    document.getElementById("statPort").innerText = currentAdminPort;
                    document.getElementById("statSafeUrl").innerText = currentSafeUrl ? `安全入口: ${currentSafeUrl}` : "安全入口: 未设置";
                    updateNativeUrl();
                }

                // 端口健康诊断徽标
                const portBadge = document.getElementById("statPortBadge");
                if (data.port_diagnostic) {
                    const pd = data.port_diagnostic;
                    if (pd.status === 'normal') {
                        portBadge.innerText = "正常";
                        portBadge.style.background = "rgba(16, 185, 129, 0.15)";
                        portBadge.style.color = "#34d399";
                    } else if (pd.status === 'conflict') {
                        portBadge.innerText = `冲突: ${pd.occupant || '外部进程'}`;
                        portBadge.style.background = "rgba(239, 68, 68, 0.15)";
                        portBadge.style.color = "#f87171";
                    } else {
                        portBadge.innerText = "空闲";
                        portBadge.style.background = "rgba(255, 255, 255, 0.08)";
                        portBadge.style.color = "var(--text-muted)";
                    }
                }
            }
        } catch (e) {
            console.error("Failed to fetch status:", e);
        }
    }

    function toggleService() {
        if (isServiceRunning) {
            runCommand('stop', '停止服务');
        } else {
            runCommand('start', '启动服务');
        }
    }

    // 开机自启动切换事件 (对齐 EasyTier)
    const autostartSwitch = document.getElementById('autostart_switch');
    if (autostartSwitch) {
        autostartSwitch.addEventListener('change', async (e) => {
            const isChecked = e.target.checked;
            const targetVal = isChecked ? 1 : 0;
            autostartSwitch.disabled = true;

            try {
                const resp = await fetch('api.php?action=set_autostart', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ autostart: targetVal })
                });
                const res = await resp.json();
                if (res.status === 'success') {
                    showToast(res.message || (isChecked ? '开机自启动已开启' : '开机自启动已关闭'));
                } else {
                    showToast(res.message || '设置开机自启失败', true);
                    autostartSwitch.checked = !isChecked;
                }
            } catch (err) {
                showToast('网络请求异常: ' + err.message, true);
                autostartSwitch.checked = !isChecked;
            } finally {
                autostartSwitch.disabled = false;
            }
        });
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
                        const bg = isCurrent ? 'rgba(14, 165, 233, 0.25)' : 'rgba(255, 255, 255, 0.05)';
                        const color = isCurrent ? '#38bdf8' : 'var(--text-main)';
                        const border = isCurrent ? 'rgba(14, 165, 233, 0.5)' : 'var(--border-color)';
                        return `<span style="font-size: 11.5px; font-family: var(--font-mono); padding: 2px 7px; border-radius: 4px; background: ${bg}; color: ${color}; border: 1px solid ${border}; font-weight: ${isCurrent ? '700' : '500'};">${p}${isCurrent ? ' (Lucky)' : ''}</span>`;
                    }).join("");
                } else {
                    badgeBox.innerHTML = "<span style='font-size: 12px; color: var(--text-muted);'>未探测到活动端口</span>";
                }
            }
        } catch (e) {
            console.error("Failed to fetch listening ports:", e);
        }
    }

    let toastTimer = null;
    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.style.borderColor = isError ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.4)';
        toast.style.color = isError ? '#f87171' : '#34d399';
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function showFeedback(isSuccess, title, content) {
        const fb = document.getElementById("execFeedback");
        fb.className = "exec-feedback " + (isSuccess ? "feedback-success" : "feedback-error");
        document.getElementById("feedbackTitle").innerText = title;
        document.getElementById("feedbackBody").innerText = content || "(执行完成)";
        fb.style.display = "block";
    }

    function hideFeedback() {
        document.getElementById("execFeedback").style.display = "none";
    }

    async function runCommand(cmdKey, cmdName) {
        const isServiceCmd = ['start', 'stop', 'restart'].includes(cmdKey);
        if (!isServiceCmd) {
            showFeedback(true, `正在执行 [${cmdName}]...`, "请稍候...");
        } else {
            showToast(`正在执行 [${cmdName}]...`);
        }

        try {
            const fd = new FormData();
            fd.append("cmd", cmdKey);
            const resp = await fetch("api.php?action=run_cmd", {
                method: "POST",
                body: fd
            });
            const res = await resp.json();

            if (res.status === "success") {
                if (isServiceCmd) {
                    const msgMap = {
                        'start': '已启动 Lucky 服务',
                        'stop': '已停止 Lucky 服务',
                        'restart': '已重启 Lucky 服务'
                    };
                    showToast(msgMap[cmdKey] || `[成功] ${cmdName} 完成`);
                    hideFeedback();
                } else {
                    showToast(`[成功] ${cmdName} 执行完成`);
                    showFeedback(true, `[成功] ${cmdName} 执行完成 (返回码: ${res.exit_code})`, res.output || "指令已成功下发至底层守护进程。");
                }
            } else {
                showToast(`[失败] ${cmdName} 执行异常`, true);
                showFeedback(false, `[失败] ${cmdName} 执行异常 (返回码: ${res.exit_code || -1})`, res.output || res.message || "未知错误");
            }

            setTimeout(() => fetchStatus(false), 1200);
            setTimeout(fetchLogs, 1600);
        } catch (e) {
            showToast(`[错误] 网络通讯异常: ${e.message}`, true);
            showFeedback(false, `[错误] 网络通讯异常`, e.message);
        }
    }

    function clearLogView() {
        document.getElementById("logOutput").innerText = "";
    }

    async function fetchLogs() {
        try {
            const resp = await fetch("api.php?action=get_log&lines=60");
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
    setInterval(() => fetchStatus(false), 4000);
    setInterval(() => {
        if (document.getElementById("autoRefreshLog").checked) {
            fetchLogs();
        }
    }, 5000);
</script>

</body>
</html>
