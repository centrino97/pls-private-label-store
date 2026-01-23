# Git askpass helper - returns token for authentication
# NOTE: Token should be stored securely, not in this file
param([string]$prompt)
if ($prompt -match "Password") {
    # Token should be retrieved from secure storage or environment variable
    Write-Output ""
} elseif ($prompt -match "Username") {
    Write-Output "centrino97"
}
