param([string]$src, [string]$points)
Add-Type -AssemblyName System.Drawing
$img = New-Object System.Drawing.Bitmap ((Resolve-Path $src).Path)
foreach ($p in $points.Split(';')) {
    $name, $xy = $p.Split('=')
    $x, $y = $xy.Split(',') | ForEach-Object { [int]$_ }
    $c = $img.GetPixel($x, $y)
    '{0,-14} ({1},{2}) #{3:x2}{4:x2}{5:x2}' -f $name, $x, $y, $c.R, $c.G, $c.B
}
$img.Dispose()
