from __future__ import annotations

import argparse
import hashlib
import json
import shutil
import zipfile
from datetime import UTC, datetime
from pathlib import Path

from ai.pose.download_public_subset import DATASET_HANDLE, DATASET_PAGE, SOURCE_DIRECTORIES

IMAGE_SUFFIXES = {".jpeg", ".jpg", ".png", ".webp"}


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def extract_subset(archive_path: Path, output_directory: Path) -> dict[str, object]:
    if not archive_path.is_file():
        raise FileNotFoundError(f"Dataset archive not found: {archive_path}")

    output_directory.mkdir(parents=True, exist_ok=True)
    existing_files = [path for path in output_directory.rglob("*") if path.is_file()]
    if existing_files:
        raise FileExistsError(
            f"Extraction destination must be empty to prevent accidental overwrites: {output_directory}"
        )

    source_to_canonical = {source: canonical for canonical, source in SOURCE_DIRECTORIES.items()}
    records: list[dict[str, object]] = []

    with zipfile.ZipFile(archive_path) as archive:
        corrupt_entry = archive.testzip()
        if corrupt_entry is not None:
            raise zipfile.BadZipFile(f"Corrupt ZIP entry: {corrupt_entry}")

        for entry in archive.infolist():
            parts = Path(entry.filename).parts
            if len(parts) != 3 or parts[0] != "dataset" or entry.is_dir():
                continue

            source_class = parts[1]
            canonical_class = source_to_canonical.get(source_class)
            suffix = Path(parts[2]).suffix.lower()
            if canonical_class is None or suffix not in IMAGE_SUFFIXES:
                continue

            destination = output_directory / canonical_class / parts[2]
            destination.parent.mkdir(parents=True, exist_ok=True)
            if destination.exists():
                raise FileExistsError(f"Refusing to overwrite extracted image: {destination}")

            digest = hashlib.sha256()
            with archive.open(entry) as source, destination.open("xb") as target:
                while chunk := source.read(1024 * 1024):
                    target.write(chunk)
                    digest.update(chunk)

            records.append(
                {
                    "canonical_class": canonical_class,
                    "source_class": source_class,
                    "archive_path": entry.filename,
                    "relative_path": destination.relative_to(output_directory).as_posix(),
                    "size_bytes": entry.file_size,
                    "sha256": digest.hexdigest(),
                }
            )

    class_counts = {
        canonical_class: sum(record["canonical_class"] == canonical_class for record in records)
        for canonical_class in SOURCE_DIRECTORIES
    }
    missing_classes = [name for name, count in class_counts.items() if count == 0]
    if missing_classes:
        shutil.rmtree(output_directory)
        raise ValueError(f"Archive is missing required classes: {', '.join(missing_classes)}")

    manifest = {
        "schema_version": 1,
        "created_at_utc": datetime.now(UTC).isoformat(),
        "dataset_handle": DATASET_HANDLE,
        "dataset_page": DATASET_PAGE,
        "archive_name": archive_path.name,
        "archive_size_bytes": archive_path.stat().st_size,
        "archive_sha256": sha256_file(archive_path),
        "image_count": len(records),
        "class_counts": class_counts,
        "records": records,
    }
    manifest_path = output_directory / "extraction_manifest.json"
    manifest_path.write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    return manifest


def main() -> None:
    parser = argparse.ArgumentParser(description="Safely extract GymRAVANA's five Yoga-107 classes.")
    parser.add_argument("--archive", type=Path, default=Path("ai/data/archive.zip"))
    parser.add_argument("--output", type=Path, default=Path("ai/data/pose_public"))
    arguments = parser.parse_args()
    manifest = extract_subset(arguments.archive, arguments.output)
    print(json.dumps({key: manifest[key] for key in ("archive_sha256", "image_count", "class_counts")}, indent=2))


if __name__ == "__main__":
    main()
