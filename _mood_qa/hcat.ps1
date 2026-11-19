param([string]$a,[string]$b,[string]$out)
Add-Type -AssemblyName System.Drawing
$ia=[System.Drawing.Image]::FromFile((Resolve-Path $a)); $ib=[System.Drawing.Image]::FromFile((Resolve-Path $b))
$bmp=New-Object System.Drawing.Bitmap ($ia.Width+$ib.Width+6),([Math]::Max($ia.Height,$ib.Height))
$g=[System.Drawing.Graphics]::FromImage($bmp); $g.Clear([System.Drawing.Color]::Red)
$g.DrawImage($ia,0,0,$ia.Width,$ia.Height); $g.DrawImage($ib,$ia.Width+6,0,$ib.Width,$ib.Height)
$bmp.Save($out,[System.Drawing.Imaging.ImageFormat]::Png); $g.Dispose(); $bmp.Dispose(); $ia.Dispose(); $ib.Dispose()