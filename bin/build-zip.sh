#!/usr/bin/env bash
#
# Builds a distributable wp-a11y.zip ready to install via wp-admin's
# "Add New Plugin > Upload Plugin", excluding development-only files.
#
# Usage: bin/build-zip.sh [output-dir]

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="${1:-$PLUGIN_DIR/..}"
VERSION=$(grep -m1 '^ \* Version:' "$PLUGIN_DIR/wp-a11y.php" | sed -E 's/.*Version:\s*//')
STAGE_DIR=$(mktemp -d)
ZIP_PATH="$OUT_DIR/wp-a11y-${VERSION}.zip"

trap 'rm -rf "$STAGE_DIR"' EXIT

# --anchored (leading /) so these only match at the plugin root, never
# nested paths that happen to share a name (e.g. an assets/.gitignore).
rsync -a "$PLUGIN_DIR"/ "$STAGE_DIR"/wp-a11y/ \
	--exclude='/.git' \
	--exclude='/.gitignore' \
	--exclude='/DECISIONS.md' \
	--exclude='/docs' \
	--exclude='/bin' \
	--exclude='/node_modules' \
	--exclude='/vendor' \
	--exclude='.DS_Store'

rm -f "$ZIP_PATH"

# php's ZipArchive instead of the `zip` CLI - not guaranteed to be
# installed on every dev machine, PHP's zip extension already is (this
# plugin's whole stack depends on PHP anyway).
php -r '
	$stageDir = $argv[1];
	$zipPath  = $argv[2];
	$zip = new ZipArchive();
	$zip->open($zipPath, ZipArchive::CREATE);
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($stageDir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($files as $file) {
		$local = substr($file->getPathname(), strlen($stageDir) + 1);
		if ($file->isDir()) {
			$zip->addEmptyDir($local);
		} else {
			$zip->addFile($file->getPathname(), $local);
		}
	}
	$zip->close();
' "$STAGE_DIR" "$ZIP_PATH"

echo "Built: $ZIP_PATH"
