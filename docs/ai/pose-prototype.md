# Yoga pose-recognition prototype

## Honest task boundary

The supplied `gymravana_pose_dataset_starter` is not a progression-readiness dataset. It contains yoga-pose images and reference annotations, while the existing Master Gate model requires monthly behavioral features with a genuine trainer `ready_for_progression` decision. The two targets must not be combined.

GymRAVANA therefore treats this as a separate five-class **pose identity** experiment. It cannot judge whether a posture is safe or correct, prevent injury, give therapy advice, or unlock Master Gate.

## Supplied dataset audit

The package contains 18 cropped reference images across six named poses and three collage sources. Its own metadata states that it is intended for pipeline prototyping and is not sufficient for academic accuracy claims. Important findings are:

- all 18 records are marked `AI/reference_collage`;
- no `form_score` is present;
- no score is trainer verified;
- no record is marked usable for final academic claims;
- no real participant or subject identifier exists;
- Santulanasana depicts both an arm balance and a standing Warrior-III-like balance.

Santulanasana is excluded rather than silently choosing one definition. The remaining name mapping is:

| Starter name | Canonical model class |
| --- | --- |
| Virasana | `virasana` |
| Adho Mukha Virasana | `balasana` |
| Chakrasana | `urdhva_dhanurasana` |
| Mayurasana | `mayurasana` |
| Shirshasana | `salamba_sirsasana` |

## Executed notebook workflow

`04_pose_data_preparation.ipynb` uses Google's pretrained MediaPipe Pose Landmarker Lite to derive 20 non-medical joint-angle, symmetry, distance and visibility features. All 15 unambiguous samples produced landmarks. The ignored feature CSV is bound to the source annotations and exact MediaPipe model by SHA-256 metadata.

`05_pose_model_training_and_evaluation.ipynb` compares Logistic Regression, Random Forest and a linear Support Vector Machine. The first two collage sources form the model-development data. Candidate evaluation leaves one of those sources out at a time, and the third source remains untouched until final evaluation.

The executed result selected Random Forest:

| Model | Grouped mean accuracy | Grouped mean macro F1 |
| --- | ---: | ---: |
| Logistic Regression | 0.4000 | 0.3500 |
| Random Forest | 0.7000 | 0.6167 |
| Linear SVM | 0.6000 | 0.5167 |

The selected model classified all five third-source images correctly. The resulting accuracy and macro F1 of `1.0` are **not a production accuracy estimate**: the holdout contains only one image per class, all from one reference collage. The result proves the training/evaluation code executes; it does not establish generalization.

`06_pose_explainability_and_prototype_export.ipynb` calculates model-agnostic permutation importance on that five-image holdout and serializes a local prototype. Its metadata permanently records:

- `prototype_only: true`;
- `deployment_allowed: false`;
- the dataset and model fingerprints;
- the exact features/classes;
- the grouped evaluation output;
- prohibited form, medical, readiness and production claims.

The ignored prototype is deliberately not connected to a member-facing prediction endpoint.

## Larger Yoga-107 retraining run

The manually downloaded `archive.zip` passed ZIP integrity and path-traversal checks. Its SHA-256 is `ad3a52c6c75677d0ee902cc77d4835cb65f4f6009ead1ed254033a21d78485b3`. The safe extractor copied only the five agreed directories and fingerprinted every extracted image:

| Canonical class | Extracted images | Valid landmark rows |
| --- | ---: | ---: |
| `balasana` | 71 | 64 |
| `mayurasana` | 51 | 50 |
| `salamba_sirsasana` | 60 | 52 |
| `urdhva_dhanurasana` | 68 | 56 |
| `virasana` | 50 | 49 |
| **Total** | **300** | **271** |

All images passed decoding and file-fingerprint validation. MediaPipe could not detect a pose in 29 images, so those records were excluded rather than assigned fabricated landmarks. Perceptual-hash grouping found no cross-class near-duplicate conflicts. Near-duplicates within a class remain in the same data partition.

`07_public_pose_retraining.ipynb` compares the same three model families using four duplicate-grouped development folds. It reserves a fifth group-aware partition as an untouched final holdout:

| Model | Development mean accuracy | Development mean macro F1 |
| --- | ---: | ---: |
| Logistic Regression | 0.8248 | 0.8205 |
| Random Forest | 0.8940 | 0.8911 |
| Linear SVM | 0.8111 | 0.8090 |

Random Forest was selected without using the holdout labels. On the 54-image holdout it achieved accuracy `0.925926` and macro F1 `0.921501`. This is a meaningful improvement over the starter pipeline result, but it is still a web-image evaluation—not a subject-independent or GymRAVANA-camera accuracy estimate.

The exported `pose_identity_public_prototype.joblib` was trained on all 271 valid rows after evaluation. Its SHA-256 is `25756929202e18c9604e110e12e62222cc0d99ea2e8fc632634fd9bacfb04380`. Permutation importance was calculated separately using a development-only model against the untouched holdout; torso orientation was the strongest feature. The artifact remains disconnected from Laravel routes.

## Public data supplement

The [Yoga-82 authors](https://sites.google.com/view/yoga-82/home) provide a large hierarchical pose-classification dataset for non-commercial research and education, and require users to respect individual image rights. The [Yoga-82 paper](https://arxiv.org/abs/2004.10362) describes pose identity classification, not correctness scoring. Google documents that [MediaPipe Pose Landmarker](https://developers.google.com/edge/mediapipe/solutions/vision/pose_landmarker/) returns image and three-dimensional body landmarks.

The Kaggle Yoga-107 mirror has 5,994 images and contains five compatible directories totaling 300 images. The archive was downloaded manually after browser authentication. `ai/pose/download_public_subset.py` remains available for a future authenticated KaggleHub download, and `ai/pose/extract_public_subset.py` performs the safe five-class extraction. Neither script bypasses Kaggle or stores account credentials.

This public dataset supports pose identity only. A correctness/form model still requires multiple real participants, consistent camera instructions and trainer/expert annotations.

## Required evidence before deployment

The public run passes the minimum 250-landmark-row and 10-visual-group software checks. Visual groups only reduce obvious duplicate leakage; they are not independent participant groups. The participant, expert-label and local-camera evidence gates remain failed. Before member use, the project must also have:

- an agreed five-pose taxonomy;
- licensed images or consented recordings;
- subject-grouped train/validation/test splits;
- trainer review of labels;
- an out-of-domain test using real local camera conditions;
- documented failure handling when landmarks are missing or confidence is low.
