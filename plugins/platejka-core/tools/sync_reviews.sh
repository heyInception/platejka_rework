#!/usr/bin/env bash
set -euo pipefail

tools_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
wp_root="${WP_ROOT:-$(cd -- "$tools_dir/../../../.." && pwd)}"
python_bin="${PYTHON_BIN:-/usr/local/bin/python}"
wp_bin="${WP_BIN:-wp}"

if ! command -v "$python_bin" >/dev/null 2>&1; then
    printf 'Python not found: %s\n' "$python_bin" >&2
    exit 1
fi
if ! command -v "$wp_bin" >/dev/null 2>&1; then
    printf 'WP-CLI not found: %s\n' "$wp_bin" >&2
    exit 1
fi
if [[ ! -f "$wp_root/wp-config.php" ]]; then
    printf 'WordPress root not found: %s\n' "$wp_root" >&2
    exit 1
fi

tmp_dir="$(mktemp -d)"
trap 'rm -rf -- "$tmp_dir"' EXIT

"$python_bin" "$tools_dir/yandex_reviews.py" --limit 9 --output "$tmp_dir/reviews.json"
"$python_bin" "$tools_dir/review_counts.py" --output "$tmp_dir/counts.json"
"$wp_bin" --path="$wp_root" platejka reviews import "$tmp_dir/reviews.json"
"$wp_bin" --path="$wp_root" platejka reviews import-counts "$tmp_dir/counts.json"
