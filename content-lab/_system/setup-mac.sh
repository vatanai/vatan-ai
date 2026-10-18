#!/bin/bash
# نصب ابزارهای ایجنت مک کارخانه‌ی محتوای وطن (فقط بار اول)
# اجرا از ریشه‌ی پروژه:  bash content-lab/_system/setup-mac.sh
set -e
cd "$(dirname "$0")"
export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
if ! command -v brew >/dev/null; then echo "اول Homebrew را نصب کنید: https://brew.sh"; exit 1; fi
command -v ffmpeg >/dev/null || brew install ffmpeg
command -v node >/dev/null || brew install node
command -v python3 >/dev/null || brew install python
python3 -m pip install --quiet numpy opencv-python-headless 2>/dev/null || python3 -m pip install --quiet --break-system-packages numpy opencv-python-headless || python3 -m pip install --quiet --user numpy opencv-python-headless
(cd render && npm install --no-audit --no-fund)
echo "✓ ایجنت آماده است. از داشبورد ← کارخانه‌ی محتوا ← تنظیمات، دستور روشن کردن را کپی کنید."
