# Job Position Copy Application Link

## What changed

The Admin > Recruitment > Job Positions table now contains an **Application Link** column.

- Open positions display a **Copy Link** button.
- Clicking it copies that position's public application URL, such as `/apply/5`.
- The button temporarily changes to **Copied!**.
- Closed positions display a **Closed** badge instead of a copy button.
- The existing public application route and application form are reused.
- No database migration is required.

## Install

1. Extract this ZIP directly into the Laravel project root.
2. Allow Windows to merge and replace the matching folders/files.
3. In the project terminal, run:

```powershell
& "C:\php84\php.exe" artisan optimize:clear
```

4. Open the admin panel and go to **Recruitment > Job Positions**.

## Network note

The copied URL uses the host through which the admin panel is opened. For applicants on another computer or tablet, open the admin panel through the laptop's LAN IP, not `127.0.0.1`.

Example:

```text
http://192.168.1.20:8000/admin
```

Then the copied application link will use the same reachable host:

```text
http://192.168.1.20:8000/apply/5
```

Start Laravel for LAN access with:

```powershell
& "C:\php84\php.exe" artisan serve --host=0.0.0.0 --port=8000
```

If Laravel reports `Call to undefined function mb_split()`, enable this line in `C:\php84\php.ini` and restart the terminal/server:

```ini
extension=mbstring
```
