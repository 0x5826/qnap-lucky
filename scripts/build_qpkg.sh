#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_DIR="$ROOT_DIR/build"
OUTPUT_DIR="$ROOT_DIR/release"

EDITION="${1:-all}"  # standard, wanji, all

echo "=== Lucky QPKG 本地构建打包工具 (Edition: $EDITION) ==="

mkdir -p "$BUILD_DIR" "$OUTPUT_DIR"

build_single() {
    local edition_name="$1"      # lucky or lucky-wanji
    local cfg_file="$2"          # qpkg.cfg or qpkg.cfg.wanji
    local arch="$3"              # x86_64 or arm_64
    local bin_source="$4"        # lucky or lucky_wanji

    echo "--------------------------------------------------------"
    echo "正在构建: ${edition_name} 架构: ${arch}..."
    local build_tmp="$BUILD_DIR/${edition_name}_${arch}"
    rm -rf "$build_tmp"
    mkdir -p "$build_tmp/${arch}" "$build_tmp/icons" "$build_tmp/shared"

    cp -r "$ROOT_DIR/package_routines" "$build_tmp/"
    cp -r "$ROOT_DIR/$cfg_file" "$build_tmp/qpkg.cfg"
    cp -r "$ROOT_DIR/icons/"* "$build_tmp/icons/"
    cp -r "$ROOT_DIR/shared/"* "$build_tmp/shared/"
    
    if [ -f "$ROOT_DIR/${arch}/${bin_source}" ]; then
        cp -f "$ROOT_DIR/${arch}/${bin_source}" "$build_tmp/${arch}/lucky"
        chmod +x "$build_tmp/${arch}/lucky"
    else
        echo "警告：未找到 $ROOT_DIR/${arch}/${bin_source}，请先执行 download_binaries.sh！"
        return 1
    fi

    chmod +x "$build_tmp/package_routines" "$build_tmp/shared/lucky.sh" || true

    if grep -q "^QPKG_DIR=" "$build_tmp/qpkg.cfg"; then
        sed -i "s/^QPKG_DIR=.*/QPKG_DIR=\"${arch}\"/" "$build_tmp/qpkg.cfg" 2>/dev/null || sed -i "" "s/^QPKG_DIR=.*/QPKG_DIR=\"${arch}\"/" "$build_tmp/qpkg.cfg"
    else
        echo "QPKG_DIR=\"${arch}\"" >> "$build_tmp/qpkg.cfg"
    fi

    if which qbuild >/dev/null 2>&1; then
        (cd "$build_tmp" && qbuild --build-dir "$OUTPUT_DIR")
    elif which docker >/dev/null 2>&1; then
        docker run --rm -v "$build_tmp":/project -v "$OUTPUT_DIR":/output -w /project qdk-builder qbuild --output-dir /output
    else
        echo "错误：未检测到 qbuild 或 docker 工具，无法完成打包。"
        return 1
    fi

    echo "✓ ${edition_name} (${arch}) 构建成功！"
}

if [ "$EDITION" = "standard" ] || [ "$EDITION" = "all" ]; then
    build_single "lucky" "qpkg.cfg" "x86_64" "lucky" || true
    build_single "lucky" "qpkg.cfg" "arm_64" "lucky" || true
fi

if [ "$EDITION" = "wanji" ] || [ "$EDITION" = "all" ]; then
    build_single "lucky-wanji" "qpkg.cfg.wanji" "x86_64" "lucky_wanji" || true
    build_single "lucky-wanji" "qpkg.cfg.wanji" "arm_64" "lucky_wanji" || true
fi

echo "=== 构建完成！产物位于: $OUTPUT_DIR ==="
ls -lh "$OUTPUT_DIR"/*.qpkg 2>/dev/null || true
