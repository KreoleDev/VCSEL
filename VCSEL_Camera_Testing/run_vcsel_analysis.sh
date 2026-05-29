#!/usr/bin/env bash

set -u

APP_DIR="/home/pertech/Documents/VCSEL_Camera_Testing"
APP_HOME="${HOME:-/home/pertech}"
PYTHON_BIN="/usr/bin/python3"

missing_cmds=()
for cmd in tesseract xwininfo import; do
  if ! command -v "$cmd" >/dev/null 2>&1; then
    missing_cmds+=("$cmd")
  fi
done

missing_py="$("$PYTHON_BIN" -c 'import importlib.util; missing = [name for name in ("cv2", "numpy", "tkinter") if importlib.util.find_spec(name) is None]; print(" ".join(missing))')"

if [[ -n "$missing_py" || ${#missing_cmds[@]} -gt 0 ]]; then
  echo "Missing VCSEL runtime dependencies."
  if [[ -n "$missing_py" ]]; then
    echo "Python modules: $missing_py"
  fi
  if [[ ${#missing_cmds[@]} -gt 0 ]]; then
    echo "System commands: ${missing_cmds[*]}"
  fi
  echo
  echo "Install these Ubuntu packages, then run again:"
  echo "  sudo apt-get install python3-opencv python3-tk tesseract-ocr imagemagick"
  exit 1
fi

cd "$APP_HOME" || exit 1
exec "$PYTHON_BIN" "$APP_DIR/vcsel_analysis.py"
