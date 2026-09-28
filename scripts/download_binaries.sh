#!/bin/bash
set -e

VERSION="${1:-2.27.2}"
VERSION="${VERSION#v}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

echo "=== 准备拉取 Lucky & Lucky-Wanji v${VERSION} 二进制文件 ==="

TMP_DIR=$(mktemp -d /tmp/lucky_bin_XXXXXX)
trap 'rm -rf "$TMP_DIR"' EXIT

BASE_URL="https://release.66666.host/v${VERSION}"
GH_BASE_URL="https://github.com/gdy666/lucky/releases/download/v${VERSION}"

mkdir -p "$ROOT_DIR/x86_64" "$ROOT_DIR/arm_64"

download_and_extract() {
    local target_file="$1"
    local url1="$2"
    local url2="$3"
    local extract_name="$4"
    local dest_path="$5"

    echo "正在下载: $target_file..."
    if curl -sSL -f --connect-timeout 10 -m 300 "$url1" -o "$TMP_DIR/$target_file" 2>/dev/null; then
        echo "从主镜像站下载成功: $url1"
    elif [ -n "$url2" ] && curl -sSL -f --connect-timeout 10 -m 300 "$url2" -o "$TMP_DIR/$target_file" 2>/dev/null; then
        echo "从备用镜像站下载成功: $url2"
    else
        echo "错误：无法下载 $target_file，请检查网络或版本号是否存在！"
        return 1
    fi

    tar -xzf "$TMP_DIR/$target_file" -C "$TMP_DIR"
    if [ -f "$TMP_DIR/$extract_name" ]; then
        mv -f "$TMP_DIR/$extract_name" "$dest_path"
        chmod +x "$dest_path"
        echo "已部署到: $dest_path"
    else
        echo "错误：解压产物中未找到 $extract_name！"
        return 1
    fi
}

# 1. 普通版 x86_64
download_and_extract \
    "lucky_${VERSION}_Linux_x86_64.tar.gz" \
    "${BASE_URL}/${VERSION}_lucky/lucky_${VERSION}_Linux_x86_64.tar.gz" \
    "${GH_BASE_URL}/lucky_${VERSION}_Linux_x86_64.tar.gz" \
    "lucky" \
    "$ROOT_DIR/x86_64/lucky"

# 2. 普通版 arm_64
download_and_extract \
    "lucky_${VERSION}_Linux_arm64.tar.gz" \
    "${BASE_URL}/${VERSION}_lucky/lucky_${VERSION}_Linux_arm64.tar.gz" \
    "${GH_BASE_URL}/lucky_${VERSION}_Linux_arm64.tar.gz" \
    "lucky" \
    "$ROOT_DIR/arm_64/lucky"

# 3. 万吉版 x86_64
download_and_extract \
    "lucky_${VERSION}_Linux_x86_64_wanji.tar.gz" \
    "${BASE_URL}/${VERSION}_wanji/lucky_${VERSION}_Linux_x86_64_wanji.tar.gz" \
    "" \
    "lucky" \
    "$ROOT_DIR/x86_64/lucky_wanji"

# 4. 万吉版 arm_64
download_and_extract \
    "lucky_${VERSION}_Linux_arm64_wanji.tar.gz" \
    "${BASE_URL}/${VERSION}_wanji/lucky_${VERSION}_Linux_arm64_wanji.tar.gz" \
    "" \
    "lucky" \
    "$ROOT_DIR/arm_64/lucky_wanji"

echo "=== 所有架构与版本二进制下载校验完毕 ==="
ls -lh "$ROOT_DIR/x86_64" "$ROOT_DIR/arm_64"
