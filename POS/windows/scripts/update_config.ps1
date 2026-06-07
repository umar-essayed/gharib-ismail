param(
  [Parameter(Mandatory = $true)] [string]$ConfigPath,
  [Parameter(Mandatory = $true)] [string]$Host,
  [Parameter(Mandatory = $true)] [string]$Port,
  [Parameter(Mandatory = $true)] [string]$Database,
  [Parameter(Mandatory = $true)] [string]$Username,
  [Parameter(Mandatory = $false)] [string]$Password = ""
)

function Escape-SingleQuote([string]$value) {
  if ($null -eq $value) { return "" }
  return $value.Replace("'", "''")
}

$hostVal = Escape-SingleQuote $Host
$portVal = Escape-SingleQuote $Port
$dbVal = Escape-SingleQuote $Database
$userVal = Escape-SingleQuote $Username
$passVal = Escape-SingleQuote $Password

$content = @"
<?php

return [
    'host' => '$hostVal',
    'port' => $portVal,
    'database' => '$dbVal',
    'username' => '$userVal',
    'password' => '$passVal',
    'charset' => 'utf8mb4',
];
"@

# Write without BOM to avoid "headers already sent" issues in PHP
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($ConfigPath, $content, $utf8NoBom)
