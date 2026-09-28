#!/usr/bin/env python3
"""
Generate high quality icons for QNAP QPKG (Lucky Standard & Lucky Wanji).
Uses the official Lucky rainbow swirl "6" logo as the core asset.
Follows PNG-backed GIF specification:
PNG data with 32-bit RGBA saved directly with .gif extension to avoid 256-color dithering/aliasing on QTS.
"""

import os
from PIL import Image, ImageDraw, ImageOps, ImageEnhance

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS_DIR = os.path.join(ROOT_DIR, "scripts", "assets")
OFFICIAL_LOGO_PATH = os.path.join(ASSETS_DIR, "lucky_logo.png")
ICONS_DIR = os.path.join(ROOT_DIR, "icons")
STATIC_DIR = os.path.join(ROOT_DIR, "shared", "web", "static")

os.makedirs(ICONS_DIR, exist_ok=True)
os.makedirs(STATIC_DIR, exist_ok=True)

def load_official_logo():
    if not os.path.exists(OFFICIAL_LOGO_PATH):
        raise FileNotFoundError(f"Official logo not found at {OFFICIAL_LOGO_PATH}")
    return Image.open(OFFICIAL_LOGO_PATH).convert("RGBA")

def create_lucky_icon(size, is_wanji=False, is_gray=False):
    scale = 4
    canvas_size = size * scale
    canvas = Image.new("RGBA", (canvas_size, canvas_size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(canvas)

    pad = int(canvas_size * 0.05)
    radius = int(canvas_size * 0.22)
    shadow_offset = int(canvas_size * 0.025)

    if is_gray:
        bg_main = (241, 245, 249, 255)
        bg_shadow = (148, 163, 184, 180)
        border_color = (203, 213, 225, 255)
    elif is_wanji:
        bg_main = (24, 28, 36, 255)       # 曜石黑
        bg_shadow = (10, 12, 16, 220)
        border_color = (245, 158, 11, 160) # 琥珀金边框
    else:
        bg_main = (255, 255, 255, 255)    # 纯净白
        bg_shadow = (16, 185, 129, 90)     # 翡翠绿阴影
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

    # 3. 缩放并贴入官方彩虹“6”涡旋 Logo
    logo = load_official_logo()
    if is_gray:
        # 转灰度
        logo_gray = ImageOps.grayscale(logo)
        logo = Image.merge("RGBA", (logo_gray, logo_gray, logo_gray, logo.split()[3]))
        enhancer = ImageEnhance.Brightness(logo)
        logo = enhancer.enhance(0.9)

    inner_pad = int(canvas_size * 0.20)
    logo_target_size = canvas_size - inner_pad * 2
    logo_resized = logo.resize((logo_target_size, logo_target_size), Image.Resampling.LANCZOS)

    paste_x = (canvas_size - logo_target_size) // 2
    paste_y = (canvas_size - logo_target_size) // 2 - int(shadow_offset * 0.3)
    canvas.paste(logo_resized, (paste_x, paste_y), logo_resized)

    # 4. 万吉版专属印记：右下角金色微章与“万”字标记
    if is_wanji and not is_gray:
        badge_r = int(canvas_size * 0.16)
        bx = canvas_size - pad - badge_r
        by = canvas_size - pad - badge_r
        draw.ellipse(
            [bx - badge_r, by - badge_r, bx + badge_r, by + badge_r],
            fill=(245, 158, 11, 255),
            outline=(255, 255, 255, 230),
            width=max(2 * scale, int(canvas_size * 0.025))
        )
        # 内部流金星印
        core_r = int(badge_r * 0.45)
        draw.ellipse(
            [bx - core_r, by - core_r, bx + core_r, by + core_r],
            fill=(254, 243, 199, 255)
        )

    # 5. 高保真超采样下采样
    final_icon = canvas.resize((size, size), Image.Resampling.LANCZOS)
    return final_icon

def save_png_backed_gif(img, filepath):
    img.save(filepath, format="PNG")

def main():
    print("Generating official Lucky icons (PNG-backed GIF)...")
    sizes = [64, 80, 100]

    # 1. 普通标准版 lucky
    for s in sizes:
        suffix = f"_{s}" if s != 64 else ""
        img = create_lucky_icon(s, is_wanji=False, is_gray=False)
        save_png_backed_gif(img, os.path.join(ICONS_DIR, f"lucky{suffix}.gif"))
        img.save(os.path.join(ICONS_DIR, f"lucky{suffix}.png"), format="PNG")

    img_gray = create_lucky_icon(64, is_wanji=False, is_gray=True)
    save_png_backed_gif(img_gray, os.path.join(ICONS_DIR, "lucky_gray.gif"))
    img_gray.save(os.path.join(ICONS_DIR, "lucky_gray.png"), format="PNG")

    # 2. 全功能万吉版 lucky-wanji
    for s in sizes:
        suffix = f"_{s}" if s != 64 else ""
        img_w = create_lucky_icon(s, is_wanji=True, is_gray=False)
        save_png_backed_gif(img_w, os.path.join(ICONS_DIR, f"lucky-wanji{suffix}.gif"))
        img_w.save(os.path.join(ICONS_DIR, f"lucky-wanji{suffix}.png"), format="PNG")

    img_w_gray = create_lucky_icon(64, is_wanji=True, is_gray=True)
    save_png_backed_gif(img_w_gray, os.path.join(ICONS_DIR, "lucky-wanji_gray.gif"))
    img_w_gray.save(os.path.join(ICONS_DIR, "lucky-wanji_gray.png"), format="PNG")

    # 3. Web 静态资源 (favicon.ico 和 logo.png)
    fav = load_official_logo().resize((64, 64), Image.Resampling.LANCZOS)
    fav.save(os.path.join(STATIC_DIR, "favicon.ico"), format="ICO", sizes=[(16, 16), (32, 32), (48, 48), (64, 64)])
    
    # 官方全彩 logo.png
    logo_web = load_official_logo()
    logo_web.save(os.path.join(STATIC_DIR, "logo.png"), format="PNG")

    print("All icons successfully generated using the official Lucky logo!")

if __name__ == "__main__":
    main()
