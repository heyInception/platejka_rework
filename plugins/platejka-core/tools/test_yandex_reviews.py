import json
import unittest
from unittest.mock import patch
from yandex_reviews import parse_reviews, fetch_reviews, latest_reviews


class ParseReviewsTest(unittest.TestCase):
    def test_extracts_reviews_from_state(self):
        state = {'stack': [{'results': {'items': [{'reviewResults': {'reviews': [
            {'reviewId': 'abc', 'businessId': '123', 'author': {'name': 'Анна'},
             'text': '  Хорошо  ', 'rating': 5, 'updatedTime': '2026-10-01T00:00:00Z'}
        ]}}]}}]}
        html = '<script type="application/json" class="state-view">' + json.dumps(state) + '</script>'
        self.assertEqual(parse_reviews(html, '123'), [{
            'source': 'yandex', 'source_id': 'abc', 'business_id': '123',
            'author': 'Анна', 'text': 'Хорошо', 'rating': 5,
            'updated_at': '2026-10-01T00:00:00Z',
            'source_url': 'https://yandex.ru/maps/org/123/reviews?reviews%5BpublicId%5D=abc',
        }])

    def test_rejects_missing_state(self):
        with self.assertRaises(ValueError):
            parse_reviews('<html></html>', '123')

    def test_skips_other_business_and_empty_text(self):
        state = {'stack': [{'results': {'items': [{'reviewResults': {'reviews': [
            {'reviewId': 'a', 'businessId': 'other', 'text': 'Wrong'},
            {'reviewId': 'b', 'businessId': '123', 'text': '   '},
        ]}}]}}]}
        html = '<script class="state-view" type="application/json">' + json.dumps(state) + '</script>'
        self.assertEqual(parse_reviews(html, '123'), [])

    def test_fetches_next_page_and_deduplicates_ids(self):
        def page(ids):
            state = {'stack': [{'results': {'items': [{'reviewResults': {'reviews': [
                {'reviewId': value, 'businessId': '123', 'text': value, 'rating': 5}
                for value in ids
            ]}}]}}]}
            return '<script class="state-view">' + json.dumps(state) + '</script>'

        with patch('yandex_reviews.fetch_html', side_effect=[page(['a', 'b']), page(['b', 'c']), page([])]):
            self.assertEqual([item['source_id'] for item in fetch_reviews('123', page_size=2)], ['a', 'b', 'c'])

    def test_latest_reviews_sorts_across_pages_before_limiting(self):
        reviews = [
            {'source_id': 'older', 'updated_at': '2024-01-01T00:00:00Z'},
            {'source_id': 'newest', 'updated_at': '2026-01-01T00:00:00Z'},
            {'source_id': 'middle', 'updated_at': '2025-01-01T00:00:00Z'},
        ]
        self.assertEqual([item['source_id'] for item in latest_reviews(reviews, 2)], ['newest', 'middle'])


if __name__ == '__main__':
    unittest.main()
