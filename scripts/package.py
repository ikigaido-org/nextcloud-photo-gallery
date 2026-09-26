#!/usr/bin/env python3
# SPDX-FileCopyrightText: 2026 Stephan Rickauer
# SPDX-License-Identifier: AGPL-3.0-only

"""Package the committed app for AppDrop; no Nextcloud installation is needed."""

import os
from pathlib import Path
import re
import subprocess
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[1]
info = ET.fromstring(subprocess.check_output(
    ['git', 'show', 'HEAD:appinfo/info.xml'], cwd=ROOT,
))
version = info.findtext('version', '')
if info.findtext('id') != 'photo_gallery' or not re.fullmatch(r'\d+\.\d+\.\d+', version):
    raise SystemExit('Expected app ID photo_gallery and a numeric three-part version.')
if os.environ.get('GITHUB_REF_TYPE') == 'tag':
    if os.environ.get('GITHUB_REF_NAME') != f'v{version}':
        raise SystemExit(f'Release tag must match appinfo/info.xml: v{version}')

# Include prebuilt runtime assets and their source/licence material.
# Tests, workflow files, local dependencies and untracked files are not shipped.
paths = [
    'appinfo', 'css', 'docs', 'fonts', 'img', 'js', 'lib', 'src',
    'templates', 'vendor', 'LICENSE', 'COPYRIGHT', 'README.md',
]
output = ROOT / 'dist' / f'photo-gallery-{version}.zip'
output.parent.mkdir(exist_ok=True)
subprocess.run([
    'git', 'archive', '--format=zip', '--prefix=photo_gallery/',
    f'--output={output}', 'HEAD', *paths,
], cwd=ROOT, check=True)

with zipfile.ZipFile(output) as archive:
    if archive.testzip() is not None:
        raise SystemExit('ZIP integrity check failed.')
    names = archive.namelist()
    if not all(name.startswith('photo_gallery/') for name in names):
        raise SystemExit('ZIP must have exactly one photo_gallery/ root folder.')
    packaged_info = ET.fromstring(archive.read('photo_gallery/appinfo/info.xml'))
    if packaged_info.findtext('version') != version:
        raise SystemExit('Packaged app version does not match the ZIP filename.')

print(f'Created {output.name} ({output.stat().st_size:,} bytes)')
