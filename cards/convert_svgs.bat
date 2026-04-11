@echo off
REM Batch converter: converts all SVGs in cards\art to PNG using ImageMagick (magick)
REM Run this from the cards folder: double-click or from cmd: convert_svgs.bat

pushd "%~dp0art"
where magick >nul 2>&1
if errorlevel 1 (
  echo ERROR: magick.exe not found in PATH. Install ImageMagick and ensure "magick" is available.
  pause
  popd
  exit /b 1
)

for %%f in (*.svg) do (
  echo Converting %%f...
  magick -density 300 "%%f" "%%~nf.png"
  if errorlevel 1 echo Failed converting %%f
)

echo Conversion complete. PNG files saved to %cd%
pause
popd
