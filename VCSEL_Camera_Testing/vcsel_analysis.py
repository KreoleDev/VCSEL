import cv2
import numpy as np
import time
import csv
import subprocess
import tempfile
import os
import re
import shutil
from datetime import datetime

# Tkinter for confirmation dialogs
import tkinter as tk
from tkinter import messagebox

# Tkinter root (hidden) so we can show message boxes
tk_root = tk.Tk()
tk_root.withdraw()                    # Hide the main window
tk_root.attributes("-topmost", True)  # Keep dialogs on top

# Prefer the external VCSEL USB camera when it is present.
PREFERRED_CAMERA_NAME = "USB 2.0 PC Cam"


def get_camera_source(preferred_name=PREFERRED_CAMERA_NAME):
    video_root = "/sys/class/video4linux"
    try:
        for entry in sorted(os.listdir(video_root)):
            name_path = os.path.join(video_root, entry, "name")
            index_path = os.path.join(video_root, entry, "index")

            if not os.path.exists(name_path):
                continue

            with open(name_path, "r", encoding="utf-8") as name_file:
                camera_name = name_file.read().strip()

            camera_index = None
            if os.path.exists(index_path):
                with open(index_path, "r", encoding="utf-8") as index_file:
                    camera_index = index_file.read().strip()

            if camera_name == preferred_name and camera_index == "0":
                camera_path = f"/dev/{entry}"
                print(f"Using preferred camera: {camera_name} ({camera_path})")
                return camera_path
    except OSError as exc:
        print(f"Could not inspect camera devices: {exc}")

    print("Preferred VCSEL camera not found. Falling back to camera index 0.")
    return 0


# Initialize the USB camera
cap = cv2.VideoCapture(get_camera_source())
cap.set(3, 640)
cap.set(4, 480)
time.sleep(2)

# CSV log setup
log_filename = "vcsel_log.csv"
if not os.path.exists(log_filename):
    with open(log_filename, mode='w', newline='') as file:
        writer = csv.writer(file)
        # Add "Acquired" to the header
        writer.writerow(["Timestamp", "Brightness", "Area", "Lot Number", "Serial Number", "Acquired"])

# Initial values
lot_number = "LOT0000"
lot_number_input_mode = False
temp_lot_number = ""
serial_number = "UNKNOWN"
serial_number_input_mode = False
temp_serial_number = ""

# Acquired mode initialization
acquired_mode = "Auto"  # Default mode is "Auto"
last_saved_serial = None
last_auto_scan_time = 0.0
auto_save_armed = False
status_message = ""
status_message_until = 0.0
AUTO_SCAN_INTERVAL = 0.75
STATUS_MESSAGE_DURATION = 8.0

# Thresholds moved to globals so analysis and pre-save checks match
BRIGHTNESS_MIN = 159
BRIGHTNESS_MAX = 220
AREA_MIN = 599
AREA_MAX = 4500

SERIAL_REGEX = re.compile(r"Serial\s*#?\s*(\d{5,})", re.IGNORECASE)
CROP_GEOMETRY_REGEX = re.compile(r"(\d+)x(\d+)\+(-?\d+)\+(-?\d+)")
WINDOW_ID_REGEX = re.compile(r"Window id:\s+(\S+)")


# --- Serial Number OCR / Screen Capture ---
def get_window_info(window_title):
    try:
        output = subprocess.check_output(
            ["xwininfo", "-name", window_title],
            stderr=subprocess.STDOUT
        ).decode("utf-8", errors="ignore")
    except subprocess.CalledProcessError as e:
        print(f"Window lookup failed for '{window_title}': {e}")
        return None

    window_id = None
    x = y = width = height = None
    for line in output.splitlines():
        if "Window id:" in line:
            match = WINDOW_ID_REGEX.search(line)
            if match:
                window_id = match.group(1)
        elif "Absolute upper-left X:" in line:
            x = int(line.split(":")[1].strip())
        elif "Absolute upper-left Y:" in line:
            y = int(line.split(":")[1].strip())
        elif "Width:" in line:
            width = int(line.split(":")[1].strip())
        elif "Height:" in line:
            height = int(line.split(":")[1].strip())

    if None in [x, y, width, height]:
        print(f"Failed to parse window geometry for '{window_title}'.")
        return None

    return {
        "id": window_id,
        "crop_geometry": f"{width}x{height}+{x}+{y}",
    }


def parse_crop_geometry(crop_geometry):
    match = CROP_GEOMETRY_REGEX.fullmatch(crop_geometry or "")
    if not match:
        return None

    width, height, x, y = map(int, match.groups())
    return x, y, width, height


def capture_with_gnome_shell(tmp_path, crop_geometry=None):
    if os.environ.get("XDG_SESSION_TYPE", "").lower() != "wayland":
        return False

    if shutil.which("gdbus") is None:
        return False

    base_command = [
        "gdbus", "call", "--session",
        "--dest", "org.gnome.Shell.Screenshot",
        "--object-path", "/org/gnome/Shell/Screenshot",
    ]

    try:
        if crop_geometry:
            parsed = parse_crop_geometry(crop_geometry)
            if not parsed:
                return False

            x, y, width, height = parsed
            command = base_command + [
                "--method", "org.gnome.Shell.Screenshot.ScreenshotArea",
                str(x), str(y), str(width), str(height),
                "false", tmp_path
            ]
        else:
            command = base_command + [
                "--method", "org.gnome.Shell.Screenshot.Screenshot",
                "false", "false", tmp_path
            ]

        result = subprocess.run(
            command,
            check=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT
        )
        print("Wayland screenshot output:", result.stdout.decode("utf-8", errors="ignore").strip())
        return os.path.exists(tmp_path) and os.path.getsize(tmp_path) > 0
    except subprocess.CalledProcessError as e:
        output = e.stdout.decode("utf-8", errors="ignore") if e.stdout else str(e)
        print("Wayland screenshot failed:", output)
        return False


def capture_with_imagemagick(tmp_path, crop_geometry=None, window_id=None):
    command = ["import", "-window", window_id or "root"]
    if crop_geometry and not window_id:
        command.extend(["-crop", crop_geometry])
    command.append(tmp_path)

    try:
        subprocess.run(command, check=True)
        return os.path.exists(tmp_path) and os.path.getsize(tmp_path) > 0
    except subprocess.CalledProcessError as e:
        print("ImageMagick screenshot failed:", e)
        return False


def capture_screen_image(tmp_path, crop_geometry=None, window_id=None):
    if window_id and capture_with_imagemagick(tmp_path, window_id=window_id):
        return True

    if capture_with_gnome_shell(tmp_path, crop_geometry):
        return True

    return capture_with_imagemagick(tmp_path, crop_geometry)


def capture_ocr_text(crop_geometry=None, window_id=None):
    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".png") as tmpfile:
            tmp_path = tmpfile.name

        if not capture_screen_image(tmp_path, crop_geometry, window_id):
            return None

        return subprocess.check_output(
            ["tesseract", tmp_path, "stdout", "--psm", "11"]
        ).decode("utf-8", errors="ignore")
    except subprocess.CalledProcessError as e:
        print("Error during capture or OCR:", e)
        return None
    finally:
        if 'tmp_path' in locals() and os.path.exists(tmp_path):
            os.remove(tmp_path)


def extract_serial_from_text(text):
    if not text:
        return None

    print("OCR Text:", text)
    match = SERIAL_REGEX.search(text)
    return match.group(1) if match else None


def extract_serial_from_window(window_title="Pertech Production"):
    window_info = get_window_info(window_title)
    if window_info:
        serial = extract_serial_from_text(capture_ocr_text(window_id=window_info.get("id")))
        if serial:
            return serial

        serial = extract_serial_from_text(capture_ocr_text(window_info["crop_geometry"]))
        if serial:
            return serial

    print("Falling back to full-screen OCR for serial lookup.")
    return extract_serial_from_text(capture_ocr_text())


def is_in_range_or_zero(value, vmin, vmax):
    """Matches your on-screen white-text rule: in range OR value == 0."""
    return (vmin <= value <= vmax) or (value == 0)


def analyze_light_dot(image):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (15, 15), 0)
    _, thresh = cv2.threshold(blurred, 150, 255, cv2.THRESH_BINARY)
    contours, _ = cv2.findContours(thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    overlay = image.copy()
    height, width = image.shape[:2]
    cv2.line(overlay, (0, 0), (width, height), (255, 255, 255), 1)
    cv2.line(overlay, (width, 0), (0, height), (255, 255, 255), 1)
    alpha = 0.3
    image = cv2.addWeighted(overlay, alpha, image, 1 - alpha, 0)

    brightness = 0
    area = 0

    if contours:
        largest = max(contours, key=cv2.contourArea)
        area = cv2.contourArea(largest)

        mask = np.zeros_like(gray)
        cv2.drawContours(mask, [largest], -1, 255, thickness=cv2.FILLED)
        brightness = cv2.mean(gray, mask=mask)[0]

        # Ignore very dim spots
        if brightness <= 20:
            brightness = 0
            area = 0
        else:
            cv2.drawContours(image, [largest], -1, (0, 255, 0), 2)

    # Threshold coloring (your existing behavior)
    brightness_color = (255, 255, 255) if is_in_range_or_zero(brightness, BRIGHTNESS_MIN, BRIGHTNESS_MAX) else (0, 0, 255)
    area_color = (255, 255, 255) if is_in_range_or_zero(area, AREA_MIN, AREA_MAX) else (0, 0, 255)

    cv2.putText(image, f"Brightness: {brightness:.2f}", (10, 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, brightness_color, 2)

    cv2.putText(image, f"Area: {area:.2f}", (10, 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, area_color, 2)

    return image, brightness, area


# Helper to run the two pre-save checks before allowing manual save
def confirm_before_save(brightness_value, area_value, lot_number_value):
    # Brightness out-of-range (NOTE: zeros do NOT trigger this warning, matching your prior behavior)
    brightness_out = not (BRIGHTNESS_MIN <= brightness_value <= BRIGHTNESS_MAX or brightness_value == 0)
    if brightness_out:
        proceed = messagebox.askyesno(
            "Brightness Out of Range",
            "The value for the Brightness is out of set perimeters.\n\n"
            "Would you like to continue to save the information?"
        )
        if not proceed:
            return False

    # Area out-of-range (NOTE: zeros do NOT trigger this warning, matching your prior behavior)
    area_out = not (AREA_MIN <= area_value <= AREA_MAX or area_value == 0)
    if area_out:
        proceed = messagebox.askyesno(
            "Area Out of Range",
            "The value for the Area is out of set perimeters.\n\n"
            "Would you like to continue to save the information?"
        )
        if not proceed:
            return False

    return True


def set_status_message(message, duration=STATUS_MESSAGE_DURATION):
    global status_message, status_message_until
    status_message = message
    status_message_until = time.time() + duration


def beep_on_save():
    try:
        tk_root.bell()
    except tk.TclError:
        print("\a", end="", flush=True)


def get_window_visibility(window_name):
    try:
        return cv2.getWindowProperty(window_name, cv2.WND_PROP_VISIBLE)
    except cv2.error:
        return -1


def cleanup_app():
    try:
        if cap.isOpened():
            cap.release()
    except Exception:
        pass

    try:
        cv2.destroyAllWindows()
    except cv2.error:
        pass

    try:
        tk_root.destroy()
    except tk.TclError:
        pass


def save_measurement(brightness_value, area_value, serial_value, acquired_value):
    global last_saved_serial

    with open(log_filename, mode='a', newline='') as file:
        writer = csv.writer(file)
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        writer.writerow([
            timestamp,
            f"{brightness_value:.2f}",
            f"{area_value:.2f}",
            lot_number,
            serial_value,
            acquired_value
        ])

    last_saved_serial = serial_value
    set_status_message(f"Saved Serial {serial_value}")
    beep_on_save()
    print(
        f"Saved: Brightness={brightness_value:.2f}, Area={area_value:.2f}, "
        f"Lot={lot_number}, Serial={serial_value}, Acquired={acquired_value}"
    )


def auto_detect_and_save(brightness_value, area_value, save_ready):
    global serial_number, last_auto_scan_time, auto_save_armed

    if acquired_mode != "Auto":
        auto_save_armed = False
        return

    if not save_ready:
        auto_save_armed = True
        return

    now = time.time()
    if now - last_auto_scan_time < AUTO_SCAN_INTERVAL:
        return

    last_auto_scan_time = now
    new_serial = extract_serial_from_window("Pertech Production")
    if not new_serial:
        return

    serial_number = new_serial
    if not auto_save_armed:
        return

    if new_serial == last_saved_serial:
        return

    save_measurement(brightness_value, area_value, new_serial, acquired_mode)
    auto_save_armed = False


# Create OpenCV window
WINDOW_NAME = "VCSEL Light Analysis"
cv2.namedWindow(WINDOW_NAME)
window_has_been_visible = False

# --- Main Loop ---
try:
    while True:
        ret, frame = cap.read()
        if not ret:
            break

        processed_frame, current_brightness, current_area = analyze_light_dot(frame)

        # Auto-save is only armed when both values are in range and non-zero.
        brightness_ready = (BRIGHTNESS_MIN <= current_brightness <= BRIGHTNESS_MAX) and (current_brightness != 0)
        area_ready = (AREA_MIN <= current_area <= AREA_MAX) and (current_area != 0)
        auto_save_ready = brightness_ready and area_ready

        auto_detect_and_save(current_brightness, current_area, auto_save_ready)

        # Show lot number input or display
        if lot_number_input_mode:
            lot_display = f"Enter Lot Number: {temp_lot_number}_"
        else:
            lot_display = f"Lot Number: {lot_number}"
        cv2.putText(processed_frame, lot_display, (10, 90),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 0), 2)

        # Show serial number input or display
        if serial_number_input_mode:
            serial_display = f"Enter Serial Number: {temp_serial_number}_"
        else:
            serial_display = f"Serial Number: {serial_number}"
        cv2.putText(processed_frame, serial_display, (10, 120),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 255), 2)

        # Show Acquired mode status
        cv2.putText(processed_frame, f"Acquired: {acquired_mode}", (10, 150),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 0, 255), 2)

        if time.time() <= status_message_until:
            cv2.putText(processed_frame, status_message, (10, 180),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)

        close_hint = "Press 'q' to close"
        hint_position = (10, processed_frame.shape[0] - 20)
        cv2.putText(processed_frame, close_hint, hint_position,
                    cv2.FONT_HERSHEY_SIMPLEX, 0.65, (0, 0, 0), 4)
        cv2.putText(processed_frame, close_hint, hint_position,
                    cv2.FONT_HERSHEY_SIMPLEX, 0.65, (255, 255, 255), 1)

        cv2.imshow(WINDOW_NAME, processed_frame)

        key = cv2.waitKey(1) & 0xFF
        window_visibility = get_window_visibility(WINDOW_NAME)
        if window_visibility > 0:
            window_has_been_visible = True
        elif window_has_been_visible:
            print("Window closed by user. Exiting application.")
            break

        # Handle lot number input
        if lot_number_input_mode:
            if key in range(ord('0'), ord('9') + 1) or key in range(ord('A'), ord('Z') + 1) or key in range(ord('a'), ord('z') + 1):
                temp_lot_number += chr(key)
            elif key in [8, 127]:  # Backspace
                temp_lot_number = temp_lot_number[:-1]
            elif key == 13:  # Enter
                if temp_lot_number.strip() != "":
                    lot_number = temp_lot_number
                    print(f"Lot number updated: {lot_number}")
                lot_number_input_mode = False
                temp_lot_number = ""
            elif key == 27:  # Esc
                lot_number_input_mode = False
                temp_lot_number = ""
            continue

        # Handle serial number input
        if serial_number_input_mode:
            if key in range(ord('0'), ord('9') + 1) or key in range(ord('A'), ord('Z') + 1) or key in range(ord('a'), ord('z') + 1):
                temp_serial_number += chr(key)
            elif key in [8, 127]:  # Backspace
                temp_serial_number = temp_serial_number[:-1]
            elif key == 13:  # Enter
                if temp_serial_number.strip() != "":
                    serial_number = temp_serial_number
                    print(f"Serial number updated: {serial_number}")
                serial_number_input_mode = False
                temp_serial_number = ""
            elif key == 27:  # Esc
                serial_number_input_mode = False
                temp_serial_number = ""
            continue

        # --- Main key handling ---
        if key == ord('q'):
            break
        elif key == ord('n'):
            serial_number_input_mode = True
            temp_serial_number = ""

        # Handle "Acquired" mode change
        elif key == ord('a'):
            acquired_mode = "Auto"
            auto_save_armed = False
            print("Acquired mode set to Auto.")
        elif key == ord('m'):
            acquired_mode = "Manual"
            auto_save_armed = False
            print("Acquired mode set to Manual.")

        # Manual save key remains available as a fallback.
        elif key == ord('s'):
            skip_save = False

            # Manual save still keeps the range confirmations.
            if not confirm_before_save(current_brightness, current_area, lot_number):
                print("Save cancelled by user.")
                skip_save = True

            if not skip_save:
                # Auto / Manual serial handling
                if acquired_mode == "Auto":
                    previous_serial_on_screen = serial_number
                    new_serial = extract_serial_from_window("Pertech Production")

                    if not new_serial:
                        serial_number = "UNKNOWN"
                        set_status_message("Serial not found")
                        print("Serial number not found; save blocked.")
                        skip_save = True
                    else:
                        if previous_serial_on_screen == new_serial and previous_serial_on_screen != "UNKNOWN":
                            set_status_message(f"Duplicate Serial {new_serial}")
                            print("Duplicate serial detected; save blocked.")
                            skip_save = True
                        else:
                            serial_number = new_serial
                            print(f"Auto-extracted serial number: {serial_number}")
                else:
                    # Manual mode: skip OCR and use currently entered serial number
                    print("Manual mode: Using manually entered serial number.")

            if not skip_save:
                save_measurement(current_brightness, current_area, serial_number, acquired_mode)
finally:
    cleanup_app()
