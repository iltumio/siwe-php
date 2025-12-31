#!/bin/bash

# Script to bump version in composer.json
# Usage: ./bump-version.sh [patch|minor|major]

COMPOSER_FILE="composer.json"

if [ ! -f "$COMPOSER_FILE" ]; then
    echo "Error: $COMPOSER_FILE not found"
    exit 1
fi

# Get current version
CURRENT_VERSION=$(grep -o '"version": "[^"]*"' "$COMPOSER_FILE" | cut -d'"' -f4)

if [ -z "$CURRENT_VERSION" ]; then
    echo "Error: Could not find version in $COMPOSER_FILE"
    exit 1
fi

echo "Current version: $CURRENT_VERSION"

# Parse version parts
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

# Determine bump type
BUMP_TYPE=${1:-patch}

case $BUMP_TYPE in
    patch)
        PATCH=$((PATCH + 1))
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    *)
        echo "Error: Invalid bump type. Use 'patch', 'minor', or 'major'"
        exit 1
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"

# Update version in composer.json
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$COMPOSER_FILE"
else
    # Linux
    sed -i "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$COMPOSER_FILE"
fi

echo "Version bumped to: $NEW_VERSION"
echo "Updated $COMPOSER_FILE"

