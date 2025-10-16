#!/bin/bash

# Get modified files
modified=$(git diff --name-only)

# Get deleted files
deleted=$(git diff --name-only --diff-filter=D)

# Get untracked files
untracked=$(git ls-files --others --exclude-standard)

# Commit modified files
for file in $modified; do
    git add "$file"
    git commit -m "Update: $file"
    echo "✓ Committed: $file"
done

# Commit deleted files
for file in $deleted; do
    git rm "$file"
    git commit -m "Remove: $file"
    echo "✓ Committed deletion: $file"
done

# Commit untracked files
for file in $untracked; do
    git add "$file"
    git commit -m "Add: $file"
    echo "✓ Committed: $file"
done

echo "\nPushing to production branch..."
git push origin production

echo "\n✓ All files committed and pushed!"
