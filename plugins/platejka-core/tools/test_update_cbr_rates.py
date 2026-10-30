import json
import os
import stat
import subprocess
import sys
import tempfile
import textwrap
import unittest
from pathlib import Path


THEME_DIR = Path(__file__).resolve().parents[1]
UPDATER = THEME_DIR / "tools" / "update_cbr_rates.py"


class UpdateCbrRatesTest(unittest.TestCase):
    def test_writes_direct_rub_rates_from_cbrapi(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            package = root / "client" / "cbrapi"
            package.mkdir(parents=True)
            (package / "__init__.py").write_text(
                textwrap.dedent(
                    """
                    from datetime import date

                    class FakePeriod:
                        def to_timestamp(self):
                            return date(2026, 9, 29)

                    class FakeIloc:
                        def __init__(self, value):
                            self.value = value

                        def __getitem__(self, index):
                            return self.value

                    class FakeSeries:
                        empty = False

                        def __init__(self, value):
                            self.iloc = FakeIloc(value)
                            self.index = [FakePeriod()]

                        def dropna(self):
                            return self

                    def get_time_series(symbol, first_date, last_date, period="D"):
                        return FakeSeries({"USD": 80.0, "EUR": 90.0, "CNY": 11.0}[symbol])
                    """
                ),
                encoding="utf-8",
            )
            output = root / "exchange-rates.json"

            result = subprocess.run(
                [
                    sys.executable,
                    str(UPDATER),
                    "--client-path",
                    str(root / "client"),
                    "--output",
                    str(output),
                    "--today",
                    "2026-09-29",
                ],
                capture_output=True,
                text=True,
                check=False,
            )

            self.assertEqual(0, result.returncode, result.stderr)
            payload = json.loads(output.read_text(encoding="utf-8"))
            self.assertEqual(1, payload["schema_version"])
            self.assertEqual("RUB", payload["base"])
            self.assertEqual("2026-09-29", payload["effective_date"])
            self.assertEqual({"USD": 80.0, "EUR": 90.0, "CNY": 11.0}, payload["rates"])
            if os.name != "nt":
                self.assertEqual(0o644, stat.S_IMODE(output.stat().st_mode))

    def test_keeps_previous_cache_when_cbrapi_fails(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            package = root / "client" / "cbrapi"
            package.mkdir(parents=True)
            (package / "__init__.py").write_text(
                "def get_time_series(*args, **kwargs):\n    raise RuntimeError('CBR unavailable')\n",
                encoding="utf-8",
            )
            output = root / "exchange-rates.json"
            previous = '{"rates":{"USD":79,"EUR":89,"CNY":10}}\n'
            output.write_text(previous, encoding="utf-8")

            result = subprocess.run(
                [
                    sys.executable,
                    str(UPDATER),
                    "--client-path",
                    str(root / "client"),
                    "--output",
                    str(output),
                    "--today",
                    "2026-09-29",
                ],
                capture_output=True,
                text=True,
                check=False,
            )

            self.assertNotEqual(0, result.returncode)
            self.assertEqual(previous, output.read_text(encoding="utf-8"))

    def test_uses_latest_date_with_real_quotes_instead_of_padded_today(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            package = root / "client" / "cbrapi"
            package.mkdir(parents=True)
            (package / "__init__.py").write_text(
                textwrap.dedent(
                    """
                    from datetime import date

                    class FakeIloc:
                        def __init__(self, value):
                            self.value = value

                        def __getitem__(self, index):
                            return self.value

                    class FakeSeries:
                        def __init__(self, value=None, quote_date=None):
                            self.empty = value is None
                            self.iloc = FakeIloc(value)
                            self.index = [] if quote_date is None else [date.fromisoformat(quote_date)]

                        def dropna(self):
                            return self

                    def get_time_series(symbol, first_date, last_date, period="D"):
                        rates = {"USD": 80.0, "EUR": 90.0, "CNY": 11.0}
                        if first_date != last_date:
                            return FakeSeries(rates[symbol], "2026-09-29")
                        if first_date == "2026-09-28":
                            return FakeSeries(rates[symbol], "2026-09-28")
                        return FakeSeries()
                    """
                ),
                encoding="utf-8",
            )
            output = root / "exchange-rates.json"

            result = subprocess.run(
                [
                    sys.executable,
                    str(UPDATER),
                    "--client-path",
                    str(root / "client"),
                    "--output",
                    str(output),
                    "--today",
                    "2026-09-29",
                ],
                capture_output=True,
                text=True,
                check=False,
            )

            self.assertEqual(0, result.returncode, result.stderr)
            payload = json.loads(output.read_text(encoding="utf-8"))
            self.assertEqual("2026-09-28", payload["effective_date"])


if __name__ == "__main__":
    unittest.main()
