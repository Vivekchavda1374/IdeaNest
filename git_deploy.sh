#!/bin/bash

# Git Deployment Script for IdeaNest Production
echo "🚀 IdeaNest Production Git Deployment"
echo "======================================"

# Check current branch
echo "📍 Current branch: $(git branch --show-current)"
echo "📊 Git status:"
git status --short

echo ""
echo "📦 Staging all changes..."
git add .

echo ""
echo "💾 Creating production commit..."
git commit -m "Production deployment configuration

✅ Database Configuration:
- Updated host to localhost for production
- Set credentials to ictmu6ya_ideanest
- Configured .env for production environment

✅ URL Updates:
- Replaced localhost with https://ictmu.in/hcd/IdeaNest/
- Updated all admin redirects to absolute URLs
- Fixed login system redirects for all roles
- Updated mentor authentication redirects

✅ Documentation:
- Updated installation guides
- Fixed deployment instructions
- Corrected configuration examples

Ready for production deployment at https://ictmu.in/hcd/IdeaNest/"

echo ""
echo "⬆️ Push command (run manually):"
echo "git push origin $(git branch --show-current)"

echo ""
echo "🔀 Create PR command (run manually if you have GitHub CLI):"
echo "gh pr create --title 'Production Deployment Ready' --body 'All production configurations updated and ready for deployment' --base main --head $(git branch --show-current)"

echo ""
echo "🌐 Production URL: https://ictmu.in/hcd/IdeaNest/"
echo "✅ All changes committed and ready for push!"