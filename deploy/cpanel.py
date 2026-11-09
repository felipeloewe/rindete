#!/usr/bin/env python3
"""Publish this tree to app.rindete.cl through the cPanel API.

Reads RINDETE_API_KEY from the environment. The cPanel user is rindete on
170.239.64.192. pidetufactura.com is never contacted.
"""

from __future__ import annotations

import argparse
import json
import os
import secrets
import shutil
import ssl
import subprocess
import tempfile
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

HOST = "170.239.64.192"
USER = "rindete"
HOME = "/home/rindete"
APP_DIR = f"{HOME}/public_html/app.rindete.cl"
DOCROOT = "public_html/app.rindete.cl/public"
BASE = f"https://{HOST}:2083"
ROOT = Path(__file__).resolve().parents[1]
SKIP_DIRS = {".git", "tests", "build", ".phpunit.cache"}
SKIP_FILES = {".env", ".env.local"}


def main() -> None:
    parser = argparse.ArgumentParser(description="Deploy rindete to app.rindete.cl")
    parser.add_argument("--dry-run", action="store_true", help="Build the archive and stop before uploading")
    args = parser.parse_args()
    token = os.environ.get("RINDETE_API_KEY", "")
    if not args.dry_run and not token:
        raise SystemExit("Falta RINDETE_API_KEY")

    with tempfile.TemporaryDirectory(prefix="rindete-deploy-") as tmp:
        stage = Path(tmp) / "app"
        copy_tree(ROOT, stage)
        subprocess.run(
            ["composer", "install", "--no-dev", "--optimize-autoloader", "--no-interaction", "--no-scripts"],
            cwd=stage,
            check=True,
        )
        archive = Path(tmp) / "rindete.zip"
        zip_tree(stage, archive)
        print(f"archive_bytes={archive.stat().st_size}")
        if args.dry_run:
            print("dry_run")
            return
        publish(token, archive)


def copy_tree(source: Path, dest: Path) -> None:
    def ignore(directory: str, names: list[str]) -> set[str]:
        skipped = set()
        for name in names:
            if name in SKIP_DIRS or name in SKIP_FILES or name == "vendor":
                skipped.add(name)
        return skipped

    shutil.copytree(source, dest, ignore=ignore)
    env_file = dest / ".env"
    if env_file.exists():
        env_file.unlink()


def zip_tree(source: Path, archive: Path) -> None:
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED) as bundle:
        for path in source.rglob("*"):
            if path.name in SKIP_FILES or ".env" in path.parts:
                continue
            bundle.write(path, path.relative_to(source).as_posix())


class Cpanel:
    def __init__(self, token: str) -> None:
        self.token = token
        self.ctx = ssl._create_unverified_context()

    def call(self, path: str, params: dict | None = None, method: str = "GET", data: bytes | None = None, headers: dict | None = None, timeout: int = 120):
        url = BASE + path
        hdrs = {"Authorization": f"cpanel {USER}:{self.token}"}
        if headers:
            hdrs.update(headers)
        body = data
        if params and method == "GET":
            url += "?" + urllib.parse.urlencode(params)
        elif params and data is None and method == "POST":
            body = urllib.parse.urlencode(params).encode()
            hdrs["Content-Type"] = "application/x-www-form-urlencoded"
        request = urllib.request.Request(url, data=body, headers=hdrs, method=method)
        try:
            with urllib.request.urlopen(request, context=self.ctx, timeout=timeout) as response:
                raw = response.read().decode()
        except Exception as error:
            raw = error.read().decode("utf-8", "replace") if hasattr(error, "read") else str(error)
        raw = raw.replace(self.token, "***")
        try:
            return json.loads(raw)
        except json.JSONDecodeError:
            return {"raw": raw[:500]}

    def api2(self, module: str, func: str, params: dict | None = None):
        query = {
            "cpanel_jsonapi_apiversion": "2",
            "cpanel_jsonapi_module": module,
            "cpanel_jsonapi_func": func,
        }
        if params:
            query.update(params)
        return self.call("/json-api/cpanel", query)

    def fileop(self, **params):
        query = {
            "cpanel_jsonapi_apiversion": "2",
            "cpanel_jsonapi_module": "Fileman",
            "cpanel_jsonapi_func": "fileop",
            "doubledecode": "0",
        }
        query.update(params)
        return self.call("/json-api/cpanel", query)

    def upload(self, directory: str, filename: str, payload: bytes) -> None:
        boundary = "----RindeteDeploy" + secrets.token_hex(8)
        head = (
            f"--{boundary}\r\nContent-Disposition: form-data; name=\"dir\"\r\n\r\n{directory}\r\n"
            f"--{boundary}\r\nContent-Disposition: form-data; name=\"file-1\"; filename=\"{filename}\"\r\n"
            "Content-Type: application/zip\r\n\r\n"
        ).encode()
        body = head + payload + f"\r\n--{boundary}--\r\n".encode()
        result = self.call(
            "/execute/Fileman/upload_files",
            method="POST",
            data=body,
            headers={"Content-Type": f"multipart/form-data; boundary={boundary}"},
            timeout=180,
        )
        if not isinstance(result, dict) or result.get("status") != 1:
            raise SystemExit("No se pudo subir el archivo")

    def read(self, directory: str, filename: str) -> str | None:
        result = self.call("/execute/Fileman/get_file_content", {"dir": directory, "file": filename})
        if not isinstance(result, dict) or result.get("status") != 1:
            return None
        data = result.get("data") or {}
        content = data.get("content")
        return content if isinstance(content, str) else None

    def write(self, directory: str, filename: str, content: str) -> None:
        result = self.call(
            "/execute/Fileman/save_file_content",
            {"dir": directory, "file": filename, "content": content, "charset": "utf-8"},
            method="POST",
        )
        if not isinstance(result, dict) or result.get("status") != 1:
            raise SystemExit(f"No se pudo escribir {filename}")


def publish(token: str, archive: Path) -> None:
    api = Cpanel(token)
    existing_env = api.read(APP_DIR, ".env")
    if existing_env is None:
        existing_env = env_from_legacy(api)
    for relative in ("includes", "template", "public"):
        result = api.fileop(op="trash", sourcefiles=f"public_html/app.rindete.cl/{relative}")
        print(f"trash {relative} {fileop_ok(result)}")

    remote_zip = "tmp/rindete-ci.zip"
    api.upload(f"{HOME}/tmp", "rindete-ci.zip", archive.read_bytes())
    extracted = api.fileop(op="extract", sourcefiles=remote_zip, destfiles=APP_DIR)
    print(f"extract {fileop_ok(extracted)}")
    api.fileop(op="trash", sourcefiles=remote_zip)

    if existing_env is None:
        raise SystemExit("No hay .env ni includes/config.php para armar la base")
    api.write(APP_DIR, ".env", existing_env)
    api.write(
        f"{APP_DIR}/public",
        ".user.ini",
        'error_log = "/home/rindete/logs/php.error.log"\nlog_errors = On\n',
    )
    for relative in ("writable", "writable/cache", "writable/logs", "writable/session", "writable/uploads"):
        api.fileop(op="chmod", sourcefiles=f"public_html/app.rindete.cl/{relative}", metadata="0775")

    docroot = api.call("/execute/SubDomain/changedocroot", {"domain": "app.rindete.cl", "docroot": DOCROOT})
    print(f"docroot {docroot.get('status') if isinstance(docroot, dict) else 'error'}")
    migrate(api)
    check_site()


def fileop_ok(result) -> str:
    try:
        row = result["cpanelresult"]["data"][0]
    except (KeyError, IndexError, TypeError):
        return "unknown"
    return "ok" if row.get("result") == 1 else str(row.get("err", "fail"))[:120]


def env_from_legacy(api: Cpanel) -> str | None:
    raw = api.read(f"{APP_DIR}/includes", "config.php")
    if not raw:
        return None
    values = {}
    for key in ("db_host", "db_name", "db_user", "db_pass", "db_port"):
        marker = f"'{key}'"
        start = raw.find(marker)
        if start < 0:
            return None
        arrow = raw.find("=>", start)
        chunk = raw[arrow + 2 : raw.find("\n", arrow)].strip().rstrip(",").strip()
        if len(chunk) >= 2 and chunk[0] == "'" and chunk[-1] == "'":
            chunk = chunk[1:-1]
        values[key] = chunk
    key = secrets.token_hex(32)
    return "\n".join([
        "CI_ENVIRONMENT = production",
        "app.baseURL = 'https://app.rindete.cl/'",
        f"database.default.hostname = {values['db_host'] or 'localhost'}",
        f"database.default.database = {values['db_name']}",
        f"database.default.username = {values['db_user']}",
        f"database.default.password = {values['db_pass']}",
        "database.default.DBDriver = MySQLi",
        f"database.default.port = {values['db_port'] or '3306'}",
        "database.default.charset = utf8mb4",
        "database.default.DBCollat = utf8mb4_unicode_ci",
        f"encryption.key = hex2bin:{key}",
        "",
    ])


def migrate(api: Cpanel) -> None:
    """spark refuses to run under PHP-FPM, so the cPanel cron runs it once."""
    import time

    log = "/home/rindete/tmp/rindete-migrate.txt"
    command = (
        "/opt/cpanel/ea-php85/root/usr/bin/php "
        f"{APP_DIR}/spark migrate -n App > {log} 2>&1"
    )
    added = api.api2("Cron", "add_line", {
        "command": command,
        "minute": "*",
        "hour": "*",
        "day": "*",
        "month": "*",
        "weekday": "*",
    })
    linekey = ""
    try:
        linekey = str(added["cpanelresult"]["data"][0].get("linekey", ""))
    except (KeyError, IndexError, TypeError):
        linekey = ""
    text = ""
    for _ in range(8):
        time.sleep(10)
        current = api.read(f"{HOME}/tmp", "rindete-migrate.txt")
        if current and "Migrations complete" in current:
            text = current
            break
        text = current or ""
    if linekey:
        api.api2("Cron", "remove_line", {"linekey": linekey})
    limpiar_cron_vacio(api)
    api.fileop(op="trash", sourcefiles="tmp/rindete-migrate.txt")
    print("migrate ok" if "Migrations complete" in text else "migrate pending")


def limpiar_cron_vacio(api: Cpanel) -> None:
    """cPanel inserts MAILTO when a crontab is installed. Drop it if no jobs remain."""
    listed = api.api2("Cron", "fetchcron")
    try:
        rows = listed["cpanelresult"]["data"]
    except (KeyError, TypeError):
        return
    if any(row.get("type") == "command" for row in rows):
        return
    for row in rows:
        key = str(row.get("linekey", ""))
        if key:
            api.api2("Cron", "remove_line", {"linekey": key})


def check_site() -> None:
    ctx = ssl._create_unverified_context()
    for url in (
        "https://app.rindete.cl/",
        "https://app.rindete.cl/registro",
        "https://app.rindete.cl/login",
        "https://pidetufactura.com/",
    ):
        try:
            with urllib.request.urlopen(url, context=ctx, timeout=25) as response:
                body = response.read(120).decode("utf-8", "replace").replace("\n", " ")
                print(f"{response.status} {url} {body[:80]}")
        except Exception as error:
            code = getattr(error, "code", "ERR")
            print(f"{code} {url}")


if __name__ == "__main__":
    main()
