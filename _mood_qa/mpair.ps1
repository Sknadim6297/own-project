param([string]$cur, [string]$out, [int]$cssWidth = 390)
# Reference phone screen (orig px 15..305 x 22..625) scaled to CSS width, next to the current capture (2x DPR).
Add-Type -AssemblyName System.Drawing
$ref = New-Object System.Drawing.Bitmap ((Resolve-Path '_mood_qa/mref.png').Path)
$img = New-Object System.Drawing.Bitmap ((Resolve-Path $cur).Path)
$k = $cssWidth / 290.0
$H = [int](603 * $k)
$bmp = New-Object System.Drawing.Bitmap ($cssWidth * 2 + 10), $H
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.Clear([System.Drawing.Color]::Red)
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.DrawImage($ref, (New-Object System.Drawing.Rectangle 0, 0, $cssWidth, $H), (New-Object System.Drawing.Rectangle 15, 22, 290, 603), [System.Drawing.GraphicsUnit]::Pixel)
$g.DrawImage($img, (New-Object System.Drawing.Rectangle ($cssWidth + 10), 0, $cssWidth, $H), (New-Object System.Drawing.Rectangle 0, 0, ($cssWidth * 2), ($H * 2)), [System.Drawing.GraphicsUnit]::Pixel)
$bmp.Save($out, [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose(); $bmp.Dispose(); $img.Dispose(); $ref.Dispose()
