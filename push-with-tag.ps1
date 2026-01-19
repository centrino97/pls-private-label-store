# PowerShell script to push with auto-tag creation
# Usage: .\push-with-tag.ps1 [branch-name]
# Default branch: main

param(
    [string]$Branch = "main"
)

Write-Host "=== PLS Release Push ===" -ForegroundColor Cyan

# Extract version from plugin file
$pluginFile = "pls-private-label-store.php"
if (-not (Test-Path $pluginFile)) {
    Write-Host "Error: $pluginFile not found!" -ForegroundColor Red
    exit 1
}

$content = Get-Content $pluginFile -Raw
if ($content -match 'Version:\s*(\d+\.\d+\.\d+)') {
    $version = $matches[1]
    $tag = "v$version"
    
    Write-Host "Version found: $version" -ForegroundColor Green
    Write-Host "Tag: $tag" -ForegroundColor Green
    
    # Check if there are uncommitted changes
    Write-Host "`nChecking git status..." -ForegroundColor Cyan
    $status = git status --porcelain
    if ($status) {
        Write-Host "`n⚠️  WARNING: You have uncommitted changes!" -ForegroundColor Yellow
        Write-Host "`nUncommitted files:" -ForegroundColor Yellow
        git status --short
        Write-Host "`nPlease commit your changes first:" -ForegroundColor Yellow
        Write-Host "  git add ." -ForegroundColor White
        Write-Host "  git commit -m 'Your commit message'" -ForegroundColor White
        Write-Host "`nThen run this script again." -ForegroundColor Yellow
        exit 1
    }
    
    # Check if there are unpushed commits
    $ahead = git rev-list --count origin/$Branch..HEAD 2>$null
    if ($ahead -eq 0) {
        Write-Host "No new commits to push." -ForegroundColor Yellow
        Write-Host "Creating/updating tag only..." -ForegroundColor Cyan
    } else {
        Write-Host "Found $ahead commit(s) to push." -ForegroundColor Green
    }
    
    # Delete tag if exists (locally and remotely)
    Write-Host "`nUpdating tag..." -ForegroundColor Cyan
    git tag -d $tag 2>$null
    git push origin ":refs/tags/$tag" 2>$null
    
    # Create new tag pointing to current commit
    git tag -a $tag -m "Release $tag"
    Write-Host "Tag created: $tag" -ForegroundColor Green
    
    # Push commits (only if there are commits to push)
    if ($ahead -gt 0) {
        Write-Host "`nPushing commits to $Branch..." -ForegroundColor Cyan
        git push origin $Branch
        if ($LASTEXITCODE -ne 0) {
            Write-Host "`n❌ Error: Failed to push commits!" -ForegroundColor Red
            Write-Host "Check your git remote and authentication." -ForegroundColor Yellow
            exit 1
        }
        Write-Host "✓ Commits pushed successfully" -ForegroundColor Green
    }
    
    # Push tag
    Write-Host "`nPushing tag $tag..." -ForegroundColor Cyan
    git push origin $tag
    if ($LASTEXITCODE -ne 0) {
        Write-Host "`n❌ Error: Failed to push tag!" -ForegroundColor Red
        Write-Host "The tag was created locally but couldn't be pushed." -ForegroundColor Yellow
        Write-Host "Try manually: git push origin $tag" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "✓ Tag pushed successfully" -ForegroundColor Green
    
    Write-Host "`n✓ Successfully pushed commits and tag $tag" -ForegroundColor Green
    Write-Host "GitHub Actions will now build the release..." -ForegroundColor Cyan
    Write-Host "Check: https://github.com/centrino97/pls-private-label-store/actions" -ForegroundColor Cyan
    
} else {
    Write-Host "Error: Could not extract version from $pluginFile" -ForegroundColor Red
    Write-Host "Make sure the file contains 'Version: X.Y.Z'" -ForegroundColor Yellow
    exit 1
}
