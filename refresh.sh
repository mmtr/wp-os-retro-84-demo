#!/usr/bin/env bash
# Refresh the two ZIPs the demo hands to Playground: OpenStation's
# latest trunk build and the Retro 84 theme's main branch.
set -euo pipefail
cd "$( dirname "$0" )/site"
curl -fsSL -o openstation.zip https://github.com/WordPress/openstation/releases/download/ci-artifacts/trunk.zip
curl -fsSL -o theme.zip https://github.com/mmtr/wp-os-retro-84/archive/refs/heads/main.zip
echo "Refreshed site/openstation.zip and site/theme.zip"
