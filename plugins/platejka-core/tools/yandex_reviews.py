#!/usr/bin/env python3
"""Export the reviews present in a Yandex Maps organization's server-rendered page."""

from __future__ import annotations

import argparse
import json
import re
import urllib.request
from urllib.parse import urlencode
from html.parser import HTMLParser
from pathlib import Path


DEFAULT_BUSINESS_ID = '217714971749'


class StateParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.active = False
        self.parts: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        attributes = dict(attrs)
        if tag == 'script' and 'state-view' in (attributes.get('class') or '').split():
            self.active = True

    def handle_endtag(self, tag: str) -> None:
        if tag == 'script':
            self.active = False

    def handle_data(self, data: str) -> None:
        if self.active:
            self.parts.append(data)


def parse_reviews(html: str, business_id: str) -> list[dict[str, object]]:
    parser = StateParser()
    parser.feed(html)
    if not parser.parts:
        raise ValueError('Yandex state-view JSON was not found')
    state = json.loads(''.join(parser.parts))
    try:
        raw_reviews = state['stack'][0]['results']['items'][0]['reviewResults']['reviews']
    except (KeyError, IndexError, TypeError) as exc:
        raise ValueError('Yandex reviewResults were not found') from exc
    if not isinstance(raw_reviews, list):
        raise ValueError('Yandex reviews have an unexpected format')

    reviews: list[dict[str, object]] = []
    for raw in raw_reviews:
        if not isinstance(raw, dict) or str(raw.get('businessId', '')) != business_id:
            continue
        review_id = raw.get('reviewId')
        text = raw.get('text')
        rating = raw.get('rating')
        if not isinstance(review_id, str) or not isinstance(text, str) or not text.strip():
            continue
        if not isinstance(rating, (int, float)) or isinstance(rating, bool) or not 1 <= rating <= 5:
            continue
        author = raw.get('author') if isinstance(raw.get('author'), dict) else {}
        reviews.append({
            'source': 'yandex',
            'source_id': review_id,
            'business_id': business_id,
            'author': str(author.get('name') or ''),
            'text': text.strip(),
            'rating': rating,
            'updated_at': str(raw.get('updatedTime') or ''),
            'source_url': f'https://yandex.ru/maps/org/{business_id}/reviews?{urlencode({"reviews[publicId]": review_id})}',
        })
    return reviews


def fetch_html(business_id: str, page: int = 1) -> str:
    if not re.fullmatch(r'\d+', business_id):
        raise ValueError('Business ID must contain digits only')
    url = f'https://yandex.ru/maps/org/{business_id}/reviews/'
    if page > 1:
        url += f'?page={page}'
    request = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 PlatejkaReviewImport/1.0'})
    with urllib.request.urlopen(request, timeout=25) as response:
        return response.read().decode('utf-8')


def fetch_reviews(business_id: str, page_size: int = 50, max_pages: int = 20) -> list[dict[str, object]]:
    reviews: list[dict[str, object]] = []
    seen: set[str] = set()
    for page in range(1, max_pages + 1):
        batch = parse_reviews(fetch_html(business_id, page), business_id)
        new = [item for item in batch if str(item['source_id']) not in seen]
        if not new:
            break
        reviews.extend(new)
        seen.update(str(item['source_id']) for item in new)
        if len(batch) < page_size:
            break
    return reviews


def latest_reviews(reviews: list[dict[str, object]], limit: int = 9) -> list[dict[str, object]]:
    return sorted(reviews, key=lambda item: str(item.get('updated_at') or ''), reverse=True)[:limit]


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--business-id', default=DEFAULT_BUSINESS_ID)
    parser.add_argument('--html', type=Path, help='Parse saved HTML instead of making a network request')
    parser.add_argument('--limit', type=int, default=9, help='Number of latest reviews to export (default: 9)')
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    if not re.fullmatch(r'\d+', args.business_id):
        parser.error('Business ID must contain digits only')
    if args.limit < 1:
        parser.error('Limit must be greater than zero')
    all_reviews = parse_reviews(args.html.read_text(encoding='utf-8'), args.business_id) if args.html else fetch_reviews(args.business_id)
    reviews = latest_reviews(all_reviews, args.limit)
    args.output.write_text(json.dumps(reviews, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'Exported {len(reviews)} reviews to {args.output}')


if __name__ == '__main__':
    main()
