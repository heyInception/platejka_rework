import unittest
import json

from review_counts import parse_2gis_count, parse_yandex_count, parse_yell_count


class CountParsingTest(unittest.TestCase):
    def test_yandex_count_is_total_not_loaded_page_size(self):
        state = {'stack': [{'results': {'items': [{'ratingData': {'reviewCount': 93},
                                                    'reviewResults': {'reviews': [{}]}}]}}]}
        html = '<script class="state-view">' + json.dumps(state) + '</script>'
        self.assertEqual(parse_yandex_count(html), 93)

    def test_yell_count(self):
        self.assertEqual(parse_yell_count('<meta itemprop="reviewCount" content="57"/>'), 57)

    def test_yell_missing_count_fails(self):
        with self.assertRaises(ValueError):
            parse_yell_count('<html></html>')

    def test_2gis_missing_state_fails(self):
        with self.assertRaises(ValueError):
            parse_2gis_count('<html></html>')

    def test_2gis_empty_profile_is_zero(self):
        state = {'data': {'entity': {'profile': {'70000001094550301': {
            'data': {'reviews': {'is_reviewable': True}}
        }}}}}
        escaped = json.dumps(state).replace('"', '\\"')
        self.assertEqual(parse_2gis_count("var initialState = JSON.parse('" + escaped + "')"), 0)


if __name__ == '__main__':
    unittest.main()
