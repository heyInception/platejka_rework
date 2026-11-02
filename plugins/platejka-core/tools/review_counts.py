#!/usr/bin/env python3
"""Read current review counts from the three public organization pages."""

from __future__ import annotations

import argparse
import ast
import json
import re
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

from yandex_reviews import StateParser, fetch_html


URLS = {
    '2gis': 'https://go.2gis.com/qUUel',
    'yell': 'https://www.yell.ru/moscow/com/internet-buxgalteriya-platezhka_14472104/',
}


def fetch(url: str) -> str:
    request = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(request, timeout=25) as response:
        return response.read().decode('utf-8', 'replace')


def parse_yandex_count(html: str) -> int:
    parser = StateParser()
    parser.feed(html)
    if not parser.parts:
        raise ValueError('Yandex state was not found')
    state = json.loads(''.join(parser.parts))
    count = state['stack'][0]['results']['items'][0]['ratingData']['reviewCount']
    if not isinstance(count, int) or count < 0:
        raise ValueError('Invalid Yandex review count')
    return count


def parse_yell_count(html: str) -> int:
    match = re.search(r'<meta\s+itemprop="reviewCount"\s+content="(\d+)"', html)
    if not match:
        raise ValueError('Yell review count was not found')
    return int(match.group(1))


def parse_2gis_count(html: str) -> int:
    match = re.search(r"var initialState = JSON\.parse\('((?:\\.|[^'])*)'\)", html)
    if not match:
        raise ValueError('2GIS state was not found')
    state = json.loads(ast.literal_eval("'" + match.group(1) + "'"))
    profile = state['data']['entity']['profile']['70000001094550301']['data']
    reviews = profile['reviews']
    count = reviews.get('general_review_count')
    if count is None and 'general_rating' not in reviews:
        return 0
    if not isinstance(count, int) or count < 0:
        raise ValueError('Invalid 2GIS review count')
    return count


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    counts = {
        'yandex': parse_yandex_count(fetch_html('217714971749')),
        '2gis': parse_2gis_count(fetch(URLS['2gis'])),
        'yell': parse_yell_count(fetch(URLS['yell'])),
    }
    result = {'counts': counts, 'updated_at': datetime.now(timezone.utc).isoformat()}
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(counts)


if __name__ == '__main__':
    main()
