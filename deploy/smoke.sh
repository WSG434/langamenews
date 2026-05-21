#!/usr/bin/env bash
# Smoke-checks for the deployed application.
# Usage: bash deploy/smoke.sh https://langamenews.ru
# Exits 0 if all checks pass, 1 otherwise.
set -euo pipefail

BASE="${1:-http://localhost}"
FAIL=0

check() {
    local desc="$1" url="$2" expected_code="$3" grep_pattern="${4:-}"
    local code
    code=$(curl -s -o /tmp/smoke_body -w "%{http_code}" --max-time 10 -L "$url")
    if [ "$code" != "$expected_code" ]; then
        echo "FAIL [$desc] — expected HTTP $expected_code, got $code ($url)"
        FAIL=1
        return
    fi
    if [ -n "$grep_pattern" ] && ! grep -q "$grep_pattern" /tmp/smoke_body; then
        echo "FAIL [$desc] — response missing: $grep_pattern ($url)"
        FAIL=1
        return
    fi
    echo "OK   [$desc]"
}

check_sse() {
    local desc="$1" url="$2"
    local ct
    ct=$(curl -s -o /dev/null -w "%{content_type}" --max-time 5 "$url" 2>/dev/null || true)
    if echo "$ct" | grep -q "text/event-stream"; then
        echo "OK   [$desc]"
    else
        echo "FAIL [$desc] — expected text/event-stream, got: $ct"
        FAIL=1
    fi
}

echo "==> Smoke checks: $BASE"

check "GET /"                   "$BASE/"              302
check "GET /login"              "$BASE/login"         200 "_csrf_token"
check "GET /register"           "$BASE/register"      200 "form"
check "GET /admin (no auth)"    "$BASE/admin"         302
check "GET /news (no auth)"     "$BASE/news"          302

echo ""
if [ "$FAIL" -eq 0 ]; then
    echo "All checks passed."
else
    echo "Some checks failed."
    exit 1
fi
