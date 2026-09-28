#!/bin/sh
CONF="/etc/config/qpkg.conf"
QPKG_NAME="lucky"

# 获取 QPKG 安装根路径
QPKG_ROOT=$(/sbin/getcfg $QPKG_NAME Install_Path -f ${CONF} 2>/dev/null)
if [ -z "$QPKG_ROOT" ] || [ ! -d "$QPKG_ROOT" ]; then
    SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
    QPKG_ROOT=$(dirname "$SCRIPT_DIR")
fi

CONF_DIR="$QPKG_ROOT/conf"
PID_FILE="$QPKG_ROOT/lucky.pid"
LOG_FILE="$CONF_DIR/lucky.log"

DEF_SHARE_INFO="/etc/config/def_share.info"
DEF_WEB_NAME="Qweb"
if [ -f "$DEF_SHARE_INFO" ]; then
    DEF_WEB_NAME=$(/sbin/getcfg SHARE_DEF defWeb -d Qweb -f $DEF_SHARE_INFO 2>/dev/null)
fi
APACHE_ROOT="/share/$DEF_WEB_NAME"

detect_binary() {
    # 1. 优先使用根目录下已就绪的二进制
    if [ -x "$QPKG_ROOT/lucky" ]; then
        CORE_BIN="$QPKG_ROOT/lucky"
        return 0
    fi

    # 2. 根据硬件架构自动探测
    ARCH=$(uname -m)
    case "$ARCH" in
        x86_64|amd64)
            ARCH_DIR="$QPKG_ROOT/x86_64"
            ;;
        aarch64|arm64)
            ARCH_DIR="$QPKG_ROOT/arm_64"
            ;;
        *)
            ARCH_DIR="$QPKG_ROOT/x86_64"
            ;;
    esac

    # 兼容普通版与万吉版二进制文件名
    if [ -f "$ARCH_DIR/lucky" ]; then
        CORE_BIN="$ARCH_DIR/lucky"
    elif [ -f "$ARCH_DIR/lucky_wanji" ]; then
        CORE_BIN="$ARCH_DIR/lucky_wanji"
    elif [ -f "$QPKG_ROOT/lucky_wanji" ]; then
        CORE_BIN="$QPKG_ROOT/lucky_wanji"
    else
        return 1
    fi

    ln -sf "$CORE_BIN" "$QPKG_ROOT/lucky"
    chmod +x "$QPKG_ROOT/lucky" 2>/dev/null
    CORE_BIN="$QPKG_ROOT/lucky"
    return 0
}

# 真实进程指纹联合探测与 PID 自愈函数
# 核心解决 Lucky Web 内部改配置触发 exec 孤儿自启、导致静态 PID 失效的问题
get_actual_pids() {
    local matched_pids=""

    # 优先采用 pgrep 按照二进制文件实际执行绝对路径进行匹配
    if which pgrep >/dev/null 2>&1; then
        matched_pids=$(pgrep -f "${QPKG_ROOT}/lucky" 2>/dev/null | tr '\n' ' ')
    fi

    # 降级：遍历 pidof lucky 并核对其 /proc/$pid/exe 符号链接指向
    if [ -z "$matched_pids" ] && which pidof >/dev/null 2>&1; then
        for p in $(pidof lucky 2>/dev/null); do
            if [ -d "/proc/$p" ]; then
                local exe_target=$(readlink -f "/proc/$p/exe" 2>/dev/null)
                case "$exe_target" in
                    *"$QPKG_ROOT"*)
                        matched_pids="$matched_pids $p"
                        ;;
                esac
            fi
        done
    fi

    # 若未开启 proc 保护且上述均未命中，做最后通配匹配
    if [ -z "$matched_pids" ] && which pidof >/dev/null 2>&1; then
        matched_pids=$(pidof lucky 2>/dev/null)
    fi

    echo "$matched_pids" | xargs 2>/dev/null
}

sync_pid_file() {
    local active_pids=$(get_actual_pids)
    if [ -n "$active_pids" ]; then
        local first_pid=$(echo "$active_pids" | awk '{print $1}')
        echo "$first_pid" > "$PID_FILE"
        return 0
    else
        rm -f "$PID_FILE" 2>/dev/null
        return 1
    fi
}

log_sys() {
    if [ -x "/sbin/log_tool" ]; then
        /sbin/log_tool -t0 -uSystem -p127.0.0.1 -mlocalhost -a "[Lucky] $1" 2>/dev/null
    fi
}

case "$1" in
  start)
    if [ -x "/sbin/getcfg" ]; then
        ENABLED=$(/sbin/getcfg $QPKG_NAME Enable -u -d TRUE -f $CONF 2>/dev/null)
        if [ "$ENABLED" = "FALSE" ]; then
            echo "$QPKG_NAME is disabled in App Center."
            exit 1
        fi
    fi

    # 检查是否已有活跃实例
    ACTIVE_PIDS=$(get_actual_pids)
    if [ -n "$ACTIVE_PIDS" ]; then
        sync_pid_file
        echo "$QPKG_NAME is already running (PID: $ACTIVE_PIDS)."
        exit 0
    fi

    echo "Starting $QPKG_NAME..."
    mkdir -p "$CONF_DIR" 2>/dev/null

    detect_binary
    if [ ! -x "$CORE_BIN" ]; then
        echo "Error: lucky binary not found or not executable for $(uname -m)."
        exit 1
    fi

    chmod +x "$CORE_BIN" 2>/dev/null
    chmod -Rf 755 "$CONF_DIR" 2>/dev/null

    # 提取目标监听端口并做冲突预检
    ADMIN_PORT="16601"
    if [ -x "$CORE_BIN" ] && [ -d "$CONF_DIR" ]; then
        CONF_PORT=$("$CORE_BIN" -baseConfInfo -cd "$CONF_DIR" 2>/dev/null | grep -o '"AdminWebListenPort":[0-9]*' | cut -d: -f2)
        [ -n "$CONF_PORT" ] && ADMIN_PORT="$CONF_PORT"
    fi

    # 检查端口是否被外部进程占用
    PORT_OCCUPIER=""
    if which fuser >/dev/null 2>&1; then
        PORT_PID=$(fuser "${ADMIN_PORT}/tcp" 2>/dev/null | xargs)
        if [ -n "$PORT_PID" ]; then
            P_NAME=$(cat "/proc/$PORT_PID/comm" 2>/dev/null || echo "PID $PORT_PID")
            PORT_OCCUPIER="$P_NAME ($PORT_PID)"
        fi
    elif which lsof >/dev/null 2>&1; then
        PORT_PID=$(lsof -t -i :${ADMIN_PORT} -sTCP:LISTEN 2>/dev/null | head -n1)
        if [ -n "$PORT_PID" ]; then
            P_NAME=$(cat "/proc/$PORT_PID/comm" 2>/dev/null || echo "PID $PORT_PID")
            PORT_OCCUPIER="$P_NAME ($PORT_PID)"
        fi
    fi

    if [ -n "$PORT_OCCUPIER" ]; then
        echo "Warning: Port $ADMIN_PORT is currently occupied by $PORT_OCCUPIER. Lucky may fail to bind or conflict."
        log_sys "警告：管理端口 $ADMIN_PORT 当前已被外部服务 [$PORT_OCCUPIER] 占用，请注意在控制台修改端口或释放占用。"
    fi

    # 建立系统级 CLI 软链接方便 SSH 终端直接使用 lucky 命令行工具
    ln -sf "$CORE_BIN" /usr/bin/lucky 2>/dev/null
    ln -sf "$CORE_BIN" /usr/sbin/lucky 2>/dev/null
    ln -sf "$CORE_BIN" /usr/local/bin/lucky 2>/dev/null

    # 挂载 Web 管理面板软链接至 QTS Apache 根目录
    WEB_SRC="$QPKG_ROOT/web"
    [ ! -d "$WEB_SRC" ] && WEB_SRC="$QPKG_ROOT/shared/web"
    if [ -d "$WEB_SRC" ]; then
        for target_web in "$APACHE_ROOT" /share/Web /share/Qweb /share/CACHEDEV1_DATA/Web; do
            if [ -d "$target_web" ]; then
                ln -sf "$WEB_SRC" "$target_web/lucky"
            fi
        done
    fi

    # 刷新 QTS 桌面与 App Center 图标库为高清无黑边图标
    ICON_SRC="$QPKG_ROOT/icons"
    [ ! -d "$ICON_SRC" ] && ICON_SRC="$QPKG_ROOT/shared/icons"
    if [ -d "$ICON_SRC" ]; then
        for sys_icon_dir in /home/httpd/RSS/images /home/httpd/cgi-bin/images /home/httpd/v3_images; do
            if [ -d "$sys_icon_dir" ]; then
                cp -f "$ICON_SRC"/* "$sys_icon_dir/" 2>/dev/null
            fi
        done
        touch /etc/config/qpkg.conf 2>/dev/null
    fi

    # 启动 Lucky 核心进程（前台转后台，并指定持久化配置目录）
    nohup "$CORE_BIN" -cd "$CONF_DIR" >> "$LOG_FILE" 2>&1 &

    # 等待进程就绪并动态捕获实际 PID
    sleep 1
    if sync_pid_file; then
        CURRENT_PID=$(cat "$PID_FILE")
        log_sys "Lucky 服务已成功启动 (PID: $CURRENT_PID)"
        echo "$QPKG_NAME started successfully (PID: $CURRENT_PID)."
    else
        echo "Error: Failed to start $QPKG_NAME. Check logs in $LOG_FILE"
        exit 1
    fi
    ;;

  stop)
    echo "Stopping $QPKG_NAME..."
    
    # 全景捕获属于当前 QPKG 的所有存活 PID（防止漏杀内部自重启产生的新 PID）
    TARGET_PIDS=$(get_actual_pids)
    if [ -f "$PID_FILE" ]; then
        SAVED_PID=$(cat "$PID_FILE" 2>/dev/null)
        [ -n "$SAVED_PID" ] && TARGET_PIDS="$TARGET_PIDS $SAVED_PID"
    fi
    TARGET_PIDS=$(echo "$TARGET_PIDS" | tr ' ' '\n' | sort -u | tr '\n' ' ')

    if [ -n "$TARGET_PIDS" ]; then
        # 阶段一：优雅关闭 (SIGTERM)
        for p in $TARGET_PIDS; do
            kill -15 "$p" 2>/dev/null
        done
        sleep 1

        # 阶段二：检查残留并强杀 (SIGKILL)
        REMAINING_PIDS=$(get_actual_pids)
        if [ -n "$REMAINING_PIDS" ]; then
            for rp in $REMAINING_PIDS; do
                kill -9 "$rp" 2>/dev/null
            done
            sleep 1
        fi
    fi

    # 兜底：精确杀除名为 lucky 且匹配路径的残留
    killall -9 lucky 2>/dev/null

    rm -f "$PID_FILE" 2>/dev/null

    # 清理系统 CLI 软链接
    rm -f /usr/bin/lucky /usr/sbin/lucky /usr/local/bin/lucky 2>/dev/null

    # 清理 WebUI 软链接
    if [ -d "$APACHE_ROOT" ]; then
        rm -f "$APACHE_ROOT/lucky" 2>/dev/null
    fi
    rm -f /share/Web/lucky /share/Qweb/lucky /share/CACHEDEV1_DATA/Web/lucky 2>/dev/null

    log_sys "Lucky 服务已停止"
    echo "$QPKG_NAME stopped."
    ;;

  restart)
    $0 stop
    sleep 2
    $0 start
    ;;

  status)
    ACTIVE_PIDS=$(get_actual_pids)
    if [ -n "$ACTIVE_PIDS" ]; then
        sync_pid_file
        FIRST_PID=$(cat "$PID_FILE" 2>/dev/null)
        echo "$QPKG_NAME is running (PID: $FIRST_PID)."
        exit 0
    else
        rm -f "$PID_FILE" 2>/dev/null
        echo "$QPKG_NAME is stopped."
        exit 1
    fi
    ;;

  *)
    echo "Usage: $0 {start|stop|restart|status}"
    exit 1
    ;;
esac

exit 0
