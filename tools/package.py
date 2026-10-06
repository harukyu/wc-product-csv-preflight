"""Build reproducible installable and source ZIPs from explicit manifests."""
import hashlib
import json
import re
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

root = Path(__file__).resolve().parent.parent
meta = json.loads((root / "project.json").read_text())
version = meta["version"]
main = (root / (meta["slug"] + ".php")).read_text()
module = (root / meta["module"]).read_text()
if re.search(r"Version:\s*([^\s]+)", main).group(1) != version or re.search(r"const VERSION = '([^']+)'", module).group(1) != version:
    raise ValueError("Version declarations must match project.json.")
output = root / "dist"
output.mkdir(exist_ok=True)
for kind in ["plugin", "source"]:
    files = meta[kind + "_files"]
    if len(files) != len(set(files)):
        raise ValueError("Duplicate publication files.")
    name = meta["slug"] + "-" + version + ("-source" if kind == "source" else "") + ".zip"
    path = output / name
    with ZipFile(path, "w", compression=ZIP_DEFLATED) as archive:
        for relative in sorted(files):
            source = root / relative
            if not source.is_file() or source.is_symlink() or root not in source.resolve().parents:
                raise ValueError("Missing or unsafe manifest entry.")
            entry = ZipInfo(meta["slug"] + "/" + relative, date_time=(2026, 10, 7, 0, 0, 0))
            entry.compress_type = ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            archive.writestr(entry, source.read_bytes())
    digest = hashlib.sha256(path.read_bytes()).hexdigest()
    (output / (name + ".sha256")).write_text(digest + "  " + name + "\n")
    print("Built", name, "with", len(files), "allowlisted files.")
