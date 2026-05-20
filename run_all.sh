#!/bin/bash

INTRANET_URL="http://127.0.0.1:8001/index.php?logoff=false"
BACKEND_LOG="/tmp/pertech-local-backend.log"

echo "Starting DB + Server..."
cd /home/pertech/Documents/VCSEL_Prod/pertechindustries-pertech-resources-intranet-fc4df53e8c21 || exit

if ! pgrep -f "php .*127.0.0.1:8001" >/dev/null 2>&1; then
  ./start-local-intranet.sh >"$BACKEND_LOG" 2>&1 &
fi

echo "Waiting for backend..."
sleep 5

echo "Opening Intranet..."
if command -v xdg-open >/dev/null 2>&1; then
  xdg-open "$INTRANET_URL" >/dev/null 2>&1 &
elif command -v gio >/dev/null 2>&1; then
  gio open "$INTRANET_URL" >/dev/null 2>&1 &
else
  echo "Could not find a browser opener. Open this URL manually: $INTRANET_URL"
fi

echo "Starting Electron App..."
cd /home/pertech/Documents/VCSEL_Prod/pertechindustries-linux-production-app-03a74c8aa95e/electron-quick-start || exit

XAUTH=$(ls /run/user/$(id -u)/.mutter-Xwaylandauth.* | head -n1)

DISPLAY=:0 \
XAUTHORITY="$XAUTH" \
DBUS_SESSION_BUS_ADDRESS="unix:path=/run/user/$(id -u)/bus" \
GDK_BACKEND=x11 \
./run-local-vcsel-app.sh
