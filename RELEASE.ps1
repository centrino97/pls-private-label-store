# PLS Release Push Script - Pushes commits + tag for UUPD
# This follows PLS dev rules automatically

cd "c:\Users\HP ProBook\Desktop\pls\pls-private-label-store"

# Clear proxy settings
Remove-Item Env:\HTTP_PROXY -ErrorAction SilentlyContinue
Remove-Item Env:\HTTPS_PROXY -ErrorAction SilentlyContinue
Remove-Item Env:\http_proxy -ErrorAction SilentlyContinue
Remove-Item Env:\https_proxy -ErrorAction SilentlyContinue
$env:NO_PROXY = "*"

Write-Host "=== PLS Release Push (UUPD Ready) ===" -ForegroundColor Cyan

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
    Write-Host ""
    Write-Host "Checking git status..." -ForegroundColor Cyan
    $status = git status --porcelain
    if ($status) {
        Write-Host ""
        Write-Host "WARNING: You have uncommitted changes!" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Uncommitted files:" -ForegroundColor Yellow
        git status --short
        Write-Host ""
        Write-Host "Please commit your changes first:" -ForegroundColor Yellow
        Write-Host "  git add ." -ForegroundColor White
        Write-Host "  git commit -m Your commit message" -ForegroundColor White
        Write-Host ""
        Write-Host "Then run this script again." -ForegroundColor Yellow
        exit 1
    }
    
    # Check if there are unpushed commits
    $ahead = git rev-list --count origin/main..HEAD 2>$null
    if ($ahead -eq 0) {
        Write-Host "No new commits to push." -ForegroundColor Yellow
        Write-Host "Creating/updating tag only..." -ForegroundColor Cyan
    } else {
        Write-Host "Found $ahead commit(s) to push." -ForegroundColor Green
    }
    
    # Delete tag if exists (locally and remotely)
    Write-Host ""
    Write-Host "Updating tag..." -ForegroundColor Cyan
    git tag -d $tag 2>$null
    git push origin ":refs/tags/$tag" 2>$null
    
    # Create new tag pointing to current commit
    git tag -a $tag -m "Release $tag"
    Write-Host "Tag created: $tag" -ForegroundColor Green
    
    # Push commits (only if there are commits to push)
    if ($ahead -gt 0) {
        Write-Host ""
        Write-Host "Pushing commits to main..." -ForegroundColor Cyan
        git -c http.proxy= -c https.proxy= push origin main
        if ($LASTEXITCODE -ne 0) {
            Write-Host ""
            Write-Host "Error: Failed to push commits!" -ForegroundColor Red
            Write-Host "Check your GitHub authentication or network connection" -ForegroundColor Yellow
            exit 1
        }
        Write-Host "Commits pushed successfully" -ForegroundColor Green
    }
    
    # Push tag (CRITICAL for UUPD!)
    Write-Host ""
    Write-Host "Pushing tag $tag (this makes UUPD work)..." -ForegroundColor Cyan
    git -c http.proxy= -c https.proxy= push origin $tag
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "Error: Failed to push tag!" -ForegroundColor Red
        Write-Host "Check your GitHub authentication or network connection" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "Tag pushed successfully" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Successfully pushed commits and tag $tag" -ForegroundColor Green
    Write-Host "GitHub Actions will now build the release..." -ForegroundColor Cyan
    Write-Host "Check: https://github.com/centrino97/pls-private-label-store/actions" -ForegroundColor Cyan
    Write-Host "Release: https://github.com/centrino97/pls-private-label-store/releases" -ForegroundColor Cyan
    
} else {
    Write-Host "Error: Could not extract version from $pluginFile" -ForegroundColor Red
    Write-Host "Make sure the file contains a version number" -ForegroundColor Yellow
    exit 1
}
