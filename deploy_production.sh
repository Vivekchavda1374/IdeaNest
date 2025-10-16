#!/bin/bash

# IdeaNest Production Deployment Script
# Updates database configuration and URLs for production environment

set -e

echo "🚀 Starting IdeaNest Production Deployment..."

# Check if we're in the right directory
if [ ! -f "README.md" ] || [ ! -d "Login" ]; then
    echo "❌ Error: Please run this script from the IdeaNest root directory"
    exit 1
fi

# Stage all changes
echo "📦 Staging all changes..."
git add .

# Commit database configuration updates
echo "💾 Committing database configuration updates..."
git commit -m "Update database configuration for production

- Change database host from ictmu.in to localhost
- Update database credentials to ictmu6ya_ideanest
- Configure production database settings in .env
- Update fallback values in db.php and test files"

# Commit URL updates
echo "🌐 Committing URL updates..."
git add .
git commit -m "Update all URLs to production domain

- Replace localhost URLs with https://ictmu.in/hcd/IdeaNest/
- Update all admin panel redirects to absolute URLs
- Fix login system redirects for all user roles
- Update mentor system authentication redirects
- Ensure all redirects use production domain"

# Commit documentation updates
echo "📚 Committing documentation updates..."
git add .
git commit -m "Update documentation for production deployment

- Update installation guides with production credentials
- Fix database setup instructions
- Update deployment guides with correct domain
- Correct all configuration examples"

# Push all commits
echo "⬆️ Pushing commits to GitHub..."
git push origin main

# Create and push production branch
echo "🔀 Creating production branch..."
git checkout -b production-deployment
git push origin production-deployment

# Create Pull Request (requires GitHub CLI)
if command -v gh &> /dev/null; then
    echo "📋 Creating Pull Request..."
    gh pr create \
        --title "Production Deployment Configuration" \
        --body "## Production Deployment Updates

### Database Configuration
- ✅ Updated database host to localhost
- ✅ Configured production credentials (ictmu6ya_ideanest)
- ✅ Updated .env file for production environment
- ✅ Fixed all database connection fallbacks

### URL Configuration  
- ✅ Replaced all localhost URLs with production domain
- ✅ Updated redirects to https://ictmu.in/hcd/IdeaNest/
- ✅ Fixed admin panel authentication redirects
- ✅ Updated login system for all user roles

### Documentation
- ✅ Updated installation guides
- ✅ Fixed deployment instructions
- ✅ Corrected configuration examples

### Testing Required
- [ ] Test database connection
- [ ] Verify login redirects work correctly
- [ ] Test admin panel access
- [ ] Verify mentor system functionality
- [ ] Test file uploads and permissions

**Ready for production deployment to https://ictmu.in/hcd/IdeaNest/**" \
        --base main \
        --head production-deployment
    
    echo "✅ Pull Request created successfully!"
else
    echo "⚠️  GitHub CLI not found. Please create PR manually at:"
    echo "   https://github.com/yourusername/IdeaNest/compare/main...production-deployment"
fi

# Switch back to main branch
git checkout main

echo "🎉 Production deployment preparation complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Review the Pull Request on GitHub"
echo "2. Test the changes in staging environment"
echo "3. Merge the PR when ready"
echo "4. Deploy to production server"
echo ""
echo "🌐 Production URL: https://ictmu.in/hcd/IdeaNest/"