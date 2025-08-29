<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>CDN Proxy Test</title>
</head>
<body>
  <h1>CDN Proxy 生成</h1>
  <h2>新パターン (JavaScript)</h2>
  <div>
    <label>取得したいURL: <input type="text" id="urlInput" size="60" value="https://ai-gazoukan.com/wp/wp-content/uploads/2025/08/00000-3199285662.jpg"></label>
    <button type="button" onclick="redirectToUrl()">取得</button>
  </div>

  <div id="generatedUrl" style="display:none; margin-top: 20px;">
    <h2>生成されたURL</h2>
    <div style="background-color: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0;">
      <p id="urlOutput" style="word-break: break-all; margin: 0;"></p>
    </div>
    <button type="button" onclick="copyToClipboard()">URLをコピー</button>
  </div>

  <script>
    function redirectToUrl() {
      const url = document.getElementById('urlInput').value;
      
      if (!url) {
        alert('URLを入力してください');
        return;
      }
      
      // URLをエンコードしてプロキシURLを生成
      const encodedUrl = encodeURIComponent(url);
      const proxyUrl = `https://blogkan.com/cdn/url/${encodedUrl}`;
      
      // 生成されたURLを表示
      document.getElementById('urlOutput').textContent = proxyUrl;
      document.getElementById('generatedUrl').style.display = 'block';
    }
    
    function copyToClipboard() {
      const urlOutput = document.getElementById('urlOutput');
      const textToCopy = urlOutput.textContent;
      
      // クリップボードにコピー
      navigator.clipboard.writeText(textToCopy).then(function() {
        alert('URLをクリップボードにコピーしました！');
      }).catch(function(err) {
        // 古いブラウザ対応のフォールバック
        const textArea = document.createElement('textarea');
        textArea.value = textToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('URLをクリップボードにコピーしました！');
      });
    }
  </script>
</body>
</html>
