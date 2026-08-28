from __future__ import annotations

import argparse
from pathlib import Path

import kagglehub

DATASET_HANDLE = "shrutisaxena/yoga-pose-image-classification-dataset/versions/1"
DATASET_PAGE = "https://www.kaggle.com/datasets/shrutisaxena/yoga-pose-image-classification-dataset"
SOURCE_DIRECTORIES = {
    "virasana": "virasana",
    "balasana": "balasana",
    "urdhva_dhanurasana": "urdhva dhanurasana",
    "mayurasana": "mayurasana",
    "salamba_sirsasana": "salamba sirsasana",
}


def download(output_directory: Path) -> None:
    output_directory.mkdir(parents=True, exist_ok=True)
    print(
        "Downloading and extracting the Yoga-107 mirror. The stable KaggleHub API currently "
        "downloads the complete archive; GymRAVANA will use only the five verified folders."
    )
    downloaded_path = Path(
        kagglehub.dataset_download(
            DATASET_HANDLE,
            output_dir=str(output_directory),
        )
    )

    missing_directories: list[str] = []
    for canonical_name, source_directory in SOURCE_DIRECTORIES.items():
        matches = [
            path
            for path in downloaded_path.rglob(source_directory)
            if path.is_dir() and path.parent.name == "dataset"
        ]
        if matches:
            print(f"Verified {canonical_name}: {matches[0]}")
        else:
            missing_directories.append(source_directory)

    if missing_directories:
        raise RuntimeError(
            "The downloaded dataset no longer has the expected directories: "
            + ", ".join(missing_directories)
        )


def main() -> None:
    parser = argparse.ArgumentParser(
        description=(
            "Download Yoga-107 and verify the five class directories used by the "
            "GymRAVANA pose experiment."
        ),
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("ai/data/pose_public"),
        help="Git-ignored destination directory.",
    )
    arguments = parser.parse_args()
    try:
        download(arguments.output)
    except Exception as exception:
        raise SystemExit(
            "Kaggle did not authorize the public file download. Authenticate with an account token "
            "using Kaggle's official kagglehub instructions, accept any displayed dataset terms, and "
            f"rerun this script. Dataset page: {DATASET_PAGE}\nOriginal error: {exception}"
        ) from exception


if __name__ == "__main__":
    main()
