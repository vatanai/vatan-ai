#!/usr/bin/env bash
# به‌روزرسانی اسکیل‌های سئو از گیت‌هاب (فقط فایل‌های متنی؛ بدون اسکریپت اجرایی)
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
for pair in "AgriciDaniel/claude-seo:claude-seo" "AgriciDaniel/claude-blog:claude-blog" "zubair-trabzada/geo-seo-claude:geo-seo"; do
  repo="${pair%%:*}"; dest="${pair##*:}"
  git clone --depth 1 -q "https://github.com/$repo.git" "$TMP/$dest"
  for top in skills agents; do
    [ -d "$TMP/$dest/$top" ] || continue
    (cd "$TMP/$dest" && find "$top" -type f \( -name '*.md' -o -name '*.txt' \)) | while read -r f; do
      mkdir -p "$DIR/$dest/$(dirname "$f")"
      cp "$TMP/$dest/$f" "$DIR/$dest/$f"
    done
  done
  cp "$TMP/$dest/LICENSE" "$DIR/$dest/LICENSE"
  echo "https://github.com/$repo.git @ $(git -C "$TMP/$dest" log -1 --format='%h %cs')" > "$DIR/$dest/UPSTREAM.txt"
  echo "✔ $dest ← $(cat "$DIR/$dest/UPSTREAM.txt")"
done
