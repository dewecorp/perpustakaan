<?php
require_once __DIR__ . '/config/config.php';
// require_login(); // Allow public access

$pdo = db();
$path = $_GET['path'] ?? '';
$path = str_replace(['..', '\\'], ['', '/'], $path);
$allowedPrefix = 'assets/uploads/books/';

if (!$path || strpos($path, $allowedPrefix) !== 0) {
  http_response_code(403);
  echo 'Akses ditolak';
  exit;
}

$fullPath = __DIR__ . '/' . $path;

if (!file_exists($fullPath)) {
  http_response_code(404);
  echo 'File tidak ditemukan';
  exit;
}

// Log visit (Melihat Buku)
try {
    // Get book title
    $stmt = $pdo->prepare("SELECT title FROM books WHERE book_path = ?");
    $stmt->execute([$path]);
    $book = $stmt->fetch();
    $title = $book['title'] ?? basename($path);

    // Determine visitor name
    $visitorName = isset($_SESSION['user']) ? $_SESSION['user']['username'] : 'Tamu (' . $_SERVER['REMOTE_ADDR'] . ')';

    // Insert log
    $stmtLog = $pdo->prepare("INSERT INTO visitors (name, purpose) VALUES (?, ?)");
    $stmtLog->execute([$visitorName, "Melihat Buku: " . $title]);

    // Increment views
    $stmtView = $pdo->prepare("UPDATE books SET views = views + 1 WHERE book_path = ?");
    $stmtView->execute([$path]);
} catch (Exception $e) {
    // Ignore logging errors to not break functionality
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
  <title>Pratinjau Buku - PUSDIGI</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    html, body { height: 100%; margin: 0; padding: 0; background: #525659; font-family: sans-serif; }
    .container { display: flex; flex-direction: column; height: 100%; }
    .toolbar {
        background: #333; color: white; padding: 10px; display: flex; justify-content: center; align-items: center; gap: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 10;
    }
    .toolbar button {
        background: #444; border: 1px solid #666; color: white; padding: 5px 15px; border-radius: 4px; cursor: pointer;
    }
    .toolbar button:hover { background: #555; }
    .toolbar span {
        font-size: 14px;
        margin: 0 5px;
    }
    .toolbar button:disabled { opacity: 0.5; cursor: not-allowed; }
    .pdf-container {
        flex: 1; overflow: auto; display: flex; justify-content: center; padding: 20px; position: relative;
    }
    #the-canvas {
        box-shadow: 0 0 10px rgba(0,0,0,0.5); background: white;
    }
    .loading-overlay {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); color: white; display: flex; 
        justify-content: center; align-items: center; z-index: 5;
        font-size: 1.2em; pointer-events: none;
    }
    .error-log {
        position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,0,0,0.8); 
        color: white; padding: 10px; font-size: 12px; max-height: 100px; 
        overflow: auto; display: none; z-index: 100;
    }
    .loading { color: white; text-align: center; margin-top: 50px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="toolbar">
        <button id="prev" title="Halaman Sebelumnya"><i class="fas fa-chevron-left"></i></button>
        <span><span id="page_num"></span> / <span id="page_count"></span></span>
        <button id="next" title="Halaman Selanjutnya"><i class="fas fa-chevron-right"></i></button>
        
        <div style="width: 1px; height: 20px; background: #666; margin: 0 10px;"></div>
        
        <button id="zoom_out" title="Perkecil"><i class="fas fa-search-minus"></i></button>
        <span id="zoom_level" style="font-size: 0.9em; min-width: 45px; text-align: center;">100%</span>
        <button id="zoom_in" title="Perbesar"><i class="fas fa-search-plus"></i></button>
        
        <div style="width: 1px; height: 20px; background: #666; margin: 0 10px;"></div>
        
        <button id="fullscreen" title="Layar Penuh"><i class="fas fa-expand"></i></button>
    </div>
    
    <div class="pdf-container">
        <div id="loading" class="loading">Memuat dokumen...</div>
        <div id="render-loading" class="loading-overlay" style="display:none">Memuat Ulang...</div>
        <canvas id="the-canvas" style="display:none"></canvas>
    </div>
    <div id="error-log" class="error-log"></div>
  </div>

  <script>
    // Disable IDM injection if possible
    window.IDM_download = false;
    
    // Debug helper
    function logError(msg) {
        var el = document.getElementById('error-log');
        el.style.display = 'block';
        el.innerHTML += '<div>' + new Date().toLocaleTimeString() + ': ' + msg + '</div>';
        console.error(msg);
    }
    
    var pdfjsLib = window['pdfjs-dist/build/pdf'];
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

    var pdfDoc = null,
        pageNum = 1,
        pageRendering = false,
        pageNumPending = null,
        scale = 1.0,
        canvas = document.getElementById('the-canvas'),
        ctx = canvas.getContext('2d'),
        renderTask = null;

    const filePath = '<?php echo $path; ?>';
    // Base64 encode the path for the URL to avoid .pdf extension visibility
    const encodedPath = btoa(filePath);
    const getUrl = 'preview_book_get.php?data=' + encodeURIComponent(encodedPath);

    /**
     * Get page info from document, resize canvas accordingly, and render page.
     */
    function renderPage(num) {
      if (!pdfDoc) return;

      pageRendering = true;
      document.getElementById('render-loading').style.display = 'flex';
      
      // Update Zoom Level Display
      document.getElementById('zoom_level').textContent = Math.round(scale * 100) + '%';

      // Cancel previous render if any
      if (renderTask) {
          try {
              renderTask.cancel();
          } catch (e) { logError('Cancel error: ' + e.message); }
          renderTask = null;
      }

      // Fetch page
      pdfDoc.getPage(num).then(function(page) {
        var viewport = page.getViewport({scale: scale});
        
        // Force explicit dimensions
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        canvas.style.height = viewport.height + 'px';
        canvas.style.width = viewport.width + 'px';

        // Render PDF page into canvas context
        var renderContext = {
          canvasContext: ctx,
          viewport: viewport
        };
        
        renderTask = page.render(renderContext);

        // Wait for render to finish
        renderTask.promise.then(function() {
          pageRendering = false;
          renderTask = null;
          document.getElementById('render-loading').style.display = 'none';
          
          if (pageNumPending !== null) {
            renderPage(pageNumPending);
            pageNumPending = null;
          }
        }).catch(function(error) {
            if (error.name === 'RenderingCancelledException') {
                // Expected behavior
                return;
            }
            document.getElementById('render-loading').style.display = 'none';
            logError('Render error: ' + error.message);
        });
      }).catch(function(error) {
          document.getElementById('render-loading').style.display = 'none';
          logError('GetPage error: ' + error.message);
      });

      // Update page counters
      document.getElementById('page_num').textContent = num;

      // Update buttons
      document.getElementById('prev').disabled = num <= 1;
      document.getElementById('next').disabled = num >= pdfDoc.numPages;
    }

    /**
     * If another page rendering in progress, waits until the rendering is
     * finised. Otherwise, executes rendering immediately.
     */
    function queueRenderPage(num) {
      if (pageRendering) {
        pageNumPending = num;
      } else {
        renderPage(num);
      }
    }

    /**
     * Displays previous page.
     */
    function onPrevPage() {
      if (pageNum <= 1) {
        return;
      }
      pageNum--;
      queueRenderPage(pageNum);
    }
    document.getElementById('prev').addEventListener('click', onPrevPage);

    /**
     * Displays next page.
     */
    function onNextPage() {
      if (pageNum >= pdfDoc.numPages) {
        return;
      }
      pageNum++;
      queueRenderPage(pageNum);
    }
    document.getElementById('next').addEventListener('click', onNextPage);

    /**
     * Zoom controls
     */
    document.getElementById('zoom_in').addEventListener('click', function() {
        scale = Math.round((scale + 0.2) * 10) / 10;
        if (scale > 3.0) scale = 3.0; // Max zoom
        renderPage(pageNum);
    });
    
    document.getElementById('zoom_out').addEventListener('click', function() {
        if (scale > 0.4) {
            scale = Math.round((scale - 0.2) * 10) / 10;
            if (scale < 0.4) scale = 0.4; // Min zoom
            renderPage(pageNum);
        }
    });

    document.getElementById('fullscreen').addEventListener('click', function() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    });

    /**
      * Asynchronously downloads PDF.
      */
     // Fetch text (base64) then convert to Uint8Array
     fetch(getUrl)
         .then(response => {
             if (!response.ok) throw new Error('Network response was not ok');
             return response.text();
         })
         .then(base64 => {
             // Convert base64 to binary
             var binaryString = window.atob(base64);
             var len = binaryString.length;
             var bytes = new Uint8Array(len);
             for (var i = 0; i < len; i++) {
                 bytes[i] = binaryString.charCodeAt(i);
             }
             return bytes;
         })
         .then(data => {
             var loadingTask = pdfjsLib.getDocument({data: data});
             return loadingTask.promise;
         })
        .then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            document.getElementById('page_count').textContent = pdfDoc.numPages;
            document.getElementById('loading').style.display = 'none';
            document.getElementById('the-canvas').style.display = 'block';

            renderPage(pageNum);
        })
        .catch(function(error) {
             document.getElementById('loading').innerHTML = '<div style="color:#ff6b6b">Gagal memuat dokumen.<br>' + error.message + '</div>';
             console.error('Error loading PDF:', error);
        });
  </script>
</body>
</html>

