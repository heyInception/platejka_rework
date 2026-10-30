#!/usr/bin/env python3
"""Update the calculator's RUB exchange-rate snapshot through cbrapi."""

from __future__ import annotations

import argparse
import json
import math
import os
import sys
import tempfile
from datetime import date, datetime, timedelta, timezone
from pathlib import Path


CURRENCIES = ("USD", "EUR", "CNY")


def index_date(value: object) -> str:
    if hasattr(value, "to_timestamp"):
        value = value.to_timestamp()
    if hasattr(value, "date"):
        value = value.date()
    return value.isoformat()


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", required=True, type=Path, help="Destination JSON cache path")
    parser.add_argument(
        "--client-path",
        type=Path,
        help="Directory containing the local cbrapi package (repository root)",
    )
    parser.add_argument("--today", type=date.fromisoformat, default=date.today(), help=argparse.SUPPRESS)
    return parser.parse_args()


def load_rates(client_path: Path | None, today: date) -> tuple[dict[str, float], str]:
    if client_path is not None:
        sys.path.insert(0, str(client_path.resolve()))

    import cbrapi  # pylint: disable=import-outside-toplevel

    for days_ago in range(15):
        candidate = (today - timedelta(days=days_ago)).isoformat()
        rates: dict[str, float] = {}

        for currency in CURRENCIES:
            series = cbrapi.get_time_series(currency, candidate, candidate, period="D").dropna()
            if series.empty or index_date(series.index[-1]) != candidate:
                break

            value = float(series.iloc[-1])
            if not math.isfinite(value) or value <= 0:
                raise RuntimeError(f"CBR returned an invalid {currency}/RUB rate")
            rates[currency] = value

        if len(rates) == len(CURRENCIES):
            return rates, candidate

    raise RuntimeError("CBR returned no complete USD/EUR/CNY quote date in the last 15 days")


def write_snapshot(output: Path, rates: dict[str, float], effective_date: str) -> None:
    payload = {
        "schema_version": 1,
        "base": "RUB",
        "effective_date": effective_date,
        "updated_at": datetime.now(timezone.utc).isoformat(timespec="seconds").replace("+00:00", "Z"),
        "rates": rates,
    }
    output.parent.mkdir(parents=True, exist_ok=True)

    temporary_path: Path | None = None
    try:
        with tempfile.NamedTemporaryFile(
            "w",
            encoding="utf-8",
            dir=output.parent,
            prefix=f".{output.name}.",
            suffix=".tmp",
            delete=False,
        ) as temporary:
            json.dump(payload, temporary, ensure_ascii=False, indent=2)
            temporary.write("\n")
            temporary.flush()
            os.fsync(temporary.fileno())
            temporary_path = Path(temporary.name)
        os.chmod(temporary_path, 0o644)
        os.replace(temporary_path, output)
    finally:
        if temporary_path is not None and temporary_path.exists():
            temporary_path.unlink()


def main() -> int:
    args = parse_args()
    rates, effective_date = load_rates(args.client_path, args.today)
    write_snapshot(args.output.resolve(), rates, effective_date)
    print(f"Updated {args.output} with CBR rates for {effective_date}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
