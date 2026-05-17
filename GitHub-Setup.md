Step 1 — Fork AureusERP on GitHub
Go to https://github.com/aureuserp/aureuserp and click Fork — this creates github.com/YOUR_USERNAME/aureuserp as your own copy.
Step 2 — Clone your fork locally
bashgit clone https://github.com/YOUR_USERNAME/aureuserp.git
cd aureuserp
Step 3 — Add the original AureusERP as a second remote called upstream
bashgit remote add upstream https://github.com/aureuserp/aureuserp.git

# Verify you now have two remotes
git remote -v
# origin    https://github.com/YOUR_USERNAME/aureuserp.git  ← your fork
# upstream  https://github.com/aureuserp/aureuserp.git      ← official
Step 4 — Create your branch structure
bash# main branch = always matches upstream (never commit your work here)
git checkout main

# Your production customisation branch
git checkout -b client/PROJECT_NAME

# Feature branches come off the client branch
git checkout -b feature/approval-workflow
Step 5 — Set up your .gitignore correctly
AureusERP already has a .gitignore but make sure these are included:
.env
/vendor
/node_modules
/storage/app/private
/storage/logs
/public/hot
/public/storage
*.sql
*_backup_*
Step 6 — Your day-to-day workflow
bash# Work on a feature
git checkout -b feature/approval-workflow
# ... make changes ...
git add .
git commit -m "feat: add multi-step approval workflow"
git push origin feature/approval-workflow

# When done, merge into your client branch
git checkout client/PROJECT_NAME
git merge feature/approval-workflow
git push origin client/PROJECT_NAME

Part 2 — Upgrading after your changes in production
This is the tricky part. The official guide recommends pulling from the main repository, but if you have local modifications that conflict, you stash them first with git stash, pull, then run git stash pop. That approach is fine for small tweaks but breaks down when you have months of customisations. Here's the professional way to handle it: GitHub
The safe upgrade flow with your own changes:
bash# ══════════════════════════════════════
# ON YOUR LOCAL / STAGING machine first
# ══════════════════════════════════════

# 1. Backup the database
mysqldump -u root -p aureus_db > backup_aureus_$(date +%F).sql

# 2. Fetch what changed in upstream (don't merge yet)
git fetch upstream

# 3. See what commits are coming before you merge
git log HEAD..upstream/main --oneline

# 4. Check if any upstream changes touch files YOU also changed
git diff HEAD upstream/main -- plugins/webkul/

# 5. Merge upstream into your client branch
git checkout client/PROJECT_NAME
git merge upstream/main
# Fix any conflicts here — your files vs their updates

# 6. Run the upgrade steps
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate
php artisan storage:link
php artisan shield:generate --all

# 7. Test everything on staging

# ══════════════════════════════════════
# ONLY THEN — deploy to production
# ══════════════════════════════════════
Production deployment script — save this as deploy.sh in your project root:
bash#!/bin/bash
set -e  # stop on any error

echo "🔒 Putting site in maintenance mode..."
php artisan down --secret="YOUR_BYPASS_TOKEN"

echo "📦 Pulling latest code..."
git pull origin client/PROJECT_NAME

echo "📚 Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "🗃️ Running migrations..."
php artisan migrate --force

echo "🔗 Linking storage..."
php artisan storage:link

echo "🔐 Regenerating permissions..."
php artisan shield:generate --all

echo "⚡ Optimising..."
php artisan optimize
php artisan filament:cache-components

echo "♻️ Restarting queue workers..."
php artisan queue:restart

echo "✅ Bringing site back online..."
php artisan up

echo "🚀 Deployment complete!"
Make it executable and run it on the server:
bashchmod +x deploy.sh
./deploy.sh
