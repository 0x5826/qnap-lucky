#!/usr/bin/env python3
"""
Generate high quality icons for QNAP QPKG (Lucky Standard & Lucky Wanji).
Follows PNG-backed GIF specification:
PNG data with 32-bit RGBA saved directly with .gif extension to avoid 256-color dithering/aliasing on QTS.
"""

import os
import math
from PIL import Image, ImageDraw, ImageFont

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ICONS_DIR = os.path.join(ROOT_DIR, "icons")
STATIC_DIR = os.path.join(ROOT_DIR, "shared", "web", "static")

os.makedirs(ICONS_DIR, exist_ok=True)
os.makedirs(STATIC_DIR, exist_ok=True)

def draw_lucky_icon(size, is_wanji=False, is_gray=False):
    # 使用更高倍率渲染以实现超级抗锯齿 (SSAA x4)
    scale = 4
    canvas_size = size * scale
    img = Image.new("RGBA", (canvas_size, canvas_size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    pad = int(canvas_size * 0.06)
    radius = int(canvas_size * 0.22)

    if is_gray:
        bg_main = (100, 116, 139, 255)
        bg_shadow = (71, 85, 105, 255)
        accent_color = (203, 213, 225, 255)
        inner_ring = (148, 163, 184, 255)
        symbol_color = (241, 245, 249, 255)
    elif is_wanji:
        # 万吉版：尊贵黑金、深曜石与流金质感
        bg_main = (24, 28, 36, 255)
        bg_shadow = (10, 12, 16, 255)
        accent_color = (245, 158, 11, 255)     # Amber-500
        inner_ring = (217, 119, 6, 255)       # Amber-600
        symbol_color = (254, 243, 199, 255)   # Amber-100
    else:
        # 普通标准版：Lucky 经典翡翠青绿、通透清爽质感
        bg_main = (16, 185, 129, 255)         # Emerald-500
        bg_shadow = (5, 150, 105, 255)        # Emerald-600
        accent_color = (52, 211, 153, 255)    # Emerald-400
        inner_ring = (110, 231, 183, 255)     # Emerald-300
        symbol_color = (255, 255, 255, 255)

    # 1. 绘制带有微妙立体阴影的底板圆角矩形
    shadow_offset = int(canvas_size * 0.02)
    draw.rounded_rectangle(
        [pad, pad + shadow_offset, canvas_size - pad, canvas_size - pad + shadow_offset],
        radius=radius,
        fill=bg_shadow
    )
    draw.rounded_rectangle(
        [pad, pad, canvas_size - pad, canvas_size - pad],
        radius=radius,
        fill=bg_main
    )

    center_x = canvas_size / 2.0
    center_y = canvas_size / 2.0 - shadow_offset / 2.0

    # 2. 绘制内嵌立体环状装饰轨道
    ring_radius = int(canvas_size * 0.32)
    ring_width = max(3 * scale, int(canvas_size * 0.035))
    draw.ellipse(
        [center_x - ring_radius, center_y - ring_radius,
         center_x + ring_radius, center_y + ring_radius],
        outline=inner_ring,
        width=ring_width
    )

    # 3. 绘制 Lucky 核心四叶草/网联拓扑与“666”几何意象
    # 4 个方向的四叶幸运花瓣 / 网联圆环
    clover_r = int(canvas_size * 0.13)
    clover_dist = int(canvas_size * 0.12)
    offsets = [
        (0, -clover_dist),
        (clover_dist, 0),
        (0, clover_dist),
        (-clover_dist, 0)
    ]

    for ox, oy in offsets:
        draw.ellipse(
            [center_x + ox - clover_r, center_y + oy - clover_r,
             center_x + ox + clover_r, center_y + oy + clover_r],
            fill=accent_color
        )

    # 中心连接光核
    core_r = int(canvas_size * 0.11)
    draw.ellipse(
        [center_x - core_r, center_y - core_r,
         center_x + core_r, center_y + core_r],
        fill=symbol_color
    )

    # 万吉版专属徽章印记：右上角金色角标 "W" / 万吉特殊光圈
    if is_wanji and not is_gray:
        badge_r = int(canvas_size * 0.14)
        badge_x = canvas_size - pad - badge_r
        badge_y = pad + badge_r
        draw.ellipse(
            [badge_x - badge_r, badge_y - badge_r, badge_x + badge_r, badge_y + badge_r],
            fill=(245, 158, 11, 255),
            outline=(255, 255, 255, 220),
            width=max(2 * scale, int(canvas_size * 0.02))
        )
        # 徽章中心星芒圆点
        draw.ellipse(
            [badge_x - int(badge_r*0.4), badge_y - int(badge_r*0.4),
             badge_x + int(badge_r*0.4), badge_y + int(badge_r*0.4)],
            fill=(255, 255, 255, 255)
        )

    # 4. 高质量下采样至目标尺寸 (抗锯齿优化)
    final_img = img.resize((size, size), Image.Resampling.LANCZOS)
    return final_img

def save_png_backed_gif(img, filepath):
    # 直接以 PNG 格式保存，即使后缀是 .gif
    img.save(filepath, format="PNG")

def main():
    print("Generating QNAP Lucky icons (PNG-backed GIF)...")
    sizes = [64, 80, 100]

    # 1. 普通标准版 lucky
    for s in sizes:
        suffix = f"_{s}" if s != 64 else ""
        img = draw_lucky_icon(s, is_wanji=False, is_gray=False)
        save_png_backed_gif(img, os.path.join(ICONS_DIR, f"lucky{suffix}.gif"))
        img.save(os.path.join(ICONS_DIR, f"lucky{suffix}.png"), format="PNG")

    img_gray = draw_lucky_icon(64, is_wanji=False, is_gray=True)
    save_png_backed_gif(img_gray, os.path.join(ICONS_DIR, "lucky_gray.gif"))
    img_gray.save(os.path.join(ICONS_DIR, "lucky_gray.png"), format="PNG")

    # 2. 全功能万吉版 lucky-wanji
    for s in sizes:
        suffix = f"_{s}" if s != 64 else ""
        img_w = draw_lucky_icon(s, is_wanji=True, is_gray=False)
        save_png_backed_gif(img_w, os.path.join(ICONS_DIR, f"lucky-wanji{suffix}.gif"))
        img_w.save(os.path.join(ICONS_DIR, f"lucky-wanji{suffix}.png"), format="PNG")

    img_w_gray = draw_lucky_icon(64, is_wanji=True, is_gray=True)
    save_png_backed_gif(img_w_gray, os.path.join(ICONS_DIR, "lucky-wanji_gray.gif"))
    img_w_gray.save(os.path.join(ICONS_DIR, "lucky-wanji_gray.png"), format="PNG")

    # 3. Favicon (32x32)
    fav = draw_lucky_icon(32, is_wanji=False, is_gray=False)
    fav.save(os.path.join(STATIC_DIR, "favicon.ico"), format="ICO", sizes=[(32, 32)])

    print("All icons successfully generated in icons/ and shared/web/static/favicon.ico")

if __name__ == "__main__":
    main()
