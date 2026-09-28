#!/usr/bin/env python3
"""
Generate ultra-high definition, anti-aliased icons for QNAP QPKG (Lucky Standard & Lucky Wanji).
Uses the 1024x1024 HD reconstructed Lucky rainbow swirl "6" logo as the master asset.
Follows PNG-backed GIF specification:
PNG data with 32-bit RGBA saved directly with .gif extension to avoid 256-color dithering/aliasing on QTS.
Outputs full scale suite: 64, 80, 100, 128, 256 to both icons/ and shared/icons/.
"""

import os
from PIL import Image, ImageDraw, ImageOps, ImageEnhance

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS_DIR = os.path.join(ROOT_DIR, "scripts", "assets")
OFFICIAL_LOGO_PATH = os.path.join(ASSETS_DIR, "lucky_logo.png")
ICONS_DIR = os.path.join(ROOT_DIR, "icons")
SHARED_ICONS_DIR = os.path.join(ROOT_DIR, "shared", "icons")
STATIC_DIR = os.path.join(ROOT_DIR, "shared", "web", "static")

os.makedirs(ICONS_DIR, exist_ok=True)
os.makedirs(SHARED_ICONS_DIR, exist_ok=True)
os.makedirs(STATIC_DIR, exist_ok=True)

def load_official_logo():
    if not os.path.exists(OFFICIAL_LOGO_PATH):
        raise FileNotFoundError(f"Official logo not found at {OFFICIAL_LOGO_PATH}")
    return Image.open(OFFICIAL_LOGO_PATH).convert("RGBA")

def create_lucky_icon(size, is_wanji=False, is_gray=False):
    # 4x 超采样画布渲染，确保在最终缩放时达到完美亚像素平滑抗锯齿
    scale = 4
    canvas_size = size * scale
    canvas = Image.new("RGBA", (canvas_size, canvas_size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(canvas)

    pad = int(canvas_size * 0.04)
    radius = int(canvas_size * 0.20)
    shadow_offset = int(canvas_size * 0.02)

    if is_gray:
        bg_main = (245, 247, 250, 255)
        bg_shadow = (148, 163, 184, 150)
        border_color = (203, 213, 225, 255)
    elif is_wanji:
        bg_main = (20, 24, 33, 255)        # 极客曜石黑
        bg_shadow = (10, 12, 16, 200)
        border_color = (245, 158, 11, 180) # 琥珀流金边框
    else:
        bg_main = (255, 255, 255, 255)    # 纯净晶透白
        bg_shadow = (16, 185, 129, 70)     # 翡翠绿立体柔和微阴影
        border_color = (226, 232, 240, 255)

    # 1. 绘制立体微阴影
    draw.rounded_rectangle(
        [pad, pad + shadow_offset, canvas_size - pad, canvas_size - pad + shadow_offset],
        radius=radius,
        fill=bg_shadow
    )

    # 2. 绘制圆角底板与微边框
    draw.rounded_rectangle(
        [pad, pad, canvas_size - pad, canvas_size - pad],
        radius=radius,
        fill=bg_main,
        outline=border_color,
        width=max(2 * scale, int(canvas_size * 0.015))
    )

    # 3. 缩放并贴入官方超清彩虹“6”涡旋 Logo
    logo = load_official_logo()
    if is_gray:
        # 转灰度
        logo_gray = ImageOps.grayscale(logo)
        logo = Image.merge("RGBA", (logo_gray, logo_gray, logo_gray, logo.split()[3]))
        enhancer = ImageEnhance.Brightness(logo)
        logo = enhancer.enhance(0.9)

    # 优化主体占比：仅保留 10% 呼吸内边距，使主体饱满锐利，避免缩小后模糊发虚
    inner_pad = int(canvas_size * 0.10)
    logo_target_size = canvas_size - inner_pad * 2
    logo_resized = logo.resize((logo_target_size, logo_target_size), Image.Resampling.LANCZOS)

    paste_x = (canvas_size - logo_target_size) // 2
    paste_y = (canvas_size - logo_target_size) // 2 - int(shadow_offset * 0.3)
    canvas.paste(logo_resized, (paste_x, paste_y), logo_resized)

    # 4. 万吉版专属印记：右下角流金微章
    if is_wanji and not is_gray:
        badge_r = int(canvas_size * 0.15)
        bx = canvas_size - pad - badge_r
        by = canvas_size - pad - badge_r
        draw.ellipse(
            [bx - badge_r, by - badge_r, bx + badge_r, by + badge_r],
            fill=(245, 158, 11, 255),
            outline=(255, 255, 255, 240),
            width=max(2 * scale, int(canvas_size * 0.022))
        )
        # 内部流金星印核心
        core_r = int(badge_r * 0.42)
        draw.ellipse(
            [bx - core_r, by - core_r, bx + core_r, by + core_r],
            fill=(254, 243, 199, 255)
        )

    # 5. 高保真超采样下采样
    final_icon = canvas.resize((size, size), Image.Resampling.LANCZOS)
    return final_icon

def save_png_backed_gif(img, filepath):
    # 强制将 32 位全彩无损 PNG 保存为 .gif 后缀，杜绝 QTS 256 色调色板杂色与锯齿
    img.save(filepath, format="PNG")

def save_all_targets(img, filename_base):
    for target_dir in [ICONS_DIR, SHARED_ICONS_DIR]:
        save_png_backed_gif(img, os.path.join(target_dir, f"{filename_base}.gif"))
        img.save(os.path.join(target_dir, f"{filename_base}.png"), format="PNG")

def main():
    print("Generating ultra-HD anti-aliased Lucky icons...")
    sizes = [64, 80, 100, 128, 256]

    # 1. 普通标准版 lucky
    for s in sizes:
        suffix = f"_{s}" if s != 64 else ""
        img = create_lucky_icon(s, is_wanji=False, is_gray=False)
        save_all_targets(img, f"lucky{suffix}")

    img_gray = create_lucky_icon(80, is_wanji=False, is_gray=True)
    save_all_targets(img_gray, "lucky_gray")

    # 2. 全功能万吉版 lucky-wanji
    for s in sizes:
        suffix = f"_{s}" if s != 64 else ""
        img_w = create_lucky_icon(s, is_wanji=True, is_gray=False)
        save_all_targets(img_w, f"lucky-wanji{suffix}")

    img_w_gray = create_lucky_icon(80, is_wanji=True, is_gray=True)
    save_all_targets(img_w_gray, "lucky-wanji_gray")

    # 3. Web 静态资源 (favicon.ico 和 logo.png)
    fav = load_official_logo()
    fav.save(
        os.path.join(STATIC_DIR, "favicon.ico"),
        format="ICO",
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128)]
    )
    
    # 官方全彩超清 logo.png
    fav.save(os.path.join(STATIC_DIR, "logo.png"), format="PNG")

    print(f"All icons successfully generated in {ICONS_DIR}, {SHARED_ICONS_DIR}, and {STATIC_DIR}!")

if __name__ == "__main__":
    main()
