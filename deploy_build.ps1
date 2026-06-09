# Deploy Script — Klinik PKP ke Hostinger
$ErrorActionPreference = "Stop"
$src = "c:\xampp\htdocs\klinik_new"
$staging = "c:\xampp\htdocs\_deploy_staging"
$zipPath = "c:\xampp\htdocs\klinik_new_deploy.zip"

# Clean staging
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }

# Copy with exclusions using robocopy
$excludeDirs = @('.git','node_modules','vendor','.playwright-mcp','.assets','_unused')
$excludeFiles = @('migrate_forum.php','migrate_likes.php','migrate_replies.php','test_api.php','clean_bg.py','.env','.env.production','composer.lock','package-lock.json','package.json','tailwind.config.js')

robocopy $src $staging /E /NFL /NDL /NJH /NJS /NP /XD $excludeDirs /XF $excludeFiles | Out-Null

# Replace .env with production version
Copy-Item "$src\.env.production" "$staging\.env" -Force

# Remove ZIP if exists
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

# Create ZIP
Compress-Archive -Path "$staging\*" -DestinationPath $zipPath -Force

# Report
$size = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
$fileCount = (Get-ChildItem $staging -Recurse -File).Count
Write-Host "Deploy package created: $zipPath"
Write-Host "Files: $fileCount | Size: $size MB"

# Cleanup staging
Remove-Item $staging -Recurse -Force
