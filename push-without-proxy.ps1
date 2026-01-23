# Git push script that bypasses proxy settings
# Usage: .\push-without-proxy.ps1

# Clear proxy environment variables completely
Remove-Item Env:\HTTP_PROXY -ErrorAction SilentlyContinue
Remove-Item Env:\HTTPS_PROXY -ErrorAction SilentlyContinue
Remove-Item Env:\http_proxy -ErrorAction SilentlyContinue
Remove-Item Env:\https_proxy -ErrorAction SilentlyContinue
$env:NO_PROXY = "*"

# Change to repository directory
$repoPath = "c:\Users\HP ProBook\Desktop\pls\pls-private-label-store"
Set-Location $repoPath

# Push with proxy bypassed and embedded token
Write-Host "`n=== Pushing to GitHub ===" -ForegroundColor Cyan
Write-Host "Repository: $repoPath" -ForegroundColor Gray
Write-Host "Branch: main`n" -ForegroundColor Gray

# Note: Token should be stored securely in Windows Credential Manager
# This script relies on git credential helper for authentication

# Set GIT_ASKPASS to provide token non-interactively
$askpassScript = Join-Path $repoPath "git-askpass.ps1"
$env:GIT_ASKPASS = "powershell.exe"
$env:GIT_ASKPASS_ARGS = "-File `"$askpassScript`""

# Use git with all proxy settings cleared
$gitArgs = @(
    "-c", "http.proxy=",
    "-c", "https.proxy=",
    "-c", "http.https://github.com.proxy=",
    "-c", "core.askPass=powershell.exe",
    "-c", "credential.helper=",
    "push", "origin", "main"
)

& git $gitArgs

if ($LASTEXITCODE -eq 0) {
    Write-Host "`nPush successful!" -ForegroundColor Green
    # Restore clean URL (without token)
    git remote set-url origin $currentUrl
} else {
    Write-Host "`nPush failed with error code: $LASTEXITCODE" -ForegroundColor Red
    # Restore clean URL
    git remote set-url origin $currentUrl
}
