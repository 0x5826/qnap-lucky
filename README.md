# QNAP Lucky 原生插件（普通版 / 全功能万吉版）

本项目致力于为威联通 QNAP NAS 提供官方原生 QPKG 格式的 **Lucky（万吉）** 插件，实现零虚拟化损耗、极致性能与 QTS 桌面深度融合。

## 📖 项目文档与方案
- **架构方案与详细功能需求**：详见 [ARCHITECTURE_AND_REQUIREMENTS.md](ARCHITECTURE_AND_REQUIREMENTS.md)。

## 🎯 核心特性规划
- **双版本支持**：
  - **普通标准版 (`lucky`)**：精简轻量，集成 DDNS、IPv6/IPv4 端口转发、反向代理、STUN 穿透、ACME 自动证书、WOL、计划任务；
  - **全功能万吉版 (`lucky-wanji`)**：全量集成 gRPC 反代、Coraza WAF 安全引擎、FileBrowser 网页文件管理、rclone 多网盘挂载、Cloudflare Tunnel 穿透与 DLNA 服务；
- **多架构原生适配**：支持 `x86_64` (Intel/AMD) 与 `arm_64` (AArch64)；
- **配置持久化**：数据独立保存在 `${QPKG_DIR}/conf`，升级重装零数据丢失；
- **桌面与运维**：QTS 原生立体高清图标（PNG-backed GIF），自动注入 CLI 软链接便于 SSH 终端快速运维。
