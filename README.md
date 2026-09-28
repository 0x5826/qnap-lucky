# QNAP Lucky 原生插件（标准版 / 全功能万吉版）

本项目致力于为威联通 QNAP NAS 提供官方原生 QPKG 格式的 **Lucky（大吉/万吉）** 插件，实现零额外虚拟化损耗、极致性能、配置持久化与 QTS 桌面深度融合。

---

## 🎯 核心特性

- **双版本支持**：
  - **普通标准版 (`lucky`)**：精简轻量，集成 DDNS、IPv6/IPv4 端口转发、反向代理、STUN 穿透、ACME 自动申请证书、WOL 网络唤醒、计划任务；
  - **全功能万吉版 (`lucky-wanji`)**：全量内置 gRPC 反代、Coraza WAF 安全引擎、FileBrowser 网页文件管理、rclone 多网盘挂载、Cloudflare Tunnel 穿透与 DLNA 服务；
- **生命周期状态闭环管理**：
  - 针对 Lucky 在 Web 后台修改配置或重启时“以新进程自启并退出旧进程”的特性，实现**真实进程指纹联合探测 + PID 动态自愈同步 + 全景级联彻底停止**，杜绝脱管、假死与无法停止问题；
- **QTS 桌面管理面板**：
  - 桌面图标点击以原生窗口打开轻量管理面板，实时呈现版本、系统架构、真实 PID、CPU 与内存利用率；
  - **动态原生后台直达**：自动捕获 Lucky 实际配置的后台监听端口（默认 16601）与 SafeURL 安全入口，即使用户在后台修改端口也不会失联；
- **应急运维指令台（实时回显控制台）**：
  - 面板集成了官方常用的紧急运维指令，执行状态与控制台输出实时回显：
    - 🔑 **重置登录凭据**：一键重置为默认账号密码 `666:666`；
    - 🚪 **取消安全入口**：一键清除 SafeURL，防止失联；
    - 🔓 **解除登录锁定**：解除防爆破封禁限制；
    - 🛡️ **禁用 2FA 双重认证**：解决手机验证器丢失；
    - 🔄 **软重启服务**；
- **多架构原生适配**：支持 `x86_64` (Intel/AMD) 与 `arm_64` (AArch64)；
- **数据持久化保障**：配置文件与规则存储于 `${QPKG_ROOT}/conf`，升级覆盖零数据丢失；
- **高清立体视觉**：遵循 32 位全彩无损 **PNG-backed GIF** 规范，杜绝 256 色 GIF 锯齿边缘。

---

## 📦 版本下载与选择

请前往 [Releases](../../releases) 页面下载对应版本和架构的 `.qpkg` 安装包：

| 版本名 | 安装包命名规则 | 适用场景与硬件 |
| :--- | :--- | :--- |
| **标准版** | `lucky_<version>_<arch>.qpkg` | 追求极简与轻量，主要使用 DDNS、端口转发、反向代理、证书申请与 WOL 的用户 |
| **全功能万吉版** | `lucky-wanji_<version>_<arch>.qpkg` | 需要使用 WAF 防火墙、文件管理、网盘挂载或 Cloudflare Tunnel 的全功能家庭网络中心用户 |

> **架构说明**：
> - Intel / AMD 处理器机型请下载 `x86_64`；
> - ARM 64 位（如 Annapurna Labs、Realtek RTD1296 等）请下载 `arm_64`。

---

## 🛠️ 安装与使用

### 1. 手动安装
1. 打开 QTS 系统的 **App Center（App 开发者中心）**；
2. 点击右上角齿轮旁边的 **“从本地安装” (加号图标)**；
3. 选择下载好的 `.qpkg` 文件，确认安装；
4. 安装完成后，QTS 桌面将出现 Lucky 原生图标。

### 2. 访问管理
- **方式一（推荐）**：点击 QTS 桌面上的 **Lucky** 图标，打开轻量管理面板，随后点击 **“🚀 进入 Lucky 完整后台”**；
- **方式二**：直接在浏览器输入 `http://<NAS_IP>:16601` 访问 Lucky 原生控制台；
- **默认登录凭据**：账号 `666`，密码 `666`。

---

## 💻 命令行运维 (SSH 终端)

本插件自动注入全局命令软链接，支持管理员通过 SSH 终端快速运维：

```bash
# 查看 Lucky 运行状态
lucky -info

# 查看当前核心配置 (监听端口与安全入口)
lucky -baseConfInfo -cd /share/CACHEDEV1_DATA/.qpkg/lucky/conf

# 重置后台登录密码为 666:666
lucky -rResetUser

# 取消安全入口限制
lucky -rCancelSafeURL

# 解除登录锁定
lucky -rUnlock

# 禁用 2FA
lucky -rDisable2FA

# 软重启 Lucky
lucky -rRestart
```

服务启停控制（以 `lucky` 为例）：
```bash
/etc/init.d/lucky.sh start    # 启动
/etc/init.d/lucky.sh stop     # 停止 (级联强杀彻底销毁)
/etc/init.d/lucky.sh restart  # 重启
/etc/init.d/lucky.sh status   # 检查状态
```

---

## 📄 开源与致谢

- Lucky 官方开源仓库: [https://github.com/gdy666/lucky](https://github.com/gdy666/lucky)
- Lucky 官方网站: [https://lucky666.cn/](https://lucky666.cn/)
- QNAP QDK 打包工具链: [https://github.com/qnap-dev/QDK](https://github.com/qnap-dev/QDK)
