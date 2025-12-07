This folder stores uploaded images referenced by the sample data.

What I changed:
- The sample SQL now references the image path 'uploads/ficus1.jpg' (see docker/sql/insert_sample_data.sql).

Place the image you provided here as 'ficus1.jpg' so the sample record points to it.

Windows (cmd.exe) example to create the folder and copy a file from your Downloads:
1) Open cmd.exe in the project root (C:\Users\filip\Desktop\School\VAII\Semestralka)
2) Create the uploads folder (if missing):
   mkdir public\uploads
3) Copy the image into the uploads folder (adjust source path):
   copy "C:\path\to\your\image.jpg" "public\uploads\ficus1.jpg"

PowerShell (if you have the image as a base64 string):
1) Save the base64 string into a file base64.txt, then run in PowerShell:
   $b = Get-Content -Raw .\base64.txt
   [System.IO.File]::WriteAllBytes('public\uploads\ficus1.jpg', [System.Convert]::FromBase64String($b))

Linux / WSL example (from project root):
   mkdir -p public/uploads
   cp /path/to/image.jpg public/uploads/ficus1.jpg

After placing the file:
- Restart or refresh your app (if needed). The sample DB image path points to 'uploads/ficus1.jpg' so the app will serve it from /uploads/ficus1.jpg (ensure your web server serves the public/uploads directory).

If you'd like, you can paste the image as a base64 string here and I will write it into public/uploads/ficus1.jpg for you.

