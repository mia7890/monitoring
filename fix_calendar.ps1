$filePath = 'c:\xampp\htdocs\monitoring\calendar.php'

# Read the full content
$lines = Get-Content -Path $filePath -Encoding UTF8

# Join into single string
$content = $lines -join "`r`n"

# Replace corrupted emoji sequences with HTML entities using exact char codes to prevent quote parsing errors

# Pin/pushpin emoji
$patPin1 = "$([char]0x00F0)$([char]0x0178)$([char]0x0022)$([char]0x0022)$([char]0x0152)"
$patPin2 = "$([char]0x00F0)$([char]0x0178)$([char]0x0022)$([char]0x0152)"
$content = $content -replace [regex]::Escape($patPin1), '&#128204;'
$content = $content -replace [regex]::Escape($patPin2), '&#128204;'

# Calendar emoji  
$patCal = "$([char]0x00F0)$([char]0x0178)$([char]0x0022)$([char]0x2026)"
$content = $content -replace [regex]::Escape($patCal), '&#128197;'

# Lock emoji
$patLock1 = "$([char]0x00F0)$([char]0x0178)$([char]0x0022)$([char]0x0022)$([char]0x0027)"
$patLock2 = "$([char]0x00F0)$([char]0x0178)$([char]0x0022)$([char]0x0027)"
$content = $content -replace [regex]::Escape($patLock1), '&#128274;'
$content = $content -replace [regex]::Escape($patLock2), '&#128274;'

# Person/bust emoji
$patPerson = "$([char]0x00F0)$([char]0x0178)$([char]0x0027)$([char]0x00A4)"
$content = $content -replace [regex]::Escape($patPerson), '&#128100;'

# Checkmark emoji
$patCheck = "$([char]0x00E2)$([char]0x0153)$([char]0x2026)"
$content = $content -replace [regex]::Escape($patCheck), '&#9989;'

# Cross mark emoji  
$patCross = "$([char]0x00E2)$([char]0x0152)"
$content = $content -replace [regex]::Escape($patCross), '&#10060;'

# Write with UTF-8 no BOM
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($filePath, $content, $utf8NoBom)

Write-Host "Done. File cleaned and saved with UTF-8 no BOM."