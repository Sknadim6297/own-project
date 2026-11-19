param([string]$src, [string]$rects)
Add-Type -AssemblyName System.Drawing
$img = New-Object System.Drawing.Bitmap ((Resolve-Path $src).Path)
foreach ($r in $rects.Split(';')) {
    $name, $box = $r.Split('=')
    $x, $y, $w, $h = $box.Split(',') | ForEach-Object { [int]$_ }
    $best = 999; $bc = $null
    for ($i = $x; $i -lt $x + $w; $i++) { for ($j = $y; $j -lt $y + $h; $j++) {
        $c = $img.GetPixel($i, $j); $l = $c.R + $c.G + $c.B
        if ($l -lt $best) { $best = $l; $bc = $c }
    } }
    '{0,-12} #{1:x2}{2:x2}{3:x2}' -f $name, $bc.R, $bc.G, $bc.B
}
$img.Dispose()
