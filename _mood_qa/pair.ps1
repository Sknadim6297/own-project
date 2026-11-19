param([string]$cur, [string]$out, [int]$x, [int]$y, [int]$w, [int]$h, [double]$scale = 2)
# Stacks reference (top) over current (bottom) for a rect given in reference (1024px) coordinates.
Add-Type -AssemblyName System.Drawing
$ref = [System.Drawing.Image]::FromFile((Resolve-Path '_mood_qa/reference.jpg'))
$img = [System.Drawing.Image]::FromFile((Resolve-Path $cur))
$k = $img.Width / 1024.0
$W = [int]($w * $scale); $H = [int]($h * $scale)
$bmp = New-Object System.Drawing.Bitmap $W, ($H * 2 + 4)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.Clear([System.Drawing.Color]::Red)
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.DrawImage($ref, (New-Object System.Drawing.Rectangle 0, 0, $W, $H), (New-Object System.Drawing.Rectangle $x, $y, $w, $h), [System.Drawing.GraphicsUnit]::Pixel)
$g.DrawImage($img, (New-Object System.Drawing.RectangleF 0, ($H + 4), $W, $H), (New-Object System.Drawing.RectangleF ($x * $k), ($y * $k), ($w * $k), ($h * $k)), [System.Drawing.GraphicsUnit]::Pixel)
$bmp.Save($out, [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose(); $bmp.Dispose(); $img.Dispose(); $ref.Dispose()
